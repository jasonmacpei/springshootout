# Database Recovery Instructions for Spring Shootout

## Overview

These instructions and SQL scripts will help you recover your database tables that are missing, specifically:
- `schedule` table (verify/recreate if needed)
- `game_results` table (create from scratch)

These tables are essential for the standings page and game results functionality of your application.

## Files Included

1. `schedule_table.sql` - SQL script to create the schedule table if it doesn't exist
2. `game_results_table.sql` - SQL script to create the game_results table
3. `recreate_tables.sql` - Combined script that creates both tables in the correct order

## How to Use These Scripts

### Option 1: Using the Combined Script (Recommended)

1. Log in to your PostgreSQL database:
   ```
   psql -U lostan6_admin1 -d lostan6_shootout
   ```

2. Run the combined script:
   ```
   \i /path/to/recreate_tables.sql
   ```

   This script will:
   - Check if the schedule table exists and create it if needed
   - Create the game_results table if it doesn't exist
   - Run a verification query to show the columns in both tables

### Option 2: Running Individual Scripts

If you prefer to run the scripts individually:

1. First run the schedule table script:
   ```
   \i /path/to/schedule_table.sql
   ```

2. Then run the game_results table script:
   ```
   \i /path/to/game_results_table.sql
   ```

## Verifying the Tables

After running the scripts, you can verify the tables were created correctly by running:

```sql
SELECT table_name, column_name, data_type 
FROM information_schema.columns 
WHERE table_name IN ('schedule', 'game_results') 
  AND table_schema = 'public'
ORDER BY table_name, ordinal_position;
```

## Using pgAdmin

If you prefer using pgAdmin:

1. Open pgAdmin and connect to your database
2. Open the Query Tool
3. Load the `recreate_tables.sql` file
4. Execute the query

## Testing the Application

After recreating the tables:

1. Try accessing the standings page to verify it no longer shows errors
2. If you have schedule data, try entering some game results
3. Check that the standings update correctly

## Table Structures

### schedule Table
- `game_id`: Serial primary key
- `home_team_id`: Reference to teams table 
- `away_team_id`: Reference to teams table
- `game_date`: Date of the game
- `game_time`: Time of the game
- `gym`: Location/venue
- `game_type`: Type of game
- `home_uniform`: Home team uniform color
- `away_uniform`: Away team uniform color
- `created_at`: Timestamp when record was created
- `updated_at`: Timestamp when record was last updated

### game_results Table
- `game_id`: Part of composite primary key, references schedule
- `team_id`: Part of composite primary key, references teams
- `points_for`: Points scored by the team
- `points_against`: Points scored against the team
- `win`: Flag (0 or 1) indicating if team won
- `loss`: Flag (0 or 1) indicating if team lost
- `created_at`: Timestamp when record was created
- `updated_at`: Timestamp when record was last updated

## Troubleshooting

If you encounter errors:

1. **Foreign Key Constraints**: Ensure the referenced tables (teams, schedule) exist and have the correct primary keys
2. **Permission Issues**: Make sure your database user has permission to create tables
3. **Syntax Errors**: PostgreSQL versions may have slight syntax differences; adjust as needed

For additional help, check the PostgreSQL documentation or contact your database administrator. 