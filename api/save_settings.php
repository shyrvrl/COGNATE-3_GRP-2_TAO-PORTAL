<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');
ob_start();

try {
    session_start();
    if (file_exists('../db_connect.php')) include '../db_connect.php';
    elseif (file_exists('db_connect.php')) include 'db_connect.php';

    // 1. Auth & Role Check
    if (!isset($_SESSION['logged_in'])) throw new Exception("Unauthorized");
    
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT role FROM staff_accounts WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    // --- FIX: Flexible Admin Check ---
    $user_role = $user['role'] ?? '';
    if (stripos($user_role, 'Admin') === false) {
        throw new Exception("Access Denied: Only Admins can change settings.");
    }
    $stmt->close();

    // 2. Process Input
    $data = json_decode(file_get_contents("php://input"), true);
    
    $open_date = $data['application_open_date'];
    $deadline = $data['application_deadline'];
    $is_open = isset($data['is_portal_open']) ? (int)$data['is_portal_open'] : 0;

    // 3. Update Database
    $stmt = $conn->prepare("UPDATE system_settings SET application_open_date = ?, application_deadline = ?, is_portal_open = ? WHERE id = 1");
    $stmt->bind_param("ssi", $open_date, $deadline, $is_open);
    
    if ($stmt->execute()) {
        ob_end_clean();
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Database update failed.");
    }
    $stmt->close();

} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
if (isset($conn)) $conn->close();
?>