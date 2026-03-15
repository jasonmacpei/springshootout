import { AdditionalContactForm } from "./contact-form";
import { PageHero } from "@/components/marketing/page-hero";
import { EmptyState } from "@/components/states/empty-state";
import { Card, CardDescription, CardTitle } from "@/components/ui/card";
import { appConfig } from "@/lib/config";
import { listContactRoles, listTeamOptionsByEventSlug } from "@/lib/db/queries/content";

export default async function AdditionalContactsPage({
  searchParams,
}: {
  searchParams?: Promise<{ teamId?: string }>;
}) {
  const params = searchParams ? await searchParams : undefined;
  const [roles, teams] = await Promise.all([listContactRoles(), listTeamOptionsByEventSlug()]);
  const formUnavailable = roles.length === 0;

  return (
    <>
      <PageHero
        eyebrow="Additional contacts"
        title="Add team contacts"
        description="Use this follow-up flow if your team needs extra coach, manager, or travel-contact records after the first registration is submitted. These updates currently sync to the Spring Shootout admin workspace only while partner API coverage is pending."
      />
      <section className="mx-auto max-w-4xl px-6 pb-20 lg:px-10">
        {formUnavailable ? (
          <Card>
            <CardTitle>Additional contacts unavailable</CardTitle>
            <CardDescription>Contact roles have not been configured for this event yet, so this follow-up form is disabled.</CardDescription>
            <p className="mt-5 text-sm text-[var(--muted-foreground)]">
              Contact: {appConfig.supportEmail} · {appConfig.supportPhone}
            </p>
          </Card>
        ) : teams.length ? (
          <AdditionalContactForm initialTeamId={params?.teamId} roles={roles} teams={teams} />
        ) : (
          <Card>
            <CardTitle>No team records yet</CardTitle>
            <CardDescription>
              Submit the first registration before using this page. Once a team is on file, extra coaches and managers can be attached here.
            </CardDescription>
            <p className="mt-5 text-sm text-[var(--muted-foreground)]">
              Contact: {appConfig.supportEmail} · {appConfig.supportPhone}
            </p>
          </Card>
        )}
        {formUnavailable ? (
          <div className="mt-6">
            <EmptyState title="Seed data required" description="Load the contact role seed data before accepting post-registration contact updates." />
          </div>
        ) : !teams.length ? (
          <div className="mt-6">
            <EmptyState title="Start with registration" description="The additional-contact flow needs at least one team record to attach staff against." />
          </div>
        ) : null}
      </section>
    </>
  );
}
