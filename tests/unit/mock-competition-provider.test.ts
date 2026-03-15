import { describe, expect, it } from "vitest";

import { mockCompetitionProvider } from "@/lib/competition/adapters/mock";

describe("mockCompetitionProvider", () => {
  it("returns a scoreboard feed", async () => {
    const games = await mockCompetitionProvider.getScoreboard({
      event: "spring-shootout-2026",
      status: "all",
      limit: 4,
    });

    expect(games.length).toBeGreaterThan(0);
    expect(games[0]?.eventSlug).toBe("spring-shootout-2026");
  });

  it("returns standings grouped around the event", async () => {
    const standings = await mockCompetitionProvider.getStandings({
      event: "spring-shootout-2026",
      limit: 10,
    });

    expect(standings[0]?.poolName).toBe("Pool A");
    expect(standings[0]?.rank).toBe(1);
  });
});
