import { revalidateTag } from "next/cache";

import { appConfig } from "@/lib/config";
import { mockCompetitionProvider } from "@/lib/competition/adapters/mock";
import {
  eventSchema,
  gameDetailSchema,
  playoffBracketSchema,
  poolSchema,
  resultSchema,
  scoreboardGameSchema,
  standingSchema,
  type CompetitionScoreboardGame,
} from "@/lib/competition/schemas";
import type { CompetitionProvider, ResultsFilter, ScoreboardFilter, StandingsFilter } from "@/lib/competition/service";

class CompetitionRequestError extends Error {
  constructor(
    message: string,
    public readonly status: number,
  ) {
    super(message);
  }
}

async function fetchCompetitionEnvelope({
  endpoint,
  searchParams,
  tag,
  revalidate,
  cache,
}: {
  endpoint: string;
  searchParams?: Record<string, string | number | undefined>;
  tag?: string;
  revalidate?: number;
  cache?: RequestCache;
}) {
  const url = new URL(`/api/v1/${endpoint}`, appConfig.hoopsApiBase);

  Object.entries(searchParams ?? {}).forEach(([key, value]) => {
    if (value !== undefined && value !== "") {
      url.searchParams.set(key, String(value));
    }
  });

  const headers: HeadersInit = {
    Accept: "application/json",
  };

  if (appConfig.partnerKey) {
    headers["Authorization"] = `Bearer ${appConfig.partnerKey}`;
  }

  const response = await fetch(url, {
    cache,
    headers,
    next: tag && revalidate !== undefined
      ? {
          revalidate,
          tags: [tag],
        }
      : undefined,
  });

  if (!response.ok) {
    throw new CompetitionRequestError(`Competition request failed for ${endpoint}: ${response.status}`, response.status);
  }

  const payload = await response.json();
  const data = payload?.data;

  if (!data) {
    throw new Error(`Competition payload missing data envelope for ${endpoint}`);
  }

  return data;
}

function getScoreboardGameBoxScore(game: CompetitionScoreboardGame) {
  return gameDetailSchema.parse({
    generatedAt: new Date().toISOString(),
    game: {
      gameId: game.gameId,
      gamePublicId: game.gamePublicId,
      status: game.status,
      scheduledAt: game.scheduledAt,
      venue: game.venue,
      court: game.court,
      eventSlug: game.eventSlug,
      eventName: game.eventName,
      divisionId: game.divisionId,
      divisionName: game.divisionName,
      poolId: game.poolId,
      poolName: game.poolName,
      stageId: game.stageId,
      stageName: game.stageName,
      homeTeamName: game.homeTeamName,
      homeScore: game.homeScore,
      awayTeamName: game.awayTeamName,
      awayScore: game.awayScore,
      periodNumber: game.periodNumber,
      clockSecondsRemaining: game.clockSecondsRemaining,
      clockDecisecondsRemaining: game.clockDecisecondsRemaining,
      isClockRunning: game.isClockRunning,
      usesGameClock: game.usesGameClock,
      clockSyncedAt: game.clockSyncedAt,
    },
    playerLinesByTeam: [],
    recentEvents: [],
  });
}

function inferGameStatus(game: Record<string, unknown>) {
  if (typeof game.status === "string") {
    return game.status;
  }

  if (game.resultWorkflowStatus === "finalized" || game.resultWorkflowStatus === "approved") {
    return "final";
  }

  return "scheduled";
}

function getLatestPeriodNumber(game: Record<string, unknown>) {
  if (typeof game.periodNumber === "number") {
    return game.periodNumber;
  }

  if (!Array.isArray(game.periodNumbers)) {
    return null;
  }

  const periodNumbers = game.periodNumbers.filter((period): period is number => typeof period === "number");
  return periodNumbers.at(-1) ?? null;
}

function normalizeGameDetailPayload(data: unknown) {
  if (!data || typeof data !== "object") {
    return data;
  }

  const payload = data as Record<string, unknown>;
  const game = payload.game;
  if (!game || typeof game !== "object") {
    return data;
  }

  const normalizedGame = game as Record<string, unknown>;
  const status = inferGameStatus(normalizedGame);
  const isFinal = status === "final";

  return {
    ...payload,
    game: {
      ...normalizedGame,
      status,
      periodNumber: getLatestPeriodNumber(normalizedGame),
      clockSecondsRemaining:
        typeof normalizedGame.clockSecondsRemaining === "number"
          ? normalizedGame.clockSecondsRemaining
          : isFinal
            ? 0
            : null,
      clockDecisecondsRemaining:
        typeof normalizedGame.clockDecisecondsRemaining === "number"
          ? normalizedGame.clockDecisecondsRemaining
          : typeof normalizedGame.clockSecondsRemaining === "number"
            ? normalizedGame.clockSecondsRemaining * 10
            : isFinal
              ? 0
              : null,
      isClockRunning:
        typeof normalizedGame.isClockRunning === "boolean"
          ? normalizedGame.isClockRunning
          : isFinal
            ? false
            : null,
    },
  };
}

