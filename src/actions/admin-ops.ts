"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";

import { assignableStaffRoles, canDeleteRecords, canManageComms, canManageSettings, canManageStaff } from "@/lib/auth/roles";
import { requireStaffSession } from "@/lib/auth/session";
import { logAuditEvent } from "@/lib/audit";
import { createServerSupabaseClient } from "@/lib/db/server";
import type { RegistrationAdminRow } from "@/lib/db/queries/content";
import { createAdminSupabaseClient } from "@/lib/db/admin";
import { addCampaignRecipient, type CampaignRecipient } from "@/lib/email/audience";
import { createResendClient } from "@/lib/email/client";
import { buildCampaignEmailContent } from "@/lib/email/render";
import { appConfig } from "@/lib/config";
import {
  contactCreateSchema,
  registrationAdminSchema,
  registrationCreateSchema,
  staffAccessRevokeSchema,
  staffInviteSchema,
  staffRoleUpdateSchema,
  teamCreateSchema,
} from "@/lib/validation/forms";

function redirectWithError(path: string, message: string): never {
  const params = new URLSearchParams({ error: message });
  redirect(`${path}?${params.toString()}`);
}

const campaignAudienceScopes = ["all_contacts", "approved_only", "pending_only"] as const;
type CampaignAudienceScope = (typeof campaignAudienceScopes)[number];
type AdminSupabaseClient = NonNullable<ReturnType<typeof createAdminSupabaseClient>>;

function normalizeAudienceScope(input: FormDataEntryValue | null) {
  const scope = String(input ?? "all_contacts").trim();

  return campaignAudienceScopes.includes(scope as (typeof campaignAudienceScopes)[number])
    ? (scope as (typeof campaignAudienceScopes)[number])
    : null;
}

async function loadCampaignAudience({
  supabase,
  eventId,
  audienceScope,
}: {
  supabase: AdminSupabaseClient;
  eventId: string;
  audienceScope: CampaignAudienceScope;
}) {
  const uniqueRecipients = new Map<string, CampaignRecipient>();

  if (audienceScope === "approved_only" || audienceScope === "pending_only") {
    const targetStatus: RegistrationAdminRow["status"] =
      audienceScope === "approved_only" ? "approved" : "pending";
    const { data: registrations, error: registrationsError } = await supabase
      .from("registrations")
      .select("team_id, contacts!registrations_primary_contact_id_fkey(id, full_name, email)")
      .eq("event_id", eventId)
      .eq("status", targetStatus);

    if (registrationsError) {
      throw new Error(registrationsError.message);
    }

    (registrations ?? []).forEach((registration) => {
      addCampaignRecipient(uniqueRecipients, registration.contacts);
    });

    const teamIds = Array.from(
      new Set((registrations ?? []).map((row) => row.team_id).filter((teamId): teamId is string => Boolean(teamId))),
    );

    if (teamIds.length) {
      const { data: teamContacts, error: teamContactsError } = await supabase
        .from("team_contacts")
        .select("contacts(id, full_name, email)")
        .in("team_id", teamIds);

      if (teamContactsError) {
        throw new Error(teamContactsError.message);
      }

      (teamContacts ?? []).forEach((row) => {
        addCampaignRecipient(uniqueRecipients, row.contacts);
      });
    }
  } else {
    const [{ data: teamContacts, error: teamContactsError }, { data: registrations, error: registrationsError }] =
      await Promise.all([
        supabase
          .from("team_contacts")
          .select("contacts(id, full_name, email), teams!inner(event_id)")
          .eq("teams.event_id", eventId),
        supabase
          .from("registrations")
          .select("contacts!registrations_primary_contact_id_fkey(id, full_name, email)")
          .eq("event_id", eventId),
      ]);

    if (teamContactsError) {
      throw new Error(teamContactsError.message);
    }

    if (registrationsError) {
      throw new Error(registrationsError.message);
    }

    (teamContacts ?? []).forEach((row) => {
      addCampaignRecipient(uniqueRecipients, row.contacts);
    });

    (registrations ?? []).forEach((registration) => {
      addCampaignRecipient(uniqueRecipients, registration.contacts);
    });
  }

  return Array.from(uniqueRecipients.values());
}

async function resolveEventRecord(client: NonNullable<Awaited<ReturnType<typeof createServerSupabaseClient>>>, eventSlug: string) {
  const { data: event } = await client.from("events").select("id, slug").eq("slug", eventSlug).maybeSingle();
  return event;
}

async function loadRoleAssignmentsForUser(
  client: NonNullable<ReturnType<typeof createAdminSupabaseClient>>,
  userId: string,
) {
  const { data, error } = await client.from("staff_role_assignments").select("role").eq("user_id", userId);

  if (error) {
    return { roles: [] as string[], error };
  }

  return {
    roles: (data ?? []).map((entry) => entry.role),
    error: null,
  };
}

export async function updateRegistrationAction(formData: FormData) {
  const session = await requireStaffSession();

  if (!canManageComms(session.role)) {
    redirectWithError("/admin/registrations", "You do not have permission to update registrations.");
  }

  const supabase = await createServerSupabaseClient();

  if (!supabase) {
    redirectWithError("/admin/registrations", "Supabase is not configured.");
  }

  const client = supabase;

  const parsed = registrationAdminSchema.safeParse({
    registrationId: formData.get("registrationId"),
    status: formData.get("status"),
    primaryContactId: formData.get("primaryContactId"),
    note: formData.get("note"),
  });

  if (!parsed.success) {
    redirectWithError("/admin/registrations", "Registration id and status are required.");
  }

  const { error } = await client
    .from("registrations")
    .update({
      status: parsed.data.status,
      primary_contact_id: parsed.data.primaryContactId || null,
      note: parsed.data.note?.trim() || null,
    })
    .eq("id", parsed.data.registrationId);

  if (error) {
    redirectWithError("/admin/registrations", error.message);
  }

  await logAuditEvent(client, {
    actorUserId: session.user.id,
    entityType: "registration",
    entityId: parsed.data.registrationId,
    action: "updated",
    metadata: {
      status: parsed.data.status,
      primaryContactId: parsed.data.primaryContactId || null,
      hasNote: Boolean(parsed.data.note?.trim()),
    },
  });

  revalidatePath("/admin");
  revalidatePath("/admin/registrations");
  revalidatePath("/admin/teams");
  revalidatePath("/teams");
}

