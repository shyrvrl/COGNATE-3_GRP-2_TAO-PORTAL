<?php
// api/change_password.php
header('Content-Type: application/json');
require_once 'db_connect.php';
require_once 'session_check.php'; // Ensures user is logged in and sets $_SESSION['user_id']

// ==========================================
// CONFIGURATION: UPDATED BASED ON YOUR SCREENSHOT
// ==========================================
$TABLE_NAME      = 'staff_accounts'; 
$ID_COLUMN       = 'id';
$PASSWORD_COLUMN = 'password_hash'; 
// ==========================================

$data = json_decode(file_get_contents("php://input"), true);

$currentPassword = $data['current_password'] ?? '';
$newPassword     = $data['new_password'] ?? '';
$confirmPassword = $data['confirm_password'] ?? '';

// We assume session_check.php sets this variable. 
// If your login logic sets a different session key (like $_SESSION['id']), please adjust here.
$userId = $_SESSION['user_id']; 

// 1. Basic Validation
if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

if ($newPassword !== $confirmPassword) {
    echo json_encode(['success' => false, 'message' => 'New passwords do not match.']);
    exit;
}

if (strlen($newPassword) < 6) {
    echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters long.']);
    exit;
}

try {
    // 2. Fetch the current hash from the database
    // SELECT password_hash FROM staff_accounts WHERE id = ?
    $query = "SELECT $PASSWORD_COLUMN FROM $TABLE_NAME WHERE $ID_COLUMN = ?";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'User account not found.']);
        exit;
    }

    $row = $result->fetch_assoc();
    $dbHash = $row[$PASSWORD_COLUMN];

    // 3. Verify Current Password
    if (!password_verify($currentPassword, $dbHash)) {
        echo json_encode(['success' => false, 'message' => 'Incorrect current password.']);
        exit;
    }

    // 4. Hash the New Password
    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

    // 5. Update Database
    // UPDATE staff_accounts SET password_hash = ? WHERE id = ?
    $updateQuery = "UPDATE $TABLE_NAME SET $PASSWORD_COLUMN = ? WHERE $ID_COLUMN = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("si", $newHash, $userId);
    
    if ($updateStmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Password updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error during update.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>