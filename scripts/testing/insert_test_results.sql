-- TOURNAMENT TESTING SCRIPT 1: INSERT REALISTIC GAME RESULTS
-- Purpose: Insert realistic results for all pool games to test playoff auto-assignment
-- Usage: Run this script in phpPgAdmin SQL interface or via psql

-- Start a transaction for safety
BEGIN;

-- First, delete any existing game results to avoid conflicts
DELETE FROM game_results WHERE game_id IN (
    SELECT game_id FROM schedule WHERE game_category = 'pool'
);

-- Insert game results for each game in the schedule
-- Each game has two rows - one for each team participating

-- Game #1: Home Team 1 vs Away Team 6
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (1, 1, 65, 52, 1, 0); -- Team 1 wins
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (1, 6, 52, 65, 0, 1); -- Team 6 loses

-- Game #2: Home Team 7 vs Away Team 53
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (2, 7, 58, 51, 1, 0); -- Team 7 wins
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (2, 53, 51, 58, 0, 1); -- Team 53 loses

-- Game #3: Home Team 19 vs Away Team 52
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (3, 19, 63, 49, 1, 0); -- Team 19 wins
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (3, 52, 49, 63, 0, 1); -- Team 52 loses

-- Game #4: Home Team 14 vs Away Team 11
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (4, 14, 60, 54, 1, 0); -- Team 14 wins
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (4, 11, 54, 60, 0, 1); -- Team 11 loses

-- Game #5: Home Team 47 vs Away Team 4
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (5, 47, 62, 58, 1, 0); -- Team 47 wins
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (5, 4, 58, 62, 0, 1); -- Team 4 loses

-- Game #6: Home Team 18 vs Away Team 16
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (6, 18, 67, 55, 1, 0); -- Team 18 wins
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (6, 16, 55, 67, 0, 1); -- Team 16 loses

-- Game #7: Home Team 46 vs Away Team 5
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (7, 46, 64, 56, 1, 0); -- Team 46 wins
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (7, 5, 56, 64, 0, 1); -- Team 5 loses

-- Game #8: Home Team 26 vs Away Team 13
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (8, 26, 59, 52, 1, 0); -- Team 26 wins
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (8, 13, 52, 59, 0, 1); -- Team 13 loses

-- Game #9: Home Team 10 vs Away Team 54
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (9, 10, 61, 55, 1, 0); -- Team 10 wins
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (9, 54, 55, 61, 0, 1); -- Team 54 loses

-- Game #10: Home Team 53 vs Away Team 17
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (10, 53, 63, 58, 1, 0); -- Team 53 wins
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (10, 17, 58, 63, 0, 1); -- Team 17 loses

-- Game #11: Home Team 6 vs Away Team 25
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (11, 6, 62, 54, 1, 0); -- Team 6 wins
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (11, 25, 54, 62, 0, 1); -- Team 25 loses

-- Game #12: Home Team 18 vs Away Team 9
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (12, 18, 72, 59, 1, 0); -- Team 18 wins
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (12, 9, 59, 72, 0, 1); -- Team 9 loses

-- Game #13: Home Team 8 vs Away Team 11
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (13, 8, 68, 55, 1, 0); -- Team 8 wins
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (13, 11, 55, 68, 0, 1); -- Team 11 loses

-- Game #14: Home Team 52 vs Away Team 4
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (14, 52, 58, 50, 1, 0); -- Team 52 wins
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (14, 4, 50, 58, 0, 1); -- Team 4 loses

-- Game #15: Home Team 19 vs Away Team 47
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (15, 19, 65, 58, 1, 0); -- Team 19 wins
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (15, 47, 58, 65, 0, 1); -- Team 47 loses

-- Game #16: Home Team 10 vs Away Team 50
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (16, 10, 64, 53, 1, 0); -- Team 10 wins
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (16, 50, 53, 64, 0, 1); -- Team 50 loses

-- Game #17: Home Team 5 vs Away Team 2
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (17, 5, 61, 49, 1, 0); -- Team 5 wins
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (17, 2, 49, 61, 0, 1); -- Team 2 loses

-- Game #18: Home Team 1 vs Away Team 25
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (18, 1, 68, 55, 1, 0); -- Team 1 wins
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (18, 25, 55, 68, 0, 1); -- Team 25 loses

-- Game #19: Home Team 7 vs Away Team 17
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (19, 7, 62, 54, 1, 0); -- Team 7 wins
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (19, 17, 54, 62, 0, 1); -- Team 17 loses

-- Game #20: Home Team 9 vs Away Team 16
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (20, 9, 61, 57, 1, 0); -- Team 9 wins
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (20, 16, 57, 61, 0, 1); -- Team 16 loses

-- Game #21: Home Team 8 vs Away Team 14
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (21, 8, 65, 52, 1, 0); -- Team 8 wins
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (21, 14, 52, 65, 0, 1); -- Team 14 loses

-- Game #22: Home Team 52 vs Away Team 47
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (22, 52, 60, 56, 1, 0); -- Team 52 wins
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (22, 47, 56, 60, 0, 1); -- Team 47 loses

-- Game #23: Home Team 15 vs Away Team 13
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (23, 15, 64, 51, 1, 0); -- Team 15 wins
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (23, 13, 51, 64, 0, 1); -- Team 13 loses

-- Game #24: Home Team 54 vs Away Team 50
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (24, 54, 68, 59, 1, 0); -- Team 54 wins
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (24, 50, 59, 68, 0, 1); -- Team 50 loses

-- Game #25: Home Team 46 vs Away Team 2
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (25, 46, 62, 55, 1, 0); -- Team 46 wins
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (25, 2, 55, 62, 0, 1); -- Team 2 loses

-- Game #26: Home Team 19 vs Away Team 4
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (26, 19, 70, 58, 1, 0); -- Team 19 wins
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (26, 4, 58, 70, 0, 1); -- Team 4 loses

-- Game #27: Home Team 26 vs Away Team 15
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (27, 26, 63, 57, 1, 0); -- Team 26 wins
INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss)
VALUES (27, 15, 57, 63, 0, 1); -- Team 15 loses

-- Commit the transaction if all inserts are successful
COMMIT;

-- To rollback in case of errors, you can run:
-- ROLLBACK;

-- After running this script, go to the edit_schedule.php page and click "Auto-Update Playoff Games"
-- to test if placeholders are correctly resolved 