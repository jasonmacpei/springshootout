import { describe, expect, it } from "vitest";

import {
  additionalContactSchema,
  registrationAdminSchema,
  registrationSchema,
  resetPasswordSchema,
  staffInviteSchema,
} from "@/lib/validation/forms";

describe("form validation", () => {
  it("accepts a valid registration payload", () => {
    const payload = registrationSchema.safeParse({
      eventSlug: "spring-shootout-2026",
      teamName: "Halifax Heat",
      contactName: "Jordan Lee",
      contactRole: "Head Coach",
      province: "NS",
      division: "U13",
      className: "AA",
      email: "coach@example.com",
      phone: "902-555-1212",
      note: "Need early Friday game.",
    });

    expect(payload.success).toBe(true);
  });

  it("rejects a broken extra contact payload", () => {
    const payload = additionalContactSchema.safeParse({
      teamName: "",
      contactName: "A",
      email: "not-an-email",
      phone: "",
      role: "",
    });

    expect(payload.success).toBe(false);
  });

  it("accepts a valid staff invite payload", () => {
    const payload = staffInviteSchema.safeParse({
      email: "staff@example.com",
      displayName: "Staff User",
      role: "comms",
    });

    expect(payload.success).toBe(true);
  });

  it("rejects mismatched password reset payloads", () => {
    const payload = resetPasswordSchema.safeParse({
      password: "password123",
      confirmPassword: "password321",
    });

    expect(payload.success).toBe(false);
  });

  it("accepts a valid admin registration edit payload", () => {
    const payload = registrationAdminSchema.safeParse({
      registrationId: "7192c9d1-8985-4a2c-b76f-a988be10f5be",
      status: "approved",
      primaryContactId: "",
      note: "Internal review complete",
    });

    expect(payload.success).toBe(true);
  });
});
