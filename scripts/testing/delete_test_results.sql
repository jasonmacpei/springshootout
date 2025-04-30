-- TOURNAMENT TESTING SCRIPT 2: DELETE TEST RESULTS
-- Purpose: Safely remove all test results from the game_results table
-- Usage: Run this script in phpPgAdmin SQL interface or via psql

-- Start a transaction for safety (allows rollback if needed)
BEGIN;

-- Only delete results for pool games
-- This ensures we don't delete any real playoff results that might exist
DELETE FROM game_results 
WHERE game_id IN (
    SELECT game_id 
    FROM schedule 
    WHERE game_category = 'pool'
);

-- Show how many records were deleted
DO $$
DECLARE
    deleted_count INTEGER;
BEGIN
    GET DIAGNOSTICS deleted_count = ROW_COUNT;
    RAISE NOTICE 'Deleted % game result records', deleted_count;
END $$;

-- Commit the transaction
COMMIT;

-- To cancel the operation without deleting data, you can run:
-- ROLLBACK;

-- Alternative deletion with date restriction (safer if you want to protect recent entries)
-- Uncomment and modify this if you want to delete only results created before/after a specific time
/*
BEGIN;

DELETE FROM game_results 
WHERE game_id IN (
    SELECT game_id 
    FROM schedule 
    WHERE game_category = 'pool'
) 
AND created_at < NOW() - INTERVAL '2 hours';  -- Only delete results created more than 2 hours ago

DO $$
DECLARE
    deleted_count INTEGER;
BEGIN
    GET DIAGNOSTICS deleted_count = ROW_COUNT;
    RAISE NOTICE 'Deleted % game result records', deleted_count;
END $$;

COMMIT;
*/

-- Note: Run this script after you've finished testing the auto-update functionality
-- It will remove all pool game results, allowing you to start fresh with new test data 