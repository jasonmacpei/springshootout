create extension if not exists pgcrypto;

create type public.staff_role as enum ('admin', 'comms');
create type public.competition_provider as enum ('hoopsscorebook');
create type public.cms_page_status as enum ('draft', 'published');
create type public.registration_status as enum ('pending', 'approved', 'waitlisted', 'withdrawn');
create type public.email_campaign_status as enum ('draft', 'scheduled', 'sent', 'failed');

create or replace function public.set_updated_at()
returns trigger
language plpgsql
as $$
begin
  new.updated_at = timezone('utc', now());
  return new;
end;
$$;

create table if not exists public.staff_profiles (
  user_id uuid primary key references auth.users(id) on delete cascade,
  display_name text,
  created_at timestamptz not null default timezone('utc', now())
);

create table if not exists public.staff_role_assignments (
  id uuid primary key default gen_random_uuid(),
  user_id uuid not null references auth.users(id) on delete cascade,
  role public.staff_role not null,
  created_at timestamptz not null default timezone('utc', now()),
  unique (user_id, role)
);

create table if not exists public.events (
  id uuid primary key default gen_random_uuid(),
  slug text not null unique,
  name text not null,
  starts_on date,
  ends_on date,
  competition_provider public.competition_provider,
  provider_event_slug text,
  provider_event_public_id uuid,
  provider_visibility_mode text,
  is_active boolean not null default false,
  created_at timestamptz not null default timezone('utc', now()),
  updated_at timestamptz not null default timezone('utc', now())
);

create table if not exists public.event_settings (
  id uuid primary key default gen_random_uuid(),
  event_id uuid not null unique references public.events(id) on delete cascade,
  hero_eyebrow text,
  hero_title text,
  hero_description text,
  support_email text,
  support_phone text,
  registration_status text,
  created_at timestamptz not null default timezone('utc', now()),
  updated_at timestamptz not null default timezone('utc', now())
);

create table if not exists public.venues (
  id uuid primary key default gen_random_uuid(),
  event_id uuid not null references public.events(id) on delete cascade,
  name text not null,
  address text,
  map_url text,
  notes text,
  display_order integer not null default 0,
  created_at timestamptz not null default timezone('utc', now()),
  updated_at timestamptz not null default timezone('utc', now())
);

create table if not exists public.contact_roles (
  id uuid primary key default gen_random_uuid(),
  slug text not null unique,
  name text not null
);

create table if not exists public.contacts (
  id uuid primary key default gen_random_uuid(),
  first_name text,
  last_name text,
  full_name text not null,
  email text,
  phone text,
  notes text,
  created_at timestamptz not null default timezone('utc', now()),
  updated_at timestamptz not null default timezone('utc', now())
);

create table if not exists public.teams (
  id uuid primary key default gen_random_uuid(),
  event_id uuid not null references public.events(id) on delete cascade,
  name text not null,
  division_name text,
  class_name text,
  province text,
  created_at timestamptz not null default timezone('utc', now()),
  updated_at timestamptz not null default timezone('utc', now())
);

create table if not exists public.registrations (
  id uuid primary key default gen_random_uuid(),
  event_id uuid not null references public.events(id) on delete cascade,
  team_id uuid not null references public.teams(id) on delete cascade,
  primary_contact_id uuid references public.contacts(id) on delete set null,
  division_name text,
  class_name text,
  province text,
  note text,
  status public.registration_status not null default 'pending',
  created_at timestamptz not null default timezone('utc', now()),
  updated_at timestamptz not null default timezone('utc', now())
);

create table if not exists public.team_contacts (
  id uuid primary key default gen_random_uuid(),
  team_id uuid not null references public.teams(id) on delete cascade,
  contact_id uuid not null references public.contacts(id) on delete cascade,
  role_id uuid references public.contact_roles(id) on delete set null,
  created_at timestamptz not null default timezone('utc', now()),
  unique (team_id, contact_id)
);

create table if not exists public.cms_pages (
  id uuid primary key default gen_random_uuid(),
  event_id uuid references public.events(id) on delete cascade,
  slug text not null,
  title text not null,
  subtitle text,
  status public.cms_page_status not null default 'draft',
  seo_title text,
  seo_description text,
  created_at timestamptz not null default timezone('utc', now()),
  updated_at timestamptz not null default timezone('utc', now()),
  unique (event_id, slug)
);

create table if not exists public.cms_sections (
  id uuid primary key default gen_random_uuid(),
  page_id uuid not null references public.cms_pages(id) on delete cascade,
  kind text not null default 'rich_text',
  heading text,
  body text,
  sort_order integer not null default 0,
  created_at timestamptz not null default timezone('utc', now()),
  updated_at timestamptz not null default timezone('utc', now())
);

create table if not exists public.site_navigation (
  id uuid primary key default gen_random_uuid(),
  event_id uuid references public.events(id) on delete cascade,
  location text not null,
  label text not null,
  href text not null,
  sort_order integer not null default 0
);

