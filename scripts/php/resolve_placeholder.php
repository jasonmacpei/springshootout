<?php
declare(strict_types=1);

/**
 * Normalize pool name for consistent matching
 * 
 * @param string $poolName The pool name to normalize
 * @return string Normalized pool name
 */
function normalizePoolName(string $poolName): string {
    // Remove any non-alphanumeric characters except spaces
    $poolName = preg_replace('/[^a-zA-Z0-9\s]/', '', $poolName);
    
    // Convert to uppercase
    $poolName = strtoupper($poolName);
    
    // Remove common prefixes like "POOL" or "POOL " if it's not just "POOL" by itself
    if (strlen($poolName) > 4 && strpos($poolName, 'POOL ') === 0) {
        $poolName = substr($poolName, 5);
    }
    
    // Trim any remaining whitespace
    $poolName = trim($poolName);
    
    return $poolName;
}

/**
 * Extract components from placeholder string
 * 
 * @param string $placeholder Placeholder string (e.g., "A1", "u11 Pool A1")
 * @param bool $debug Whether to include debug information
 * @return array|null Array with extracted components or null if not matching expected format
 */
function extractPlaceholderComponents(string $placeholder, bool $debug = false): ?array {
    if ($debug) {
        error_log("Analyzing placeholder: '" . $placeholder . "'");
    }
    
    // First, try to detect division and pool separately
    // This is specifically for formats like "u11 Pool A2" or "u12 Pool B3"
    if (preg_match('/(u\d+)\s+Pool\s+([A-Z])(\d+)/i', $placeholder, $matches)) {
        if ($debug) {
            error_log("Matched division-pool pattern: Division: " . $matches[1] . ", Pool: " . $matches[2] . ", Position: " . $matches[3]);
        }
        return [
            'type' => 'pool_position',
            'division' => strtolower(trim($matches[1])),
            'pool' => strtoupper(trim($matches[2])),
            'position' => (int)$matches[3],
            'original_pool_text' => 'Pool ' . $matches[2], // Store the original text
        ];
    }
    
    // Special case for just division and pool letter/number without the word "Pool"
    if (preg_match('/(u\d+)\s+([A-Z])(\d+)/i', $placeholder, $matches)) {
        if ($debug) {
            error_log("Matched simplified division-pool pattern: Division: " . $matches[1] . ", Pool: " . $matches[2] . ", Position: " . $matches[3]);
        }
        return [
            'type' => 'pool_position',
            'division' => strtolower(trim($matches[1])),
            'pool' => strtoupper(trim($matches[2])),
            'position' => (int)$matches[3],
            'original_pool_text' => $matches[2], // Store the original text
        ];
    }
    
    // Check for Winner of Game format (more flexible pattern)
    if (preg_match('/Winner\s+of\s+Game\s+#?(\d+)/i', $placeholder, $matches)) {
        if ($debug) {
            error_log("Matched 'Winner of Game' pattern: Game ID " . $matches[1]);
        }
        return [
            'type' => 'game_winner',
            'game_id' => (int)$matches[1]
        ];
    }
    
    // Check for basic pool position format "A1", "B2", etc.
    if (preg_match('/^([A-Z])(\d+)$/i', $placeholder, $matches)) {
        if ($debug) {
            error_log("Matched simple pool pattern: Pool: " . $matches[1] . ", Position: " . $matches[2]);
        }
        return [
            'type' => 'pool_position',
            'division' => null,
            'pool' => strtoupper($matches[1]),
            'position' => (int)$matches[2],
            'original_pool_text' => $matches[1], // Store the original text
        ];
    }
    
    // Special case for "Pool X#" format
    if (preg_match('/Pool\s+([A-Z])(\d+)/i', $placeholder, $matches)) {
        if ($debug) {
            error_log("Matched 'Pool X#' pattern: Pool: " . $matches[1] . ", Position: " . $matches[2]);
        }
        return [
            'type' => 'pool_position',
            'division' => null,
            'pool' => strtoupper($matches[1]),
            'position' => (int)$matches[2],
            'original_pool_text' => 'Pool ' . $matches[1], // Store the original text
        ];
    }
    
    // Last resort: Try one more flexible pattern for any letter+number combination
    // This is dangerous but might catch some edge cases
    if (preg_match('/([A-Z])[^0-9]*(\d+)/i', $placeholder, $matches)) {
        if ($debug) {
            error_log("Matched flexible pool pattern: Pool: " . $matches[1] . ", Position: " . $matches[2]);
        }
        return [
            'type' => 'pool_position',
            'division' => null,
            'pool' => strtoupper($matches[1]),
            'position' => (int)$matches[2],
            'original_pool_text' => $matches[1], // Store the original text
        ];
    }
    
    if ($debug) {
        error_log("Failed to match any pattern for placeholder: '" . $placeholder . "'");
    }
    
    // If no patterns match, return null
    return null;
}

/**
 * Resolve team placeholder (e.g., "A1", "B2") to actual team based on standings
 * 
 * @param PDO $pdo Database connection
 * @param string $placeholder Placeholder code (e.g., "A1", "B2", "u11 Pool A1", "Winner of Game #30")
 * @param int $year Tournament year
 * @param string|null $gameType Optional game type (division) to filter by (e.g. "u11", "u12")
 * @param bool $debug Whether to include debug information in the result
 * @return array|null Team information or null if not resolvable
 */
