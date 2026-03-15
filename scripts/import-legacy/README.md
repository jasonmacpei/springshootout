# Legacy Import

This directory contains the import tooling for the legacy InMotion PostgreSQL database.

## Current workflow

1. Place the legacy dump at `supabase/spring-shootout-legacy.dump`
2. Apply `supabase/migrations/0002_legacy_import_support.sql`
3. Generate the import SQL:

```bash
node scripts/import-legacy/generate-import-sql.mjs
```

4. Review the generated file at `scripts/import-legacy/output/legacy-import.sql`
5. Apply that SQL to Supabase

## What gets imported

- Full legacy raw tables into the private `legacy` schema
- Normalized public records into the new app schema:
  - `events` for legacy tournament years
  - `contact_roles`
  - `contacts`
  - `teams`
  - `registrations`
  - `team_contacts`
  - `email_templates` from `welcome_emails`

## What stays archive-only for now

- `schedule`
- `game_results`
- `pools`
- `team_pools`
- `sent_emails`

Those tables are preserved in `legacy.*` so the data is not lost, but they are not yet projected into the new app model because live competition data now belongs to Hoops Scorebook.
