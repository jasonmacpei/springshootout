import type { CompetitionPoolRecord, CompetitionScoreboardGame, CompetitionStanding } from "@/lib/competition/schemas";
import { isCompleteRoundRobinStatus, isRoundRobinGame } from "@/lib/competition/playoff-presentation";

export type StandingRow = Pick<
  CompetitionStanding,
  | "eventSlug"
  | "eventName"
  | "divisionId"
  | "divisionName"
  | "poolId"
  | "poolName"
  | "stageName"
  | "teamPublicId"
  | "teamName"
  | "rank"
  | "wins"
  | "losses"
  | "ties"
  | "gamesPlayed"
  | "pointsFor"
  | "pointsAgainst"
  | "pointDifferential"
>;

type StandingRowWithOrder = StandingRow & { sourceOrder: number };

function getStandingKey(row: Pick<StandingRow, "divisionId" | "divisionName" | "poolId" | "poolName" | "teamPublicId">) {
  return [
    row.divisionId ?? row.divisionName ?? "Division",
    row.poolId ?? row.poolName ?? "Pool",
    row.teamPublicId,
  ].join(":");
}

function getPoolKey(row: Pick<StandingRow, "divisionId" | "divisionName" | "poolId" | "poolName">) {
  return [row.divisionId ?? row.divisionName ?? "Division", row.poolId ?? row.poolName ?? "Pool"].join(":");
}

function getWinPct(row: Pick<StandingRow, "gamesPlayed" | "ties" | "wins">) {
  if (row.gamesPlayed === 0) {
    return 0;
  }

  return (row.wins + row.ties * 0.5) / row.gamesPlayed;
}

function updateCompletedGame(row: StandingRowWithOrder, pointsFor: number, pointsAgainst: number) {
  row.gamesPlayed += 1;
  row.pointsFor += pointsFor;
  row.pointsAgainst += pointsAgainst;
  row.pointDifferential += pointsFor - pointsAgainst;

  if (pointsFor > pointsAgainst) {
    row.wins += 1;
  } else if (pointsFor < pointsAgainst) {
    row.losses += 1;
  } else {
    row.ties += 1;
  }
}

export function buildScheduleStandings(schedule: CompetitionScoreboardGame[]): StandingRow[] {
  const rows = new Map<string, StandingRowWithOrder>();

  schedule.forEach((game, index) => {
    if (!isRoundRobinGame(game) || !game.divisionId || !game.poolId || !game.divisionName || !game.poolName) {
      return;
    }

    const teams = [
      { id: game.homeTeamPublicId, name: game.homeTeamName, score: game.homeScore, opponentScore: game.awayScore },
      { id: game.awayTeamPublicId, name: game.awayTeamName, score: game.awayScore, opponentScore: game.homeScore },
    ];

    teams.forEach((team) => {
      if (!team.id || !team.name) {
        return;
      }

      const key = `${game.divisionId}:${game.poolId}:${team.id}`;

      if (!rows.has(key)) {
        rows.set(key, {
          eventSlug: game.eventSlug,
          eventName: game.eventName,
          divisionId: game.divisionId,
          divisionName: game.divisionName,
          poolId: game.poolId,
          poolName: game.poolName,
          stageName: game.stageName,
          teamPublicId: team.id,
          teamName: team.name,
          rank: 0,
          wins: 0,
          losses: 0,
          ties: 0,
          gamesPlayed: 0,
          pointsFor: 0,
          pointsAgainst: 0,
          pointDifferential: 0,
          sourceOrder: index,
        });
      }
    });

    if (
      isCompleteRoundRobinStatus(game.status) &&
      typeof game.homeScore === "number" &&
      typeof game.awayScore === "number"
    ) {
      teams.forEach((team) => {
        if (!team.id || typeof team.score !== "number" || typeof team.opponentScore !== "number") {
          return;
        }

        const row = rows.get(`${game.divisionId}:${game.poolId}:${team.id}`);

        if (row) {
          updateCompletedGame(row, team.score, team.opponentScore);
        }
      });
    }
  });

  const sortedRows = Array.from(rows.values()).sort(
    (a, b) =>
      getWinPct(b) - getWinPct(a) ||
      b.wins - a.wins ||
      b.pointDifferential - a.pointDifferential ||
      b.pointsFor - a.pointsFor ||
      a.sourceOrder - b.sourceOrder ||
      a.teamName.localeCompare(b.teamName),
  );

  return rankRows(
    sortedRows.map((row, index) => ({ ...row, rank: index + 1, sourceOrder: index })),
    new Set(),
  );
}

export function buildPoolStandings(pools: CompetitionPoolRecord[]): StandingRow[] {
  return pools
    .filter((pool) => isRoundRobinGame(pool))
    .flatMap((pool, poolIndex) =>
      pool.teams.map((team, teamIndex) => ({
        eventSlug: pool.eventSlug,
        eventName: pool.eventName,
        divisionId: pool.divisionId,
        divisionName: pool.divisionName,
        poolId: pool.poolId,
        poolName: pool.poolName,
        stageName: pool.stageName,
        teamPublicId: team.teamPublicId,
        teamName: team.teamName,
        rank: team.rank || teamIndex + 1,
        wins: team.wins,
        losses: team.losses,
        ties: team.ties,
        gamesPlayed: team.gamesPlayed,
        pointsFor: team.pointsFor,
        pointsAgainst: team.pointsAgainst,
        pointDifferential: team.pointDifferential,
        sourceOrder: poolIndex * 1000 + teamIndex,
      })),
    );
}

