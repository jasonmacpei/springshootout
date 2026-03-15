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
  order by case
    when role = 'owner' then 0
    when role = 'admin' then 1
    else 2
  end
  limit 1
$$;

drop policy if exists "admin manages events" on public.events;
create policy "admin manages events"
on public.events for all
to authenticated
using (public.current_staff_role() in ('owner', 'admin'))
with check (public.current_staff_role() in ('owner', 'admin'));

drop policy if exists "comms can manage cms pages" on public.cms_pages;
create policy "comms can manage cms pages"
on public.cms_pages for all
to authenticated
using (public.current_staff_role() in ('owner', 'admin', 'comms'))
with check (public.current_staff_role() in ('owner', 'admin', 'comms'));

drop policy if exists "staff can manage contacts" on public.contacts;
create policy "staff can manage contacts"
on public.contacts for all
to authenticated
using (public.current_staff_role() in ('owner', 'admin', 'comms'))
with check (public.current_staff_role() in ('owner', 'admin', 'comms'));

drop policy if exists "staff can manage registrations" on public.registrations;
create policy "staff can manage registrations"
on public.registrations for all
to authenticated
using (public.current_staff_role() in ('owner', 'admin', 'comms'))
with check (public.current_staff_role() in ('owner', 'admin', 'comms'));

drop policy if exists "staff can manage campaigns" on public.email_campaigns;
create policy "staff can manage campaigns"
on public.email_campaigns for all
to authenticated
using (public.current_staff_role() in ('owner', 'admin', 'comms'))
with check (public.current_staff_role() in ('owner', 'admin', 'comms'));

drop policy if exists "admins manage role assignments" on public.staff_role_assignments;
create policy "admins manage role assignments"
on public.staff_role_assignments for all
to authenticated
using (public.current_staff_role() in ('owner', 'admin'))
with check (public.current_staff_role() in ('owner', 'admin'));

drop policy if exists "staff can manage event settings" on public.event_settings;
create policy "staff can manage event settings"
on public.event_settings for all
to authenticated
using (public.current_staff_role() in ('owner', 'admin'))
with check (public.current_staff_role() in ('owner', 'admin'));

drop policy if exists "staff can manage venues" on public.venues;
create policy "staff can manage venues"
on public.venues for all
to authenticated
using (public.current_staff_role() in ('owner', 'admin'))
with check (public.current_staff_role() in ('owner', 'admin'));

drop policy if exists "staff can manage cms sections" on public.cms_sections;
create policy "staff can manage cms sections"
on public.cms_sections for all
to authenticated
using (public.current_staff_role() in ('owner', 'admin', 'comms'))
with check (public.current_staff_role() in ('owner', 'admin', 'comms'));

drop policy if exists "staff can manage navigation" on public.site_navigation;
create policy "staff can manage navigation"
on public.site_navigation for all
to authenticated
using (public.current_staff_role() in ('owner', 'admin', 'comms'))
with check (public.current_staff_role() in ('owner', 'admin', 'comms'));

drop policy if exists "staff can manage contact roles" on public.contact_roles;
create policy "staff can manage contact roles"
on public.contact_roles for all
to authenticated
using (public.current_staff_role() in ('owner', 'admin', 'comms'))
with check (public.current_staff_role() in ('owner', 'admin', 'comms'));

drop policy if exists "staff can manage teams" on public.teams;
create policy "staff can manage teams"
on public.teams for all
to authenticated
using (public.current_staff_role() in ('owner', 'admin', 'comms'))
with check (public.current_staff_role() in ('owner', 'admin', 'comms'));

drop policy if exists "staff can manage team contacts" on public.team_contacts;
create policy "staff can manage team contacts"
on public.team_contacts for all
to authenticated
using (public.current_staff_role() in ('owner', 'admin', 'comms'))
with check (public.current_staff_role() in ('owner', 'admin', 'comms'));

drop policy if exists "staff can manage email templates" on public.email_templates;
create policy "staff can manage email templates"
on public.email_templates for all
to authenticated
using (public.current_staff_role() in ('owner', 'admin', 'comms'))
with check (public.current_staff_role() in ('owner', 'admin', 'comms'));
