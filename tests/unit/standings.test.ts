import { describe, expect, it } from "vitest";

import { mergeStandings } from "@/lib/competition/standings";
import type { CompetitionPoolRecord, CompetitionScoreboardGame, CompetitionStanding } from "@/lib/competition/schemas";

const basePool: CompetitionPoolRecord = {
  poolId: 10,
  poolName: "Pool A",
  eventSlug: "spring-shootout-2026",
  eventName: "Spring Shootout 2026",
  divisionId: 1,
  divisionName: "U13 Girls",
  stageName: "Pool Play",
  teams: [
    {
      teamId: 1,
      teamPublicId: "team-a",
      teamName: "Island Aces",
      rank: 1,
      gamesPlayed: 0,
      wins: 0,
      losses: 0,
      ties: 0,
      pointsFor: 0,
      pointsAgainst: 0,
      pointDifferential: 0,
    },
    {
      teamId: 2,
      teamPublicId: "team-b",
      teamName: "Riverview Royals",
      rank: 2,
      gamesPlayed: 0,
      wins: 0,
      losses: 0,
      ties: 0,
      pointsFor: 0,
      pointsAgainst: 0,
      pointDifferential: 0,
    },
    {
      teamId: 3,
      teamPublicId: "team-c",
      teamName: "Summerside Spartans",
      rank: 3,
      gamesPlayed: 0,
      wins: 0,
      losses: 0,
      ties: 0,
      pointsFor: 0,
      pointsAgainst: 0,
      pointDifferential: 0,
    },
  ],
};

const resultStandings: CompetitionStanding[] = [
  {
    eventSlug: "spring-shootout-2026",
    eventName: "Spring Shootout 2026",
    divisionId: 1,
    divisionName: "U13 Girls",
    poolId: 10,
    poolName: "Pool A",
    stageName: "Pool Play",
    teamPublicId: "team-a",
    teamName: "Island Aces",
    rank: 1,
    gamesPlayed: 1,
    wins: 1,
    losses: 0,
    ties: 0,
    pointsFor: 30,
    pointsAgainst: 25,
    pointDifferential: 5,
  },
  {
    eventSlug: "spring-shootout-2026",
    eventName: "Spring Shootout 2026",
    divisionId: 1,
    divisionName: "U13 Girls",
    poolId: 10,
    poolName: "Pool A",
    stageName: "Pool Play",
    teamPublicId: "team-b",
    teamName: "Riverview Royals",
    rank: 2,
    gamesPlayed: 1,
    wins: 0,
    losses: 1,
    ties: 0,
    pointsFor: 25,
    pointsAgainst: 30,
    pointDifferential: -5,
  },
];

const scheduleOnlyGame: CompetitionScoreboardGame = {
  gameId: 12,
  gamePublicId: "game-12",
  status: "scheduled",
  scheduledAt: "2026-04-18T12:00:00.000Z",
  eventSlug: "spring-shootout-2026",
  eventName: "Spring Shootout 2026",
  divisionId: 2,
  divisionName: "U15 Boys",
  poolId: 20,
  poolName: "Pool B",
  stageName: "Pool Play",
  homeTeamPublicId: "team-d",
  homeTeamName: "Halifax Heat",
  awayTeamPublicId: "team-e",
  awayTeamName: "Moncton Storm",
};

describe("mergeStandings", () => {
  it("keeps zero-record teams from pools after standings rows are returned", () => {
    const rows = mergeStandings({
      pools: [basePool],
      schedule: [],
      standings: resultStandings,
    });

    expect(rows.map((row) => row.teamName)).toEqual(["Island Aces", "Riverview Royals", "Summerside Spartans"]);
    expect(rows[2]).toMatchObject({
      gamesPlayed: 0,
      wins: 0,
      losses: 0,
      ties: 0,
      rank: 3,
    });
  });

  it("adds schedule-only pools when the pools feed is incomplete", () => {
    const rows = mergeStandings({
      pools: [basePool],
      schedule: [scheduleOnlyGame],
      standings: resultStandings,
    });

    expect(rows.some((row) => row.divisionName === "U15 Boys" && row.poolName === "Pool B")).toBe(true);
    expect(rows.filter((row) => row.poolName === "Pool B").map((row) => row.teamName)).toEqual([
      "Halifax Heat",
      "Moncton Storm",
    ]);
  });
});
