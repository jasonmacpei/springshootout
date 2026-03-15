import { CompetitionPoweredNote } from "@/components/marketing/competition-powered-note";
import { PageHero } from "@/components/marketing/page-hero";
import { Card, CardDescription, CardTitle } from "@/components/ui/card";
import { getCompetitionProvider } from "@/lib/competition";
import { getCompetitionEventSlugByLocalSlug } from "@/lib/db/queries/content";
import { formatDateTime } from "@/lib/utils";

export default async function SchedulePage() {
  const provider = getCompetitionProvider();
  const competitionEventSlug = await getCompetitionEventSlugByLocalSlug();
  const schedule = await provider.getSchedule({
    event: competitionEventSlug,
    status: "all",
    limit: 8,
  });

  return (
    <>
      <PageHero
        eyebrow="Schedule"
        title="Game schedule"
        description="The full weekend schedule will publish here once the 2026 event feed opens. Until then, the latest available game cards appear below."
      />
      <section className="mx-auto max-w-6xl px-6 pb-20 lg:px-10">
        <CompetitionPoweredNote />
        <div className="mt-6 grid gap-4">
          {schedule.map((game) => (
            <Card key={game.gamePublicId}>
              <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                  <CardTitle>
                    {game.homeTeamName} vs {game.awayTeamName}
                  </CardTitle>
                  <CardDescription>
                    {game.venue ?? "Venue pending"} · {formatDateTime(game.scheduledAt)}
                  </CardDescription>
                </div>
                <div className="rounded-full bg-[var(--surface)] px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-[var(--muted-foreground)]">
                  {game.status.replaceAll("_", " ")}
                </div>
              </div>
            </Card>
          ))}
        </div>
      </section>
    </>
  );
}
