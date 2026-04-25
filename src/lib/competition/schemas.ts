import { z } from "zod";

export const eventSchema = z.object({
  id: z.number().optional(),
  publicId: z.string().nullable().optional(),
  slug: z.string(),
  name: z.string(),
  eventType: z.string().optional(),
  visibility: z.string().optional(),
  status: z.string().optional(),
  startsOn: z.string().optional(),
  endsOn: z.string().optional(),
});

export const scoreboardGameSchema = z.object({
  gameId: z.number(),
  gamePublicId: z.string(),
  status: z.string(),
  scheduledAt: z.string(),
  venue: z.string().nullable().optional(),
  court: z.string().nullable().optional(),
  eventSlug: z.string(),
  eventName: z.string(),
  divisionId: z.number().nullable().optional(),
  divisionName: z.string().nullable().optional(),
  poolId: z.number().nullable().optional(),
  poolName: z.string().nullable().optional(),
  stageId: z.number().nullable().optional(),
  stageName: z.string().nullable().optional(),
  homeTeamName: z.string(),
  homeTeamPublicId: z.string().nullable().optional(),
  homeSlotLabel: z.string().nullable().optional(),
  homeScore: z.number().nullable().optional(),
  awayTeamName: z.string(),
  awayTeamPublicId: z.string().nullable().optional(),
  awaySlotLabel: z.string().nullable().optional(),
  awayScore: z.number().nullable().optional(),
  periodNumber: z.number().nullable().optional(),
  clockSecondsRemaining: z.number().nullable().optional(),
  isClockRunning: z.boolean().nullable().optional(),
});

export const resultSchema = z.object({
  gamePublicId: z.string(),
  gameStatus: z.string(),
  resultWorkflowStatus: z.string(),
  scheduledAt: z.string(),
  venue: z.string().nullable().optional(),
  eventSlug: z.string(),
  eventName: z.string(),
  divisionId: z.number().nullable().optional(),
  divisionName: z.string().nullable().optional(),
  poolId: z.number().nullable().optional(),
  poolName: z.string().nullable().optional(),
  stageName: z.string().nullable().optional(),
  teamName: z.string(),
  opponentTeamName: z.string(),
  result: z.string(),
  score: z.number(),
  opponentScore: z.number(),
});

export const standingSchema = z.object({
  eventSlug: z.string(),
  eventName: z.string(),
  divisionId: z.number().nullable().optional(),
  divisionName: z.string().nullable().optional(),
  poolId: z.number().nullable().optional(),
  poolName: z.string().nullable().optional(),
  stageName: z.string().nullable().optional(),
  teamPublicId: z.string(),
  teamName: z.string(),
  rank: z.number(),
  wins: z.number(),
  losses: z.number(),
  ties: z.number(),
  gamesPlayed: z.number(),
  pointsFor: z.number(),
  pointsAgainst: z.number(),
  pointDifferential: z.number(),
});

export const gameDetailSchema = z.object({
  game: z.object({
    gameId: z.number(),
    gamePublicId: z.string(),
    status: z.string(),
    scheduledAt: z.string(),
    venue: z.string().nullable().optional(),
    eventSlug: z.string(),
    eventName: z.string(),
    homeTeamName: z.string(),
    homeScore: z.number().nullable().optional(),
    awayTeamName: z.string(),
    awayScore: z.number().nullable().optional(),
    periodNumber: z.number().nullable().optional(),
    clockSecondsRemaining: z.number().nullable().optional(),
  }),
  recentEvents: z
    .array(
      z.object({
        eventSequence: z.number(),
        eventType: z.string(),
        periodNumber: z.number().nullable().optional(),
        clockSecondsRemaining: z.number().nullable().optional(),
        teamName: z.string().nullable().optional(),
        playerFirstName: z.string().nullable().optional(),
        playerLastName: z.string().nullable().optional(),
        recordedAt: z.string(),
      }),
    )
    .default([]),
});

export const poolTeamSchema = z.object({
  teamId: z.number().nullable().optional(),
  teamPublicId: z.string(),
  teamName: z.string(),
  rank: z.number(),
  tieBreakRank: z.number().nullable().optional(),
  manualOverrideRank: z.number().nullable().optional(),
  gamesPlayed: z.number(),
  wins: z.number(),
  losses: z.number(),
  ties: z.number(),
  pointsFor: z.number(),
  pointsAgainst: z.number(),
  pointDifferential: z.number(),
  winPctBps: z.number().nullable().optional(),
  winPct: z.number().nullable().optional(),
  revision: z.number().nullable().optional(),
  computedAt: z.string().nullable().optional(),
  updatedAt: z.string().nullable().optional(),
});

