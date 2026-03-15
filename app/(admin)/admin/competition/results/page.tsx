import { Card, CardDescription, CardTitle } from "@/components/ui/card";
import { getCompetitionProvider } from "@/lib/competition";
import { getCompetitionEventSlugByLocalSlug } from "@/lib/db/queries/content";
import { formatDateTime } from "@/lib/utils";

export default async function AdminCompetitionResultsPage() {
  const provider = getCompetitionProvider();
  const competitionEventSlug = await getCompetitionEventSlugByLocalSlug();
  const results = await provider.getResults({
    event: competitionEventSlug,
    workflow: "approved",
    limit: 24,
  });

  return (
    <div className="space-y-4">
      <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10">
        <CardTitle className="text-white">Competition results</CardTitle>
        <CardDescription className="text-[#9fb2ce]">
          Read-only admin view of the published result stream. Score entry remains outside this app.
        </CardDescription>
      </Card>
      {results.length ? (
        results.map((result) => (
          <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10" key={`${result.gamePublicId}-${result.teamName}`}>
            <CardTitle className="text-white">
              {result.teamName} {result.score} - {result.opponentScore} {result.opponentTeamName}
            </CardTitle>
            <CardDescription className="text-[#9fb2ce]">
              {result.divisionName ?? "Division pending"} · {result.poolName ?? "Pool pending"} · {formatDateTime(result.scheduledAt)}
            </CardDescription>
          </Card>
        ))
      ) : (
        <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10">
          <CardDescription className="text-[#9fb2ce]">
            No approved results were returned for the linked competition event yet.
          </CardDescription>
        </Card>
      )}
    </div>
  );
}
