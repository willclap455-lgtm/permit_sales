import Link from "next/link";
import type { AnchorHTMLAttributes, ButtonHTMLAttributes, ReactNode } from "react";

type Variant = "primary" | "secondary" | "ghost" | "dark";
type Size = "sm" | "md" | "lg";

const variants: Record<Variant, string> = {
  primary: "bg-coral-500 text-ink-950 shadow-glow hover:-translate-y-0.5 hover:bg-coral-400",
  secondary: "border border-ink-950/10 bg-white/70 text-ink-950 hover:-translate-y-0.5 hover:bg-white",
  ghost: "text-ink-700 hover:bg-ink-950/5",
  dark: "bg-ink-950 text-cream-50 shadow-xl hover:-translate-y-0.5 hover:bg-ink-800"
};

const base =
  "inline-flex items-center justify-center rounded-full text-sm font-bold transition duration-300 focus:outline-none focus:ring-4 focus:ring-coral-300/50";

const sizes: Record<Size, string> = {
  sm: "px-4 py-2",
  md: "px-5 py-3",
  lg: "px-7 py-4"
};

type ButtonProps = ButtonHTMLAttributes<HTMLButtonElement> & {
  children: ReactNode;
  variant?: Variant;
  size?: Size;
};

export function Button({ children, variant = "primary", size = "md", className = "", ...props }: ButtonProps) {
  return (
    <button className={`${base} ${sizes[size]} ${variants[variant]} ${className}`} {...props}>
      {children}
    </button>
  );
}

type LinkButtonProps = AnchorHTMLAttributes<HTMLAnchorElement> & {
  children: ReactNode;
  href: string;
  variant?: Variant;
  size?: Size;
};

export function LinkButton({ children, href, variant = "primary", size = "md", className = "", ...props }: LinkButtonProps) {
  const classes = `${base} ${sizes[size]} ${variants[variant]} ${className}`;
  if (href.startsWith("http")) {
    return (
      <a href={href} className={classes} {...props}>
        {children}
      </a>
    );
  }

  return (
    <Link href={href} className={classes}>
      {children}
    </Link>
  );
}
