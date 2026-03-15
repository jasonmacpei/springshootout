import { PageHero } from "@/components/marketing/page-hero";
import { Card, CardDescription, CardTitle } from "@/components/ui/card";
import { getCompetitionProvider } from "@/lib/competition";
import { getCompetitionEventSlugByLocalSlug } from "@/lib/db/queries/content";

export default async function StandingsPage() {
  const provider = getCompetitionProvider();
  const competitionEventSlug = await getCompetitionEventSlugByLocalSlug();
  const standings = await provider.getStandings({
    event: competitionEventSlug,
    limit: 20,
  });

  const grouped = Object.values(
    standings.reduce<Record<string, typeof standings>>((acc, item) => {
      const key = `${item.divisionName ?? "Division"}-${item.poolName ?? "Pool"}`;
      acc[key] ??= [];
      acc[key].push(item);
      return acc;
    }, {}),
  );

  return (
    <>
      <PageHero
        eyebrow="Standings"
        title="Standings"
        description="Pool tables update from the competition feed as games are approved through the event workflow."
      />
      <section className="mx-auto max-w-6xl px-6 pb-20 lg:px-10">
        <div className="grid gap-6 lg:grid-cols-2">
          {grouped.map((pool) => (
            <Card key={`${pool[0]?.divisionName}-${pool[0]?.poolName}`}>
              <CardTitle>
                {pool[0]?.divisionName ?? "Division"} · {pool[0]?.poolName ?? "Pool"}
              </CardTitle>
              <CardDescription>{pool[0]?.stageName ?? "Stage pending"}</CardDescription>
              <div className="mt-5 space-y-3">
                {pool.map((row) => (
                  <div
                    className="grid grid-cols-[40px_1fr_auto] items-center gap-4 rounded-2xl border border-black/8 bg-white px-4 py-3 text-sm"
                    key={row.teamPublicId}
                  >
                    <strong>{row.rank}</strong>
                    <span>{row.teamName}</span>
                    <span className="text-[var(--muted-foreground)]">
                      {row.wins}-{row.losses}
                    </span>
                  </div>
                ))}
              </div>
            </Card>
          ))}
        </div>
      </section>
    </>
  );
}