export async function createRegistrationAction(formData: FormData) {
  const session = await requireStaffSession();

  if (!canManageComms(session.role)) {
    redirectWithError("/admin/registrations", "You do not have permission to create registrations.");
  }

  const supabase = await createServerSupabaseClient();

  if (!supabase) {
    redirectWithError("/admin/registrations", "Supabase is not configured.");
  }

  const parsed = registrationCreateSchema.safeParse({
    eventSlug: formData.get("eventSlug"),
    teamId: formData.get("teamId"),
    primaryContactId: formData.get("primaryContactId"),
    roleId: formData.get("roleId"),
    divisionName: formData.get("divisionName"),
    className: formData.get("className"),
    province: formData.get("province"),
    note: formData.get("note"),
    status: formData.get("status"),
  });

  if (!parsed.success) {
    redirectWithError("/admin/registrations", "Enter a valid team, status, and registration details.");
  }

  const event = await resolveEventRecord(supabase, parsed.data.eventSlug);

  if (!event?.id) {
    redirectWithError("/admin/registrations", "Event record could not be found.");
  }

  const { data: team, error: teamError } = await supabase
    .from("teams")
    .select("id")
    .eq("id", parsed.data.teamId)
    .eq("event_id", event.id)
    .maybeSingle();

  if (teamError || !team?.id) {
    redirectWithError("/admin/registrations", teamError?.message ?? "Team does not belong to this event.");
  }

  const { data: existingRegistration } = await supabase
    .from("registrations")
    .select("id")
    .eq("event_id", event.id)
    .eq("team_id", parsed.data.teamId)
    .maybeSingle();

  if (existingRegistration?.id) {
    redirectWithError("/admin/registrations", "That team already has a registration for this event.");
  }

  if (parsed.data.primaryContactId) {
    const { data: contact } = await supabase.from("contacts").select("id").eq("id", parsed.data.primaryContactId).maybeSingle();

    if (!contact?.id) {
      redirectWithError("/admin/registrations", "Primary contact could not be found.");
    }

    const { error: teamContactError } = await supabase.from("team_contacts").upsert(
      {
        team_id: parsed.data.teamId,
        contact_id: parsed.data.primaryContactId,
        role_id: parsed.data.roleId || null,
      },
      { onConflict: "team_id,contact_id" },
    );

    if (teamContactError) {
      redirectWithError("/admin/registrations", teamContactError.message);
    }
  }

  const { data: insertedRegistration, error } = await supabase
    .from("registrations")
    .insert({
      event_id: event.id,
      team_id: parsed.data.teamId,
      primary_contact_id: parsed.data.primaryContactId || null,
      division_name: parsed.data.divisionName?.trim() || null,
      class_name: parsed.data.className?.trim() || null,
      province: parsed.data.province?.trim() || null,
      note: parsed.data.note?.trim() || null,
      status: parsed.data.status,
    })
    .select("id")
    .single();

  if (error) {
    redirectWithError("/admin/registrations", error.message);
  }

  await logAuditEvent(supabase, {
    actorUserId: session.user.id,
    entityType: "registration",
    entityId: insertedRegistration.id,
    action: "created",
    metadata: {
      teamId: parsed.data.teamId,
      status: parsed.data.status,
    },
  });

  revalidatePath("/admin");
  revalidatePath("/admin/registrations");
  revalidatePath("/admin/teams");
  revalidatePath("/teams");
}

export async function inviteStaffMemberAction(formData: FormData) {
  const session = await requireStaffSession();

  if (!canManageStaff(session.role)) {
    redirectWithError("/admin/staff", "You do not have permission to manage staff.");
  }

  const supabase = createAdminSupabaseClient();

  if (!supabase) {
    redirectWithError("/admin/staff", "Server database credentials are not configured.");
  }

  const parsed = staffInviteSchema.safeParse({
    email: formData.get("email"),
    displayName: formData.get("displayName"),
    role: formData.get("role"),
  });

  if (!parsed.success) {
    redirectWithError("/admin/staff", "Enter a valid staff email, display name, and role.");
  }

  if (!assignableStaffRoles(session.role).includes(parsed.data.role)) {
    redirectWithError("/admin/staff", "You are not allowed to assign that role.");
  }

  const { data: listedUsers, error: listError } = await supabase.auth.admin.listUsers({
    page: 1,
    perPage: 200,
  });

  if (listError) {
    redirectWithError("/admin/staff", listError.message);
  }

  const existingUser = (listedUsers?.users ?? []).find(
    (user) => user.email?.trim().toLowerCase() === parsed.data.email.trim().toLowerCase(),
  );

  let targetUserId = existingUser?.id ?? null;

  if (!targetUserId) {
    const { data: invited, error: inviteError } = await supabase.auth.admin.inviteUserByEmail(parsed.data.email, {
      data: {
        full_name: parsed.data.displayName,
      },
    });

    if (inviteError || !invited.user) {
      redirectWithError("/admin/staff", inviteError?.message ?? "Could not invite staff member.");
    }

    targetUserId = invited.user.id;
  }

  if (!targetUserId) {
    redirectWithError("/admin/staff", "Could not resolve the invited staff user.");
  }

  const { error: profileError } = await supabase.from("staff_profiles").upsert(
    {
      user_id: targetUserId,
      display_name: parsed.data.displayName,
    },
    { onConflict: "user_id" },
  );

  if (profileError) {
    redirectWithError("/admin/staff", profileError.message);
  }

  const { error: clearRolesError } = await supabase.from("staff_role_assignments").delete().eq("user_id", targetUserId);

  if (clearRolesError) {
    redirectWithError("/admin/staff", clearRolesError.message);
  }

  const { error: roleError } = await supabase.from("staff_role_assignments").insert({
    user_id: targetUserId,
    role: parsed.data.role,
  });

  if (roleError) {
    redirectWithError("/admin/staff", roleError.message);
  }

  await logAuditEvent(supabase, {
    actorUserId: session.user.id,
    entityType: "staff_user",
    entityId: targetUserId,
    action: existingUser ? "access_granted" : "invited",
    metadata: {
      email: parsed.data.email,
      role: parsed.data.role,
    },
  });

  revalidatePath("/admin/staff");
}

