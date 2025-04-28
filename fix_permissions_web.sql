-- SQL Script to fix permissions for Spring Shootout application (Web Interface Version)
-- This script resolves the "permission denied for table game_results" error
-- Run this script as a superuser or database owner

-- Display current user for verification
SELECT current_user, current_database();

-- Step 1: Grant schema usage permission
GRANT USAGE ON SCHEMA public TO lostan6_admin1;

-- Step 2: Grant all privileges on existing tables in the public schema
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO lostan6_admin1;

-- Step 3: Grant all privileges on existing sequences in the public schema
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO lostan6_admin1;

-- Step 4: Explicitly grant permissions to the game_results table
GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE game_results TO lostan6_admin1;

-- Step 5: Explicitly grant permissions to the schedule table
GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE schedule TO lostan6_admin1;

-- Step 6: Set default privileges for future tables created in the public schema
ALTER DEFAULT PRIVILEGES IN SCHEMA public 
GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO lostan6_admin1;

-- Step 7: Set default privileges for future sequences created in the public schema
ALTER DEFAULT PRIVILEGES IN SCHEMA public 
GRANT USAGE, SELECT, UPDATE ON SEQUENCES TO lostan6_admin1;

-- Step 8: Verify permissions by checking information schema
SELECT grantee, table_schema, table_name, privilege_type
FROM information_schema.table_privileges 
WHERE table_name IN ('game_results', 'schedule')
AND grantee = 'lostan6_admin1'
ORDER BY table_name, privilege_type;

-- Additional note: If still experiencing issues, you may need to run:
-- ALTER TABLE game_results OWNER TO lostan6_admin1;
-- ALTER TABLE schedule OWNER TO lostan6_admin1;
-- But try without these first, as they change ownership completely. 