import type { Metadata } from "next";

import { appConfig } from "@/lib/config";

import "./globals.css";

export const metadata: Metadata = {
  title: {
    default: appConfig.appName,
    template: `%s | ${appConfig.appName}`,
  },
  description: appConfig.appDescription,
};

export default function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) {
  return (
    <html lang="en">
      <body className="antialiased">{children}</body>
    </html>
  );
}
