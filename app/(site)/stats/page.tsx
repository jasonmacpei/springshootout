import { Activity, AlertTriangle, Award, ShieldAlert, Trophy, UsersRound } from "lucide-react";
import type { Metadata } from "next";

import { CompetitionPoweredNote } from "@/components/marketing/competition-powered-note";
import { EmptyState } from "@/components/states/empty-state";
import { Badge } from "@/components/ui/badge";
import { Card, CardDescription, CardTitle } from "@/components/ui/card";
import { getTournamentStatsFeed, type DivisionStats, type TournamentPlayerStat } from "@/lib/competition/stats";

export const dynamic = "force-dynamic";

export const metadata: Metadata = {
  title: "Stats | Spring Shootout",
  description: "Spring Shootout player stat leaders.",
};

const atlanticDateTimeFormatter = new Intl.DateTimeFormat("en-CA", {
  month: "short",
  day: "numeric",
  hour: "numeric",
  minute: "2-digit",
  timeZone: "America/Halifax",
  timeZoneName: "short",
});

function formatGeneratedAt(value: string) {
  return atlanticDateTimeFormatter.format(new Date(value));
}

function formatDecimal(value: number) {
  return value.toFixed(1);
}

function formatPlayerName(player: TournamentPlayerStat) {
  return player.jerseyNumber === null ? player.playerName : `#${player.jerseyNumber} ${player.playerName}`;
}

