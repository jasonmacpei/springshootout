# Tournament Playoff Testing Scripts

This directory contains three SQL scripts designed to help test the automatic resolution of playoff placeholders in the Spring Shootout tournament system.

## Purpose

These scripts provide a complete testing cycle for verifying that the tournament's auto-update functionality correctly assigns teams to playoff games based on pool play results.

## Scripts Overview

### 1. `insert_test_results.sql`
- Inserts realistic game results for all pool games in the schedule
- Creates a balanced set of wins/losses with realistic basketball scores
- Allows testing of the playoff auto-assignment functionality with complete standings

### 2. `delete_test_results.sql`
- Safely removes all pool game results
- Only targets pool game results, preserving any actual playoff results
- Includes transaction protection (BEGIN/COMMIT) for safety

### 3. `reset_playoff_assignments.sql`
- Resets any team assignments in playoff games
- Preserves placeholder values (`placeholder_home` and `placeholder_away`)
- Prepares the system for testing the auto-assignment feature again

## Usage Instructions

### Testing Procedure

1. **Setup Test Data:**
   - Run `insert_test_results.sql` in phpPgAdmin or via psql
   - This will create realistic pool play results for all divisions

2. **Test Auto-Assignment:**
   - Go to the `edit_schedule.php` page
   - Click the "Auto-Update Playoff Games" button
   - Verify that playoff placeholders are correctly resolved based on standings

3. **Reset Team Assignments:**
   - Run `reset_playoff_assignments.sql` to clear team assignments
   - This retains placeholders but removes assigned teams from playoff games

4. **Cleanup Test Data:**
   - Run `delete_test_results.sql` to remove all test game results
   - This will return the database to its original state

### Running the Scripts

**In phpPgAdmin:**
1. Navigate to your database
2. Click "SQL" tab
3. Copy and paste the script content
4. Click "Execute"

**Using psql:**
```bash
psql -U username -d database_name -f path/to/script.sql
```

## Safety Features

- All scripts use transactions (BEGIN/COMMIT) for safe execution
- Each script includes commented ROLLBACK statements to cancel if needed
- Scripts contain internal validation to prevent unintended data loss
- Clear comments explain the purpose of each operation

## Notes

- Always run these scripts in a logical order: insert → test → reset → delete
- The insert script is designed to work with the existing teams and games
- You may need to modify team IDs if your database has a different team structure
- Consider making a database backup before running these tests

## Troubleshooting

If scripts fail to execute:
1. Check that you have appropriate database permissions
2. Ensure team_id values match your actual database
3. Verify that game_id values correspond to your schedule
4. Look for error messages in the script output for specific issues 