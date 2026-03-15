import Link from "next/link";
import { redirect } from "next/navigation";

import { loginAction } from "@/actions/auth";
import { Button } from "@/components/ui/button";
import { Card, CardDescription, CardTitle } from "@/components/ui/card";
import { getOptionalSession } from "@/lib/auth/session";

export default async function LoginPage({
  searchParams,
}: {
  searchParams?: Promise<{ error?: string }>;
}) {
  const session = await getOptionalSession();
  const params = searchParams ? await searchParams : undefined;

  if (session.user && session.role) {
    redirect("/admin");
  }

  return (
    <div className="mx-auto flex min-h-screen max-w-5xl items-center px-6 py-16 lg:px-10">
      <div className="grid w-full gap-6 lg:grid-cols-[1.1fr_0.9fr]">
        <div>
          <p className="text-xs font-semibold uppercase tracking-[0.24em] text-[#b5c9e5]">Staff access</p>
          <h1 className="mt-6 text-5xl font-black uppercase tracking-[-0.04em]">Tournament staff sign-in.</h1>
          <p className="mt-5 max-w-xl text-base leading-7 text-[#b5c9e5]">
            Use your Supabase staff account to manage content, registrations, contacts, and email for Spring Shootout.
          </p>
        </div>
        <Card className="bg-white/95">
          <CardTitle>Sign in</CardTitle>
          <CardDescription>Access is limited to staff accounts with an assigned role in Supabase.</CardDescription>
          {params?.error ? (
            <p className="mt-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
              {params.error}
            </p>
          ) : null}
          <form action={loginAction} className="mt-6 space-y-4">
            <label className="grid gap-2 text-sm font-medium text-[var(--foreground)]">
              Email
              <input className="rounded-2xl border border-black/10 px-4 py-3" name="email" required type="email" />
            </label>
            <label className="grid gap-2 text-sm font-medium text-[var(--foreground)]">
              Password
              <input
                className="rounded-2xl border border-black/10 px-4 py-3"
                name="password"
                required
                type="password"
              />
            </label>
            <Button className="w-full" type="submit">
              Continue
            </Button>
          </form>
          <div className="mt-5 flex justify-between text-sm text-[var(--muted-foreground)]">
            <Link href="/forgot-password">Forgot password</Link>
            <Link href="/">Back to site</Link>
          </div>
        </Card>
      </div>
    </div>
  );
}
