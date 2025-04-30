# Database Permissions Fix Instructions

## Overview

This document provides instructions for fixing the "permission denied for table game_results" error in your Spring Shootout application.

The error occurs because the database user your application uses (`lostan6_admin1`) doesn't have the necessary permissions to access the newly created tables.

## Prerequisites

1. PostgreSQL database access with superuser or owner privileges
2. Access to run SQL scripts on your database
3. Knowledge of your application's database user name

## How to Apply the Permissions Fix

### Option 1: Using psql (Command Line)

1. Login to your PostgreSQL database as a superuser or the database owner:
   ```
   psql -U postgres -d lostan6_shootout
   ```
   
   Note: Replace `postgres` with your superuser username if different.

2. Run the fix_permissions.sql script:
   ```
   \i /path/to/fix_permissions.sql
   ```

3. If you need to modify the application username in the script first:
   ```
   \e /path/to/fix_permissions.sql
   ```
   Then change the `\set app_user 'lostan6_admin1'` line to use your actual database username.

### Option 2: Using pgAdmin

1. Open pgAdmin and connect to your database
2. Open the Query Tool
3. Load the `fix_permissions.sql` file
4. Update the username if needed (line 5: `\set app_user 'lostan6_admin1'`)
5. Execute the query

### Option 3: Using a Hosting Control Panel

If you're using shared hosting with a control panel like cPanel, Plesk, or DirectAdmin:

1. Navigate to the PostgreSQL or database section
2. Use the SQL interface/tool provided
3. Copy and paste the contents of `fix_permissions.sql`
4. Update the username if needed
5. Execute the SQL

## Verifying the Fix

After applying the permissions:

1. Try accessing the standings page again: https://springshootout.ca/pages/standings.php
2. If the page loads without the permission error, the fix was successful
3. If you still see permission errors, see the troubleshooting section

## Troubleshooting

If you still encounter permission issues after running the script:

1. **Ownership Change**: Uncomment and run the last two lines in the script:
   ```sql
   ALTER TABLE game_results OWNER TO lostan6_admin1;
   ALTER TABLE schedule OWNER TO lostan6_admin1;
   ```

2. **Connection Issues**: Verify that your application is connecting with the correct database credentials

3. **Database User Existence**: Confirm that the `lostan6_admin1` user exists in your PostgreSQL database:
   ```sql
   SELECT usename FROM pg_user WHERE usename = 'lostan6_admin1';
   ```

4. **Schema Verification**: Confirm that your tables are in the public schema:
   ```sql
   SELECT table_schema, table_name 
   FROM information_schema.tables 
   WHERE table_name IN ('game_results', 'schedule');
   ```

## Additional Notes

- This script grants permissions on all existing tables and sequences in the public schema
- It also sets default privileges for any future tables and sequences created
- You only need to run this script once, unless you create new database objects with a different user

## Contact Support

If you continue to have issues after trying these solutions, contact your database administrator or hosting provider for additional assistance. 