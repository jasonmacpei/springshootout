import type {
  CompetitionEvent,
  CompetitionEventTeam,
  CompetitionGameDetail,
  CompetitionPlayoffBracket,
  CompetitionPool,
  CompetitionResult,
  CompetitionScoreboardGame,
  CompetitionStanding,
} from "@/lib/competition/schemas";

export type ScoreboardFilter = {
  event: string;
  status?: "live" | "final" | "all";
  limit?: number;
};

export type ResultsFilter = {
  event: string;
  divisionId?: number;
  poolId?: number;
  stage?: string | number;
  workflow?: "all" | "finalized" | "approved" | "locked";
  dateFrom?: string;
  dateTo?: string;
  limit?: number;
};

export type StandingsFilter = {
  event: string;
  divisionId?: number;
  poolId?: number;
  stage?: string | number;
  limit?: number;
};

export interface CompetitionProvider {
  listEvents(): Promise<CompetitionEvent[]>;
  getSchedule(filter: ScoreboardFilter): Promise<CompetitionScoreboardGame[]>;
  getScoreboard(filter: ScoreboardFilter): Promise<CompetitionScoreboardGame[]>;
  getResults(filter: ResultsFilter): Promise<CompetitionResult[]>;
  getStandings(filter: StandingsFilter): Promise<CompetitionStanding[]>;
  getPools(filter: StandingsFilter): Promise<CompetitionPool[]>;
  getPlayoffBrackets(filter: ResultsFilter): Promise<CompetitionPlayoffBracket[]>;
  getTeamsForEvent(event: string): Promise<CompetitionEventTeam[]>;
  getGame(publicId: string): Promise<CompetitionGameDetail | null>;
  submitScore(input: {
    event: string;
    gamePublicId: string;
    homeScore: number;
    awayScore: number;
  }): Promise<{ accepted: boolean; reason?: string }>;
  refreshEvent(identifier: string): Promise<void>;
}
