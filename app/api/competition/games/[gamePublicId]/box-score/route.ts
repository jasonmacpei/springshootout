import { NextResponse } from "next/server";

import { getCompetitionProvider } from "@/lib/competition";

export const dynamic = "force-dynamic";

export async function GET(
  _request: Request,
  { params }: { params: Promise<{ gamePublicId: string }> },
) {
  const { gamePublicId } = await params;

  try {
    const boxScore = await getCompetitionProvider().getGameBoxScore(gamePublicId);

    if (!boxScore) {
      return NextResponse.json(
        { error: "Box score is not available yet." },
        {
          headers: {
            "Cache-Control": "no-store",
          },
          status: 404,
        },
      );
    }

    return NextResponse.json(boxScore, {
      headers: {
        "Cache-Control": "no-store",
      },
    });
  } catch {
    return NextResponse.json(
      { error: "Box score updates are temporarily unavailable." },
      {
        headers: {
          "Cache-Control": "no-store",
        },
        status: 502,
      },
    );
  }
}
