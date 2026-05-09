import { describe, expect, it } from "vitest";

import { buildSchedulePdf } from "@/lib/competition/schedule-pdf";
import type { CompetitionScoreboardGame } from "@/lib/competition/schemas";

function game(overrides: Partial<CompetitionScoreboardGame>): CompetitionScoreboardGame {
  return {
    gameId: overrides.gameId ?? 1,
    gamePublicId: overrides.gamePublicId ?? `mock-game-${overrides.gameId ?? 1}`,
    status: "scheduled",
    scheduledAt: overrides.scheduledAt ?? "2026-05-08T18:00:00.000Z",
    venue: "Chi-Wan Young Sports Centre",
    court: "Court 1",
    eventSlug: "spring-shootout-2026",
    eventName: "Spring Shootout 2026",
    divisionId: overrides.divisionId ?? 1,
    divisionName: overrides.divisionName ?? "U15 Girls",
    poolId: null,
    poolName: "Pool A",
    stageId: null,
    stageName: "Pool Play",
    homeTeamName: "Island Aces",
    homeSlotLabel: null,
    awayTeamName: "Halifax Heat",
    awaySlotLabel: null,
    ...overrides,
  };
}

describe("buildSchedulePdf", () => {
  it("creates one PDF page per division", () => {
    const pdf = buildSchedulePdf(
      [
        game({ gameId: 1, divisionId: 10, divisionName: "U15 Girls" }),
        game({ gameId: 2, divisionId: 20, divisionName: "U17 Boys" }),
      ],
      { generatedAt: new Date("2026-05-08T12:00:00.000Z") },
    );
    const body = pdf.toString("utf8");

    expect(body.startsWith("%PDF-1.4")).toBe(true);
    expect(body).toContain("/Count 2");
    expect(body).toContain("U15 Girls");
    expect(body).toContain("U17 Boys");
  });
});
