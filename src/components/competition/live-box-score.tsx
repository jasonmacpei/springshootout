"use client";

import Link from "next/link";
import { Activity, AlertCircle, CalendarClock, Clock3, MapPin, Radio } from "lucide-react";
import { useEffect, useMemo, useState } from "react";

import { PageHero } from "@/components/marketing/page-hero";
import { Badge } from "@/components/ui/badge";
import { Card, CardDescription, CardTitle } from "@/components/ui/card";
import type { CompetitionGameDetail } from "@/lib/competition/schemas";

type LiveBoxScoreProps = {
  gamePublicId: string;
  initialBoxScore: CompetitionGameDetail | null;
  initialError?: boolean;
};

type ConnectionState = "idle" | "updating" | "connection" | "unavailable";
type GameSnapshot = CompetitionGameDetail["game"];
type GameEvent = CompetitionGameDetail["recentEvents"][number];
type PlayerLine = CompetitionGameDetail["playerLinesByTeam"][number]["players"][number];

const livePollIntervalMs = 7_000;
const waitingPollIntervalMs = 30_000;
const tickIntervalMs = 200;

const atlanticDateTimeFormatter = new Intl.DateTimeFormat("en-CA", {
  month: "short",
  day: "numeric",
  hour: "numeric",
  minute: "2-digit",
  timeZone: "America/Halifax",
  timeZoneName: "short",
});

const atlanticShortTimeFormatter = new Intl.DateTimeFormat("en-CA", {
  hour: "numeric",
  minute: "2-digit",
  second: "2-digit",
  timeZone: "America/Halifax",
});

function formatStatus(status: string) {
  return status.replaceAll("_", " ");
}

function isLiveStatus(status: string | undefined) {
  return status === "in_progress" || status === "live";
}

function isFinalStatus(status: string | undefined) {
  return status === "final" || status === "finalized" || status === "complete";
}

function getDisplayDeciseconds(game: GameSnapshot | null) {
  if (!game?.usesGameClock) {
    return null;
  }

  const base =
    typeof game.clockDecisecondsRemaining === "number"
      ? game.clockDecisecondsRemaining
      : typeof game.clockSecondsRemaining === "number"
        ? game.clockSecondsRemaining * 10
        : null;

  if (base === null) {
    return null;
  }

  if (isFinalStatus(game.status) || !game.isClockRunning || !game.clockSyncedAt) {
    return Math.max(0, base);
  }

  const parsedSyncedAtMs = new Date(game.clockSyncedAt).getTime();
  const syncedAtMs = Number.isFinite(parsedSyncedAtMs)
    ? parsedSyncedAtMs
    : new Date(game.clockSyncedAt.replace(" ", "T")).getTime();
  if (!Number.isFinite(syncedAtMs)) {
    return Math.max(0, base);
  }

  const elapsedDeciseconds = Math.floor((Date.now() - syncedAtMs) / 100);
  return Math.max(0, base - elapsedDeciseconds);
}

function formatClockFromDeciseconds(deciseconds: number | null) {
  if (deciseconds === null) {
    return "--:--";
  }

  const totalSeconds = Math.ceil(deciseconds / 10);
  const minutes = Math.floor(totalSeconds / 60);
  const seconds = totalSeconds % 60;
  return `${String(minutes).padStart(2, "0")}:${String(seconds).padStart(2, "0")}`;
}

function formatScore(score: number | null | undefined) {
  return score ?? "-";
}

function formatScheduledAt(value: string) {
  return atlanticDateTimeFormatter.format(new Date(value));
}

function formatRecordedTime(value: string) {
  return atlanticShortTimeFormatter.format(new Date(value));
}

function formatMinutes(seconds: number | null | undefined) {
  if (typeof seconds !== "number") {
    return null;
  }

  const bounded = Math.max(0, Math.floor(seconds));
  const minutes = Math.floor(bounded / 60);
  const remainder = String(bounded % 60).padStart(2, "0");
  return `${minutes}:${remainder}`;
}

function formatPlayerDisplay(player: PlayerLine) {
  if (player.jerseyNumber === null || player.jerseyNumber === undefined) {
    return player.playerName;
  }

  return `#${player.jerseyNumber} ${player.playerName}`;
}

