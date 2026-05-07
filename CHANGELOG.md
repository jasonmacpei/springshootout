# Changelog

## [0.2.0] - 2026-05-07

- Added live game box score pages backed by the Hoops Scorebook public box score API.
- Added local live-clock ticking between API polls, including paused-clock and final-game handling.
- Added player stat tables grouped by team with points, fouls, minutes, and plus/minus when available.
- Linked schedule and live results games to the new public box score view.
- Added a live scoreboard fallback for game pages while the Hoops Scorebook box score endpoint is still deploying.
- Updated the live results board to poll no-store snapshots and tick running game clocks locally between polls.

## [0.1.1] - 2026-05-07

- Renamed the public Results navigation item to Live Results.
- Added live games to the results page with background score updates every 15 seconds.
- Added recent finalized results below live games, grouped once per game.
- Moved the Hoops Scorebook powered-by promotion to the bottom of the live results page.
