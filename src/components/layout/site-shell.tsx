import Link from "next/link";

import { Button } from "@/components/ui/button";
import { appConfig } from "@/lib/config";
import type { NavigationItem } from "@/types/domain";

const navigation: NavigationItem[] = [
  { href: "/", label: "Home" },
  { href: appConfig.registrationUrl, label: "Register" },
  { href: "/schedule", label: "Schedule" },
  { href: "/results", label: "Results" },
  { href: "/standings", label: "Standings" },
  { href: "/rules", label: "Rules" },
  { href: "/gyms", label: "Gyms" },
  { href: "/hotels", label: "Hotels" },
  { href: "/contact", label: "Contact" },
];

export function SiteShell({ children }: { children: React.ReactNode }) {
  return (
    <div className="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(94,156,220,0.12),_transparent_28%),radial-gradient(circle_at_top_right,_rgba(17,52,92,0.08),_transparent_22%),linear-gradient(180deg,#f7fbff_0%,#eef4fb_45%,#f8fafc_100%)] text-[var(--foreground)]">
      <header className="sticky top-0 z-40 border-b border-black/5 bg-[rgba(247,250,252,0.88)] backdrop-blur">
        <div className="mx-auto flex max-w-7xl items-center justify-between px-6 py-4 lg:px-10">
          <div>
            <p className="text-xs font-semibold uppercase tracking-[0.28em] text-[var(--muted-foreground)]">
              Atlantic Basketball Weekend
            </p>
            <Link className="mt-1 block text-xl font-black uppercase tracking-[0.08em]" href="/">
              {appConfig.appName}
            </Link>
          </div>
          <nav className="hidden items-center gap-6 lg:flex">
            {navigation.map((item) => (
              <Link
                className="text-sm font-medium text-[var(--muted-foreground)] transition hover:text-[var(--foreground)]"
                href={item.href}
                key={item.href}
                rel={item.href.startsWith("http") ? "noreferrer" : undefined}
                target={item.href.startsWith("http") ? "_blank" : undefined}
              >
                {item.label}
              </Link>
            ))}
          </nav>
          <Link href={appConfig.registrationUrl} rel="noreferrer" target="_blank">
            <Button size="sm">Get In</Button>
          </Link>
        </div>
      </header>
      <main>{children}</main>
      <footer className="border-t border-black/5 bg-white/60">
        <div className="mx-auto grid max-w-7xl gap-8 px-6 py-10 lg:grid-cols-3 lg:px-10">
          <div>
            <p className="text-sm font-semibold uppercase tracking-[0.2em] text-[var(--muted-foreground)]">
              Spring Shootout
            </p>
            <p className="mt-3 max-w-md text-sm leading-6 text-[var(--muted-foreground)]">
              Registration, event content, and communications are managed here. Live competition data is powered by
              Hoops Scorebook.
            </p>
          </div>
          <div>
            <p className="text-sm font-semibold uppercase tracking-[0.2em] text-[var(--muted-foreground)]">
              Support
            </p>
            <p className="mt-3 text-sm text-[var(--muted-foreground)]">{appConfig.supportEmail}</p>
            <p className="mt-1 text-sm text-[var(--muted-foreground)]">{appConfig.supportPhone}</p>
          </div>
          <div>
            <p className="text-sm font-semibold uppercase tracking-[0.2em] text-[var(--muted-foreground)]">
              Admin
            </p>
            <div className="mt-3 flex gap-3">
              <Link href="/login">
                <Button size="sm" variant="outline">
                  Staff login
                </Button>
              </Link>
              <Link href="/admin">
                <Button size="sm" variant="ghost">
                  Dashboard
                </Button>
              </Link>
            </div>
          </div>
        </div>
      </footer>
    </div>
  );
}
