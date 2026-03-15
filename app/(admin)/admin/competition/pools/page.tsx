import { Card, CardDescription, CardTitle } from "@/components/ui/card";
import { getCompetitionProvider } from "@/lib/competition";
import { getCompetitionEventSlugByLocalSlug } from "@/lib/db/queries/content";

export default async function AdminCompetitionPoolsPage() {
  const provider = getCompetitionProvider();
  const competitionEventSlug = await getCompetitionEventSlugByLocalSlug();
  const pools = await provider.getPools({
    event: competitionEventSlug,
    limit: 60,
  });

  return (
    <div className="space-y-4">
      <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10">
        <CardTitle className="text-white">Competition pools</CardTitle>
        <CardDescription className="text-[#9fb2ce]">
          This view is currently derived from standings data. It will switch to a dedicated pool endpoint when Hoops Scorebook exposes one.
        </CardDescription>
      </Card>
      {pools.length ? (
        <div className="grid gap-4 md:grid-cols-2">
          {pools.map((pool) => (
            <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10" key={`${pool.stageId ?? "stage"}-${pool.poolId ?? pool.poolName ?? "pool"}`}>
              <CardTitle className="text-white">
                {pool.divisionName ?? "Division"} · {pool.poolName ?? "Pool"}
              </CardTitle>
              <CardDescription className="text-[#9fb2ce]">
                {pool.stageName ?? "Stage pending"} · {pool.teams.length} teams
              </CardDescription>
              <div className="mt-5 space-y-2 text-sm text-[#dce7f8]">
                {pool.teams.map((row) => (
                  <div className="rounded-2xl border border-white/10 bg-[#121d31] px-4 py-3" key={row.teamPublicId}>
                    {row.teamName}
                  </div>
                ))}
              </div>
            </Card>
          ))}
        </div>
      ) : (
        <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10">
          <CardDescription className="text-[#9fb2ce]">
            No pool groupings could be derived from the linked competition event yet.
          </CardDescription>
        </Card>
      )}
    </div>
  );
}
