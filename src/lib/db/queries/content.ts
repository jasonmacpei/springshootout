import {
  fallbackCmsPages,
  fallbackContactRoles,
  fallbackEvent,
  fallbackEventSettings,
  fallbackVenues,
  type FallbackVenue,
  type SimpleCmsPage,
} from "@/lib/cms/fallbacks";
import { appConfig, shouldUseContentFallbacks } from "@/lib/config";
import { createServerSupabaseClient } from "@/lib/db/server";

type CmsSectionRecord = {
  id?: string;
  heading: string | null;
  body: string | null;
  sort_order?: number | null;
};

type RegistrationQueryRow = {
  id: string;
  team_id: string | null;
  status: "pending" | "approved" | "waitlisted" | "withdrawn";
  created_at: string;
  division_name: string | null;
  class_name: string | null;
  province: string | null;
  note: string | null;
  teams: { name: string | null } | Array<{ name: string | null }> | null;
  contacts:
    | { id: string | null; full_name: string | null; email: string | null; phone: string | null }
    | Array<{ id: string | null; full_name: string | null; email: string | null; phone: string | null }>
    | null;
};

type TeamContactsQueryRow = {
  contacts:
    | { id: string; full_name: string | null; email: string | null; phone: string | null }
    | Array<{ id: string; full_name: string | null; email: string | null; phone: string | null }>
    | null;
  teams: { event_id: string; name: string | null } | Array<{ event_id: string; name: string | null }> | null;
};

type EmailCampaignQueryRow = {
  id: string;
  subject: string;
  status: "draft" | "scheduled" | "sent" | "failed";
  created_at: string;
  sent_at: string | null;
  email_templates: { slug: string | null } | Array<{ slug: string | null }> | null;
};

type EmailDeliveryQueryRow = {
  id: string;
  campaign_id: string;
  recipient_email: string;
  recipient_name: string | null;
  provider_message_id: string | null;
  delivery_status: string | null;
  created_at: string;
  error_text: string | null;
  email_campaigns: { subject: string | null } | Array<{ subject: string | null }> | null;
};

export type CmsEditorPage = {
  id: string | null;
  slug: string;
  title: string;
  subtitle: string;
  status: "draft" | "published";
  sections: Array<{
    id?: string;
    heading: string;
    body: string;
    sortOrder: number;
  }>;
};

export type EventSettingsRecord = {
  hero_eyebrow: string | null;
  hero_title: string | null;
  hero_description: string | null;
  support_email: string | null;
  support_phone: string | null;
  registration_status: string | null;
};

export type TeamDirectoryGroup = {
  divisionName: string;
  entries: Array<{
    id: string;
    name: string;
    className: string | null;
    province: string | null;
  }>;
};

export type TeamOption = {
  id: string;
  name: string;
  divisionName: string | null;
  className: string | null;
};

export type ContactOption = {
  id: string;
  fullName: string;
  email: string | null;
};

export type ContactRoleOption = {
  id: string;
  name: string;
  slug: string;
};

export type RegistrationAdminRow = {
  id: string;
  teamId: string | null;
  status: "pending" | "approved" | "waitlisted" | "withdrawn";
  createdAt: string;
  teamName: string;
  divisionName: string | null;
  className: string | null;
  province: string | null;
  primaryContactId: string | null;
  primaryContactName: string | null;
  primaryContactEmail: string | null;
  primaryContactPhone: string | null;
  note: string | null;
};

export type TeamAdminRow = {
  id: string;
  name: string;
  divisionName: string | null;
  className: string | null;
  province: string | null;
  registrationStatus: string | null;
  primaryContactName: string | null;
  primaryContactEmail: string | null;
};

export type ContactAdminRow = {
  id: string;
  fullName: string;
  email: string | null;
  phone: string | null;
  teams: string[];
};

export type EmailTemplateAdminRow = {
  id: string;
  slug: string;
  subject: string;
  htmlBody: string;
  textBody: string;
  isActive: boolean;
};

