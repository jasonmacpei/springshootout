import { NextResponse } from "next/server";

import { logAuditEvent } from "@/lib/audit";
import { createAdminSupabaseClient } from "@/lib/db/admin";
import { createResendClient } from "@/lib/email/client";

export async function POST(request: Request) {
  const payload = await request.text();
  const resend = createResendClient();
  const supabase = createAdminSupabaseClient();

  if (!resend || !supabase || !process.env.RESEND_WEBHOOK_SECRET) {
    return NextResponse.json({ ok: false, error: "Email webhook is not configured." }, { status: 500 });
  }

  try {
    const svixId = request.headers.get("svix-id");
    const svixTimestamp = request.headers.get("svix-timestamp");
    const svixSignature = request.headers.get("svix-signature");

    if (!svixId || !svixTimestamp || !svixSignature) {
      return NextResponse.json({ ok: false, error: "Missing webhook signature headers." }, { status: 400 });
    }

    const event = resend.webhooks.verify({
      payload,
      headers: {
        id: svixId,
        timestamp: svixTimestamp,
        signature: svixSignature,
      },
      webhookSecret: process.env.RESEND_WEBHOOK_SECRET,
    });

    const deliveryStatus = event.type.replace("email.", "");
    const data = (event.data ?? {}) as unknown as Record<string, unknown>;
    const emailId = typeof data.email_id === "string" ? data.email_id : null;
    const firstRecipient =
      Array.isArray(data.to) && typeof data.to[0] === "string" ? data.to[0] : null;
    const tags = data.tags && typeof data.tags === "object" ? (data.tags as Record<string, string>) : {};
    const tagEntries = Object.entries(tags);
    const campaignId = tagEntries.find(([name]) => name === "campaign_id")?.[1] ?? null;

    if (emailId) {
      await supabase
        .from("email_deliveries")
        .update({
          delivery_status: deliveryStatus,
          error_text:
            deliveryStatus === "failed" || deliveryStatus === "bounced" || deliveryStatus === "complained"
              ? JSON.stringify(event.data)
              : null,
        })
        .eq("provider_message_id", emailId);
    }

    await logAuditEvent(supabase, {
      entityType: "email_delivery",
      entityId: campaignId,
      action: `webhook.${event.type}`,
      metadata: {
        emailId,
        recipient: firstRecipient,
      },
    });

    return NextResponse.json({ ok: true });
  } catch {
    return NextResponse.json({ ok: false, error: "Invalid webhook signature." }, { status: 400 });
  }
}
