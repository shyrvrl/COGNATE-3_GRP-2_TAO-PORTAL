<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');
ob_start();

try {
    session_start();
    // Connect DB
    if (file_exists('../db_connect.php')) include '../db_connect.php';
    elseif (file_exists('db_connect.php')) include 'db_connect.php';
    else throw new Exception("DB connection not found");

    if (!isset($_SESSION['logged_in'])) throw new Exception("Unauthorized");

    $user_id = $_SESSION['user_id'];

    // 1. Get Current User Role
    $stmt = $conn->prepare("SELECT role FROM staff_accounts WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $roleRes = $stmt->get_result()->fetch_assoc();
    $user_role = $roleRes['role'] ?? 'Evaluator';
    $stmt->close();

    // --- FIX: Flexible Admin Check ---
    // Checks if "Admin" appears anywhere in the role string (e.g., "Admin (Full Control)")
    $is_admin = (stripos($user_role, 'Admin') !== false);

    // 2. Get System Settings
    $result = $conn->query("SELECT application_open_date, application_deadline, is_portal_open FROM system_settings WHERE id = 1");
    $settings = $result->fetch_assoc();

    if (!$settings) {
        $settings = [
            'application_open_date' => date('Y-m-d'),
            'application_deadline' => date('Y-m-d', strtotime('+1 month')),
            'is_portal_open' => 1
        ];
    }

    ob_end_clean();
    echo json_encode([
        'settings' => $settings,
        'user_role' => $user_role,
        'can_edit' => $is_admin // Logic updated here
    ]);

} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => $e->getMessage()]);
}
if (isset($conn)) $conn->close();
?>