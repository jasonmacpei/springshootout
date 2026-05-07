import { LiveBoxScore } from "@/components/competition/live-box-score";
import { getCompetitionProvider } from "@/lib/competition";

export async function BoxScorePage({ gamePublicId }: { gamePublicId: string }) {
  try {
    const boxScore = await getCompetitionProvider().getGameBoxScore(gamePublicId);

    return <LiveBoxScore gamePublicId={gamePublicId} initialBoxScore={boxScore} />;
  } catch {
    return <LiveBoxScore gamePublicId={gamePublicId} initialBoxScore={null} initialError />;
  }
}
