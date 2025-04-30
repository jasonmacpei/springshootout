<?php
require_once '/home/lostan6/springshootout.ca/includes/config.php';
require __DIR__ . '/../scripts/php/db_connect.php';
require_once __DIR__ . '/../scripts/php/resolve_placeholder.php';

error_log("Starting script");
// Set up content type and error reporting based on the type of request
$isAjaxRequest = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
if ($isAjaxRequest) {
    ini_set('display_errors', 0); // Turn off display of errors for AJAX
    ini_set('log_errors', 1); // Log errors for review
} else {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}
error_log("Before database call");
$gymFilter = isset($_GET['gym']) && $_GET['gym'] !== '' ? $_GET['gym'] : null;
$divisionFilter = isset($_GET['division']) && $_GET['division'] !== '' ? $_GET['division'] : null;
$categoryFilter = isset($_GET['category']) && $_GET['category'] !== '' ? $_GET['category'] : null;

$query = "SELECT s.game_id, s.game_date, s.game_time, h.team_name AS home_team_name, s.home_team_id, s.home_uniform,
a.team_name AS away_team_name, s.away_team_id, s.away_uniform, s.gym, s.game_type, 
s.placeholder_home, s.placeholder_away, s.game_category
FROM schedule s
LEFT JOIN teams h ON s.home_team_id = h.team_id
LEFT JOIN teams a ON s.away_team_id = a.team_id";

$whereAdded = false;

if ($gymFilter && $gymFilter !== 'All Gyms') {
    $query .= " WHERE s.gym = :gymFilter";
    $whereAdded = true;
}

if ($divisionFilter && $divisionFilter !== 'All Divisions') {
    if ($whereAdded) {
        $query .= " AND";
    } else {
        $query .= " WHERE";
        $whereAdded = true;
    }
    $query .= " s.game_type LIKE :divisionFilter";
}

if ($categoryFilter && $categoryFilter !== 'All Categories') {
    if ($whereAdded) {
        $query .= " AND";
    } else {
        $query .= " WHERE";
        $whereAdded = true;
    }
    $query .= " s.game_category = :categoryFilter";
}

$query .= " ORDER BY s.game_date, s.game_time;";
$stmt = $pdo->prepare($query);

if ($gymFilter && $gymFilter !== 'All Gyms') {
    $stmt->bindParam(':gymFilter', $gymFilter);
}

if ($divisionFilter && $divisionFilter !== 'All Divisions') {
    $divisionFilterParam = $divisionFilter . '%';
    $stmt->bindParam(':divisionFilter', $divisionFilterParam);
}

if ($categoryFilter && $categoryFilter !== 'All Categories') {
    $stmt->bindParam(':categoryFilter', $categoryFilter);
}

try {
    $stmt->execute();
    $scheduleRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log('Fetched rows: ' . print_r($scheduleRows, true)); // This will log the fetched data
      // Before sending the response
      $htmlOutput = buildScheduleTableBody($scheduleRows, $pdo);
      error_log('HTML Output: ' . $htmlOutput); // This will log the generated HTML
      
} catch (PDOException $e) {
    error_log("Error executing query: " . $e->getMessage());
    if ($isAjaxRequest) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['error' => 'Error executing query', 'details' => $e->getMessage()]);
        exit;
    } else {
        header('Location: http://www.springshootout.ca/pages/error.html');
        exit;
    }
}

if ($isAjaxRequest) {
  header('Content-Type: application/json'); // This ensures the client will treat the response as JSON
  // No need for ob_end_clean() since we haven't started output buffering
  echo json_encode(['html' => buildScheduleTableBody($scheduleRows, $pdo)]);
  exit;
}

error_log("After database call");
if ($isAjaxRequest) {
    error_log("Inside isAjaxRequest");
    header('Content-Type: application/json');
    echo json_encode(['html' => buildScheduleTableBody($scheduleRows, $pdo)]);
    exit;
}

