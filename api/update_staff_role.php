<?php
// api/update_staff_role.php
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');
ob_start();

try {
    session_start();
    if (file_exists('../db_connect.php')) include '../db_connect.php';
    elseif (file_exists('db_connect.php')) include 'db_connect.php';
    else throw new Exception("Database connection not found");

    // 1. Auth Check: User must be logged in
    if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user_id'])) {
        throw new Exception("Unauthorized access.");
    }

    $current_user_id = $_SESSION['user_id'];

    // 2. Permission Check: Current user must be an Admin
    // We check the DB to ensure the user making the request is actually an Admin
    $stmt = $conn->prepare("SELECT role FROM staff_accounts WHERE id = ?");
    $stmt->bind_param("i", $current_user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $current_role = $res['role'] ?? '';
    
    // Allow "Admin" or "Admin (Full Control)"
    if (stripos($current_role, 'Admin') === false) {
        throw new Exception("Access Denied: You do not have permission to edit roles.");
    }

    // 3. Get Input Data
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['staff_id']) || !isset($data['new_role'])) {
        throw new Exception("Missing required fields.");
    }

    $target_staff_id = intval($data['staff_id']);
    $new_role = trim($data['new_role']);

    // 4. Update the Role
    // We allow any string that comes from the dropdown ("Analyzer", "Document Evaluator", etc.)
    $update_stmt = $conn->prepare("UPDATE staff_accounts SET role = ? WHERE id = ?");
    $update_stmt->bind_param("si", $new_role, $target_staff_id);
    
    if ($update_stmt->execute()) {
        ob_end_clean();
        echo json_encode(['success' => true, 'message' => 'Role updated successfully.']);
    } else {
        throw new Exception("Database error: " . $conn->error);
    }
    $update_stmt->close();

} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

if (isset($conn)) $conn->close();
?>