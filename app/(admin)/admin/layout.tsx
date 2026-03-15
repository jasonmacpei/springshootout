import { AdminShell } from "@/components/layout/admin-shell";

export default function AdminLayout({ children }: { children: React.ReactNode }) {
  return (
    <AdminShell
      title="Operations console"
      description="Own the local CMS, registrations, contacts, campaigns, and the adapter-backed competition workspace."
    >
      {children}
    </AdminShell>
  );
}
