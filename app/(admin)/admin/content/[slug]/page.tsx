import { updateCmsPageAction } from "@/actions/cms";
import { Card, CardDescription, CardTitle } from "@/components/ui/card";
import { getCmsEditorPageBySlug } from "@/lib/db/queries/content";
import { CmsEditorFormSections } from "./cms-editor-form";

export default async function AdminContentEditorPage({
  params,
  searchParams,
}: {
  params: Promise<{ slug: string }>;
  searchParams?: Promise<{ error?: string; saved?: string }>;
}) {
  const { slug } = await params;
  const query = searchParams ? await searchParams : undefined;
  const page = await getCmsEditorPageBySlug(slug);

  if (!page) {
    return (
      <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10">
        <CardTitle className="text-white capitalize">Page not found</CardTitle>
        <CardDescription className="text-[#9fb2ce]">
          There is no CMS page configured for <strong>{slug}</strong>.
        </CardDescription>
      </Card>
    );
  }

  return (
    <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10">
      <CardTitle className="text-white capitalize">Edit {page.title}</CardTitle>
      <CardDescription className="text-[#9fb2ce]">
        Update the public copy for <strong>{page.slug}</strong>. Saving here updates the live page immediately after revalidation.
      </CardDescription>
      {page.source === "fallback" ? (
        <p className="mt-4 rounded-2xl border border-amber-400/30 bg-amber-400/10 px-4 py-3 text-sm text-amber-100">
          This editor is currently using seed fallback copy. Save once Supabase is configured to create the real CMS record.
        </p>
      ) : null}
      {query?.error ? (
        <p className="mt-4 rounded-2xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-100">
          {query.error}
        </p>
      ) : null}
      {query?.saved ? (
        <p className="mt-4 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">
          Changes saved.
        </p>
      ) : null}
      <form action={updateCmsPageAction} className="mt-6 space-y-6">
        <input name="pageId" type="hidden" value={page.id ?? ""} />
        <input name="eventSlug" type="hidden" value="spring-shootout-2026" />
        <input name="slug" type="hidden" value={page.slug} />
        <label className="grid gap-2 text-sm font-medium text-white">
          Title
          <input
            className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white"
            defaultValue={page.title}
            name="title"
            required
          />
        </label>
        <label className="grid gap-2 text-sm font-medium text-white">
          Subtitle
          <textarea
            className="min-h-28 rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white"
            defaultValue={page.subtitle}
            name="subtitle"
          />
        </label>
        <label className="grid gap-2 text-sm font-medium text-white">
          Status
          <select
            className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white"
            defaultValue={page.status}
            name="status"
          >
            <option value="draft">Draft</option>
            <option value="published">Published</option>
          </select>
        </label>
        <CmsEditorFormSections initialSections={page.sections} />
        <button className="inline-flex rounded-full bg-white px-5 py-2.5 text-sm font-semibold text-[#11182a]" type="submit">
          Save page
        </button>
      </form>
    </Card>
  );
}