export async function updateStaffMemberRoleAction(formData: FormData) {
  const session = await requireStaffSession();

  if (!canManageStaff(session.role)) {
    redirectWithError("/admin/staff", "You do not have permission to manage staff.");
  }

  const supabase = createAdminSupabaseClient();

  if (!supabase) {
    redirectWithError("/admin/staff", "Server database credentials are not configured.");
  }

  const parsed = staffRoleUpdateSchema.safeParse({
    userId: formData.get("userId"),
    role: formData.get("role"),
  });

  if (!parsed.success) {
    redirectWithError("/admin/staff", "Select a valid staff member and role.");
  }

  if (parsed.data.userId === session.user.id) {
    redirectWithError("/admin/staff", "Manage your own role assignments directly in the database, not from the UI.");
  }

  if (!assignableStaffRoles(session.role).includes(parsed.data.role)) {
    redirectWithError("/admin/staff", "You are not allowed to assign that role.");
  }

  const { roles: existingRoles, error: rolesError } = await loadRoleAssignmentsForUser(supabase, parsed.data.userId);

  if (rolesError) {
    redirectWithError("/admin/staff", rolesError.message);
  }

  if (session.role !== "owner" && existingRoles.includes("owner")) {
    redirectWithError("/admin/staff", "Only an owner can modify another owner.");
  }

  if (session.role !== "owner" && parsed.data.role === "owner") {
    redirectWithError("/admin/staff", "Only an owner can assign the owner role.");
  }

  if (existingRoles.includes("owner") && parsed.data.role !== "owner") {
    const { count: ownerCount, error: ownerCountError } = await supabase
      .from("staff_role_assignments")
      .select("id", { count: "exact", head: true })
      .eq("role", "owner");

    if (ownerCountError) {
      redirectWithError("/admin/staff", ownerCountError.message);
    }

    if ((ownerCount ?? 0) <= 1) {
      redirectWithError("/admin/staff", "At least one owner must remain assigned.");
    }
  }

  const { error: clearRolesError } = await supabase.from("staff_role_assignments").delete().eq("user_id", parsed.data.userId);

  if (clearRolesError) {
    redirectWithError("/admin/staff", clearRolesError.message);
  }

  const { error } = await supabase.from("staff_role_assignments").insert({
    user_id: parsed.data.userId,
    role: parsed.data.role,
  });

  if (error) {
    redirectWithError("/admin/staff", error.message);
  }

  await logAuditEvent(supabase, {
    actorUserId: session.user.id,
    entityType: "staff_user",
    entityId: parsed.data.userId,
    action: "role_updated",
    metadata: {
      role: parsed.data.role,
    },
  });

  revalidatePath("/admin/staff");
}

export async function revokeStaffAccessAction(formData: FormData) {
  const session = await requireStaffSession();

  if (!canManageStaff(session.role)) {
    redirectWithError("/admin/staff", "You do not have permission to manage staff.");
  }

  const supabase = createAdminSupabaseClient();

  if (!supabase) {
    redirectWithError("/admin/staff", "Server database credentials are not configured.");
  }

  const parsed = staffAccessRevokeSchema.safeParse({
    userId: formData.get("userId"),
  });

  if (!parsed.success) {
    redirectWithError("/admin/staff", "Select a valid staff user.");
  }

  if (parsed.data.userId === session.user.id) {
    redirectWithError("/admin/staff", "You cannot revoke your own staff access from the admin UI.");
  }

  const { roles: existingRoles, error: rolesError } = await loadRoleAssignmentsForUser(supabase, parsed.data.userId);

  if (rolesError) {
    redirectWithError("/admin/staff", rolesError.message);
  }

  if (!existingRoles.length) {
    redirectWithError("/admin/staff", "That user does not have any staff access to revoke.");
  }

  if (session.role !== "owner" && existingRoles.includes("owner")) {
    redirectWithError("/admin/staff", "Only an owner can revoke another owner.");
  }

  if (existingRoles.includes("owner")) {
    const { count: ownerCount, error: ownerCountError } = await supabase
      .from("staff_role_assignments")
      .select("id", { count: "exact", head: true })
      .eq("role", "owner");

    if (ownerCountError) {
      redirectWithError("/admin/staff", ownerCountError.message);
    }

    if ((ownerCount ?? 0) <= 1) {
      redirectWithError("/admin/staff", "At least one owner must remain assigned.");
    }
  }

  const { error } = await supabase.from("staff_role_assignments").delete().eq("user_id", parsed.data.userId);

  if (error) {
    redirectWithError("/admin/staff", error.message);
  }

  await logAuditEvent(supabase, {
    actorUserId: session.user.id,
    entityType: "staff_user",
    entityId: parsed.data.userId,
    action: "access_revoked",
  });

  revalidatePath("/admin/staff");
}

