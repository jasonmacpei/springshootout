# Database Permissions Fix Instructions for Web Interface

## Overview

This document provides instructions for fixing the "permission denied for table game_results" error in your Spring Shootout application using a web interface like phpPgAdmin or a hosting control panel.

## Files Provided

1. `fix_permissions_web.sql` - The main script to grant necessary permissions (use this first)
2. `change_table_ownership_web.sql` - An alternative script to change table ownership (use only if the first script doesn't fix the issue)

## How to Apply the Permissions Fix

### Using a Web-Based Database Manager (phpPgAdmin, Adminer, etc.)

1. Log in to your database management interface with superuser or database owner credentials
2. Navigate to the SQL query interface/tool
3. Open the `fix_permissions_web.sql` file on your computer
4. Copy the entire contents
5. Paste the SQL into the query window
6. Execute the query

### Using a Hosting Control Panel (cPanel, Plesk, DirectAdmin)

1. Navigate to the PostgreSQL or database section of your control panel
2. Find the SQL interface/query tool
3. Copy and paste the contents of `fix_permissions_web.sql`
4. Execute the SQL

## What This Script Does

The `fix_permissions_web.sql` script:

1. Verifies the current database user
2. Grants USAGE permission on the public schema
3. Grants ALL PRIVILEGES on all existing tables
4. Grants ALL PRIVILEGES on all existing sequences
5. Explicitly grants permissions to the game_results and schedule tables
6. Sets default privileges for future tables and sequences
7. Verifies the permissions were applied correctly

## Verifying the Fix

After applying the permissions:

1. Try accessing the standings page again: https://springshootout.ca/pages/standings.php
2. If the page loads without the permission error, the fix was successful

## If You Still Have Permission Issues

If the first script didn't fix the issue, run the `change_table_ownership_web.sql` script:

1. Follow the same steps as above, but use the `change_table_ownership_web.sql` file
2. This script will:
   - Show the current ownership of the tables
   - Change ownership of game_results and schedule tables to your application user
   - Identify any related sequences that might need ownership changes
   - Verify the changes were applied correctly

## Troubleshooting

If you continue to experience permission issues:

1. **Check Database User**: Make sure the username in the script ('lostan6_admin1') matches your actual database user
   - If different, modify the scripts to use your correct username before running

2. **Check Database Connection**: Verify that your application is using the correct database connection details

3. **Verify Table Existence**: Confirm that the tables actually exist:
   ```sql
   SELECT table_name FROM information_schema.tables 
   WHERE table_schema = 'public' AND table_name IN ('game_results', 'schedule');
   ```

4. **Check for Error Messages**: The SQL queries may provide helpful error messages if they fail

## Need to Customize the Username?

If your application uses a different database username:

1. Open the script file in a text editor
2. Find all occurrences of 'lostan6_admin1'
3. Replace them with your actual database username
4. Save the file and then run it

## Contact Support

If you continue to have issues after trying these solutions, contact your database administrator or hosting provider for additional assistance. 