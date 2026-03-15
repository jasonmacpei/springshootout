import { PageHero } from "@/components/marketing/page-hero";
import { EmptyState } from "@/components/states/empty-state";
import { Card, CardDescription, CardTitle } from "@/components/ui/card";
import { getTeamDirectoryByEventSlug } from "@/lib/db/queries/content";

export default async function TeamsPage() {
  const teams = await getTeamDirectoryByEventSlug();

  return (
    <>
      <PageHero
        eyebrow="Teams"
        title="Registered teams"
        description="Confirmed team entries will appear here by division as the tournament field is finalized."
      />
      <section className="mx-auto max-w-6xl px-6 pb-20 lg:px-10">
        {teams.length ? (
          <div className="grid gap-6 lg:grid-cols-2">
            {teams.map((group) => (
              <Card key={group.divisionName}>
                <CardTitle>{group.divisionName}</CardTitle>
                <CardDescription>Teams are listed as registrations are approved.</CardDescription>
                <div className="mt-5 space-y-3">
                  {group.entries.map((team) => (
                    <div
                      className="grid grid-cols-[1fr_auto] items-center gap-4 rounded-2xl border border-black/8 bg-white px-4 py-3 text-sm"
                      key={team.id}
                    >
                      <div>
                        <p className="font-semibold text-[var(--foreground)]">{team.name}</p>
                        <p className="mt-1 text-xs uppercase tracking-[0.14em] text-[var(--muted-foreground)]">
                          {[team.className, team.province].filter(Boolean).join(" · ") || "Class pending"}
                        </p>
                      </div>
                    </div>
                  ))}
                </div>
              </Card>
            ))}
          </div>
        ) : (
          <EmptyState
            title="Team list publishing soon"
            description="Registrations are open. Confirmed teams will be posted here once the 2026 field starts to lock in."
          />
        )}
      </section>
    </>
  );
}
