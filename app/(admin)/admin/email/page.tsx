import Link from "next/link";

import { Card, CardDescription, CardTitle } from "@/components/ui/card";
import { listEmailCampaignsForAdmin, listEmailTemplatesForAdmin } from "@/lib/db/queries/content";

export default async function AdminEmailPage() {
  const [templates, campaigns] = await Promise.all([
    listEmailTemplatesForAdmin(),
    listEmailCampaignsForAdmin(),
  ]);

  const draftCampaigns = campaigns.filter((campaign) => campaign.status === "draft").length;
  const sentCampaigns = campaigns.filter((campaign) => campaign.status === "sent").length;

  return (
    <div className="space-y-6">
      <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10">
        <CardTitle className="text-white">Email operations</CardTitle>
        <CardDescription className="text-[#9fb2ce]">
          Manage reusable templates, draft campaigns, and delivery follow-up from one place.
        </CardDescription>
      </Card>

      <div className="grid gap-4 md:grid-cols-3">
        <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10">
          <CardDescription className="text-[#9fb2ce]">Active templates</CardDescription>
          <CardTitle className="mt-3 text-white">{String(templates.filter((template) => template.isActive).length)}</CardTitle>
        </Card>
        <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10">
          <CardDescription className="text-[#9fb2ce]">Draft campaigns</CardDescription>
          <CardTitle className="mt-3 text-white">{String(draftCampaigns)}</CardTitle>
        </Card>
        <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10">
          <CardDescription className="text-[#9fb2ce]">Sent campaigns</CardDescription>
          <CardTitle className="mt-3 text-white">{String(sentCampaigns)}</CardTitle>
        </Card>
      </div>

      <div className="grid gap-4 lg:grid-cols-2">
        <Link href="/admin/email/templates">
          <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10 transition hover:bg-white/10">
            <CardTitle className="text-white">Templates</CardTitle>
            <CardDescription className="text-[#9fb2ce]">
              Maintain welcome, registration, and broadcast templates before campaigns are sent.
            </CardDescription>
          </Card>
        </Link>
        <Link href="/admin/email/campaigns">
          <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10 transition hover:bg-white/10">
            <CardTitle className="text-white">Campaigns</CardTitle>
            <CardDescription className="text-[#9fb2ce]">
              Create drafts, send to filtered audiences, and review recent delivery activity.
            </CardDescription>
          </Card>
        </Link>
      </div>
    </div>
  );
}