function resolveTeamFromPlaceholder(PDO $pdo, string $placeholder, int $year = 2025, ?string $gameType = null, bool $debug = false): ?array {
    // Initialize error tracking
    $errorInfo = [
        'error' => false,
        'message' => '',
        'step' => '',
        'details' => []
    ];
    
    if (empty($placeholder)) {
        if ($debug) error_log("Empty placeholder provided");
        $errorInfo = [
            'error' => true,
            'message' => 'Empty placeholder provided',
            'step' => 'validation',
            'details' => ['placeholder' => $placeholder]
        ];
        return $debug ? ['error_info' => $errorInfo] : null;
    }
    
    // Extract placeholder components
    $components = extractPlaceholderComponents($placeholder, $debug);
    
    if ($debug) {
        error_log("Placeholder: '" . $placeholder . "'");
        error_log("Components: " . ($components ? json_encode($components) : "Failed to parse"));
    }
    
    if (!$components) {
        if ($debug) error_log("Could not parse placeholder format: " . $placeholder);
        $errorInfo = [
            'error' => true,
            'message' => 'Could not parse placeholder format',
            'step' => 'parsing',
            'details' => ['placeholder' => $placeholder]
        ];
        return $debug ? ['error_info' => $errorInfo] : null;
    }
    
    // If game_type (division) is not provided but available in components, use it
    if ($gameType === null && isset($components['division'])) {
        $gameType = $components['division'];
    }
    
    if ($debug && $gameType) {
        error_log("Using division context: " . $gameType);
    }
    
    // Handle different placeholder types
    if ($components['type'] === 'game_winner') {
        $result = resolveGameWinnerTeam($pdo, $components['game_id'], $debug);
        
        // Check if we have an error from the game winner resolution
        if (is_array($result) && isset($result['error_info'])) {
            return $result;
        }
        
        if (!$result) {
            $errorInfo = [
                'error' => true,
                'message' => 'Could not resolve game winner',
                'step' => 'game_winner_lookup',
                'details' => [
                    'game_id' => $components['game_id'],
                    'placeholder' => $placeholder
                ]
            ];
            return $debug ? ['error_info' => $errorInfo] : null;
        }
        
        return $result;
    }
    
    if ($components['type'] === 'pool_position') {
        $poolLetter = $components['pool'];
        $position = $components['position'] - 1; // Convert to zero-based index
        $originalPoolText = $components['original_pool_text'] ?? $poolLetter;
        
        if ($debug) {
            error_log("Looking for pool with name: '" . $poolLetter . "'");
            error_log("Original pool text in placeholder: '" . $originalPoolText . "'");
        }
        
        // Try using our new pool name mapping system first
        $poolVariationResult = findPoolByVariations($pdo, $poolLetter, $gameType, $debug);
        
        if ($poolVariationResult) {
            $poolResult = $poolVariationResult;
            
            if ($debug) {
                error_log("Found pool using variation matching: ID=" . $poolResult['pool_id'] . ", Name='" . $poolResult['pool_name'] . "'");
            }
        } else {
            // Fall back to the original matching logic
            
            // Get pool ID using a more flexible query
            // First, try exact match
            $poolStmt = $pdo->prepare("
                SELECT pool_id, pool_name FROM pools WHERE pool_name = :poolName
            ");
            $poolStmt->execute([':poolName' => $poolLetter]);
            $poolResult = $poolStmt->fetch(PDO::FETCH_ASSOC);
            
            // If exact match fails, try more flexible approaches
            if (!$poolResult) {
                // Try with normalized pool name (removing "Pool " prefix if present)
                $normalizedPoolName = normalizePoolName($poolLetter);
                
                if ($debug) {
                    error_log("Exact match failed, trying with normalized pool name: '" . $normalizedPoolName . "'");
                }
                
                $poolStmt = $pdo->prepare("
                    SELECT pool_id, pool_name FROM pools 
                    WHERE UPPER(TRIM(pool_name)) = :normPoolName
                ");
                $poolStmt->execute([':normPoolName' => $normalizedPoolName]);
                $poolResult = $poolStmt->fetch(PDO::FETCH_ASSOC);
                
                // If still no match, try with pattern matching
                if (!$poolResult) {
                    if ($debug) {
                        error_log("Normalized match failed, trying with LIKE pattern matching");
                    }
                    
                    // Try searching for pool names that contain the letter
                    $poolStmt = $pdo->prepare("
                        SELECT pool_id, pool_name FROM pools 
                        WHERE pool_name LIKE :poolPattern
                        OR pool_name LIKE :poolPatternWithSpace
                        OR pool_name = :singleLetter
                        ORDER BY 
                            CASE 
                                WHEN pool_name = :singleLetter THEN 1 
                                WHEN pool_name LIKE :exactPattern THEN 2
                                ELSE 3 
                            END
                    ");
                    $poolStmt->execute([
                        ':poolPattern' => '%' . $poolLetter . '%',
                        ':poolPatternWithSpace' => '%Pool ' . $poolLetter . '%',
                        ':singleLetter' => $poolLetter,
                        ':exactPattern' => $poolLetter . '%'
                    ]);
                    $poolResult = $poolStmt->fetch(PDO::FETCH_ASSOC);
                }
            }
            
            if (!$poolResult) {
                // Final attempt: Check if this is a division-pool combination
                if ($gameType && $debug) {
                    error_log("Trying division-pool combination match");
                    
                    // Try formats like "u11 A", "u11_A", etc.
                    $possibleFormats = [
                        $gameType . ' ' . $poolLetter,
                        $gameType . '_' . $poolLetter,
                        $gameType . $poolLetter,
                        $gameType . ' Pool ' . $poolLetter,
                        'Pool ' . $poolLetter . ' ' . $gameType,
                        $poolLetter . ' ' . $gameType
                    ];
                    
                    foreach ($possibleFormats as $format) {
                        error_log("Trying possible format: '" . $format . "'");
                        
                        $poolStmt = $pdo->prepare("
                            SELECT pool_id, pool_name FROM pools WHERE pool_name = :format
                        ");
                        $poolStmt->execute([':format' => $format]);
                        $poolResult = $poolStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($poolResult) {
                            error_log("Found match with format: '" . $format . "'");
                            break;
                        }
                    }
                }
            }
        }
        
        if (!$poolResult) {
            if ($debug) {
                error_log("Pool not found with any naming convention for: '" . $poolLetter . "'");
                
                // Check all available pools for debugging
                $allPoolsStmt = $pdo->query("SELECT pool_id, pool_name FROM pools ORDER BY pool_name");
                $allPools = $allPoolsStmt->fetchAll(PDO::FETCH_ASSOC);
                error_log("Available pools: " . json_encode($allPools));
                
                // Get the variations we tried
                $variations = getPoolNameVariations($poolLetter, $gameType);
                
                // Show attempted matches for additional clarity
                error_log("Attempted to match:");
                error_log(" - Exact match: '" . $poolLetter . "'");
                error_log(" - Normalized match: '" . normalizePoolName($poolLetter) . "'");
                error_log(" - All variations: " . json_encode($variations));
            }
            
            $errorInfo = [
                'error' => true,
                'message' => 'Pool not found',
                'step' => 'pool_lookup',
                'details' => [
                    'pool_letter' => $poolLetter,
                    'original_pool_text' => $originalPoolText,
                    'normalized_pool_name' => normalizePoolName($poolLetter),
                    'division' => $gameType,
                    'placeholder' => $placeholder,
                    'available_pools' => $debug ? $allPools : [],
                    'attempted_matches' => $debug ? [
                        'exact' => $poolLetter,
                        'normalized' => normalizePoolName($poolLetter),
                        'variations' => getPoolNameVariations($poolLetter, $gameType)
                    ] : []
                ]
            ];
            return $debug ? ['error_info' => $errorInfo] : null;
        }
        
        $poolId = $poolResult['pool_id'];
        $matchedPoolName = $poolResult['pool_name'];
        
        if ($debug) {
            error_log("Successfully matched pool: ID=" . $poolId . ", Name='" . $matchedPoolName . "'");
            if ($matchedPoolName !== $poolLetter) {
                error_log("Note: Original pool letter '" . $poolLetter . "' was matched to database pool name '" . $matchedPoolName . "'");
            }
        }
        
        // Build query to get teams from the specified pool, ordered by standings criteria
        $sql = "
            SELECT 
                t.team_id,
                t.team_name,
                p.pool_name,
                r.division,
                COALESCE(SUM(gr.win), 0) AS wins,
                COALESCE(SUM(gr.points_for), 0) - COALESCE(SUM(gr.points_against), 0) AS plus_minus,
                COALESCE(SUM(gr.points_for), 0) AS points_for
            FROM 
                teams t
            JOIN 
                team_pools tp ON t.team_id = tp.team_id
            JOIN 
                pools p ON tp.pool_id = p.pool_id
            JOIN 
                registrations r ON t.team_id = r.team_id
            LEFT JOIN 
                game_results gr ON t.team_id = gr.team_id
            LEFT JOIN
                schedule s ON gr.game_id = s.game_id AND s.game_category = 'pool'
            WHERE 
                p.pool_id = :poolId AND r.year = :year AND r.status = 1
                AND (gr.game_id IS NULL OR s.game_id IS NOT NULL)
        ";
        
        // Add division filter if provided
        $params = [
            ':poolId' => $poolId,
            ':year' => $year
        ];
        
        if ($gameType !== null) {
            $sql .= " AND r.division = :division";
            $params[':division'] = $gameType;
        }
        
        $sql .= "
            GROUP BY 
                t.team_id, t.team_name, p.pool_name, r.division
            ORDER BY 
                wins DESC, plus_minus DESC, points_for DESC
        ";
        
        if ($debug) {
            error_log("SQL: " . $sql);
            error_log("Params: " . json_encode($params));
        }
        
        try {
            $teamStmt = $pdo->prepare($sql);
            $teamStmt->execute($params);
            
            $teams = $teamStmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($debug) {
                error_log("Teams found: " . count($teams));
                if (count($teams) > 0) {
                    error_log("All teams in pool: " . json_encode($teams));
                } else {
                    // Check if there are any teams in this pool at all
                    $checkTeamsStmt = $pdo->prepare("
                        SELECT 
                            t.team_id, 
                            t.team_name,
                            r.division
                        FROM 
                            teams t
                        JOIN 
                            team_pools tp ON t.team_id = tp.team_id
                        JOIN 
                            registrations r ON t.team_id = r.team_id
                        WHERE 
                            tp.pool_id = :poolId
                    ");
                    $checkTeamsStmt->execute([':poolId' => $poolId]);
                    $teamsInPool = $checkTeamsStmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    error_log("Teams assigned to this pool (ignoring division/status): " . json_encode($teamsInPool));
                }
                error_log("Looking for position: " . $position . " (zero-based index)");
            }
            
            // Empty teams array
            if (empty($teams)) {
                $errorInfo = [
                    'error' => true,
                    'message' => 'No teams found in pool',
                    'step' => 'team_lookup',
                    'details' => [
                        'pool_id' => $poolId,
                        'pool_letter' => $poolLetter,
                        'matched_pool_name' => $matchedPoolName,
                        'division' => $gameType,
                        'placeholder' => $placeholder,
                        'teams_in_pool' => $debug ? $teamsInPool : []
                    ]
                ];
                return $debug ? ['error_info' => $errorInfo] : null;
            }
            
            // Position out of range
            if (!isset($teams[$position])) {
                if ($debug) {
                    error_log("Position not available in standings: " . ($position + 1) . " out of " . count($teams) . " teams");
                }
                
                $errorInfo = [
                    'error' => true,
                    'message' => 'Position not available in standings',
                    'step' => 'position_lookup',
                    'details' => [
                        'position' => $position + 1,
                        'available_positions' => count($teams),
                        'placeholder' => $placeholder,
                        'pool_letter' => $poolLetter,
                        'matched_pool_name' => $matchedPoolName,
                        'teams' => $debug ? $teams : []
                    ]
                ];
                return $debug ? ['error_info' => $errorInfo] : null;
            }
            
            // Add pool matching info to return data
            $team = $teams[$position];
            $team['matched_pool_name'] = $matchedPoolName;
            $team['original_pool_reference'] = $originalPoolText;
            
            // Successfully found team
            if ($debug) {
                error_log("Successfully resolved team: " . $team['team_name']);
                error_log("From pool: '" . $matchedPoolName . "' (originally referenced as '" . $originalPoolText . "')");
            }
            return $team;
            
        } catch (PDOException $e) {
            if ($debug) {
                error_log("Database error: " . $e->getMessage());
            }
            
            $errorInfo = [
                'error' => true,
                'message' => 'Database error during team lookup',
                'step' => 'database_query',
                'details' => [
                    'sql_error' => $e->getMessage(),
                    'placeholder' => $placeholder,
                    'pool_letter' => $poolLetter,
                    'matched_pool_name' => $matchedPoolName
                ]
            ];
            return $debug ? ['error_info' => $errorInfo] : null;
        }
    }
    
    // If we get here, the placeholder type wasn't handled
    if ($debug) error_log("Unhandled placeholder type: " . ($components['type'] ?? 'unknown'));
    
    $errorInfo = [
        'error' => true,
        'message' => 'Unhandled placeholder type',
        'step' => 'type_handling',
        'details' => [
            'type' => $components['type'] ?? 'unknown',
            'placeholder' => $placeholder
        ]
    ];
    return $debug ? ['error_info' => $errorInfo] : null;
}

/**
 * Resolve a team based on a game winner reference
 * 
 * @param PDO $pdo Database connection
 * @param int $gameId ID of the game to find the winner for
 * @param bool $debug Whether to include debug information in the result
 * @return array|null Team information or null if not resolvable
 */
function resolveGameWinnerTeam(PDO $pdo, int $gameId, bool $debug = false): ?array {
    // Initialize error tracking
    $errorInfo = [
        'error' => false,
        'message' => '',
        'step' => '',
        'details' => []
    ];
    
    if ($gameId <= 0) {
        if ($debug) error_log("Invalid game ID: " . $gameId);
        
        $errorInfo = [
            'error' => true,
            'message' => 'Invalid game ID',
            'step' => 'validation',
            'details' => ['game_id' => $gameId]
        ];
        return $debug ? ['error_info' => $errorInfo] : null;
    }
    
    try {
        // First, check if the game exists and get its type (division)
        $gameStmt = $pdo->prepare("
            SELECT game_id, game_type, home_team_id, away_team_id FROM schedule WHERE game_id = :gameId
        ");
        $gameStmt->execute([':gameId' => $gameId]);
        $game = $gameStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$game) {
            if ($debug) error_log("Game not found: " . $gameId);
            
            $errorInfo = [
                'error' => true,
                'message' => 'Game not found',
                'step' => 'game_lookup',
                'details' => ['game_id' => $gameId]
            ];
            return $debug ? ['error_info' => $errorInfo] : null;
        }
        
        if ($debug) {
            error_log("Found game: " . $gameId . ", type: " . $game['game_type']);
            error_log("Game teams: home_id=" . ($game['home_team_id'] ?? 'NULL') . ", away_id=" . ($game['away_team_id'] ?? 'NULL'));
        }
        
        // Check if we even have teams assigned to this game
        if (empty($game['home_team_id']) || empty($game['away_team_id'])) {
            if ($debug) error_log("Game #" . $gameId . " doesn't have both teams assigned yet");
            
            $errorInfo = [
                'error' => true,
                'message' => 'Game does not have both teams assigned',
                'step' => 'team_assignment',
                'details' => [
                    'game_id' => $gameId,
                    'home_team_id' => $game['home_team_id'] ?? null,
                    'away_team_id' => $game['away_team_id'] ?? null
                ]
            ];
            return $debug ? ['error_info' => $errorInfo] : null;
        }
        
        // Look for a team with win=1 for this game
        $winnerStmt = $pdo->prepare("
            SELECT 
                gr.team_id,
                gr.win,
                t.team_name,
                r.division
            FROM 
                game_results gr
            JOIN
                teams t ON gr.team_id = t.team_id
            JOIN
                registrations r ON t.team_id = r.team_id
            WHERE 
                gr.game_id = :gameId
            ORDER BY
                gr.win DESC
        ");
        $winnerStmt->execute([':gameId' => $gameId]);
        $results = $winnerStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($debug) {
            error_log("Game results for game #" . $gameId . ": " . json_encode($results));
        }
        
        // No results for this game
        if (empty($results)) {
            if ($debug) error_log("No results found for game " . $gameId);
            
            $errorInfo = [
                'error' => true,
                'message' => 'No game results found',
                'step' => 'results_lookup',
                'details' => ['game_id' => $gameId]
            ];
            return $debug ? ['error_info' => $errorInfo] : null;
        }
        
        // Find the winner among results
        $winner = null;
        foreach ($results as $result) {
            if ($result['win'] == 1) {
                $winner = $result;
                break;
            }
        }
        
        if ($winner) {
            if ($debug) error_log("Found winner for game " . $gameId . ": " . $winner['team_name']);
            return $winner;
        } else {
            if ($debug) error_log("No winner found for game " . $gameId);
            
            // Get the game details to provide better debug info
            if ($debug) {
                $detailsStmt = $pdo->prepare("
                    SELECT 
                        s.home_team_id, 
                        s.away_team_id, 
                        h.team_name AS home_team_name, 
                        a.team_name AS away_team_name,
                        s.placeholder_home,
                        s.placeholder_away,
                        COUNT(gr.game_id) AS has_results
                    FROM schedule s
                    LEFT JOIN teams h ON s.home_team_id = h.team_id
                    LEFT JOIN teams a ON s.away_team_id = a.team_id
                    LEFT JOIN game_results gr ON s.game_id = gr.game_id
                    WHERE s.game_id = :gameId
                    GROUP BY s.game_id, s.home_team_id, s.away_team_id, h.team_name, a.team_name, s.placeholder_home, s.placeholder_away
                ");
                $detailsStmt->execute([':gameId' => $gameId]);
                $details = $detailsStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($details) {
                    error_log("Game details for " . $gameId . ":");
                    error_log("Home team: " . ($details['home_team_name'] ?? $details['placeholder_home'] ?? 'None'));
                    error_log("Away team: " . ($details['away_team_name'] ?? $details['placeholder_away'] ?? 'None'));
                    error_log("Has results: " . ($details['has_results'] ? 'Yes' : 'No'));
                }
            }
            
            $errorInfo = [
                'error' => true,
                'message' => 'No winner designated in game results',
                'step' => 'winner_determination',
                'details' => [
                    'game_id' => $gameId,
                    'results' => $results
                ]
            ];
            return $debug ? ['error_info' => $errorInfo] : null;
        }
    } catch (PDOException $e) {
        // Log the error but continue
        error_log("Error resolving game winner: " . $e->getMessage());
        
        $errorInfo = [
            'error' => true,
            'message' => 'Database error during game winner lookup',
            'step' => 'database_query',
            'details' => [
                'sql_error' => $e->getMessage(),
                'game_id' => $gameId
            ]
        ];
        return $debug ? ['error_info' => $errorInfo] : null;
    }
}

/**
 * Get HTML display for team with placeholder
 * 
 * @param PDO $pdo Database connection
 * @param string $placeholder Placeholder code
 * @param string|null $teamName Team name if already assigned
 * @param int $teamId Team ID if already assigned
 * @param int $year Tournament year
 * @return string Formatted HTML for displaying team
 */
function getTeamDisplayWithPlaceholder(PDO $pdo, string $placeholder, ?string $teamName, ?int $teamId, int $year = 2025): string {
    if (!empty($teamName)) {
        // If team is already assigned, show "Team Name (A1)"
        return htmlspecialchars($teamName) . ' <span class="placeholder-code">(' . htmlspecialchars($placeholder) . ')</span>';
    }
    
    // Handle "Winner of Game #X" placeholders
    if (preg_match('/Winner of Game #?(\d+)/i', $placeholder, $matches)) {
        $gameId = $matches[1];
        
        // Try to find the winner of the referenced game using our helper function
        $winner = resolveGameWinnerTeam($pdo, (int)$gameId);
        
        if ($winner) {
            return htmlspecialchars($winner['team_name']) . 
                   ' <span class="placeholder-code">(' . htmlspecialchars($placeholder) . ')</span>';
        }
        
        // If no winner found, try to get game details to show teams
        try {
            $gameStmt = $pdo->prepare("
                SELECT 
                    g.home_team_id, g.away_team_id,
                    h.team_name AS home_team_name,
                    a.team_name AS away_team_name
                FROM 
                    schedule g
                LEFT JOIN 
                    teams h ON g.home_team_id = h.team_id
                LEFT JOIN 
                    teams a ON g.away_team_id = a.team_id
                WHERE 
                    g.game_id = :gameId
            ");
            $gameStmt->execute([':gameId' => $gameId]);
            $gameResult = $gameStmt->fetch(PDO::FETCH_ASSOC);
            
            // If we can determine the teams, display them
            if ($gameResult && !empty($gameResult['home_team_name']) && !empty($gameResult['away_team_name'])) {
                return '<span class="placeholder-unresolved">' . htmlspecialchars($placeholder) . 
                       ' (' . htmlspecialchars($gameResult['home_team_name']) . ' vs ' . 
                       htmlspecialchars($gameResult['away_team_name']) . ')</span>';
            }
        } catch (PDOException $e) {
            // Log the error but continue
            error_log("Error getting game details: " . $e->getMessage());
        }
        
        // If we can't resolve the game or there was an error, just show the placeholder
        return '<span class="placeholder-unresolved">' . htmlspecialchars($placeholder) . '</span>';
    }
    
    // Handle standard pool position placeholders (A1, B2, etc.)
    // Try to resolve the placeholder using our enhanced function
    $team = resolveTeamFromPlaceholder($pdo, $placeholder, $year);
    
    if ($team) {
        // Placeholder is resolved, show "Team Name (A1)"
        return htmlspecialchars($team['team_name']) . ' <span class="placeholder-code">(' . htmlspecialchars($placeholder) . ')</span>';
    } else {
        // Placeholder can't be resolved yet, just show the placeholder
        return '<span class="placeholder-unresolved">' . htmlspecialchars($placeholder) . '</span>';
    }
}

/**
 * Check if all pool games are complete for a given year
 * 
 * @param PDO $pdo Database connection
 * @param int $year Tournament year
 * @return bool True if all pool games have results
 */
function areAllPoolGamesComplete(PDO $pdo, int $year = 2025): bool {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS total_games, 
               COUNT(gr.game_id) AS games_with_results
        FROM schedule s
        LEFT JOIN game_results gr ON s.game_id = gr.game_id
        WHERE s.game_category = 'pool'
    ");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return ($result['total_games'] > 0 && $result['total_games'] == $result['games_with_results']);
}

/**
 * Check standing entries for each pool to help debug issues
 * 
 * @param PDO $pdo Database connection
 * @param bool $debug Whether to enable detailed debug logging
 * @return array Diagnostic information about pools and standings
 */
function analyzePoolStandings(PDO $pdo, bool $debug = false): array {
    $diagnostics = [];
    
    try {
        // Get all pools
        $poolsStmt = $pdo->query("SELECT pool_id, pool_name FROM pools ORDER BY pool_name");
        $pools = $poolsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($pools as $pool) {
            $poolId = $pool['pool_id'];
            $poolName = $pool['pool_name'];
            
            // Count teams in this pool
            $teamsStmt = $pdo->prepare("
                SELECT COUNT(*) as team_count
                FROM team_pools
                WHERE pool_id = :poolId
            ");
            $teamsStmt->execute([':poolId' => $poolId]);
            $teamCount = $teamsStmt->fetch(PDO::FETCH_ASSOC)['team_count'];
            
            // Get actual teams in pool
            $teamListStmt = $pdo->prepare("
                SELECT 
                    t.team_id, 
                    t.team_name,
                    r.division,
                    r.status
                FROM 
                    teams t
                JOIN 
                    team_pools tp ON t.team_id = tp.team_id
                JOIN 
                    registrations r ON t.team_id = r.team_id
                WHERE 
                    tp.pool_id = :poolId
                ORDER BY 
                    r.division, t.team_name
            ");
            $teamListStmt->execute([':poolId' => $poolId]);
            $teams = $teamListStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get standings for this pool
            $standingsStmt = $pdo->prepare("
                SELECT 
                    t.team_id,
                    t.team_name,
                    r.division,
                    COALESCE(SUM(gr.win), 0) AS wins,
                    COALESCE(SUM(gr.points_for), 0) - COALESCE(SUM(gr.points_against), 0) AS plus_minus,
                    COALESCE(SUM(gr.points_for), 0) AS points_for
                FROM 
                    teams t
                JOIN 
                    team_pools tp ON t.team_id = tp.team_id
                JOIN 
                    pools p ON tp.pool_id = p.pool_id
                JOIN 
                    registrations r ON t.team_id = r.team_id
                LEFT JOIN 
                    game_results gr ON t.team_id = gr.team_id
                LEFT JOIN
                    schedule s ON gr.game_id = s.game_id AND s.game_category = 'pool'
                WHERE 
                    p.pool_id = :poolId AND r.status = 1
                    AND (gr.game_id IS NULL OR s.game_id IS NOT NULL)
                GROUP BY 
                    t.team_id, t.team_name, r.division
                ORDER BY 
                    r.division, wins DESC, plus_minus DESC, points_for DESC
            ");
            $standingsStmt->execute([':poolId' => $poolId]);
            $standings = $standingsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $diagnostics[$poolName] = [
                'pool_id' => $poolId,
                'team_count' => $teamCount,
                'team_list' => $teams,
                'standings' => $standings,
                'has_standings' => !empty($standings)
            ];
            
            if ($debug) {
                error_log("Pool {$poolName} (ID: {$poolId}): {$teamCount} teams assigned");
                error_log("Teams in pool {$poolName}: " . json_encode($teams));
                error_log("Standings for pool {$poolName}: " . json_encode($standings));
            }
        }
    } catch (PDOException $e) {
        if ($debug) {
            error_log("Error analyzing pool standings: " . $e->getMessage());
        }
    }
    
    return $diagnostics;
}

/**
 * Auto-update playoff games based on current standings
 * 
 * @param PDO $pdo Database connection
 * @param int $year Tournament year
 * @param bool $requireAllPoolGamesComplete Whether to require all pool games to be complete
 * @param bool $debug Whether to enable detailed debug logging
 * @return array Detailed result information including update count and diagnostics
 */
function autoUpdatePlayoffGames(PDO $pdo, int $year = 2025, bool $requireAllPoolGamesComplete = true, bool $debug = false): array {
    $result = [
        'update_count' => 0,
        'message' => '',
        'all_pool_games_complete' => false,
        'games_found' => 0,
        'games_attempted' => 0,
        'games_updated' => 0,
        'diagnostics' => [],
        'pool_analysis' => [],
        'available_pools' => [], // Add available pools for easier diagnostics
        'pool_mapping_info' => [] // Add pool mapping info
    ];
    
    if ($debug) {
        error_log("========= STARTING AUTO-UPDATE PLAYOFF GAMES =========");
        // Get all available pools for diagnostics
        try {
            $poolsStmt = $pdo->query("SELECT pool_id, pool_name FROM pools ORDER BY pool_name");
            $pools = $poolsStmt->fetchAll(PDO::FETCH_ASSOC);
            $result['available_pools'] = $pools;
            error_log("Available pools: " . json_encode($pools));
            
            // Create pool mapping information
            $poolMappingInfo = [];
            foreach ($pools as $pool) {
                $poolName = $pool['pool_name'];
                // Extract pool letter if possible
                if (preg_match('/([A-Z])/i', $poolName, $matches)) {
                    $letter = strtoupper($matches[1]);
                    $variations = getPoolNameVariations($letter);
                    $poolMappingInfo[$poolName] = [
                        'pool_id' => $pool['pool_id'],
                        'pool_name' => $poolName,
                        'extracted_letter' => $letter,
                        'normalized' => normalizePoolName($poolName),
                        'variations' => $variations,
                        'will_match_for' => array_merge([$letter], $variations)
                    ];
                }
            }
            $result['pool_mapping_info'] = $poolMappingInfo;
            
        } catch (PDOException $e) {
            error_log("Error fetching available pools: " . $e->getMessage());
        }
        
        // Analyze all pools and standings for diagnostics
        $result['pool_analysis'] = analyzePoolStandings($pdo, $debug);
    }
    
    // Check if all pool games are complete (if required)
    $allPoolGamesComplete = areAllPoolGamesComplete($pdo, $year);
    $result['all_pool_games_complete'] = $allPoolGamesComplete;
    
    if ($debug) {
        error_log("All pool games complete: " . ($allPoolGamesComplete ? 'Yes' : 'No'));
    }
    
    if ($requireAllPoolGamesComplete && !$allPoolGamesComplete) {
        $result['message'] = "Not all pool games are complete. Auto-update aborted.";
        if ($debug) error_log($result['message']);
        return $result;
    }
    
    // Get all playoff games with placeholders
    $stmt = $pdo->prepare("
        SELECT 
            game_id, 
            game_type,
            game_category,
            placeholder_home, 
            placeholder_away
        FROM 
            schedule
        WHERE 
            game_category != 'pool' AND
            (placeholder_home IS NOT NULL OR placeholder_away IS NOT NULL)
    ");
    $stmt->execute();
    $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $result['games_found'] = count($games);
    
    if ($debug) {
        error_log("Found " . count($games) . " playoff games with placeholders");
    }
    
    if (empty($games)) {
        $result['message'] = "No playoff games found with placeholders.";
        return $result;
    }
    
    foreach ($games as $game) {
        $gameId = $game['game_id'];
        $gameType = $game['game_type']; // This should be the division (u11, u12, etc.)
        $gameCategory = $game['game_category'];
        
        if ($debug) {
            error_log("\n--- Processing game #" . $gameId . " (" . $gameType . ", " . $gameCategory . ") ---");
        }
        
        $result['games_attempted']++;
        $gameDiagnostics = [
            'game_id' => $gameId,
            'game_type' => $gameType,
            'game_category' => $gameCategory,
            'home' => [
                'placeholder' => $game['placeholder_home'],
                'resolved' => false,
                'team_id' => null,
                'team_name' => null,
                'matched_pool' => null,
                'reason' => null,
                'error_details' => null
            ],
            'away' => [
                'placeholder' => $game['placeholder_away'],
                'resolved' => false,
                'team_id' => null,
                'team_name' => null,
                'matched_pool' => null,
                'reason' => null,
                'error_details' => null
            ],
            'updated' => false
        ];
        
        $homeTeamId = null;
        $awayTeamId = null;
        
        // Resolve home team placeholder - use the enhanced resolveTeamFromPlaceholder function
        if (!empty($game['placeholder_home'])) {
            if ($debug) error_log("Resolving home placeholder: '" . $game['placeholder_home'] . "'");
            
            $homeTeam = resolveTeamFromPlaceholder($pdo, $game['placeholder_home'], $year, $gameType, $debug);
            
            // Check if we got an error info object instead of a team
            if (is_array($homeTeam) && isset($homeTeam['error_info'])) {
                $errorInfo = $homeTeam['error_info'];
                $gameDiagnostics['home']['reason'] = $errorInfo['message'] . ' (' . $errorInfo['step'] . ')';
                $gameDiagnostics['home']['error_details'] = $errorInfo;
                
                if ($debug) {
                    error_log("Home team resolution failed: " . $errorInfo['message'] . " in step: " . $errorInfo['step']);
                    error_log("Error details: " . json_encode($errorInfo['details']));
                }
            } else if ($homeTeam) {
                $homeTeamId = $homeTeam['team_id'];
                $gameDiagnostics['home']['resolved'] = true;
                $gameDiagnostics['home']['team_id'] = $homeTeamId;
                $gameDiagnostics['home']['team_name'] = $homeTeam['team_name'];
                $gameDiagnostics['home']['matched_pool'] = $homeTeam['matched_pool_name'] ?? null;
                
                if ($debug) {
                    error_log("Resolved home team: " . $homeTeam['team_name'] . " (ID: " . $homeTeamId . ")");
                    if (isset($homeTeam['matched_pool_name']) && isset($homeTeam['original_pool_reference'])) {
                        error_log("Matched pool: '" . $homeTeam['matched_pool_name'] . "' from reference '" . $homeTeam['original_pool_reference'] . "'");
                    }
                }
            } else {
                $gameDiagnostics['home']['reason'] = "Unknown error resolving placeholder";
                if ($debug) error_log("Could not resolve home team from placeholder: '" . $game['placeholder_home'] . "'");
            }
        } else {
            $gameDiagnostics['home']['reason'] = "No placeholder provided";
            if ($debug) error_log("No home placeholder for game #" . $gameId);
        }
        
        // Resolve away team placeholder - use the enhanced resolveTeamFromPlaceholder function
        if (!empty($game['placeholder_away'])) {
            if ($debug) error_log("Resolving away placeholder: '" . $game['placeholder_away'] . "'");
            
            $awayTeam = resolveTeamFromPlaceholder($pdo, $game['placeholder_away'], $year, $gameType, $debug);
            
            // Check if we got an error info object instead of a team
            if (is_array($awayTeam) && isset($awayTeam['error_info'])) {
                $errorInfo = $awayTeam['error_info'];
                $gameDiagnostics['away']['reason'] = $errorInfo['message'] . ' (' . $errorInfo['step'] . ')';
                $gameDiagnostics['away']['error_details'] = $errorInfo;
                
                if ($debug) {
                    error_log("Away team resolution failed: " . $errorInfo['message'] . " in step: " . $errorInfo['step']);
                    error_log("Error details: " . json_encode($errorInfo['details']));
                }
            } else if ($awayTeam) {
                $awayTeamId = $awayTeam['team_id'];
                $gameDiagnostics['away']['resolved'] = true;
                $gameDiagnostics['away']['team_id'] = $awayTeamId;
                $gameDiagnostics['away']['team_name'] = $awayTeam['team_name'];
                $gameDiagnostics['away']['matched_pool'] = $awayTeam['matched_pool_name'] ?? null;
                
                if ($debug) {
                    error_log("Resolved away team: " . $awayTeam['team_name'] . " (ID: " . $awayTeamId . ")");
                    if (isset($awayTeam['matched_pool_name']) && isset($awayTeam['original_pool_reference'])) {
                        error_log("Matched pool: '" . $awayTeam['matched_pool_name'] . "' from reference '" . $awayTeam['original_pool_reference'] . "'");
                    }
                }
            } else {
                $gameDiagnostics['away']['reason'] = "Unknown error resolving placeholder";
                if ($debug) error_log("Could not resolve away team from placeholder: '" . $game['placeholder_away'] . "'");
            }
        } else {
            $gameDiagnostics['away']['reason'] = "No placeholder provided";
            if ($debug) error_log("No away placeholder for game #" . $gameId);
        }
        
        // Update the game if we have both teams
        if ($homeTeamId && $awayTeamId) {
            if ($debug) error_log("Updating game #" . $gameId . " with home team ID: " . $homeTeamId . ", away team ID: " . $awayTeamId);
            
            $updateStmt = $pdo->prepare("
                UPDATE schedule
                SET home_team_id = :homeTeamId,
                    away_team_id = :awayTeamId
                WHERE game_id = :gameId
            ");
            
            $updateStmt->execute([
                ':homeTeamId' => $homeTeamId,
                ':awayTeamId' => $awayTeamId,
                ':gameId' => $gameId
            ]);
            
            $result['update_count']++;
            $result['games_updated']++;
            $gameDiagnostics['updated'] = true;
            
            if ($debug) error_log("Successfully updated game #" . $gameId);
        } else {
            if ($debug) {
                if (!$homeTeamId && !$awayTeamId) {
                    error_log("Could not resolve both teams for game #" . $gameId);
                } else if (!$homeTeamId) {
                    error_log("Could not resolve home team for game #" . $gameId);
                } else {
                    error_log("Could not resolve away team for game #" . $gameId);
                }
            }
        }
        
        $result['diagnostics'][] = $gameDiagnostics;
    }
    
    // Set appropriate message based on results
    if ($result['update_count'] > 0) {
        $result['message'] = "Successfully updated " . $result['update_count'] . " playoff game(s).";
    } else {
        $notAllPoolGamesMessage = (!$allPoolGamesComplete && !$requireAllPoolGamesComplete) ? " (Note: Not all pool games are complete)" : "";
        $result['message'] = "No playoff games could be updated. Either all pool games aren't complete, or placeholders couldn't be resolved." . $notAllPoolGamesMessage;
    }
    
    if ($debug) {
        error_log("Final result: " . $result['message']);
        error_log("========= FINISHED AUTO-UPDATE PLAYOFF GAMES =========");
    }
    
    return $result;
}

/**
 * Pool name mapping system to handle common variations
 * 
 * @param string $poolLetter The pool letter to map
 * @param string|null $division Optional division context
 * @return array Array of possible pool name variations
 */
function getPoolNameVariations(string $poolLetter, ?string $division = null): array {
    // Basic variations for a pool letter
    $variations = [
        $poolLetter,                   // Just the letter (A)
        'Pool ' . $poolLetter,         // Pool + letter (Pool A)
        'Pool' . $poolLetter,          // Pool + letter without space (PoolA)
        $poolLetter . ' Pool',         // Letter + Pool (A Pool)
    ];
    
    // If division is provided, add division-specific variations
    if ($division) {
        $variations = array_merge($variations, [
            $division . ' ' . $poolLetter,                // Division + letter (u11 A)
            $division . ' Pool ' . $poolLetter,           // Division + Pool + letter (u11 Pool A)
            $division . '_' . $poolLetter,                // Division + underscore + letter (u11_A)
            $division . $poolLetter,                      // Division + letter no space (u11A)
            $poolLetter . ' ' . $division,                // Letter + division (A u11)
            'Pool ' . $poolLetter . ' ' . $division,      // Pool + letter + division (Pool A u11)
            $division . ' ' . $poolLetter . ' Pool',      // Division + letter + Pool (u11 A Pool)
        ]);
    }
    
    return $variations;
}

/**
 * Try to find a pool ID using multiple naming variations
 * 
 * @param PDO $pdo Database connection
 * @param string $poolLetter The pool letter to look up
 * @param string|null $division Optional division context
 * @param bool $debug Whether to include debug information
 * @return array|null Array with pool_id and pool_name if found, or null if not found
 */
function findPoolByVariations(PDO $pdo, string $poolLetter, ?string $division = null, bool $debug = false): ?array {
    // Get all possible variations of pool names
    $variations = getPoolNameVariations($poolLetter, $division);
    
    if ($debug) {
        error_log("Trying pool name variations for letter '" . $poolLetter . "'" . 
                 ($division ? " in division '" . $division . "'" : ""));
        error_log("Variations to try: " . json_encode($variations));
    }
    
    // First try exact matches
    $placeholders = implode(', ', array_fill(0, count($variations), '?'));
    $query = "SELECT pool_id, pool_name FROM pools WHERE pool_name IN ($placeholders) 
              ORDER BY CASE WHEN pool_name = ? THEN 0 ELSE 1 END";
    
    try {
        $stmt = $pdo->prepare($query);
        $i = 1;
        foreach ($variations as $var) {
            $stmt->bindValue($i++, $var);
        }
        // Prefer exact match to the original pool letter
        $stmt->bindValue($i, $poolLetter);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            if ($debug) {
                error_log("Found pool match using variation: '" . $result['pool_name'] . "'");
            }
            return $result;
        }
        
        // If exact matches fail, try LIKE patterns
        if ($debug) {
            error_log("No exact match found, trying pattern matches");
        }
        
        // Try pattern matching with each variation
        foreach ($variations as $variation) {
            $patternQuery = "SELECT pool_id, pool_name FROM pools 
                             WHERE pool_name LIKE ? OR pool_name LIKE ?
                             ORDER BY LENGTH(pool_name)";
            $patternStmt = $pdo->prepare($patternQuery);
            $patternStmt->execute(["%" . $variation . "%", "%" . strtolower($variation) . "%"]);
            $patternResult = $patternStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($patternResult) {
                if ($debug) {
                    error_log("Found pool using pattern match: '" . $patternResult['pool_name'] . 
                             "' matched pattern '%" . $variation . "%'");
                }
                return $patternResult;
            }
        }
        
        if ($debug) {
            error_log("No pool found with any variation of '" . $poolLetter . "'");
        }
        
        return null;
    } catch (PDOException $e) {
        if ($debug) {
            error_log("Database error in findPoolByVariations: " . $e->getMessage());
        }
        return null;
    }
} 