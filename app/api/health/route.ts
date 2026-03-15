import { NextResponse } from "next/server";

export async function GET() {
  return NextResponse.json({
    ok: true,
    service: "spring-shootout",
    timestamp: new Date().toISOString(),
  });
}