export type EmailCampaignAdminRow = {
  id: string;
  subject: string;
  status: "draft" | "scheduled" | "sent" | "failed";
  createdAt: string;
  sentAt: string | null;
  templateSlug: string | null;
  recipientCount: number;
  deliveredCount: number;
};

export type EmailDeliveryAdminRow = {
  id: string;
  campaignId: string;
  campaignSubject: string | null;
  recipientEmail: string;
  recipientName: string | null;
  providerMessageId: string | null;
  deliveryStatus: string | null;
  createdAt: string;
  errorText: string | null;
};

export type AuditLogAdminRow = {
  id: string;
  entityType: string;
  entityId: string | null;
  action: string;
  createdAt: string;
};

export type AdminEventSummary = {
  id: string;
  slug: string;
  name: string;
  startsOn: string | null;
  endsOn: string | null;
  isActive: boolean;
  providerEventSlug: string | null;
  source: "database" | "fallback";
};

export type EventSettingsEditor = {
  id: string;
  slug: string;
  name: string;
  startsOn: string | null;
  endsOn: string | null;
  isActive: boolean;
  providerEventSlug: string | null;
  providerVisibilityMode: string | null;
  heroEyebrow: string;
  heroTitle: string;
  heroDescription: string;
  supportEmail: string;
  supportPhone: string;
  registrationStatus: string;
  source: "database" | "fallback";
};

export type AdminDashboardSummary = {
  cmsPages: number;
  venues: number;
  registrations: number;
  contacts: number;
  usingFallbackData: boolean;
  missingItems: string[];
};

function normalizeSections(sections: CmsSectionRecord[] | null | undefined) {
  return (
    sections
      ?.slice()
      .sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0))
      .map((section, index) => ({
        id: section.id,
        heading: section.heading ?? `Section ${index + 1}`,
        body: section.body ?? "",
        sortOrder: section.sort_order ?? index,
      })) ?? []
  );
}

function normalizeSimplePage(page: {
  slug: string;
  title: string;
  subtitle?: string | null;
  cms_sections?: CmsSectionRecord[] | null;
}): SimpleCmsPage {
  return {
    slug: page.slug,
    title: page.title,
    subtitle: page.subtitle ?? "",
    sections: normalizeSections(page.cms_sections).map((section) => ({
      heading: section.heading,
      body: section.body,
    })),
  };
}

async function resolveEventIdBySlug(slug: string) {
  const supabase = await createServerSupabaseClient();

  if (!supabase) {
    return null;
  }

  const { data } = await supabase.from("events").select("id").eq("slug", slug).maybeSingle();
  return data?.id ?? null;
}

export async function getCompetitionEventSlugByLocalSlug(localSlug: string = appConfig.defaultEventSlug) {
  const event = await getEventBySlug(localSlug);
  return event?.provider_event_slug ?? event?.slug ?? localSlug;
}

export async function getEventBySlug(slug: string = appConfig.defaultEventSlug) {
  const supabase = await createServerSupabaseClient();
  const allowFallbacks = shouldUseContentFallbacks();

  if (!supabase) {
    return allowFallbacks ? fallbackEvent : null;
  }

  const { data } = await supabase.from("events").select("*").eq("slug", slug).maybeSingle();

  return data ?? (allowFallbacks ? fallbackEvent : null);
}

export async function getEventSettingsBySlug(slug: string = appConfig.defaultEventSlug) {
  const supabase = await createServerSupabaseClient();
  const allowFallbacks = shouldUseContentFallbacks();

  if (!supabase) {
    return allowFallbacks ? fallbackEventSettings : null;
  }

  const { data } = await supabase
    .from("event_settings")
    .select("hero_eyebrow, hero_title, hero_description, support_email, support_phone, registration_status, events!inner(slug)")
    .eq("events.slug", slug)
    .maybeSingle();

  if (!data) {
    return allowFallbacks ? fallbackEventSettings : null;
  }

  return allowFallbacks
    ? {
        ...fallbackEventSettings,
        ...data,
      }
    : data;
}

