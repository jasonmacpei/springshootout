import Image from "next/image";
import Link from "next/link";

import { PageHero } from "@/components/marketing/page-hero";
import { appConfig } from "@/lib/config";
import { getEventSettingsBySlug } from "@/lib/db/queries/content";

export default async function HomePage() {
  const settings = await getEventSettingsBySlug();

  return (
    <>
      <section className="relative overflow-hidden">
        <div className="pointer-events-none absolute inset-x-0 top-0 h-[30rem] bg-[radial-gradient(circle_at_12%_18%,rgba(85,160,227,0.18),transparent_24%),radial-gradient(circle_at_82%_14%,rgba(18,52,92,0.12),transparent_22%),linear-gradient(180deg,rgba(245,250,255,0.96)_0%,rgba(247,250,252,0)_100%)]" />
        <div className="pointer-events-none absolute left-[-6rem] top-24 h-52 w-52 rounded-full bg-[#5d99d8]/10 blur-3xl" />
        <div className="pointer-events-none absolute right-[-2rem] top-20 h-64 w-64 rounded-full bg-[#f08a4b]/8 blur-3xl" />
        <PageHero
          eyebrow={settings?.hero_eyebrow ?? "3rd annual tournament"}
          title={settings?.hero_title ?? "Spring Shootout returns to Charlottetown on May 8-10, 2026."}
          description={
            <>
              AtlanticHoops.com powers registration for Spring Shootout.
              <br />
              Quickly create an account and sign up.
            </>
          }
          actions={
            <Link
              className="inline-flex min-w-[280px] items-center justify-center whitespace-nowrap rounded-full px-8 py-4 text-xl font-bold leading-none shadow-[0_16px_32px_rgba(179,92,54,0.18)] transition hover:opacity-90"
              href={appConfig.registrationUrl}
              rel="noreferrer"
              style={{ backgroundColor: "#b35c36", color: "#fff8f1" }}
              target="_blank"
            >
                Register your team
            </Link>
          }
        />
      </section>
      <section className="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-10 lg:pb-24">
        <div className="relative overflow-hidden rounded-[32px] border border-white/70 bg-[linear-gradient(180deg,#f8fbff_0%,#eef4fb_100%)] p-3 shadow-[0_30px_90px_rgba(28,52,86,0.12)] sm:p-5">
          <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(94,156,220,0.16),transparent_24%),radial-gradient(circle_at_bottom_right,rgba(240,138,75,0.10),transparent_22%)]" />
          <div className="relative overflow-hidden rounded-[24px]">
            <Image
              alt="Spring Shootout 2026 tournament poster"
              className="h-auto w-full"
              height={1536}
              priority
              src="/images/posters/Spring Shootout 2026.png"
              width={832}
            />
          </div>
        </div>
      </section>
    </>
  );
}
