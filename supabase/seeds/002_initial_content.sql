with event_row as (
  select id
  from public.events
  where slug = 'spring-shootout-2026'
)
insert into public.event_settings (
  event_id,
  hero_eyebrow,
  hero_title,
  hero_description,
  support_email,
  support_phone,
  registration_status
)
select
  event_row.id,
  '3rd annual girls tournament',
  'Spring Shootout returns to Charlottetown on May 8-10, 2026.',
  'Girls divisions only, U12 and U13. Three-game guarantee, Basketball PEI sanctioned referees, medals for winners, all-star shirts, and negotiated team hotel rates.',
  'jasonmacpei@hotmail.com',
  '902-626-1936',
  'Open now. Entry fee is $495 per team.'
from event_row
on conflict (event_id) do update
set
  hero_eyebrow = excluded.hero_eyebrow,
  hero_title = excluded.hero_title,
  hero_description = excluded.hero_description,
  support_email = excluded.support_email,
  support_phone = excluded.support_phone,
  registration_status = excluded.registration_status;

with event_row as (
  select id
  from public.events
  where slug = 'spring-shootout-2026'
),
upserted as (
  insert into public.cms_pages (event_id, slug, title, subtitle, status)
  select event_row.id, 'rules', 'Tournament Rules', 'Game administration, tie breakers, and division-specific notes for Spring Shootout weekend.', 'published'
  from event_row
  on conflict (event_id, slug) do update
  set title = excluded.title, subtitle = excluded.subtitle, status = excluded.status
  returning id
)
delete from public.cms_sections
where page_id in (select id from upserted);

with page_row as (
  select cp.id
  from public.cms_pages cp
  join public.events e on e.id = cp.event_id
  where e.slug = 'spring-shootout-2026' and cp.slug = 'rules'
)
insert into public.cms_sections (page_id, heading, body, sort_order)
select page_row.id, section.heading, section.body, section.sort_order
from page_row
cross join (
  values
    ('Tournament format', 'Games are capped at a 20-point spread for standings purposes. A 60-30 result is recorded as 50-30. Division finish is driven first by wins, then by the published tie-break sequence.', 0),
    ('Standings tie breakers', 'Tie breakers move in this order: head-to-head record, plus-minus, points against, then a final draw if teams are still level.', 1),
    ('Core game rules', 'Games follow FIBA rules. All games are 5-on-5 on a 10-foot hoop, played in four 8-minute quarters with a 2-minute halftime. Teams receive two timeouts per half and one additional timeout in overtime.', 2),
    ('Defense and sportsmanship', 'Man-to-man only. No zone defense and no off-ball double teams. Help-side defense is allowed. Full-court man-to-man is allowed until the margin reaches 20 points, then teams must defend in the half court only.', 3),
    ('Division notes', 'U12 uses a 27.5-inch ball and does not allow ball screens. U13 uses a 28.5-inch ball. Event staff may issue a final coaches bulletin before opening tip if facility or officiating conditions require a clarification.', 4)
) as section(heading, body, sort_order);

with event_row as (
  select id
  from public.events
  where slug = 'spring-shootout-2026'
),
upserted as (
  insert into public.cms_pages (event_id, slug, title, subtitle, status)
  select event_row.id, 'gyms', 'Gyms & Venues', 'Court locations for players, coaches, and travelling families across Charlottetown and Stratford.', 'published'
  from event_row
  on conflict (event_id, slug) do update
  set title = excluded.title, subtitle = excluded.subtitle, status = excluded.status
  returning id
)
delete from public.cms_sections
where page_id in (select id from upserted);

with page_row as (
  select cp.id
  from public.cms_pages cp
  join public.events e on e.id = cp.event_id
  where e.slug = 'spring-shootout-2026' and cp.slug = 'gyms'
)
insert into public.cms_sections (page_id, heading, body, sort_order)
select page_row.id, section.heading, section.body, section.sort_order
from page_row
cross join (
  values
    ('Weekend logistics', 'Most venues are within a short drive of the tournament hotels. Check the game card carefully before leaving because divisions will move between city, Stratford, and UPEI sites through the weekend.', 0),
    ('Arrival notes', 'Plan to arrive at least 30 minutes before your first game. PEI spring weather can slow bridge and city traffic, especially on Saturday morning.', 1)
) as section(heading, body, sort_order);

with event_row as (
  select id
  from public.events
  where slug = 'spring-shootout-2026'
),
upserted as (
  insert into public.cms_pages (event_id, slug, title, subtitle, status)
  select event_row.id, 'hotels', 'Hotels', 'Tournament-rate lodging guidance for teams travelling into Charlottetown.', 'published'
  from event_row
  on conflict (event_id, slug) do update
  set title = excluded.title, subtitle = excluded.subtitle, status = excluded.status
  returning id
)
delete from public.cms_sections
where page_id in (select id from upserted);

