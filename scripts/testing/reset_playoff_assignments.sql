-- TOURNAMENT TESTING SCRIPT 3: RESET PLAYOFF TEAM ASSIGNMENTS
-- Purpose: Remove team assignments from playoff games while keeping placeholders intact
-- Usage: Run this script in phpPgAdmin SQL interface or via psql

-- Start a transaction for safety
BEGIN;

-- Count and display the current state of playoff games before making changes
DO $$
DECLARE
    total_playoff_games INTEGER;
    games_with_teams INTEGER;
BEGIN
    -- Count total playoff games
    SELECT COUNT(*) INTO total_playoff_games 
    FROM schedule 
    WHERE game_category != 'pool';
    
    -- Count playoff games with assigned teams
    SELECT COUNT(*) INTO games_with_teams 
    FROM schedule 
    WHERE game_category != 'pool' AND (home_team_id IS NOT NULL OR away_team_id IS NOT NULL);
    
    RAISE NOTICE 'Before reset: % playoff games total, % with team assignments', 
                 total_playoff_games, games_with_teams;
END $$;

-- Reset playoff games to use placeholders only by setting team_id fields to NULL
-- This preserves placeholder_home and placeholder_away values
UPDATE schedule
SET 
    home_team_id = NULL,
    away_team_id = NULL
WHERE 
    game_category != 'pool' AND
    (home_team_id IS NOT NULL OR away_team_id IS NOT NULL);

-- Display how many records were updated
DO $$
DECLARE
    updated_count INTEGER;
BEGIN
    GET DIAGNOSTICS updated_count = ROW_COUNT;
    RAISE NOTICE 'Reset team assignments for % playoff games', updated_count;
END $$;

-- Safety check to ensure placeholders are preserved
DO $$
DECLARE
    missing_placeholders INTEGER;
BEGIN
    SELECT COUNT(*) INTO missing_placeholders
    FROM schedule
    WHERE 
        game_category != 'pool' AND
        home_team_id IS NULL AND
        away_team_id IS NULL AND
        (placeholder_home IS NULL OR placeholder_away IS NULL);
    
    IF missing_placeholders > 0 THEN
        RAISE WARNING 'Warning: % playoff games have missing placeholders', missing_placeholders;
    END IF;
END $$;

-- Commit the transaction
COMMIT;

-- To cancel the operation without modifying data, you can run:
-- ROLLBACK;

-- Note: After running this script, you should be able to use the "Auto-Update Playoff Games" 
-- function on the edit_schedule.php page to test if placeholders are correctly resolved
-- This script specifically targets only playoff games, and ensures placeholders remain intact 