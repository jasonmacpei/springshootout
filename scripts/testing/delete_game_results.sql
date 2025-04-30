select * from game_results gr
--left outer join schedule s on s.game_id = gr.game_id

--select * from schedule

--DELETE FROM game_results
--WHERE game_id IN (
 --   SELECT gr.game_id
--    FROM game_results gr
--    LEFT JOIN schedule s ON s.game_id = gr.game_id
--    WHERE s.game_category <> 'pool'
--);

--DELETE FROM game_results;