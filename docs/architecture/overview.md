# Spring Shootout Architecture

Spring Shootout is the branded event application.

Responsibilities:

1. Public website and homepage
2. Registration and extra contacts
3. CMS-managed informational content
4. Staff auth, roles, and admin workspace
5. Email templates and campaigns

Hoops Scorebook remains the competition engine.

Responsibilities delegated externally:

1. Events discovery
2. Live scoreboard
3. Finalized results
4. Standings
5. Per-game detail
6. Future schedule-capable feed

Integration pattern:

1. Next.js server components call `CompetitionProvider`
2. `CompetitionProvider` selects `HoopsScorebookProvider` or `mockCompetitionProvider`
3. The browser never receives partner keys
4. Revalidation is tag-based via Next.js server cache tags
