import { expect, test } from "@playwright/test";

test("home page renders poster-led headline", async ({ page }) => {
  await page.goto("/");
  await expect(page.getByRole("heading", { name: /Spring Shootout returns to Charlottetown on May 8-10, 2026\./i })).toBeVisible();
  await expect(page.getByAltText("Spring Shootout 2026 tournament poster")).toBeVisible();
  await expect(page.getByRole("link", { name: "Register your team" })).toHaveAttribute(
    "href",
    "https://www.atlantichoops.com/spring-shootout-2026/2026",
  );
});

test("admin email route requires staff auth", async ({ page }) => {
  await page.goto("/admin/email");
  await expect(page).toHaveURL(/\/login$/);
  await expect(page.getByRole("heading", { name: "Tournament staff sign-in." })).toBeVisible();
});
