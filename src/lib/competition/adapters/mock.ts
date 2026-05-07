import { revalidateTag } from "next/cache";

import type { CompetitionProvider } from "@/lib/competition/service";

export const mockCompetitionProvider: CompetitionProvider = {
  async listEvents() {
    return [
      {
        slug: "spring-shootout-2026",
        name: "Spring Shootout 2026",
        publicId: "mock-event-public-id",
        visibility: "public_live",
        status: "published",
        startsOn: "2026-04-17",
        endsOn: "2026-04-19",
      },
    ];
  },

  async getScoreboard() {
    return [
      {
        gameId: 18,
        gamePublicId: "mock-game-18",
        status: "in_progress",
        scheduledAt: "2026-04-18T18:00:00.000Z",
        venue: "Chi-Wan Court 1",
        eventSlug: "spring-shootout-2026",
        eventName: "Spring Shootout 2026",
        homeTeamName: "Island Aces",
        homeScore: 52,
        awayTeamName: "Moncton Storm",
        awayScore: 47,
        periodNumber: 3,
        clockSecondsRemaining: 142,
        clockDecisecondsRemaining: 1420,
        isClockRunning: true,
        usesGameClock: true,
        clockSyncedAt: "2026-04-18T18:44:00.000Z",
      },
      {
        gameId: 19,
        gamePublicId: "mock-game-19",
        status: "final",
        scheduledAt: "2026-04-18T19:30:00.000Z",
        venue: "Chi-Wan Court 2",
        eventSlug: "spring-shootout-2026",
        eventName: "Spring Shootout 2026",
        homeTeamName: "Halifax Heat",
        homeScore: 71,
        awayTeamName: "Charlottetown Celtics",
        awayScore: 64,
        periodNumber: 4,
        clockSecondsRemaining: 0,
        clockDecisecondsRemaining: 0,
        isClockRunning: false,
        usesGameClock: true,
        clockSyncedAt: "2026-04-18T19:18:00.000Z",
      },
    ];
  },

  async getSchedule(filter) {
    return this.getScoreboard({
      ...filter,
      status: filter.status ?? "all",
    });
  },

  async getResults() {
    return [
      {
        gamePublicId: "mock-game-19",
        gameStatus: "final",
        resultWorkflowStatus: "approved",
        scheduledAt: "2026-04-18T19:30:00.000Z",
        venue: "Chi-Wan Court 2",
        eventSlug: "spring-shootout-2026",
        eventName: "Spring Shootout 2026",
        divisionId: 3,
        divisionName: "U15 Boys",
        poolId: 8,
        poolName: "Pool A",
        stageName: "Pool Play",
        teamName: "Halifax Heat",
        opponentTeamName: "Charlottetown Celtics",
        result: "win",
        score: 71,
        opponentScore: 64,
      },
    ];
  },

  async getStandings() {
    return [
      {
        eventSlug: "spring-shootout-2026",
        eventName: "Spring Shootout 2026",
        divisionId: 3,
        divisionName: "U15 Boys",
        poolId: 8,
        poolName: "Pool A",
        stageName: "Pool Play",
        teamPublicId: "mock-team-1",
        teamName: "Halifax Heat",
        rank: 1,
        wins: 3,
        losses: 0,
        ties: 0,
        gamesPlayed: 3,
        pointsFor: 198,
        pointsAgainst: 162,
        pointDifferential: 36,
      },
      {
        eventSlug: "spring-shootout-2026",
        eventName: "Spring Shootout 2026",
        divisionId: 3,
        divisionName: "U15 Boys",
        poolId: 8,
        poolName: "Pool A",
        stageName: "Pool Play",
        teamPublicId: "mock-team-2",
        teamName: "Charlottetown Celtics",
        rank: 2,
        wins: 2,
        losses: 1,
        ties: 0,
        gamesPlayed: 3,
        pointsFor: 181,
        pointsAgainst: 171,
        pointDifferential: 10,
      },
    ];
  },

  async getPools(filter) {
    const standings = await this.getStandings(filter);
    return [
      {
        poolId: standings[0]?.poolId ?? 8,
        poolName: standings[0]?.poolName ?? "Pool A",
        eventSlug: standings[0]?.eventSlug ?? "spring-shootout-2026",
        eventName: standings[0]?.eventName ?? "Spring Shootout 2026",
        divisionId: standings[0]?.divisionId ?? 3,
        divisionName: standings[0]?.divisionName ?? "U15 Boys",
        stageName: standings[0]?.stageName ?? "Pool Play",
        stageType: "pool_play",
        stageScope: "pool",
        stageOrder: 1,
        stageStatus: "active",
        teams: standings.map((team) => ({
          teamId: null,
          teamPublicId: team.teamPublicId,
          teamName: team.teamName,
          rank: team.rank,
          tieBreakRank: team.rank,
          manualOverrideRank: null,
          gamesPlayed: team.gamesPlayed,
          wins: team.wins,
          losses: team.losses,
          ties: team.ties,
          pointsFor: team.pointsFor,
          pointsAgainst: team.pointsAgainst,
          pointDifferential: team.pointDifferential,
          winPctBps: null,
          winPct: null,
          revision: null,
          computedAt: null,
          updatedAt: null,
        })),
      },
    ];
  },

  async getPlayoffBrackets(filter) {
    const results = await this.getResults(filter);
    const result = results[0];

    if (!result) {
      return [];
    }

    return [
      {
        stageName: "Semi Final",
        eventSlug: result.eventSlug,
        eventName: result.eventName,
        divisionId: result.divisionId ?? null,
        divisionName: result.divisionName ?? null,
        stageType: "playoff_bracket",
        stageScope: "division",
        stageOrder: 1,
        stageStatus: "active",
        bracketDefinition: [
          { order: 1, name: "Semi 1", homeSource: "1A", awaySource: "2B" },
        ],
        games: [
          {
            gameId: 19,
            gamePublicId: result.gamePublicId,
            status: result.gameStatus,
            scheduledAt: result.scheduledAt,
            venue: result.venue ?? null,
            court: null,
            homeTeamName: result.teamName,
            homeTeamPublicId: null,
            homeSlotLabel: null,
            homeScore: result.score,
            awayTeamName: result.opponentTeamName,
            awayTeamPublicId: null,
            awaySlotLabel: null,
            awayScore: result.opponentScore,
            stageName: "Semi Final",
          },
        ],
      },
    ];
  },

  async getTeamsForEvent(filterEvent) {
    const standings = await this.getStandings({ event: filterEvent });
    return standings.map((team) => ({
      teamPublicId: team.teamPublicId,
      teamName: team.teamName,
      divisionName: team.divisionName ?? null,
      poolName: team.poolName ?? null,
    }));
  },

  async getGame(publicId) {
    const scoreboard = await this.getScoreboard({
      event: "spring-shootout-2026",
      status: "all",
      limit: 10,
    });
    const selected =
      scoreboard.find((game) => game.gamePublicId === publicId) ??
      scoreboard[0];

    return {
      generatedAt: "2026-04-18T18:44:10.000Z",
      game: {
        gameId: selected.gameId,
        gamePublicId: selected.gamePublicId,
        status: selected.status,
        scheduledAt: selected.scheduledAt,
        venue: selected.venue,
        eventSlug: selected.eventSlug,
        eventName: selected.eventName,
        divisionId: selected.divisionId ?? null,
        divisionName: selected.divisionName ?? "U15 Boys",
        poolId: selected.poolId ?? null,
        poolName: selected.poolName ?? "Pool A",
        stageId: selected.stageId ?? null,
        stageName: selected.stageName ?? "Pool Play",
        homeTeamName: selected.homeTeamName,
        homeScore: selected.homeScore,
        awayTeamName: selected.awayTeamName,
        awayScore: selected.awayScore,
        periodNumber: selected.periodNumber,
        clockSecondsRemaining: selected.clockSecondsRemaining,
        clockDecisecondsRemaining: selected.clockDecisecondsRemaining,
        isClockRunning: selected.isClockRunning,
        usesGameClock: true,
        clockSyncedAt: "2026-04-18T18:44:00.000Z",
      },
      playerLinesByTeam: [
        {
          teamId: 1,
          teamName: selected.homeTeamName,
          players: [
            {
              playerId: 101,
              playerName: "Jordan Lee",
              jerseyNumber: 12,
              points: 18,
              fouls: 2,
              secondsPlayed: 1260,
              plusMinus: 7,
            },
            {
              playerId: 102,
              playerName: "Morgan Smith",
              jerseyNumber: 4,
              points: 11,
              fouls: 1,
              secondsPlayed: 980,
              plusMinus: 3,
            },
          ],
        },
        {
          teamId: 2,
          teamName: selected.awayTeamName,
          players: [
            {
              playerId: 201,
              playerName: "Sam Carter",
              jerseyNumber: 21,
              points: 16,
              fouls: 3,
              secondsPlayed: 1180,
              plusMinus: -4,
            },
          ],
        },
      ],
      recentEvents: [
        {
          eventSequence: 11,
          eventType: "score_3",
          periodNumber: 3,
          clockSecondsRemaining: 155,
          points: 3,
          teamName: selected.awayTeamName,
          playerFirstName: "Sam",
          playerLastName: "Carter",
          recordedAt: "2026-04-18T18:43:12.000Z",
        },
        {
          eventSequence: 12,
          eventType: "score_2",
          periodNumber: 3,
          clockSecondsRemaining: 142,
          points: 2,
          teamName: selected.homeTeamName,
          playerFirstName: "Jordan",
          playerLastName: "Lee",
          recordedAt: "2026-04-18T18:44:00.000Z",
        },
      ],
    };
  },

  async getGameBoxScore(publicId) {
    return this.getGame(publicId);
  },

  async refreshEvent(identifier) {
    revalidateTag(`competition:${identifier}`, "max");
  },

  async submitScore() {
    return {
      accepted: false,
      reason: "Score submission remains external to the competition system.",
    };
  },
};
