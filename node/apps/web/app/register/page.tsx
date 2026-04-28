import Link from "next/link";
import { Button } from "../../components/ui/Button";
import { Field } from "../../components/ui/Field";
import { Panel } from "../../components/ui/Panel";

export default function RegisterPage() {
  return (
    <main className="min-h-screen bg-[radial-gradient(circle_at_20%_10%,rgba(255,122,69,0.18),transparent_28%),#111111] px-6 py-10 text-white">
      <div className="mx-auto grid min-h-[calc(100vh-5rem)] max-w-6xl items-center gap-12 lg:grid-cols-[0.95fr_1.05fr]">
        <section>
          <Link href="/" className="text-sm uppercase tracking-[0.35em] text-white/50">
            PermitSales
          </Link>
          <h1 className="mt-8 max-w-xl text-5xl font-semibold tracking-[-0.05em] md:text-7xl">
            Start a monthly parking account.
          </h1>
          <p className="mt-6 max-w-lg text-lg leading-8 text-white/62">
            Register once, add your vehicle details, and keep payment credentials ready for your monthly permit.
          </p>
          <div className="mt-8 rounded-[2rem] border border-white/10 bg-white/[0.04] p-5 text-sm text-white/60">
            Vehicle onboarding only asks for make, model, color, and license plate. VINs are intentionally excluded.
          </div>
        </section>

        <Panel className="bg-white p-8 text-ink shadow-2xl shadow-orange/20 md:p-10">
          <h2 className="text-3xl font-semibold tracking-[-0.04em]">Create account</h2>
          <form className="mt-8 space-y-5">
            <Field label="Full name" placeholder="Avery Stone" />
            <Field label="Email" type="email" placeholder="avery@example.com" />
            <Field label="Phone" placeholder="(555) 012-9087" />
            <Field label="Password" type="password" placeholder="At least 8 characters" />
            <Button className="w-full">Register monthly account</Button>
          </form>
          <p className="mt-6 text-sm text-ink/55">
            Already registered?{" "}
            <Link href="/login" className="font-semibold text-orange">
              Sign in
            </Link>
          </p>
        </Panel>
      </div>
    </main>
  );
}
