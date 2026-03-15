export const STAFF_ROLES = ["owner", "admin", "comms"] as const;

export type StaffRole = (typeof STAFF_ROLES)[number];

export function canManageSettings(role: StaffRole | null) {
  return role === "owner" || role === "admin";
}

export function canManageComms(role: StaffRole | null) {
  return role === "owner" || role === "admin" || role === "comms";
}

export function canDeleteRecords(role: StaffRole | null) {
  return role === "owner" || role === "admin";
}

export function canManageStaff(role: StaffRole | null) {
  return role === "owner" || role === "admin";
}

export function assignableStaffRoles(actorRole: StaffRole | null) {
  if (actorRole === "owner") {
    return STAFF_ROLES;
  }

  if (actorRole === "admin") {
    return STAFF_ROLES.filter((role) => role !== "owner");
  }

  return [] as readonly StaffRole[];
}
