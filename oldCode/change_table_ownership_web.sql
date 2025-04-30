-- SQL Script to change ownership of tables for Spring Shootout application (Web Interface Version)
-- Use this script only if granting permissions didn't resolve the issue
-- Run this script as a superuser or database owner

-- Display current user for verification
SELECT current_user, current_database();

-- Show current ownership of tables (using information schema instead of \dt)
SELECT 
    t.table_schema,
    t.table_name,
    u.usename AS table_owner
FROM 
    information_schema.tables t
JOIN 
    pg_catalog.pg_class c ON t.table_name = c.relname
JOIN 
    pg_catalog.pg_user u ON c.relowner = u.usesysid
WHERE 
    t.table_name IN ('game_results', 'schedule')
    AND t.table_schema = 'public';

-- Change ownership of the game_results table
ALTER TABLE game_results OWNER TO lostan6_admin1;

-- Change ownership of the schedule table
ALTER TABLE schedule OWNER TO lostan6_admin1;

-- Find related sequences
SELECT 
    c.relname AS sequence_name, 
    u.usename AS owner
FROM 
    pg_class c
JOIN 
    pg_user u ON c.relowner = u.usesysid
WHERE 
    c.relkind = 'S' 
    AND (c.relname LIKE '%game%' OR c.relname LIKE '%schedule%');

-- If sequences were found from above query, uncomment and modify this line:
-- ALTER SEQUENCE schedule_game_id_seq OWNER TO lostan6_admin1;

-- Verify the changes - check ownership again
SELECT 
    t.table_schema,
    t.table_name,
    u.usename AS table_owner
FROM 
    information_schema.tables t
JOIN 
    pg_catalog.pg_class c ON t.table_name = c.relname
JOIN 
    pg_catalog.pg_user u ON c.relowner = u.usesysid
WHERE 
    t.table_name IN ('game_results', 'schedule')
    AND t.table_schema = 'public';

-- Additional verification - check permissions
SELECT 
    grantee, 
    table_schema, 
    table_name, 
    privilege_type
FROM 
    information_schema.table_privileges 
WHERE 
    table_name IN ('game_results', 'schedule')
ORDER BY 
    table_name, 
    grantee, 
    privilege_type; 