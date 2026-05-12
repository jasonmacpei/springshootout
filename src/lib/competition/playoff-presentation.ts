import type {
  CompetitionPlayoffBracket,
  CompetitionScoreboardGame,
} from "@/lib/competition/schemas";

export type PlayoffSlotLabels = {
  home?: string | null;
  away?: string | null;
};

export type PlayoffSlotLabelMap = Map<string, PlayoffSlotLabels>;

const completeRoundRobinStatuses = new Set([
  "approved",
  "canceled",
  "cancelled",
  "complete",
  "completed",
  "final",
  "forfeit",
  "forfeited",
  "locked",
]);

const liveStatuses = new Set(["in_progress", "live"]);

function normalizeStatus(status: string) {
  return status.trim().toLowerCase().replaceAll(" ", "_");
}

export function isCompleteRoundRobinStatus(status: string) {
  return completeRoundRobinStatuses.has(normalizeStatus(status));
}

function normalizeStageValue(value?: string | null) {
  return value?.trim().toLowerCase().replaceAll("_", " ") ?? "";
}

function isPlayoffStageValue(stageType: string, stageScope: string, stageName: string) {
  return (
    stageType.includes("playoff") ||
    stageType.includes("bracket") ||
    stageType.includes("championship") ||
    stageType.includes("crossover") ||
    stageScope === "division" ||
    stageName.includes("playoff") ||
    stageName.includes("semi") ||
    stageName.includes("cross") ||
    stageName.includes("championship") ||
    stageName.includes("final") ||
    stageName.includes("place")
  );
}

function getDivisionKey(game: Pick<CompetitionScoreboardGame, "divisionId" | "divisionName">) {
  return String(game.divisionId ?? game.divisionName ?? "division:tbd");
}

function sameDivision(
  a: Pick<CompetitionScoreboardGame, "divisionId" | "divisionName">,
  b: Pick<CompetitionScoreboardGame, "divisionId" | "divisionName">,
) {
  if (a.divisionId != null && b.divisionId != null) {
    return a.divisionId === b.divisionId;
  }

  if (a.divisionName && b.divisionName) {
    return a.divisionName === b.divisionName;
  }

  return getDivisionKey(a) === getDivisionKey(b);
}

export function isRoundRobinGame(
  game: Pick<
    CompetitionScoreboardGame,
    "poolId" | "poolName" | "stageName" | "stageScope" | "stageType"
  >,
) {
  const stageType = normalizeStageValue(game.stageType);
  const stageScope = normalizeStageValue(game.stageScope);
  const stageName = normalizeStageValue(game.stageName);

  if (isPlayoffStageValue(stageType, stageScope, stageName)) {
    return false;
  }

  return (
    stageType === "pool play" ||
    stageScope === "pool" ||
    Boolean(game.poolId || game.poolName) ||
    stageName.includes("pool") ||
    stageName.includes("round robin")
  );
}

export function isPlayoffGame(
  game: Pick<
    CompetitionScoreboardGame,
    "gamePublicId" | "poolId" | "poolName" | "stageName" | "stageScope" | "stageType"
  >,
  slotLabelMap?: PlayoffSlotLabelMap,
) {
  if (slotLabelMap?.has(game.gamePublicId)) {
    return true;
  }

  if (isRoundRobinGame(game)) {
    return false;
  }

  const stageType = normalizeStageValue(game.stageType);
  const stageScope = normalizeStageValue(game.stageScope);
  const stageName = normalizeStageValue(game.stageName);

  return isPlayoffStageValue(stageType, stageScope, stageName);
}

export function isRoundRobinCompleteForDivision(
  schedule: CompetitionScoreboardGame[],
  division: Pick<CompetitionScoreboardGame, "divisionId" | "divisionName">,
) {
  const roundRobinGames = schedule.filter(
    (game) => sameDivision(game, division) && isRoundRobinGame(game),
  );

  if (roundRobinGames.length === 0) {
    return true;
  }

  return roundRobinGames.every((game) => isCompleteRoundRobinStatus(game.status));
}

export function buildPlayoffSlotLabelMap(brackets: CompetitionPlayoffBracket[]) {
  const labels: PlayoffSlotLabelMap = new Map();

  brackets.forEach((bracket) => {
    const definitions = [...bracket.bracketDefinition].sort((a, b) => a.order - b.order);
    const games = [...bracket.games].sort(
      (a, b) => new Date(a.scheduledAt).getTime() - new Date(b.scheduledAt).getTime(),
    );

    games.forEach((game, index) => {
      const definition = definitions[index];
      labels.set(game.gamePublicId, {
        home: game.homeSlotLabel ?? definition?.homeSource ?? null,
        away: game.awaySlotLabel ?? definition?.awaySource ?? null,
      });
    });
  });

  return labels;
}

export function shouldHoldPlayoffAssignment({
  game,
  schedule,
  slotLabelMap,
}: {
  game: CompetitionScoreboardGame;
  schedule: CompetitionScoreboardGame[];
  slotLabelMap?: PlayoffSlotLabelMap;
}) {
  const status = normalizeStatus(game.status);

  if (isCompleteRoundRobinStatus(status) || liveStatuses.has(status)) {
    return false;
  }

  return (
    isPlayoffGame(game, slotLabelMap) &&
    !isRoundRobinCompleteForDivision(schedule, game)
  );
}

export function getPublicSlotName({
  fallback,
  side,
  game,
  holdAssignment,
  slotLabelMap,
}: {
  fallback: string;
  side: "home" | "away";
  game: Pick<
    CompetitionScoreboardGame,
    "awaySlotLabel" | "awayTeamName" | "gamePublicId" | "homeSlotLabel" | "homeTeamName"
  >;
  holdAssignment: boolean;
  slotLabelMap?: PlayoffSlotLabelMap;
}) {
  const teamName = side === "home" ? game.homeTeamName : game.awayTeamName;
  const slotLabel = side === "home" ? game.homeSlotLabel : game.awaySlotLabel;
  const bracketLabel = slotLabelMap?.get(game.gamePublicId)?.[side];

  if (holdAssignment) {
    return bracketLabel ?? slotLabel ?? fallback;
  }

  return teamName || slotLabel || bracketLabel || fallback;
}

export function formatPublicMatchup({
  game,
  holdAssignment,
  slotLabelMap,
}: {
  game: CompetitionScoreboardGame;
  holdAssignment: boolean;
  slotLabelMap?: PlayoffSlotLabelMap;
}) {
  if (game.gameName && !holdAssignment) {
    return game.gameName;
  }

  const home = getPublicSlotName({
    fallback: "Home TBD",
    side: "home",
    game,
    holdAssignment,
    slotLabelMap,
  });
  const away = getPublicSlotName({
    fallback: "Away TBD",
    side: "away",
    game,
    holdAssignment,
    slotLabelMap,
  });

  return `${home} vs ${away}`;
}