export const poolSchema = z.object({
  poolId: z.number().nullable().optional(),
  poolName: z.string().nullable().optional(),
  eventPublicId: z.string().nullable().optional(),
  eventSlug: z.string(),
  eventName: z.string(),
  divisionId: z.number().nullable().optional(),
  divisionName: z.string().nullable().optional(),
  stageId: z.number().nullable().optional(),
  stagePublicId: z.string().nullable().optional(),
  stageName: z.string().nullable().optional(),
  stageType: z.string().nullable().optional(),
  stageScope: z.string().nullable().optional(),
  stageOrder: z.number().nullable().optional(),
  stageStatus: z.string().nullable().optional(),
  teams: z.array(poolTeamSchema).default([]),
});

export const playoffGameSchema = z.object({
  gameId: z.number(),
  gamePublicId: z.string(),
  status: z.string(),
  scheduledAt: z.string(),
  venue: z.string().nullable().optional(),
  court: z.string().nullable().optional(),
  homeTeamName: z.string().nullable().optional(),
  homeTeamPublicId: z.string().nullable().optional(),
  homeSlotLabel: z.string().nullable().optional(),
  homeScore: z.number().nullable().optional(),
  awayTeamName: z.string().nullable().optional(),
  awayTeamPublicId: z.string().nullable().optional(),
  awaySlotLabel: z.string().nullable().optional(),
  awayScore: z.number().nullable().optional(),
  stageName: z.string().nullable().optional(),
});

export const playoffBracketSchema = z.object({
  stageId: z.number().nullable().optional(),
  stagePublicId: z.string().nullable().optional(),
  stageName: z.string(),
  stageType: z.string().nullable().optional(),
  stageScope: z.string().nullable().optional(),
  stageOrder: z.number().nullable().optional(),
  stageStatus: z.string().nullable().optional(),
  eventPublicId: z.string().nullable().optional(),
  eventSlug: z.string(),
  eventName: z.string(),
  divisionId: z.number().nullable().optional(),
  divisionName: z.string().nullable().optional(),
  bracketDefinition: z
    .array(
      z.object({
        order: z.number(),
        name: z.string(),
        homeSource: z.string().nullable().optional(),
        awaySource: z.string().nullable().optional(),
      }),
    )
    .default([]),
  games: z.array(playoffGameSchema).default([]),
});

export type CompetitionPool = {
  poolId?: number | null;
  poolName?: string | null;
  eventPublicId?: string | null;
  eventSlug: string;
  eventName: string;
  divisionId?: number | null;
  divisionName?: string | null;
  stageId?: number | null;
  stagePublicId?: string | null;
  stageName?: string | null;
  stageType?: string | null;
  stageScope?: string | null;
  stageOrder?: number | null;
  stageStatus?: string | null;
  teams: Array<z.infer<typeof poolTeamSchema>>;
};

export type CompetitionPlayoffBracket = {
  stageName: string;
  stageId?: number | null;
  stagePublicId?: string | null;
  stageType?: string | null;
  stageScope?: string | null;
  stageOrder?: number | null;
  stageStatus?: string | null;
  eventPublicId?: string | null;
  eventSlug: string;
  eventName: string;
  divisionId?: number | null;
  divisionName?: string | null;
  bracketDefinition: Array<z.infer<typeof playoffBracketSchema>["bracketDefinition"][number]>;
  games: Array<z.infer<typeof playoffGameSchema>>;
};

export type CompetitionEventTeam = {
  teamPublicId: string;
  teamName: string;
  divisionName: string | null;
  poolName: string | null;
};

export type CompetitionEvent = z.infer<typeof eventSchema>;
export type CompetitionScoreboardGame = z.infer<typeof scoreboardGameSchema>;
export type CompetitionResult = z.infer<typeof resultSchema>;
export type CompetitionStanding = z.infer<typeof standingSchema>;
export type CompetitionGameDetail = z.infer<typeof gameDetailSchema>;
export type CompetitionPoolTeam = z.infer<typeof poolTeamSchema>;
export type CompetitionPoolRecord = z.infer<typeof poolSchema>;
export type CompetitionPlayoffGame = z.infer<typeof playoffGameSchema>;
export type CompetitionPlayoffBracketRecord = z.infer<typeof playoffBracketSchema>;