// Fetch teams by division and pool for the current year
try {
    // Query to get teams for u11 division
    $u11Stmt = $pdo->prepare("
        SELECT 
            t.team_name,
            p.pool_name
        FROM 
            teams t
        JOIN 
            team_pools tp ON t.team_id = tp.team_id
        JOIN 
            pools p ON tp.pool_id = p.pool_id
        JOIN 
            registrations r ON t.team_id = r.team_id
        WHERE 
            r.division = 'u11' AND r.year = 2025 AND r.status = 1
        ORDER BY 
            p.pool_name, t.team_name
    ");
    $u11Stmt->execute();
    $u11Teams = $u11Stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group u11 teams by pool
    $u11Pools = [];
    foreach ($u11Teams as $team) {
        if (!isset($u11Pools[$team['pool_name']])) {
            $u11Pools[$team['pool_name']] = [];
        }
        $u11Pools[$team['pool_name']][] = $team['team_name'];
    }
    
    // Query to get teams for u12 division
    $u12Stmt = $pdo->prepare("
        SELECT 
            t.team_name,
            p.pool_name
        FROM 
            teams t
        JOIN 
            team_pools tp ON t.team_id = tp.team_id
        JOIN 
            pools p ON tp.pool_id = p.pool_id
        JOIN 
            registrations r ON t.team_id = r.team_id
        WHERE 
            r.division = 'u12' AND r.year = 2025 AND r.status = 1
        ORDER BY 
            p.pool_name, t.team_name
    ");
    $u12Stmt->execute();
    $u12Teams = $u12Stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group u12 teams by pool
    $u12Pools = [];
    foreach ($u12Teams as $team) {
        if (!isset($u12Pools[$team['pool_name']])) {
            $u12Pools[$team['pool_name']] = [];
        }
        $u12Pools[$team['pool_name']][] = $team['team_name'];
    }
    
    // Query to get teams for u13 division
    $u13Stmt = $pdo->prepare("
        SELECT 
            t.team_name,
            p.pool_name
        FROM 
            teams t
        JOIN 
            team_pools tp ON t.team_id = tp.team_id
        JOIN 
            pools p ON tp.pool_id = p.pool_id
        JOIN 
            registrations r ON t.team_id = r.team_id
        WHERE 
            r.division = 'u13' AND r.year = 2025 AND r.status = 1
        ORDER BY 
            p.pool_name, t.team_name
    ");
    $u13Stmt->execute();
    $u13Teams = $u13Stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group u13 teams by pool
    $u13Pools = [];
    foreach ($u13Teams as $team) {
        if (!isset($u13Pools[$team['pool_name']])) {
            $u13Pools[$team['pool_name']] = [];
        }
        $u13Pools[$team['pool_name']][] = $team['team_name'];
    }
    
} catch (PDOException $e) {
    error_log("Error fetching teams by division and pool: " . $e->getMessage());
    $u11Pools = [];
    $u12Pools = [];
    $u13Pools = [];
}

function buildScheduleTableBody($rows, $pdo) {
  error_log("Inside Function buildScheduleTableBody");
    $htmlOutput = '';
    foreach ($rows as $row) {
        $htmlOutput .= "<tr>";
        // Add Game # column
        $htmlOutput .= "<td><strong>#" . htmlspecialchars($row['game_id']) . "</strong></td>";
        $htmlOutput .= "<td>" . htmlspecialchars($row['game_date']) . "</td>";
        // Format the time
        $time = new DateTime($row['game_time']);
        $formattedTime = $time->format('g:i a');  // Formats to h:mm am/pm
        
        $htmlOutput .= "<td>" . htmlspecialchars($formattedTime) . "</td>";
        
        // Handle home team display with placeholders
        if (!empty($row['placeholder_home'])) {
            $homeTeamDisplay = getTeamDisplayWithPlaceholder(
                $pdo, 
                $row['placeholder_home'], 
                $row['home_team_name'], 
                $row['home_team_id'],
                2025
            );
            $htmlOutput .= "<td>" . $homeTeamDisplay . "</td>";
        } else {
            $htmlOutput .= "<td>" . ($row['home_team_name'] ? htmlspecialchars($row['home_team_name']) : 'TBD') . "</td>";
        }
        
        // Handle away team display with placeholders
        if (!empty($row['placeholder_away'])) {
            $awayTeamDisplay = getTeamDisplayWithPlaceholder(
                $pdo, 
                $row['placeholder_away'], 
                $row['away_team_name'], 
                $row['away_team_id'],
                2025
            );
            $htmlOutput .= "<td>" . $awayTeamDisplay . "</td>";
        } else {
            $htmlOutput .= "<td>" . ($row['away_team_name'] ? htmlspecialchars($row['away_team_name']) : 'TBD') . "</td>";
        }
        
        $htmlOutput .= "<td>" . htmlspecialchars($row['gym']) . "</td>";
        
        // Add game category as a CSS class for styling
        $gameTypeClass = 'game-' . strtolower($row['game_category']);
        $htmlOutput .= "<td class='" . $gameTypeClass . "'>" . htmlspecialchars($row['game_type']);
        
        // Show game category if not "pool"
        if ($row['game_category'] != 'pool') {
            $htmlOutput .= " <span class='game-category'>(" . ucfirst(htmlspecialchars($row['game_category'])) . ")</span>";
        }
        
        $htmlOutput .= "</td>";
        $htmlOutput .= "</tr>";
    }
    return $htmlOutput;
}
error_log("End PHP script");
// The rest of your HTML document starts here for non-AJAX requests
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom styles if needed -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
              .white-text {
                  color: white;
              }
              .table-white tbody td, .table-white tbody th,
              .table-white thead th, .table-white tfoot td {
                  color: white;
              }
              .table-responsive {
                  width: 100%;
                  overflow-x: auto; /* Ensure that scrolling is possible */
                  -webkit-overflow-scrolling: touch; /* For smooth scrolling on iOS */
              }

              .table td, .table th {
                  padding: .5rem;
                  font-size: .85rem;
                  white-space: nowrap;
                  overflow: hidden;
                  text-overflow: ellipsis;
              }
              
              /* Styling for placeholders */
              .placeholder-code {
                  font-size: 0.8em;
                  color: #aaa;
              }
              
              .placeholder-unresolved {
                  color: #ff9900;
                  font-weight: bold;
              }
              
              /* Game category styling */
              .game-crossover {
                  background-color: rgba(255, 170, 0, 0.1);
              }
              
              .game-quarterfinal {
                  background-color: rgba(0, 170, 255, 0.1);
              }
              
              .game-semifinal {
                  background-color: rgba(170, 0, 255, 0.1);
              }
              
              .game-final {
                  background-color: rgba(255, 0, 0, 0.1);
              }
              
              .game-consolation-semifinal {
                  background-color: rgba(108, 117, 125, 0.1);
              }
              
              .game-consolation-final {
                  background-color: rgba(40, 167, 69, 0.1);
              }
              
              .game-category {
                  font-size: 0.8em;
                  font-style: italic;
                  color: #aaa;
              }
              
              /* Game ID column styling */
              .table td:first-child {
                  font-weight: bold;
                  background-color: rgba(255, 255, 255, 0.05);
                  text-align: center;
              }
              
              .table th:first-child {
                  text-align: center;
                  background-color: #23272b;
              }

      @media (max-width: 850px) {
          .table td, .table th {
            padding: .25rem; /* Adjust padding */
              font-size: 0.8rem; /* Adjust font size */
              max-width: 90px; /* Prevents cells from being too wide */
          }
      }

      @media (max-width: 768px) {
          .table td, .table th {
            padding: .2rem; /* Adjust padding */
              font-size: 0.7rem; /* Adjust font size */
              max-width: 90px; /* Prevents cells from being too wide */
          }
      }

      /* Phones */
      @media (max-width: 480px) {
          .table td, .table th {
              padding: .2rem; /* Smaller padding */
              font-size: 0.55rem; /* Smaller font size */
              max-width: 80px; /* Even more narrow cells */
          }
      }
.filter-container {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    color: white;  /* This will set the label text to white */
}

.filter-item {
    display: flex;
    align-items: center;
    margin-right: 15px;
    margin-bottom: 10px;
}

.select-label {
    margin-right: 10px;  /* Spacing between label and dropdown */
    white-space: nowrap;
}

.select-wrap {
    position: relative;
    flex-grow: 1;  /* Lets the dropdown fill the space */
    margin-right: 10px;
}

.select-wrap::after {
    content: '▼';
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    color: white;
}

.form-control {
    padding: 0 10px;  /* Adds padding inside the select box */
    margin-right: 20px; /* Adds a 20px gap to the right of the form control */
    appearance: none;  /* Removes native dropdown styling */
    -webkit-appearance: none;
    -moz-appearance: none;
    background: transparent;  /* Makes the background transparent */
    border: 1px solid white;  /* Adds white border */
    color: white;  /* Sets the text color inside the dropdown to white */
}

/* Removes the default arrow icon of select element */
.form-control::-ms-expand {
    display: none;
}

.btn {
    white-space: nowrap;  /* Prevents the button text from wrapping */
}
.btn-download {
    margin-top: -5px; /* Moves the button up by 5 pixels */
    padding-top: 5px;
}

    </style>
</head>
<body>

<div class="container">
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark justify-content-center">
      <div id="nav-placeholder"></div>
    
      <!-- The static navbar HTML is removed because it will be loaded dynamically into the nav-placeholder div -->
    </div>
    </div>
  <!-- Content of the page -->
  <div class="poster">
    <img src="../assets/images/name.png">
  </div>
  <div style="padding-top: 20px;">
    <hr>
  </div>

<!-- Division tables, each in their own .table-responsive if necessary -->
<div class="container my-3">
    <div class="row">
        <div class="col-md-6">
          <h3 class="text-center">u11 Division</h3>
          <table class="table table-bordered white-text">
            <thead class="thead-dark">
              <tr>
                <?php 
                $u11PoolNames = array_keys($u11Pools);
                foreach ($u11PoolNames as $poolName): ?>
                  <th scope="col">Pool <?php echo htmlspecialchars($poolName); ?></th>
                <?php endforeach; ?>
                
                <?php // Add empty columns if we don't have enough pools
                for ($i = count($u11PoolNames); $i < 2; $i++): ?>
                  <th scope="col">Pool <?php echo chr(65 + $i); // A, B, etc. ?></th>
                <?php endfor; ?>
              </tr>
            </thead>
            <tbody class="white-text">
              <?php 
              // Find the maximum number of teams in any pool
              $maxU11Teams = 0;
              foreach ($u11Pools as $teams) {
                  $maxU11Teams = max($maxU11Teams, count($teams));
              }
              
              // Create rows for each team
              for ($i = 0; $i < $maxU11Teams; $i++): ?>
                <tr>
                  <?php foreach ($u11PoolNames as $poolName): ?>
                    <td>
                      <?php 
                      if (isset($u11Pools[$poolName][$i])) {
                          echo htmlspecialchars($u11Pools[$poolName][$i]);
                      }
                      ?>
                    </td>
                  <?php endforeach; ?>
                  
                  <?php // Add empty cells if we don't have enough pools
                  for ($j = count($u11PoolNames); $j < 2; $j++): ?>
                    <td></td>
                  <?php endfor; ?>
                </tr>
              <?php endfor; ?>
              
              <?php // If no teams found, display a message
              if (empty($u11Pools)): ?>
                <tr>
                  <td colspan="2" class="text-center">No teams assigned to pools yet.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="col-md-6">
          <h3 class="text-center">u12 Division</h3>
          <table class="table table-bordered white-text">
            <thead class="thead-dark">
              <tr>
                <?php 
                $u12PoolNames = array_keys($u12Pools);
                foreach ($u12PoolNames as $poolName): ?>
                  <th scope="col">Pool <?php echo htmlspecialchars($poolName); ?></th>
                <?php endforeach; ?>
                
                <?php // Add empty columns if we don't have enough pools
                for ($i = count($u12PoolNames); $i < 2; $i++): ?>
                  <th scope="col">Pool <?php echo chr(67 + $i); // C, D, etc. ?></th>
                <?php endfor; ?>
              </tr>
            </thead>
            <tbody class="white-text">
              <?php 
              // Find the maximum number of teams in any pool
              $maxU12Teams = 0;
              foreach ($u12Pools as $teams) {
                  $maxU12Teams = max($maxU12Teams, count($teams));
              }
              
              // Create rows for each team
              for ($i = 0; $i < $maxU12Teams; $i++): ?>
                <tr>
                  <?php foreach ($u12PoolNames as $poolName): ?>
                    <td>
                      <?php 
                      if (isset($u12Pools[$poolName][$i])) {
                          echo htmlspecialchars($u12Pools[$poolName][$i]);
                      }
                      ?>
                    </td>
                  <?php endforeach; ?>
                  
                  <?php // Add empty cells if we don't have enough pools
                  for ($j = count($u12PoolNames); $j < 2; $j++): ?>
                    <td></td>
                  <?php endfor; ?>
                </tr>
              <?php endfor; ?>
              
              <?php // If no teams found, display a message
              if (empty($u12Pools)): ?>
                <tr>
                  <td colspan="2" class="text-center">No teams assigned to pools yet.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      
      <!-- u13 Division in its own row -->
      <div class="row mt-4">
        <div class="col-12">
          <h3 class="text-center">u13 Division</h3>
          <table class="table table-bordered white-text">
            <thead class="thead-dark">
              <tr>
                <?php 
                $u13PoolNames = array_keys($u13Pools);
                foreach ($u13PoolNames as $poolName): ?>
                  <th scope="col">Pool <?php echo htmlspecialchars($poolName); ?></th>
                <?php endforeach; ?>
                
                <?php // Add empty columns if we don't have enough pools
                for ($i = count($u13PoolNames); $i < 4; $i++): ?>
                  <th scope="col">Pool <?php echo chr(65 + $i); // A, B, C, D, etc. ?></th>
                <?php endfor; ?>
              </tr>
            </thead>
            <tbody class="white-text">
              <?php 
              // Find the maximum number of teams in any pool
              $maxU13Teams = 0;
              foreach ($u13Pools as $teams) {
                  $maxU13Teams = max($maxU13Teams, count($teams));
              }
              
              // Create rows for each team
              for ($i = 0; $i < $maxU13Teams; $i++): ?>
                <tr>
                  <?php foreach ($u13PoolNames as $poolName): ?>
                    <td>
                      <?php 
                      if (isset($u13Pools[$poolName][$i])) {
                          echo htmlspecialchars($u13Pools[$poolName][$i]);
                      }
                      ?>
                    </td>
                  <?php endforeach; ?>
                  
                  <?php // Add empty cells if we don't have enough pools
                  for ($j = count($u13PoolNames); $j < 4; $j++): ?>
                    <td></td>
                  <?php endfor; ?>
                </tr>
              <?php endfor; ?>
              
              <?php // If no teams found, display a message
              if (empty($u13Pools)): ?>
                <tr>
                  <td colspan="4" class="text-center">No teams assigned to pools yet.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
  </div>

    <div class="container">
        <h1 class="mt-4">Schedule</h1>

        <div class="filter-container">
            <div class="filter-item">
                <label for="gymFilter" class="select-label">Select Gym:</label>
                <div class="select-wrap">
                    <select id="gymFilter" class="form-control" onchange="filterSchedule()">
                        <option value="">All Gyms</option>
                        <option value="Town Hall">Town Hall</option>
                        <option value="Donagh">Donagh</option>
                        <option value="Stonepark">Stonepark</option>
                        <option value="Rural">Rural</option>
                        <option value="Glen Stewart">Glen Stewart</option>
                        <option value="Colonel Grey">Colonel Grey</option>
                        <option value="UPEI">UPEI</option>
                    </select>
                </div>
            </div>

            <div class="filter-item">
                <label for="divisionFilter" class="select-label">Select Division:</label>
                <div class="select-wrap">
                    <select id="divisionFilter" class="form-control" onchange="filterSchedule()">
                        <option value="">All Divisions</option>
                        <option value="u11">U11</option>
                        <option value="u12">U12</option>
                        <option value="u13">U13</option>
                    </select>
                </div>
            </div>
            
            <div class="filter-item">
                <label for="categoryFilter" class="select-label">Game Type:</label>
                <div class="select-wrap">
                    <select id="categoryFilter" class="form-control" onchange="filterSchedule()">
                        <option value="">All Categories</option>
                        <option value="pool">Pool Play</option>
                        <option value="crossover">Crossover</option>
                        <option value="quarterfinal">Quarterfinals</option>
                        <option value="semifinal">Semifinals</option>
                        <option value="consolation-semifinal">Consolation Semifinals</option>
                        <option value="consolation-final">Consolation Final</option>
                        <option value="final">Finals</option>
                    </select>
                </div>
            </div>

              <!-- <a href="../files/combined.pdf" class="btn btn-primary mb-3 btn-download">Download as PDF</a> -->
        </div>

        <div class="table-responsive"> 
          <table class="table table-striped table-white">
              <thead class="thead-dark">
                  <tr>
                      <th>Game #</th>
                      <th>Date</th>
                      <th>Time</th>
                      <th>Home Team</th>
                      <th>Away Team</th>
                      <th>Gym</th>
                      <th>Game Type</th>
                  </tr>
              </thead>


              <tbody id="scheduleTableBody">
                  <!-- This content will be replaced by AJAX -->
              </tbody>

                    </table>
                    </div>
              </div>

  <!-- jQuery for dynamic content loading -->
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <!-- Bootstrap JS for Bootstrap components functionality -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  <!-- Script to dynamically load the navbar -->
  <script>
    $(document).ready(function() {
        $("#nav-placeholder").load("../includes/navbar.html");
        filterSchedule(); // This will load the full schedule when the page loads
    });
  </script>

<script>
function filterSchedule() {
    var gym = document.getElementById('gymFilter').value;
    var division = document.getElementById('divisionFilter').value;
    var category = document.getElementById('categoryFilter').value;
    
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'schedule.php?gym=' + encodeURIComponent(gym) + 
                   '&division=' + encodeURIComponent(division) + 
                   '&category=' + encodeURIComponent(category), true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

    // This event handler will log all responses, which can help with debugging
    xhr.onload = function () {
        console.log("Response received:", xhr.responseText); // Log the raw response

        if (xhr.status >= 200 && xhr.status < 300) {
            try {
                var response = JSON.parse(xhr.responseText);
                // Check if the response actually contains the 'html' key
                if (response.html !== undefined) {
                    document.getElementById('scheduleTableBody').innerHTML = response.html;
                } else {
                    // If there is no 'html' key in the response, log the whole response
                    // This might indicate an error message or something unexpected
                    console.error("Unexpected response structure:", response);
                    // Inform the user
                    alert('Unexpected response from the server. Please check the console for more details.');
                }
            } catch (e) {
                // If JSON parsing fails, log the error and the response for debugging
                console.error("Error parsing JSON:", e);
                console.error("Raw response:", xhr.responseText);
                alert('Error parsing response. Please check the console for more information.');
            }
        } else {
            // Non-200 responses indicate some sort of HTTP error
            console.error("HTTP Error:", xhr.statusText);
            alert('HTTP Error: ' + xhr.statusText);
        }
    };

    // Network errors should be logged and the user informed
    xhr.onerror = function () {
        console.error("Network error occurred");
        alert('Network Error. Please check your connection.');
    };

    // Send the AJAX request
    xhr.send();
}
</script>


</body>
</html>
