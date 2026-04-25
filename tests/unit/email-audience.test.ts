import { describe, expect, it } from "vitest";

import { addCampaignRecipient, normalizeCampaignEmail, type CampaignRecipient } from "@/lib/email/audience";

describe("email audience helpers", () => {
  it("normalizes valid campaign email addresses", () => {
    expect(normalizeCampaignEmail(" Coach@Example.COM ")).toBe("coach@example.com");
  });

  it("rejects blank and malformed email addresses", () => {
    expect(normalizeCampaignEmail("")).toBeNull();
    expect(normalizeCampaignEmail("not-an-email")).toBeNull();
    expect(normalizeCampaignEmail("coach@example")).toBeNull();
  });

  it("deduplicates recipients by normalized email", () => {
    const recipients = new Map<string, CampaignRecipient>();

    addCampaignRecipient(recipients, {
      id: "contact-1",
      full_name: "First Coach",
      email: "coach@example.com",
    });

    addCampaignRecipient(recipients, {
      id: "contact-2",
      full_name: "Updated Coach",
      email: " Coach@Example.com ",
    });

    expect(Array.from(recipients.values())).toEqual([
      {
        contactId: "contact-2",
        name: "Updated Coach",
        email: "coach@example.com",
      },
    ]);
  });
});