export async function updateEventSettingsAction(formData: FormData) {
  const session = await requireStaffSession();

  if (!canManageSettings(session.role)) {
    redirectWithError("/admin/events", "You do not have permission to manage event settings.");
  }

  const supabase = await createServerSupabaseClient();

  if (!supabase) {
    redirectWithError("/admin/events", "Supabase is not configured.");
  }

  const client = supabase;

  const eventId = String(formData.get("eventId") ?? "").trim();
  const eventSlug = String(formData.get("eventSlug") ?? "").trim();
  const name = String(formData.get("name") ?? "").trim();
  const startsOn = String(formData.get("startsOn") ?? "").trim();
  const endsOn = String(formData.get("endsOn") ?? "").trim();
  const providerEventSlug = String(formData.get("providerEventSlug") ?? "").trim();
  const providerVisibilityMode = String(formData.get("providerVisibilityMode") ?? "").trim();
  const supportEmail = String(formData.get("supportEmail") ?? "").trim();
  const supportPhone = String(formData.get("supportPhone") ?? "").trim();
  const heroEyebrow = String(formData.get("heroEyebrow") ?? "").trim();
  const heroTitle = String(formData.get("heroTitle") ?? "").trim();
  const heroDescription = String(formData.get("heroDescription") ?? "").trim();
  const registrationStatus = String(formData.get("registrationStatus") ?? "").trim();
  const isActive = formData.get("isActive") === "on";

  if (!eventId || !eventSlug || !name) {
    redirectWithError(`/admin/events/${eventSlug || eventId}/settings`, "Event id, slug, and name are required.");
  }

  const { error: eventError } = await client
    .from("events")
    .update({
      slug: eventSlug,
      name,
      starts_on: startsOn || null,
      ends_on: endsOn || null,
      provider_event_slug: providerEventSlug || null,
      provider_visibility_mode: providerVisibilityMode || null,
      is_active: isActive,
    })
    .eq("id", eventId);

  if (eventError) {
    redirectWithError(`/admin/events/${eventSlug}/settings`, eventError.message);
  }

  const { error: settingsError } = await client.from("event_settings").upsert(
    {
      event_id: eventId,
      support_email: supportEmail || null,
      support_phone: supportPhone || null,
      hero_eyebrow: heroEyebrow || null,
      hero_title: heroTitle || null,
      hero_description: heroDescription || null,
      registration_status: registrationStatus || null,
    },
    { onConflict: "event_id" },
  );

  if (settingsError) {
    redirectWithError(`/admin/events/${eventSlug}/settings`, settingsError.message);
  }

  await logAuditEvent(client, {
    actorUserId: session.user.id,
    entityType: "event",
    entityId: eventId,
    action: "settings_updated",
    metadata: {
      slug: eventSlug,
      isActive,
      providerEventSlug: providerEventSlug || null,
      providerVisibilityMode: providerVisibilityMode || null,
      registrationStatus: registrationStatus || null,
    },
  });

  revalidatePath("/");
  revalidatePath("/register");
  revalidatePath("/contact");
  revalidatePath("/admin");
  revalidatePath("/admin/events");
  revalidatePath(`/admin/events/${eventSlug}/settings`);
}

export async function createTeamAction(formData: FormData) {
  const session = await requireStaffSession();

  if (!canManageComms(session.role)) {
    redirectWithError("/admin/teams", "You do not have permission to create teams.");
  }

  const supabase = await createServerSupabaseClient();

  if (!supabase) {
    redirectWithError("/admin/teams", "Supabase is not configured.");
  }

  const parsed = teamCreateSchema.safeParse({
    eventSlug: formData.get("eventSlug"),
    name: formData.get("name"),
    divisionName: formData.get("divisionName"),
    className: formData.get("className"),
    province: formData.get("province"),
    primaryContactId: formData.get("primaryContactId"),
    roleId: formData.get("roleId"),
  });

  if (!parsed.success) {
    redirectWithError("/admin/teams", "Enter a valid event, team name, and team details.");
  }

  const event = await resolveEventRecord(supabase, parsed.data.eventSlug);

  if (!event?.id) {
    redirectWithError("/admin/teams", "Event record could not be found.");
  }

  const { data: insertedTeam, error } = await supabase
    .from("teams")
    .insert({
      event_id: event.id,
      name: parsed.data.name.trim(),
      division_name: parsed.data.divisionName?.trim() || null,
      class_name: parsed.data.className?.trim() || null,
      province: parsed.data.province?.trim() || null,
    })
    .select("id")
    .single();

  if (error) {
    redirectWithError("/admin/teams", error.message);
  }

  if (parsed.data.primaryContactId) {
    const { error: teamContactError } = await supabase.from("team_contacts").insert({
      team_id: insertedTeam.id,
      contact_id: parsed.data.primaryContactId,
      role_id: parsed.data.roleId || null,
    });

    if (teamContactError) {
      redirectWithError("/admin/teams", teamContactError.message);
    }
  }

  await logAuditEvent(supabase, {
    actorUserId: session.user.id,
    entityType: "team",
    entityId: insertedTeam.id,
    action: "created",
    metadata: {
      name: parsed.data.name.trim(),
      primaryContactId: parsed.data.primaryContactId || null,
    },
  });

  revalidatePath("/admin");
  revalidatePath("/admin/teams");
  revalidatePath("/admin/contacts");
  revalidatePath("/admin/registrations");
  revalidatePath("/teams");
}

