import { getCompetitionProvider } from "@/lib/competition";
import { buildSchedulePdf } from "@/lib/competition/schedule-pdf";
import { getCompetitionEventSlugByLocalSlug } from "@/lib/db/queries/content";

export const runtime = "nodejs";

export async function GET() {
  const provider = getCompetitionProvider();
  const competitionEventSlug = await getCompetitionEventSlugByLocalSlug();
  const schedule = await provider.getSchedule({
    event: competitionEventSlug,
    status: "all",
    limit: 500,
  });
  const pdf = buildSchedulePdf(schedule);

  return new Response(new Uint8Array(pdf), {
    headers: {
      "Cache-Control": "public, max-age=60, stale-while-revalidate=300",
      "Content-Disposition": 'attachment; filename="spring-shootout-schedule.pdf"',
      "Content-Type": "application/pdf",
    },
  });
}
