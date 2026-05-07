"use client";

import Link from "next/link";
import { Activity, Clock, Trophy } from "lucide-react";
import { useEffect, useMemo, useState } from "react";

import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardDescription, CardTitle } from "@/components/ui/card";
import type { LiveResultsFeed } from "@/lib/competition/live-results";
import { formatDateTime } from "@/lib/utils";

const pollIntervalMs = 15_000;

function formatClock(seconds: number | null | undefined) {
  if (seconds === null || seconds === undefined) {
    return null;
  }

  const bounded = Math.max(0, Math.floor(seconds));
  const minutes = Math.floor(bounded / 60);
  const remainder = String(bounded % 60).padStart(2, "0");
  return `${minutes}:${remainder}`;
}

function formatStatus(status: string) {
  return status.replaceAll("_", " ");
}

function formatUpdatedAt(value: string) {
  return new Intl.DateTimeFormat("en-CA", {
    hour: "numeric",
    minute: "2-digit",
    second: "2-digit",
  }).format(new Date(value));
}

export function LiveResultsBoard({ initialFeed }: { initialFeed: LiveResultsFeed }) {
  const [feed, setFeed] = useState(initialFeed);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let ignore = false;

    async function poll() {
      try {
        const response = await fetch("/api/competition/live-results", {
          cache: "no-store",
        });

        if (!response.ok) {
          throw new Error(`Live results request failed: ${response.status}`);
        }

        const nextFeed = (await response.json()) as LiveResultsFeed;
        if (!ignore) {
          setFeed(nextFeed);
          setError(null);
        }
      } catch (pollError) {
        if (!ignore) {
          setError(pollError instanceof Error ? pollError.message : "Live results are temporarily unavailable.");
        }
      }
    }

    const interval = window.setInterval(poll, pollIntervalMs);
    return () => {
      ignore = true;
      window.clearInterval(interval);
    };
  }, []);

  const liveCountLabel = useMemo(() => {
    if (feed.liveGames.length === 1) {
      return "1 live game";
    }

    return `${feed.liveGames.length} live games`;
  }, [feed.liveGames.length]);

  return (
    <div className="grid gap-8">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div className="flex flex-wrap items-center gap-2">
          <Badge className="border-emerald-200 bg-emerald-50 text-emerald-800">
            <Activity aria-hidden="true" className="mr-2 h-3.5 w-3.5" />
            {liveCountLabel}
          </Badge>
          <span className="text-sm font-medium text-[var(--muted-foreground)]">
            Last checked {formatUpdatedAt(feed.generatedAt)}
          </span>
        </div>
        {error ? <span className="text-sm font-semibold text-red-700">Keeping last successful update.</span> : null}
      </div>

      <section aria-labelledby="live-games-heading">
        <div className="mb-4 flex items-center gap-2">
          <Activity aria-hidden="true" className="h-5 w-5 text-emerald-700" />
          <h2 className="text-2xl font-black tracking-tight text-[var(--foreground)]" id="live-games-heading">
            Live now
          </h2>
        </div>

        {feed.liveGames.length > 0 ? (
          <div className="grid gap-4">
            {feed.liveGames.map((game) => {
              const clock = formatClock(game.clockSecondsRemaining);

              return (
                <Card className="border-emerald-200 bg-white p-5" key={game.gamePublicId}>
                  <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="min-w-0 flex-1">
                      <div className="mb-3 flex flex-wrap items-center gap-2">
                        <Badge className="border-emerald-200 bg-emerald-50 text-emerald-800">Live</Badge>
                        <span className="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--muted-foreground)]">
                          {game.divisionName ?? "Division pending"} · {game.poolName ?? game.stageName ?? "Stage pending"}
                        </span>
                      </div>
                      <div className="grid gap-2">
                        <div className="flex items-center justify-between gap-4">
                          <span className="text-lg font-bold text-[var(--foreground)]">{game.homeTeamName}</span>
                          <span className="text-3xl font-black tabular-nums text-[var(--foreground)]">
                            {game.homeScore ?? 0}
                          </span>
                        </div>
                        <div className="flex items-center justify-between gap-4">
                          <span className="text-lg font-bold text-[var(--foreground)]">{game.awayTeamName}</span>
                          <span className="text-3xl font-black tabular-nums text-[var(--foreground)]">
                            {game.awayScore ?? 0}
                          </span>
                        </div>
                      </div>
                      <CardDescription>
                        {game.venue ?? "Venue pending"} · {formatDateTime(game.scheduledAt)}
                      </CardDescription>
                    </div>
                    <div className="flex shrink-0 flex-col items-end gap-3">
                      <div className="rounded-lg border border-black/10 bg-[var(--surface)] px-3 py-2 text-right">
                        <div className="flex items-center justify-end gap-1 text-xs font-semibold uppercase tracking-[0.14em] text-[var(--muted-foreground)]">
                          <Clock aria-hidden="true" className="h-3.5 w-3.5" />
                          Period {game.periodNumber ?? 1}
                        </div>
                        <div className="mt-1 text-xl font-black tabular-nums text-[var(--foreground)]">
                          {clock ?? formatStatus(game.status)}
                        </div>
                      </div>
                      <Link href={`/games/${game.gamePublicId}`}>
                        <Button size="sm" variant="outline">
                          Box score
                        </Button>
                      </Link>
                    </div>
                  </div>
                </Card>
              );
            })}
          </div>
        ) : (
          <Card className="p-5">
            <CardTitle>No games are live right now</CardTitle>
            <CardDescription>
              Games will appear here once scoring starts in Hoops Scorebook.
            </CardDescription>
          </Card>
        )}
      </section>

      <section aria-labelledby="final-results-heading">
        <div className="mb-4 flex items-center gap-2">
          <Trophy aria-hidden="true" className="h-5 w-5 text-[var(--accent)]" />
          <h2 className="text-2xl font-black tracking-tight text-[var(--foreground)]" id="final-results-heading">
            Recent finals
          </h2>
        </div>

        {feed.finalGames.length > 0 ? (
          <div className="grid gap-4">
            {feed.finalGames.map((result) => (
              <Card key={result.gamePublicId}>
                <div className="flex flex-wrap items-start justify-between gap-3">
                  <div>
                    <div className="mb-2 flex flex-wrap items-center gap-2">
                      <Badge>{formatStatus(result.resultWorkflowStatus)}</Badge>
                      <span className="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--muted-foreground)]">
                        {result.divisionName ?? "Division pending"} · {result.poolName ?? result.stageName ?? "Stage pending"}
                      </span>
                    </div>
                    <CardTitle>
                      {result.teamName} {result.score} - {result.opponentScore} {result.opponentTeamName}
                    </CardTitle>
                    <CardDescription>
                      {result.venue ?? "Venue pending"} · {formatDateTime(result.scheduledAt)}
                    </CardDescription>
                  </div>
                  <Link href={`/games/${result.gamePublicId}`}>
                    <Button size="sm" variant="outline">
                      Box score
                    </Button>
                  </Link>
                </div>
              </Card>
            ))}
          </div>
        ) : (
          <Card className="p-5">
            <CardTitle>No final results yet</CardTitle>
            <CardDescription>
              Final scores will remain here after a scorer finalizes the game in Hoops Scorebook.
            </CardDescription>
          </Card>
        )}
      </section>
    </div>
  );
}
