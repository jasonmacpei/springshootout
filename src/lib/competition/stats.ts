import { getCompetitionProvider } from "@/lib/competition";
import type {
  CompetitionGameDetail,
  CompetitionScoreboardGame,
  CompetitionStanding,
} from "@/lib/competition/schemas";
import { getCompetitionEventSlugByLocalSlug } from "@/lib/db/queries/content";

type PlayerLine = CompetitionGameDetail["playerLinesByTeam"][number]["players"][number];
type TeamLine = CompetitionGameDetail["playerLinesByTeam"][number];

export type TournamentPlayerStat = {
  key: string;
  playerName: string;
  jerseyNumber: number | null;
  teamName: string;
  gamesPlayed: number;
  points: number;
  fouls: number;
  pointsPerGame: number;
  foulsPerGame: number;
};

export type DivisionStats = {
  key: string;
  divisionId: number | null;
  divisionName: string;
  playerCount: number;
  statGameCount: number;
  pointsLeaders: TournamentPlayerStat[];
  foulsLeaders: TournamentPlayerStat[];
};

export type TournamentStatsFeed = {
  eventSlug: string;
  generatedAt: string;
  gameCount: number;
  statGameCount: number;
  playerCount: number;
  divisions: DivisionStats[];
};

type DivisionSeed = {
  divisionId?: number | null;
  divisionName?: string | null;
};

type PlayerAccumulator = {
  key: string;
  playerName: string;
  jerseyNumber: number | null;
  teamName: string;
  gameKeys: Set<string>;
  points: number;
  fouls: number;
};

type DivisionAccumulator = {
  key: string;
  divisionId: number | null;
  divisionName: string;
  statGameKeys: Set<string>;
  players: Map<string, PlayerAccumulator>;
};

function getDivisionKey(division: DivisionSeed) {
  return String(division.divisionId ?? division.divisionName ?? "division-pending");
}

function getDivisionName(division: DivisionSeed) {
  return division.divisionName ?? "Division pending";
}

function sortDivisionStats(left: DivisionStats, right: DivisionStats) {
  if (left.divisionId !== null && right.divisionId !== null && left.divisionId !== right.divisionId) {
    return left.divisionId - right.divisionId;
  }

  if (left.divisionId !== null && right.divisionId === null) {
    return -1;
  }

  if (left.divisionId === null && right.divisionId !== null) {
    return 1;
  }

  return left.divisionName.localeCompare(right.divisionName);
}

function sortByPoints(left: TournamentPlayerStat, right: TournamentPlayerStat) {
  return (
    right.points - left.points ||
    right.pointsPerGame - left.pointsPerGame ||
    right.fouls - left.fouls ||
    left.playerName.localeCompare(right.playerName) ||
    left.teamName.localeCompare(right.teamName)
  );
}

function sortByFouls(left: TournamentPlayerStat, right: TournamentPlayerStat) {
  return (
    right.fouls - left.fouls ||
    right.foulsPerGame - left.foulsPerGame ||
    right.points - left.points ||
    left.playerName.localeCompare(right.playerName) ||
    left.teamName.localeCompare(right.teamName)
  );
}

function getGameKey(boxScore: CompetitionGameDetail) {
  return boxScore.game.gamePublicId || String(boxScore.game.gameId);
}

function getPlayerKey({
  divisionKey,
  player,
  team,
}: {
  divisionKey: string;
  player: PlayerLine;
  team: TeamLine;
}) {
  return [
    divisionKey,
    team.teamId ?? team.teamName,
    player.playerId ?? player.jerseyNumber ?? "no-number",
    player.playerName.trim().toLowerCase(),
  ].join(":");
}

function ensureDivision(acc: Map<string, DivisionAccumulator>, seed: DivisionSeed) {
  const key = getDivisionKey(seed);
  const existing = acc.get(key);

  if (existing) {
    if (!existing.divisionName || existing.divisionName === "Division pending") {
      existing.divisionName = getDivisionName(seed);
    }

    if (existing.divisionId === null && typeof seed.divisionId === "number") {
      existing.divisionId = seed.divisionId;
    }

    return existing;
  }

  const division: DivisionAccumulator = {
    key,
    divisionId: seed.divisionId ?? null,
    divisionName: getDivisionName(seed),
    statGameKeys: new Set(),
    players: new Map(),
  };

  acc.set(key, division);
  return division;
}

