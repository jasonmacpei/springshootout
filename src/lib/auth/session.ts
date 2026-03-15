import { redirect } from "next/navigation";

import { createServerSupabaseClient } from "@/lib/db/server";
import type { StaffRole } from "@/lib/auth/roles";

export async function getOptionalSession() {
  const supabase = await createServerSupabaseClient();

  if (!supabase) {
    return {
      user: null,
      role: null as StaffRole | null,
      configured: false,
    };
  }

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return {
      user: null,
      role: null as StaffRole | null,
      configured: true,
    };
  }

  const { data: roles } = await supabase
    .from("staff_role_assignments")
    .select("role")
    .eq("user_id", user.id)
    .order("role");

  const resolvedRole =
    roles?.find((entry) => entry.role === "owner")?.role ??
    roles?.find((entry) => entry.role === "admin")?.role ??
    roles?.find((entry) => entry.role === "comms")?.role ??
    null;

  return {
    user,
    role: resolvedRole as StaffRole | null,
    configured: true,
  };
}

export async function requireStaffSession() {
  const session = await getOptionalSession();

  if (!session.user) {
    redirect("/login");
  }

  if (!session.role) {
    redirect("/login?error=Your account does not have a staff role.");
  }

  return session;
}
