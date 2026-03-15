"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";

import { buildPartnerRegistrationPayload, submitPartnerRegistration } from "@/lib/competition/partner-registration";
import { createAdminSupabaseClient } from "@/lib/db/admin";
import { additionalContactSchema, registrationSchema } from "@/lib/validation/forms";

export type PublicActionState = {
  success: boolean;
  message: string;
};

function splitName(fullName: string) {
  const parts = fullName.trim().split(/\s+/).filter(Boolean);

  return {
    firstName: parts[0] ?? null,
    lastName: parts.slice(1).join(" ") || null,
  };
}

async function mirrorTeamContactRegistrationLocally({
  supabase,
  eventId,
  teamName,
  divisionName,
  className,
  province,
  contactName,
  contactEmail,
  contactPhone,
  contactRole,
  note,
}: {
  supabase: NonNullable<ReturnType<typeof createAdminSupabaseClient>>;
  eventId: string;
  teamName: string;
  divisionName: string;
  className?: string;
  province?: string;
  contactName: string;
  contactEmail: string;
  contactPhone?: string;
  contactRole: string;
  note?: string;
}) {
  const [{ data: roleRow }, { data: existingTeam }, { data: existingContact }] = await Promise.all([
    supabase.from("contact_roles").select("id").eq("slug", contactRole).maybeSingle(),
    supabase.from("teams").select("id, name").eq("event_id", eventId).ilike("name", teamName).maybeSingle(),
    supabase.from("contacts").select("id").eq("email", contactEmail).maybeSingle(),
  ]);

  let contactId = existingContact?.id ?? null;

  if (!contactId) {
    const nameParts = splitName(contactName);
    const { data: contact, error: contactError } = await supabase
      .from("contacts")
      .insert({
        first_name: nameParts.firstName,
        last_name: nameParts.lastName,
        full_name: contactName,
        email: contactEmail,
        phone: contactPhone,
      })
      .select("id")
      .single();

    if (contactError || !contact?.id) {
      throw new Error(contactError?.message ?? "Unable to mirror the primary contact locally.");
    }

    contactId = contact.id;
  } else {
    const nameParts = splitName(contactName);
    const { error: contactUpdateError } = await supabase
      .from("contacts")
      .update({
        first_name: nameParts.firstName,
        last_name: nameParts.lastName,
        full_name: contactName,
        email: contactEmail,
        phone: contactPhone ?? null,
      })
      .eq("id", contactId);

    if (contactUpdateError) {
      throw new Error(contactUpdateError.message);
    }
  }

  let teamId = existingTeam?.id ?? null;
  let teamLabel = existingTeam?.name ?? teamName;

  if (!teamId) {
    const { data: team, error: teamError } = await supabase
      .from("teams")
      .insert({
        event_id: eventId,
        name: teamName,
        division_name: divisionName,
        class_name: className,
        province,
      })
      .select("id, name")
      .single();

    if (teamError || !team?.id) {
      throw new Error(teamError?.message ?? "Unable to mirror the team locally.");
    }

    teamId = team.id;
    teamLabel = team.name;
  } else {
    const { error: teamUpdateError } = await supabase
      .from("teams")
      .update({
        division_name: divisionName,
        class_name: className,
        province,
      })
      .eq("id", teamId);

    if (teamUpdateError) {
      throw new Error(teamUpdateError.message);
    }
  }

  const { data: existingRegistration } = await supabase
    .from("registrations")
    .select("id")
    .eq("event_id", eventId)
    .eq("team_id", teamId)
    .maybeSingle();

  if (existingRegistration?.id) {
    const { error: registrationUpdateError } = await supabase
      .from("registrations")
      .update({
        primary_contact_id: contactId,
        division_name: divisionName,
        class_name: className,
        province,
        note: note ?? null,
        status: "pending",
      })
      .eq("id", existingRegistration.id);

    if (registrationUpdateError) {
      throw new Error(registrationUpdateError.message);
    }
  } else {
    const { error: registrationInsertError } = await supabase.from("registrations").insert({
      event_id: eventId,
      team_id: teamId,
      primary_contact_id: contactId,
      division_name: divisionName,
      class_name: className,
      province,
      note: note ?? null,
      status: "pending",
    });

    if (registrationInsertError) {
      throw new Error(registrationInsertError.message);
    }
  }

  const { error: teamContactError } = await supabase.from("team_contacts").upsert(
    {
      team_id: teamId,
      contact_id: contactId,
      role_id: roleRow?.id ?? null,
    },
    { onConflict: "team_id,contact_id" },
  );

  if (teamContactError) {
    throw new Error(teamContactError.message);
  }

  return {
    teamId,
    teamName: teamLabel,
  };
}

