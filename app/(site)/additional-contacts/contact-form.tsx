"use client";

import { useActionState } from "react";

import { submitAdditionalContact, type PublicActionState } from "@/actions/public-registration";
import { Button } from "@/components/ui/button";
import { Card, CardDescription, CardTitle } from "@/components/ui/card";

const initialState: PublicActionState = {
  success: false,
  message: "",
};

export function AdditionalContactForm({
  initialTeamId,
  roles,
  teams,
}: {
  initialTeamId?: string;
  roles: Array<{ slug: string; name: string }>;
  teams: Array<{ id: string; name: string; divisionName: string | null; className: string | null }>;
}) {
  const [state, action, pending] = useActionState(submitAdditionalContact, initialState);

  return (
    <Card className="bg-white">
      <CardTitle>Team contact update</CardTitle>
      <CardDescription>Add an extra coach, manager, or travel contact to a team already on file.</CardDescription>
      <form action={action} className="mt-6 grid gap-4 md:grid-cols-2">
        <label className="grid gap-2 text-sm font-medium md:col-span-2">
          Team
          <select className="rounded-2xl border border-black/10 px-4 py-3" defaultValue={initialTeamId ?? ""} name="teamId" required>
            <option value="">Select a team</option>
            {teams.map((team) => (
              <option key={team.id} value={team.id}>
                {team.name}
                {team.divisionName ? ` · ${team.divisionName}` : ""}
                {team.className ? ` · ${team.className}` : ""}
              </option>
            ))}
          </select>
        </label>
        <label className="grid gap-2 text-sm font-medium">
          Contact name
          <input className="rounded-2xl border border-black/10 px-4 py-3" name="contactName" required />
        </label>
        <label className="grid gap-2 text-sm font-medium">
          Role
          <select className="rounded-2xl border border-black/10 px-4 py-3" name="role" required>
            <option value="">Select a role</option>
            {roles.map((role) => (
              <option key={role.slug} value={role.slug}>
                {role.name}
              </option>
            ))}
          </select>
        </label>
        <label className="grid gap-2 text-sm font-medium">
          Email
          <input className="rounded-2xl border border-black/10 px-4 py-3" name="email" required type="email" />
        </label>
        <label className="grid gap-2 text-sm font-medium">
          Phone
          <input className="rounded-2xl border border-black/10 px-4 py-3" name="phone" required />
        </label>
        <div className="md:col-span-2 flex items-center justify-between gap-4">
          <p className={`text-sm ${state.success ? "text-emerald-700" : "text-[var(--muted-foreground)]"}`}>
            {state.message || "Additional contacts will be attached to the selected team record."}
          </p>
          <Button disabled={pending} type="submit">
            {pending ? "Saving..." : "Add contact"}
          </Button>
        </div>
      </form>
    </Card>
  );
}