export async function getCmsPageBySlug(slug: string, eventSlug: string = appConfig.defaultEventSlug) {
  const supabase = await createServerSupabaseClient();
  const allowFallbacks = shouldUseContentFallbacks();

  if (!supabase) {
    return allowFallbacks ? (fallbackCmsPages[slug] ?? null) : null;
  }

  const { data: page } = await supabase
    .from("cms_pages")
    .select("slug, title, subtitle, cms_sections(id, heading, body, sort_order), events!inner(slug)")
    .eq("slug", slug)
    .eq("events.slug", eventSlug)
    .maybeSingle();

  if (!page) {
    return allowFallbacks ? (fallbackCmsPages[slug] ?? null) : null;
  }

  return normalizeSimplePage(page);
}

export async function getCmsEditorPageBySlug(slug: string, eventSlug: string = appConfig.defaultEventSlug) {
  const supabase = await createServerSupabaseClient();
  const fallback = fallbackCmsPages[slug];
  const allowFallbacks = shouldUseContentFallbacks();

  if (!supabase) {
    return allowFallbacks && fallback
      ? {
          id: null,
          slug: fallback.slug,
          title: fallback.title,
          subtitle: fallback.subtitle,
          status: fallback.status ?? "published",
          source: "fallback" as const,
          sections: fallback.sections.map((section, index) => ({
            heading: section.heading,
            body: section.body,
            sortOrder: index,
          })),
        }
      : null;
  }

  const { data: page } = await supabase
    .from("cms_pages")
    .select("id, slug, title, subtitle, status, cms_sections(id, heading, body, sort_order), events!inner(slug)")
    .eq("slug", slug)
    .eq("events.slug", eventSlug)
    .maybeSingle();

  if (!page && (!fallback || !allowFallbacks)) {
    return null;
  }

  if (!page && fallback && allowFallbacks) {
    return {
      id: null,
      slug: fallback.slug,
      title: fallback.title,
      subtitle: fallback.subtitle,
      status: fallback.status ?? "published",
      source: "fallback" as const,
      sections: fallback.sections.map((section, index) => ({
        heading: section.heading,
        body: section.body,
        sortOrder: index,
      })),
    };
  }

  if (!page) {
    return null;
  }

  return {
    id: page.id,
    slug: page.slug,
    title: page.title,
    subtitle: page.subtitle ?? "",
    status: (page.status ?? "draft") as "draft" | "published",
    source: "database",
    sections: normalizeSections(page.cms_sections),
  };
}

export async function listCmsPages(eventSlug: string = appConfig.defaultEventSlug) {
  const supabase = await createServerSupabaseClient();
  const allowFallbacks = shouldUseContentFallbacks();

  if (!supabase) {
    return allowFallbacks ? Object.values(fallbackCmsPages) : [];
  }

  const { data } = await supabase
    .from("cms_pages")
    .select("slug, title, subtitle, status, events!inner(slug)")
    .eq("events.slug", eventSlug)
    .order("slug");

  if (!data?.length) {
    return allowFallbacks ? Object.values(fallbackCmsPages) : [];
  }

  return data.map((page) => ({
    slug: page.slug,
    title: page.title,
    subtitle: page.subtitle ?? "",
    status: page.status ?? "draft",
  }));
}

export async function getVenuesByEventSlug(eventSlug: string = appConfig.defaultEventSlug) {
  const supabase = await createServerSupabaseClient();
  const allowFallbacks = shouldUseContentFallbacks();

  if (!supabase) {
    return allowFallbacks ? fallbackVenues : [];
  }

  const { data } = await supabase
    .from("venues")
    .select("name, address, map_url, notes, display_order, events!inner(slug)")
    .eq("events.slug", eventSlug)
    .order("display_order");

  if (!data?.length) {
    return allowFallbacks ? fallbackVenues : [];
  }

  return data.map(
    (venue): FallbackVenue => ({
      name: venue.name,
      address: venue.address ?? "",
      mapUrl: venue.map_url ?? "",
      notes: venue.notes ?? "",
    }),
  );
}

