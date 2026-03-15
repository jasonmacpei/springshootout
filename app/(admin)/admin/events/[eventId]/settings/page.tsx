import { updateEventSettingsAction } from "@/actions/admin-ops";
import { Card, CardDescription, CardTitle } from "@/components/ui/card";
import { getEventSettingsEditorBySlug } from "@/lib/db/queries/content";

export default async function EventSettingsPage({
  params,
  searchParams,
}: {
  params: Promise<{ eventId: string }>;
  searchParams?: Promise<{ error?: string }>;
}) {
  const { eventId } = await params;
  const query = searchParams ? await searchParams : undefined;
  const event = await getEventSettingsEditorBySlug(eventId);

  if (!event) {
    return (
      <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10">
        <CardTitle className="text-white">Event not found</CardTitle>
        <CardDescription className="text-[#9fb2ce]">No event record matches {eventId}.</CardDescription>
      </Card>
    );
  }

  return (
    <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10">
      <CardTitle className="text-white">Event settings: {event.name}</CardTitle>
      <CardDescription className="text-[#9fb2ce]">
        Manage the public hero copy, support contacts, registration status line, and Hoops Scorebook linkage for this event.
      </CardDescription>
      {event.source === "fallback" ? (
        <p className="mt-4 rounded-2xl border border-amber-400/30 bg-amber-400/10 px-4 py-3 text-sm text-amber-100">
          This screen is showing seed fallback settings. Saving will create or overwrite the real event settings once the event exists in Supabase.
        </p>
      ) : null}
      {query?.error ? <p className="mt-4 text-sm text-red-300">{query.error}</p> : null}
      <form action={updateEventSettingsAction} className="mt-6 grid gap-4 lg:grid-cols-2">
        <input name="eventId" type="hidden" value={event.id} />
        <label className="grid gap-2 text-sm font-medium text-white">
          Event slug
          <input className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" defaultValue={event.slug} name="eventSlug" required />
        </label>
        <label className="grid gap-2 text-sm font-medium text-white">
          Event name
          <input className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" defaultValue={event.name} name="name" required />
        </label>
        <label className="grid gap-2 text-sm font-medium text-white">
          Starts on
          <input className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" defaultValue={event.startsOn ?? ""} name="startsOn" type="date" />
        </label>
        <label className="grid gap-2 text-sm font-medium text-white">
          Ends on
          <input className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" defaultValue={event.endsOn ?? ""} name="endsOn" type="date" />
        </label>
        <label className="grid gap-2 text-sm font-medium text-white">
          Provider event slug
          <input className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" defaultValue={event.providerEventSlug ?? ""} name="providerEventSlug" />
        </label>
        <label className="grid gap-2 text-sm font-medium text-white">
          Visibility mode
          <input className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" defaultValue={event.providerVisibilityMode ?? ""} name="providerVisibilityMode" />
        </label>
        <label className="grid gap-2 text-sm font-medium text-white">
          Support email
          <input className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" defaultValue={event.supportEmail} name="supportEmail" type="email" />
        </label>
        <label className="grid gap-2 text-sm font-medium text-white">
          Support phone
          <input className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" defaultValue={event.supportPhone} name="supportPhone" />
        </label>
        <label className="grid gap-2 text-sm font-medium text-white lg:col-span-2">
          Hero eyebrow
          <input className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" defaultValue={event.heroEyebrow} name="heroEyebrow" />
        </label>
        <label className="grid gap-2 text-sm font-medium text-white lg:col-span-2">
          Hero title
          <input className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" defaultValue={event.heroTitle} name="heroTitle" />
        </label>
        <label className="grid gap-2 text-sm font-medium text-white lg:col-span-2">
          Hero description
          <textarea className="min-h-32 rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" defaultValue={event.heroDescription} name="heroDescription" />
        </label>
        <label className="grid gap-2 text-sm font-medium text-white lg:col-span-2">
          Registration status line
          <textarea className="min-h-24 rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" defaultValue={event.registrationStatus} name="registrationStatus" />
        </label>
        <label className="flex items-center gap-3 text-sm font-medium text-white lg:col-span-2">
          <input defaultChecked={event.isActive} name="isActive" type="checkbox" />
          Active event
        </label>
        <div className="lg:col-span-2">
          <button className="rounded-full bg-white px-5 py-2.5 text-sm font-semibold text-[#11182a]" type="submit">
            Save settings
          </button>
        </div>
      </form>
    </Card>
  );
}
