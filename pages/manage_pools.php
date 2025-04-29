<?php
// manage_pools.php
// Allows administrators to create, edit, and delete pools

session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: admin.php');
    exit;
}

// Include the database connection
require_once '/home/lostan6/springshootout.ca/includes/config.php';
require __DIR__ . '/../scripts/php/db_connect.php';

$message = '';
$editPool = null;

// Handle delete operation
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $poolId = $_GET['id'];
    
    try {
        // Check if the pool is associated with any teams
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM team_pools WHERE pool_id = :pool_id");
        $checkStmt->execute([':pool_id' => $poolId]);
        $count = $checkStmt->fetchColumn();
        
        if ($count > 0) {
            $message = '<div class="alert alert-danger">Cannot delete this pool because it is assigned to ' . $count . ' team(s). Please remove all team assignments first.</div>';
        } else {
            $deleteStmt = $pdo->prepare("DELETE FROM pools WHERE pool_id = :pool_id");
            $deleteStmt->execute([':pool_id' => $poolId]);
            $message = '<div class="alert alert-success">Pool deleted successfully.</div>';
        }
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error deleting pool: ' . $e->getMessage() . '</div>';
    }
}

// Handle edit action (load pool data for editing)
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $poolId = $_GET['id'];
    
    try {
        $editStmt = $pdo->prepare("SELECT pool_id, pool_name FROM pools WHERE pool_id = :pool_id");
        $editStmt->execute([':pool_id' => $poolId]);
        $editPool = $editStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$editPool) {
            $message = '<div class="alert alert-danger">Pool not found.</div>';
        }
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error loading pool data: ' . $e->getMessage() . '</div>';
    }
}

// Handle form submission for add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $poolName = trim($_POST['pool_name']);
    $poolId = isset($_POST['pool_id']) ? $_POST['pool_id'] : null;
    
    if (empty($poolName)) {
        $message = '<div class="alert alert-danger">Pool name cannot be empty.</div>';
    } else {
        try {
            // Check if pool name already exists (for different pool)
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM pools WHERE pool_name = :pool_name" . ($poolId ? " AND pool_id != :pool_id" : ""));
            $params = [':pool_name' => $poolName];
            if ($poolId) {
                $params[':pool_id'] = $poolId;
            }
            $checkStmt->execute($params);
            $count = $checkStmt->fetchColumn();
            
            if ($count > 0) {
                $message = '<div class="alert alert-danger">A pool with this name already exists.</div>';
            } else {
                if ($poolId) {
                    // Update existing pool
                    $stmt = $pdo->prepare("UPDATE pools SET pool_name = :pool_name WHERE pool_id = :pool_id");
                    $stmt->execute([
                        ':pool_name' => $poolName,
                        ':pool_id' => $poolId
                    ]);
                    $message = '<div class="alert alert-success">Pool updated successfully.</div>';
                    $editPool = null; // Clear edit mode
                } else {
                    // Add new pool
                    $stmt = $pdo->prepare("INSERT INTO pools (pool_name) VALUES (:pool_name)");
                    $stmt->execute([':pool_name' => $poolName]);
                    $message = '<div class="alert alert-success">Pool added successfully.</div>';
                }
            }
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Error saving pool: ' . $e->getMessage() . '</div>';
        }
    }
}

// Fetch all pools
try {
    $poolsStmt = $pdo->query("SELECT pool_id, pool_name FROM pools ORDER BY pool_name");
    $pools = $poolsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = '<div class="alert alert-danger">Error fetching pools: ' . $e->getMessage() . '</div>';
    $pools = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Pools</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha2/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-aFq/bzH65dt+w6FI2ooMVUpc+21e0SRygnTpmBvdBgSdnuTN7QbdgL+OapgHtvPp" crossorigin="anonymous">
    <!-- Custom Stylesheet -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background-color: black;
            color: white;
        }
        .pool-row:nth-child(odd) {
            background-color: rgba(255, 255, 255, 0.05);
        }
        .pool-row:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        .form-control, .form-select {
            background-color: #212529;
            color: white;
            border-color: #495057;
        }
        .form-control:focus, .form-select:focus {
            background-color: #2c3034;
            color: white;
        }
        .table-dark {
            --bs-table-bg: transparent;
        }
        .action-buttons .btn {
            margin-right: 5px;
        }
    </style>
</head>
<body>

<div class="container">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark justify-content-center">
        <div id="nav-placeholder"></div>
    </nav>
</div>

<!-- Content of the page -->
<div class="poster">
    <img src="../assets/images/name.png" alt="Spring Shootout">
</div>
<div class="container mt-4">
    <h1 class="text-center mb-4">Manage Pools</h1>
    
    <?php echo $message; ?>
    
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card bg-dark text-white mb-4">
                <div class="card-header">
                    <h4><?php echo $editPool ? 'Edit Pool' : 'Add New Pool'; ?></h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <?php if ($editPool): ?>
                            <input type="hidden" name="pool_id" value="<?php echo $editPool['pool_id']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="pool_name" class="form-label">Pool Name</label>
                            <input type="text" class="form-control" id="pool_name" name="pool_name" 
                                   value="<?php echo $editPool ? htmlspecialchars($editPool['pool_name']) : ''; ?>" required>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">
                                <?php echo $editPool ? 'Update Pool' : 'Add Pool'; ?>
                            </button>
                            <?php if ($editPool): ?>
                                <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="btn btn-secondary">Cancel Edit</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card bg-dark text-white">
                <div class="card-header">
                    <h4>Existing Pools</h4>
                </div>
                <div class="card-body">
                    <?php if (empty($pools)): ?>
                        <div class="alert alert-info">No pools have been created yet.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-dark">
                                <thead>
                                    <tr>
                                        <th>Pool Name</th>
                                        <th width="200">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pools as $pool): ?>
                                        <tr class="pool-row">
                                            <td><?php echo htmlspecialchars($pool['pool_name']); ?></td>
                                            <td class="action-buttons">
                                                <a href="?action=edit&id=<?php echo $pool['pool_id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                                <a href="?action=delete&id=<?php echo $pool['pool_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this pool? This cannot be undone.');">Delete</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="d-grid gap-2 col-6 mx-auto mt-4">
                <a href="menu.php" class="btn btn-secondary">Back to Menu</a>
            </div>
        </div>
    </div>
</div>

<!-- jQuery and Bootstrap JS -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<!-- Navbar dynamic loading -->
<script>
    $(document).ready(function() {
        $("#nav-placeholder").load("../includes/navbar.html");
    });
</script>

</body>
</html> 