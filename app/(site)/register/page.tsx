import Link from "next/link";

import { PageHero } from "@/components/marketing/page-hero";
import { EmptyState } from "@/components/states/empty-state";
import { Button } from "@/components/ui/button";
import { getEventSettingsBySlug, listContactRoles } from "@/lib/db/queries/content";
import { appConfig } from "@/lib/config";
import { RegisterForm } from "./register-form";

export default async function RegisterPage() {
  const [roles, settings] = await Promise.all([listContactRoles(), getEventSettingsBySlug()]);
  const registrationUnavailable = roles.length === 0;

  return (
    <>
      <PageHero
        eyebrow="Registration"
        title={settings?.hero_title ?? "Register your team for Spring Shootout."}
        description={settings?.registration_status ?? "Registration details are being finalized."}
        actions={
          <Link href="/additional-contacts">
            <Button variant="outline">Add extra contacts</Button>
          </Link>
        }
      />
      <section className="mx-auto max-w-5xl px-6 pb-20 lg:px-10">
        {registrationUnavailable ? (
          <EmptyState
            title="Registration is temporarily unavailable"
            description="Contact roles have not been configured for this event yet, so the form is hidden until the event seed data is loaded."
          />
        ) : (
          <RegisterForm eventSlug={appConfig.defaultEventSlug} roles={roles} />
        )}
      </section>
    </>
  );
}