export async function submitRegistration(
  _previousState: PublicActionState,
  formData: FormData,
): Promise<PublicActionState> {
  const parsed = registrationSchema.safeParse({
    eventSlug: formData.get("eventSlug"),
    teamName: formData.get("teamName"),
    contactName: formData.get("contactName"),
    contactRole: formData.get("contactRole"),
    province: formData.get("province"),
    division: formData.get("division"),
    className: formData.get("className"),
    email: formData.get("email"),
    phone: formData.get("phone"),
    note: formData.get("note"),
  });

  if (!parsed.success) {
    return {
      success: false,
      message: "Complete all required fields before submitting.",
    };
  }

  const supabase = createAdminSupabaseClient();

  if (!supabase) {
    return {
      success: false,
      message: "Server database credentials are not configured yet. Add the service role key before using public forms.",
    };
  }

  const payload = parsed.data;
  const { data: event } = await supabase
    .from("events")
    .select("id, name, provider_event_slug")
    .eq("slug", payload.eventSlug)
    .maybeSingle();

  if (!event?.id) {
    return {
      success: false,
      message: "The active event could not be found.",
    };
  }

  try {
    await submitPartnerRegistration(
      buildPartnerRegistrationPayload({
        event: event.provider_event_slug ?? payload.eventSlug,
        teamName: payload.teamName,
        divisionName: payload.division,
        className: payload.className,
        province: payload.province,
        contactName: payload.contactName,
        contactEmail: payload.email,
        contactPhone: payload.phone,
        contactRole: payload.contactRole,
        note: payload.note,
      }),
    );
  } catch (error) {
    return {
      success: false,
      message: error instanceof Error ? error.message : "Unable to submit the registration to Hoops Scorebook.",
    };
  }

  let localTeamId = "";
  let localTeamName = payload.teamName;

  try {
    const mirrored = await mirrorTeamContactRegistrationLocally({
      supabase,
      eventId: event.id,
      teamName: payload.teamName,
      divisionName: payload.division,
      className: payload.className,
      province: payload.province,
      contactName: payload.contactName,
      contactEmail: payload.email,
      contactPhone: payload.phone,
      contactRole: payload.contactRole,
      note: payload.note,
    });

    localTeamId = mirrored.teamId;
    localTeamName = mirrored.teamName;
  } catch (error) {
    return {
      success: false,
      message:
        error instanceof Error
          ? `Registration reached Hoops Scorebook, but local sync failed: ${error.message}`
          : "Registration reached Hoops Scorebook, but local sync failed.",
    };
  }

  revalidatePath("/teams");
  revalidatePath("/admin");
  revalidatePath("/admin/teams");
  revalidatePath("/admin/contacts");
  revalidatePath("/admin/registrations");

  const params = new URLSearchParams({
    teamId: localTeamId,
    teamName: localTeamName,
    eventName: event.name,
  });

  redirect(`/register/success?${params.toString()}`);
}

export async function submitAdditionalContact(
  _previousState: PublicActionState,
  formData: FormData,
): Promise<PublicActionState> {
  const parsed = additionalContactSchema.safeParse({
    teamId: formData.get("teamId"),
    contactName: formData.get("contactName"),
    email: formData.get("email"),
    phone: formData.get("phone"),
    role: formData.get("role"),
  });

  if (!parsed.success) {
    return {
      success: false,
      message: "Choose a team and complete all contact fields before submitting.",
    };
  }

  const supabase = createAdminSupabaseClient();

  if (!supabase) {
    return {
      success: false,
      message: "Server database credentials are not configured yet. Add the service role key before using public forms.",
    };
  }

  const payload = parsed.data;

  const [{ data: team }, { data: roleRow }] = await Promise.all([
    supabase.from("teams").select("id, name").eq("id", payload.teamId).maybeSingle(),
    supabase.from("contact_roles").select("id").eq("slug", payload.role).maybeSingle(),
  ]);

  if (!team?.id) {
    return {
      success: false,
      message: "The selected team could not be found.",
    };
  }

  const nameParts = splitName(payload.contactName);

  const { data: contact, error: contactError } = await supabase
    .from("contacts")
    .insert({
      first_name: nameParts.firstName,
      last_name: nameParts.lastName,
      full_name: payload.contactName,
      email: payload.email,
      phone: payload.phone,
    })
    .select("id")
    .single();

  if (contactError || !contact?.id) {
    return {
      success: false,
      message: contactError?.message ?? "Unable to create the contact record.",
    };
  }

  const { error: linkError } = await supabase.from("team_contacts").insert({
    team_id: team.id,
    contact_id: contact.id,
    role_id: roleRow?.id ?? null,
  });

  if (linkError) {
    await supabase.from("contacts").delete().eq("id", contact.id);

    return {
      success: false,
      message: linkError.message,
    };
  }

  revalidatePath("/admin/contacts");
  revalidatePath("/admin/teams");

  return {
    success: true,
    message: `Added ${payload.contactName} to ${team.name}.`,
  };
}
