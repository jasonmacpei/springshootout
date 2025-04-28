<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include config and DB connection
require_once '/home/lostan6/springshootout.ca/includes/config.php';
require_once __DIR__ . '/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contact_id = isset($_POST['contact_id']) ? $_POST['contact_id'] : null;
    $force_delete = isset($_POST['force_delete']) ? (bool)$_POST['force_delete'] : false;

    if ($contact_id) {
        try {
            // First check for foreign key constraints by checking the team_contacts table
            $checkStmt = $pdo->prepare("
                SELECT tc.team_id, t.team_name 
                FROM team_contacts tc 
                JOIN teams t ON tc.team_id = t.team_id 
                WHERE tc.contact_id = :contact_id
            ");
            $checkStmt->bindParam(':contact_id', $contact_id, PDO::PARAM_INT);
            $checkStmt->execute();
            $teams = $checkStmt->fetchAll(PDO::FETCH_ASSOC);
            $refCount = count($teams);
            
            if ($refCount > 0 && !$force_delete) {
                // Contact is referenced in team_contacts, can't delete unless forced
                http_response_code(409); // Conflict status code
                
                // Return JSON with team information
                $response = [
                    'error' => true,
                    'message' => "Cannot delete contact: This contact is associated with " . $refCount . " team(s). Remove these associations first or click 'Delete Anyway' to remove associations and delete the contact.",
                    'teams' => $teams,
                    'count' => $refCount
                ];
                
                echo json_encode($response);
                exit;
            }
            
            // If force_delete is true, remove the team_contacts associations first
            if ($force_delete && $refCount > 0) {
                $deleteAssociationsStmt = $pdo->prepare("DELETE FROM team_contacts WHERE contact_id = :contact_id");
                $deleteAssociationsStmt->bindParam(':contact_id', $contact_id, PDO::PARAM_INT);
                $deleteAssociationsStmt->execute();
            }
            
            // Now delete the contact
            $stmt = $pdo->prepare("DELETE FROM contacts WHERE contact_id = :contact_id");
            $stmt->bindParam(':contact_id', $contact_id, PDO::PARAM_INT);
            $result = $stmt->execute();
            
            if ($result && $stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => "Contact deleted successfully."]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => true, 'message' => "No contact found with ID " . $contact_id]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => true, 'message' => "Error deleting contact: " . $e->getMessage()]);
            error_log("Delete contact error: " . $e->getMessage());
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => true, 'message' => "No contact ID provided."]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => true, 'message' => "Invalid request method."]);
}
?>