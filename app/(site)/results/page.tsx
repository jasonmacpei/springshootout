import { LiveResultsBoard } from "@/components/competition/live-results-board";
import { CompetitionPoweredNote } from "@/components/marketing/competition-powered-note";
import { PageHero } from "@/components/marketing/page-hero";
import { getLiveResultsFeed } from "@/lib/competition/live-results";

export default async function ResultsPage() {
  const feed = await getLiveResultsFeed();

  return (
    <>
      <PageHero
        eyebrow="Live results"
        title="Live results"
        description="Games being scored in Hoops Scorebook appear here while they are live, then remain below as finalized results."
      />
      <section className="mx-auto max-w-6xl px-6 pb-20 lg:px-10">
        <LiveResultsBoard initialFeed={feed} />
        <div className="mt-10">
          <CompetitionPoweredNote />
        </div>
      </section>
    </>
  );
}
