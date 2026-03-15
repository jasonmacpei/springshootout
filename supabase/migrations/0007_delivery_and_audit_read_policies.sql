create policy "staff can read email deliveries"
on public.email_deliveries for select
to authenticated
using (public.current_staff_role() in ('owner', 'admin', 'comms'));

create policy "admins can read audit logs"
on public.audit_logs for select
to authenticated
using (public.current_staff_role() in ('owner', 'admin'));