function isRoundRobinStanding(standing: CompetitionStanding) {
  const stageName = standing.stageName?.trim().toLowerCase() ?? "";

  if (
    stageName.includes("playoff") ||
    stageName.includes("semi") ||
    stageName.includes("cross") ||
    stageName.includes("championship") ||
    stageName.includes("final") ||
    stageName.includes("place")
  ) {
    return false;
  }

  return Boolean(standing.poolId || standing.poolName || stageName.includes("pool") || stageName.includes("round robin"));
}

export function mergeStandings({
  pools,
  schedule,
  standings,
}: {
  pools: CompetitionPoolRecord[];
  schedule: CompetitionScoreboardGame[];
  standings: CompetitionStanding[];
}): StandingRow[] {
  const rows = new Map<string, StandingRowWithOrder>();

  const computedScheduleRows = buildScheduleStandings(schedule);
  const computedSchedulePoolKeys = new Set(computedScheduleRows.map((row) => getPoolKey(row)));

  buildPoolStandings(pools).forEach((row, index) => {
    const key = getStandingKey(row);

    if (!rows.has(key)) {
      rows.set(key, { ...row, sourceOrder: index });
    }
  });

  computedScheduleRows.forEach((row, index) => {
    rows.set(getStandingKey(row), { ...row, sourceOrder: index });
  });

  const standingsKeys = new Set<string>();

  standings.forEach((standing, index) => {
    if (!isRoundRobinStanding(standing)) {
      return;
    }

    const key = getStandingKey(standing);

    if (computedSchedulePoolKeys.has(getPoolKey(standing))) {
      return;
    }

    standingsKeys.add(key);
    rows.set(key, {
      ...standing,
      sourceOrder: rows.get(key)?.sourceOrder ?? index,
    });
  });

  return rankRows(Array.from(rows.values()), standingsKeys);
}

function rankRows(rows: StandingRowWithOrder[], standingsKeys: Set<string>): StandingRow[] {
  const groupedRows = rows.reduce<Record<string, StandingRowWithOrder[]>>((acc, row) => {
    const key = getPoolKey(row);
    acc[key] ??= [];
    acc[key].push(row);
    return acc;
  }, {});

  return Object.values(groupedRows).flatMap((poolRows) => {
    const hasStandingsRows = poolRows.some((row) => standingsKeys.has(getStandingKey(row)));

    return poolRows
      .sort((a, b) => {
        if (hasStandingsRows) {
          const aFromStandings = standingsKeys.has(getStandingKey(a));
          const bFromStandings = standingsKeys.has(getStandingKey(b));

          if (aFromStandings !== bFromStandings) {
            return aFromStandings ? -1 : 1;
          }
        }

        return a.rank - b.rank || a.sourceOrder - b.sourceOrder || a.teamName.localeCompare(b.teamName);
      })
      .map((row, index) => toStandingRow(row, index + 1));
  });
}

function toStandingRow(row: StandingRowWithOrder, rank: number): StandingRow {
  return {
    eventSlug: row.eventSlug,
    eventName: row.eventName,
    divisionId: row.divisionId,
    divisionName: row.divisionName,
    poolId: row.poolId,
    poolName: row.poolName,
    stageName: row.stageName,
    teamPublicId: row.teamPublicId,
    teamName: row.teamName,
    rank,
    wins: row.wins,
    losses: row.losses,
    ties: row.ties,
    gamesPlayed: row.gamesPlayed,
    pointsFor: row.pointsFor,
    pointsAgainst: row.pointsAgainst,
    pointDifferential: row.pointDifferential,
  };
}

export function groupStandings(standings: StandingRow[]) {
  return Object.values(
    standings.reduce<Record<string, StandingRow[]>>((acc, item) => {
      const key = `${item.divisionId ?? item.divisionName ?? "Division"}-${item.poolId ?? item.poolName ?? "Pool"}`;
      acc[key] ??= [];
      acc[key].push(item);
      return acc;
    }, {}),
  )
    .map((pool) => [...pool].sort((a, b) => a.rank - b.rank || a.teamName.localeCompare(b.teamName)))
    .sort((a, b) => {
      const divisionOrder =
        (a[0]?.divisionId ?? Number.MAX_SAFE_INTEGER) - (b[0]?.divisionId ?? Number.MAX_SAFE_INTEGER);

      if (divisionOrder !== 0) {
        return divisionOrder;
      }

      const divisionNameOrder = (a[0]?.divisionName ?? "").localeCompare(b[0]?.divisionName ?? "");

      if (divisionNameOrder !== 0) {
        return divisionNameOrder;
      }

      const poolOrder = (a[0]?.poolId ?? Number.MAX_SAFE_INTEGER) - (b[0]?.poolId ?? Number.MAX_SAFE_INTEGER);

      if (poolOrder !== 0) {
        return poolOrder;
      }

      return (a[0]?.poolName ?? "").localeCompare(b[0]?.poolName ?? "");
    });
}

export function formatWinPct(row: StandingRow) {
  return getWinPct(row).toFixed(3);
}
