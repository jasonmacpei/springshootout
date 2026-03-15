import type { PropsWithChildren } from "react";

import { cn } from "@/lib/utils";

export function Badge({ className, children }: PropsWithChildren<{ className?: string }>) {
  return (
    <span
      className={cn(
        "inline-flex items-center rounded-full border border-black/10 bg-white/70 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-[var(--muted-foreground)]",
        className,
      )}
    >
      {children}
    </span>
  );
}
