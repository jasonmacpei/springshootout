import { createAdminClientFromLocalEnv, getEventBySlug } from "./lib.mjs";

const targetSlug = process.argv[2] || "spring-shootout-2026";
const sourceSlugs = (process.argv[3] || "spring-shootout-2,spring-shootout-2025")
  .split(",")
  .map((slug) => slug.trim())
  .filter(Boolean)
  .filter((slug) => slug !== targetSlug);

const { supabase } = createAdminClientFromLocalEnv();
const targetEvent = await getEventBySlug(supabase, targetSlug);

if (!targetEvent) {
  console.error(`Target event '${targetSlug}' was not found.`);
  process.exit(1);
}

const { data: sourceEvents, error: sourceEventsError } = await supabase
  .from("events")
  .select("id, slug, name")
  .in("slug", sourceSlugs);

if (sourceEventsError) {
  throw sourceEventsError;
}

if (!sourceEvents?.length) {
  console.log(
    JSON.stringify(
      {
        target: targetSlug,
        sourceSlugs,
        updated: {
          teams: 0,
          registrations: 0,
        },
        message: "No source events found.",
      },
      null,
      2,
    ),
  );
  process.exit(0);
}

const sourceIds = sourceEvents.map((event) => event.id);

const [{ count: teamsBefore }, { count: registrationsBefore }] = await Promise.all([
  supabase.from("teams").select("id", { count: "exact", head: true }).in("event_id", sourceIds),
  supabase.from("registrations").select("id", { count: "exact", head: true }).in("event_id", sourceIds),
]);

const { error: teamsError } = await supabase.from("teams").update({ event_id: targetEvent.id }).in("event_id", sourceIds);

if (teamsError) {
  throw teamsError;
}

const { error: registrationsError } = await supabase
  .from("registrations")
  .update({ event_id: targetEvent.id })
  .in("event_id", sourceIds);

if (registrationsError) {
  throw registrationsError;
}

const [{ count: targetTeams }, { count: targetRegistrations }, { count: targetTeamContacts }] = await Promise.all([
  supabase.from("teams").select("id", { count: "exact", head: true }).eq("event_id", targetEvent.id),
  supabase.from("registrations").select("id", { count: "exact", head: true }).eq("event_id", targetEvent.id),
  supabase
    .from("team_contacts")
    .select("id, teams!inner(event_id)", { count: "exact", head: true })
    .eq("teams.event_id", targetEvent.id),
]);

console.log(
  JSON.stringify(
    {
      target: {
        slug: targetEvent.slug,
        name: targetEvent.name,
      },
      sources: sourceEvents.map((event) => ({
        slug: event.slug,
        name: event.name,
      })),
      updated: {
        teams: teamsBefore ?? 0,
        registrations: registrationsBefore ?? 0,
      },
      targetCounts: {
        teams: targetTeams ?? 0,
        registrations: targetRegistrations ?? 0,
        teamContactLinks: targetTeamContacts ?? 0,
      },
    },
    null,
    2,
  ),
);
