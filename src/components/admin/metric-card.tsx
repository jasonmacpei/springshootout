import { Card, CardDescription, CardTitle } from "@/components/ui/card";

export function MetricCard({
  label,
  value,
  detail,
}: {
  label: string;
  value: string;
  detail: string;
}) {
  return (
    <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10">
      <CardDescription className="mt-0 text-[#9fb2ce]">{label}</CardDescription>
      <CardTitle className="mt-3 text-4xl text-white">{value}</CardTitle>
      <p className="mt-3 text-sm text-[#9fb2ce]">{detail}</p>
    </Card>
  );
}
