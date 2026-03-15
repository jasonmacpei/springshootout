import { Card, CardDescription, CardTitle } from "@/components/ui/card";
import { getCompetitionProvider } from "@/lib/competition";
import { getCompetitionEventSlugByLocalSlug } from "@/lib/db/queries/content";

export default async function AdminCompetitionStandingsPage() {
  const provider = getCompetitionProvider();
  const competitionEventSlug = await getCompetitionEventSlugByLocalSlug();
  const standings = await provider.getStandings({
    event: competitionEventSlug,
    limit: 40,
  });

  const grouped = Object.values(
    standings.reduce<Record<string, typeof standings>>((acc, row) => {
      const key = `${row.divisionName ?? "Division"}-${row.poolName ?? "Pool"}`;
      acc[key] ??= [];
      acc[key].push(row);
      return acc;
    }, {}),
  );

  return grouped.length ? (
    <div className="grid gap-4 lg:grid-cols-2">
      {grouped.map((pool) => (
        <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10" key={`${pool[0]?.divisionName}-${pool[0]?.poolName}`}>
          <CardTitle className="text-white">
            {pool[0]?.divisionName ?? "Division"} · {pool[0]?.poolName ?? "Pool"}
          </CardTitle>
          <CardDescription className="text-[#9fb2ce]">{pool[0]?.stageName ?? "Stage pending"}</CardDescription>
          <div className="mt-5 space-y-2 text-sm text-[#dce7f8]">
            {pool.map((row) => (
              <div className="flex items-center justify-between gap-4 rounded-2xl border border-white/10 bg-[#121d31] px-4 py-3" key={row.teamPublicId}>
                <span>
                  #{row.rank} {row.teamName}
                </span>
                <strong>
                  {row.wins}-{row.losses}
                </strong>
              </div>
            ))}
          </div>
        </Card>
      ))}
    </div>
  ) : (
    <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10">
      <CardDescription className="text-[#9fb2ce]">
        No standings rows were returned for the linked competition event yet.
      </CardDescription>
    </Card>
  );
}
