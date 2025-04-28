<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the configuration file which sets the error log path
require_once '/home/lostan6/springshootout.ca/includes/config.php';

// Ensure you have the correct path to the db_connect.php file.
require __DIR__ . '/../scripts/php/db_connect.php';

// Process the POST request for deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['registration_id']) && !empty($_POST['registration_id'])) {
        $registration_id = $_POST['registration_id'];

        try {
            // Prepare and execute the DELETE statement
            $stmt = $pdo->prepare("DELETE FROM registrations WHERE registration_id = :registration_id");
            $stmt->bindParam(':registration_id', $registration_id, PDO::PARAM_INT);
            $stmt->execute();

            echo "Registration deleted successfully.";
        } catch (PDOException $e) {
            // Log and return error message
            echo "Error deleting registration: " . $e->getMessage();
        }
    } else {
        echo "No registration ID provided.";
    }
} else {
    echo "Invalid request method.";
}
?>