import Link from "next/link";

import { logoutAction } from "@/actions/auth";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
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
    <div className="min-h-screen bg-[var(--admin-bg)] text-[var(--foreground)]">
      <div className="grid min-h-screen lg:grid-cols-[280px_1fr]">
        <aside className="border-r border-white/10 bg-[#11182a] p-6">
          <p className="text-xs font-semibold uppercase tracking-[0.22em] text-[#89a3c8]">Spring Shootout Admin</p>
          <p className="mt-2 text-2xl font-bold text-white">{title}</p>
          <p className="mt-3 text-sm leading-6 text-[#9fb2ce]">{description}</p>
          <div className="mt-6 flex gap-2">
            <Badge className="bg-white/10 text-white">{session.role ?? "setup"}</Badge>
            <Badge className="bg-white/10 text-white">
              {session.configured ? "supabase linked" : "local scaffold"}
            </Badge>
          </div>
          <nav className="mt-8 space-y-2">
            {adminLinks.map((item) => (
              <Link
                className="block rounded-2xl px-4 py-3 text-sm font-medium text-[#dce7f8] transition hover:bg-white/8"
                href={item.href}
                key={item.href}
              >
                {item.label}
              </Link>
            ))}
          </nav>
          <div className="mt-8">
            <form action={logoutAction}>
              <Button className="w-full" type="submit" variant="secondary">
                Sign out
              </Button>
            </form>
          </div>
        </aside>
        <div className="bg-[linear-gradient(180deg,#131d31_0%,#0f1626_45%,#0b1120_100%)]">
          <div className="mx-auto max-w-6xl px-6 py-8">{children}</div>
        </div>
      </div>
    </div>
  );
}
