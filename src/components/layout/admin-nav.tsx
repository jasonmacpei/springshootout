"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";

import { cn } from "@/lib/utils";

type AdminNavItem = {
  href: string;
  label: string;
};

export function AdminNav({ items }: { items: AdminNavItem[] }) {
  const pathname = usePathname() ?? "";

  return (
    <nav className="mt-8 space-y-2">
      {items.map((item) => {
        const isActive = pathname === item.href || (item.href !== "/admin" && pathname.startsWith(item.href));

        return (
          <Link
            className={cn(
              "block rounded-2xl px-4 py-3 text-sm font-semibold transition",
              isActive
                ? "bg-slate-100 text-slate-950 shadow-[0_12px_30px_rgba(15,23,42,0.22)]"
                : "text-slate-200 hover:bg-white/10 hover:text-white",
            )}
            href={item.href}
            key={item.href}
          >
            {item.label}
          </Link>
        );
      })}
    </nav>
  );
}