export async function getTeamDirectoryByEventSlug(eventSlug: string = appConfig.defaultEventSlug) {
  const supabase = await createServerSupabaseClient();

  if (!supabase) {
    return [] as TeamDirectoryGroup[];
  }

  const { data } = await supabase
    .from("teams")
    .select("id, name, division_name, class_name, province, events!inner(slug)")
    .eq("events.slug", eventSlug)
    .order("division_name")
    .order("name");

  if (!data?.length) {
    return [] as TeamDirectoryGroup[];
  }

  const grouped = data.reduce<Record<string, TeamDirectoryGroup["entries"]>>((acc, team) => {
    const key = team.division_name ?? "Division TBD";
    acc[key] ??= [];
    acc[key].push({
      id: team.id,
      name: team.name,
      className: team.class_name ?? null,
      province: team.province ?? null,
    });
    return acc;
  }, {});

  return Object.entries(grouped).map(([divisionName, entries]) => ({
    divisionName,
    entries,
  }));
}

export async function listContactRoles() {
  const supabase = await createServerSupabaseClient();
  const allowFallbacks = shouldUseContentFallbacks();

  if (!supabase) {
    return allowFallbacks ? fallbackContactRoles : [];
  }

  const { data } = await supabase.from("contact_roles").select("slug, name").order("name");

  if (!data?.length) {
    return allowFallbacks ? fallbackContactRoles : [];
  }

  return data;
}

export async function listTeamOptionsByEventSlug(eventSlug: string = appConfig.defaultEventSlug) {
  const supabase = await createServerSupabaseClient();

  if (!supabase) {
    return [] as TeamOption[];
  }

  const { data } = await supabase
    .from("teams")
    .select("id, name, division_name, class_name, events!inner(slug)")
    .eq("events.slug", eventSlug)
    .order("name");

  if (!data?.length) {
    return [] as TeamOption[];
  }

  return data.map((team) => ({
    id: team.id,
    name: team.name,
    divisionName: team.division_name ?? null,
    className: team.class_name ?? null,
  }));
}

export async function listEventsForAdmin() {
  const supabase = await createServerSupabaseClient();
  const allowFallbacks = shouldUseContentFallbacks();

  if (!supabase) {
    return allowFallbacks
      ? [
          {
            id: fallbackEvent.id,
            slug: fallbackEvent.slug,
            name: fallbackEvent.name,
            startsOn: fallbackEvent.starts_on ?? null,
            endsOn: fallbackEvent.ends_on ?? null,
            isActive: true,
            providerEventSlug: fallbackEvent.provider_event_slug ?? null,
            source: "fallback",
          },
        ]
      : [];
  }

  const { data } = await supabase.from("events").select("id, slug, name, starts_on, ends_on, is_active, provider_event_slug").order("starts_on", { ascending: false });

  return (
    data?.map((event) => ({
      id: event.id,
      slug: event.slug,
      name: event.name,
      startsOn: event.starts_on ?? null,
      endsOn: event.ends_on ?? null,
      isActive: event.is_active ?? false,
      providerEventSlug: event.provider_event_slug ?? null,
      source: "database",
    })) ?? []
  );
}