export const hoopsscorebookProvider: CompetitionProvider = {
  async listEvents() {
    try {
      const data = await fetchCompetitionEnvelope({
        endpoint: "events",
        tag: "competition:events",
        revalidate: 300,
      });

      return eventSchema.array().parse(data.events ?? []);
    } catch {
      return mockCompetitionProvider.listEvents();
    }
  },

  async getSchedule(filter: ScoreboardFilter) {
    try {
      const data = await fetchCompetitionEnvelope({
        endpoint: "schedule",
        searchParams: {
          event: filter.event,
          limit: filter.limit ?? 500,
        },
        tag: `competition:${filter.event}:schedule`,
        revalidate: 60,
      });

      return scoreboardGameSchema.array().parse(data.games ?? []);
    } catch {
      return mockCompetitionProvider.getSchedule(filter);
    }
  },

  async getScoreboard(filter: ScoreboardFilter) {
    try {
      const noStore = filter.noStore === true;
      const data = await fetchCompetitionEnvelope({
        endpoint: "scoreboard",
        searchParams: {
          event: filter.event,
          status: filter.status ?? "all",
          limit: filter.limit ?? 20,
        },
        tag: noStore ? undefined : `competition:${filter.event}:scoreboard`,
        revalidate: noStore ? undefined : 15,
        cache: noStore ? "no-store" : undefined,
      });

      return scoreboardGameSchema.array().parse(data.games ?? []);
    } catch {
      return mockCompetitionProvider.getScoreboard(filter);
    }
  },

  async getResults(filter: ResultsFilter) {
    try {
      const data = await fetchCompetitionEnvelope({
        endpoint: "results",
        searchParams: {
          event: filter.event,
          divisionId: filter.divisionId,
          poolId: filter.poolId,
          stage: filter.stage as string | number | undefined,
          workflow: filter.workflow ?? "approved",
          dateFrom: filter.dateFrom,
          dateTo: filter.dateTo,
          limit: filter.limit ?? 150,
        },
        tag: `competition:${filter.event}:results`,
        revalidate: 45,
      });

      return resultSchema.array().parse(data.results ?? []);
    } catch {
      return mockCompetitionProvider.getResults(filter);
    }
  },

  async getStandings(filter: StandingsFilter) {
    try {
      const data = await fetchCompetitionEnvelope({
        endpoint: "standings",
        searchParams: {
          event: filter.event,
          divisionId: filter.divisionId,
          poolId: filter.poolId,
          stage: filter.stage as string | number | undefined,
          limit: filter.limit ?? 200,
        },
        tag: `competition:${filter.event}:standings`,
        revalidate: 45,
      });

      return standingSchema.array().parse(data.standings ?? []);
    } catch {
      return mockCompetitionProvider.getStandings(filter);
    }
  },

  async getPools(filter: StandingsFilter) {
    try {
      const data = await fetchCompetitionEnvelope({
        endpoint: "pools",
        searchParams: {
          event: filter.event,
          divisionId: filter.divisionId,
          stage: filter.stage as string | number | undefined,
          limit: filter.limit ?? 50,
        },
        tag: `competition:${filter.event}:pools`,
        revalidate: 60,
      });

      return poolSchema.array().parse(data.pools ?? []);
    } catch {
      return mockCompetitionProvider.getPools(filter);
    }
  },

  async getPlayoffBrackets(filter: ResultsFilter) {
    try {
      const data = await fetchCompetitionEnvelope({
        endpoint: "playoffs",
        searchParams: {
          event: filter.event,
          divisionId: filter.divisionId,
          stage: filter.stage as string | number | undefined,
          limit: filter.limit ?? 50,
        },
        tag: `competition:${filter.event}:playoffs`,
        revalidate: 60,
      });

      return playoffBracketSchema.array().parse(data.brackets ?? []);
    } catch {
      return mockCompetitionProvider.getPlayoffBrackets(filter);
    }
  },

  async getTeamsForEvent(event: string) {
    const standings = await this.getStandings({ event });
    const seen = new Set<string>();

    return standings
      .filter((team) => {
        if (seen.has(team.teamPublicId)) {
          return false;
        }

        seen.add(team.teamPublicId);
        return true;
      })
      .map((team) => ({
        teamPublicId: team.teamPublicId,
        teamName: team.teamName,
        divisionName: team.divisionName ?? null,
        poolName: team.poolName ?? null,
      }));
  },

  async getGame(publicId: string) {
    return this.getGameBoxScore(publicId);
  },

  async getGameBoxScore(publicId: string) {
    try {
      const data = await fetchCompetitionEnvelope({
        endpoint: `games/${publicId}/box-score`,
        cache: "no-store",
      });

      return gameDetailSchema.parse(normalizeGameDetailPayload(data));
    } catch (error) {
      if (error instanceof CompetitionRequestError && error.status === 404) {
        const games = await this.getScoreboard({
          event: appConfig.defaultEventSlug,
          status: "all",
          limit: 100,
          noStore: true,
        });
        const game = games.find((scoreboardGame) => scoreboardGame.gamePublicId === publicId);

        return game ? getScoreboardGameBoxScore(game) : null;
      }

      throw error;
    }
  },

  async refreshEvent(identifier: string) {
    revalidateTag(`competition:${identifier}:scoreboard`, "max");
    revalidateTag(`competition:${identifier}:results`, "max");
    revalidateTag(`competition:${identifier}:standings`, "max");
    revalidateTag(`competition:${identifier}:schedule`, "max");
    revalidateTag(`competition:${identifier}:pools`, "max");
    revalidateTag(`competition:${identifier}:playoffs`, "max");
    revalidateTag(`competition:${identifier}`, "max");
  },

  async submitScore() {
    return {
      accepted: false,
      reason: "Score submission remains external to Hoops Scorebook operations.",
    };
  },
};
