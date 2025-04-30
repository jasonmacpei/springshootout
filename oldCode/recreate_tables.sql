-- SQL Script to recreate missing tables for Spring Shootout application
-- Created on: April 2024

-- Step 1: Ensure we're using the correct schema
SET search_path TO public;

-- Step 2: Create schedule table if it doesn't exist
DO $$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_tables WHERE schemaname = 'public' AND tablename = 'schedule') THEN
        -- Create schedule table
        CREATE TABLE schedule (
            game_id SERIAL PRIMARY KEY,
            home_team_id INTEGER REFERENCES teams(team_id),
            away_team_id INTEGER REFERENCES teams(team_id),
            game_date DATE NOT NULL,
            game_time TIME NOT NULL,
            gym VARCHAR(100),
            game_type VARCHAR(50),
            home_uniform VARCHAR(50),
            away_uniform VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );

        -- Add comments to table and columns
        COMMENT ON TABLE schedule IS 'Stores game schedule information';
        COMMENT ON COLUMN schedule.game_id IS 'Unique identifier for each game';
        COMMENT ON COLUMN schedule.home_team_id IS 'Foreign key reference to teams table for home team';
        COMMENT ON COLUMN schedule.away_team_id IS 'Foreign key reference to teams table for away team';
        COMMENT ON COLUMN schedule.game_date IS 'Date of the game';
        COMMENT ON COLUMN schedule.game_time IS 'Time of the game';
        COMMENT ON COLUMN schedule.gym IS 'Location/venue where the game is played';
        COMMENT ON COLUMN schedule.game_type IS 'Type of game (e.g., regular, playoff, final)';
        COMMENT ON COLUMN schedule.home_uniform IS 'Uniform color for home team';
        COMMENT ON COLUMN schedule.away_uniform IS 'Uniform color for away team';
        COMMENT ON COLUMN schedule.created_at IS 'Timestamp when record was created';
        COMMENT ON COLUMN schedule.updated_at IS 'Timestamp when record was last updated';

        -- Create index on commonly queried columns
        CREATE INDEX idx_schedule_game_date ON schedule(game_date);
        CREATE INDEX idx_schedule_home_team ON schedule(home_team_id);
        CREATE INDEX idx_schedule_away_team ON schedule(away_team_id);
        
        RAISE NOTICE 'schedule table created successfully';
    ELSE
        RAISE NOTICE 'schedule table already exists';
    END IF;
END $$;

-- Step 3: Create game_results table if it doesn't exist
DO $$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_tables WHERE schemaname = 'public' AND tablename = 'game_results') THEN
        -- Create game_results table
        CREATE TABLE game_results (
            game_id INTEGER NOT NULL,
            team_id INTEGER NOT NULL,
            points_for INTEGER NOT NULL DEFAULT 0,
            points_against INTEGER NOT NULL DEFAULT 0,
            win INTEGER NOT NULL DEFAULT 0,
            loss INTEGER NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            -- Define primary key
            PRIMARY KEY (game_id, team_id),
            -- Add foreign key constraints
            CONSTRAINT fk_game_results_game FOREIGN KEY (game_id) REFERENCES schedule(game_id) ON DELETE CASCADE,
            CONSTRAINT fk_game_results_team FOREIGN KEY (team_id) REFERENCES teams(team_id) ON DELETE CASCADE,
            -- Add check constraints
            CONSTRAINT check_points_positive CHECK (points_for >= 0 AND points_against >= 0),
            CONSTRAINT check_win_loss_values CHECK (win IN (0, 1) AND loss IN (0, 1)),
            CONSTRAINT check_win_loss_exclusive CHECK ((win + loss) <= 1)
        );

        -- Add comments to table and columns
        COMMENT ON TABLE game_results IS 'Stores game results information for teams';
        COMMENT ON COLUMN game_results.game_id IS 'Foreign key reference to schedule table';
        COMMENT ON COLUMN game_results.team_id IS 'Foreign key reference to teams table';
        COMMENT ON COLUMN game_results.points_for IS 'Points scored by the team';
        COMMENT ON COLUMN game_results.points_against IS 'Points scored against the team';
        COMMENT ON COLUMN game_results.win IS 'Flag indicating if team won (1) or not (0)';
        COMMENT ON COLUMN game_results.loss IS 'Flag indicating if team lost (1) or not (0)';
        COMMENT ON COLUMN game_results.created_at IS 'Timestamp when record was created';
        COMMENT ON COLUMN game_results.updated_at IS 'Timestamp when record was last updated';

        -- Create indexes
        CREATE INDEX idx_game_results_game_id ON game_results(game_id);
        CREATE INDEX idx_game_results_team_id ON game_results(team_id);
        
        RAISE NOTICE 'game_results table created successfully';
    ELSE
        RAISE NOTICE 'game_results table already exists';
    END IF;
END $$;

-- Step 4: Verification query - confirm tables exist
SELECT 
    table_name, 
    column_name, 
    data_type 
FROM 
    information_schema.columns 
WHERE 
    table_name IN ('schedule', 'game_results') 
    AND table_schema = 'public'
ORDER BY 
    table_name, 
    ordinal_position; 