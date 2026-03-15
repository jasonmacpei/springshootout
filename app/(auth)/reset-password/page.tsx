import Link from "next/link";

import { updatePasswordAction } from "@/actions/auth";
import { Button } from "@/components/ui/button";
import { Card, CardDescription, CardTitle } from "@/components/ui/card";
import { getOptionalSession } from "@/lib/auth/session";

export default async function ResetPasswordPage({
  searchParams,
}: {
  searchParams?: Promise<{ error?: string }>;
}) {
  const params = searchParams ? await searchParams : undefined;
  const session = await getOptionalSession();

  return (
    <div className="mx-auto flex min-h-screen max-w-3xl items-center px-6 py-16">
      <Card className="w-full bg-white/95">
        <CardTitle>Choose a new password</CardTitle>
        <CardDescription>
          {session.user
            ? "Set a new password for your staff account."
            : "Open the password reset link from your email first. That link signs you into a recovery session before you can update the password."}
        </CardDescription>
        {params?.error ? (
          <p className="mt-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{params.error}</p>
        ) : null}
        {session.user ? (
          <form action={updatePasswordAction} className="mt-6 space-y-4">
            <label className="grid gap-2 text-sm font-medium text-[var(--foreground)]">
              New password
              <input className="rounded-2xl border border-black/10 px-4 py-3" name="password" required type="password" />
            </label>
            <label className="grid gap-2 text-sm font-medium text-[var(--foreground)]">
              Confirm password
              <input className="rounded-2xl border border-black/10 px-4 py-3" name="confirmPassword" required type="password" />
            </label>
            <Button className="w-full" type="submit">
              Update password
            </Button>
          </form>
        ) : (
          <div className="mt-6 text-sm text-[var(--muted-foreground)]">
            <Link href="/forgot-password">Request a fresh reset email</Link>
          </div>
        )}
      </Card>
    </div>
  );
}
