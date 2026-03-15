import { PageHero } from "@/components/marketing/page-hero";
import { EmptyState } from "@/components/states/empty-state";

export default function MediaPage() {
  return (
    <>
      <PageHero
        eyebrow="Media"
        title="Media and event coverage"
        description="Photos, recap notes, and highlight links will live here once the 2026 weekend gets underway."
      />
      <section className="mx-auto max-w-4xl px-6 pb-20 lg:px-10">
        <EmptyState
          title="Coverage will publish during tournament weekend"
          description="Keep this page live for families, coaches, and players. It will become the home for recaps, galleries, and any partner media links."
        />
      </section>
    </>
  );
}
