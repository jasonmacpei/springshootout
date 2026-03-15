import Link from "next/link";

import { PageHero } from "@/components/marketing/page-hero";
import { Button } from "@/components/ui/button";
import { Card, CardDescription, CardTitle } from "@/components/ui/card";

export default async function RegistrationSuccessPage({
  searchParams,
}: {
  searchParams?: Promise<{ teamId?: string; teamName?: string; eventName?: string }>;
}) {
  const params = searchParams ? await searchParams : undefined;
  const teamId = params?.teamId ?? "";
  const teamName = params?.teamName ?? "Your team";
  const eventName = params?.eventName ?? "Spring Shootout";

  return (
    <>
      <PageHero
        eyebrow="Submission received"
        title={`${teamName} is in the queue.`}
        description={`Your registration for ${eventName} has been recorded as pending review. The tournament team can now follow up without you re-entering the whole form.`}
      />
      <section className="mx-auto max-w-4xl px-6 pb-20 lg:px-10">
        <Card className="bg-[linear-gradient(135deg,#fff8ef_0%,#ffffff_100%)]">
          <CardTitle>What happens next</CardTitle>
          <CardDescription>
            Tournament staff can review the team inside the new admin workspace. If you need to add a coach, manager, or travel contact right away, use the additional contacts flow below.
          </CardDescription>
          <div className="mt-6 flex flex-wrap gap-3">
            <Link href={`/additional-contacts${teamId ? `?teamId=${encodeURIComponent(teamId)}` : ""}`}>
              <Button>Add additional contacts</Button>
            </Link>
            <Link href="/">
              <Button variant="outline">Back to the site</Button>
            </Link>
          </div>
        </Card>
      </section>
    </>
  );
}
