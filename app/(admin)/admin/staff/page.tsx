import { redirect } from "next/navigation";

import {
  inviteStaffMemberAction,
  revokeStaffAccessAction,
  updateStaffMemberRoleAction,
} from "@/actions/admin-ops";
import { Card, CardDescription, CardTitle } from "@/components/ui/card";
import { assignableStaffRoles, canManageStaff } from "@/lib/auth/roles";
import { requireStaffSession } from "@/lib/auth/session";
import { listStaffUsersForAdmin } from "@/lib/db/queries/staff";
import { formatDateTime } from "@/lib/utils";

export default async function AdminStaffPage({
  searchParams,
}: {
  searchParams?: Promise<{ error?: string }>;
}) {
  const session = await requireStaffSession();

  if (!canManageStaff(session.role)) {
    redirect("/admin");
  }

  const params = searchParams ? await searchParams : undefined;
  const [staffUsers] = await Promise.all([listStaffUsersForAdmin()]);
  const allowedRoles = assignableStaffRoles(session.role);

  return (
    <div className="space-y-6">
      <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10">
        <CardTitle className="text-white">Staff access</CardTitle>
        <CardDescription className="text-[#9fb2ce]">
          Invite staff, replace role assignments, and revoke dashboard access. Role changes from this screen replace any existing staff roles.
        </CardDescription>
        {params?.error ? <p className="mt-4 text-sm text-red-300">{params.error}</p> : null}
      </Card>

      <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10">
        <CardTitle className="text-white">Invite or grant access</CardTitle>
        <form action={inviteStaffMemberAction} className="mt-5 grid gap-4 md:grid-cols-2">
          <label className="grid gap-2 text-sm font-medium text-white">
            Display name
            <input className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" name="displayName" required />
          </label>
          <label className="grid gap-2 text-sm font-medium text-white">
            Email
            <input className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" name="email" required type="email" />
          </label>
          <label className="grid gap-2 text-sm font-medium text-white md:col-span-2">
            Role
            <select className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" defaultValue={allowedRoles[0] ?? "comms"} name="role">
              {allowedRoles.map((role) => (
                <option key={role} value={role}>
                  {role}
                </option>
              ))}
            </select>
          </label>
          <div className="md:col-span-2">
            <button className="rounded-full bg-white px-4 py-2 text-sm font-semibold text-[#11182a]" type="submit">
              Invite staff member
            </button>
          </div>
        </form>
      </Card>

      <div className="grid gap-4 lg:grid-cols-2">
        {staffUsers.map((staffUser) => (
          <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10" key={staffUser.id}>
            <CardTitle className="text-white">{staffUser.displayName}</CardTitle>
            <CardDescription className="text-[#9fb2ce]">{staffUser.email}</CardDescription>
            <div className="mt-5 grid gap-2 text-sm text-[#dce7f8]">
              <p>Effective role: {staffUser.effectiveRole ?? "none"}</p>
              <p>Assigned roles: {staffUser.assignedRoles.join(", ") || "none"}</p>
              <p>Created: {staffUser.createdAt ? formatDateTime(staffUser.createdAt) : "Unknown"}</p>
              <p>Last sign-in: {staffUser.lastSignInAt ? formatDateTime(staffUser.lastSignInAt) : "Never"}</p>
            </div>
            <form action={updateStaffMemberRoleAction} className="mt-5 grid gap-3">
              <input name="userId" type="hidden" value={staffUser.id} />
              <label className="grid gap-2 text-sm font-medium text-white">
                Replace role assignment
                <select
                  className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white"
                  defaultValue={staffUser.effectiveRole ?? allowedRoles[0] ?? "comms"}
                  name="role"
                >
                  {allowedRoles.map((role) => (
                    <option key={role} value={role}>
                      {role}
                    </option>
                  ))}
                </select>
              </label>
              <div className="flex gap-3">
                <button className="rounded-full bg-white px-4 py-2 text-sm font-semibold text-[#11182a]" type="submit">
                  Save role
                </button>
              </div>
            </form>
            <form action={revokeStaffAccessAction} className="mt-3">
              <input name="userId" type="hidden" value={staffUser.id} />
              <button className="rounded-full border border-red-300 px-4 py-2 text-sm font-semibold text-red-200" type="submit">
                Revoke access
              </button>
            </form>
          </Card>
        ))}
      </div>
    </div>
  );
}
