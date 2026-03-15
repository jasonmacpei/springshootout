alter table public.contact_roles
  add column if not exists legacy_role_id integer;

alter table public.contacts
  add column if not exists legacy_contact_id integer;

alter table public.teams
  add column if not exists legacy_team_id integer;

alter table public.registrations
  add column if not exists legacy_registration_id integer;

alter table public.team_contacts
  add column if not exists legacy_team_contact_id integer;

alter table public.email_templates
  add column if not exists legacy_welcome_email_id integer;

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

create schema if not exists legacy;

create table if not exists legacy.contact_roles (
  role_id integer primary key,
  role_name text not null
);

create table if not exists legacy.contacts (
  contact_id integer primary key,
  contact_name text,
  email_address text,
  phone_number text
);

create table if not exists legacy.teams (
  team_id integer primary key,
  team_name text not null
);

create table if not exists legacy.registrations (
  registration_id integer primary key,
  team_id integer references legacy.teams(team_id) on delete set null,
  contact_id integer references legacy.contacts(contact_id) on delete set null,
  division text,
  class text,
  province text,
  note text,
  year integer,
  paid boolean,
  status integer
);

create table if not exists legacy.team_contacts (
  team_contact_id integer primary key,
  team_id integer not null references legacy.teams(team_id) on delete cascade,
  contact_id integer not null references legacy.contacts(contact_id) on delete cascade,
  role_id integer references legacy.contact_roles(role_id) on delete set null,
  created_at timestamp,
  updated_at timestamp
);

create table if not exists legacy.pools (
  pool_id integer primary key,
  pool_name text not null
);

create table if not exists legacy.team_pools (
  team_id integer not null references legacy.teams(team_id) on delete cascade,
  pool_id integer not null references legacy.pools(pool_id) on delete cascade,
  primary key (team_id, pool_id)
);

create table if not exists legacy.schedule (
  game_id integer primary key,
  home_team_id integer references legacy.teams(team_id) on delete set null,
  away_team_id integer references legacy.teams(team_id) on delete set null,
  game_time time,
  game_date date,
  gym text,
  game_type text,
  home_uniform text,
  away_uniform text,
  placeholder_home text,
  placeholder_away text,
  game_category text
);

create table if not exists legacy.game_results (
  game_id integer not null references legacy.schedule(game_id) on delete cascade,
  team_id integer not null references legacy.teams(team_id) on delete cascade,
  points_for integer not null default 0,
  points_against integer not null default 0,
  win integer not null default 0,
  loss integer not null default 0,
  created_at timestamp,
  updated_at timestamp,
  primary key (game_id, team_id)
);

create table if not exists legacy.sent_emails (
  email_id integer primary key,
  sent_at timestamp,
  recipient text not null,
  subject text not null,
  body text not null
);

create table if not exists legacy.welcome_emails (
  id integer primary key,
  subject text not null,
  body text not null,
  created_at timestamp
);
