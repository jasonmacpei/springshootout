import { createAdminClientFromLocalEnv, getEventBySlug } from "./lib.mjs";

const templates = [
  {
    slug: "registration-confirmation",
    subject: "Spring Shootout registration received",
    html_body: `
      <p>Thanks for registering for Spring Shootout.</p>
      <p>We have received your team submission and will review it with the event staff. If anything is missing, we will follow up using the contact details on file.</p>
      <p>You can reply to this message if travel plans, roster notes, or division details change before the weekend.</p>
    `.trim(),
    text_body:
      "Thanks for registering for Spring Shootout. We have received your team submission and will review it with the event staff. Reply to this message if travel plans, roster notes, or division details change before the weekend.",
  },
  {
    slug: "additional-contact-confirmation",
    subject: "Spring Shootout contact update received",
    html_body: `
      <p>Your additional team contact has been received.</p>
      <p>The Spring Shootout staff will use this information for logistics, schedule updates, and tournament communication as needed.</p>
      <p>If you submitted anything in error, reply with the corrected details and we will update the team record.</p>
    `.trim(),
    text_body:
      "Your additional team contact has been received. The Spring Shootout staff will use this information for logistics, schedule updates, and tournament communication as needed.",
  },
  {
    slug: "team-broadcast",
    subject: "Spring Shootout update",
    html_body: `
      <p>Hello coaches and team contacts,</p>
      <p>This template is the default starting point for event-wide broadcast messages from Spring Shootout. Replace the content with the current update before sending.</p>
      <p>Include venue changes, registration reminders, arrival notes, or game-day instructions here.</p>
    `.trim(),
    text_body:
      "Hello coaches and team contacts. This is the default starting point for event-wide broadcast messages from Spring Shootout. Replace the content with the current update before sending.",
  },
];

const { env, supabase } = createAdminClientFromLocalEnv();
const eventSlug = process.argv[2] || env.NEXT_PUBLIC_DEFAULT_EVENT_SLUG || "spring-shootout-2026";
const event = await getEventBySlug(supabase, eventSlug);

if (!event) {
  console.error(`Event '${eventSlug}' was not found.`);
  process.exit(1);
}

const rows = templates.map((template) => ({
  event_id: event.id,
  slug: template.slug,
  subject: template.subject,
  html_body: template.html_body,
  text_body: template.text_body,
  is_active: true,
}));

const { data, error } = await supabase
  .from("email_templates")
  .upsert(rows, {
    onConflict: "event_id,slug",
  })
  .select("id, slug, subject, is_active");

if (error) {
  throw error;
}

console.log(
  JSON.stringify(
    {
      event: {
        slug: event.slug,
        name: event.name,
      },
      upserted: data ?? [],
    },
    null,
    2,
  ),
);
