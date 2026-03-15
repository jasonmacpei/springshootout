import { afterEach, describe, expect, it, vi } from "vitest";

const originalEnv = { ...process.env };

async function loadConfigModule() {
  vi.resetModules();
  return import("@/lib/config");
}

afterEach(() => {
  process.env = { ...originalEnv };
  vi.resetModules();
});

describe("content fallback policy", () => {
  it("uses fallback content when Supabase is not configured", async () => {
    delete process.env.NEXT_PUBLIC_SUPABASE_URL;
    delete process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY;
    delete process.env.SPRING_SHOOTOUT_ENABLE_CONTENT_FALLBACKS;

    const { shouldUseContentFallbacks } = await loadConfigModule();

    expect(shouldUseContentFallbacks()).toBe(true);
  });

  it("disables fallback content once Supabase env is configured", async () => {
    process.env.NEXT_PUBLIC_SUPABASE_URL = "https://example.supabase.co";
    process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY = "anon-key";
    delete process.env.SPRING_SHOOTOUT_ENABLE_CONTENT_FALLBACKS;

    const { shouldUseContentFallbacks } = await loadConfigModule();

    expect(shouldUseContentFallbacks()).toBe(false);
  });

  it("allows explicit fallback mode even with Supabase env configured", async () => {
    process.env.NEXT_PUBLIC_SUPABASE_URL = "https://example.supabase.co";
    process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY = "anon-key";
    process.env.SPRING_SHOOTOUT_ENABLE_CONTENT_FALLBACKS = "true";

    const { shouldUseContentFallbacks } = await loadConfigModule();

    expect(shouldUseContentFallbacks()).toBe(true);
  });
});
