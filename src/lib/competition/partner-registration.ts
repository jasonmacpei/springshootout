import { createHash } from "node:crypto";

import { z } from "zod";

import { appConfig } from "@/lib/config";

const partnerContactSchema = z.object({
  contactId: z.number(),
  contactPublicId: z.string(),
  fullName: z.string(),
  email: z.string().nullable().optional(),
  roleSlug: z.string(),
  isPrimary: z.boolean(),
  created: z.boolean(),
  linkCreated: z.boolean(),
});

const partnerRegistrationResponseSchema = z.object({
  data: z.object({
    event: z.object({
      eventId: z.number(),
      eventPublicId: z.string(),
      eventSlug: z.string(),
      eventName: z.string(),
    }),
    division: z.object({
      divisionId: z.number().nullable().optional(),
      divisionName: z.string(),
    }),
    team: z.object({
      teamId: z.number(),
      teamPublicId: z.string(),
      teamName: z.string(),
      created: z.boolean(),
      assignmentCreated: z.boolean(),
      externalTeamId: z.string().nullable().optional(),
    }),
    registration: z.object({
      registrationId: z.number(),
      registrationPublicId: z.string(),
      status: z.string(),
      created: z.boolean(),
      externalRegistrationId: z.string(),
      source: z.string(),
    }),
    contacts: z.array(partnerContactSchema).default([]),
  }),
});

const partnerErrorSchema = z.object({
  error: z.string(),
  code: z.string().optional(),
});

type PartnerRegistrationPayload = {
  event: string;
  externalRegistrationId: string;
  source: string;
  team: {
    name: string;
    divisionName: string;
    className?: string;
    province?: string;
    externalTeamId: string;
  };
  primaryContact: {
    fullName: string;
    email: string;
    phone?: string;
    roleSlug: string;
    externalContactId: string;
  };
  additionalContacts?: Array<{
    fullName: string;
    email: string;
    phone?: string;
    roleSlug: string;
    externalContactId?: string;
  }>;
  registration: {
    status: "pending" | "approved" | "waitlisted" | "withdrawn";
    note?: string;
  };
};

export type PartnerRegistrationResponse = z.infer<typeof partnerRegistrationResponseSchema>["data"];

function createStableExternalId(prefix: string, values: string[]) {
  const hash = createHash("sha256")
    .update(values.map((value) => value.trim().toLowerCase()).join("|"))
    .digest("hex")
    .slice(0, 24);

  return `${prefix}-${hash}`;
}

export function buildPartnerRegistrationPayload(input: {
  event: string;
  teamName: string;
  divisionName: string;
  className?: string;
  province?: string;
  contactName: string;
  contactEmail: string;
  contactPhone?: string;
  contactRole: string;
  note?: string;
}): PartnerRegistrationPayload {
  const source = appConfig.partnerRegistrationSource;

  return {
    event: input.event,
    externalRegistrationId: createStableExternalId("ss-reg", [source, input.event, input.teamName, input.contactEmail]),
    source,
    team: {
      name: input.teamName,
      divisionName: input.divisionName,
      className: input.className,
      province: input.province,
      externalTeamId: createStableExternalId("ss-team", [source, input.event, input.teamName]),
    },
    primaryContact: {
      fullName: input.contactName,
      email: input.contactEmail,
      phone: input.contactPhone,
      roleSlug: input.contactRole,
      externalContactId: createStableExternalId("ss-contact", [source, input.event, input.contactEmail]),
    },
    registration: {
      status: "pending",
      note: input.note,
    },
  };
}

export async function submitPartnerRegistration(payload: PartnerRegistrationPayload): Promise<PartnerRegistrationResponse> {
  if (!appConfig.partnerKey) {
    throw new Error("HOOPS_SCOREBOOK_PARTNER_KEY is not configured.");
  }

  const response = await fetch(new URL("/api/v1/partner/registrations", appConfig.hoopsApiBase), {
    method: "POST",
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
      Authorization: `Bearer ${appConfig.partnerKey}`,
    },
    body: JSON.stringify(payload),
    cache: "no-store",
  });

  const json = await response.json().catch(() => null);

  if (!response.ok) {
    const parsedError = partnerErrorSchema.safeParse(json);
    throw new Error(parsedError.success ? parsedError.data.error : "Partner registration request failed.");
  }

  const parsed = partnerRegistrationResponseSchema.safeParse(json);

  if (!parsed.success) {
    throw new Error("Partner registration response was invalid.");
  }

  return parsed.data.data;
}
