<?php
// fetch_team_contacts.php
// Returns a table of contacts for the specified team, using a dark table for styling.
// Called via AJAX from manage_team_contacts.php.

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include config and DB connection
require_once '/home/lostan6/springshootout.ca/includes/config.php';
require_once __DIR__ . '/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teamId = isset($_POST['team_id']) ? (int)$_POST['team_id'] : 0;
    if ($teamId <= 0) {
        echo "<div class='alert alert-danger'>Invalid team ID.</div>";
        exit;
    }

    try {
        // Fetch all contacts linked to this team, including their role names
        $sql = "
            SELECT 
                c.contact_id, 
                c.contact_name, 
                c.email_address, 
                tc.role_id,
                COALESCE(cr.role_name, 'No Role') as role_name
            FROM team_contacts tc
            JOIN contacts c ON c.contact_id = tc.contact_id
            LEFT JOIN contact_roles cr ON cr.role_id = tc.role_id
            WHERE tc.team_id = :team_id
            ORDER BY c.contact_name
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':team_id' => $teamId]);
        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($contacts)) {
            echo "<p>No contacts assigned to this team.</p>";
            exit;
        }

        // Build a dark table
        echo "<table class='table table-dark table-striped'>";
        echo "<thead>
                <tr>
                  <th>Contact Name</th>
                  <th>Email</th>
                  <th>Role</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>";

        foreach ($contacts as $contact) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($contact['contact_name']) . "</td>";
            echo "<td>" . htmlspecialchars($contact['email_address']) . "</td>";
            echo "<td>" . htmlspecialchars($contact['role_name']) . "</td>";
            // Remove button: .btn.btn-danger plus data attributes
            echo "<td>
                    <button 
                        class='btn btn-danger delete-contact-link'
                        data-team-id='" . $teamId . "'
                        data-contact-id='" . $contact['contact_id'] . "'>
                      Remove
                    </button>
                  </td>";
            echo "</tr>";
        }

        echo "</tbody></table>";
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'>Error fetching team contacts: " . htmlspecialchars($e->getMessage()) . "</div>";
        error_log("Error in fetch_team_contacts.php: " . $e->getMessage());
    }
} else {
    echo "<div class='alert alert-danger'>Invalid request method.</div>";
}
?>