export async function getEventSettingsEditorBySlug(eventSlug: string) {
  const supabase = await createServerSupabaseClient();
  const allowFallbacks = shouldUseContentFallbacks();

  if (!supabase) {
    return allowFallbacks
      ? {
          id: fallbackEvent.id,
          slug: fallbackEvent.slug,
          name: fallbackEvent.name,
          startsOn: fallbackEvent.starts_on ?? null,
          endsOn: fallbackEvent.ends_on ?? null,
          isActive: true,
          providerEventSlug: fallbackEvent.provider_event_slug ?? null,
          providerVisibilityMode: "public_live",
          heroEyebrow: fallbackEventSettings.hero_eyebrow ?? "",
          heroTitle: fallbackEventSettings.hero_title ?? "",
          heroDescription: fallbackEventSettings.hero_description ?? "",
          supportEmail: fallbackEventSettings.support_email ?? "",
          supportPhone: fallbackEventSettings.support_phone ?? "",
          registrationStatus: fallbackEventSettings.registration_status ?? "",
          source: "fallback",
        }
      : null;
  }

  const { data } = await supabase
    .from("events")
    .select(
      "id, slug, name, starts_on, ends_on, is_active, provider_event_slug, provider_visibility_mode, event_settings(hero_eyebrow, hero_title, hero_description, support_email, support_phone, registration_status)",
    )
    .eq("slug", eventSlug)
    .maybeSingle();

  if (!data) {
    return allowFallbacks
      ? {
          id: fallbackEvent.id,
          slug: fallbackEvent.slug,
          name: fallbackEvent.name,
          startsOn: fallbackEvent.starts_on ?? null,
          endsOn: fallbackEvent.ends_on ?? null,
          isActive: true,
          providerEventSlug: fallbackEvent.provider_event_slug ?? null,
          providerVisibilityMode: "public_live",
          heroEyebrow: fallbackEventSettings.hero_eyebrow ?? "",
          heroTitle: fallbackEventSettings.hero_title ?? "",
          heroDescription: fallbackEventSettings.hero_description ?? "",
          supportEmail: fallbackEventSettings.support_email ?? "",
          supportPhone: fallbackEventSettings.support_phone ?? "",
          registrationStatus: fallbackEventSettings.registration_status ?? "",
          source: "fallback",
        }
      : null;
  }

  const settings = Array.isArray(data.event_settings) ? data.event_settings[0] : data.event_settings;

  return {
    id: data.id,
    slug: data.slug,
    name: data.name,
    startsOn: data.starts_on ?? null,
    endsOn: data.ends_on ?? null,
    isActive: data.is_active ?? false,
    providerEventSlug: data.provider_event_slug ?? null,
    providerVisibilityMode: data.provider_visibility_mode ?? null,
    heroEyebrow: settings?.hero_eyebrow ?? "",
    heroTitle: settings?.hero_title ?? "",
    heroDescription: settings?.hero_description ?? "",
    supportEmail: settings?.support_email ?? "",
    supportPhone: settings?.support_phone ?? "",
    registrationStatus: settings?.registration_status ?? "",
    source: "database",
  } as EventSettingsEditor;
}

export async function listRegistrationsForAdmin(eventSlug: string = appConfig.defaultEventSlug) {
  const supabase = await createServerSupabaseClient();

  if (!supabase) {
    return [] as RegistrationAdminRow[];
  }

  const eventId = await resolveEventIdBySlug(eventSlug);

  if (!eventId) {
    return [] as RegistrationAdminRow[];
  }

  const { data } = await supabase
    .from("registrations")
    .select(
      "id, team_id, status, created_at, division_name, class_name, province, note, teams(name), contacts!registrations_primary_contact_id_fkey(id, full_name, email, phone)",
    )
    .eq("event_id", eventId)
    .order("created_at", { ascending: false });

  const rows = (data ?? []) as RegistrationQueryRow[];

  return rows.map((registration) => ({
      id: registration.id,
      teamId: registration.team_id ?? null,
      status: registration.status,
      createdAt: registration.created_at,
      teamName: Array.isArray(registration.teams) ? registration.teams[0]?.name ?? "Team pending" : registration.teams?.name ?? "Team pending",
      divisionName: registration.division_name ?? null,
      className: registration.class_name ?? null,
      province: registration.province ?? null,
      primaryContactId: Array.isArray(registration.contacts)
        ? registration.contacts[0]?.id ?? null
        : registration.contacts?.id ?? null,
      primaryContactName: Array.isArray(registration.contacts)
        ? registration.contacts[0]?.full_name ?? null
        : registration.contacts?.full_name ?? null,
      primaryContactEmail: Array.isArray(registration.contacts)
        ? registration.contacts[0]?.email ?? null
        : registration.contacts?.email ?? null,
      primaryContactPhone: Array.isArray(registration.contacts)
        ? registration.contacts[0]?.phone ?? null
        : registration.contacts?.phone ?? null,
      note: registration.note ?? null,
    }));
}

