export const appConfig = {
  appName: "Spring Shootout",
  appDescription:
    "A modern tournament site for registration, content, communication, and live competition storytelling.",
  supportEmail: process.env.NEXT_PUBLIC_SUPPORT_EMAIL ?? "hello@springshootout.ca",
  supportPhone: process.env.NEXT_PUBLIC_SUPPORT_PHONE ?? "902-626-1936",
  registrationUrl:
    process.env.NEXT_PUBLIC_REGISTRATION_URL ?? "https://www.atlantichoops.com/spring-shootout-2/2026",
  hoopsApiBase:
    process.env.HOOPS_SCOREBOOK_API_BASE?.replace(/\/$/, "") ?? "https://hoopsscorebook.com",
  defaultEventSlug: process.env.NEXT_PUBLIC_DEFAULT_EVENT_SLUG ?? "spring-shootout-2026",
  partnerKey: process.env.HOOPS_SCOREBOOK_PARTNER_KEY ?? null,
  partnerRegistrationSource: process.env.HOOPS_SCOREBOOK_PARTNER_SOURCE ?? "spring-shootout",
  resendFrom:
    process.env.RESEND_FROM_EMAIL ?? "Spring Shootout <updates@springshootout.ca>",
};

export function hasSupabaseEnv() {
  return Boolean(process.env.NEXT_PUBLIC_SUPABASE_URL && process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY);
}

export function hasServerSupabaseEnv() {
  return Boolean(
    process.env.NEXT_PUBLIC_SUPABASE_URL &&
      process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY &&
      process.env.SUPABASE_SERVICE_ROLE_KEY,
  );
}

export function shouldUseContentFallbacks() {
  if (!hasSupabaseEnv()) {
    return true;
  }

  return process.env.SPRING_SHOOTOUT_ENABLE_CONTENT_FALLBACKS === "true";
}
