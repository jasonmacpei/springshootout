-- SQL Script to create the game_results table
-- For Spring Shootout application

-- Check if game_results table exists
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