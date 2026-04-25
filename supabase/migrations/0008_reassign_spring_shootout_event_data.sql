do $$
declare
  target_event_id uuid;
begin
  select id
  into target_event_id
  from public.events
  where slug = 'spring-shootout-2026';

  if target_event_id is null then
    raise notice 'spring-shootout-2026 event is missing; no event data was reassigned.';
    return;
  end if;

  update public.teams
  set event_id = target_event_id
  where event_id in (
    select id
    from public.events
    where slug in ('spring-shootout-2', 'spring-shootout-2025')
  );

  update public.registrations
  set event_id = target_event_id
  where event_id in (
    select id
    from public.events
    where slug in ('spring-shootout-2', 'spring-shootout-2025')
  );
end $$;
