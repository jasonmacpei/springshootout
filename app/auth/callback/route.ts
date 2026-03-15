import { NextResponse } from "next/server";
import { createServerClient } from "@supabase/ssr";

export async function GET(request: Request) {
  const url = new URL(request.url);
  const code = url.searchParams.get("code");
  const next = url.searchParams.get("next") ?? "/admin";
  const response = NextResponse.redirect(new URL(next, url.origin));

  if (!process.env.NEXT_PUBLIC_SUPABASE_URL || !process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY) {
    return NextResponse.redirect(new URL("/login?error=Supabase is not configured.", url.origin));
  }

  const supabase = createServerClient(process.env.NEXT_PUBLIC_SUPABASE_URL, process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY, {
    cookies: {
      getAll() {
        return request.headers.get("cookie")
          ?.split(";")
          .map((cookie) => cookie.trim())
          .filter(Boolean)
          .map((cookie) => {
            const index = cookie.indexOf("=");
            return {
              name: cookie.slice(0, index),
              value: decodeURIComponent(cookie.slice(index + 1)),
            };
          }) ?? [];
      },
      setAll(cookieList) {
        cookieList.forEach(({ name, value, options }) => {
          response.cookies.set(name, value, options);
        });
      },
    },
  });

  if (code) {
    const { error } = await supabase.auth.exchangeCodeForSession(code);

    if (error) {
      return NextResponse.redirect(new URL(`/login?error=${encodeURIComponent(error.message)}`, url.origin));
    }
  }

  return response;
}
