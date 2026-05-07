import { CompetitionPoweredNote } from "@/components/marketing/competition-powered-note";
import { PageHero } from "@/components/marketing/page-hero";
import { EmptyState } from "@/components/states/empty-state";
import { Card } from "@/components/ui/card";
import { getCompetitionProvider } from "@/lib/competition";
import { formatWinPct, groupStandings, mergeStandings } from "@/lib/competition/standings";
import { getCompetitionEventSlugByLocalSlug } from "@/lib/db/queries/content";

export default async function StandingsPage() {
  const provider = getCompetitionProvider();
  const competitionEventSlug = await getCompetitionEventSlugByLocalSlug();
  const [standings, pools, schedule] = await Promise.all([
    provider.getStandings({
      event: competitionEventSlug,
      limit: 500,
    }),
    provider.getPools({
      event: competitionEventSlug,
      limit: 500,
    }),
    provider.getSchedule({
      event: competitionEventSlug,
      status: "all",
      limit: 500,
    }),
  ]);

  const grouped = groupStandings(mergeStandings({ pools, schedule, standings }));

  return (
    <>
      <PageHero
        eyebrow="Standings"
        title="Standings"
        description="Pool tables update from the competition feed as games are approved through the event workflow."
      />
      <section className="mx-auto max-w-6xl px-6 pb-20 lg:px-10">
        {grouped.length ? (
          <div className="grid gap-6">
            {grouped.map((pool) => (
              <Card className="overflow-hidden p-0" key={`${pool[0]?.divisionName}-${pool[0]?.poolName}`}>
                <div className="border-b border-black/10 bg-white/50 px-5 py-4">
                  <h2 className="text-xl font-semibold tracking-tight text-[var(--foreground)]">
                    {pool[0]?.divisionName ?? "Division"} · {pool[0]?.poolName ?? "Pool"}
                  </h2>
                </div>
                <div className="overflow-x-auto">
                  <table className="w-full min-w-[720px] border-collapse text-left text-sm">
                    <thead className="bg-[var(--surface)] text-xs font-semibold uppercase tracking-[0.12em] text-[var(--muted-foreground)]">
                      <tr>
                        <th className="px-4 py-3">Rank</th>
                        <th className="px-4 py-3">Team</th>
                        <th className="px-4 py-3 text-right">GP</th>
                        <th className="px-4 py-3 text-right">W</th>
                        <th className="px-4 py-3 text-right">L</th>
                        <th className="px-4 py-3 text-right">T</th>
                        <th className="px-4 py-3 text-right">PF</th>
                        <th className="px-4 py-3 text-right">PA</th>
                        <th className="px-4 py-3 text-right">+/-</th>
                        <th className="px-4 py-3 text-right">Win %</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-black/10">
                      {pool.map((row) => (
                        <tr key={row.teamPublicId}>
                          <td className="whitespace-nowrap px-4 py-3 font-semibold">{row.rank}</td>
                          <td className="px-4 py-3 font-semibold">{row.teamName}</td>
                          <td className="px-4 py-3 text-right">{row.gamesPlayed}</td>
                          <td className="px-4 py-3 text-right">{row.wins}</td>
                          <td className="px-4 py-3 text-right">{row.losses}</td>
                          <td className="px-4 py-3 text-right">{row.ties}</td>
                          <td className="px-4 py-3 text-right">{row.pointsFor}</td>
                          <td className="px-4 py-3 text-right">{row.pointsAgainst}</td>
                          <td className="px-4 py-3 text-right">{row.pointDifferential}</td>
                          <td className="px-4 py-3 text-right">{formatWinPct(row)}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </Card>
            ))}
          </div>
        ) : (
          <EmptyState
            description="Standings will appear here after pool or standings data is available from Hoops Scorebook."
            title="No standings available yet"
          />
        )}
        <div className="mt-8">
          <CompetitionPoweredNote />
        </div>
      </section>
    </>
  );
}
