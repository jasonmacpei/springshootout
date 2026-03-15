import { Card, CardDescription, CardTitle } from "@/components/ui/card";
import { getCompetitionProvider } from "@/lib/competition";
import { getCompetitionEventSlugByLocalSlug } from "@/lib/db/queries/content";
import { formatDateTime } from "@/lib/utils";

export default async function AdminCompetitionPlayoffsPage() {
  const provider = getCompetitionProvider();
  const competitionEventSlug = await getCompetitionEventSlugByLocalSlug();
  const brackets = await provider.getPlayoffBrackets({
    event: competitionEventSlug,
    workflow: "approved",
    limit: 60,
  });

  return (
    <div className="space-y-4">
      <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10">
        <CardTitle className="text-white">Competition playoffs</CardTitle>
        <CardDescription className="text-[#9fb2ce]">
          This view infers playoff-stage games from the published result stream until a dedicated bracket feed is available.
        </CardDescription>
      </Card>
      {brackets.length ? (
        brackets.map((bracket) => (
            <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10" key={bracket.stageName}>
              <CardTitle className="text-white">{bracket.stageName}</CardTitle>
              {bracket.bracketDefinition.length ? (
                <CardDescription className="mt-2 text-[#9fb2ce]">
                  {bracket.bracketDefinition.map((entry) => `${entry.name}: ${entry.homeSource ?? "TBD"} vs ${entry.awaySource ?? "TBD"}`).join(" · ")}
                </CardDescription>
              ) : null}
              <div className="mt-5 space-y-3">
              {bracket.games.map((game) => (
                <div className="rounded-2xl border border-white/10 bg-[#121d31] px-4 py-3" key={game.gamePublicId}>
                  <p className="font-semibold text-white">
                    {game.homeTeamName ?? game.homeSlotLabel ?? "TBD"} {game.homeScore ?? "-"} - {game.awayScore ?? "-"}{" "}
                    {game.awayTeamName ?? game.awaySlotLabel ?? "TBD"}
                  </p>
                  <p className="mt-1 text-sm text-[#9fb2ce]">
                    {formatDateTime(game.scheduledAt)} · {game.venue ?? "Venue pending"}
                  </p>
                </div>
              ))}
            </div>
          </Card>
        ))
      ) : (
        <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10">
          <CardDescription className="text-[#9fb2ce]">
            No playoff-stage games are exposed in the current results feed yet.
          </CardDescription>
        </Card>
      )}
    </div>
  );
}
