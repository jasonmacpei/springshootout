import { createRegistrationAction, updateRegistrationAction } from "@/actions/admin-ops";
import { Card, CardDescription, CardTitle } from "@/components/ui/card";
import {
  listContactOptionsForAdmin,
  listContactRoleOptionsForAdmin,
  listRegistrationsForAdmin,
  listTeamOptionsForAdmin,
} from "@/lib/db/queries/content";
import { appConfig } from "@/lib/config";
import { formatDateTime } from "@/lib/utils";

const registrationStatuses = ["pending", "approved", "waitlisted", "withdrawn"] as const;

export default async function AdminRegistrationsPage({
  searchParams,
}: {
  searchParams?: Promise<{ error?: string }>;
}) {
  const params = searchParams ? await searchParams : undefined;
  const [registrations, contacts, teams, contactRoles] = await Promise.all([
    listRegistrationsForAdmin(),
    listContactOptionsForAdmin(),
    listTeamOptionsForAdmin(),
    listContactRoleOptionsForAdmin(),
  ]);

  return (
    <div className="space-y-6">
      <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10">
        <CardTitle className="text-white">Registrations</CardTitle>
        <CardDescription className="text-[#9fb2ce]">
          Review incoming registrations and move them between pending, approved, waitlisted, or withdrawn without leaving the new stack.
        </CardDescription>
        {params?.error ? <p className="mt-4 text-sm text-red-300">{params.error}</p> : null}
      </Card>
      <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10">
        <CardTitle className="text-white">Create registration</CardTitle>
        <form action={createRegistrationAction} className="mt-5 grid gap-4">
          <input name="eventSlug" type="hidden" value={appConfig.defaultEventSlug} />
          <div className="grid gap-4 md:grid-cols-2">
            <label className="grid gap-2 text-sm font-medium text-white">
              Team
              <select className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" defaultValue="" name="teamId" required>
                <option value="">Select a team</option>
                {teams.map((team) => (
                  <option key={team.id} value={team.id}>
                    {team.name} {[team.divisionName, team.className].filter(Boolean).join(" · ")}
                  </option>
                ))}
              </select>
            </label>
            <label className="grid gap-2 text-sm font-medium text-white">
              Primary contact
              <select className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" defaultValue="" name="primaryContactId">
                <option value="">No primary contact</option>
                {contacts.map((contact) => (
                  <option key={contact.id} value={contact.id}>
                    {contact.fullName} {contact.email ? `· ${contact.email}` : ""}
                  </option>
                ))}
              </select>
            </label>
          </div>
          <div className="grid gap-4 md:grid-cols-4">
            <label className="grid gap-2 text-sm font-medium text-white">
              Division
              <input className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" name="divisionName" />
            </label>
            <label className="grid gap-2 text-sm font-medium text-white">
              Class
              <input className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" name="className" />
            </label>
            <label className="grid gap-2 text-sm font-medium text-white">
              Province
              <input className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" name="province" />
            </label>
            <label className="grid gap-2 text-sm font-medium text-white">
              Status
              <select className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" defaultValue="pending" name="status">
                {registrationStatuses.map((status) => (
                  <option key={status} value={status}>
                    {status}
                  </option>
                ))}
              </select>
            </label>
          </div>
          <div className="grid gap-4 md:grid-cols-2">
            <label className="grid gap-2 text-sm font-medium text-white">
              Contact role
              <select className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" defaultValue="" name="roleId">
                <option value="">No role yet</option>
                {contactRoles.map((role) => (
                  <option key={role.id} value={role.id}>
                    {role.name}
                  </option>
                ))}
              </select>
            </label>
            <label className="grid gap-2 text-sm font-medium text-white">
              Note
              <textarea className="min-h-24 rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" name="note" />
            </label>
          </div>
          <div>
            <button className="rounded-full bg-white px-4 py-2 text-sm font-semibold text-[#11182a]" type="submit">
              Create registration
            </button>
          </div>
        </form>
      </Card>
      <Card className="overflow-hidden bg-white/6 text-white shadow-none ring-1 ring-white/10">
        <div className="overflow-x-auto">
          <table className="min-w-full text-left text-sm">
            <thead className="bg-white/6 text-[#9fb2ce]">
              <tr>
                <th className="px-4 py-3 font-medium">Team</th>
                <th className="px-4 py-3 font-medium">Division</th>
                <th className="px-4 py-3 font-medium">Primary contact</th>
                <th className="px-4 py-3 font-medium">Submitted</th>
                <th className="px-4 py-3 font-medium">Admin controls</th>
              </tr>
            </thead>
            <tbody>
              {registrations.map((registration) => (
                <tr className="border-t border-white/10" key={registration.id}>
                  <td className="px-4 py-4">
                    <p className="font-semibold text-white">{registration.teamName}</p>
                    <p className="text-xs uppercase tracking-[0.14em] text-[#9fb2ce]">
                      {[registration.className, registration.province].filter(Boolean).join(" · ")}
                    </p>
                  </td>
                  <td className="px-4 py-4 text-[#dce7f8]">{registration.divisionName ?? "Division TBD"}</td>
                  <td className="px-4 py-4 text-[#dce7f8]">
                    <p>{registration.primaryContactName ?? "Contact pending"}</p>
                    <p className="text-xs text-[#9fb2ce]">{registration.primaryContactEmail ?? "No email on file"}</p>
                  </td>
                  <td className="px-4 py-4 text-[#dce7f8]">{formatDateTime(registration.createdAt)}</td>
                  <td className="px-4 py-4">
                    <form action={updateRegistrationAction} className="grid gap-3">
                      <input name="registrationId" type="hidden" value={registration.id} />
                      <div className="flex items-center gap-2">
                        <select
                          className="rounded-full border border-white/12 bg-[#121d31] px-3 py-2 text-xs font-semibold uppercase tracking-[0.14em] text-white"
                          defaultValue={registration.status}
                          name="status"
                        >
                          {registrationStatuses.map((status) => (
                            <option key={status} value={status}>
                              {status}
                            </option>
                          ))}
                        </select>
                        <button className="rounded-full bg-white px-3 py-2 text-xs font-semibold text-[#11182a]" type="submit">
                          Save
                        </button>
                      </div>
                      <select
                        className="rounded-2xl border border-white/12 bg-[#121d31] px-3 py-2 text-xs text-white"
                        defaultValue={registration.primaryContactId ?? ""}
                        name="primaryContactId"
                      >
                        <option value="">No primary contact</option>
                        {contacts.map((contact) => (
                          <option key={contact.id} value={contact.id}>
                            {contact.fullName} {contact.email ? `· ${contact.email}` : ""}
                          </option>
                        ))}
                      </select>
                      <textarea
                        className="min-h-20 rounded-2xl border border-white/12 bg-[#121d31] px-3 py-2 text-xs text-white"
                        defaultValue={registration.note ?? ""}
                        name="note"
                        placeholder="Internal note or registration note"
                      />
                    </form>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </Card>
    </div>
  );
}
