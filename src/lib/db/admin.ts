import { createClient } from "@supabase/supabase-js";

import { hasServerSupabaseEnv } from "@/lib/config";

export function createAdminSupabaseClient() {
  if (!hasServerSupabaseEnv()) {
    return null;
  }

  return createClient(process.env.NEXT_PUBLIC_SUPABASE_URL!, process.env.SUPABASE_SERVICE_ROLE_KEY!, {
    auth: {
      persistSession: false,
      autoRefreshToken: false,
    },
  });
}
