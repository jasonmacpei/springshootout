# Spring Shootout Partner Registration Handoff

Base path:
`/api/v1/partner`

Endpoint:
`POST /api/v1/partner/registrations`

Purpose:
Allow Spring Shootout to submit registrations directly into Hoops Scorebook so Hoops Scorebook is the canonical source of truth for:

1. teams
2. event participation
3. registrations
4. linked team contacts

## Auth

This endpoint is server-to-server only.

Requirements:

1. Send a partner API key by `x-api-key` or `Authorization: Bearer ...`
2. The key must include `partner:write`
3. Event-scoped keys can only write to their configured event
4. Do not expose the write key to the browser

## Request Contract

```json
{
  "event": "spring-shootout-2026",
  "externalRegistrationId": "ss-reg-12345",
  "source": "spring-shootout",
  "team": {
    "name": "Moncton Storm",
    "divisionName": "U12 Girls",
    "className": "Division 1",
    "province": "NB",
    "externalTeamId": "ss-team-12345"
  },
  "primaryContact": {
    "fullName": "Jane Coach",
    "email": "jane@example.com",
    "phone": "555-111-2222",
    "roleSlug": "head-coach",
    "externalContactId": "ss-contact-1"
  },
  "additionalContacts": [
    {
      "fullName": "Sam Manager",
      "email": "sam@example.com",
      "phone": "555-333-4444",
      "roleSlug": "manager",
      "externalContactId": "ss-contact-2"
    }
  ],
  "registration": {
    "status": "pending",
    "note": "Submitted from Spring Shootout website"
  }
}
```

Required fields:

1. `event`
2. `externalRegistrationId`
3. `source`
4. `team.name`
5. `team.divisionName`
6. `primaryContact.fullName`
7. `primaryContact.email`

Allowed contact roles:

1. `primary-contact`
2. `head-coach`
3. `assistant-coach`
4. `manager`

## Response Contract

`201 Created` for a new registration:

```json
{
  "data": {
    "event": {
      "eventId": 12,
      "eventPublicId": "event-uuid",
      "eventSlug": "spring-shootout-2026",
      "eventName": "Spring Shootout 2026"
    },
    "division": {
      "divisionId": 3,
      "divisionName": "U12 Girls"
    },
    "team": {
      "teamId": 41,
      "teamPublicId": "team-uuid",
      "teamName": "Moncton Storm",
      "created": true,
      "assignmentCreated": true,
      "externalTeamId": "ss-team-12345"
    },
    "registration": {
      "registrationId": 88,
      "registrationPublicId": "registration-uuid",
      "status": "pending",
      "created": true,
      "externalRegistrationId": "ss-reg-12345",
      "source": "spring-shootout"
    },
    "contacts": [
      {
        "contactId": 101,
        "contactPublicId": "contact-uuid",
        "fullName": "Jane Coach",
        "email": "jane@example.com",
        "roleSlug": "head-coach",
        "isPrimary": true,
        "created": true,
        "linkCreated": true
      },
      {
        "contactId": 102,
        "contactPublicId": "contact-uuid-2",
        "fullName": "Sam Manager",
        "email": "sam@example.com",
        "roleSlug": "manager",
        "isPrimary": false,
        "created": true,
        "linkCreated": true
      }
    ]
  }
}
```

`200 OK` for an idempotent retry or update:

1. Same response shape
2. `registration.created` becomes `false`
3. Existing ids are returned
4. `team.created` and `contact.created` reflect whether those rows were newly created or reused

## Idempotency Rules

1. `externalRegistrationId` is the primary idempotency key and is scoped by `(event, source)`.
2. `externalTeamId` is resolved within `(event, source)`.
3. `externalContactId` is resolved within `(event, source)`.
4. If `externalContactId` is omitted, normalized email is the fallback contact idempotency key for that event/source.
5. Retrying the same registration payload returns or updates the existing registration instead of creating duplicates.

## Team Collision Rules

1. External team mapping is authoritative when `externalTeamId` is present.
2. If no conflicting external mapping exists yet, an exact case-insensitive team-name match within the event reuses the existing team instead of creating a duplicate.
3. If the same team name is already mapped to a different `externalTeamId` for the same event/source, the endpoint returns `409`.
4. If `externalRegistrationId` is already linked to a different team, the endpoint returns `409`.

## Error Behavior

1. `400` invalid payload, unknown division, or invalid `roleSlug`
2. `401` missing or invalid partner key
3. `403` missing `partner:write` scope or event not allowed by the key
4. `404` event not found
5. `409` conflicting registration/team/contact mapping

Example error:

```json
{
  "error": "A team with this name is already mapped to a different externalTeamId.",
  "code": "team_conflict"
}
```

## Current Limits

1. `event` currently resolves by Hoops Scorebook event `slug` or `publicId`, not a separate `provider_event_slug` lookup.
2. `className` and `province` are stored as partner-source metadata because the canonical Hoops `teams` model does not yet have dedicated columns for them.
3. There is not yet a partner read endpoint for registrations; the canonical write path exists, but readback for admin/public consumption still happens through existing event/team surfaces plus internal tables.
4. This endpoint creates or updates event participation through `tournament_teams`, but it does not assign pools or seeds at registration time.
