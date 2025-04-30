-- SQL Script to fix permissions for Spring Shootout application
-- This script resolves the "permission denied for table game_results" error
-- Run this script as a superuser or database owner

-- Set the application username (change if different)
\set app_user 'lostan6_admin1'

-- Display current user for verification
SELECT current_user, current_database();

-- Step 1: Grant schema usage permission
GRANT USAGE ON SCHEMA public TO :app_user;

-- Step 2: Grant all privileges on existing tables in the public schema
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO :app_user;

-- Step 3: Grant all privileges on existing sequences in the public schema
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO :app_user;

-- Step 4: Explicitly grant permissions to the game_results table
GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE game_results TO :app_user;

-- Step 5: Explicitly grant permissions to the schedule table
GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE schedule TO :app_user;

-- Step 6: Set default privileges for future tables created in the public schema
ALTER DEFAULT PRIVILEGES IN SCHEMA public 
GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO :app_user;

-- Step 7: Set default privileges for future sequences created in the public schema
ALTER DEFAULT PRIVILEGES IN SCHEMA public 
GRANT USAGE, SELECT, UPDATE ON SEQUENCES TO :app_user;

-- Step 8: Verify permissions
\echo 'Permissions granted to the application user. Verifying permissions:'
\dp game_results
\dp schedule

-- Additional note: If still experiencing issues, you may need to run:
-- ALTER TABLE game_results OWNER TO :app_user;
-- ALTER TABLE schedule OWNER TO :app_user;
-- But try without these first, as they change ownership completely. 