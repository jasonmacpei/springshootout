-- SQL Script to check if schedule table exists and create it if needed
-- For Spring Shootout application

-- Check if schedule table exists
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