import type { InputHTMLAttributes } from "react";

type FieldProps = InputHTMLAttributes<HTMLInputElement> & {
  label: string;
};

export function Field({ label, ...props }: FieldProps) {
  return (
    <label className="grid gap-2 text-sm text-ink/70">
      <span>{label}</span>
      <input
        {...props}
        className="rounded-2xl border border-ink/10 bg-white/80 px-4 py-3 text-base text-ink shadow-inner outline-none transition focus:border-coral focus:ring-4 focus:ring-coral/15"
      />
    </label>
  );
}
