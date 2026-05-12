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

type DisplayGame = {
  game: CompetitionPlayoffGame;
  displayGame: CompetitionScoreboardGame;
  holdAssignment: boolean;
  home: string;
  away: string;
};

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

function getGameTimestamp(game: CompetitionPlayoffGame) {
  return new Date(game.scheduledAt).getTime();
}

function getGameTitle(game: CompetitionPlayoffGame, index: number) {
  const stageName = game.stageName?.trim();

  if (stageName && !stageName.toLowerCase().includes("championship bracket")) {
    return stageName;
  }

  return `Game ${index + 1}`;
}

function isPlaceholderTeamName(name: string) {
  const normalizedName = name.toLowerCase();

  return (
    normalizedName.includes("winner") ||
    normalizedName.includes("championship") ||
    normalizedName.includes("final") ||
    normalizedName.includes("tbd")
  );
}

function getFinalGame(games: DisplayGame[]) {
  const championshipGame =
    [...games].reverse().find(({ game, home, away }) => {
      const text = `${game.stageName ?? ""} ${home} ${away}`.toLowerCase();
      return text.includes("championship") || text.includes("final");
    }) ?? games.at(-1);

  return championshipGame ?? null;
}

function splitBracketGames(games: DisplayGame[]) {
  const sortedGames = [...games].sort((a, b) => getGameTimestamp(a.game) - getGameTimestamp(b.game));
  const finalGame = getFinalGame(sortedGames);

  if (!finalGame || sortedGames.length < 3) {
    return {
      finalGame,
      openingGames: sortedGames.filter((item) => item !== finalGame),
      placementGames: [] as DisplayGame[],
    };
  }

  const remainingGames = sortedGames.filter((item) => item !== finalGame);

  return {
    finalGame,
    openingGames: remainingGames.slice(0, 2),
    placementGames: remainingGames.slice(2),
  };
}

function getWinnerName(game: DisplayGame) {
  if (game.holdAssignment || typeof game.game.homeScore !== "number" || typeof game.game.awayScore !== "number") {
    return "Winner";
  }

  if (game.game.homeScore === game.game.awayScore) {
    return "Winner";
  }

  const winner = game.game.homeScore > game.game.awayScore ? game.home : game.away;

  if (isPlaceholderTeamName(winner)) {
    return "Winner";
  }

  return winner;
}

function inferFinalParticipant(currentName: string, sourceGame?: DisplayGame) {
  if (!sourceGame || !isPlaceholderTeamName(currentName)) {
    return currentName;
  }

  return getWinnerName(sourceGame);
}

function GameNode({
  game,
  label,
  variant = "standard",
}: {
  game: DisplayGame;
  label: string;
  variant?: "standard" | "final";
}) {
  const content = (
    <>
      <div className="flex items-center justify-between gap-3 border-b border-black/10 pb-2 text-xs font-semibold uppercase tracking-[0.12em] text-[var(--muted-foreground)]">
        <span>{label}</span>
        <span>{game.holdAssignment ? "Pending" : formatStatus(game.game.status)}</span>
      </div>
      <div className="mt-2 text-xs font-semibold uppercase tracking-[0.12em] text-[var(--muted-foreground)]">
        {formatGameTime(game.game.scheduledAt)}
      </div>
      <div className="mt-3 grid gap-2 text-sm">
        <div className="flex items-center justify-between gap-3">
          <span className="font-semibold">{game.home}</span>
          <span className="tabular-nums text-[var(--muted-foreground)]">
            {game.holdAssignment ? "-" : game.game.homeScore ?? "-"}
          </span>
        </div>
        <div className="flex items-center justify-between gap-3">
          <span className="font-semibold">{game.away}</span>
          <span className="tabular-nums text-[var(--muted-foreground)]">
            {game.holdAssignment ? "-" : game.game.awayScore ?? "-"}
          </span>
        </div>
      </div>
      <p className="mt-3 text-xs leading-5 text-[var(--muted-foreground)]">
        {game.game.venue ?? "Venue pending"}
      </p>
    </>
  );
  const className =
    variant === "final"
      ? "block rounded-xl border border-[var(--accent)] bg-white p-3 shadow-[0_12px_30px_rgba(20,33,61,0.08)]"
      : "block rounded-xl border border-black/10 bg-[var(--background)] p-3 shadow-[0_8px_22px_rgba(20,33,61,0.04)]";

  if (game.holdAssignment) {
    return <div className={className}>{content}</div>;
  }

  return (
    <Link className={`${className} transition hover:border-[var(--accent)]`} href={`/games/${game.game.gamePublicId}`}>
      {content}
    </Link>
  );
}

