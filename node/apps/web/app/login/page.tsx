import Link from "next/link";
import { Button } from "../../components/ui/Button";
import { Field } from "../../components/ui/Field";
import { Panel } from "../../components/ui/Panel";

export default function LoginPage() {
  return (
    <main className="min-h-screen bg-[#08070c] px-6 py-10 text-white">
      <div className="mx-auto grid min-h-[calc(100vh-5rem)] max-w-6xl items-center gap-12 lg:grid-cols-[0.9fr_1fr]">
        <section>
          <Link href="/" className="text-sm uppercase tracking-[0.35em] text-white/50">
            PermitSales
          </Link>
          <h1 className="mt-12 text-5xl font-semibold leading-tight md:text-7xl">
            Welcome back to your monthly parking studio.
          </h1>
          <p className="mt-6 max-w-xl text-lg text-white/60">
            Manage vehicles, review saved payment methods, and keep your monthly permit profile beautifully organized.
          </p>
        </section>
        <Panel className="p-8 md:p-10">
          <div className="mb-8">
            <p className="text-sm uppercase tracking-[0.28em] text-amber-200/70">Secure login</p>
            <h2 className="mt-3 text-3xl font-semibold">Enter the garage</h2>
          </div>
          <form className="space-y-5">
            <Field label="Email" name="email" type="email" placeholder="you@example.com" />
            <Field label="Password" name="password" type="password" placeholder="••••••••" />
            <Button className="w-full justify-center">Login</Button>
          </form>
          <p className="mt-6 text-center text-sm text-white/55">
            New monthly parker?{" "}
            <Link href="/register" className="text-amber-200">
              Create your account
            </Link>
          </p>
        </Panel>
      </div>
    </main>
  );
}
