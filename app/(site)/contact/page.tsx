import { PageHero } from "@/components/marketing/page-hero";
import { EmptyState } from "@/components/states/empty-state";
import { Card, CardDescription, CardTitle } from "@/components/ui/card";
import { appConfig } from "@/lib/config";
import { getCmsPageBySlug } from "@/lib/db/queries/content";

export default async function ContactPage() {
  const page = await getCmsPageBySlug("contact");

  return (
    <>
      <PageHero
        eyebrow="Contact"
        title={page?.title ?? "Contact"}
        description={page?.subtitle ?? "Direct lines for the tournament team."}
      />
      <section className="mx-auto max-w-4xl px-6 pb-20 lg:px-10">
        <div className="grid gap-6 lg:grid-cols-2">
          <Card>
            <CardTitle>General support</CardTitle>
            <CardDescription>{appConfig.supportEmail}</CardDescription>
            <p className="mt-4 text-sm text-[var(--muted-foreground)]">{appConfig.supportPhone}</p>
          </Card>
          {page?.sections.map((section) => (
            <Card key={section.heading}>
              <CardTitle>{section.heading}</CardTitle>
              <CardDescription className="whitespace-pre-line">{section.body}</CardDescription>
            </Card>
          ))}
        </div>
        {!page ? (
          <div className="mt-6">
            <EmptyState
              title="Contact details are still being published"
              description="General support is available now, but the extended tournament contact directory has not been loaded from the CMS yet."
            />
          </div>
        ) : null}
      </section>
    </>
  );
}
