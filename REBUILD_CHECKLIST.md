# Spring Shootout Rebuild Checklist

This file tracks the remaining work against [PLAN.md](./PLAN.md). Items are ordered by shipping priority, not by implementation difficulty. Checkboxes are updated as work is completed.

## 1. Immediate Alignment

- [x] Audit the current codebase against `PLAN.md` and identify completed, partial, and missing work.
- [x] Add a working `/admin/email` landing page so the route structure matches the rebuild spec.
- [x] Fix stale automated coverage so tests reflect the current homepage and admin route structure.
- [x] Add a first-pass redirect map for legacy public brochure routes in `next.config.ts`.
- [x] Remove or replace any debug/dev-only routes before production cutover.
- [x] Remove hardcoded credentials from active legacy PHP config/auth/email helpers.
- [ ] Manually verify the new `/admin/email` hub and updated route/auth behavior.

## 2. Production Data Readiness

- [ ] Finish Supabase-backed content population so public pages no longer rely on fallback CMS data in normal operation.
- [ ] Verify event, venue, and registration data flows correctly from seeded/imported data.
- [ ] Review all admin empty states and fallback returns to ensure production failures are visible and actionable.

## 3. Competition Integration

- [x] Resolve competition reads through each event's configured `provider_event_slug`.
- [x] Expand `CompetitionProvider` to match the spec (`getSchedule`, `getPools`, `getPlayoffBrackets`, `getTeamsForEvent`, `submitScore`) or explicitly revise the spec.
- [x] Make competition admin no-data states and active provider linkage explicit for manual verification.
- [x] Replace scoreboard-backed interim schedule views with a dedicated schedule adapter response.
- [x] Replace inferred pools/playoff admin views with dedicated adapter-backed data.
- [x] Add feature-flagged or explicit pending states where real competition endpoints are not ready.
- [ ] Validate Hoops Scorebook adapter behavior against real API documentation once available.
- [x] Add or receive Hoops Scorebook write APIs for creating/upserting teams, registrations, and contacts so registration source-of-truth can move there.
- [x] Submit Spring Shootout registrations to Hoops Scorebook first, with local Supabase retained as a mirrored read model.
- [x] Add partner-backed support for post-registration additional contact updates, or document that those remain local-only for now.

## 4. Admin and Operations Quality

- [x] Review role model drift between `PLAN.md` and implementation (`owner`, `admin`, `comms`) and either align code or update the spec.
- [x] Confirm all admin mutations enforce the intended permissions model.
- [ ] Review email campaign send/retry flows with live Resend sandbox data.
- [x] Confirm audit logging coverage for all sensitive admin actions.

## 5. Testing and Cutover

- [ ] Add E2E coverage for registration, additional contacts, admin auth, CMS editing, and comms permissions.
- [ ] Add integration coverage for Supabase role/RLS behavior and data-linking flows.
- [ ] Add adapter contract tests for real and mock competition providers.
- [ ] Review legacy URL parity and add redirects where needed for cutover.
- [ ] Final production sweep: no debug routes, no secrets committed, no legacy-only dependencies required for launch.

## Notes

- Automated test execution is currently deferred. Manual verification will be used until you decide otherwise.
- CMS/event seed fallbacks are now disabled by default whenever Supabase is configured. Set `SPRING_SHOOTOUT_ENABLE_CONTENT_FALLBACKS=true` only for intentional preview or recovery scenarios.