with page_row as (
  select cp.id
  from public.cms_pages cp
  join public.events e on e.id = cp.event_id
  where e.slug = 'spring-shootout-2026' and cp.slug = 'hotels'
)
insert into public.cms_sections (page_id, heading, body, sort_order)
select page_row.id, section.heading, section.body, section.sort_order
from page_row
cross join (
  values
    ('Hampton Inn & Suites', '300 Capital Drive, Charlottetown, PEI C1E 2N1. Call (902) 368-3551 and reference the Spring Shootout team block. Tournament rate: $189 plus tax per night.', 0),
    ('Holiday Inn Express & Suites', '200 Capital Drive, Charlottetown, PEI C1E 2E8. Call (902) 892-1201 and reference the Spring Shootout team block. Tournament rate: $189 plus tax per night.', 1),
    ('Booking process', 'Coaches or managers should reserve the team block first, then families can book from that block individually. Wait for the final booking note from the tournament if inventory or deadlines change.', 2)
) as section(heading, body, sort_order);

with event_row as (
  select id
  from public.events
  where slug = 'spring-shootout-2026'
),
upserted as (
  insert into public.cms_pages (event_id, slug, title, subtitle, status)
  select event_row.id, 'contact', 'Contact', 'Reach the people running Spring Shootout and the local basketball partners supporting the weekend.', 'published'
  from event_row
  on conflict (event_id, slug) do update
  set title = excluded.title, subtitle = excluded.subtitle, status = excluded.status
  returning id
)
delete from public.cms_sections
where page_id in (select id from upserted);

with page_row as (
  select cp.id
  from public.cms_pages cp
  join public.events e on e.id = cp.event_id
  where e.slug = 'spring-shootout-2026' and cp.slug = 'contact'
)
insert into public.cms_sections (page_id, heading, body, sort_order)
select page_row.id, section.heading, section.body, section.sort_order
from page_row
cross join (
  values
    ('Tournament organizer', 'Jason MacDonald is the primary tournament contact for registrations, venue logistics, and weekend issues. Phone: 902-626-1936. Email: jasonmacpei@hotmail.com.', 0),
    ('Basketball PEI', 'Basketball PEI supports officiating and the broader event partnership. Contact Josh Whitty at josh@basketballpei.ca for federation-related questions.', 1)
) as section(heading, body, sort_order);

with event_row as (
  select id
  from public.events
  where slug = 'spring-shootout-2026'
)
delete from public.venues
where event_id = (select id from event_row);

with event_row as (
  select id
  from public.events
  where slug = 'spring-shootout-2026'
)
insert into public.venues (event_id, name, address, map_url, notes, display_order)
select
  event_row.id,
  venue.name,
  venue.address,
  venue.map_url,
  venue.notes,
  venue.display_order
from event_row
cross join (
  values
    ('UPEI Field House', '550 University Ave, Charlottetown, PE C1A 4P3', 'https://maps.app.goo.gl/S3rqe51KgiNECmTQ9', 'Championship-site venue. Use the Chi-Wan Young Sports Centre entrance.', 0),
    ('Colonel Grey High School', '175 Spring Park Rd, Charlottetown, PE C1A 3Y8', 'https://maps.app.goo.gl/oqBFBLt4oDEoD7DQ9', 'Central Charlottetown court with easy family parking.', 1),
    ('Glen Stewart Primary', '34 Glen Stewart Dr, Stratford, PE C1B 0J9', 'https://maps.app.goo.gl/CKEwj1judPZCqvzM7', 'Stratford-side venue used throughout pool play weekend.', 2),
    ('Stonepark Intermediate', '50 Pope Ave, Charlottetown, PE C1A 7P5', 'https://maps.app.goo.gl/CRHCKp9vhRsvzofz8', 'One of the busiest event gyms; allow extra time for warm-up traffic.', 3),
    ('Birchwood Intermediate', '49 Longworth Ave, Charlottetown, PE C1A 5A6', 'https://maps.app.goo.gl/YiRAHQYaB5Jq4Bx77', 'Additional city venue used to keep game turnarounds tight.', 4),
    ('Charlottetown Rural High School', '100 Raiders Rd, Charlottetown, PE C1E 1K6', 'https://maps.app.goo.gl/FBU6gUKxebatbYRL8', 'Large gym footprint with straightforward highway access.', 5),
    ('Donagh Regional School', '928 Bethel Rd, Donagh, PE C1B 3J7', 'https://maps.app.goo.gl/XXC5K9TSr8Tip9L97', 'Used when division volume requires overflow courts.', 6),
    ('Stratford Town Hall', '234 Shakespeare Dr, Stratford, PE C1B 2V8', 'https://maps.app.goo.gl/tHqy8ZR4uV4Hwsc68', 'Short drive from the hotels and a reliable opening-night site.', 7)
) as venue(name, address, map_url, notes, display_order);

with event_row as (
  select id
  from public.events
  where slug = 'spring-shootout-2026'
)
delete from public.site_navigation
where event_id = (select id from event_row);

with event_row as (
  select id
  from public.events
  where slug = 'spring-shootout-2026'
)
insert into public.site_navigation (event_id, location, label, href, sort_order)
select
  event_row.id,
  nav.location,
  nav.label,
  nav.href,
  nav.sort_order
from event_row
cross join (
  values
    ('header', 'Home', '/', 0),
    ('header', 'Register', '/register', 1),
    ('header', 'Schedule', '/schedule', 2),
    ('header', 'Results', '/results', 3),
    ('header', 'Standings', '/standings', 4),
    ('header', 'Rules', '/rules', 5),
    ('header', 'Gyms', '/gyms', 6),
    ('header', 'Hotels', '/hotels', 7),
    ('header', 'Contact', '/contact', 8)
) as nav(location, label, href, sort_order);