create table if not exists public.email_templates (
  id uuid primary key default gen_random_uuid(),
  event_id uuid references public.events(id) on delete cascade,
  slug text not null,
  subject text not null,
  html_body text,
  text_body text,
  is_active boolean not null default true,
  created_at timestamptz not null default timezone('utc', now()),
  updated_at timestamptz not null default timezone('utc', now()),
  unique (event_id, slug)
);

create table if not exists public.email_campaigns (
  id uuid primary key default gen_random_uuid(),
  event_id uuid references public.events(id) on delete cascade,
  template_id uuid references public.email_templates(id) on delete set null,
  created_by uuid references auth.users(id) on delete set null,
  subject text not null,
  content_html text,
  content_text text,
  status public.email_campaign_status not null default 'draft',
  sent_at timestamptz,
  created_at timestamptz not null default timezone('utc', now()),
  updated_at timestamptz not null default timezone('utc', now())
);

create table if not exists public.email_deliveries (
  id uuid primary key default gen_random_uuid(),
  campaign_id uuid not null references public.email_campaigns(id) on delete cascade,
  recipient_email text not null,
  recipient_name text,
  provider_message_id text,
  delivery_status text,
  error_text text,
  created_at timestamptz not null default timezone('utc', now())
);

create table if not exists public.audit_logs (
  id uuid primary key default gen_random_uuid(),
  actor_user_id uuid references auth.users(id) on delete set null,
  entity_type text not null,
  entity_id uuid,
  action text not null,
  metadata jsonb not null default '{}'::jsonb,
  created_at timestamptz not null default timezone('utc', now())
);

create or replace function public.current_staff_role()
returns public.staff_role
language sql
stable
security definer
set search_path = public
as $$
  select role
  from public.staff_role_assignments
  where user_id = auth.uid()
  order by case when role = 'admin' then 0 else 1 end
  limit 1
$$;

alter table public.staff_profiles enable row level security;
alter table public.staff_role_assignments enable row level security;
alter table public.events enable row level security;
alter table public.event_settings enable row level security;
alter table public.venues enable row level security;
alter table public.contact_roles enable row level security;
alter table public.contacts enable row level security;
alter table public.teams enable row level security;
alter table public.registrations enable row level security;
alter table public.team_contacts enable row level security;
alter table public.cms_pages enable row level security;
alter table public.cms_sections enable row level security;
alter table public.site_navigation enable row level security;
alter table public.email_templates enable row level security;
alter table public.email_campaigns enable row level security;
alter table public.email_deliveries enable row level security;
alter table public.audit_logs enable row level security;

create policy "staff can read local data"
on public.events for select
to authenticated
using (public.current_staff_role() is not null);

create policy "admin manages events"
on public.events for all
to authenticated
using (public.current_staff_role() = 'admin')
with check (public.current_staff_role() = 'admin');

create policy "staff can read cms pages"
on public.cms_pages for select
to authenticated
using (public.current_staff_role() is not null);

create policy "comms can manage cms pages"
on public.cms_pages for all
to authenticated
using (public.current_staff_role() in ('admin', 'comms'))
with check (public.current_staff_role() in ('admin', 'comms'));

create policy "staff can manage contacts"
on public.contacts for all
to authenticated
using (public.current_staff_role() in ('admin', 'comms'))
with check (public.current_staff_role() in ('admin', 'comms'));

create policy "staff can manage registrations"
on public.registrations for all
to authenticated
using (public.current_staff_role() in ('admin', 'comms'))
with check (public.current_staff_role() in ('admin', 'comms'));

create policy "staff can manage campaigns"
on public.email_campaigns for all
to authenticated
using (public.current_staff_role() in ('admin', 'comms'))
with check (public.current_staff_role() in ('admin', 'comms'));

create policy "admins manage role assignments"
on public.staff_role_assignments for all
to authenticated
using (public.current_staff_role() = 'admin')
with check (public.current_staff_role() = 'admin');

create trigger set_events_updated_at
before update on public.events
for each row execute function public.set_updated_at();

create trigger set_event_settings_updated_at
before update on public.event_settings
for each row execute function public.set_updated_at();

create trigger set_venues_updated_at
before update on public.venues
for each row execute function public.set_updated_at();

create trigger set_contacts_updated_at
before update on public.contacts
for each row execute function public.set_updated_at();

create trigger set_teams_updated_at
before update on public.teams
for each row execute function public.set_updated_at();

create trigger set_registrations_updated_at
before update on public.registrations
for each row execute function public.set_updated_at();

create trigger set_cms_pages_updated_at
before update on public.cms_pages
for each row execute function public.set_updated_at();

create trigger set_cms_sections_updated_at
before update on public.cms_sections
for each row execute function public.set_updated_at();

create trigger set_email_templates_updated_at
before update on public.email_templates
for each row execute function public.set_updated_at();

create trigger set_email_campaigns_updated_at
before update on public.email_campaigns
for each row execute function public.set_updated_at();
