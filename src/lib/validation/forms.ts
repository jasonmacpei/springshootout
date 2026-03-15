import { z } from "zod";

export const registrationSchema = z.object({
  eventSlug: z.string().min(1),
  teamName: z.string().min(2),
  contactName: z.string().min(2),
  contactRole: z.string().min(2),
  province: z.string().min(2),
  division: z.string().min(2),
  className: z.string().min(1),
  email: z.email(),
  phone: z.string().min(7),
  note: z.string().optional(),
});

export const additionalContactSchema = z.object({
  teamId: z.uuid(),
  contactName: z.string().min(2),
  email: z.email(),
  phone: z.string().min(7),
  role: z.string().min(2),
});

export const loginSchema = z.object({
  email: z.email(),
  password: z.string().min(8),
});

export const forgotPasswordSchema = z.object({
  email: z.email(),
});

export const resetPasswordSchema = z
  .object({
    password: z.string().min(8),
    confirmPassword: z.string().min(8),
  })
  .refine((data) => data.password === data.confirmPassword, {
    path: ["confirmPassword"],
    message: "Passwords must match.",
  });

export const registrationAdminSchema = z.object({
  registrationId: z.uuid(),
  status: z.enum(["pending", "approved", "waitlisted", "withdrawn"]),
  primaryContactId: z.union([z.uuid(), z.literal(""), z.null()]).optional(),
  note: z.string().max(2000).optional(),
});

export const staffInviteSchema = z.object({
  email: z.email(),
  displayName: z.string().min(2).max(120),
  role: z.enum(["owner", "admin", "comms"]),
});

export const staffRoleUpdateSchema = z.object({
  userId: z.uuid(),
  role: z.enum(["owner", "admin", "comms"]),
});

export const staffAccessRevokeSchema = z.object({
  userId: z.uuid(),
});

export const teamCreateSchema = z.object({
  eventSlug: z.string().min(1),
  name: z.string().min(2).max(120),
  divisionName: z.string().max(120).optional(),
  className: z.string().max(120).optional(),
  province: z.string().max(120).optional(),
  primaryContactId: z.union([z.uuid(), z.literal(""), z.null()]).optional(),
  roleId: z.union([z.uuid(), z.literal(""), z.null()]).optional(),
});

export const contactCreateSchema = z.object({
  fullName: z.string().min(2).max(120),
  email: z.union([z.email(), z.literal(""), z.null()]).optional(),
  phone: z.string().max(40).optional(),
  notes: z.string().max(2000).optional(),
  teamId: z.union([z.uuid(), z.literal(""), z.null()]).optional(),
  roleId: z.union([z.uuid(), z.literal(""), z.null()]).optional(),
});

export const registrationCreateSchema = z.object({
  eventSlug: z.string().min(1),
  teamId: z.uuid(),
  primaryContactId: z.union([z.uuid(), z.literal(""), z.null()]).optional(),
  roleId: z.union([z.uuid(), z.literal(""), z.null()]).optional(),
  divisionName: z.string().max(120).optional(),
  className: z.string().max(120).optional(),
  province: z.string().max(120).optional(),
  note: z.string().max(2000).optional(),
  status: z.enum(["pending", "approved", "waitlisted", "withdrawn"]),
});
