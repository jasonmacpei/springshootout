-- SQL Script to change ownership of tables for Spring Shootout application
-- Use this script only if granting permissions didn't resolve the issue
-- Run this script as a superuser or database owner

-- Set the application username (change if different)
\set app_user 'lostan6_admin1'

-- Display current user for verification
SELECT current_user, current_database();

-- Show current ownership of tables
\echo 'Current ownership of tables:'
\dt game_results
\dt schedule

-- Change ownership of the game_results table
ALTER TABLE game_results OWNER TO :app_user;

-- Change ownership of the schedule table
ALTER TABLE schedule OWNER TO :app_user;

-- Change ownership of sequences if needed
-- Look for sequences related to these tables
\echo 'Checking for related sequences:'
SELECT c.relname AS sequence_name, u.usename AS owner
FROM pg_class c
JOIN pg_user u ON c.relowner = u.usesysid
WHERE c.relkind = 'S' AND c.relname LIKE '%game%' OR c.relname LIKE '%schedule%';

-- If sequences were found, change their ownership too
-- Uncomment and modify the line below as needed:
-- ALTER SEQUENCE schedule_game_id_seq OWNER TO :app_user;

-- Verify the changes
\echo 'New ownership of tables:'
\dt game_results
\dt schedule

-- Additional verification
\echo 'Verifying permissions on tables:'
\dp game_results
\dp schedule

\echo 'Table ownership has been changed to the application user.'
\echo 'Please test your application to confirm the issue is resolved.' 