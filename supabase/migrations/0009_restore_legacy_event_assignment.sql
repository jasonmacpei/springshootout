do $$
declare
  current_event_id uuid;
  legacy_event_id uuid;
begin
  select id
  into current_event_id
  from public.events
  where slug = 'spring-shootout-2026';

  select id
  into legacy_event_id
  from public.events
  where slug = 'spring-shootout-2025';

  if current_event_id is null or legacy_event_id is null then
    raise notice 'Required Spring Shootout event rows are missing; no legacy event assignment was restored.';
    return;
  end if;

  update public.teams
  set event_id = legacy_event_id
  where event_id = current_event_id
    and legacy_team_id is not null;

  update public.registrations
  set event_id = legacy_event_id
  where event_id = current_event_id
    and legacy_registration_id is not null;
end $$;