export async function updateTeamAction(formData: FormData) {
  const session = await requireStaffSession();

  if (!canManageComms(session.role)) {
    redirectWithError("/admin/teams", "You do not have permission to update teams.");
  }

  const supabase = await createServerSupabaseClient();

  if (!supabase) {
    redirectWithError("/admin/teams", "Supabase is not configured.");
  }

  const client = supabase;
  const teamId = String(formData.get("teamId") ?? "").trim();
  const name = String(formData.get("name") ?? "").trim();
  const divisionName = String(formData.get("divisionName") ?? "").trim();
  const className = String(formData.get("className") ?? "").trim();
  const province = String(formData.get("province") ?? "").trim();

  if (!teamId || !name) {
    redirectWithError("/admin/teams", "Team id and name are required.");
  }

  const { error } = await client
    .from("teams")
    .update({
      name,
      division_name: divisionName || null,
      class_name: className || null,
      province: province || null,
    })
    .eq("id", teamId);

  if (error) {
    redirectWithError("/admin/teams", error.message);
  }

  await logAuditEvent(client, {
    actorUserId: session.user.id,
    entityType: "team",
    entityId: teamId,
    action: "updated",
    metadata: {
      name,
      divisionName: divisionName || null,
      className: className || null,
      province: province || null,
    },
  });

  revalidatePath("/admin/teams");
  revalidatePath("/teams");
}

export async function deleteTeamAction(formData: FormData) {
  const session = await requireStaffSession();

  if (!canDeleteRecords(session.role)) {
    redirectWithError("/admin/teams", "You do not have permission to delete teams.");
  }

  const supabase = await createServerSupabaseClient();

  if (!supabase) {
    redirectWithError("/admin/teams", "Supabase is not configured.");
  }

  const teamId = String(formData.get("teamId") ?? "").trim();

  if (!teamId) {
    redirectWithError("/admin/teams", "Team id is required.");
  }

  const { error } = await supabase.from("teams").delete().eq("id", teamId);

  if (error) {
    redirectWithError("/admin/teams", error.message);
  }

  await logAuditEvent(supabase, {
    actorUserId: session.user.id,
    entityType: "team",
    entityId: teamId,
    action: "deleted",
  });

  revalidatePath("/admin/teams");
  revalidatePath("/admin/contacts");
  revalidatePath("/admin/registrations");
  revalidatePath("/teams");
}

export async function createContactAction(formData: FormData) {
  const session = await requireStaffSession();

  if (!canManageComms(session.role)) {
    redirectWithError("/admin/contacts", "You do not have permission to create contacts.");
  }

  const supabase = await createServerSupabaseClient();

  if (!supabase) {
    redirectWithError("/admin/contacts", "Supabase is not configured.");
  }

  const parsed = contactCreateSchema.safeParse({
    fullName: formData.get("fullName"),
    email: formData.get("email"),
    phone: formData.get("phone"),
    notes: formData.get("notes"),
    teamId: formData.get("teamId"),
    roleId: formData.get("roleId"),
  });

  if (!parsed.success) {
    redirectWithError("/admin/contacts", "Enter a valid contact name and details.");
  }

  const parts = parsed.data.fullName.trim().split(/\s+/).filter(Boolean);

  const { data: insertedContact, error } = await supabase
    .from("contacts")
    .insert({
      first_name: parts[0] ?? null,
      last_name: parts.slice(1).join(" ") || null,
      full_name: parsed.data.fullName.trim(),
      email: parsed.data.email?.trim() || null,
      phone: parsed.data.phone?.trim() || null,
      notes: parsed.data.notes?.trim() || null,
    })
    .select("id")
    .single();

  if (error) {
    redirectWithError("/admin/contacts", error.message);
  }

  if (parsed.data.teamId) {
    const { error: linkError } = await supabase.from("team_contacts").insert({
      team_id: parsed.data.teamId,
      contact_id: insertedContact.id,
      role_id: parsed.data.roleId || null,
    });

    if (linkError) {
      redirectWithError("/admin/contacts", linkError.message);
    }
  }

  await logAuditEvent(supabase, {
    actorUserId: session.user.id,
    entityType: "contact",
    entityId: insertedContact.id,
    action: "created",
    metadata: {
      fullName: parsed.data.fullName.trim(),
      linkedTeamId: parsed.data.teamId || null,
    },
  });

  revalidatePath("/admin");
  revalidatePath("/admin/contacts");
  revalidatePath("/admin/teams");
  revalidatePath("/admin/registrations");
}

export async function updateContactAction(formData: FormData) {
  const session = await requireStaffSession();

  if (!canManageComms(session.role)) {
    redirectWithError("/admin/contacts", "You do not have permission to update contacts.");
  }

  const supabase = await createServerSupabaseClient();

  if (!supabase) {
    redirectWithError("/admin/contacts", "Supabase is not configured.");
  }

  const client = supabase;
  const contactId = String(formData.get("contactId") ?? "").trim();
  const fullName = String(formData.get("fullName") ?? "").trim();
  const email = String(formData.get("email") ?? "").trim();
  const phone = String(formData.get("phone") ?? "").trim();

  if (!contactId || !fullName) {
    redirectWithError("/admin/contacts", "Contact id and name are required.");
  }

  const parts = fullName.split(/\s+/).filter(Boolean);

  const { error } = await client
    .from("contacts")
    .update({
      full_name: fullName,
      first_name: parts[0] ?? null,
      last_name: parts.slice(1).join(" ") || null,
      email: email || null,
      phone: phone || null,
    })
    .eq("id", contactId);

  if (error) {
    redirectWithError("/admin/contacts", error.message);
  }

  await logAuditEvent(client, {
    actorUserId: session.user.id,
    entityType: "contact",
    entityId: contactId,
    action: "updated",
    metadata: {
      fullName,
      email: email || null,
      phone: phone || null,
    },
  });

  revalidatePath("/admin/contacts");
}

