import { MetricCard } from "@/components/admin/metric-card";
import { Card, CardDescription, CardTitle } from "@/components/ui/card";
import { getAdminDashboardSummary } from "@/lib/db/queries/content";

export default async function AdminDashboardPage() {
  const summary = await getAdminDashboardSummary();

  return (
    <div className="space-y-6">
      <div className="grid gap-4 md:grid-cols-3">
        <MetricCard label="CMS pages" value={String(summary.cmsPages)} detail="Published site copy now lives in Supabase-backed CMS tables." />
        <MetricCard label="Venues" value={String(summary.venues)} detail="Venue records drive the public gyms page and travel information." />
        <MetricCard label="Registrations" value={String(summary.registrations)} detail="Imported legacy registrations are available for archive and admin parity work." />
      </div>
      <div className="grid gap-6 lg:grid-cols-2">
        <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10">
          <CardTitle className="text-white">Local admin ownership</CardTitle>
          <CardDescription className="text-[#9fb2ce]">
            Spring Shootout owns content, venues, registrations, contacts, and outbound communication inside Supabase.
          </CardDescription>
        </Card>
        <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10">
          <CardTitle className="text-white">Competition boundary</CardTitle>
          <CardDescription className="text-[#9fb2ce]">
            Schedule publishing, live game state, results, standings, and playoff computation continue to come from Hoops Scorebook.
          </CardDescription>
        </Card>
      </div>
      {summary.missingItems.length ? (
        <Card className="bg-amber-500/10 text-white shadow-none ring-1 ring-amber-300/20">
          <CardTitle className="text-white">{summary.usingFallbackData ? "Seed fallback data is active" : "Content setup still has gaps"}</CardTitle>
          <CardDescription className="text-amber-100/80">
            Resolve these before checking off the production data readiness items in the rebuild checklist.
          </CardDescription>
          <ul className="mt-4 space-y-2 text-sm text-amber-50">
            {summary.missingItems.map((item) => (
              <li key={item}>{item}</li>
            ))}
          </ul>
        </Card>
      ) : null}
    </div>
  );
}
