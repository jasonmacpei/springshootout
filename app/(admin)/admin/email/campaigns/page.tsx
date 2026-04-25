import {
  createEmailCampaignAction,
  deleteEmailCampaignAction,
  retryEmailDeliveryAction,
  sendEmailCampaignAction,
} from "@/actions/admin-ops";
import { Card, CardDescription, CardTitle } from "@/components/ui/card";
import { appConfig } from "@/lib/config";
import {
  listEmailCampaignsForAdmin,
  listEmailTemplatesForAdmin,
  listRecentAuditLogs,
  listRecentEmailDeliveriesForAdmin,
} from "@/lib/db/queries/content";
import { formatDateTime } from "@/lib/utils";

export default async function AdminEmailCampaignsPage({
  searchParams,
}: {
  searchParams?: Promise<{ error?: string; deliveryStatus?: string; campaignId?: string }>;
}) {
  const params = searchParams ? await searchParams : undefined;
  const [campaigns, templates, deliveries, auditLogs] = await Promise.all([
    listEmailCampaignsForAdmin(),
    listEmailTemplatesForAdmin(),
    listRecentEmailDeliveriesForAdmin(appConfig.defaultEventSlug, {
      status: params?.deliveryStatus ?? null,
      campaignId: params?.campaignId ?? null,
      limit: 50,
    }),
    listRecentAuditLogs(),
  ]);

  return (
    <div className="space-y-6">
      <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10">
        <CardTitle className="text-white">Campaigns</CardTitle>
        <CardDescription className="text-[#9fb2ce]">
          Build drafts, send Resend broadcasts to local event contacts, and monitor delivery tracking.
        </CardDescription>
        {params?.error ? <p className="mt-4 text-sm text-red-300">{params.error}</p> : null}
      </Card>
      <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10">
        <CardTitle className="text-white">New draft campaign</CardTitle>
        <form action={createEmailCampaignAction} className="mt-5 grid gap-4">
          <input name="eventSlug" type="hidden" value={appConfig.defaultEventSlug} />
          <label className="grid gap-2 text-sm font-medium text-white">
            Base template
            <select className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" defaultValue="" name="templateId">
              <option value="">No template</option>
              {templates.map((template) => (
                <option key={template.id} value={template.id}>
                  {template.slug}
                </option>
              ))}
            </select>
          </label>
          <label className="grid gap-2 text-sm font-medium text-white">
            Subject
            <input className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" name="subject" required />
          </label>
          <label className="grid gap-2 text-sm font-medium text-white">
            HTML content
            <textarea className="min-h-32 rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" name="contentHtml" />
          </label>
          <label className="grid gap-2 text-sm font-medium text-white">
            Text content
            <textarea className="min-h-28 rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" name="contentText" />
          </label>
          <div>
            <button className="rounded-full bg-white px-4 py-2 text-sm font-semibold text-[#11182a]" type="submit">
              Create draft campaign
            </button>
          </div>
        </form>
      </Card>
      <div className="space-y-4">
        {campaigns.map((campaign) => (
          <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10" key={campaign.id}>
            <CardTitle className="text-white">{campaign.subject}</CardTitle>
            <CardDescription className="text-[#9fb2ce]">
              Status: <span className="capitalize">{campaign.status}</span>
            </CardDescription>
            <div className="mt-5 grid gap-2 text-sm text-[#dce7f8]">
              <p>Template: {campaign.templateSlug ?? "No template"}</p>
              <p>Created: {formatDateTime(campaign.createdAt)}</p>
              <p>Sent: {campaign.sentAt ? formatDateTime(campaign.sentAt) : "Not sent"}</p>
              <p>
                Deliveries: {campaign.deliveredCount}/{campaign.recipientCount || 0}
              </p>
            </div>
            <div className="mt-5">
              {campaign.status === "draft" ? (
                <div className="flex gap-3">
                  <form action={sendEmailCampaignAction}>
                    <input name="campaignId" type="hidden" value={campaign.id} />
                    <select className="mr-3 rounded-full border border-white/12 bg-[#121d31] px-3 py-2 text-xs text-white" defaultValue="all_contacts" name="audienceScope">
                      <option value="all_contacts">All event contacts</option>
                      <option value="approved_only">Approved teams only</option>
                      <option value="pending_only">Pending teams only</option>
                    </select>
                    <button className="rounded-full bg-white px-4 py-2 text-sm font-semibold text-[#11182a]" type="submit">
                      Send campaign
                    </button>
                  </form>
                  <form action={deleteEmailCampaignAction}>
                    <input name="campaignId" type="hidden" value={campaign.id} />
                    <button className="rounded-full border border-red-300 px-4 py-2 text-sm font-semibold text-red-200" type="submit">
                      Delete
                    </button>
                  </form>
                </div>
              ) : (
                <p className="text-sm text-[#9fb2ce]">Webhook updates will continue to settle delivery state after send.</p>
              )}
            </div>
          </Card>
        ))}
      </div>
      <div className="grid gap-6 lg:grid-cols-2">
        <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10">
          <CardTitle className="text-white">Recent deliveries</CardTitle>
          <form className="mt-5 grid gap-4 md:grid-cols-[1fr_1fr_auto]">
            <label className="grid gap-2 text-sm font-medium text-white">
              Delivery status
              <select
                className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white"
                defaultValue={params?.deliveryStatus ?? ""}
                name="deliveryStatus"
              >
                <option value="">All statuses</option>
                <option value="queued">Queued</option>
                <option value="sent">Sent</option>
                <option value="delivered">Delivered</option>
                <option value="failed">Failed</option>
                <option value="bounced">Bounced</option>
                <option value="complained">Complained</option>
              </select>
            </label>
            <label className="grid gap-2 text-sm font-medium text-white">
              Campaign
              <select
                className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white"
                defaultValue={params?.campaignId ?? ""}
                name="campaignId"
              >
                <option value="">All campaigns</option>
                {campaigns.map((campaign) => (
                  <option key={campaign.id} value={campaign.id}>
                    {campaign.subject}
                  </option>
                ))}
              </select>
            </label>
            <div className="flex items-end gap-3">
              <button className="rounded-full bg-white px-4 py-2 text-sm font-semibold text-[#11182a]" type="submit">
                Filter
              </button>
              <a className="rounded-full border border-white/12 px-4 py-2 text-sm font-semibold text-white" href="/admin/email/campaigns">
                Reset
              </a>
            </div>
          </form>
          <div className="mt-5 space-y-3">
            {deliveries.map((delivery) => (
              <div className="rounded-2xl border border-white/10 bg-[#121d31] px-4 py-3 text-sm" key={delivery.id}>
                <p className="font-semibold text-white">{delivery.recipientEmail}</p>
                <p className="mt-1 text-[#9fb2ce]">
                  {delivery.campaignSubject ?? "Campaign"} · {delivery.deliveryStatus ?? "pending"} · {formatDateTime(delivery.createdAt)}
                </p>
                {delivery.providerMessageId ? (
                  <p className="mt-1 text-xs text-[#89a3c8]">Provider id: {delivery.providerMessageId}</p>
                ) : null}
                {delivery.errorText ? <p className="mt-2 text-red-300">{delivery.errorText}</p> : null}
                {delivery.deliveryStatus && ["failed", "bounced", "complained"].includes(delivery.deliveryStatus) ? (
                  <form action={retryEmailDeliveryAction} className="mt-3">
                    <input name="deliveryId" type="hidden" value={delivery.id} />
                    <button className="rounded-full border border-amber-300 px-4 py-2 text-sm font-semibold text-amber-100" type="submit">
                      Retry delivery
                    </button>
                  </form>
                ) : null}
              </div>
            ))}
          </div>
        </Card>
        <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10">
          <CardTitle className="text-white">Recent audit events</CardTitle>
          <div className="mt-5 space-y-3">
            {auditLogs.map((entry) => (
              <div className="rounded-2xl border border-white/10 bg-[#121d31] px-4 py-3 text-sm" key={entry.id}>
                <p className="font-semibold text-white">{entry.action}</p>
                <p className="mt-1 text-[#9fb2ce]">
                  {entry.entityType} · {formatDateTime(entry.createdAt)}
                </p>
              </div>
            ))}
          </div>
        </Card>
      </div>
    </div>
  );
}