export async function deleteContactAction(formData: FormData) {
  const session = await requireStaffSession();

  if (!canDeleteRecords(session.role)) {
    redirectWithError("/admin/contacts", "You do not have permission to delete contacts.");
  }

  const supabase = await createServerSupabaseClient();

  if (!supabase) {
    redirectWithError("/admin/contacts", "Supabase is not configured.");
  }

  const contactId = String(formData.get("contactId") ?? "").trim();

  if (!contactId) {
    redirectWithError("/admin/contacts", "Contact id is required.");
  }

  const { error } = await supabase.from("contacts").delete().eq("id", contactId);

  if (error) {
    redirectWithError("/admin/contacts", error.message);
  }

  await logAuditEvent(supabase, {
    actorUserId: session.user.id,
    entityType: "contact",
    entityId: contactId,
    action: "deleted",
  });

  revalidatePath("/admin/contacts");
  revalidatePath("/admin/teams");
}

export async function upsertEmailTemplateAction(formData: FormData) {
  const session = await requireStaffSession();

  if (!canManageComms(session.role)) {
    redirectWithError("/admin/email/templates", "You do not have permission to manage email templates.");
  }

  const supabase = await createServerSupabaseClient();

  if (!supabase) {
    redirectWithError("/admin/email/templates", "Supabase is not configured.");
  }

  const client = supabase;
  const templateId = String(formData.get("templateId") ?? "").trim();
  const eventSlug = String(formData.get("eventSlug") ?? appConfig.defaultEventSlug).trim();
  const slug = String(formData.get("slug") ?? "").trim();
  const subject = String(formData.get("subject") ?? "").trim();
  const htmlBody = String(formData.get("htmlBody") ?? "");
  const textBody = String(formData.get("textBody") ?? "");
  const isActive = formData.get("isActive") === "on";

  if (!slug || !subject) {
    redirectWithError("/admin/email/templates", "Template slug and subject are required.");
  }

  const { data: event } = await client.from("events").select("id").eq("slug", eventSlug).maybeSingle();

  if (!event?.id) {
    redirectWithError("/admin/email/templates", "Event record could not be found.");
  }

  if (templateId) {
    const { error } = await client
      .from("email_templates")
      .update({
        slug,
        subject,
        html_body: htmlBody || null,
        text_body: textBody || null,
        is_active: isActive,
      })
      .eq("id", templateId);

    if (error) {
      redirectWithError("/admin/email/templates", error.message);
    }

    await logAuditEvent(client, {
      actorUserId: session.user.id,
      entityType: "email_template",
      entityId: templateId,
      action: "updated",
      metadata: {
        slug,
        isActive,
      },
    });
  } else {
    const { data: insertedTemplate, error } = await client
      .from("email_templates")
      .insert({
        event_id: event.id,
        slug,
        subject,
        html_body: htmlBody || null,
        text_body: textBody || null,
        is_active: isActive,
      })
      .select("id")
      .single();

    if (error) {
      redirectWithError("/admin/email/templates", error.message);
    }

    await logAuditEvent(client, {
      actorUserId: session.user.id,
      entityType: "email_template",
      entityId: insertedTemplate.id,
      action: "created",
      metadata: {
        slug,
        isActive,
      },
    });
  }

  revalidatePath("/admin/email/templates");
}

export async function deleteEmailTemplateAction(formData: FormData) {
  const session = await requireStaffSession();

  if (!canDeleteRecords(session.role)) {
    redirectWithError("/admin/email/templates", "You do not have permission to delete templates.");
  }

  const supabase = await createServerSupabaseClient();

  if (!supabase) {
    redirectWithError("/admin/email/templates", "Supabase is not configured.");
  }

  const templateId = String(formData.get("templateId") ?? "").trim();

  if (!templateId) {
    redirectWithError("/admin/email/templates", "Template id is required.");
  }

  const { count: templateUsageCount, error: templateUsageError } = await supabase
    .from("email_campaigns")
    .select("id", { count: "exact", head: true })
    .eq("template_id", templateId)
    .neq("status", "draft");

  if (templateUsageError) {
    redirectWithError("/admin/email/templates", templateUsageError.message);
  }

  if ((templateUsageCount ?? 0) > 0) {
    redirectWithError("/admin/email/templates", "Templates used by sent or failed campaigns cannot be deleted.");
  }

  const { error } = await supabase.from("email_templates").delete().eq("id", templateId);

  if (error) {
    redirectWithError("/admin/email/templates", error.message);
  }

  await logAuditEvent(supabase, {
    actorUserId: session.user.id,
    entityType: "email_template",
    entityId: templateId,
    action: "deleted",
  });

  revalidatePath("/admin/email/templates");
}

export async function createEmailCampaignAction(formData: FormData) {
  const session = await requireStaffSession();

  if (!canManageComms(session.role)) {
    redirectWithError("/admin/email/campaigns", "You do not have permission to manage campaigns.");
  }

  const supabase = await createServerSupabaseClient();

  if (!supabase) {
    redirectWithError("/admin/email/campaigns", "Supabase is not configured.");
  }

  const client = supabase;
  const eventSlug = String(formData.get("eventSlug") ?? appConfig.defaultEventSlug).trim();
  const templateId = String(formData.get("templateId") ?? "").trim();
  const subject = String(formData.get("subject") ?? "").trim();
  const contentHtml = String(formData.get("contentHtml") ?? "");
  const contentText = String(formData.get("contentText") ?? "");

  if (!subject) {
    redirectWithError("/admin/email/campaigns", "Campaign subject is required.");
  }

  const { data: event } = await client.from("events").select("id").eq("slug", eventSlug).maybeSingle();

  if (!event?.id) {
    redirectWithError("/admin/email/campaigns", "Event record could not be found.");
  }

  let resolvedHtml = contentHtml || null;
  let resolvedText = contentText || null;

  if (templateId && (!resolvedHtml || !resolvedText)) {
    const { data: template } = await client
      .from("email_templates")
      .select("html_body, text_body")
      .eq("id", templateId)
      .maybeSingle();

    resolvedHtml = resolvedHtml || template?.html_body || null;
    resolvedText = resolvedText || template?.text_body || null;
  }

  const { data: insertedCampaign, error } = await client
    .from("email_campaigns")
    .insert({
      event_id: event.id,
      template_id: templateId || null,
      created_by: session.user.id,
      subject,
      content_html: resolvedHtml,
      content_text: resolvedText,
      status: "draft",
    })
    .select("id")
    .single();

  if (error) {
    redirectWithError("/admin/email/campaigns", error.message);
  }

  await logAuditEvent(client, {
    actorUserId: session.user.id,
    entityType: "email_campaign",
    entityId: insertedCampaign.id,
    action: "created",
    metadata: {
      templateId: templateId || null,
      hasHtml: Boolean(resolvedHtml),
      hasText: Boolean(resolvedText),
    },
  });

  revalidatePath("/admin/email/campaigns");
}

