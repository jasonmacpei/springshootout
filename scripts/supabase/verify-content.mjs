import { createAdminClientFromLocalEnv, getEventBySlug } from "./lib.mjs";

const { env, supabase } = createAdminClientFromLocalEnv();
const eventSlug = process.argv[2] || env.NEXT_PUBLIC_DEFAULT_EVENT_SLUG || "spring-shootout-2026";

const event = await getEventBySlug(supabase, eventSlug);

if (!event) {
  console.error(`FAIL: event '${eventSlug}' was not found.`);
  process.exit(1);
}

const [eventSettings, cmsPages, venues, contactRoles, navigation, teams, registrations, emailTemplates] = await Promise.all([
  supabase.from("event_settings").select("*", { count: "exact", head: true }).eq("event_id", event.id),
  supabase.from("cms_pages").select("slug, status", { count: "exact" }).eq("event_id", event.id).order("slug"),
  supabase.from("venues").select("name", { count: "exact" }).eq("event_id", event.id).order("display_order"),
  supabase.from("contact_roles").select("slug", { count: "exact" }).order("name"),
  supabase.from("site_navigation").select("href", { count: "exact" }).eq("event_id", event.id).order("sort_order"),
  supabase.from("teams").select("id", { count: "exact", head: true }).eq("event_id", event.id),
  supabase.from("registrations").select("id", { count: "exact", head: true }).eq("event_id", event.id),
  supabase.from("email_templates").select("slug", { count: "exact" }).eq("event_id", event.id).order("slug"),
]);

const requiredFailures = [
  (eventSettings.count ?? 0) > 0 ? null : "event_settings is missing",
  (cmsPages.count ?? 0) > 0 ? null : "cms_pages is empty",
  (venues.count ?? 0) > 0 ? null : "venues is empty",
  (contactRoles.count ?? 0) > 0 ? null : "contact_roles is empty",
  (navigation.count ?? 0) > 0 ? null : "site_navigation is empty",
].filter(Boolean);

const warnings = [
  (emailTemplates.count ?? 0) > 0 ? null : "email_templates is empty",
  (teams.count ?? 0) > 0 ? null : "teams is empty for the active event",
  (registrations.count ?? 0) > 0 ? null : "registrations is empty for the active event",
].filter(Boolean);

console.log(
  JSON.stringify(
    {
      event,
      counts: {
        eventSettings: eventSettings.count ?? 0,
        cmsPages: cmsPages.count ?? 0,
        venues: venues.count ?? 0,
        contactRoles: contactRoles.count ?? 0,
        navigation: navigation.count ?? 0,
        teams: teams.count ?? 0,
        registrations: registrations.count ?? 0,
        emailTemplates: emailTemplates.count ?? 0,
      },
      cmsPages: cmsPages.data ?? [],
      venues: (venues.data ?? []).map((venue) => venue.name),
      contactRoles: (contactRoles.data ?? []).map((role) => role.slug),
      navigation: (navigation.data ?? []).map((item) => item.href),
      emailTemplates: (emailTemplates.data ?? []).map((template) => template.slug),
      requiredFailures,
      warnings,
    },
    null,
    2,
  ),
);

if (requiredFailures.length) {
  process.exit(1);
}
