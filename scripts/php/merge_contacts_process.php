<?php
/**
 * merge_contacts_process.php
 *
 * Takes form data from merge_contacts.php.
 * For each group of duplicate contacts, merges them into the selected "master" contact.
 * Now also updates the registrations table to point duplicate contact IDs to the master.
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../../pages/admin.php');
    exit;
}

require_once '/home/lostan6/springshootout.ca/includes/config.php';
require __DIR__ . '/db_connect.php';

// Optional: Confirm DB connection for debugging
// echo "<pre>DB Connection Status: " . $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS) . "</pre>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // For debugging: see exactly what was posted
    // echo "<pre>"; print_r($_POST); echo "</pre>";

    if (!isset($_POST['duplicate_groups']) || !is_array($_POST['duplicate_groups'])) {
        die("No duplicate groups provided.");
    }

    try {
        $debugOutput = [];

        foreach ($_POST['duplicate_groups'] as $groupKey => $csvIds) {
            $masterField = "master_for_" . $groupKey;
            if (!isset($_POST[$masterField])) {
                $debugOutput[] = "No master selected for groupKey: $groupKey. Skipping.";
                continue;
            }

            $masterId = (int)$_POST[$masterField];
            $allIds = array_map('intval', explode(',', $csvIds));
            // Re-index the array to ensure it starts at 0
            $allIds = array_values($allIds);
            $duplicateIds = array_values(array_diff($allIds, [$masterId]));

            $debugOutput[] = "Group: $groupKey | Master ID: $masterId | All IDs: " . implode(',', $allIds);

            if (empty($duplicateIds)) {
                $debugOutput[] = "No duplicates to merge in this group (only 1 contact).";
                continue;
            }

            // Build placeholders separately for the update and delete queries
            $countDup = count($duplicateIds);
            $placeholders = implode(',', array_fill(0, $countDup, '?'));

            // 1) Update bridging table references in team_contacts
            $sqlUpdateTC = "UPDATE team_contacts
                          SET contact_id = ?
                          WHERE contact_id IN ($placeholders)";
            $updateParamsTC = array_merge([$masterId], $duplicateIds);
            $stmtUpdateTC = $pdo->prepare($sqlUpdateTC);
            $stmtUpdateTC->execute($updateParamsTC);
            $updatedRowsTC = $stmtUpdateTC->rowCount();

            $debugOutput[] = "Updated $updatedRowsTC row(s) in team_contacts for groupKey: $groupKey.";
            $debugOutput[] = "UPDATE team_contacts Query: [$sqlUpdateTC] with params: (" . implode(',', $updateParamsTC) . ")";

            // 2) Update registrations table to reassign duplicate contacts to master
            $sqlUpdateReg = "UPDATE registrations
                          SET contact_id = ?
                          WHERE contact_id IN ($placeholders)";
            $updateParamsReg = array_merge([$masterId], $duplicateIds);
            $stmtUpdateReg = $pdo->prepare($sqlUpdateReg);
            $stmtUpdateReg->execute($updateParamsReg);
            $updatedRowsReg = $stmtUpdateReg->rowCount();

            $debugOutput[] = "Updated $updatedRowsReg row(s) in registrations for groupKey: $groupKey.";
            $debugOutput[] = "UPDATE registrations Query: [$sqlUpdateReg] with params: (" . implode(',', $updateParamsReg) . ")";

            // 3) Delete duplicates from the contacts table
            $sqlDelete = "DELETE FROM contacts
                          WHERE contact_id IN ($placeholders)";
            $stmtDelete = $pdo->prepare($sqlDelete);
            $stmtDelete->execute($duplicateIds);
            $deletedRows = $stmtDelete->rowCount();

            $debugOutput[] = "Deleted $deletedRows row(s) from contacts for groupKey: $groupKey.";
            $debugOutput[] = "DELETE Query: [$sqlDelete] with params: (" . implode(',', $duplicateIds) . ")";
        }

        echo "<p style='color:green;'>Merging complete.</p>";
        echo "<p><a href='../../pages/merge_contacts.php'>Back to Merge Page</a></p>";
        echo "<hr><pre>" . implode("\n", $debugOutput) . "</pre>";
    } catch (PDOException $e) {
        echo "Error merging contacts: " . $e->getMessage();
    }
} else {
    echo "Invalid request method.";
}
?>