export async function listTeamsForAdmin(eventSlug: string = appConfig.defaultEventSlug) {
  const registrations = await listRegistrationsForAdmin(eventSlug);
  const supabase = await createServerSupabaseClient();

  if (!supabase) {
    return [] as TeamAdminRow[];
  }

  const eventId = await resolveEventIdBySlug(eventSlug);

  if (!eventId) {
    return [] as TeamAdminRow[];
  }

  const { data } = await supabase
    .from("teams")
    .select("id, name, division_name, class_name, province")
    .eq("event_id", eventId)
    .order("division_name")
    .order("name");

  return (
    data?.map((team) => {
      const registration = registrations.find((row) => row.teamId === team.id);

      return {
        id: team.id,
        name: team.name,
        divisionName: team.division_name ?? null,
        className: team.class_name ?? null,
        province: team.province ?? null,
        registrationStatus: registration?.status ?? null,
        primaryContactName: registration?.primaryContactName ?? null,
        primaryContactEmail: registration?.primaryContactEmail ?? null,
      };
    }) ?? []
  );
}

export async function listContactsForAdmin(eventSlug: string = appConfig.defaultEventSlug) {
  const supabase = await createServerSupabaseClient();

  if (!supabase) {
    return [] as ContactAdminRow[];
  }

  const eventId = await resolveEventIdBySlug(eventSlug);

  if (!eventId) {
    return [] as ContactAdminRow[];
  }

  const [{ data: contacts }, { data: links }] = await Promise.all([
    supabase.from("contacts").select("id, full_name, email, phone").order("full_name"),
    supabase
      .from("team_contacts")
      .select("contacts(id, full_name, email, phone), teams!inner(event_id, name)")
      .eq("teams.event_id", eventId),
  ]);

  const rows = (links ?? []) as TeamContactsQueryRow[];
  const grouped = new Map<string, ContactAdminRow>(
    (contacts ?? []).map((contact) => [
      contact.id,
      {
        id: contact.id,
        fullName: contact.full_name ?? "Contact pending",
        email: contact.email ?? null,
        phone: contact.phone ?? null,
        teams: [],
      } satisfies ContactAdminRow,
    ]),
  );

  rows.forEach((row) => {
    const contact = Array.isArray(row.contacts) ? row.contacts[0] : row.contacts;
    const team = Array.isArray(row.teams) ? row.teams[0] : row.teams;

    if (!contact?.id) {
      return;
    }

    const existing = grouped.get(contact.id) ?? {
      id: contact.id,
      fullName: contact.full_name ?? "Contact pending",
      email: contact.email ?? null,
      phone: contact.phone ?? null,
      teams: [],
    };

    if (team?.name && !existing.teams.includes(team.name)) {
      existing.teams.push(team.name);
    }

    grouped.set(contact.id, existing);
  });

  return Array.from(grouped.values()).sort((a, b) => a.fullName.localeCompare(b.fullName));
}

export async function listContactOptionsForAdmin() {
  const supabase = await createServerSupabaseClient();

  if (!supabase) {
    return [] as ContactOption[];
  }

  const { data } = await supabase.from("contacts").select("id, full_name, email").order("full_name");

  return (
    data?.map((contact) => ({
      id: contact.id,
      fullName: contact.full_name,
      email: contact.email ?? null,
    })) ?? []
  );
}

export async function listTeamOptionsForAdmin(eventSlug: string = appConfig.defaultEventSlug) {
  const supabase = await createServerSupabaseClient();

  if (!supabase) {
    return [] as TeamOption[];
  }

  const eventId = await resolveEventIdBySlug(eventSlug);

  if (!eventId) {
    return [] as TeamOption[];
  }

  const { data } = await supabase
    .from("teams")
    .select("id, name, division_name, class_name")
    .eq("event_id", eventId)
    .order("division_name")
    .order("name");

  return (
    data?.map((team) => ({
      id: team.id,
      name: team.name,
      divisionName: team.division_name ?? null,
      className: team.class_name ?? null,
    })) ?? []
  );
}

export async function listContactRoleOptionsForAdmin() {
  const supabase = await createServerSupabaseClient();

  if (!supabase) {
    return fallbackContactRoles.map((role) => ({
      id: role.slug,
      name: role.name,
      slug: role.slug,
    })) as ContactRoleOption[];
  }

  const { data } = await supabase.from("contact_roles").select("id, name, slug").order("name");

  return (
    data?.map((role) => ({
      id: role.id,
      name: role.name,
      slug: role.slug,
    })) ?? []
  );
}

