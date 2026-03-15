import type { PropsWithChildren } from "react";

import { cn } from "@/lib/utils";

export function Card({ className, children }: PropsWithChildren<{ className?: string }>) {
  return (
    <div
      className={cn(
        "rounded-[28px] border border-black/10 bg-white/80 p-6 shadow-[0_16px_60px_rgba(20,33,61,0.08)] backdrop-blur",
        className,
      )}
    >
      {children}
    </div>
  );
}

export function CardTitle({ className, children }: PropsWithChildren<{ className?: string }>) {
  return <h3 className={cn("text-lg font-semibold tracking-tight text-[var(--foreground)]", className)}>{children}</h3>;
}

export function CardDescription({
  className,
  children,
}: PropsWithChildren<{ className?: string }>) {
  return <p className={cn("mt-2 text-sm leading-6 text-[var(--muted-foreground)]", className)}>{children}</p>;
}
