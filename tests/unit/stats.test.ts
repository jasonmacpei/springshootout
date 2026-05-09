import { describe, expect, it } from "vitest";

import { buildTournamentStatsFeed } from "@/lib/competition/stats";
import type { CompetitionGameDetail, CompetitionScoreboardGame, CompetitionStanding } from "@/lib/competition/schemas";

const baseGame = {
  status: "final",
  scheduledAt: "2026-04-18T18:00:00.000Z",
  venue: "Chi-Wan Court 1",
  court: null,
  eventSlug: "spring-shootout-2026",
  eventName: "Spring Shootout 2026",
  poolId: 10,
  poolName: "Pool A",
  stageId: 1,
  stageName: "Pool Play",
  homeTeamName: "Island Aces",
  homeScore: 54,
  awayTeamName: "Moncton Storm",
  awayScore: 48,
  periodNumber: 4,
  clockSecondsRemaining: 0,
  clockDecisecondsRemaining: 0,
  isClockRunning: false,
  usesGameClock: true,
  clockSyncedAt: "2026-04-18T19:00:00.000Z",
};

function scoreboardGame({
  divisionId,
  divisionName,
  gameId,
  gamePublicId,
}: {
  divisionId: number;
  divisionName: string;
  gameId: number;
  gamePublicId: string;
}): CompetitionScoreboardGame {
  return {
    ...baseGame,
    gameId,
    gamePublicId,
    divisionId,
    divisionName,
  };
}

function boxScore({
  divisionId,
  divisionName,
  gameId,
  gamePublicId,
  points,
  fouls,
}: {
  divisionId: number;
  divisionName: string;
  gameId: number;
  gamePublicId: string;
  points: number;
  fouls: number;
}): CompetitionGameDetail {
  return {
    generatedAt: "2026-04-18T19:00:00.000Z",
    game: {
      ...baseGame,
      gameId,
      gamePublicId,
      divisionId,
      divisionName,
    },
    playerLinesByTeam: [
      {
        teamId: 1,
        teamName: "Island Aces",
        players: [
          {
            playerId: 101,
            playerName: "Jordan Lee",
            jerseyNumber: 12,
            points,
            fouls,
            secondsPlayed: 1200,
            plusMinus: 4,
          },
        ],
      },
    ],
    recentEvents: [],
  };
}

describe("buildTournamentStatsFeed", () => {
  it("aggregates player points and fouls by division", () => {
    const feed = buildTournamentStatsFeed({
      eventSlug: "spring-shootout-2026",
      generatedAt: "2026-04-18T20:00:00.000Z",
      scoreboardGames: [
        scoreboardGame({ divisionId: 1, divisionName: "U13 Boys", gameId: 1, gamePublicId: "game-1" }),
        scoreboardGame({ divisionId: 1, divisionName: "U13 Boys", gameId: 2, gamePublicId: "game-2" }),
      ],
      standings: [],
      boxScores: [
        boxScore({ divisionId: 1, divisionName: "U13 Boys", gameId: 1, gamePublicId: "game-1", points: 14, fouls: 2 }),
        boxScore({ divisionId: 1, divisionName: "U13 Boys", gameId: 2, gamePublicId: "game-2", points: 11, fouls: 3 }),
      ],
    });

    expect(feed.statGameCount).toBe(2);
    expect(feed.playerCount).toBe(1);
    expect(feed.divisions[0]?.pointsLeaders[0]).toMatchObject({
      playerName: "Jordan Lee",
      gamesPlayed: 2,
      points: 25,
      fouls: 5,
      pointsPerGame: 12.5,
      foulsPerGame: 2.5,
    });
  });

  it("keeps tournament divisions visible before player lines are available", () => {
    const standings: CompetitionStanding[] = [
      {
        eventSlug: "spring-shootout-2026",
        eventName: "Spring Shootout 2026",
        divisionId: 2,
        divisionName: "U15 Girls",
        poolId: 12,
        poolName: "Pool A",
        stageName: "Pool Play",
        teamPublicId: "team-1",
        teamName: "Halifax Heat",
        rank: 1,
        wins: 0,
        losses: 0,
        ties: 0,
        gamesPlayed: 0,
        pointsFor: 0,
        pointsAgainst: 0,
        pointDifferential: 0,
      },
    ];

    const feed = buildTournamentStatsFeed({
      eventSlug: "spring-shootout-2026",
      generatedAt: "2026-04-18T20:00:00.000Z",
      scoreboardGames: [],
      standings,
      boxScores: [],
    });

    expect(feed.divisions).toHaveLength(1);
    expect(feed.divisions[0]).toMatchObject({
      divisionName: "U15 Girls",
      playerCount: 0,
      statGameCount: 0,
      pointsLeaders: [],
      foulsLeaders: [],
    });
  });
});
