import Link from "next/link";

import type {
  CompetitionPlayoffBracket,
  CompetitionPlayoffGame,
  CompetitionScoreboardGame,
} from "@/lib/competition/schemas";
import {
  buildPlayoffSlotLabelMap,
  getPublicSlotName,
  isRoundRobinCompleteForDivision,
  shouldHoldPlayoffAssignment,
} from "@/lib/competition/playoff-presentation";

const tournamentTimeZone = "America/Halifax";

function formatGameTime(date: string) {
  return new Intl.DateTimeFormat("en-CA", {
    month: "short",
    day: "numeric",
    hour: "numeric",
    minute: "2-digit",
    timeZone: tournamentTimeZone,
    weekday: "short",
  }).format(new Date(date));
}

function formatStatus(status: string) {
  return status.replaceAll("_", " ");
}

function toScoreboardGame(game: CompetitionPlayoffGame, bracket: CompetitionPlayoffBracket): CompetitionScoreboardGame {
  return {
    gameId: game.gameId,
    gamePublicId: game.gamePublicId,
    status: game.status,
    scheduledAt: game.scheduledAt,
    venue: game.venue,
    court: game.court,
    eventSlug: bracket.eventSlug,
    eventName: bracket.eventName,
    divisionId: bracket.divisionId,
    divisionName: bracket.divisionName,
    poolId: null,
    poolName: null,
    stageId: bracket.stageId,
    stagePublicId: bracket.stagePublicId,
    stageName: game.stageName ?? bracket.stageName,
    stageType: bracket.stageType,
    stageScope: bracket.stageScope,
    homeTeamName: game.homeTeamName ?? "",
    homeTeamPublicId: game.homeTeamPublicId,
    homeSlotLabel: game.homeSlotLabel,
    homeScore: game.homeScore,
    awayTeamName: game.awayTeamName ?? "",
    awayTeamPublicId: game.awayTeamPublicId,
    awaySlotLabel: game.awaySlotLabel,
    awayScore: game.awayScore,
  };
}

function groupBracketsByDivision(brackets: CompetitionPlayoffBracket[]) {
  const divisions = brackets.reduce((map, bracket) => {
    const key = String(bracket.divisionId ?? bracket.divisionName ?? "Division");
    const existing = map.get(key) ?? {
      id: key,
      name: bracket.divisionName ?? "Division",
      brackets: [] as CompetitionPlayoffBracket[],
    };

    existing.brackets.push(bracket);
    map.set(key, existing);
    return map;
  }, new Map<string, { id: string; name: string; brackets: CompetitionPlayoffBracket[] }>());

  return Array.from(divisions.values())
    .map((division) => ({
      ...division,
      brackets: division.brackets.sort(
        (a, b) =>
          (a.stageOrder ?? Number.MAX_SAFE_INTEGER) - (b.stageOrder ?? Number.MAX_SAFE_INTEGER) ||
          a.stageName.localeCompare(b.stageName),
      ),
    }))
    .sort((a, b) => a.name.localeCompare(b.name));
}

