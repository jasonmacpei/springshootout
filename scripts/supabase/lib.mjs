import fs from "node:fs";
import path from "node:path";

import { createClient } from "@supabase/supabase-js";

export function loadLocalEnv(envPath = path.resolve(process.cwd(), ".env.local")) {
  if (!fs.existsSync(envPath)) {
    throw new Error(`Missing env file at ${envPath}`);
  }

  return Object.fromEntries(
    fs
      .readFileSync(envPath, "utf8")
      .split(/\r?\n/)
      .map((line) => line.trim())
      .filter((line) => line && !line.startsWith("#"))
      .map((line) => {
        const index = line.indexOf("=");
        return index === -1 ? [line, ""] : [line.slice(0, index).trim(), line.slice(index + 1).trim()];
      }),
  );
}

export function createAdminClientFromLocalEnv() {
  const env = loadLocalEnv();
  const url = env.NEXT_PUBLIC_SUPABASE_URL;
  const serviceRoleKey = env.SUPABASE_SERVICE_ROLE_KEY;

  if (!url || !serviceRoleKey) {
    throw new Error("NEXT_PUBLIC_SUPABASE_URL and SUPABASE_SERVICE_ROLE_KEY are required in .env.local");
  }

  return {
    env,
    supabase: createClient(url, serviceRoleKey, {
      auth: {
        persistSession: false,
        autoRefreshToken: false,
      },
    }),
  };
}

export async function getEventBySlug(supabase, slug) {
  const { data, error } = await supabase
    .from("events")
    .select("id, slug, name, starts_on, ends_on, provider_event_slug, provider_visibility_mode, is_active")
    .eq("slug", slug)
    .maybeSingle();

  if (error) {
    throw error;
  }

  return data;
}
