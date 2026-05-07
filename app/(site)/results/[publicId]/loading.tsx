import { PageHero } from "@/components/marketing/page-hero";
import { Card, CardDescription, CardTitle } from "@/components/ui/card";

export default function ResultBoxScoreLoading() {
  return (
    <>
      <PageHero
        eyebrow="Game box score"
        title="Game box score"
        description="Loading the latest Hoops Scorebook snapshot."
      />
      <section className="mx-auto max-w-6xl px-6 pb-20 lg:px-10">
        <Card>
          <CardTitle>Loading box score</CardTitle>
          <CardDescription>Scores, clock, and player lines will appear shortly.</CardDescription>
        </Card>
      </section>
    </>
  );
}
