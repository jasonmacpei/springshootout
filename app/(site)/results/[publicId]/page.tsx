import { BoxScorePage } from "@/components/competition/box-score-page";

export const dynamic = "force-dynamic";

export default async function ResultGameDetailRoute({
  params,
}: {
  params: Promise<{ publicId: string }>;
}) {
  const { publicId } = await params;

  return <BoxScorePage gamePublicId={publicId} />;
}
