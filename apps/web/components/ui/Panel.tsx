import type { HTMLAttributes, ReactNode } from "react";

type PanelProps = HTMLAttributes<HTMLElement> & {
  children: ReactNode;
};

export function Panel({ children, className = "", ...props }: PanelProps) {
  return (
    <section
      className={`relative rounded-[2rem] border border-white/60 bg-white/70 p-6 shadow-soft backdrop-blur ${className}`}
      {...props}
    >
      {children}
    </section>
  );
}
