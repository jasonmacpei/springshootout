"use client";

import { useActionState } from "react";

import { Button } from "@/components/ui/button";
import { Card, CardDescription, CardTitle } from "@/components/ui/card";
import type { PublicActionState } from "@/actions/public-registration";
import { submitRegistration } from "./actions";

const initialState: PublicActionState = {
  success: false,
  message: "",
};

export function RegisterForm({
  eventSlug,
  roles,
}: {
  eventSlug: string;
  roles: Array<{ slug: string; name: string }>;
}) {
  const [state, action, pending] = useActionState(submitRegistration, initialState);

  return (
    <Card className="bg-white">
      <CardTitle>Team registration</CardTitle>
      <CardDescription>Submit one primary contact now. Coaches, managers, and travel contacts can be added after the first registration is received.</CardDescription>
      <form action={action} className="mt-6 grid gap-4 md:grid-cols-2">
        <input name="eventSlug" type="hidden" value={eventSlug} />
        <label className="grid gap-2 text-sm font-medium">
          Team name
          <input className="rounded-2xl border border-black/10 px-4 py-3" name="teamName" required />
        </label>
        <label className="grid gap-2 text-sm font-medium">
          Primary contact
          <input className="rounded-2xl border border-black/10 px-4 py-3" name="contactName" required />
        </label>
        <label className="grid gap-2 text-sm font-medium">
          Contact role
          <select className="rounded-2xl border border-black/10 px-4 py-3" defaultValue="primary-contact" name="contactRole" required>
            {roles.map((role) => (
              <option key={role.slug} value={role.slug}>
                {role.name}
              </option>
            ))}
          </select>
        </label>
        <label className="grid gap-2 text-sm font-medium">
          Province
          <input className="rounded-2xl border border-black/10 px-4 py-3" name="province" required />
        </label>
        <label className="grid gap-2 text-sm font-medium">
          Division
          <input className="rounded-2xl border border-black/10 px-4 py-3" name="division" required />
        </label>
        <label className="grid gap-2 text-sm font-medium">
          Class
          <input className="rounded-2xl border border-black/10 px-4 py-3" name="className" required />
        </label>
        <label className="grid gap-2 text-sm font-medium">
          Email
          <input className="rounded-2xl border border-black/10 px-4 py-3" name="email" required type="email" />
        </label>
        <label className="grid gap-2 text-sm font-medium">
          Phone
          <input className="rounded-2xl border border-black/10 px-4 py-3" name="phone" required />
        </label>
        <label className="grid gap-2 text-sm font-medium md:col-span-2">
          Notes
          <textarea className="min-h-32 rounded-2xl border border-black/10 px-4 py-3" name="note" />
        </label>
        <div className="md:col-span-2 flex items-center justify-between gap-4">
          <p className={`text-sm ${state.success ? "text-emerald-700" : "text-[var(--muted-foreground)]"}`}>
            {state.message || "Registrations are submitted to Hoops Scorebook first and mirrored locally for the Spring Shootout admin workspace."}
          </p>
          <Button disabled={pending} type="submit">
            {pending ? "Submitting..." : "Submit registration"}
          </Button>
        </div>
      </form>
    </Card>
  );
}
