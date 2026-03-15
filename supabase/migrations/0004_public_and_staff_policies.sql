create policy "public can read events"
on public.events for select
to anon, authenticated
using (true);

create policy "public can read event settings"
on public.event_settings for select
to anon, authenticated
using (true);

create policy "public can read venues"
on public.venues for select
to anon, authenticated
using (true);

create policy "staff can manage event settings"
on public.event_settings for all
to authenticated
using (public.current_staff_role() in ('admin', 'comms'))
with check (public.current_staff_role() in ('admin', 'comms'));

create policy "staff can manage venues"
on public.venues for all
to authenticated
using (public.current_staff_role() in ('admin', 'comms'))
with check (public.current_staff_role() in ('admin', 'comms'));

create policy "public can read published cms pages"
on public.cms_pages for select
to anon, authenticated
using (status = 'published');

create policy "public can read published cms sections"
on public.cms_sections for select
to anon, authenticated
using (
  exists (
    select 1
    from public.cms_pages
    where public.cms_pages.id = page_id
      and public.cms_pages.status = 'published'
  )
);

create policy "staff can manage cms sections"
on public.cms_sections for all
to authenticated
using (public.current_staff_role() in ('admin', 'comms'))
with check (public.current_staff_role() in ('admin', 'comms'));

create policy "public can read navigation"
on public.site_navigation for select
to anon, authenticated
using (true);

create policy "staff can manage navigation"
on public.site_navigation for all
to authenticated
using (public.current_staff_role() in ('admin', 'comms'))
with check (public.current_staff_role() in ('admin', 'comms'));

create policy "public can read contact roles"
on public.contact_roles for select
to anon, authenticated
using (true);

create policy "staff can manage contact roles"
on public.contact_roles for all
to authenticated
using (public.current_staff_role() in ('admin', 'comms'))
with check (public.current_staff_role() in ('admin', 'comms'));

create policy "public can read teams"
on public.teams for select
to anon, authenticated
using (true);

create policy "staff can manage teams"
on public.teams for all
to authenticated
using (public.current_staff_role() in ('admin', 'comms'))
with check (public.current_staff_role() in ('admin', 'comms'));

create policy "staff can manage team contacts"
on public.team_contacts for all
to authenticated
using (public.current_staff_role() in ('admin', 'comms'))
with check (public.current_staff_role() in ('admin', 'comms'));

create policy "staff can manage email templates"
on public.email_templates for all
to authenticated
using (public.current_staff_role() in ('admin', 'comms'))
with check (public.current_staff_role() in ('admin', 'comms'));
