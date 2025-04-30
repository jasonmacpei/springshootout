-- Minimal SQL script to fix table permissions
-- Contains only essential commands

-- Grant schema usage
GRANT USAGE ON SCHEMA public TO lostan6_admin1;

-- Grant permissions on game_results table
GRANT ALL PRIVILEGES ON TABLE game_results TO lostan6_admin1;

-- Grant permissions on schedule table
GRANT ALL PRIVILEGES ON TABLE schedule TO lostan6_admin1;

-- Grant permissions on all tables (in case there are other related tables)
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO lostan6_admin1;

-- Grant permissions on sequences
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO lostan6_admin1; 