function formatEventLabel(event: GameEvent) {
  if (event.eventType.startsWith("score_")) {
    const pointValue = event.points ?? event.eventType.replace("score_", "");
    return `${pointValue}-point score`;
  }

  return formatStatus(event.eventType);
}

function formatPlayerName(event: GameEvent) {
  const name = [event.playerFirstName, event.playerLastName].filter(Boolean).join(" ");
  return name || null;
}

function formatEventSummary(event: GameEvent) {
  const team = event.teamName ?? "Team";
  const player = formatPlayerName(event);

  if (event.eventType.startsWith("score_")) {
    return player ? `${player} scored for ${team}.` : `${team} scored.`;
  }

  return player ? `${team} · ${player}` : team;
}

function getStatusBadgeClass(status: string | undefined) {
  if (isLiveStatus(status)) {
    return "border-emerald-200 bg-emerald-50 text-emerald-800";
  }

  if (isFinalStatus(status)) {
    return "border-sky-200 bg-sky-50 text-sky-800";
  }

  return "border-amber-200 bg-amber-50 text-amber-800";
}

function getPollInterval(status: string | undefined) {
  if (isFinalStatus(status)) {
    return null;
  }

  return isLiveStatus(status) ? livePollIntervalMs : waitingPollIntervalMs;
}

function playerTableColumns(players: PlayerLine[]) {
  return {
    showMinutes: players.some((player) => typeof player.secondsPlayed === "number"),
    showPlusMinus: players.some((player) => typeof player.plusMinus === "number"),
  };
}

