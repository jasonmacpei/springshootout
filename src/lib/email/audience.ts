export type CampaignRecipientContact = {
  id?: string | null;
  full_name?: string | null;
  email?: string | null;
};

export type CampaignRecipient = {
  contactId: string | null;
  name: string;
  email: string;
};

const basicEmailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

export function normalizeCampaignEmail(input: string | null | undefined) {
  const email = input?.trim().toLowerCase() ?? "";

  if (!email || !basicEmailPattern.test(email)) {
    return null;
  }

  return email;
}

export function addCampaignRecipient(
  recipients: Map<string, CampaignRecipient>,
  contact: CampaignRecipientContact | CampaignRecipientContact[] | null | undefined,
) {
  const resolvedContact = Array.isArray(contact) ? contact[0] : contact;
  const email = normalizeCampaignEmail(resolvedContact?.email);

  if (!email) {
    return;
  }

  recipients.set(email, {
    contactId: resolvedContact?.id ?? null,
    name: resolvedContact?.full_name?.trim() || email,
    email,
  });
}
