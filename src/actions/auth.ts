"use server";

import { headers } from "next/headers";
import { redirect } from "next/navigation";

import { createServerSupabaseClient } from "@/lib/db/server";
import { forgotPasswordSchema, resetPasswordSchema } from "@/lib/validation/forms";

export async function loginAction(formData: FormData) {
  const supabase = await createServerSupabaseClient();

  if (!supabase) {
    const params = new URLSearchParams({ error: "Supabase is not configured for this environment." });
    redirect(`/login?${params.toString()}`);
  }

  const email = String(formData.get("email") ?? "").trim();
  const password = String(formData.get("password") ?? "");

  if (!email || !password) {
    const params = new URLSearchParams({ error: "Enter both your email and password." });
    redirect(`/login?${params.toString()}`);
  }

  const { data, error } = await supabase.auth.signInWithPassword({
    email,
    password,
  });

  if (error || !data.user) {
    const params = new URLSearchParams({ error: error?.message ?? "Unable to sign in." });
    redirect(`/login?${params.toString()}`);
  }

  const { data: roleRow } = await supabase
    .from("staff_role_assignments")
    .select("role")
    .eq("user_id", data.user.id)
    .limit(1)
    .maybeSingle();

  if (!roleRow?.role) {
    await supabase.auth.signOut();
    const params = new URLSearchParams({ error: "Your account does not have staff access yet." });
    redirect(`/login?${params.toString()}`);
  }

  redirect("/admin");
}

export async function logoutAction() {
  const supabase = await createServerSupabaseClient();

  if (supabase) {
    await supabase.auth.signOut();
  }

  redirect("/login");
}

function resolveOriginFromHeaders(headerList: Headers) {
  const explicitOrigin = headerList.get("origin");

  if (explicitOrigin) {
    return explicitOrigin;
  }

  const host = headerList.get("x-forwarded-host") ?? headerList.get("host");
  const proto = headerList.get("x-forwarded-proto") ?? "https";

  return host ? `${proto}://${host}` : null;
}

export async function requestPasswordResetAction(formData: FormData) {
  const supabase = await createServerSupabaseClient();

  if (!supabase) {
    redirect("/forgot-password?error=Supabase is not configured for this environment.");
  }

  const parsed = forgotPasswordSchema.safeParse({
    email: formData.get("email"),
  });

  if (!parsed.success) {
    redirect("/forgot-password?error=Enter a valid email address.");
  }

  const headerList = await headers();
  const origin = resolveOriginFromHeaders(headerList);

  if (!origin) {
    redirect("/forgot-password?error=Unable to determine the site origin for reset links.");
  }

  const { error } = await supabase.auth.resetPasswordForEmail(parsed.data.email, {
    redirectTo: `${origin}/auth/callback?next=/reset-password`,
  });

  if (error) {
    redirect(`/forgot-password?error=${encodeURIComponent(error.message)}`);
  }

  redirect("/forgot-password?sent=1");
}

export async function updatePasswordAction(formData: FormData) {
  const supabase = await createServerSupabaseClient();

  if (!supabase) {
    redirect("/reset-password?error=Supabase is not configured for this environment.");
  }

  const parsed = resetPasswordSchema.safeParse({
    password: formData.get("password"),
    confirmPassword: formData.get("confirmPassword"),
  });

  if (!parsed.success) {
    redirect("/reset-password?error=Enter a valid new password and confirm it.");
  }

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    redirect("/forgot-password?error=Open the reset link from your email before choosing a new password.");
  }

  const { error } = await supabase.auth.updateUser({
    password: parsed.data.password,
  });

  if (error) {
    redirect(`/reset-password?error=${encodeURIComponent(error.message)}`);
  }

  redirect("/login?error=Password updated. Please sign in with your new password.");
}
