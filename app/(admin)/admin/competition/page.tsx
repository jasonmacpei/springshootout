import Link from "next/link";

import { Card, CardDescription, CardTitle } from "@/components/ui/card";
import { getCompetitionProvider } from "@/lib/competition";
import { getCompetitionEventSlugByLocalSlug } from "@/lib/db/queries/content";

const surfaces = [
  { href: "/admin/competition/schedule", label: "Schedule" },
  { href: "/admin/competition/results", label: "Results" },
  { href: "/admin/competition/standings", label: "Standings" },
  { href: "/admin/competition/pools", label: "Pools" },
  { href: "/admin/competition/playoffs", label: "Playoffs" },
];

export default async function AdminCompetitionPage() {
  const provider = getCompetitionProvider();
  const competitionEventSlug = await getCompetitionEventSlugByLocalSlug();
  const [schedule, results, standings] = await Promise.all([
    provider.getSchedule({ event: competitionEventSlug, status: "all", limit: 6 }),
    provider.getResults({ event: competitionEventSlug, workflow: "approved", limit: 12 }),
    provider.getStandings({ event: competitionEventSlug, limit: 24 }),
  ]);

  return (
    <div className="space-y-6">
      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        {surfaces.map((surface) => (
          <Link href={surface.href} key={surface.href}>
            <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10">
              <CardTitle className="text-white">{surface.label}</CardTitle>
              <CardDescription className="text-[#9fb2ce]">
                Read-only ops surface backed by the Hoops Scorebook adapter.
              </CardDescription>
            </Card>
          </Link>
        ))}
      </div>
      <div className="grid gap-4 md:grid-cols-3">
        <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10">
          <CardDescription className="text-[#9fb2ce]">Visible schedule cards</CardDescription>
          <CardTitle className="mt-3 text-white">{String(schedule.length)}</CardTitle>
        </Card>
        <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10">
          <CardDescription className="text-[#9fb2ce]">Published results</CardDescription>
          <CardTitle className="mt-3 text-white">{String(results.length)}</CardTitle>
        </Card>
        <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10">
          <CardDescription className="text-[#9fb2ce]">Standings rows</CardDescription>
          <CardTitle className="mt-3 text-white">{String(standings.length)}</CardTitle>
        </Card>
      </div>
      <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10">
        <CardTitle className="text-white">Provider linkage</CardTitle>
        <CardDescription className="text-[#9fb2ce]">
          Competition reads are currently targeting Hoops Scorebook event slug <strong>{competitionEventSlug}</strong>.
        </CardDescription>
      </Card>
    </div>
  );
}
