# Hoops Scorebook Public API Notes

Reference file:

`/Users/jasonmacdonald/Documents/hoopsscorebook.com/docs/public_api_v1.md`

Mapped endpoints:

1. `/api/v1/events`
2. `/api/v1/scoreboard`
3. `/api/v1/schedule`
4. `/api/v1/results`
5. `/api/v1/standings`
6. `/api/v1/pools`
7. `/api/v1/playoffs`
8. `/api/v1/games/{publicId}`
9. `/api/v1/openapi.json`

Current product note:

The public API v1 does not yet expose a dedicated upcoming schedule endpoint. The Spring Shootout schedule page is scaffolded for that future capability and currently reuses the competition adapter boundary without claiming local ownership of competition data.

Current integration note:

Spring Shootout already has event data in Hoops Scorebook for the active tournament. The app should resolve competition reads through the event's configured `provider_event_slug`, rather than assuming the local app slug will always match the external provider slug.

Partner write coverage note:

- Initial team registrations are submitted upstream through the partner registration API.
- Post-registration additional-contact updates are not yet backed by a Hoops Scorebook partner write endpoint in this app.
- The current `/additional-contacts` flow writes to local Supabase only (`contacts` and `team_contacts`) so Spring Shootout staff can keep local admin records accurate while upstream coverage is pending.
