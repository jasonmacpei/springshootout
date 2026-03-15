# Spring Shootout Rebuild Spec

## Summary
Rebuild Spring Shootout as a single `Next.js` App Router application on Vercel, backed by Supabase and Resend, with a fresh branded UI and a cleaner security model. The new app will own branding, public pages, registration, contacts, CMS-style content, admin email, and local back-office workflows. `hoopsscorebook.com` will be the long-term source of truth for competition operations such as schedule, pools, results, standings, and playoff logic; the new app will define and scaffold those screens now behind a typed adapter boundary so the API integration can be completed once documentation is provided.

Spring Shootout event data already exists in Hoops Scorebook for the active tournament, so the current integration target is not a blank future hookup. The app should use the configured `provider_event_slug` immediately for live event reads, while still treating broader endpoint coverage such as dedicated schedule, pools, and bracket feeds as phased follow-up work.

Hoops Scorebook now exposes a partner registration write endpoint for Spring Shootout intake. Spring Shootout should submit registrations there first so Hoops Scorebook becomes the canonical source of truth, while local Supabase remains a mirrored read model until equivalent partner read coverage exists for all local admin and public surfaces.

Target stack: `Next.js 16`, `React 19.2`, `TypeScript`, `Tailwind CSS 4`, `shadcn/ui`, `Supabase` (Auth/Postgres/Storage), `Resend`, `Zod`, `Playwright`, `Vitest`, `pnpm`, deployed on `Vercel`. Version basis: [Next.js docs/support](https://nextjs.org/docs/app/getting-started/upgrading), [Next.js support policy](https://nextjs.org/support-policy), [React 19.2](https://react.dev/blog/2025/10/01/react-19-2), [Tailwind docs](https://tailwindcss.com/), [Node download/LTS](https://nodejs.org/en/download/), [shadcn/ui install](https://ui.shadcn.com/docs/installation), [Vercel Next.js docs](https://vercel.com/docs/frameworks/nextjs), [Supabase Next.js guide](https://supabase.com/docs/guides/with-nextjs), [Supabase Auth quickstart](https://supabase.com/docs/guides/auth/quickstarts/nextjs), [Resend Next.js docs](https://resend.com/docs/send-with-nextjs).

## Key Changes
- Build one app with route groups for public, auth, and admin experiences.
- Replace the current PHP auth with Supabase Auth email/password for staff only.
- Use local app roles `owner`, `admin`, and `comms`; scoring stays outside this app with the sister system.
- Model the product as multi-event from day one so Spring Shootout becomes one event/season, not a hard-coded one-off.
- Move editable content into the database and manage it from an admin CMS instead of static files.
- Replace direct SMTP handling with Resend and React Email templates.
- Define a `CompetitionProvider` interface now and use it everywhere schedule/results/standings/ops data is needed.
- Use each event's configured `provider_event_slug` for competition reads instead of assuming the local app slug matches the external system slug forever.
- Submit registrations to Hoops Scorebook first and keep local Supabase as a mirrored read model until registration/team/contact readback can fully move upstream.
- Scaffold tournament-ops screens locally now; until the sister API is wired, those screens use a mock adapter in development and an “integration pending” state or feature flag in production.

## App Structure
```text
spring-shootout/
  src/
    app/
      (site)/
        page.tsx
        register/page.tsx
        register/success/page.tsx
        teams/page.tsx
        schedule/page.tsx
        results/page.tsx
        standings/page.tsx
        rules/page.tsx
        gyms/page.tsx
        hotels/page.tsx
        contact/page.tsx
        media/page.tsx
      (auth)/
        login/page.tsx
        forgot-password/page.tsx
        reset-password/page.tsx
      (admin)/
        admin/page.tsx
        admin/content/page.tsx
        admin/content/[slug]/page.tsx
        admin/events/page.tsx
        admin/events/[eventId]/settings/page.tsx
        admin/registrations/page.tsx
        admin/teams/page.tsx
        admin/contacts/page.tsx
        admin/email/page.tsx
        admin/email/templates/page.tsx
        admin/email/campaigns/page.tsx
        admin/competition/page.tsx
        admin/competition/schedule/page.tsx
        admin/competition/results/page.tsx
        admin/competition/standings/page.tsx
        admin/competition/pools/page.tsx
        admin/competition/playoffs/page.tsx
      api/
        competition/revalidate/route.ts
        resend/webhook/route.ts
        health/route.ts
      layout.tsx
      globals.css
    components/
      marketing/
      admin/
      forms/
      tables/
      email/
      layout/
      states/
      ui/
    lib/
      auth/
      cms/
      competition/
        adapters/
          mock.ts
          hoopsscorebook.ts
        schemas.ts
        service.ts
      db/
        server.ts
        client.ts
        queries/
      email/
      permissions/
      validation/
      utils/
    hooks/
    types/
  supabase/
    migrations/
    seeds/
    functions/
  public/
    images/
    icons/
  scripts/
    import-legacy/
    seed-dev/
    sync-competition/
  tests/
    e2e/
    integration/
    unit/
  docs/
    architecture/
    api-contracts/
  package.json
  pnpm-workspace.yaml
  middleware.ts
  components.json
  env.example
```

## Domain and Interface Spec
- Local core entities:
  - `events`, `event_settings`, `venues`, `divisions`
  - `teams`, `registrations`, `contacts`, `team_contacts`, `contact_roles`
  - `cms_pages`, `cms_sections`, `site_navigation`
  - `email_templates`, `email_campaigns`, `email_deliveries`
  - `staff_profiles`, `staff_role_assignments`, `audit_logs`
- Auth and permissions:
  - Supabase `auth.users` is the identity source.
  - App roles are `owner`, `admin`, and `comms`.
  - `owner` has full local control, including staff-role administration and owner-only safeguards.
  - `admin` can manage local operations and settings, but cannot override owner-only protections.
  - `comms` can manage content, contacts, registrations, and email, but cannot change app settings or protected competition integration settings.
- Competition boundary:
  - Create a typed `CompetitionProvider` interface with `getSchedule`, `getResults`, `getStandings`, `getPools`, `getPlayoffBrackets`, `getTeamsForEvent`, `submitScore`, and `refreshEvent`.
  - Resolve competition reads through each event's configured `provider_event_slug`, falling back to the local slug only when no provider slug is stored.
  - All public competition pages and admin ops scaffolds consume this interface only.
  - `MockCompetitionProvider` is used during initial build and tests.
  - `HoopsScorebookProvider` is the later implementation once the API docs are available.
- Registration ownership boundary:
  - Canonical write path: Spring Shootout submits registrations to Hoops Scorebook through the partner registration API.
  - Current read model: Spring Shootout mirrors registrations, teams, contacts, and team-contact links into local Supabase so existing admin and public pages continue to function.
  - Current gap: post-registration additional-contact updates still write to local Supabase only; no partner write endpoint is integrated for that flow yet.
  - Remaining follow-up: partner read coverage for registration/team/contact admin workflows and additional-contact updates after initial registration.
- Public routes mapped from the legacy site:
  - Home
  - Registration
  - Registration success
  - Additional contacts flow
  - Teams list
  - Schedule
  - Results
  - Standings
  - Rules
  - Gyms
  - Hotels
  - Contact
  - Media placeholder
- Admin routes mapped from the legacy site:
  - Dashboard
  - Event settings
  - Registrations
  - Teams
  - Contacts
  - Content/CMS
  - Email templates and campaigns
  - Competition screens for schedule, results, standings, pools, playoffs as adapter-backed scaffolds
- UI direction:
  - Use a fresh branded tournament look, not a Bootstrap port.
  - Public site should feel editorial and event-driven.
  - Admin should be dense, fast, and table-first.
  - Use shadcn primitives, custom tokens, and Tailwind theme variables rather than a generic dashboard template.
- Forms and mutations:
  - Use Server Actions plus shared Zod schemas.
  - Use optimistic UI only where low-risk; default to server-validated form submissions for admin actions.
- Content model:
  - Rules, gyms, hotels, contact info, homepage hero/promo blocks, and event notices are DB-managed CMS content.
  - Welcome email templates and email campaign templates are DB-managed and versioned.
- Email:
  - Use Resend with React Email templates for welcome email, confirmation, and admin broadcast sends.
  - Persist campaign metadata and delivery status in Supabase.
- Migration and rollout:
  - Import legacy Postgres data into Supabase.
  - Preserve current public URLs where practical with modern route names and redirects.
  - Launch public pages and local admin features first.
  - Complete sister API integration after the docs arrive, then remove temporary mock/integration-pending states.

## Build Phases
- Phase 1: foundation
  - Scaffold the Next.js app, auth shell, Tailwind/shadcn setup, Supabase wiring, environment management, and CI on Vercel.
  - Add RLS, role model, base layout, design tokens, and navigation.
- Phase 2: data and CMS
  - Create migrations for local entities and import scripts from the legacy database.
  - Build CMS editing, event settings, registrations, teams, contacts, and email templates.
- Phase 3: public experience
  - Build the new homepage and all public informational pages.
  - Build registration and additional-contact flows.
  - Build public teams, schedule, results, and standings pages on the competition adapter contract.
- Phase 4: admin experience
  - Build dashboard, registrations, contacts, email campaigns, and event management.
  - Scaffold competition admin pages against the adapter contract with feature flags.
- Phase 5: integration and cutover
  - Use the existing Hoops Scorebook Spring Shootout event linkage now via `provider_event_slug`.
  - Expand `HoopsScorebookProvider` as additional API docs/endpoints become available.
  - Replace mocks, validate parity, configure redirects, and switch production traffic.

## Test Plan
- Unit tests:
  - Zod schemas, role checks, server-action validation, CMS mapping, email payload builders, competition adapter contract tests.
- Integration tests:
  - Supabase RLS for `owner` vs `admin` vs `comms`.
  - Registration creates team/contact/registration links correctly.
  - Additional contacts attach correctly to existing teams.
  - CMS content renders correctly by slug and event.
  - Email campaign creation persists metadata and sends through Resend sandbox/test mode.
- E2E tests:
  - Public registration happy path.
  - Existing-team additional-contact flow.
  - Admin login and logout.
  - Admin creates/edits content page.
  - Comms user can send campaign but cannot access protected admin settings.
  - Public schedule/results/standings pages render through the mock competition provider.
  - Feature-flagged competition admin pages show correct pending state before real API hookup.
- Acceptance criteria:
  - No public debug routes.
  - No committed secrets.
  - All admin mutations require auth and role checks.
  - Legacy feature coverage is met for all non-competition local features.
  - Competition screens are structurally complete and adapter-driven before live API integration.

## Assumptions and Defaults
- Competition operations remain external and will be integrated later using documentation from `hoopsscorebook.com`.
- The active Spring Shootout event already exists in Hoops Scorebook; the remaining integration work is primarily endpoint coverage and parity, not initial event creation.
- Hoops Scorebook now exposes a documented partner write API for Spring Shootout registrations; local Supabase is still needed as a mirrored read model until upstream read/write parity is complete for all workflows.
- The new app is single-repo, single-Next-app, not a Turborepo.
- The app is multi-event capable even if Spring Shootout is the only live event initially.
- No public payments are included in v1 because the legacy site does not currently implement them.
- Email/password is intentionally retained for staff auth even though SSO would be stronger.
- Local roles stop at `owner`, `admin`, and `comms`; scoring users belong in the sister system, not this app.
- Use Supabase-native access patterns rather than Drizzle or Prisma.
- Use `Node 24 LTS` in development and CI by default.
