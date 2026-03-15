import type { User } from "@supabase/supabase-js";

import { STAFF_ROLES, type StaffRole } from "@/lib/auth/roles";
import { createAdminSupabaseClient } from "@/lib/db/admin";

export type StaffUserAdminRow = {
  id: string;
  email: string;
  displayName: string;
  effectiveRole: StaffRole | null;
  assignedRoles: StaffRole[];
  createdAt: string | null;
  lastSignInAt: string | null;
};

function resolveEffectiveRole(roles: StaffRole[]) {
  return (
    roles.find((role) => role === "owner") ??
    roles.find((role) => role === "admin") ??
    roles.find((role) => role === "comms") ??
    null
  );
}

function resolveDisplayName(user: User, profileName: string | null | undefined) {
  const metadataName =
    typeof user.user_metadata?.full_name === "string"
      ? user.user_metadata.full_name
      : typeof user.user_metadata?.name === "string"
        ? user.user_metadata.name
        : null;

  return profileName?.trim() || metadataName?.trim() || user.email || "Unnamed staff user";
}

export async function listStaffUsersForAdmin() {
  const supabase = createAdminSupabaseClient();

  if (!supabase) {
    return [] as StaffUserAdminRow[];
  }

  const [{ data: userData }, { data: profiles }, { data: roleRows }] = await Promise.all([
    supabase.auth.admin.listUsers({
      page: 1,
      perPage: 200,
    }),
    supabase.from("staff_profiles").select("user_id, display_name"),
    supabase.from("staff_role_assignments").select("user_id, role"),
  ]);

  const profileMap = new Map((profiles ?? []).map((profile) => [profile.user_id, profile.display_name]));
  const roleMap = new Map<string, StaffRole[]>();

  (roleRows ?? []).forEach((row) => {
    if (!STAFF_ROLES.includes(row.role)) {
      return;
    }

    const roles = roleMap.get(row.user_id) ?? [];
    roles.push(row.role);
    roleMap.set(row.user_id, roles);
  });

  const staffUsers = (userData?.users ?? [])
    .filter((user) => roleMap.has(user.id))
    .map((user) => {
      const assignedRoles = (roleMap.get(user.id) ?? []).sort((left, right) => STAFF_ROLES.indexOf(left) - STAFF_ROLES.indexOf(right));

      return {
        id: user.id,
        email: user.email ?? "No email",
        displayName: resolveDisplayName(user, profileMap.get(user.id)),
        effectiveRole: resolveEffectiveRole(assignedRoles),
        assignedRoles,
        createdAt: user.created_at ?? null,
        lastSignInAt: user.last_sign_in_at ?? null,
      } satisfies StaffUserAdminRow;
    })
    .sort((left, right) => left.displayName.localeCompare(right.displayName));

  return staffUsers;
}
