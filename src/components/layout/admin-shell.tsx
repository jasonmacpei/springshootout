import type { CSSProperties } from "react";

import { logoutAction } from "@/actions/auth";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { AdminNav } from "@/components/layout/admin-nav";
import { canManageStaff } from "@/lib/auth/roles";
import { requireStaffSession } from "@/lib/auth/session";

export async function AdminShell({
  title,
  description,
  children,
}: {
  title: string;
  description: string;
  children: React.ReactNode;
}) {
  const session = await requireStaffSession();
  const adminLinks = [
    { href: "/admin", label: "Overview" },
    { href: "/admin/events", label: "Events" },
    { href: "/admin/content", label: "Content" },
    { href: "/admin/registrations", label: "Registrations" },
    { href: "/admin/teams", label: "Teams" },
    { href: "/admin/contacts", label: "Contacts" },
    { href: "/admin/email", label: "Email" },
    { href: "/admin/email/templates", label: "Templates" },
    { href: "/admin/email/campaigns", label: "Campaigns" },
    ...(canManageStaff(session.role) ? [{ href: "/admin/staff", label: "Staff" }] : []),
    { href: "/admin/competition", label: "Competition" },
  ];

  return (
    <div
      className="min-h-screen bg-[var(--admin-bg)] text-[var(--foreground)]"
      style={
        {
          "--foreground": "#f3f7ff",
          "--muted-foreground": "#c7d5eb",
          "--border-strong": "rgba(255,255,255,0.18)",
        } as CSSProperties
      }
    >
      <div className="grid min-h-screen lg:grid-cols-[280px_1fr]">
        <aside className="border-r border-white/10 bg-slate-950 p-6">
          <p className="text-xs font-semibold uppercase tracking-[0.22em] text-sky-200/80">Spring Shootout Admin</p>
          <p className="mt-2 text-2xl font-bold text-white">{title}</p>
          <p className="mt-3 text-sm leading-6 text-slate-300">{description}</p>
          <div className="mt-6 flex gap-2">
            <Badge className="border-white/10 bg-white/10 text-white">{session.role ?? "setup"}</Badge>
            <Badge className="border-white/10 bg-white/10 text-white">
              {session.configured ? "supabase linked" : "local scaffold"}
            </Badge>
          </div>
          <AdminNav items={adminLinks} />
          <div className="mt-8">
            <form action={logoutAction}>
              <Button className="w-full" type="submit" variant="secondary">
                Sign out
              </Button>
            </form>
          </div>
        </aside>
        <div className="bg-[linear-gradient(180deg,#172033_0%,#111827_45%,#0f172a_100%)]">
          <div className="mx-auto max-w-6xl px-6 py-8">{children}</div>
        </div>
      </div>
    </div>
  );
}
