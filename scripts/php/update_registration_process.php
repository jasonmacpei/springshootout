<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the configuration file which sets the error log path
require_once '/home/lostan6/springshootout.ca/includes/config.php';
// ...
// Log the script execution with a timestamp
error_log("\n");
error_log("update_registration_process.php was run at " . date("Y-m-d H:i:s"));
error_log("\n");

require __DIR__ . '/db_connect.php';

// Set PDO to throw exceptions
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve all form fields
    $registrationId = $_POST['registration_id'];
    $teamName = $_POST['team_name'];
    $division = $_POST['division'];
    $class = $_POST['class'];
    $province = $_POST['province'];
    $primaryContact = $_POST['primary_contact'];
    $note = $_POST['note'];
    $year = $_POST['year'];
    $paid = $_POST['paid'];
    // Log the raw status value
    // write the actual status to the error log:
      error_log("Raw status POST value: " . $_POST['status']);
    $status = $_POST['status'];
  //   if (strtolower(trim($_POST['status'])) === 'Active') {
  //     $status = 1;
  // } elseif (strtolower(trim($_POST['status'])) === 'Withdrawn') {
  //     $status = 2;
  // } else {
  //     // Handle unexpected value or set a default status
  //     $status = 3;
  // }
  

    try {
        $pdo->beginTransaction();

        // Fetch the current registration to get the team_id
        $registrationStmt = $pdo->prepare("SELECT team_id FROM registrations WHERE registration_id = :registrationId");
        $registrationStmt->execute(['registrationId' => $registrationId]);
        $registration = $registrationStmt->fetch(PDO::FETCH_ASSOC);

        if (!$registration) {
            throw new Exception("Registration not found.");
        }

        $teamId = $registration['team_id'];

        // Update the registration details
        $updateRegistrationStmt = $pdo->prepare("
            UPDATE registrations 
            SET division = :division,
                class = :class,
                province = :province,
                contact_id = :primaryContact,
                note = :note,
                year = :year,
                paid = :paid,
                status = :status
            WHERE registration_id = :registrationId
        ");
        $updateRegistrationStmt->execute([
            'division' => $division,
            'class' => $class,
            'province' => $province,
            'primaryContact' => $primaryContact,
            'note' => $note,
            'year' => $year,
            'paid' => $paid, // 't' or 'f' as a string for PostgreSQL.
            'status' => $status,
            'registrationId' => $registrationId,
        ]);

        // Check if the registration update was successful
        if ($updateRegistrationStmt->rowCount() === 0) {
            throw new Exception('No rows updated for registration.');
        }

        // Update the team name
        $updateTeamStmt = $pdo->prepare("
            UPDATE teams 
            SET team_name = :teamName 
            WHERE team_id = :teamId
        ");
        $updateTeamStmt->execute([
            'teamName' => $teamName,
            'teamId' => $teamId,
        ]);

        // Check if the team update was successful
        if ($updateTeamStmt->rowCount() === 0) {
            throw new Exception('No rows updated for team.');
        }

        $pdo->commit();

        echo json_encode(['success' => true, 'message' => 'Registration and team name updated successfully.']);


        // Log variable values (example)
              error_log("Registration ID: " . $registrationId);
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("PDOException: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => "PDOException: " . $e->getMessage()]);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("PDOException: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => "General Error: " . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Request method not POST']);
}
?>
