import { createBrowserClient } from "@supabase/ssr";

import { hasSupabaseEnv } from "@/lib/config";

export function createBrowserSupabaseClient() {
  if (!hasSupabaseEnv()) {
    return null;
  }

  return createBrowserClient(
    process.env.NEXT_PUBLIC_SUPABASE_URL!,
    process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY!,
  );
}
