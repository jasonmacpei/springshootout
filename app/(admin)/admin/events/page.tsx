import Link from "next/link";

import { EmptyState } from "@/components/states/empty-state";
import { Card, CardDescription, CardTitle } from "@/components/ui/card";
import { listEventsForAdmin } from "@/lib/db/queries/content";

export default async function AdminEventsPage() {
  const events = await listEventsForAdmin();

  if (!events.length) {
    return (
      <EmptyState
        title="No events are configured"
        description="Seed or import the active event before managing event settings. The admin event list stays empty until Supabase has a real event record."
      />
    );
  }

  return (
    <div className="space-y-4">
      <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10">
        <CardTitle className="text-white">Events</CardTitle>
        <CardDescription className="text-[#9fb2ce]">
          Multi-event support starts here. Each event can carry local content plus an external competition linkage.
        </CardDescription>
      </Card>
      <div className="grid gap-4 lg:grid-cols-2">
        {events.map((event) => (
          <Link href={`/admin/events/${event.slug}/settings`} key={event.id}>
            <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10">
              <CardTitle className="text-white">{event.name}</CardTitle>
              <CardDescription className="text-[#9fb2ce]">
                {[event.startsOn, event.endsOn].filter(Boolean).join(" to ") || "Dates pending"}
              </CardDescription>
              <div className="mt-5 flex items-center justify-between gap-4 text-sm text-[#dce7f8]">
                <span>{event.source === "fallback" ? "Seed fallback event" : event.isActive ? "Active event" : "Archive / inactive"}</span>
                <strong>{event.providerEventSlug ?? "No provider link"}</strong>
              </div>
            </Card>
          </Link>
        ))}
      </div>
    </div>
  );
}
