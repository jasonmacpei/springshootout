import { PageHero } from "@/components/marketing/page-hero";
import { EmptyState } from "@/components/states/empty-state";
import { Card, CardDescription, CardTitle } from "@/components/ui/card";
import { getCmsPageBySlug } from "@/lib/db/queries/content";

export default async function HotelsPage() {
  const page = await getCmsPageBySlug("hotels");

  return (
    <>
      <PageHero eyebrow="Hotels" title={page?.title ?? "Hotels"} description={page?.subtitle ?? "Travel notes and partner stays."} />
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
            title="Hotel block details are not published yet"
            description="Travel and lodging details have not been loaded from the CMS. Please check back shortly before booking."
          />
        )}
      </section>
    </>
  );
}