export async function listEmailTemplatesForAdmin(eventSlug: string = appConfig.defaultEventSlug) {
  const supabase = await createServerSupabaseClient();

  if (!supabase) {
    return [] as EmailTemplateAdminRow[];
  }

  const eventId = await resolveEventIdBySlug(eventSlug);

  if (!eventId) {
    return [] as EmailTemplateAdminRow[];
  }

  const { data } = await supabase
    .from("email_templates")
    .select("id, slug, subject, html_body, text_body, is_active")
    .eq("event_id", eventId)
    .order("slug");

  return (
    data?.map((template) => ({
      id: template.id,
      slug: template.slug,
      subject: template.subject,
      htmlBody: template.html_body ?? "",
      textBody: template.text_body ?? "",
      isActive: template.is_active ?? true,
    })) ?? []
  );
}

export async function listEmailCampaignsForAdmin(eventSlug: string = appConfig.defaultEventSlug) {
  const supabase = await createServerSupabaseClient();

  if (!supabase) {
    return [] as EmailCampaignAdminRow[];
  }

  const eventId = await resolveEventIdBySlug(eventSlug);

  if (!eventId) {
    return [] as EmailCampaignAdminRow[];
  }

  const { data } = await supabase
    .from("email_campaigns")
    .select("id, subject, status, created_at, sent_at, email_templates(slug)")
    .eq("event_id", eventId)
    .order("created_at", { ascending: false });

  const rows = (data ?? []) as EmailCampaignQueryRow[];

  const campaignIds = rows.map((campaign) => campaign.id);
  const { data: deliveries } = campaignIds.length
    ? await supabase
        .from("email_deliveries")
        .select("campaign_id, delivery_status")
        .in("campaign_id", campaignIds)
    : { data: [] };

  const deliverySummary = (deliveries ?? []).reduce<Record<string, { recipientCount: number; deliveredCount: number }>>(
    (acc, delivery) => {
      acc[delivery.campaign_id] ??= { recipientCount: 0, deliveredCount: 0 };
      acc[delivery.campaign_id].recipientCount += 1;
      if (delivery.delivery_status === "delivered") {
        acc[delivery.campaign_id].deliveredCount += 1;
      }
      return acc;
    },
    {},
  );

  return rows.map((campaign) => ({
      id: campaign.id,
      subject: campaign.subject,
      status: campaign.status,
      createdAt: campaign.created_at,
      sentAt: campaign.sent_at ?? null,
      templateSlug: Array.isArray(campaign.email_templates)
        ? campaign.email_templates[0]?.slug ?? null
        : campaign.email_templates?.slug ?? null,
      recipientCount: deliverySummary[campaign.id]?.recipientCount ?? 0,
      deliveredCount: deliverySummary[campaign.id]?.deliveredCount ?? 0,
    }));
}

export async function listRecentEmailDeliveriesForAdmin(
  eventSlug: string = appConfig.defaultEventSlug,
  {
    status,
    campaignId,
    limit = 25,
  }: {
    status?: string | null;
    campaignId?: string | null;
    limit?: number;
  } = {},
) {
  const supabase = await createServerSupabaseClient();

  if (!supabase) {
    return [] as EmailDeliveryAdminRow[];
  }

  const eventId = await resolveEventIdBySlug(eventSlug);

  if (!eventId) {
    return [] as EmailDeliveryAdminRow[];
  }

  let query = supabase
    .from("email_deliveries")
    .select("id, campaign_id, recipient_email, recipient_name, provider_message_id, delivery_status, created_at, error_text, email_campaigns!inner(subject, event_id)")
    .eq("email_campaigns.event_id", eventId)
    .order("created_at", { ascending: false })
    .limit(limit);

  if (status?.trim()) {
    query = query.eq("delivery_status", status.trim());
  }

  if (campaignId?.trim()) {
    query = query.eq("campaign_id", campaignId.trim());
  }

  const { data } = await query;

  const rows = (data ?? []) as EmailDeliveryQueryRow[];

  return rows.map((delivery) => ({
    id: delivery.id,
    campaignId: delivery.campaign_id,
    campaignSubject: Array.isArray(delivery.email_campaigns)
      ? delivery.email_campaigns[0]?.subject ?? null
      : delivery.email_campaigns?.subject ?? null,
    recipientEmail: delivery.recipient_email,
    recipientName: delivery.recipient_name ?? null,
    providerMessageId: delivery.provider_message_id ?? null,
    deliveryStatus: delivery.delivery_status ?? null,
    createdAt: delivery.created_at,
    errorText: delivery.error_text ?? null,
  }));
}