function LeaderTable({
  emptyLabel,
  leaders,
  metric,
  title,
}: {
  emptyLabel: string;
  leaders: TournamentPlayerStat[];
  metric: "points" | "fouls";
  title: string;
}) {
  const averageKey = metric === "points" ? "pointsPerGame" : "foulsPerGame";
  const valueLabel = metric === "points" ? "PTS" : "FLS";
  const averageLabel = metric === "points" ? "PPG" : "FPG";

  return (
    <Card className="overflow-hidden p-0">
      <div className="flex items-center justify-between gap-3 border-b border-black/8 bg-white/60 px-5 py-4">
        <CardTitle>{title}</CardTitle>
        <Badge>{valueLabel}</Badge>
      </div>
      {leaders.length ? (
        <div className="overflow-x-auto">
          <table className="w-full min-w-[520px] border-collapse text-left text-sm">
            <thead className="bg-[var(--surface)]/70 text-xs font-bold uppercase tracking-[0.14em] text-[var(--muted-foreground)]">
              <tr>
                <th className="w-14 px-5 py-3">Rank</th>
                <th className="px-3 py-3">Player</th>
                <th className="px-3 py-3">Team</th>
                <th className="px-3 py-3 text-right">GP</th>
                <th className="px-3 py-3 text-right">{valueLabel}</th>
                <th className="px-5 py-3 text-right">{averageLabel}</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-black/8">
              {leaders.map((player, index) => (
                <tr key={player.key}>
                  <td className="px-5 py-3 font-mono text-base font-black tabular-nums">{index + 1}</td>
                  <td className="px-3 py-3 font-semibold text-[var(--foreground)]">{formatPlayerName(player)}</td>
                  <td className="px-3 py-3 text-[var(--muted-foreground)]">{player.teamName}</td>
                  <td className="px-3 py-3 text-right font-mono font-black tabular-nums">{player.gamesPlayed}</td>
                  <td className="px-3 py-3 text-right font-mono text-base font-black tabular-nums">
                    {player[metric]}
                  </td>
                  <td className="px-5 py-3 text-right font-mono font-black tabular-nums">
                    {formatDecimal(player[averageKey])}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      ) : (
        <div className="px-5 py-8">
          <CardDescription>{emptyLabel}</CardDescription>
        </div>
      )}
    </Card>
  );
}

function DivisionSection({ division }: { division: DivisionStats }) {
  const topScorer = division.pointsLeaders[0];
  const foulLeader = division.foulsLeaders[0];

  return (
    <section aria-labelledby={`division-${division.key}`} className="grid gap-5">
      <Card className="overflow-hidden p-0">
        <div className="grid gap-4 border-b border-black/8 bg-white/70 px-5 py-5 md:grid-cols-[1fr_auto] md:items-center">
          <div>
            <Badge>{division.statGameCount} stat games</Badge>
            <h2 className="mt-3 text-2xl font-black text-[var(--foreground)]" id={`division-${division.key}`}>
              {division.divisionName}
            </h2>
          </div>
          <div className="grid grid-cols-2 gap-3 sm:min-w-96">
            <div className="rounded-2xl border border-black/8 bg-[var(--surface)]/65 px-4 py-3">
              <div className="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.14em] text-[var(--muted-foreground)]">
                <Trophy aria-hidden="true" className="h-4 w-4" />
                Points
              </div>
              <p className="mt-2 truncate text-sm font-black text-[var(--foreground)]">
                {topScorer ? formatPlayerName(topScorer) : "Pending"}
              </p>
              <p className="mt-1 font-mono text-xl font-black tabular-nums">{topScorer?.points ?? 0}</p>
            </div>
            <div className="rounded-2xl border border-black/8 bg-[var(--surface)]/65 px-4 py-3">
              <div className="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.14em] text-[var(--muted-foreground)]">
                <ShieldAlert aria-hidden="true" className="h-4 w-4" />
                Fouls
              </div>
              <p className="mt-2 truncate text-sm font-black text-[var(--foreground)]">
                {foulLeader ? formatPlayerName(foulLeader) : "Pending"}
              </p>
              <p className="mt-1 font-mono text-xl font-black tabular-nums">{foulLeader?.fouls ?? 0}</p>
            </div>
          </div>
        </div>
        <div className="grid gap-5 p-5 xl:grid-cols-2">
          <LeaderTable
            emptyLabel="Point leaders will appear after box-score player lines are recorded."
            leaders={division.pointsLeaders}
            metric="points"
            title="Points leaders"
          />
          <LeaderTable
            emptyLabel="Foul leaders will appear after fouls are recorded."
            leaders={division.foulsLeaders}
            metric="fouls"
            title="Foul leaders"
          />
        </div>
      </Card>
    </section>
  );
}

export default async function StatsPage() {
  const feed = await getTournamentStatsFeed();

  return (
    <>
      <section className="mx-auto max-w-7xl px-6 pt-16 pb-10 lg:px-10">
        <Badge>Stats</Badge>
        <div className="mt-6 grid gap-6 lg:grid-cols-[1.4fr_1fr] lg:items-end">
          <div>
            <h1 className="max-w-4xl text-5xl font-black uppercase text-[var(--foreground)] sm:text-6xl">
              Player leaders
            </h1>
            <p className="mt-5 max-w-2xl text-base leading-8 text-[var(--muted-foreground)] sm:text-lg">
              Points and fouls leaders by division from the official Hoops Scorebook box-score feed.
            </p>
          </div>
          <Card className="grid gap-4 p-5 sm:grid-cols-3 lg:grid-cols-1">
            <div>
              <div className="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.14em] text-[var(--muted-foreground)]">
                <Activity aria-hidden="true" className="h-4 w-4" />
                Updated
              </div>
              <p className="mt-2 text-sm font-black text-[var(--foreground)]">{formatGeneratedAt(feed.generatedAt)}</p>
            </div>
            <div>
              <div className="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.14em] text-[var(--muted-foreground)]">
                <Award aria-hidden="true" className="h-4 w-4" />
                Divisions
              </div>
              <p className="mt-2 font-mono text-2xl font-black tabular-nums text-[var(--foreground)]">
                {feed.divisions.length}
              </p>
            </div>
            <div>
              <div className="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.14em] text-[var(--muted-foreground)]">
                <UsersRound aria-hidden="true" className="h-4 w-4" />
                Players
              </div>
              <p className="mt-2 font-mono text-2xl font-black tabular-nums text-[var(--foreground)]">
                {feed.playerCount}
              </p>
            </div>
          </Card>
        </div>
      </section>

      <section className="mx-auto grid max-w-7xl gap-6 px-6 pb-20 lg:px-10">
        {feed.divisions.length ? (
          feed.divisions.map((division) => <DivisionSection division={division} key={division.key} />)
        ) : (
          <EmptyState
            description="Stats will appear here after Hoops Scorebook publishes tournament games and player lines."
            title="No stats available yet"
          />
        )}
        {feed.statGameCount === 0 && feed.divisions.length > 0 ? (
          <Card className="border-amber-200 bg-amber-50/80">
            <div className="flex gap-3">
              <AlertTriangle aria-hidden="true" className="mt-1 h-5 w-5 text-amber-700" />
              <div>
                <CardTitle>No player lines have been published yet</CardTitle>
                <CardDescription>
                  The divisions are available, but the leaderboard tables need finalized or live box-score player lines.
                </CardDescription>
              </div>
            </div>
          </Card>
        ) : null}
        <CompetitionPoweredNote />
      </section>
    </>
  );
}
