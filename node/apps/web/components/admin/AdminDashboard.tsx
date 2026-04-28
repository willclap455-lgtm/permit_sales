"use client";

import { motion } from "framer-motion";
import { Activity, BadgeDollarSign, CarFront, Shield, Users } from "lucide-react";
import { Panel } from "../ui/Panel";

const metrics = [
  { label: "Monthly users", value: "1,284", icon: Users, tint: "text-ink" },
  { label: "Registered vehicles", value: "2,917", icon: CarFront, tint: "text-lagoon" },
  { label: "Cards on file", value: "1,102", icon: BadgeDollarSign, tint: "text-coral" },
  { label: "Permit renewals", value: "94%", icon: Activity, tint: "text-graphite" }
];

const users = [
  ["Maya Collins", "maya@example.com", "User", "Active"],
  ["Nolan Reed", "nolan@example.com", "Admin", "Active"],
  ["Iris Van", "iris@example.com", "User", "Review"]
];

const vehicles = [
  ["Maya Collins", "Toyota", "RAV4", "Graphite", "PERM 482"],
  ["Iris Van", "Honda", "Civic", "Pearl", "CITY 915"],
  ["Theo Akins", "Tesla", "Model 3", "Blue", "EV 3001"]
];

export function AdminDashboard() {
  return (
    <main className="min-h-screen bg-[#111827] px-6 py-8 text-white">
      <div className="mx-auto max-w-7xl">
        <nav className="mb-10 flex flex-wrap items-center justify-between gap-4">
          <div>
            <p className="mb-2 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs uppercase tracking-[0.24em] text-white/70">
              <Shield size={14} /> Admin console
            </p>
            <h1 className="font-display text-4xl font-semibold">PermitSales operations deck.</h1>
          </div>
          <button className="rounded-full bg-white px-5 py-3 text-sm font-semibold text-ink shadow-glow transition hover:-translate-y-0.5">
            Export report
          </button>
        </nav>

        <section className="grid gap-4 md:grid-cols-4">
          {metrics.map((metric, index) => (
            <motion.div
              key={metric.label}
              initial={{ opacity: 0, y: 18 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: index * 0.05 }}
              className="rounded-[2rem] border border-white/10 bg-white/[0.08] p-5 shadow-2xl backdrop-blur"
            >
              <metric.icon className="mb-6 text-sun" />
              <p className="text-3xl font-semibold">{metric.value}</p>
              <p className="text-sm text-white/60">{metric.label}</p>
            </motion.div>
          ))}
        </section>

        <section className="mt-8 grid gap-6 lg:grid-cols-[1.05fr_0.95fr]">
          <Panel className="border-white/10 bg-white text-ink">
            <div className="mb-5 flex items-center justify-between">
              <div>
                <p className="text-sm font-semibold uppercase tracking-[0.22em] text-coral">Users</p>
                <h2 className="font-display text-2xl font-semibold">Account directory</h2>
              </div>
              <span className="rounded-full bg-cream px-3 py-1 text-xs font-semibold">RBAC enabled</span>
            </div>
            <div className="overflow-hidden rounded-3xl border border-ink/10">
              {users.map((user) => (
                <div key={user[1]} className="grid grid-cols-4 gap-4 border-b border-ink/10 px-4 py-4 text-sm last:border-b-0">
                  <span className="font-semibold">{user[0]}</span>
                  <span className="text-graphite">{user[1]}</span>
                  <span>{user[2]}</span>
                  <span className="text-lagoon">{user[3]}</span>
                </div>
              ))}
            </div>
          </Panel>

          <Panel className="border-white/10 bg-white text-ink">
            <div className="mb-5">
              <p className="text-sm font-semibold uppercase tracking-[0.22em] text-lagoon">Vehicles</p>
              <h2 className="font-display text-2xl font-semibold">Registered fleet</h2>
            </div>
            <div className="space-y-3">
              {vehicles.map((vehicle) => (
                <div key={vehicle.join("-")} className="rounded-3xl bg-cream p-4">
                  <div className="flex items-center justify-between">
                    <p className="font-semibold">{vehicle[0]}</p>
                    <span className="rounded-full bg-white px-3 py-1 text-xs font-semibold">{vehicle[4]}</span>
                  </div>
                  <p className="mt-2 text-sm text-graphite">
                    {vehicle[3]} {vehicle[1]} {vehicle[2]}
                  </p>
                </div>
              ))}
            </div>
          </Panel>
        </section>
      </div>
    </main>
  );
}
