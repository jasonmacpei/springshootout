import { createTeamAction, deleteTeamAction, updateTeamAction } from "@/actions/admin-ops";
import { Card, CardDescription, CardTitle } from "@/components/ui/card";
import { listContactOptionsForAdmin, listContactRoleOptionsForAdmin, listTeamsForAdmin } from "@/lib/db/queries/content";
import { appConfig } from "@/lib/config";

export default async function AdminTeamsPage({
  searchParams,
}: {
  searchParams?: Promise<{ error?: string }>;
}) {
  const params = searchParams ? await searchParams : undefined;
  const [teams, contacts, contactRoles] = await Promise.all([
    listTeamsForAdmin(),
    listContactOptionsForAdmin(),
    listContactRoleOptionsForAdmin(),
  ]);

  return (
    <div className="space-y-6">
      <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10">
        <CardTitle className="text-white">Teams</CardTitle>
        <CardDescription className="text-[#9fb2ce]">
          Team records stay local for registration, contacts, and public publishing even while competition data is external.
        </CardDescription>
        {params?.error ? <p className="mt-4 text-sm text-red-300">{params.error}</p> : null}
      </Card>
      <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10">
        <CardTitle className="text-white">Create team</CardTitle>
        <form action={createTeamAction} className="mt-5 grid gap-4">
          <input name="eventSlug" type="hidden" value={appConfig.defaultEventSlug} />
          <label className="grid gap-2 text-sm font-medium text-white">
            Team name
            <input className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" name="name" required />
          </label>
          <div className="grid gap-4 md:grid-cols-3">
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
          </div>
          <div className="grid gap-4 md:grid-cols-2">
            <label className="grid gap-2 text-sm font-medium text-white">
              Primary contact
              <select className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" defaultValue="" name="primaryContactId">
                <option value="">Add later</option>
                {contacts.map((contact) => (
                  <option key={contact.id} value={contact.id}>
                    {contact.fullName} {contact.email ? `· ${contact.email}` : ""}
                  </option>
                ))}
              </select>
            </label>
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
          </div>
          <div>
            <button className="rounded-full bg-white px-4 py-2 text-sm font-semibold text-[#11182a]" type="submit">
              Create team
            </button>
          </div>
        </form>
      </Card>
      <div className="grid gap-4 lg:grid-cols-2">
        {teams.map((team) => (
          <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10" key={team.id}>
            <CardTitle className="text-white">{team.name}</CardTitle>
            <CardDescription className="text-[#9fb2ce]">
              Status: <span className="capitalize">{team.registrationStatus ?? "pending"}</span>
            </CardDescription>
            <form action={updateTeamAction} className="mt-5 grid gap-3">
              <input name="teamId" type="hidden" value={team.id} />
              <label className="grid gap-2 text-sm font-medium text-white">
                Team name
                <input className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" defaultValue={team.name} name="name" required />
              </label>
              <div className="grid gap-3 md:grid-cols-3">
                <label className="grid gap-2 text-sm font-medium text-white">
                  Division
                  <input className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" defaultValue={team.divisionName ?? ""} name="divisionName" />
                </label>
                <label className="grid gap-2 text-sm font-medium text-white">
                  Class
                  <input className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" defaultValue={team.className ?? ""} name="className" />
                </label>
                <label className="grid gap-2 text-sm font-medium text-white">
                  Province
                  <input className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" defaultValue={team.province ?? ""} name="province" />
                </label>
              </div>
              <div className="grid gap-2 text-sm text-[#dce7f8]">
                <p>Primary contact: {team.primaryContactName ?? "Unassigned"}</p>
                <p>Email: {team.primaryContactEmail ?? "No email"}</p>
              </div>
              <div>
                <button className="rounded-full bg-white px-4 py-2 text-sm font-semibold text-[#11182a]" type="submit">
                  Save team
                </button>
              </div>
            </form>
            <form action={deleteTeamAction} className="mt-3">
              <input name="teamId" type="hidden" value={team.id} />
              <button className="rounded-full border border-red-300 px-4 py-2 text-sm font-semibold text-red-200" type="submit">
                Delete team
              </button>
            </form>
          </Card>
        ))}
      </div>
    </div>
  );
}
