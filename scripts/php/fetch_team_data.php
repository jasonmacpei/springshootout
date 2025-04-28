<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the configuration file which sets the error log path
require_once '/home/lostan6/springshootout.ca/includes/config.php';

require __DIR__ . '/db_connect.php';

if (isset($_POST['registration_id'])) {
    $registrationId = $_POST['registration_id'];

    try {
        $pdo->beginTransaction();

        // Fetch registration details
        $registrationStmt = $pdo->prepare("SELECT * FROM registrations WHERE registration_id = :registration_id");
        $registrationStmt->execute(['registration_id' => $registrationId]);
        $registration = $registrationStmt->fetch(PDO::FETCH_ASSOC);

        if ($registration) {
          // After fetching the data
              error_log('Fetched data: ' . print_r($registration, true));

            // Fetch related contact details
            $contactStmt = $pdo->prepare("SELECT contact_id, contact_name FROM contacts");
            $contactStmt->execute();
            $contacts = $contactStmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch team name
            $teamnameStmt = $pdo->prepare("SELECT team_name FROM teams WHERE team_id = :team_id");
            $teamnameStmt->execute(['team_id' => $registration['team_id']]);
            $teamname = $teamnameStmt->fetch(PDO::FETCH_ASSOC);


            // Fetch divisions, classes, and provinces
            $divisions = $pdo->query("SELECT DISTINCT division FROM registrations")->fetchAll(PDO::FETCH_ASSOC);
            $classes = $pdo->query("SELECT DISTINCT class FROM registrations")->fetchAll(PDO::FETCH_ASSOC);

            $pdo->commit();

            echo "<form id='updateRegistrationForm'>";
            
            $teamNameValue = isset($teamname['team_name']) ? htmlspecialchars($teamname['team_name']) : '';
            echo "<label for='team_name'>Team Name:</label>";
            echo "<input type='text' name='team_name' class='form-control mb-2' value='" . $teamNameValue . "'>";

            // Division dropdown
            echo "<label for='division'>Division:</label>";
            echo "<select name='division' class='form-control mb-2'>";
            foreach ($divisions as $division) {
                $selected = $division['division'] == $registration['division'] ? 'selected' : '';
                echo "<option value='" . htmlspecialchars($division['division']) . "' $selected>" . htmlspecialchars($division['division']) . "</option>";
            }
            echo "</select>";

            // Class dropdown
            echo "<label for='class'>Class:</label>";
            echo "<select name='class' class='form-control mb-2'>";
            foreach ($classes as $class) {
                $selected = $class['class'] == $registration['class'] ? 'selected' : '';
                echo "<option value='" . htmlspecialchars($class['class']) . "' $selected>" . htmlspecialchars($class['class']) . "</option>";
            }
            echo "</select>";

            // Province dropdown
            echo "<label for='province'>Province:</label>";
            echo "<select name='province' class='form-control mb-2'>";
            $provinces = ['PEI', 'NS', 'NB', 'NFLD', 'Quebec', 'Other'];
            foreach ($provinces as $province) {
                $selected = $province == $registration['province'] ? 'selected' : '';
                echo "<option value='" . htmlspecialchars($province) . "' $selected>" . htmlspecialchars($province) . "</option>";
            }
            echo "</select>";

            // Primary Contact dropdown
            echo "<label for='primary_contact'>Primary Contact:</label>";
            echo "<select name='primary_contact' class='form-control mb-2'>";
            foreach ($contacts as $contact) {
                $selected = $contact['contact_id'] == $registration['contact_id'] ? 'selected' : '';
                echo "<option value='" . htmlspecialchars($contact['contact_id']) . "' $selected>" . htmlspecialchars($contact['contact_name']) . "</option>";
            }
            echo "</select>";

            // Note
            echo "<label for='note'>Note:</label>";
            echo "<input type='text' name='note' class='form-control mb-2' value='" . htmlspecialchars($registration['note']) . "'>";

            // Year dropdown
            echo "<label for='year'>Year:</label>";
            echo "<select name='year' class='form-control mb-2'>";
            $years = ['2024', '2025', '2026'];
            foreach ($years as $year) {
              $selected = $year == $registration['year'] ? 'selected' : '';
              echo "<option value='" . htmlspecialchars($year) . "' $selected>" . htmlspecialchars($year) . "</option>";
          }
          echo "</select>";

          // Paid dropdown
          echo "<label for='paid'>Paid:</label>";
          echo "Actual: " . $registration['paid'];
          echo "<select name='paid' class='form-control mb-2'>";
          $paidStatus = ['1' => 'Yes', '0' => 'No'];
          foreach ($paidStatus as $key => $value) {
              $selected = $registration['paid'] == $key ? 'selected' : '';
              echo "<option value='" . htmlspecialchars($key) . "' $selected>" . htmlspecialchars($value) . "</option>";
          }
          echo "</select>";


          // Status dropdown
          echo "<label for='status'>Status:</label>";
          echo "<select name='status' class='form-control mb-2'>";
          $statusOptions = ['1' => 'Active', '2' => 'Withdrawn'];
          foreach ($statusOptions as $key => $value) {
              $selected = $key == $registration['status'] ? 'selected' : '';
              echo "<option value='" . htmlspecialchars($key) . "' $selected>" . htmlspecialchars($value) . "</option>";
          }
          echo "</select>";

          // Hidden input for registration_id to identify the record on submission
          echo "<input type='hidden' name='registration_id' value='" . htmlspecialchars($registrationId) . "'>";

          // Submit button
          echo "<button type='submit' class='btn btn-primary'>Update Registration</button>";
          echo "</form>";
      } else {
          echo "No registration found for the selected ID.";
      }
  } catch (PDOException $e) {
      $pdo->rollBack();  // Rollback the transaction on error
      echo "Error fetching registration details: " . $e->getMessage();
  }
} else {
  echo "No registration ID selected.";
}
?>

