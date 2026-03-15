"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";

import { canManageComms } from "@/lib/auth/roles";
import { logAuditEvent } from "@/lib/audit";
import { requireStaffSession } from "@/lib/auth/session";
import { createServerSupabaseClient } from "@/lib/db/server";

export async function updateCmsPageAction(formData: FormData) {
  const session = await requireStaffSession();
  const slug = String(formData.get("slug") ?? "").trim();

  if (!canManageComms(session.role)) {
    const params = new URLSearchParams({ error: "You do not have permission to edit content." });
    redirect(`/admin/content/${slug || "content"}?${params.toString()}`);
  }

  const supabase = await createServerSupabaseClient();

  if (!supabase) {
    const params = new URLSearchParams({ error: "Supabase is not configured." });
    redirect(`/admin/content/${slug || "content"}?${params.toString()}`);
  }

  const pageId = String(formData.get("pageId") ?? "").trim();
  const eventSlug = String(formData.get("eventSlug") ?? "").trim();
  const title = String(formData.get("title") ?? "").trim();
  const subtitle = String(formData.get("subtitle") ?? "").trim();
  const status = String(formData.get("status") ?? "draft").trim() as "draft" | "published";

  if (!eventSlug || !slug || !title) {
    const params = new URLSearchParams({ error: "Title, slug, and event are required." });
    redirect(`/admin/content/${slug || "content"}?${params.toString()}`);
  }

  const { data: eventRecord } = await supabase
    .from("events")
    .select("id")
    .eq("slug", eventSlug)
    .maybeSingle();

  if (!eventRecord?.id) {
    const params = new URLSearchParams({ error: "Event record could not be found." });
    redirect(`/admin/content/${slug}?${params.toString()}`);
  }

  let resolvedPageId = pageId;
  const isCreate = !resolvedPageId;

  if (resolvedPageId) {
    const { error } = await supabase
      .from("cms_pages")
      .update({
        title,
        subtitle,
        status,
      })
      .eq("id", resolvedPageId);

    if (error) {
      const params = new URLSearchParams({ error: error.message });
      redirect(`/admin/content/${slug}?${params.toString()}`);
    }
  } else {
    const { data: insertedPage, error } = await supabase
      .from("cms_pages")
      .insert({
        event_id: eventRecord.id,
        slug,
        title,
        subtitle,
        status,
      })
      .select("id")
      .single();

    if (error || !insertedPage?.id) {
      const params = new URLSearchParams({ error: error?.message ?? "Unable to create page." });
      redirect(`/admin/content/${slug}?${params.toString()}`);
    }

    resolvedPageId = insertedPage.id;
  }

  const headingValues = formData.getAll("sectionHeading").map((value) => String(value).trim());
  const bodyValues = formData.getAll("sectionBody").map((value) => String(value).trim());

  const sections = headingValues
    .map((heading, index) => ({
      heading,
      body: bodyValues[index] ?? "",
      sort_order: index,
      kind: "rich_text",
    }))
    .filter((section) => section.heading || section.body);

  const { error: deleteError } = await supabase.from("cms_sections").delete().eq("page_id", resolvedPageId);

  if (deleteError) {
    const params = new URLSearchParams({ error: deleteError.message });
    redirect(`/admin/content/${slug}?${params.toString()}`);
  }

  if (sections.length) {
    const { error: insertError } = await supabase.from("cms_sections").insert(
      sections.map((section) => ({
        page_id: resolvedPageId,
        ...section,
      })),
    );

    if (insertError) {
      const params = new URLSearchParams({ error: insertError.message });
      redirect(`/admin/content/${slug}?${params.toString()}`);
    }
  }

  await logAuditEvent(supabase, {
    actorUserId: session.user.id,
    entityType: "cms_page",
    entityId: resolvedPageId,
    action: isCreate ? "created" : "updated",
    metadata: {
      slug,
      status,
      sectionCount: sections.length,
    },
  });

  revalidatePath("/");
  revalidatePath(`/${slug}`);
  revalidatePath("/admin");
  revalidatePath("/admin/content");
  revalidatePath(`/admin/content/${slug}`);

  redirect(`/admin/content/${slug}?saved=1`);
}
