insert into public.contact_roles (slug, name)
values
  ('head-coach', 'Head Coach'),
  ('assistant-coach', 'Assistant Coach'),
  ('manager', 'Manager'),
  ('primary-contact', 'Primary Contact')
on conflict (slug) do nothing;

insert into public.events (
  slug,
  name,
  starts_on,
  ends_on,
  competition_provider,
  provider_event_slug,
  provider_visibility_mode,
  is_active
)
values (
  'spring-shootout-2026',
  'Spring Shootout 2026',
  '2026-05-08',
  '2026-05-10',
  'hoopsscorebook',
  'spring-shootout-2026',
  'public_live',
  true
)
on conflict (slug) do nothing;
