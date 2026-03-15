# Spring Shootout Public API Handoff

Base path:
`/api/v1`

This handoff covers the three public endpoints added for the Spring Shootout integration:

1. `GET /api/v1/schedule`
2. `GET /api/v1/pools`
3. `GET /api/v1/playoffs`

These endpoints follow the same public API v1 conventions as the existing feeds:

1. JSON responses are wrapped in `data`
2. Filtered endpoints echo normalized filters in `data.filters`
3. Anonymous access is limited by event visibility
4. Partner API keys can be passed with `x-api-key` or `Authorization: Bearer ...`

## Visibility Rules

1. `public_live`: live, final, scheduled, canceled, and postponed data is available on `schedule` and `playoffs`
2. `public_final_only`: `schedule` and `playoffs` include scheduled, final, canceled, and postponed rows, but not `in_progress` rows for anonymous callers
3. `private`: requires a partner key with `private:read`
4. `pools` is standings-backed, so it returns published/computed standings data for visible events

## `GET /api/v1/schedule`

Purpose:
Return full event schedule rows for a single event, including upcoming games.

Query params:

1. `event` required event `slug` or event `publicId`
2. `divisionId` optional numeric division id
3. `poolId` optional numeric pool id
4. `stage` optional stage `publicId`, numeric stage id, or exact stage name
5. `dateFrom` optional ISO date (`YYYY-MM-DD`) or ISO datetime
6. `dateTo` optional ISO date (`YYYY-MM-DD`) or ISO datetime
7. `venue` optional exact venue filter, case-insensitive
8. `limit` optional integer, default `500`, max `1000`

Response shape:

```json
{
  "data": {
    "generatedAt": "2026-05-01T12:00:00.000Z",
    "filters": {
      "event": "spring-shootout-2026",
      "divisionId": 1,
      "poolId": null,
      "stage": null,
      "dateFrom": null,
      "dateTo": null,
      "venue": null,
      "limit": 500
    },
    "games": [
      {
        "gameId": 201,
        "gamePublicId": "game-201",
        "status": "scheduled",
        "scheduledAt": "2026-05-09T18:00:00.000Z",
        "venue": "UPEI Field House",
        "court": null,
        "eventPublicId": "event-uuid",
        "eventSlug": "spring-shootout-2026",
        "eventName": "Spring Shootout 2026",
        "divisionId": 1,
        "divisionName": "U12 Girls",
        "poolId": 10,
        "poolName": "Pool A",
        "stageId": 31,
        "stagePublicId": "stage-uuid",
        "stageName": "Pool Play",
        "stageType": "pool_play",
        "stageScope": "pool",
        "homeTeamName": "Team A",
        "homeTeamPublicId": "team-a",
        "homeSlotLabel": null,
        "homeScore": 0,
        "awayTeamName": "Team B",
        "awayTeamPublicId": "team-b",
        "awaySlotLabel": null,
        "awayScore": 0
      }
    ]
  }
}
```

Notes:

1. Use this for schedule pages and upcoming-game lists.
2. `homeSlotLabel` and `awaySlotLabel` are included so unresolved bracket slots can still render.
3. `court` is currently always `null` because the current Hoops Scorebook model only stores `venue`.

## `GET /api/v1/pools`

Purpose:
Return grouped pool standings for a single event.

Query params:

1. `event` required event `slug` or event `publicId`
2. `divisionId` optional numeric division id
3. `stage` optional stage `publicId`, numeric stage id, or exact stage name
4. `limit` optional integer, default `50`, max `100`

Response shape:

```json
{
  "data": {
    "generatedAt": "2026-05-01T12:00:00.000Z",
    "filters": {
      "event": "spring-shootout-2026",
      "divisionId": 1,
      "stage": null,
      "limit": 50
    },
    "pools": [
      {
        "poolId": 10,
        "poolName": "Pool A",
        "eventPublicId": "event-uuid",
        "eventSlug": "spring-shootout-2026",
        "eventName": "Spring Shootout 2026",
        "divisionId": 1,
        "divisionName": "U12 Girls",
        "stageId": 31,
        "stagePublicId": "stage-uuid",
        "stageName": "Pool Play",
        "stageType": "pool_play",
        "stageScope": "pool",
        "stageOrder": 1,
        "stageStatus": "active",
        "teams": [
          {
            "teamId": 41,
            "teamPublicId": "team-a",
            "teamName": "Team A",
            "rank": 1,
            "tieBreakRank": 1,
            "manualOverrideRank": null,
            "gamesPlayed": 3,
            "wins": 3,
            "losses": 0,
            "ties": 0,
            "pointsFor": 120,
            "pointsAgainst": 88,
            "pointDifferential": 32,
            "winPctBps": 100000,
            "winPct": 1,
            "revision": 4,
            "computedAt": "2026-05-01T11:00:00.000Z",
            "updatedAt": "2026-05-01T11:00:00.000Z"
          }
        ]
      }
    ]
  }
}
```

Notes:

1. `pools` is grouped data, not flat standings rows.
2. `limit` applies to pool groups, not to individual team rows.
3. Grouping key is effectively `(stageId, poolId)`, so the same pool can appear separately if the competition model uses multiple standings stages.

## `GET /api/v1/playoffs`

Purpose:
Return grouped playoff stage data for a single event.

Query params:

1. `event` required event `slug` or event `publicId`
2. `divisionId` optional numeric division id
3. `stage` optional stage `publicId`, numeric stage id, or exact stage name
4. `limit` optional integer, default `50`, max `100`

Response shape:

```json
{
  "data": {
    "generatedAt": "2026-05-01T12:00:00.000Z",
    "filters": {
      "event": "spring-shootout-2026",
      "divisionId": 1,
      "stage": null,
      "limit": 50
    },
    "brackets": [
      {
        "stageId": 41,
        "stagePublicId": "stage-uuid",
        "stageName": "Semi Final",
        "stageType": "playoff_bracket",
        "stageScope": "division",
        "stageOrder": 3,
        "stageStatus": "active",
        "eventPublicId": "event-uuid",
        "eventSlug": "spring-shootout-2026",
        "eventName": "Spring Shootout 2026",
        "divisionId": 1,
        "divisionName": "U12 Girls",
        "bracketDefinition": [
          {
            "order": 1,
            "name": "Semi 1",
            "homeSource": "1A",
            "awaySource": "2B"
          }
        ],
        "games": [
          {
            "gameId": 201,
            "gamePublicId": "game-201",
            "status": "final",
            "scheduledAt": "2026-05-09T18:00:00.000Z",
            "venue": "UPEI Field House",
            "court": null,
            "homeTeamName": "Team A",
            "homeTeamPublicId": "team-a",
            "homeSlotLabel": null,
            "homeScore": 52,
            "awayTeamName": "Team B",
            "awayTeamPublicId": "team-b",
            "awaySlotLabel": null,
            "awayScore": 47,
            "stageName": "Semi Final"
          }
        ]
      }
    ]
  }
}
```

Notes:

1. This endpoint returns structured stage-grouped playoff data, not inferred result rows.
2. `bracketDefinition` is included when the underlying stage has stored bracket metadata.
3. If `bracketDefinition` is empty, consumers should fall back to a stage-card or stage-list layout instead of assuming a fully connected bracket tree.
4. `court` is currently always `null` because the current Hoops Scorebook model only stores `venue`.

## Current Limitations

1. The current data model does not have a separate persisted `court` field, so route responses return `court: null`.
2. True bracket-tree rendering depends on stored `bracketDefinition` metadata on playoff stages.
3. If a playoff stage exists without bracket metadata, the API still returns structured stage-grouped games, but not a full source-linked bracket graph.
4. The API resolves `event` using the Hoops Scorebook event `slug` or `publicId`. This handoff assumes Spring Shootout’s `provider_event_slug` matches the Hoops event slug, for example `spring-shootout-2026`.

## Recommended Consumer Usage

1. Use `schedule` for all upcoming-game and event schedule pages.
2. Use `pools` for grouped pool cards and standings summaries.
3. Use `playoffs` for playoff stage cards and bracket sections.
4. Use `games/{publicId}` only when drilling into a specific game.
