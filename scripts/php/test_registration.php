<?php
/**
 * test_registration.php
 * 
 * A simple script to test the registration backend logic without using the actual web form.
 * This script will:
 * 1. Simulate form submissions with different phone number formats
 * 2. Verify that phone numbers are properly cleaned and stored
 * 3. Check that team-contact relationships are correctly established
 */

// Display all errors for testing
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Load the same dependencies as register.php
require_once 'db_connect.php';

// Function to generate a unique test team name to avoid duplicates
function generateTestTeamName() {
    return 'Test Team ' . date('YmdHis') . '_' . rand(100, 999);
}

// Function to clean up test data after testing (comment out if you want to keep test data)
function cleanupTestData($pdo, $teamName) {
    try {
        // Find the team
        $teamQuery = "SELECT team_id FROM teams WHERE team_name = :name";
        $stmt = $pdo->prepare($teamQuery);
        $stmt->execute([':name' => $teamName]);
        $teamId = $stmt->fetchColumn();
        
        if (!$teamId) {
            return false; // Team not found
        }
        
        // Find associated contacts
        $contactQuery = "SELECT contact_id FROM team_contacts WHERE team_id = :team_id";
        $stmt = $pdo->prepare($contactQuery);
        $stmt->execute([':team_id' => $teamId]);
        $contactIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Start transaction for cleanup
        $pdo->beginTransaction();
        
        // Delete from registrations
        $stmt = $pdo->prepare("DELETE FROM registrations WHERE team_id = :team_id");
        $stmt->execute([':team_id' => $teamId]);
        
        // Delete from team_contacts
        $stmt = $pdo->prepare("DELETE FROM team_contacts WHERE team_id = :team_id");
        $stmt->execute([':team_id' => $teamId]);
        
        // Delete the team
        $stmt = $pdo->prepare("DELETE FROM teams WHERE team_id = :team_id");
        $stmt->execute([':team_id' => $teamId]);
        
        // Delete contacts
        if (!empty($contactIds)) {
            $placeholders = implode(',', array_fill(0, count($contactIds), '?'));
            $stmt = $pdo->prepare("DELETE FROM contacts WHERE contact_id IN ($placeholders)");
            $stmt->execute($contactIds);
        }
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo "Error cleaning up test data: " . $e->getMessage() . "\n";
        return false;
    }
}

