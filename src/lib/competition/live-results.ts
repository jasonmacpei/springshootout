import { getCompetitionProvider } from "@/lib/competition";
import type { CompetitionResult, CompetitionScoreboardGame } from "@/lib/competition/schemas";
import { getCompetitionEventSlugByLocalSlug } from "@/lib/db/queries/content";

export type FinalResultGame = {
  gamePublicId: string;
  gameStatus: string;
  resultWorkflowStatus: string;
  scheduledAt: string;
  venue?: string | null;
  eventSlug: string;
  eventName: string;
  divisionName?: string | null;
  poolName?: string | null;
  stageName?: string | null;
  teamName: string;
  opponentTeamName: string;
  score: number;
  opponentScore: number;
  result: string;
};

export type LiveResultsFeed = {
  eventSlug: string;
  generatedAt: string;
  liveGames: CompetitionScoreboardGame[];
  finalGames: FinalResultGame[];
};

function groupFinalResults(results: CompetitionResult[]) {
  const byGame = new Map<string, FinalResultGame>();

  for (const result of results) {
    const existing = byGame.get(result.gamePublicId);
    if (existing && existing.result === "win") {
      continue;
    }

    if (!existing || result.result === "win") {
      byGame.set(result.gamePublicId, {
        gamePublicId: result.gamePublicId,
        gameStatus: result.gameStatus,
        resultWorkflowStatus: result.resultWorkflowStatus,
        scheduledAt: result.scheduledAt,
        venue: result.venue,
        eventSlug: result.eventSlug,
        eventName: result.eventName,
        divisionName: result.divisionName,
        poolName: result.poolName,
        stageName: result.stageName,
        teamName: result.teamName,
        opponentTeamName: result.opponentTeamName,
        score: result.score,
        opponentScore: result.opponentScore,
        result: result.result,
      });
    }
  }

  return [...byGame.values()];
}

export async function getLiveResultsFeed(): Promise<LiveResultsFeed> {
  const provider = getCompetitionProvider();
  const eventSlug = await getCompetitionEventSlugByLocalSlug();
  const [scoreboardGames, results] = await Promise.all([
    provider.getScoreboard({
      event: eventSlug,
      status: "all",
      limit: 100,
      noStore: true,
    }),
    provider.getResults({
      event: eventSlug,
      workflow: "all",
      limit: 80,
    }),
  ]);

  return {
    eventSlug,
    generatedAt: new Date().toISOString(),
    liveGames: scoreboardGames.filter((game) => game.status === "in_progress" || game.status === "live"),
    finalGames: groupFinalResults(results),
  };
}
