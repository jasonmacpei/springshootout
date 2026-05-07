import { BoxScorePage } from "@/components/competition/box-score-page";

export const dynamic = "force-dynamic";

export default async function GameBoxScoreRoute({
  params,
}: {
  params: Promise<{ gamePublicId: string }>;
}) {
  const { gamePublicId } = await params;

  return <BoxScorePage gamePublicId={gamePublicId} />;
}