export function PlayoffBracketSection({
  brackets,
  schedule,
}: {
  brackets: CompetitionPlayoffBracket[];
  schedule: CompetitionScoreboardGame[];
}) {
  if (brackets.length === 0) {
    return null;
  }

  const slotLabelMap = buildPlayoffSlotLabelMap(brackets);
  const divisions = groupBracketsByDivision(brackets);

  return (
    <section className="mt-14">
      <div className="mb-5 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <p className="text-sm font-semibold uppercase tracking-[0.12em] text-[var(--accent)]">
            Playoff Brackets
          </p>
          <h2 className="text-2xl font-semibold tracking-tight text-[var(--foreground)]">
            Crossovers and championships
          </h2>
        </div>
        <p className="max-w-2xl text-sm leading-6 text-[var(--muted-foreground)]">
          Matchups stay as seed placeholders until that division&apos;s round-robin games are complete.
        </p>
      </div>

      <div className="grid gap-8">
        {divisions.map((division) => {
          const representativeBracket = division.brackets[0];
          const roundRobinComplete = isRoundRobinCompleteForDivision(schedule, {
            divisionId: representativeBracket?.divisionId ?? null,
            divisionName: representativeBracket?.divisionName ?? division.name,
          });

          return (
            <div key={division.id}>
              <div className="mb-3 flex flex-wrap items-center gap-3">
                <h3 className="text-xl font-semibold tracking-tight text-[var(--foreground)]">
                  {division.name}
                </h3>
                <span className="rounded-full border border-black/10 bg-white/70 px-3 py-1 text-xs font-semibold uppercase tracking-[0.12em] text-[var(--muted-foreground)]">
                  {roundRobinComplete ? "Bracket set" : "Pending pool results"}
                </span>
              </div>

              <div className="overflow-x-auto pb-2">
                <div className="grid min-w-[760px] auto-cols-[minmax(260px,1fr)] grid-flow-col gap-4">
                  {division.brackets.map((bracket) => (
                    <div
                      className="rounded-[18px] border border-black/10 bg-white/75 p-4 shadow-[0_10px_35px_rgba(20,33,61,0.06)]"
                      key={`${bracket.stagePublicId ?? bracket.stageId ?? bracket.stageName}`}
                    >
                      <div className="mb-4">
                        <p className="text-xs font-semibold uppercase tracking-[0.12em] text-[var(--muted-foreground)]">
                          {bracket.stageName}
                        </p>
                      </div>

                      <div className="grid gap-3">
                        {bracket.games.map((game) => {
                          const displayGame = toScoreboardGame(game, bracket);
                          const holdAssignment = shouldHoldPlayoffAssignment({
                            game: displayGame,
                            schedule,
                            slotLabelMap,
                          });
                          const home = getPublicSlotName({
                            fallback: "Home TBD",
                            side: "home",
                            game: displayGame,
                            holdAssignment,
                            slotLabelMap,
                          });
                          const away = getPublicSlotName({
                            fallback: "Away TBD",
                            side: "away",
                            game: displayGame,
                            holdAssignment,
                            slotLabelMap,
                          });
                          const gameCard = (
                            <>
                              <div className="flex items-center justify-between gap-3 border-b border-black/10 pb-2 text-xs font-semibold uppercase tracking-[0.12em] text-[var(--muted-foreground)]">
                                <span>{formatGameTime(game.scheduledAt)}</span>
                                <span>{holdAssignment ? "Pending" : formatStatus(game.status)}</span>
                              </div>
                              <div className="mt-3 grid gap-2 text-sm">
                                <div className="flex items-center justify-between gap-3">
                                  <span className="font-semibold">{home}</span>
                                  <span className="tabular-nums text-[var(--muted-foreground)]">
                                    {holdAssignment ? "-" : game.homeScore ?? "-"}
                                  </span>
                                </div>
                                <div className="flex items-center justify-between gap-3">
                                  <span className="font-semibold">{away}</span>
                                  <span className="tabular-nums text-[var(--muted-foreground)]">
                                    {holdAssignment ? "-" : game.awayScore ?? "-"}
                                  </span>
                                </div>
                              </div>
                              <p className="mt-3 text-xs leading-5 text-[var(--muted-foreground)]">
                                {game.venue ?? "Venue pending"}
                              </p>
                            </>
                          );

                          if (holdAssignment) {
                            return (
                              <div
                                className="block rounded-xl border border-black/10 bg-[var(--background)] p-3"
                                key={game.gamePublicId}
                              >
                                {gameCard}
                              </div>
                            );
                          }

                          return (
                            <Link
                              className="block rounded-xl border border-black/10 bg-[var(--background)] p-3 transition hover:border-[var(--accent)]"
                              href={`/games/${game.gamePublicId}`}
                              key={game.gamePublicId}
                            >
                              {gameCard}
                            </Link>
                          );
                        })}
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          );
        })}
      </div>
    </section>
  );
}