function toPlayerStat(player: PlayerAccumulator): TournamentPlayerStat {
  const gamesPlayed = player.gameKeys.size;

  return {
    key: player.key,
    playerName: player.playerName,
    jerseyNumber: player.jerseyNumber,
    teamName: player.teamName,
    gamesPlayed,
    points: player.points,
    fouls: player.fouls,
    pointsPerGame: gamesPlayed > 0 ? player.points / gamesPlayed : 0,
    foulsPerGame: gamesPlayed > 0 ? player.fouls / gamesPlayed : 0,
  };
}

export function buildTournamentStatsFeed({
  boxScores,
  eventSlug,
  generatedAt = new Date().toISOString(),
  scoreboardGames,
  standings,
}: {
  boxScores: CompetitionGameDetail[];
  eventSlug: string;
  generatedAt?: string;
  scoreboardGames: CompetitionScoreboardGame[];
  standings: CompetitionStanding[];
}): TournamentStatsFeed {
  const divisions = new Map<string, DivisionAccumulator>();

  standings.forEach((standing) => {
    ensureDivision(divisions, standing);
  });

  scoreboardGames.forEach((game) => {
    ensureDivision(divisions, game);
  });

  boxScores.forEach((boxScore) => {
    const division = ensureDivision(divisions, boxScore.game);
    const gameKey = getGameKey(boxScore);
    const hasPlayerStats = boxScore.playerLinesByTeam.some((team) => team.players.length > 0);

    if (hasPlayerStats) {
      division.statGameKeys.add(gameKey);
    }

    boxScore.playerLinesByTeam.forEach((team) => {
      team.players.forEach((player) => {
        const playerKey = getPlayerKey({
          divisionKey: division.key,
          player,
          team,
        });
        const existing = division.players.get(playerKey);
        const points = player.points ?? 0;
        const fouls = player.fouls ?? 0;

        if (existing) {
          existing.gameKeys.add(gameKey);
          existing.points += points;
          existing.fouls += fouls;
          return;
        }

        division.players.set(playerKey, {
          key: playerKey,
          playerName: player.playerName,
          jerseyNumber: player.jerseyNumber ?? null,
          teamName: team.teamName,
          gameKeys: new Set([gameKey]),
          points,
          fouls,
        });
      });
    });
  });

  const divisionStats = Array.from(divisions.values())
    .map((division) => {
      const players = Array.from(division.players.values()).map(toPlayerStat);

      return {
        key: division.key,
        divisionId: division.divisionId,
        divisionName: division.divisionName,
        playerCount: players.length,
        statGameCount: division.statGameKeys.size,
        pointsLeaders: players.filter((player) => player.points > 0).sort(sortByPoints).slice(0, 10),
        foulsLeaders: players.filter((player) => player.fouls > 0).sort(sortByFouls).slice(0, 10),
      };
    })
    .sort(sortDivisionStats);

  return {
    eventSlug,
    generatedAt,
    gameCount: scoreboardGames.length,
    statGameCount: divisionStats.reduce((total, division) => total + division.statGameCount, 0),
    playerCount: divisionStats.reduce((total, division) => total + division.playerCount, 0),
    divisions: divisionStats,
  };
}

async function mapWithConcurrency<T, R>(items: T[], limit: number, task: (item: T) => Promise<R>) {
  const results: R[] = [];
  let index = 0;

  async function worker() {
    while (index < items.length) {
      const currentIndex = index;
      index += 1;
      results[currentIndex] = await task(items[currentIndex]);
    }
  }

  await Promise.all(Array.from({ length: Math.min(limit, items.length) }, worker));
  return results;
}

export async function getTournamentStatsFeed(): Promise<TournamentStatsFeed> {
  const provider = getCompetitionProvider();
  const eventSlug = await getCompetitionEventSlugByLocalSlug();
  const [scoreboardGames, standings] = await Promise.all([
    provider.getScoreboard({
      event: eventSlug,
      status: "all",
      limit: 500,
      noStore: true,
    }),
    provider.getStandings({
      event: eventSlug,
      limit: 500,
    }),
  ]);

  const boxScoreResults = await mapWithConcurrency(scoreboardGames, 6, async (game) => {
    try {
      return await provider.getGameBoxScore(game.gamePublicId);
    } catch {
      return null;
    }
  });

  return buildTournamentStatsFeed({
    boxScores: boxScoreResults.filter((boxScore): boxScore is CompetitionGameDetail => Boolean(boxScore)),
    eventSlug,
    scoreboardGames,
    standings,
  });
}