function BracketConnectors() {
  return (
    <div className="relative h-[300px]">
      <div className="absolute left-0 top-[22%] h-px w-10 bg-[var(--surface-strong)]" />
      <div className="absolute left-0 top-[78%] h-px w-10 bg-[var(--surface-strong)]" />
      <div className="absolute left-10 top-[22%] h-[56%] w-px bg-[var(--surface-strong)]" />
      <div className="absolute left-10 top-1/2 h-px w-12 bg-[var(--surface-strong)]" />
      <div className="absolute right-0 top-1/2 h-px w-10 bg-[var(--surface-strong)]" />
    </div>
  );
}

function BracketPanel({
  bracket,
  games,
}: {
  bracket: CompetitionPlayoffBracket;
  games: DisplayGame[];
}) {
  const { finalGame, openingGames, placementGames } = splitBracketGames(games);

  if (!finalGame) {
    return null;
  }

  const displayedFinalGame: DisplayGame =
    openingGames.length >= 2
      ? {
          ...finalGame,
          home: inferFinalParticipant(finalGame.home, openingGames[0]),
          away: inferFinalParticipant(finalGame.away, openingGames[1]),
        }
      : finalGame;
  const champion = getWinnerName(displayedFinalGame);

  return (
    <div
      className="rounded-[18px] border border-black/10 bg-white/75 p-4 shadow-[0_10px_35px_rgba(20,33,61,0.06)]"
      key={`${bracket.stagePublicId ?? bracket.stageId ?? bracket.stageName}`}
    >
      <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
        <p className="text-xs font-semibold uppercase tracking-[0.12em] text-[var(--muted-foreground)]">
          {bracket.stageName}
        </p>
        <span className="rounded-full border border-black/10 bg-[var(--background)] px-3 py-1 text-xs font-semibold uppercase tracking-[0.12em] text-[var(--muted-foreground)]">
          {games.length} {games.length === 1 ? "game" : "games"}
        </span>
      </div>

      {openingGames.length >= 2 ? (
        <div className="overflow-x-auto pb-2">
          <div className="grid min-w-[900px] grid-cols-[minmax(280px,1fr)_92px_minmax(280px,1fr)_150px] items-center">
            <div className="grid gap-12">
              {openingGames.slice(0, 2).map((game, index) => (
                <GameNode game={game} key={game.game.gamePublicId} label={`Semifinal ${index + 1}`} />
              ))}
            </div>
            <BracketConnectors />
            <GameNode game={displayedFinalGame} label="Championship" variant="final" />
            <div className="relative flex items-center pl-8">
              <div className="absolute left-0 top-1/2 h-px w-8 bg-[var(--surface-strong)]" />
              <div className="rounded-xl border border-[var(--accent)] bg-[var(--accent)] px-4 py-3 text-sm font-semibold text-[var(--accent-foreground)] shadow-[0_10px_28px_rgba(179,92,54,0.20)]">
                <div className="text-xs uppercase tracking-[0.12em] opacity-80">Champion</div>
                <div className="mt-1">{champion}</div>
              </div>
            </div>
          </div>
        </div>
      ) : (
        <div className="grid gap-3 md:grid-cols-2">
          {games.map((game, index) => (
            <GameNode game={game} key={game.game.gamePublicId} label={getGameTitle(game.game, index)} />
          ))}
        </div>
      )}

      {placementGames.length ? (
        <div className="mt-6 border-t border-black/10 pt-4">
          <p className="mb-3 text-xs font-semibold uppercase tracking-[0.12em] text-[var(--muted-foreground)]">
            Placement games
          </p>
          <div className="grid gap-3 md:grid-cols-2">
            {placementGames.map((game, index) => (
              <GameNode
                game={game}
                key={game.game.gamePublicId}
                label={placementGames.length === 1 ? "Placement game" : `Placement ${index + 1}`}
              />
            ))}
          </div>
        </div>
      ) : null}
    </div>
  );
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

              <div className="grid gap-5">
                {division.brackets.map((bracket) => {
                  const games = bracket.games.map((game) => {
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

                    return {
                      game,
                      displayGame,
                      holdAssignment,
                      home,
                      away,
                    };
                  });

                  return (
                    <BracketPanel
                      bracket={bracket}
                      games={games}
                      key={`${bracket.stagePublicId ?? bracket.stageId ?? bracket.stageName}`}
                    />
                  );
                })}
              </div>
            </div>
          );
        })}
      </div>
    </section>
  );
}
