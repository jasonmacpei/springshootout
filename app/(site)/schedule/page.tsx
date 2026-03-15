import { AlertTriangle } from "lucide-react";

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
        <Card className="border-amber-300/60 bg-amber-50/80">
          <div className="flex gap-4">
            <AlertTriangle className="mt-1 h-5 w-5 text-amber-700" />
            <div>
              <CardTitle>Schedule publishing note</CardTitle>
              <CardDescription className="text-amber-900/80">
                Hoops Scorebook currently exposes the live/final game feed used below. A dedicated full-schedule endpoint will replace this interim view once it is available.
              </CardDescription>
            </div>
          </div>
        </Card>
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
