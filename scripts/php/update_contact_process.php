<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include config and DB connection
require_once '/home/lostan6/springshootout.ca/includes/config.php';
require_once __DIR__ . '/../scripts/php/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve posted form data
    $contact_id = isset($_POST['contact_id']) ? $_POST['contact_id'] : null;
    $contact_name = isset($_POST['contact_name']) ? $_POST['contact_name'] : '';
    $email_address = isset($_POST['email_address']) ? $_POST['email_address'] : '';
    $phone_number = isset($_POST['phone_number']) ? $_POST['phone_number'] : '';

    if ($contact_id) {
        try {
            // Prepare and execute update statement
            $sql = "UPDATE contacts
                    SET contact_name = :contact_name,
                        email_address = :email_address,
                        phone_number = :phone_number
                    WHERE contact_id = :contact_id";
            $stmt = $pdo->prepare($sql);

            $stmt->bindParam(':contact_id', $contact_id, PDO::PARAM_INT);
            $stmt->bindParam(':contact_name', $contact_name, PDO::PARAM_STR);
            $stmt->bindParam(':email_address', $email_address, PDO::PARAM_STR);
            $stmt->bindParam(':phone_number', $phone_number, PDO::PARAM_STR);

            $stmt->execute();

            echo "Contact updated successfully.";
        } catch (PDOException $e) {
            echo "Error updating contact: " . $e->getMessage();
        }
    } else {
        echo "No contact ID provided.";
    }
} else {
    echo "Invalid request method.";
}
?>