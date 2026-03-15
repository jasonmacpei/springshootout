import Link from "next/link";

import { EmptyState } from "@/components/states/empty-state";
import { Badge } from "@/components/ui/badge";
import { Card, CardDescription, CardTitle } from "@/components/ui/card";
import { listCmsPages } from "@/lib/db/queries/content";

export default async function AdminContentPage() {
  const pages = await listCmsPages();

  if (!pages.length) {
    return (
      <EmptyState
        title="No CMS pages are seeded yet"
        description="Load the initial content seed for the active event before using the content editor. Once the seed is in place, rules, gyms, hotels, and contact pages will appear here."
      />
    );
  }

  return (
    <div className="grid gap-4 md:grid-cols-2">
      {pages.map((page) => (
        <Link href={`/admin/content/${page.slug}`} key={page.slug}>
          <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10">
            <div className="flex items-start justify-between gap-4">
              <div>
                <CardTitle className="text-white">{page.title}</CardTitle>
                <CardDescription className="text-[#9fb2ce]">{page.subtitle}</CardDescription>
              </div>
              <Badge className="bg-white/10 text-white capitalize">{page.status}</Badge>
            </div>
          </Card>
        </Link>
      ))}
    </div>
  );
}