// Function to simulate a registration with a given phone format
function testRegistration($pdo, $phoneFormat) {
    $teamName = generateTestTeamName();
    $contactName = "Test Contact " . date('YmdHis');
    $roleId = 1; // Assuming role_id 1 exists in your database
    $province = "Test Province";
    $division = "u12";
    $class = "AA";
    $email = "test_" . time() . "@example.com";
    $phone = $phoneFormat;
    $note = "Test registration created at " . date('Y-m-d H:i:s');
    
    echo "Testing registration with phone format: $phone\n";
    
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // Clean phone number (same as in register.php)
        $cleanedPhone = preg_replace('/\D/', '', $phone);
        echo "  Phone after cleaning: $cleanedPhone\n";
        
        // 1) Insert into contacts table
        $contactSQL = "INSERT INTO contacts (contact_name, email_address, phone_number, role_id)
                       VALUES (:contactName, :email, :phone, :role_id)
                       RETURNING contact_id";
        $contactStmt = $pdo->prepare($contactSQL);
        $contactStmt->execute([
            ':contactName' => $contactName,
            ':email'       => $email,
            ':phone'       => $cleanedPhone,
            ':role_id'     => $roleId
        ]);
        $newContactId = $contactStmt->fetchColumn();
        
        echo "  Inserted contact with ID: $newContactId\n";
        
        // 2) Insert into teams table
        $teamSQL = "INSERT INTO teams (team_name)
                    VALUES (:teamName)
                    RETURNING team_id";
        $teamStmt = $pdo->prepare($teamSQL);
        $teamStmt->execute([':teamName' => $teamName]);
        $newTeamId = $teamStmt->fetchColumn();
        
        echo "  Inserted team with ID: $newTeamId\n";
        
        // 3) Insert into registrations table
        $registrationSQL = "
          INSERT INTO registrations 
              (team_id, contact_id, province, division, class, note, year, paid, status)
          VALUES 
              (:teamId, :contactId, :province, :division, :class, :note, :year, FALSE, 1)
        ";
        $regStmt = $pdo->prepare($registrationSQL);
        $regStmt->execute([
            ':teamId'    => $newTeamId,
            ':contactId' => $newContactId,
            ':province'  => $province,
            ':division'  => $division,
            ':class'     => $class,
            ':note'      => $note,
            ':year'      => date('Y')
        ]);
        
        // 4) Insert into pivot table: team_contacts
        $tcSQL = "INSERT INTO team_contacts (team_id, contact_id, role_id, created_at, updated_at)
                  VALUES (:teamId, :contactId, :roleId, NOW(), NOW())";
        $tcStmt = $pdo->prepare($tcSQL);
        $tcStmt->execute([
            ':teamId'    => $newTeamId,
            ':contactId' => $newContactId,
            ':roleId'    => $roleId
        ]);
        
        // Commit transaction
        $pdo->commit();
        
        // Verify the data was stored correctly
        $verifyQuery = "
            SELECT 
                t.team_name, 
                c.contact_name, 
                c.email_address, 
                c.phone_number,
                r.province,
                r.division,
                r.class,
                r.note,
                tc.role_id
            FROM teams t
            JOIN team_contacts tc ON t.team_id = tc.team_id
            JOIN contacts c ON tc.contact_id = c.contact_id
            JOIN registrations r ON t.team_id = r.team_id
            WHERE t.team_name = :team_name
        ";
        $verifyStmt = $pdo->prepare($verifyQuery);
        $verifyStmt->execute([':team_name' => $teamName]);
        $result = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        
        echo "Verification Results:\n";
        echo "  Team Name: " . $result['team_name'] . "\n";
        echo "  Contact Name: " . $result['contact_name'] . "\n";
        echo "  Email: " . $result['email_address'] . "\n";
        echo "  Phone Number in DB: " . $result['phone_number'] . "\n";
        echo "  Province: " . $result['province'] . "\n";
        echo "  Division: " . $result['division'] . "\n";
        echo "  Class: " . $result['class'] . "\n";
        echo "  Note: " . $result['note'] . "\n";
        echo "  Role ID: " . $result['role_id'] . "\n";
        
        // Verify phone number was stored correctly (digits only)
        if ($result['phone_number'] === $cleanedPhone) {
            echo "✅ PASS: Phone number stored correctly as digits only.\n";
        } else {
            echo "❌ FAIL: Phone number not stored correctly.\n";
            echo "  Expected: $cleanedPhone, Got: " . $result['phone_number'] . "\n";
        }
        
        echo "Test completed successfully.\n";
        
        // Clean up test data
        return $teamName; // Return team name for cleanup
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo "Error during test: " . $e->getMessage() . "\n";
        return false;
    }
}

// Run tests with different phone formats
echo "=== STARTING REGISTRATION TESTS ===\n\n";

// Test with different phone formats
$phoneFormats = [
    '(123) 456-7890',
    '123-456-7890',
    '123.456.7890',
    '123 456 7890',
    '(123)456-7890',
    '1234567890'
];

$teamsToCleanup = [];

foreach ($phoneFormats as $format) {
    echo "\n===== Testing with format: $format =====\n";
    $result = testRegistration($pdo, $format);
    if ($result) {
        $teamsToCleanup[] = $result;
    }
    echo "======================================\n";
}

// Clean up all test data
echo "\n=== CLEANING UP TEST DATA ===\n";
foreach ($teamsToCleanup as $teamName) {
    echo "Cleaning up team: $teamName\n";
    if (cleanupTestData($pdo, $teamName)) {
        echo "✅ Cleanup successful\n";
    } else {
        echo "❌ Cleanup failed\n";
    }
}

echo "\n=== ALL TESTS COMPLETED ===\n"; 