export async function deleteEmailCampaignAction(formData: FormData) {
  const session = await requireStaffSession();

  if (!canDeleteRecords(session.role)) {
    redirectWithError("/admin/email/campaigns", "You do not have permission to delete campaigns.");
  }

  const supabase = await createServerSupabaseClient();

  if (!supabase) {
    redirectWithError("/admin/email/campaigns", "Supabase is not configured.");
  }

  const campaignId = String(formData.get("campaignId") ?? "").trim();

  if (!campaignId) {
    redirectWithError("/admin/email/campaigns", "Campaign id is required.");
  }

  const { data: campaign, error: lookupError } = await supabase
    .from("email_campaigns")
    .select("id, status")
    .eq("id", campaignId)
    .maybeSingle();

  if (lookupError) {
    redirectWithError("/admin/email/campaigns", lookupError.message);
  }

  if (!campaign) {
    redirectWithError("/admin/email/campaigns", "Campaign not found.");
  }

  if (campaign.status !== "draft") {
    redirectWithError("/admin/email/campaigns", "Only draft campaigns can be deleted.");
  }

  const { error } = await supabase.from("email_campaigns").delete().eq("id", campaignId);

  if (error) {
    redirectWithError("/admin/email/campaigns", error.message);
  }

  await logAuditEvent(supabase, {
    actorUserId: session.user.id,
    entityType: "email_campaign",
    entityId: campaignId,
    action: "deleted",
  });

  revalidatePath("/admin/email/campaigns");
}

export async function sendEmailCampaignAction(formData: FormData) {
  const session = await requireStaffSession();

  if (!canManageComms(session.role)) {
    redirectWithError("/admin/email/campaigns", "You do not have permission to send campaigns.");
  }

  const supabase = createAdminSupabaseClient();
  const resend = createResendClient();

  if (!supabase) {
    redirectWithError("/admin/email/campaigns", "Server database credentials are not configured.");
  }

  if (!resend) {
    redirectWithError("/admin/email/campaigns", "RESEND_API_KEY is not configured.");
  }

  const campaignId = String(formData.get("campaignId") ?? "").trim();
  const audienceScope = normalizeAudienceScope(formData.get("audienceScope"));

  if (!campaignId) {
    redirectWithError("/admin/email/campaigns", "Campaign id is required.");
  }

  if (!audienceScope) {
    redirectWithError("/admin/email/campaigns", "Campaign audience is invalid.");
  }

  const { data: campaign, error: campaignError } = await supabase
    .from("email_campaigns")
    .select("id, event_id, subject, content_html, content_text, status")
    .eq("id", campaignId)
    .maybeSingle();

  if (campaignError) {
    redirectWithError("/admin/email/campaigns", campaignError.message);
  }

  if (!campaign) {
    redirectWithError("/admin/email/campaigns", "Campaign not found.");
  }

  if (campaign.status !== "draft") {
    redirectWithError("/admin/email/campaigns", "Only draft campaigns can be sent.");
  }

  if (!campaign.event_id) {
    redirectWithError("/admin/email/campaigns", "Campaign is not linked to an event.");
  }

  let audience: CampaignRecipient[] = [];

  try {
    audience = await loadCampaignAudience({
      supabase,
      eventId: campaign.event_id,
      audienceScope,
    });
  } catch (error) {
    redirectWithError(
      "/admin/email/campaigns",
      error instanceof Error ? error.message : "Could not resolve campaign audience.",
    );
  }

  if (!audience.length) {
    redirectWithError("/admin/email/campaigns", "No event contacts with email addresses were found.");
  }

  const { html, text } = await buildCampaignEmailContent({
    subject: campaign.subject,
    htmlBody: campaign.content_html,
    textBody: campaign.content_text,
  });

  const { data: claimedCampaign, error: claimError } = await supabase
    .from("email_campaigns")
    .update({ status: "scheduled" })
    .eq("id", campaign.id)
    .eq("status", "draft")
    .select("id")
    .maybeSingle();

  if (claimError) {
    redirectWithError("/admin/email/campaigns", claimError.message);
  }

  if (!claimedCampaign) {
    redirectWithError("/admin/email/campaigns", "Campaign was already sent or is being sent.");
  }

  await logAuditEvent(supabase, {
    actorUserId: session.user.id,
    entityType: "email_campaign",
    entityId: campaign.id,
    action: "send_requested",
    metadata: {
      recipientCount: audience.length,
      audienceScope,
    },
  });

  const chunkSize = 100;
  let queuedCount = 0;

  for (let index = 0; index < audience.length; index += chunkSize) {
    const chunk = audience.slice(index, index + chunkSize);
    const batchPayload = chunk.map((recipient) => ({
      from: appConfig.resendFrom,
      to: [recipient.email],
      subject: campaign.subject,
      html,
      text,
      tags: [
        { name: "campaign_id", value: campaign.id },
        { name: "contact_id", value: recipient.contactId ?? "unknown" },
      ],
    }));

    const { data, error } = await resend.batch.send(batchPayload);

    if (error || !data) {
      await supabase.from("email_campaigns").update({ status: "failed" }).eq("id", campaign.id);
      await logAuditEvent(supabase, {
        actorUserId: session.user.id,
        entityType: "email_campaign",
        entityId: campaign.id,
        action: "send_failed",
        metadata: {
          error: error?.message ?? "Batch send failed",
        },
      });
      redirectWithError("/admin/email/campaigns", error?.message ?? "Campaign send failed.");
    }

    const batchResults = (data.data ?? []) as Array<{ id?: string | null }>;

    const deliveryRows = chunk.map((recipient, chunkIndex) => ({
      campaign_id: campaign.id,
      recipient_email: recipient.email,
      recipient_name: recipient.name,
      provider_message_id: batchResults[chunkIndex]?.id ?? null,
      delivery_status: "queued",
      error_text: null,
    }));

    const { error: deliveryInsertError } = await supabase.from("email_deliveries").insert(deliveryRows);

    if (deliveryInsertError) {
      await supabase.from("email_campaigns").update({ status: "failed" }).eq("id", campaign.id);
      await logAuditEvent(supabase, {
        actorUserId: session.user.id,
        entityType: "email_campaign",
        entityId: campaign.id,
        action: "delivery_tracking_failed",
        metadata: {
          error: deliveryInsertError.message,
          acceptedRecipientCount: queuedCount + deliveryRows.length,
        },
      });
      redirectWithError("/admin/email/campaigns", deliveryInsertError.message);
    }

    queuedCount += deliveryRows.length;
  }

  const { error: campaignUpdateError } = await supabase
    .from("email_campaigns")
    .update({
      status: "sent",
      sent_at: new Date().toISOString(),
    })
    .eq("id", campaign.id);

  if (campaignUpdateError) {
    await logAuditEvent(supabase, {
      actorUserId: session.user.id,
      entityType: "email_campaign",
      entityId: campaign.id,
      action: "sent_status_update_failed",
      metadata: {
        error: campaignUpdateError.message,
        recipientCount: queuedCount,
      },
    });
    redirectWithError("/admin/email/campaigns", campaignUpdateError.message);
  }

  await logAuditEvent(supabase, {
    actorUserId: session.user.id,
    entityType: "email_campaign",
    entityId: campaign.id,
    action: "sent",
    metadata: {
      recipientCount: queuedCount,
      audienceScope,
    },
  });

  revalidatePath("/admin/email/campaigns");
}

