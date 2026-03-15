import Link from "next/link";

import { PageHero } from "@/components/marketing/page-hero";
import { EmptyState } from "@/components/states/empty-state";
import { Card, CardDescription, CardTitle } from "@/components/ui/card";
import { getCmsPageBySlug, getVenuesByEventSlug } from "@/lib/db/queries/content";

export default async function GymsPage() {
  const [page, venues] = await Promise.all([getCmsPageBySlug("gyms"), getVenuesByEventSlug()]);

  return (
    <>
      <PageHero eyebrow="Gyms" title={page?.title ?? "Gyms"} description={page?.subtitle ?? "Venue and map information."} />
      <section className="mx-auto max-w-6xl px-6 pb-20 lg:px-10">
        {page ? (
          <div className="space-y-4">
            {page.sections.map((section) => (
              <Card key={section.heading}>
                <CardTitle>{section.heading}</CardTitle>
                <CardDescription className="whitespace-pre-line">{section.body}</CardDescription>
              </Card>
            ))}
          </div>
        ) : null}
        {venues.length ? (
          <div className="mt-8 grid gap-4 lg:grid-cols-2">
            {venues.map((venue) => (
              <Card key={venue.name}>
                <CardTitle>{venue.name}</CardTitle>
                <CardDescription>{venue.address}</CardDescription>
                <p className="mt-4 text-sm leading-6 text-[var(--muted-foreground)]">{venue.notes}</p>
                <Link className="mt-5 inline-flex text-sm font-semibold text-[var(--accent)]" href={venue.mapUrl}>
                  Open map
                </Link>
              </Card>
            ))}
          </div>
        ) : (
          <div className="mt-8">
            <EmptyState
              title="Venue details are not published yet"
              description="Gym assignments and travel notes have not been loaded from the event database. Check back shortly for the final venue list."
            />
          </div>
        )}
      </section>
    </>
  );
}
