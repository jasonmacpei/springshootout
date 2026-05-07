import { LiveBoxScore } from "@/components/competition/live-box-score";
import { getCompetitionProvider } from "@/lib/competition";
import type { CompetitionGameDetail } from "@/lib/competition/schemas";

export async function BoxScorePage({ gamePublicId }: { gamePublicId: string }) {
  let boxScore: CompetitionGameDetail | null = null;
  let initialError = false;

  try {
    boxScore = await getCompetitionProvider().getGameBoxScore(gamePublicId);
  } catch {
    initialError = true;
  }

  return <LiveBoxScore gamePublicId={gamePublicId} initialBoxScore={boxScore} initialError={initialError} />;
}
