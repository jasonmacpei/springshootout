import { NextResponse } from "next/server";

import { getLiveResultsFeed } from "@/lib/competition/live-results";

export const dynamic = "force-dynamic";

export async function GET() {
  const feed = await getLiveResultsFeed();

  return NextResponse.json(feed, {
    headers: {
      "Cache-Control": "no-store",
    },
  });
}