export async function retryEmailDeliveryAction(formData: FormData) {
  const session = await requireStaffSession();

  if (!canManageComms(session.role)) {
    redirectWithError("/admin/email/campaigns", "You do not have permission to retry deliveries.");
  }

  const supabase = createAdminSupabaseClient();
  const resend = createResendClient();

  if (!supabase) {
    redirectWithError("/admin/email/campaigns", "Server database credentials are not configured.");
  }

  if (!resend) {
    redirectWithError("/admin/email/campaigns", "RESEND_API_KEY is not configured.");
  }

  const deliveryId = String(formData.get("deliveryId") ?? "").trim();

  if (!deliveryId) {
    redirectWithError("/admin/email/campaigns", "Delivery id is required.");
  }

  const { data: delivery, error } = await supabase
    .from("email_deliveries")
    .select("id, campaign_id, recipient_email, recipient_name, delivery_status, email_campaigns!inner(id, subject, content_html, content_text)")
    .eq("id", deliveryId)
    .maybeSingle();

  if (error || !delivery) {
    redirectWithError("/admin/email/campaigns", error?.message ?? "Delivery record could not be found.");
  }

  const campaign = Array.isArray(delivery.email_campaigns) ? delivery.email_campaigns[0] : delivery.email_campaigns;

  if (!campaign?.id) {
    redirectWithError("/admin/email/campaigns", "Campaign data for this delivery could not be found.");
  }

  if (delivery.delivery_status === "delivered") {
    redirectWithError("/admin/email/campaigns", "Delivered emails cannot be retried.");
  }

  const { html, text } = await buildCampaignEmailContent({
    subject: campaign.subject,
    htmlBody: campaign.content_html,
    textBody: campaign.content_text,
  });

  const { data: sendData, error: sendError } = await resend.batch.send([
    {
      from: appConfig.resendFrom,
      to: [delivery.recipient_email],
      subject: campaign.subject,
      html,
      text,
      tags: [
        { name: "campaign_id", value: campaign.id },
        { name: "retry_delivery_id", value: delivery.id },
      ],
    },
  ]);

  if (sendError || !sendData) {
    await logAuditEvent(supabase, {
      actorUserId: session.user.id,
      entityType: "email_delivery",
      entityId: delivery.id,
      action: "retry_failed",
      metadata: {
        error: sendError?.message ?? "Retry send failed",
      },
    });

    redirectWithError("/admin/email/campaigns", sendError?.message ?? "Delivery retry failed.");
  }

  const providerMessageId = sendData.data?.[0]?.id ?? null;

  const { error: updateError } = await supabase
    .from("email_deliveries")
    .update({
      provider_message_id: providerMessageId,
      delivery_status: "queued",
      error_text: null,
    })
    .eq("id", delivery.id);

  if (updateError) {
    redirectWithError("/admin/email/campaigns", updateError.message);
  }

  await logAuditEvent(supabase, {
    actorUserId: session.user.id,
    entityType: "email_delivery",
    entityId: delivery.id,
    action: "retry_requested",
    metadata: {
      campaignId: campaign.id,
      recipientEmail: delivery.recipient_email,
    },
  });

  revalidatePath("/admin/email/campaigns");
}