export function LiveBoxScore({ gamePublicId, initialBoxScore, initialError = false }: LiveBoxScoreProps) {
  const [boxScore, setBoxScore] = useState(initialBoxScore);
  const [connectionState, setConnectionState] = useState<ConnectionState>(
    initialBoxScore ? "idle" : initialError ? "connection" : "unavailable",
  );
  const [displayDeciseconds, setDisplayDeciseconds] = useState(() =>
    getDisplayDeciseconds(initialBoxScore?.game ?? null),
  );

  const game = boxScore?.game ?? null;
  const statusLabel = game ? (isLiveStatus(game.status) ? "LIVE" : isFinalStatus(game.status) ? "FINAL" : formatStatus(game.status)) : "Box score";
  const venueLabel = game?.court ? `${game.venue ?? "Venue pending"} · ${game.court}` : (game?.venue ?? "Venue pending");
  const detailLabel = [game?.divisionName, game?.poolName, game?.stageName].filter(Boolean).join(" · ");
  const hasPlayerLines = Boolean(boxScore?.playerLinesByTeam.some((team) => team.players.length > 0));
  const timelineEvents = useMemo(
    () => [...(boxScore?.recentEvents ?? [])].sort((left, right) => right.eventSequence - left.eventSequence),
    [boxScore?.recentEvents],
  );

  useEffect(() => {
    setDisplayDeciseconds(getDisplayDeciseconds(game));

    if (!game?.usesGameClock || !game.isClockRunning || isFinalStatus(game.status)) {
      return;
    }

    const interval = window.setInterval(() => {
      setDisplayDeciseconds(getDisplayDeciseconds(game));
    }, tickIntervalMs);

    return () => window.clearInterval(interval);
  }, [game]);

  useEffect(() => {
    let ignore = false;
    let intervalId: number | null = null;

    async function poll() {
      if (document.hidden) {
        return;
      }

      if (boxScore) {
        setConnectionState("updating");
      }

      try {
        const response = await fetch(`/api/competition/games/${encodeURIComponent(gamePublicId)}/box-score`, {
          cache: "no-store",
        });

        if (response.status === 404) {
          if (!ignore) {
            setConnectionState(boxScore ? "idle" : "unavailable");
          }
          return;
        }

        if (!response.ok) {
          throw new Error(`Box score request failed: ${response.status}`);
        }

        const nextBoxScore = (await response.json()) as CompetitionGameDetail;
        if (!ignore) {
          setBoxScore(nextBoxScore);
          setConnectionState("idle");
        }
      } catch {
        if (!ignore) {
          setConnectionState("connection");
        }
      }
    }

    function configurePolling() {
      if (intervalId !== null) {
        window.clearInterval(intervalId);
        intervalId = null;
      }

      if (document.hidden) {
        return;
      }

      const intervalMs = getPollInterval(boxScore?.game.status);
      if (intervalMs !== null) {
        intervalId = window.setInterval(poll, intervalMs);
      }
    }

    function handleVisibilityChange() {
      if (!document.hidden) {
        void poll();
      }

      configurePolling();
    }

    configurePolling();
    document.addEventListener("visibilitychange", handleVisibilityChange);

    return () => {
      ignore = true;
      if (intervalId !== null) {
        window.clearInterval(intervalId);
      }
      document.removeEventListener("visibilitychange", handleVisibilityChange);
    };
  }, [boxScore, gamePublicId]);

  return (
    <>
      <PageHero
        eyebrow="Game box score"
        title={game ? `${game.homeTeamName} vs ${game.awayTeamName}` : "Game box score"}
        description={
          game
            ? "Live score, game clock, player lines, and recent game events from Hoops Scorebook."
            : "Live game details will appear here when the Hoops Scorebook box score is available."
        }
      />

      <section className="mx-auto grid max-w-6xl gap-8 px-6 pb-20 lg:px-10">
        {!boxScore ? (
          <Card className="p-6">
            <div className="flex items-start gap-3">
              <AlertCircle aria-hidden="true" className="mt-1 h-5 w-5 text-[var(--accent)]" />
              <div>
                <CardTitle>
                  {connectionState === "connection" ? "Unable to load box score" : "Box score is not available yet."}
                </CardTitle>
                <CardDescription>
                  {connectionState === "connection"
                    ? "The game page is available, but the live box score endpoint could not be reached."
                    : "This can happen before Hoops Scorebook has published a game snapshot."}
                </CardDescription>
              </div>
            </div>
          </Card>
        ) : (
          <>
            <Card className="overflow-hidden p-0">
              <div className="border-b border-black/8 bg-white px-5 py-5 sm:px-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                  <div className="flex flex-wrap items-center gap-2">
                    <Badge className={getStatusBadgeClass(game?.status)}>
                      {isLiveStatus(game?.status) ? <Radio aria-hidden="true" className="mr-2 h-3.5 w-3.5" /> : null}
                      {statusLabel}
                    </Badge>
                    {detailLabel ? (
                      <span className="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--muted-foreground)]">
                        {detailLabel}
                      </span>
                    ) : null}
                  </div>
                  <span className="font-mono text-xs font-semibold text-[var(--muted-foreground)]">
                    {connectionState === "connection"
                      ? "Connection issue"
                      : connectionState === "updating"
                        ? "Updating..."
                        : boxScore.generatedAt
                          ? `Updated ${formatRecordedTime(boxScore.generatedAt)}`
                          : "Live snapshot"}
                  </span>
                </div>
                <CardTitle className="mt-5 text-2xl font-black">Box score</CardTitle>
                <CardDescription>
                  {venueLabel} · {game ? formatScheduledAt(game.scheduledAt) : "Schedule pending"}
                </CardDescription>
              </div>

              <div className="grid divide-y divide-black/8 md:grid-cols-[1fr_auto_1fr] md:divide-x md:divide-y-0">
                <div className="px-5 py-6 sm:px-6">
                  <div className="text-xs font-bold uppercase tracking-[0.16em] text-[var(--muted-foreground)]">Home</div>
                  <div className="mt-2 text-2xl font-black text-[var(--foreground)]">{game?.homeTeamName}</div>
                  <div className="mt-4 font-mono text-6xl font-black tabular-nums text-[var(--foreground)]">
                    {formatScore(game?.homeScore)}
                  </div>
                </div>

                <div className="flex min-w-44 flex-col items-center justify-center gap-2 bg-[var(--surface)] px-5 py-6 text-center sm:px-6">
                  <div className="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.16em] text-[var(--muted-foreground)]">
                    <Clock3 aria-hidden="true" className="h-4 w-4" />
                    {game?.usesGameClock ? `Period ${game.periodNumber ?? "-"}` : "Game clock"}
                  </div>
                  <div className="font-mono text-4xl font-black tabular-nums text-[var(--foreground)]">
                    {game?.usesGameClock ? formatClockFromDeciseconds(displayDeciseconds) : "No clock"}
                  </div>
                  <div className="text-xs font-semibold uppercase tracking-[0.14em] text-[var(--muted-foreground)]">
                    {isFinalStatus(game?.status) ? "Final" : game?.isClockRunning ? "Running" : "Stopped"}
                  </div>
                </div>

                <div className="px-5 py-6 text-left md:text-right sm:px-6">
                  <div className="text-xs font-bold uppercase tracking-[0.16em] text-[var(--muted-foreground)]">Away</div>
                  <div className="mt-2 text-2xl font-black text-[var(--foreground)]">{game?.awayTeamName}</div>
                  <div className="mt-4 font-mono text-6xl font-black tabular-nums text-[var(--foreground)]">
                    {formatScore(game?.awayScore)}
                  </div>
                </div>
              </div>

              <div className="grid border-t border-black/8 bg-white/70 sm:grid-cols-3">
                <div className="border-b border-black/8 px-5 py-4 sm:border-r sm:border-b-0 sm:px-6">
                  <div className="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.16em] text-[var(--muted-foreground)]">
                    <Activity aria-hidden="true" className="h-4 w-4" />
                    Event
                  </div>
                  <div className="mt-2 text-lg font-black text-[var(--foreground)]">{game?.eventName}</div>
                  <div className="mt-1 text-sm font-medium text-[var(--muted-foreground)]">{game?.eventSlug}</div>
                </div>
                <div className="border-b border-black/8 px-5 py-4 sm:border-r sm:border-b-0 sm:px-6">
                  <div className="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.16em] text-[var(--muted-foreground)]">
                    <CalendarClock aria-hidden="true" className="h-4 w-4" />
                    Scheduled
                  </div>
                  <div className="mt-2 text-lg font-black text-[var(--foreground)]">
                    {game ? formatScheduledAt(game.scheduledAt) : "Pending"}
                  </div>
                  <div className="mt-1 text-sm font-medium text-[var(--muted-foreground)]">{detailLabel || "Division pending"}</div>
                </div>
                <div className="px-5 py-4 sm:px-6">
                  <div className="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.16em] text-[var(--muted-foreground)]">
                    <MapPin aria-hidden="true" className="h-4 w-4" />
                    Location
                  </div>
                  <div className="mt-2 text-lg font-black text-[var(--foreground)]">{venueLabel}</div>
                  <div className="mt-1 text-sm font-medium text-[var(--muted-foreground)]">Official Hoops Scorebook feed</div>
                </div>
              </div>
            </Card>

            <section aria-labelledby="player-lines-heading">
              <div className="mb-4 flex flex-wrap items-end justify-between gap-3">
                <div>
                  <h2 className="text-2xl font-black tracking-tight text-[var(--foreground)]" id="player-lines-heading">
                    Player stats
                  </h2>
                  <p className="mt-1 text-sm font-medium text-[var(--muted-foreground)]">Official player lines grouped by team.</p>
                </div>
                <Badge>{hasPlayerLines ? "Box score" : "No stats"}</Badge>
              </div>

              <div className="grid gap-5 lg:grid-cols-2">
                {boxScore.playerLinesByTeam.length > 0 ? (
                  boxScore.playerLinesByTeam.map((team) => {
                    const columns = playerTableColumns(team.players);

                    return (
                      <Card className="overflow-hidden p-0" key={team.teamId ?? team.teamName}>
                        <div className="border-b border-black/8 px-5 py-4 sm:px-6">
                          <CardTitle>{team.teamName}</CardTitle>
                        </div>
                        {team.players.length > 0 ? (
                          <div className="overflow-x-auto">
                            <table className="w-full min-w-[380px] border-collapse text-left text-sm">
                              <thead className="bg-[var(--surface)]/70 text-xs font-bold uppercase tracking-[0.14em] text-[var(--muted-foreground)]">
                                <tr>
                                  <th className="px-5 py-3 sm:px-6">Player</th>
                                  <th className="px-3 py-3 text-right">PTS</th>
                                  <th className="px-3 py-3 text-right">FLS</th>
                                  {columns.showMinutes ? <th className="px-3 py-3 text-right">MIN</th> : null}
                                  {columns.showPlusMinus ? <th className="px-5 py-3 text-right sm:px-6">+/-</th> : null}
                                </tr>
                              </thead>
                              <tbody className="divide-y divide-black/8">
                                {team.players.map((player) => (
                                  <tr key={player.playerId ?? `${team.teamName}-${player.playerName}`}>
                                    <td className="px-5 py-3 font-semibold text-[var(--foreground)] sm:px-6">
                                      {formatPlayerDisplay(player)}
                                    </td>
                                    <td className="px-3 py-3 text-right font-mono font-black tabular-nums">
                                      {player.points ?? 0}
                                    </td>
                                    <td className="px-3 py-3 text-right font-mono font-black tabular-nums">
                                      {player.fouls ?? 0}
                                    </td>
                                    {columns.showMinutes ? (
                                      <td className="px-3 py-3 text-right font-mono font-black tabular-nums">
                                        {formatMinutes(player.secondsPlayed) ?? "-"}
                                      </td>
                                    ) : null}
                                    {columns.showPlusMinus ? (
                                      <td className="px-5 py-3 text-right font-mono font-black tabular-nums sm:px-6">
                                        {player.plusMinus ?? "-"}
                                      </td>
                                    ) : null}
                                  </tr>
                                ))}
                              </tbody>
                            </table>
                          </div>
                        ) : (
                          <div className="px-5 py-8 sm:px-6">
                            <CardDescription>No player stats yet.</CardDescription>
                          </div>
                        )}
                      </Card>
                    );
                  })
                ) : (
                  <Card className="p-6 lg:col-span-2">
                    <CardTitle>No player stats yet.</CardTitle>
                    <CardDescription>Player lines will appear after Hoops Scorebook publishes them.</CardDescription>
                  </Card>
                )}
              </div>
            </section>

            <section aria-labelledby="recent-events-heading">
              <div className="mb-4 flex flex-wrap items-end justify-between gap-3">
                <div>
                  <h2 className="text-2xl font-black tracking-tight text-[var(--foreground)]" id="recent-events-heading">
                    Recent events
                  </h2>
                  <p className="mt-1 text-sm font-medium text-[var(--muted-foreground)]">Latest scoring and game events from the official feed.</p>
                </div>
                <Badge>{timelineEvents.length} events</Badge>
              </div>

              <Card className="overflow-hidden p-0">
                {timelineEvents.length > 0 ? (
                  <ol>
                    {timelineEvents.map((event) => (
                      <li className="grid gap-4 border-b border-black/8 px-5 py-5 last:border-b-0 sm:grid-cols-[8rem_1fr] sm:px-6" key={event.eventSequence}>
                        <div>
                          <div className="font-mono text-lg font-black tabular-nums text-[var(--foreground)]">
                            {event.periodNumber ? `P${event.periodNumber}` : "Game"}
                          </div>
                          <time className="mt-1 block font-mono text-xs font-semibold text-[var(--muted-foreground)]" dateTime={event.recordedAt}>
                            {formatRecordedTime(event.recordedAt)}
                          </time>
                        </div>
                        <div>
                          <div className="flex flex-wrap items-center gap-2">
                            <span className="text-base font-black text-[var(--foreground)]">{formatEventLabel(event)}</span>
                            {event.points ? (
                              <span className="rounded-full bg-[var(--surface)] px-2.5 py-1 font-mono text-xs font-black text-[var(--foreground)]">
                                +{event.points}
                              </span>
                            ) : null}
                          </div>
                          <p className="mt-1 text-sm font-medium text-[var(--muted-foreground)]">{formatEventSummary(event)}</p>
                        </div>
                      </li>
                    ))}
                  </ol>
                ) : (
                  <div className="px-5 py-8 sm:px-6">
                    <CardTitle>No recent events yet</CardTitle>
                    <CardDescription>Events will appear here after they are recorded in Hoops Scorebook.</CardDescription>
                  </div>
                )}
              </Card>
            </section>

            <div>
              <Link className="text-sm font-semibold text-[var(--accent)] underline-offset-4 hover:underline" href="/results">
                Back to live results
              </Link>
            </div>
          </>
        )}
      </section>
    </>
  );
}
