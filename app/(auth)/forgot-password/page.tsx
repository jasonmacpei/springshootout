import Link from "next/link";

import { requestPasswordResetAction } from "@/actions/auth";
import { Button } from "@/components/ui/button";
import { Card, CardDescription, CardTitle } from "@/components/ui/card";

export default async function ForgotPasswordPage({
  searchParams,
}: {
  searchParams?: Promise<{ error?: string; sent?: string }>;
}) {
  const params = searchParams ? await searchParams : undefined;

  return (
    <div className="mx-auto flex min-h-screen max-w-3xl items-center px-6 py-16">
      <Card className="w-full bg-white/95">
        <CardTitle>Reset access</CardTitle>
        <CardDescription>Send a secure password recovery link to your staff email.</CardDescription>
        {params?.error ? (
          <p className="mt-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{params.error}</p>
        ) : null}
        {params?.sent ? (
          <p className="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            Reset instructions were sent if the address exists in Supabase Auth.
          </p>
        ) : null}
        <form action={requestPasswordResetAction} className="mt-6 space-y-4">
          <label className="grid gap-2 text-sm font-medium text-[var(--foreground)]">
            Email
            <input className="rounded-2xl border border-black/10 px-4 py-3" name="email" required type="email" />
          </label>
          <Button className="w-full" type="submit">
            Send reset link
          </Button>
        </form>
        <div className="mt-5 text-sm text-[var(--muted-foreground)]">
          <Link href="/login">Back to sign in</Link>
        </div>
      </Card>
    </div>
  );
}
