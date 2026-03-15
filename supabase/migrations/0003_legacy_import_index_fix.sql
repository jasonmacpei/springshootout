drop index if exists public.contact_roles_legacy_role_id_idx;
drop index if exists public.contacts_legacy_contact_id_idx;
drop index if exists public.teams_event_legacy_team_id_idx;
drop index if exists public.registrations_legacy_registration_id_idx;
drop index if exists public.team_contacts_legacy_team_contact_id_idx;
drop index if exists public.email_templates_legacy_welcome_email_id_idx;

create unique index if not exists contact_roles_legacy_role_id_idx
  on public.contact_roles (legacy_role_id);

create unique index if not exists contacts_legacy_contact_id_idx
  on public.contacts (legacy_contact_id);

create unique index if not exists teams_event_legacy_team_id_idx
  on public.teams (event_id, legacy_team_id);

create unique index if not exists registrations_legacy_registration_id_idx
  on public.registrations (legacy_registration_id);

create unique index if not exists team_contacts_legacy_team_contact_id_idx
  on public.team_contacts (legacy_team_contact_id);

create unique index if not exists email_templates_legacy_welcome_email_id_idx
  on public.email_templates (legacy_welcome_email_id);
