import type { ReactNode } from "react";

import { Badge } from "@/components/ui/badge";

export function PageHero({
  eyebrow,
  title,
  description,
  actions,
}: {
  eyebrow: string;
  title: string;
  description: ReactNode;
  actions?: ReactNode;
}) {
  return (
    <section className="mx-auto max-w-7xl px-6 pt-16 pb-10 lg:px-10">
      <Badge>{eyebrow}</Badge>
      <div className="mt-6 grid gap-6 lg:grid-cols-[1.5fr_1fr] lg:items-end">
        <div>
          <h1 className="max-w-4xl text-5xl font-black uppercase tracking-[-0.04em] text-[var(--foreground)] sm:text-6xl">
            {title}
          </h1>
          <p className="mt-5 max-w-2xl text-base leading-8 text-[var(--muted-foreground)] sm:text-lg">{description}</p>
        </div>
        {actions ? <div className="flex flex-wrap gap-3 lg:justify-end">{actions}</div> : null}
      </div>
    </section>
  );
}
