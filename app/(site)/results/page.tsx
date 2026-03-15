import Link from "next/link";

import { PageHero } from "@/components/marketing/page-hero";
import { Button } from "@/components/ui/button";
import { Card, CardDescription, CardTitle } from "@/components/ui/card";
import { getCompetitionProvider } from "@/lib/competition";
import { getCompetitionEventSlugByLocalSlug } from "@/lib/db/queries/content";
import { formatDateTime } from "@/lib/utils";

export default async function ResultsPage() {
  const provider = getCompetitionProvider();
  const competitionEventSlug = await getCompetitionEventSlugByLocalSlug();
  const results = await provider.getResults({
    event: competitionEventSlug,
    workflow: "approved",
    limit: 12,
  });

  return (
    <>
      <PageHero
        eyebrow="Results"
        title="Latest results"
        description="Approved game results are published here as they clear the competition workflow."
      />
      <section className="mx-auto max-w-6xl px-6 pb-20 lg:px-10">
        <div className="grid gap-4">
          {results.map((result) => (
            <Card key={`${result.gamePublicId}-${result.teamName}`}>
              <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                  <CardTitle>
                    {result.teamName} {result.score} - {result.opponentScore} {result.opponentTeamName}
                  </CardTitle>
                  <CardDescription>
                    {result.divisionName ?? "Division pending"} · {result.poolName ?? "Pool pending"} ·{" "}
                    {formatDateTime(result.scheduledAt)}
                  </CardDescription>
                </div>
                <Link href={`/results/${result.gamePublicId}`}>
                  <Button size="sm" variant="outline">
                    Game detail
                  </Button>
                </Link>
              </div>
            </Card>
          ))}
        </div>
      </section>
    </>
  );
}
