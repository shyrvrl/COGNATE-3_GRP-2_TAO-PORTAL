<?php
// api/get_staff_list.php
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');
ob_start();

try {
    session_start();
    if (file_exists('../db_connect.php')) include '../db_connect.php';
    elseif (file_exists('db_connect.php')) include 'db_connect.php';
    else throw new Exception("Database connection not found");

    if (!isset($_SESSION['logged_in'])) throw new Exception("Unauthorized");

    $sql = "SELECT id, staff_id_no, full_name, role FROM staff_accounts ORDER BY full_name ASC";
    
    $result = $conn->query($sql);
    
    $staff = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $staff[] = $row;
        }
    }

    ob_end_clean();
    echo json_encode($staff);

} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => $e->getMessage()]);
}

if (isset($conn)) $conn->close();
?>