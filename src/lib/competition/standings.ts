import type { CompetitionPoolRecord, CompetitionScoreboardGame, CompetitionStanding } from "@/lib/competition/schemas";

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

export function buildScheduleStandings(schedule: CompetitionScoreboardGame[]): StandingRow[] {
  const rows = new Map<string, StandingRowWithOrder>();

  schedule.forEach((game, index) => {
    if (!game.divisionId || !game.poolId || !game.divisionName || !game.poolName) {
      return;
    }

    [
      { id: game.homeTeamPublicId, name: game.homeTeamName },
      { id: game.awayTeamPublicId, name: game.awayTeamName },
    ].forEach((team) => {
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
  });

  return rankRows(Array.from(rows.values()), new Set());
}

export function buildPoolStandings(pools: CompetitionPoolRecord[]): StandingRow[] {
  return pools.flatMap((pool, poolIndex) =>
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

  [...buildPoolStandings(pools), ...buildScheduleStandings(schedule)].forEach((row, index) => {
    const key = getStandingKey(row);

    if (!rows.has(key)) {
      rows.set(key, { ...row, sourceOrder: index });
    }
  });

  const standingsKeys = new Set<string>();

  standings.forEach((standing, index) => {
    const key = getStandingKey(standing);
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
  if (row.gamesPlayed === 0) {
    return "0.000";
  }

  return ((row.wins + row.ties * 0.5) / row.gamesPlayed).toFixed(3);
}
