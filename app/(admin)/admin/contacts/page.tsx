import { createContactAction, deleteContactAction, updateContactAction } from "@/actions/admin-ops";
import { Card, CardDescription, CardTitle } from "@/components/ui/card";
import { listContactRoleOptionsForAdmin, listContactsForAdmin, listTeamOptionsForAdmin } from "@/lib/db/queries/content";

export default async function AdminContactsPage({
  searchParams,
}: {
  searchParams?: Promise<{ error?: string }>;
}) {
  const params = searchParams ? await searchParams : undefined;
  const [contacts, teams, contactRoles] = await Promise.all([
    listContactsForAdmin(),
    listTeamOptionsForAdmin(),
    listContactRoleOptionsForAdmin(),
  ]);

  return (
    <div className="space-y-6">
      <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10">
        <CardTitle className="text-white">Contacts</CardTitle>
        <CardDescription className="text-[#9fb2ce]">
          Contact and team-contact management are now local admin responsibilities and no longer ride through unsecured PHP endpoints.
        </CardDescription>
        {params?.error ? <p className="mt-4 text-sm text-red-300">{params.error}</p> : null}
      </Card>
      <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10">
        <CardTitle className="text-white">Create contact</CardTitle>
        <form action={createContactAction} className="mt-5 grid gap-4">
          <div className="grid gap-4 md:grid-cols-2">
            <label className="grid gap-2 text-sm font-medium text-white">
              Full name
              <input className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" name="fullName" required />
            </label>
            <label className="grid gap-2 text-sm font-medium text-white">
              Email
              <input className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" name="email" type="email" />
            </label>
          </div>
          <div className="grid gap-4 md:grid-cols-2">
            <label className="grid gap-2 text-sm font-medium text-white">
              Phone
              <input className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" name="phone" />
            </label>
            <label className="grid gap-2 text-sm font-medium text-white">
              Link to team
              <select className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" defaultValue="" name="teamId">
                <option value="">Leave unlinked</option>
                {teams.map((team) => (
                  <option key={team.id} value={team.id}>
                    {team.name} {[team.divisionName, team.className].filter(Boolean).join(" · ")}
                  </option>
                ))}
              </select>
            </label>
          </div>
          <div className="grid gap-4 md:grid-cols-2">
            <label className="grid gap-2 text-sm font-medium text-white">
              Team role
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
              Notes
              <textarea className="min-h-24 rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" name="notes" />
            </label>
          </div>
          <div>
            <button className="rounded-full bg-white px-4 py-2 text-sm font-semibold text-[#11182a]" type="submit">
              Create contact
            </button>
          </div>
        </form>
      </Card>
      <div className="grid gap-4 lg:grid-cols-2">
        {contacts.map((contact) => (
          <Card className="bg-white/6 text-white shadow-none ring-1 ring-white/10" key={contact.id}>
            <CardTitle className="text-white">{contact.fullName}</CardTitle>
            <CardDescription className="text-[#9fb2ce]">{contact.teams.join(", ") || "No team links"}</CardDescription>
            <form action={updateContactAction} className="mt-5 grid gap-3">
              <input name="contactId" type="hidden" value={contact.id} />
              <label className="grid gap-2 text-sm font-medium text-white">
                Full name
                <input className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" defaultValue={contact.fullName} name="fullName" required />
              </label>
              <label className="grid gap-2 text-sm font-medium text-white">
                Email
                <input className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" defaultValue={contact.email ?? ""} name="email" type="email" />
              </label>
              <label className="grid gap-2 text-sm font-medium text-white">
                Phone
                <input className="rounded-2xl border border-white/12 bg-[#121d31] px-4 py-3 text-white" defaultValue={contact.phone ?? ""} name="phone" />
              </label>
              <div>
                <button className="rounded-full bg-white px-4 py-2 text-sm font-semibold text-[#11182a]" type="submit">
                  Save contact
                </button>
              </div>
            </form>
            <form action={deleteContactAction} className="mt-3">
              <input name="contactId" type="hidden" value={contact.id} />
              <button className="rounded-full border border-red-300 px-4 py-2 text-sm font-semibold text-red-200" type="submit">
                Delete contact
              </button>
            </form>
          </Card>
        ))}
      </div>
    </div>
  );
}
