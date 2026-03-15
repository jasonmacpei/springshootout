import { deleteEmailTemplateAction, upsertEmailTemplateAction } from "@/actions/admin-ops";
import { Card, CardDescription, CardTitle } from "@/components/ui/card";
import { appConfig } from "@/lib/config";
import { listEmailTemplatesForAdmin } from "@/lib/db/queries/content";

export default async function AdminEmailTemplatesPage() {
  const templates = await listEmailTemplatesForAdmin();

  return (
    <div className="space-y-6">
      <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10">
        <CardTitle className="text-white">Email templates</CardTitle>
        <CardDescription className="text-[#9fb2ce]">
          Maintain welcome, registration follow-up, and broadcast templates locally before wiring send flows through Resend.
        </CardDescription>
      </Card>
      <div className="space-y-4">
        {templates.map((template) => (
          <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10" key={template.id}>
            <CardTitle className="text-white">{template.slug}</CardTitle>
            <form action={upsertEmailTemplateAction} className="mt-5 grid gap-4">
              <input name="templateId" type="hidden" value={template.id} />
              <input name="eventSlug" type="hidden" value={appConfig.defaultEventSlug} />
              <div className="grid gap-4 md:grid-cols-2">
                <label className="grid gap-2 text-sm font-medium text-white">
                  Slug
                  <input className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" defaultValue={template.slug} name="slug" required />
                </label>
                <label className="grid gap-2 text-sm font-medium text-white">
                  Subject
                  <input className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" defaultValue={template.subject} name="subject" required />
                </label>
              </div>
              <label className="grid gap-2 text-sm font-medium text-white">
                HTML body
                <textarea className="min-h-32 rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" defaultValue={template.htmlBody} name="htmlBody" />
              </label>
              <label className="grid gap-2 text-sm font-medium text-white">
                Text body
                <textarea className="min-h-28 rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" defaultValue={template.textBody} name="textBody" />
              </label>
              <label className="flex items-center gap-3 text-sm font-medium text-white">
                <input defaultChecked={template.isActive} name="isActive" type="checkbox" />
                Active template
              </label>
              <div>
                <button className="rounded-full bg-white px-4 py-2 text-sm font-semibold text-[#11182a]" type="submit">
                  Save template
                </button>
              </div>
            </form>
            <form action={deleteEmailTemplateAction} className="mt-3">
              <input name="templateId" type="hidden" value={template.id} />
              <button className="rounded-full border border-red-300 px-4 py-2 text-sm font-semibold text-red-200" type="submit">
                Delete template
              </button>
            </form>
          </Card>
        ))}
        <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10">
          <CardTitle className="text-white">New template</CardTitle>
          <form action={upsertEmailTemplateAction} className="mt-5 grid gap-4">
            <input name="eventSlug" type="hidden" value={appConfig.defaultEventSlug} />
            <div className="grid gap-4 md:grid-cols-2">
              <label className="grid gap-2 text-sm font-medium text-white">
                Slug
                <input className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" name="slug" required />
              </label>
              <label className="grid gap-2 text-sm font-medium text-white">
                Subject
                <input className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" name="subject" required />
              </label>
            </div>
            <label className="grid gap-2 text-sm font-medium text-white">
              HTML body
              <textarea className="min-h-32 rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" name="htmlBody" />
            </label>
            <label className="grid gap-2 text-sm font-medium text-white">
              Text body
              <textarea className="min-h-28 rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" name="textBody" />
            </label>
            <label className="flex items-center gap-3 text-sm font-medium text-white">
              <input defaultChecked name="isActive" type="checkbox" />
              Active template
            </label>
            <div>
              <button className="rounded-full bg-white px-4 py-2 text-sm font-semibold text-[#11182a]" type="submit">
                Create template
              </button>
            </div>
          </form>
        </Card>
      </div>
    </div>
  );
}
