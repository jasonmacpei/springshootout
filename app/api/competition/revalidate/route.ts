import { NextResponse } from "next/server";

import { getCompetitionProvider } from "@/lib/competition";

export async function POST(request: Request) {
  const body = (await request.json().catch(() => null)) as { event?: string; secret?: string } | null;

  if (process.env.REVALIDATE_SECRET && body?.secret !== process.env.REVALIDATE_SECRET) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  const identifier = body?.event ?? "all";
  await getCompetitionProvider().refreshEvent(identifier);

  return NextResponse.json({ ok: true, identifier });
}
