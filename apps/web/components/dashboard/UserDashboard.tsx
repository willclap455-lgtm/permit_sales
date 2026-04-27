"use client";

import { motion } from "framer-motion";
import { Car, CreditCard, ShieldCheck, Sparkles } from "lucide-react";
import { Button } from "../ui/Button";
import { Field } from "../ui/Field";
import { Panel } from "../ui/Panel";

const vehicles = [
  { make: "Audi", model: "Q5", color: "Moonstone", plate: "PS-2048" },
  { make: "Tesla", model: "Model 3", color: "Obsidian", plate: "EV-7712" }
];

const cards = [
  { brand: "Visa", lastFour: "1842", default: true },
  { brand: "Amex", lastFour: "0091", default: false }
];

export function UserDashboard() {
  return (
    <main className="min-h-screen bg-[#f7efe4] px-6 py-8 text-ink">
      <div className="mx-auto grid max-w-7xl gap-6 lg:grid-cols-[280px_1fr]">
        <aside className="rounded-[2rem] bg-ink p-6 text-cream shadow-soft">
          <div className="flex items-center gap-3">
            <div className="grid size-11 place-items-center rounded-2xl bg-coral text-ink">
              <Sparkles size={20} />
            </div>
            <div>
              <p className="font-display text-2xl">PermitSales</p>
              <p className="text-xs uppercase tracking-[0.3em] text-cream/50">Monthly account</p>
            </div>
          </div>
          <nav className="mt-10 space-y-2 text-sm">
            {["Overview", "Vehicles", "Payment methods", "Permit history"].map((item, index) => (
              <a
                key={item}
                className={`block rounded-2xl px-4 py-3 transition hover:bg-white/10 ${
                  index === 0 ? "bg-white/15 text-white" : "text-cream/65"
                }`}
                href={`#${item.toLowerCase().replaceAll(" ", "-")}`}
              >
                {item}
              </a>
            ))}
          </nav>
        </aside>

        <section className="space-y-6">
          <Panel className="overflow-hidden bg-cream p-8">
            <div className="absolute right-8 top-6 hidden rounded-full bg-lagoon/10 px-4 py-2 text-sm font-semibold text-lagoon md:block">
              Active monthly permit
            </div>
            <p className="eyebrow">Welcome back</p>
            <h1 className="mt-3 max-w-2xl font-display text-5xl leading-[0.92] md:text-7xl">
              Your parking account, tuned like a private studio.
            </h1>
            <div className="mt-8 grid gap-4 md:grid-cols-3">
              {[
                ["2", "Registered vehicles", Car],
                ["1", "Default card", CreditCard],
                ["Secure", "Encrypted billing vault", ShieldCheck]
              ].map(([value, label, Icon]) => (
                <motion.div
                  key={label as string}
                  whileHover={{ y: -4 }}
                  className="rounded-[1.5rem] border border-ink/10 bg-white/60 p-5"
                >
                  <Icon className="text-coral" size={22} />
                  <p className="mt-5 font-display text-4xl">{value as string}</p>
                  <p className="text-sm text-ink/55">{label as string}</p>
                </motion.div>
              ))}
            </div>
          </Panel>

          <div className="grid gap-6 xl:grid-cols-2">
            <Panel id="vehicles" className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="eyebrow">Vehicle management</p>
                  <h2 className="mt-2 font-display text-4xl">Garage</h2>
                </div>
                <Button size="sm">Add vehicle</Button>
              </div>
              <div className="mt-6 space-y-3">
                {vehicles.map((vehicle) => (
                  <div key={vehicle.plate} className="rounded-3xl border border-ink/10 bg-cream/70 p-5">
                    <div className="flex items-center justify-between">
                      <div>
                        <p className="font-semibold">
                          {vehicle.color} {vehicle.make} {vehicle.model}
                        </p>
                        <p className="mt-1 text-sm text-ink/55">Plate {vehicle.plate}</p>
                      </div>
                      <Button variant="ghost" size="sm">Edit</Button>
                    </div>
                  </div>
                ))}
              </div>
              <form className="mt-6 grid gap-3 md:grid-cols-2">
                <Field label="Make" placeholder="Honda" />
                <Field label="Model" placeholder="Civic" />
                <Field label="Color" placeholder="Blue" />
                <Field label="License plate" placeholder="ABC-1234" />
              </form>
            </Panel>

            <Panel id="payment-methods" className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="eyebrow">Payment management</p>
                  <h2 className="mt-2 font-display text-4xl">Card vault</h2>
                </div>
                <Button size="sm">Add card</Button>
              </div>
              <div className="mt-6 space-y-3">
                {cards.map((card) => (
                  <div key={card.lastFour} className="rounded-3xl bg-ink p-5 text-cream">
                    <div className="flex items-center justify-between">
                      <p className="font-semibold">{card.brand} ending in {card.lastFour}</p>
                      {card.default && <span className="rounded-full bg-coral px-3 py-1 text-xs text-ink">Default</span>}
                    </div>
                    <p className="mt-5 text-xs uppercase tracking-[0.3em] text-cream/45">AES-256-GCM encrypted</p>
                  </div>
                ))}
              </div>
              <form className="mt-6 grid gap-3">
                <Field label="Cardholder name" placeholder="Avery Stone" />
                <Field label="Card number" placeholder="•••• •••• •••• ••••" />
                <div className="grid gap-3 md:grid-cols-3">
                  <Field label="Exp. month" placeholder="08" />
                  <Field label="Exp. year" placeholder="2029" />
                  <Field label="CVC" placeholder="•••" />
                </div>
              </form>
            </Panel>
          </div>
        </section>
      </div>
    </main>
  );
}
