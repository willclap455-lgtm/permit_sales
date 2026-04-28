"use client";

import { motion } from "framer-motion";
import { ArrowUpRight, CarFront, CreditCard, ShieldCheck, Sparkles } from "lucide-react";
import Link from "next/link";
import { LinkButton } from "../ui/Button";

const dayPassUrl = "https://permitsales.com/daily-pass";

export function Hero() {
  return (
    <section className="relative overflow-hidden px-6 py-8 sm:px-10 lg:px-16">
      <div className="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(245,123,83,0.24),transparent_28%),radial-gradient(circle_at_82%_14%,rgba(49,92,255,0.20),transparent_24%),radial-gradient(circle_at_50%_80%,rgba(255,230,109,0.16),transparent_30%)]" />
      <div className="relative mx-auto flex min-h-[calc(100vh-4rem)] max-w-7xl flex-col">
        <nav className="flex items-center justify-between py-5">
          <Link href="/" className="group flex items-center gap-3">
            <span className="grid size-11 place-items-center rounded-2xl bg-ink text-white shadow-soft transition-transform group-hover:-rotate-6">
              <CarFront size={22} />
            </span>
            <span className="text-lg font-semibold tracking-tight text-ink">PermitSales</span>
          </Link>
          <div className="hidden items-center gap-3 sm:flex">
            <Link href="/login" className="text-sm font-medium text-ink/70 transition hover:text-ink">
              Log in
            </Link>
            <LinkButton href="/register" size="sm">Create monthly account</LinkButton>
          </div>
        </nav>

        <div className="grid flex-1 items-center gap-12 py-12 lg:grid-cols-[1.05fr_0.95fr]">
          <motion.div initial={{ opacity: 0, y: 24 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.7 }}>
            <div className="mb-7 inline-flex items-center gap-2 rounded-full border border-ink/10 bg-white/65 px-4 py-2 text-sm text-ink/70 shadow-soft backdrop-blur">
              <Sparkles size={16} className="text-coral" />
              Monthly permits, curated for modern mobility.
            </div>
            <h1 className="max-w-4xl text-6xl font-semibold leading-[0.92] tracking-[-0.06em] text-ink sm:text-7xl lg:text-8xl">
              Parking permits with a little more polish.
            </h1>
            <p className="mt-7 max-w-2xl text-lg leading-8 text-ink/68 sm:text-xl">
              Manage vehicles, monthly permit accounts, and secure payment cards in a calm, crafted workspace built for drivers and operators.
            </p>
            <div className="mt-10 grid gap-4 sm:flex">
              <LinkButton href="/register" size="lg">Register monthly account</LinkButton>
              <LinkButton href={dayPassUrl} variant="secondary" size="lg" target="_blank" rel="noreferrer">
                Buy single-day pass <ArrowUpRight size={18} />
              </LinkButton>
            </div>
          </motion.div>

          <motion.div
            initial={{ opacity: 0, scale: 0.96, rotate: 2 }}
            animate={{ opacity: 1, scale: 1, rotate: 0 }}
            transition={{ duration: 0.75, delay: 0.1 }}
            className="relative"
          >
            <div className="absolute -inset-6 rounded-[3rem] bg-gradient-to-br from-coral/30 via-sun/20 to-blueprint/25 blur-2xl" />
            <div className="relative rounded-[2.5rem] border border-white/70 bg-white/72 p-5 shadow-soft backdrop-blur-xl">
              <div className="rounded-[2rem] bg-ink p-5 text-white">
                <div className="flex items-center justify-between">
                  <p className="text-sm text-white/60">Monthly Account</p>
                  <span className="rounded-full bg-mint px-3 py-1 text-xs font-semibold text-ink">Active</span>
                </div>
                <div className="mt-10 rounded-3xl bg-white/10 p-5">
                  <p className="text-sm text-white/50">Primary vehicle</p>
                  <p className="mt-3 text-3xl font-semibold tracking-tight">Tesla Model 3</p>
                  <p className="mt-1 text-white/60">Pearl - PS-2048</p>
                </div>
                <div className="mt-4 grid grid-cols-2 gap-4">
                  <div className="rounded-3xl bg-coral p-5 text-ink">
                    <CreditCard />
                    <p className="mt-8 text-sm font-medium">Card ending 4242</p>
                  </div>
                  <div className="rounded-3xl bg-sun p-5 text-ink">
                    <ShieldCheck />
                    <p className="mt-8 text-sm font-medium">Encrypted vault</p>
                  </div>
                </div>
              </div>
              <div className="mt-5 grid grid-cols-3 gap-3">
                {["Vehicles", "Cards", "Support"].map((label) => (
                  <div key={label} className="rounded-2xl bg-white/70 px-4 py-5 text-center text-sm font-medium text-ink/70">
                    {label}
                  </div>
                ))}
              </div>
            </div>
          </motion.div>
        </div>
      </div>
    </section>
  );
}
