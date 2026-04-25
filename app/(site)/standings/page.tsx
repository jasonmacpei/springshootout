import { CompetitionPoweredNote } from "@/components/marketing/competition-powered-note";
import { PageHero } from "@/components/marketing/page-hero";
import { EmptyState } from "@/components/states/empty-state";
import { Card } from "@/components/ui/card";
import { getCompetitionProvider } from "@/lib/competition";
import { getCompetitionEventSlugByLocalSlug } from "@/lib/db/queries/content";
import type { CompetitionScoreboardGame, CompetitionStanding } from "@/lib/competition/schemas";

type StandingRow = Pick<
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

function buildScheduleStandings(schedule: CompetitionScoreboardGame[]): StandingRow[] {
  const rows = new Map<string, StandingRow & { firstSeen: number }>();

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
          firstSeen: index,
        });
      }
    });
  });

  const groupedRows = Array.from(rows.values()).reduce<Record<string, Array<StandingRow & { firstSeen: number }>>>(
    (acc, row) => {
      const key = `${row.divisionId}:${row.poolId}`;
      acc[key] ??= [];
      acc[key].push(row);
      return acc;
    },
    {},
  );

  return Object.values(groupedRows).flatMap((poolRows) =>
    poolRows
      .sort((a, b) => a.firstSeen - b.firstSeen || a.teamName.localeCompare(b.teamName))
      .map((row, index) => ({
        eventSlug: row.eventSlug,
        eventName: row.eventName,
        divisionId: row.divisionId,
        divisionName: row.divisionName,
        poolId: row.poolId,
        poolName: row.poolName,
        stageName: row.stageName,
        teamPublicId: row.teamPublicId,
        teamName: row.teamName,
        rank: index + 1,
        wins: row.wins,
        losses: row.losses,
        ties: row.ties,
        gamesPlayed: row.gamesPlayed,
        pointsFor: row.pointsFor,
        pointsAgainst: row.pointsAgainst,
        pointDifferential: row.pointDifferential,
      })),
  );
}

function groupStandings(standings: StandingRow[]) {
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

function formatWinPct(row: StandingRow) {
  if (row.gamesPlayed === 0) {
    return "0.000";
  }

  return ((row.wins + row.ties * 0.5) / row.gamesPlayed).toFixed(3);
}

export default async function StandingsPage() {
  const provider = getCompetitionProvider();
  const competitionEventSlug = await getCompetitionEventSlugByLocalSlug();
  const standings = await provider.getStandings({
    event: competitionEventSlug,
    limit: 500,
  });

  const scheduleStandings =
    standings.length === 0
      ? buildScheduleStandings(
          await provider.getSchedule({
            event: competitionEventSlug,
            status: "all",
            limit: 500,
          }),
        )
      : [];

  const grouped = groupStandings(standings.length > 0 ? standings : scheduleStandings);

  return (
    <>
      <PageHero
        eyebrow="Standings"
        title="Standings"
        description="Pool tables update from the competition feed as games are approved through the event workflow."
      />
      <section className="mx-auto max-w-6xl px-6 pb-20 lg:px-10">
        {grouped.length ? (
          <div className="grid gap-6">
            {grouped.map((pool) => (
              <Card className="overflow-hidden p-0" key={`${pool[0]?.divisionName}-${pool[0]?.poolName}`}>
                <div className="border-b border-black/10 bg-white/50 px-5 py-4">
                  <h2 className="text-xl font-semibold tracking-tight text-[var(--foreground)]">
                    {pool[0]?.divisionName ?? "Division"} · {pool[0]?.poolName ?? "Pool"}
                  </h2>
                </div>
                <div className="overflow-x-auto">
                  <table className="w-full min-w-[720px] border-collapse text-left text-sm">
                    <thead className="bg-[var(--surface)] text-xs font-semibold uppercase tracking-[0.12em] text-[var(--muted-foreground)]">
                      <tr>
                        <th className="px-4 py-3">Rank</th>
                        <th className="px-4 py-3">Team</th>
                        <th className="px-4 py-3 text-right">GP</th>
                        <th className="px-4 py-3 text-right">W</th>
                        <th className="px-4 py-3 text-right">L</th>
                        <th className="px-4 py-3 text-right">T</th>
                        <th className="px-4 py-3 text-right">PF</th>
                        <th className="px-4 py-3 text-right">PA</th>
                        <th className="px-4 py-3 text-right">+/-</th>
                        <th className="px-4 py-3 text-right">Win %</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-black/10">
                      {pool.map((row) => (
                        <tr key={row.teamPublicId}>
                          <td className="whitespace-nowrap px-4 py-3 font-semibold">{row.rank}</td>
                          <td className="px-4 py-3 font-semibold">{row.teamName}</td>
                          <td className="px-4 py-3 text-right">{row.gamesPlayed}</td>
                          <td className="px-4 py-3 text-right">{row.wins}</td>
                          <td className="px-4 py-3 text-right">{row.losses}</td>
                          <td className="px-4 py-3 text-right">{row.ties}</td>
                          <td className="px-4 py-3 text-right">{row.pointsFor}</td>
                          <td className="px-4 py-3 text-right">{row.pointsAgainst}</td>
                          <td className="px-4 py-3 text-right">{row.pointDifferential}</td>
                          <td className="px-4 py-3 text-right">{formatWinPct(row)}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </Card>
            ))}
          </div>
        ) : (
          <EmptyState
            description="Standings will appear here after pool or standings data is available from Hoops Scorebook."
            title="No standings available yet"
          />
        )}
        <div className="mt-8">
          <CompetitionPoweredNote />
        </div>
      </section>
    </>
  );
}
