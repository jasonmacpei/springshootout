import { describe, expect, it } from "vitest";

import {
  buildPlayoffSlotLabelMap,
  formatPublicMatchup,
  isRoundRobinCompleteForDivision,
  shouldHoldPlayoffAssignment,
} from "@/lib/competition/playoff-presentation";
import type {
  CompetitionPlayoffBracket,
  CompetitionScoreboardGame,
} from "@/lib/competition/schemas";

function game(overrides: Partial<CompetitionScoreboardGame>): CompetitionScoreboardGame {
  return {
    gameId: overrides.gameId ?? 1,
    gamePublicId: overrides.gamePublicId ?? `game-${overrides.gameId ?? 1}`,
    status: overrides.status ?? "scheduled",
    scheduledAt: overrides.scheduledAt ?? "2026-05-09T12:00:00.000Z",
    venue: "UPEI Field House",
    eventSlug: "spring-shootout-2026",
    eventName: "Spring Shootout 2026",
    divisionId: overrides.divisionId ?? 10,
    divisionName: overrides.divisionName ?? "U13 Girls",
    poolId: overrides.poolId ?? 1,
    poolName: overrides.poolName ?? "Pool A",
    stageId: overrides.stageId ?? 20,
    stageName: overrides.stageName ?? "Pool Play",
    stageType: overrides.stageType ?? "pool_play",
    stageScope: overrides.stageScope ?? "pool",
    homeTeamName: "Island Aces",
    awayTeamName: "Halifax Heat",
    ...overrides,
  };
}

const bracket: CompetitionPlayoffBracket = {
  stageName: "Crossover",
  stageType: "playoff_bracket",
  stageScope: "division",
  stageOrder: 2,
  eventSlug: "spring-shootout-2026",
  eventName: "Spring Shootout 2026",
  divisionId: 10,
  divisionName: "U13 Girls",
  bracketDefinition: [
    {
      order: 1,
      name: "Crossover 1",
      homeSource: "1A",
      awaySource: "2B",
    },
  ],
  games: [
    {
      gameId: 9,
      gamePublicId: "playoff-1",
      status: "scheduled",
      scheduledAt: "2026-05-10T18:00:00.000Z",
      venue: "UPEI Field House",
      homeTeamName: "Island Aces",
      homeScore: null,
      awayTeamName: "Halifax Heat",
      awayScore: null,
      stageName: "Crossover",
    },
  ],
};

describe("playoff presentation", () => {
  it("holds scheduled playoff assignments until the division round robin is complete", () => {
    const schedule = [
      game({ gameId: 1, status: "final" }),
      game({ gameId: 2, status: "scheduled", scheduledAt: "2026-05-09T15:00:00.000Z" }),
      game({
        gameId: 9,
        gamePublicId: "playoff-1",
        poolId: null,
        poolName: null,
        stageName: "Crossover",
        stageType: "playoff_bracket",
        stageScope: "division",
      }),
    ];
    const slotLabelMap = buildPlayoffSlotLabelMap([bracket]);
    const playoffGame = schedule[2];
    const holdAssignment = shouldHoldPlayoffAssignment({
      game: playoffGame,
      schedule,
      slotLabelMap,
    });

    expect(holdAssignment).toBe(true);
    expect(formatPublicMatchup({ game: playoffGame, holdAssignment, slotLabelMap })).toBe("1A vs 2B");
  });

  it("shows resolved playoff teams after every division round-robin game is complete", () => {
    const schedule = [
      game({ gameId: 1, status: "final" }),
      game({ gameId: 2, status: "final", scheduledAt: "2026-05-09T15:00:00.000Z" }),
      game({
        gameId: 9,
        gamePublicId: "playoff-1",
        poolId: null,
        poolName: null,
        stageName: "Crossover",
        stageType: "playoff_bracket",
        stageScope: "division",
      }),
    ];
    const slotLabelMap = buildPlayoffSlotLabelMap([bracket]);
    const playoffGame = schedule[2];
    const holdAssignment = shouldHoldPlayoffAssignment({
      game: playoffGame,
      schedule,
      slotLabelMap,
    });

    expect(isRoundRobinCompleteForDivision(schedule, playoffGame)).toBe(true);
    expect(holdAssignment).toBe(false);
    expect(formatPublicMatchup({ game: playoffGame, holdAssignment, slotLabelMap })).toBe(
      "Island Aces vs Halifax Heat",
    );
  });
});