export async function listRecentAuditLogs(limit: number = 25) {
  const supabase = await createServerSupabaseClient();

  if (!supabase) {
    return [] as AuditLogAdminRow[];
  }

  const { data } = await supabase
    .from("audit_logs")
    .select("id, entity_type, entity_id, action, created_at")
    .order("created_at", { ascending: false })
    .limit(limit);

  return (
    data?.map((entry) => ({
      id: entry.id,
      entityType: entry.entity_type,
      entityId: entry.entity_id ?? null,
      action: entry.action,
      createdAt: entry.created_at,
    })) ?? []
  );
}

export async function getAdminDashboardSummary(eventSlug: string = appConfig.defaultEventSlug) {
  const supabase = await createServerSupabaseClient();
  const allowFallbacks = shouldUseContentFallbacks();

  if (!supabase) {
    return {
      cmsPages: allowFallbacks ? Object.keys(fallbackCmsPages).length : 0,
      venues: allowFallbacks ? fallbackVenues.length : 0,
      registrations: 0,
      contacts: 0,
      usingFallbackData: allowFallbacks,
      missingItems: allowFallbacks ? ["Supabase is not configured. The dashboard is rendering seed fallback content only."] : ["Supabase is not configured."],
    };
  }

  const { data: eventRecord } = await supabase.from("events").select("id").eq("slug", eventSlug).maybeSingle();

  if (!eventRecord?.id) {
    return {
      cmsPages: allowFallbacks ? Object.keys(fallbackCmsPages).length : 0,
      venues: allowFallbacks ? fallbackVenues.length : 0,
      registrations: 0,
      contacts: 0,
      usingFallbackData: allowFallbacks,
      missingItems: [
        allowFallbacks
          ? `The ${eventSlug} event is missing in Supabase. Seed fallback data is covering the public site.`
          : `The ${eventSlug} event is missing in Supabase.`,
      ],
    };
  }

  const [{ count: cmsPages }, { count: venues }, { count: registrations }, { count: contacts }, { count: eventSettings }, { count: contactRoles }] =
    await Promise.all([
      supabase.from("cms_pages").select("*", { count: "exact", head: true }).eq("event_id", eventRecord.id),
      supabase.from("venues").select("*", { count: "exact", head: true }).eq("event_id", eventRecord.id),
      supabase.from("registrations").select("*", { count: "exact", head: true }).eq("event_id", eventRecord.id),
      supabase.from("contacts").select("*", { count: "exact", head: true }),
      supabase.from("event_settings").select("*", { count: "exact", head: true }).eq("event_id", eventRecord.id),
      supabase.from("contact_roles").select("*", { count: "exact", head: true }),
    ]);

  const missingItems = [
    (eventSettings ?? 0) > 0 ? null : "Event settings are missing for the active event.",
    (cmsPages ?? 0) > 0 ? null : "No CMS pages are seeded for the active event.",
    (venues ?? 0) > 0 ? null : "No venues are seeded for the active event.",
    (contactRoles ?? 0) > 0 ? null : "No contact roles are configured, which blocks registration forms.",
  ].filter((item): item is string => Boolean(item));

  return {
    cmsPages: cmsPages ?? 0,
    venues: venues ?? 0,
    registrations: registrations ?? 0,
    contacts: contacts ?? 0,
    usingFallbackData: false,
    missingItems,
  };
}
