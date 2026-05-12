import Link from "next/link";
import { Download } from "lucide-react";

import { CompetitionPoweredNote } from "@/components/marketing/competition-powered-note";
import { PageHero } from "@/components/marketing/page-hero";
import { EmptyState } from "@/components/states/empty-state";
import { Card } from "@/components/ui/card";
import { getCompetitionProvider } from "@/lib/competition";
import {
  buildPlayoffSlotLabelMap,
  formatPublicMatchup,
  shouldHoldPlayoffAssignment,
} from "@/lib/competition/playoff-presentation";
import { getCompetitionEventSlugByLocalSlug } from "@/lib/db/queries/content";

const tournamentTimeZone = "America/Halifax";

function formatScheduleDate(date: string) {
  return new Intl.DateTimeFormat("en-CA", {
    month: "short",
    day: "numeric",
    weekday: "short",
    timeZone: tournamentTimeZone,
  }).format(new Date(date));
}

function formatScheduleTime(date: string) {
  return new Intl.DateTimeFormat("en-CA", {
    hour: "numeric",
    minute: "2-digit",
    timeZone: tournamentTimeZone,
  }).format(new Date(date));
}

function formatStatus(status: string) {
  return status.replaceAll("_", " ");
}

export default async function SchedulePage({
  searchParams,
}: {
  searchParams?: Promise<{ division?: string }>;
}) {
  const params = searchParams ? await searchParams : undefined;
  const selectedDivision = params?.division ?? "all";
  const provider = getCompetitionProvider();
  const competitionEventSlug = await getCompetitionEventSlugByLocalSlug();
  const [rawSchedule, playoffBrackets] = await Promise.all([
    provider.getSchedule({
      event: competitionEventSlug,
      status: "all",
      limit: 500,
    }),
    provider.getPlayoffBrackets({
      event: competitionEventSlug,
      workflow: "approved",
      limit: 100,
    }),
  ]);
  const schedule = rawSchedule.sort((a, b) => new Date(a.scheduledAt).getTime() - new Date(b.scheduledAt).getTime());
  const playoffSlotLabelMap = buildPlayoffSlotLabelMap(playoffBrackets);

  const divisions = Array.from(
    schedule.reduce((map, game) => {
      if (game.divisionId && game.divisionName) {
        map.set(String(game.divisionId), game.divisionName);
      }

      return map;
    }, new Map<string, string>()),
  ).sort(([, a], [, b]) => a.localeCompare(b));

  const filteredSchedule =
    selectedDivision === "all" ? schedule : schedule.filter((game) => String(game.divisionId) === selectedDivision);

  const selectedDivisionName =
    selectedDivision === "all" ? "All divisions" : divisions.find(([id]) => id === selectedDivision)?.[1] ?? "Division";

  return (
    <>
      <PageHero
        eyebrow="Schedule"
        title="Game schedule"
        description="Browse the full Spring Shootout schedule from Hoops Scorebook, with division filters for faster scanning."
      />
      <section className="mx-auto max-w-6xl px-6 pb-20 lg:px-10">
        <div className="mt-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
          <div className="flex flex-wrap items-center gap-2">
            <Link
              className={`rounded-full border px-4 py-2 text-sm font-semibold transition ${
                selectedDivision === "all"
                  ? "border-[var(--accent)] bg-[var(--accent)] text-[var(--accent-foreground)]"
                  : "border-[var(--border-strong)] bg-white/60 text-[var(--foreground)] hover:bg-white"
              }`}
              href="/schedule"
            >
              All divisions
            </Link>
            {divisions.map(([id, name]) => (
              <Link
                className={`rounded-full border px-4 py-2 text-sm font-semibold transition ${
                  selectedDivision === id
                    ? "border-[var(--accent)] bg-[var(--accent)] text-[var(--accent-foreground)]"
                    : "border-[var(--border-strong)] bg-white/60 text-[var(--foreground)] hover:bg-white"
                }`}
                href={`/schedule?division=${encodeURIComponent(id)}`}
                key={id}
              >
                {name}
              </Link>
            ))}
          </div>
          <a
            className="inline-flex shrink-0 items-center justify-center gap-2 rounded-full border border-[var(--accent)] bg-[var(--accent)] px-5 py-2.5 text-sm font-semibold text-[var(--accent-foreground)] transition hover:opacity-90"
            download
            href="/schedule/pdf"
          >
            <Download aria-hidden="true" size={16} strokeWidth={2.25} />
            Schedule PDF
          </a>
        </div>
        <div className="mt-4 text-sm font-semibold text-[var(--muted-foreground)]">
          {filteredSchedule.length} {filteredSchedule.length === 1 ? "game" : "games"} · {selectedDivisionName}
        </div>

        {filteredSchedule.length > 0 ? (
          <Card className="mt-4 overflow-hidden p-0">
            <div className="hidden overflow-x-auto md:block">
              <table className="w-full border-collapse text-left text-sm">
                <thead className="bg-[var(--surface)] text-xs font-semibold uppercase tracking-[0.12em] text-[var(--muted-foreground)]">
                  <tr>
                    <th className="px-4 py-3">Date</th>
                    <th className="px-4 py-3">Time</th>
                    <th className="px-4 py-3">Division</th>
                    <th className="px-4 py-3">Matchup</th>
                    <th className="px-4 py-3">Venue</th>
                    <th className="px-4 py-3">Stage</th>
                    <th className="px-4 py-3">Status</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-black/10">
                  {filteredSchedule.map((game) => {
                    const holdAssignment = shouldHoldPlayoffAssignment({
                      game,
                      schedule,
                      slotLabelMap: playoffSlotLabelMap,
                    });
                    const matchup = formatPublicMatchup({
                      game,
                      holdAssignment,
                      slotLabelMap: playoffSlotLabelMap,
                    });

                    return (
                      <tr className="align-top hover:bg-black/[0.025]" key={game.gamePublicId}>
                        <td className="whitespace-nowrap px-4 py-3 font-semibold">{formatScheduleDate(game.scheduledAt)}</td>
                        <td className="whitespace-nowrap px-4 py-3">{formatScheduleTime(game.scheduledAt)}</td>
                        <td className="whitespace-nowrap px-4 py-3">{game.divisionName ?? "TBD"}</td>
                        <td className="px-4 py-3 font-semibold">
                          {holdAssignment ? (
                            <span>{matchup}</span>
                          ) : (
                            <Link className="underline-offset-4 hover:underline" href={`/games/${game.gamePublicId}`}>
                              {matchup}
                            </Link>
                          )}
                          {holdAssignment ? (
                            <div className="mt-1 text-xs font-semibold uppercase tracking-[0.12em] text-[var(--muted-foreground)]">
                              Pending pool results
                            </div>
                          ) : null}
                        </td>
                        <td className="px-4 py-3">{game.venue ?? "Venue pending"}</td>
                        <td className="px-4 py-3 text-[var(--muted-foreground)]">{game.poolName ?? game.stageName ?? "TBD"}</td>
                        <td className="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-[0.12em] text-[var(--muted-foreground)]">
                          {formatStatus(game.status)}
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>

            <div className="divide-y divide-black/10 md:hidden">
              {filteredSchedule.map((game) => {
                const holdAssignment = shouldHoldPlayoffAssignment({
                  game,
                  schedule,
                  slotLabelMap: playoffSlotLabelMap,
                });
                const matchup = formatPublicMatchup({
                  game,
                  holdAssignment,
                  slotLabelMap: playoffSlotLabelMap,
                });

                return (
                  <div className="grid gap-2 p-4" key={game.gamePublicId}>
                    <div className="flex items-start justify-between gap-3">
                      {holdAssignment ? (
                        <div>
                          <div className="font-semibold">{matchup}</div>
                          <div className="mt-1 text-xs font-semibold uppercase tracking-[0.12em] text-[var(--muted-foreground)]">
                            Pending pool results
                          </div>
                        </div>
                      ) : (
                        <Link className="font-semibold underline-offset-4 hover:underline" href={`/games/${game.gamePublicId}`}>
                          {matchup}
                        </Link>
                      )}
                      <div className="shrink-0 text-right text-xs font-semibold uppercase tracking-[0.12em] text-[var(--muted-foreground)]">
                        {formatStatus(game.status)}
                      </div>
                    </div>
                    <div className="text-sm text-[var(--muted-foreground)]">
                      {formatScheduleDate(game.scheduledAt)} · {formatScheduleTime(game.scheduledAt)} ·{" "}
                      {game.divisionName ?? "Division TBD"}
                    </div>
                    <div className="text-sm text-[var(--muted-foreground)]">
                      {game.venue ?? "Venue pending"} · {game.poolName ?? game.stageName ?? "Stage TBD"}
                    </div>
                  </div>
                );
              })}
            </div>
          </Card>
        ) : (
          <div className="mt-4">
            <EmptyState
              description="Try another division or clear the filter to see the full event feed."
              title="No games match this filter"
            />
          </div>
        )}
        <div className="mt-8">
          <CompetitionPoweredNote />
        </div>
      </section>
    </>
  );
}
