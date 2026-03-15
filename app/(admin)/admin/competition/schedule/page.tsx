import { Card, CardDescription, CardTitle } from "@/components/ui/card";
import { getCompetitionProvider } from "@/lib/competition";
import { getCompetitionEventSlugByLocalSlug } from "@/lib/db/queries/content";
import { formatDateTime } from "@/lib/utils";

export default async function AdminCompetitionSchedulePage() {
  const provider = getCompetitionProvider();
  const competitionEventSlug = await getCompetitionEventSlugByLocalSlug();
  const games = await provider.getSchedule({
    event: competitionEventSlug,
    status: "all",
    limit: 16,
  });

  return (
    <div className="space-y-4">
      <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10">
        <CardTitle className="text-white">Competition schedule</CardTitle>
        <CardDescription className="text-[#9fb2ce]">
          This remains a scoreboard-backed interim surface until a dedicated schedule endpoint is available from Hoops Scorebook.
        </CardDescription>
      </Card>
      {games.length ? (
        games.map((game) => (
          <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10" key={game.gamePublicId}>
            <CardTitle className="text-white">
              {game.homeTeamName} vs {game.awayTeamName}
            </CardTitle>
            <CardDescription className="text-[#9fb2ce]">
              {game.venue ?? "Venue pending"} · {formatDateTime(game.scheduledAt)} · {game.status.replaceAll("_", " ")}
            </CardDescription>
          </Card>
        ))
      ) : (
        <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10">
          <CardDescription className="text-[#9fb2ce]">
            No schedule records were returned for the linked competition event yet.
          </CardDescription>
        </Card>
      )}
    </div>
  );
}
