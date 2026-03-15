import { notFound } from "next/navigation";

import { PageHero } from "@/components/marketing/page-hero";
import { Card, CardDescription, CardTitle } from "@/components/ui/card";
import { getCompetitionProvider } from "@/lib/competition";

export default async function GameDetailPage({ params }: { params: Promise<{ publicId: string }> }) {
  const { publicId } = await params;
  const provider = getCompetitionProvider();
  const game = await provider.getGame(publicId);

  if (!game) {
    notFound();
  }

  return (
    <>
      <PageHero
        eyebrow="Game detail"
        title={`${game.game.homeTeamName} vs ${game.game.awayTeamName}`}
        description="Per-game detail and recent events come directly from the external competition adapter."
      />
      <section className="mx-auto max-w-5xl px-6 pb-20 lg:px-10">
        <Card>
          <CardTitle>
            {game.game.homeScore ?? "-"} - {game.game.awayScore ?? "-"}
          </CardTitle>
          <CardDescription>
            {game.game.status.replaceAll("_", " ")} · {game.game.venue ?? "Venue pending"}
          </CardDescription>
          <div className="mt-6 space-y-3">
            {game.recentEvents.map((event) => (
              <div className="rounded-2xl border border-black/8 bg-white px-4 py-3 text-sm" key={event.eventSequence}>
                {event.eventType} · {event.teamName ?? "Team"} · {event.playerFirstName ?? ""} {event.playerLastName ?? ""}
              </div>
            ))}
          </div>
        </Card>
      </section>
    </>
  );
}
