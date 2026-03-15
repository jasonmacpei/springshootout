import type { ReactNode } from "react";

import { Card, CardDescription, CardTitle } from "@/components/ui/card";

export function EmptyState({
  title,
  description,
  action,
}: {
  title: string;
  description: string;
  action?: ReactNode;
}) {
  return (
    <Card className="border-dashed border-black/20 bg-white/70 text-center">
      <CardTitle>{title}</CardTitle>
      <CardDescription>{description}</CardDescription>
      {action ? <div className="mt-4">{action}</div> : null}
    </Card>
  );
}
