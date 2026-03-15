import { PageHero } from "@/components/marketing/page-hero";
import { EmptyState } from "@/components/states/empty-state";
import { Card, CardDescription, CardTitle } from "@/components/ui/card";
import { getCmsPageBySlug } from "@/lib/db/queries/content";

export default async function RulesPage() {
  const page = await getCmsPageBySlug("rules");

  return (
    <>
      <PageHero eyebrow="Rules" title={page?.title ?? "Rules"} description={page?.subtitle ?? "Tournament rules and notes."} />
      <section className="mx-auto max-w-4xl px-6 pb-20 lg:px-10">
        {page ? (
          <div className="space-y-4">
            {page.sections.map((section) => (
              <Card key={section.heading}>
                <CardTitle>{section.heading}</CardTitle>
                <CardDescription className="whitespace-pre-line">{section.body}</CardDescription>
              </Card>
            ))}
          </div>
        ) : (
          <EmptyState
            title="Rules page is not published yet"
            description="Tournament rules have not been loaded from the CMS. Please check back shortly or contact the event staff for the latest bulletin."
          />
        )}
      </section>
    </>
  );
}
