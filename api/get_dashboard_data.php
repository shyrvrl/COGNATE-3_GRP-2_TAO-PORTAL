<?php
// api/get_dashboard_data.php

// 1. Disable HTML error output to prevent breaking JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');

// Start output buffering
ob_start();

try {
    session_start();

    // 2. ROBUST DATABASE CONNECTION
    // This fixes the 500 error by finding the file wherever it is.
    if (file_exists('../db_connect.php')) {
        include '../db_connect.php';
    } elseif (file_exists('db_connect.php')) {
        include 'db_connect.php';
    } else {
        throw new Exception("Critical Error: 'db_connect.php' file not found. Please check file structure.");
    }

    // Check if connection variable exists
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Database connection failed: " . ($conn->connect_error ?? 'Check db_connect.php'));
    }

    // 3. Security Check
    if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user_id'])) {
        http_response_code(403);
        throw new Exception("Unauthorized: You must be logged in.");
    }

    $current_user_id = $_SESSION['user_id'];

    $response = [
        'stats' => [],
        'notices' => [],
        'tasks' => []
    ];

    // 4. Fetch Statistics
    // Using 'application_status' and 'evaluator_id' as requested.
    $status_counts_sql = "
        SELECT
            COUNT(*) AS total_applications,
            SUM(CASE WHEN application_status = 'For Evaluation' THEN 1 ELSE 0 END) AS for_evaluation,
            SUM(CASE WHEN application_status = 'For Interview' THEN 1 ELSE 0 END) AS for_interview,
            SUM(CASE WHEN application_status = 'For Approval' THEN 1 ELSE 0 END) AS for_approval,
            SUM(CASE WHEN application_status = 'Approved' THEN 1 ELSE 0 END) AS approved,
            SUM(CASE WHEN application_status = 'Rejected' THEN 1 ELSE 0 END) AS rejected,
            SUM(CASE WHEN evaluator_id = ? THEN 1 ELSE 0 END) AS my_evaluated
        FROM applications
    ";

    $stmt = $conn->prepare($status_counts_sql);
    
    // ERROR TRAP: Check if the query failed (e.g., if 'evaluator_id' doesn't exist)
    if (!$stmt) {
        throw new Exception("Database Query Failed: " . $conn->error . " (Check if column 'evaluator_id' or 'application_status' exists)");
    }

    $stmt->bind_param("i", $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $response['stats'] = $row;
    }
    $stmt->close();

    // 5. Fetch Notices
    // We use a try-catch inside here so if the table doesn't exist, it doesn't crash the whole dashboard
    try {
        $notices_sql = "SELECT notice_title, notice_content, notice_type FROM system_notices ORDER BY created_at DESC LIMIT 5";
        $result = $conn->query($notices_sql);
        if ($result) {
            while($row = $result->fetch_assoc()) {
                $response['notices'][] = $row;
            }
        }
    } catch (Throwable $e) {
        // Ignore notice errors (table might not exist yet), just return empty notices
    }

    // 6. Fetch Tasks
    $tasks_sql = "SELECT CONCAT(last_name, ', ', first_name, 
               CASE WHEN middle_name IS NOT NULL AND middle_name != '' THEN CONCAT(' ', middle_name) ELSE '' END,
               CASE WHEN name_extension IS NOT NULL AND name_extension != '' THEN CONCAT(' ', name_extension) ELSE '' END) AS student_name, 
               application_status FROM applications WHERE application_status = 'For Evaluation' ORDER BY id ASC LIMIT 3";
    $result = $conn->query($tasks_sql);
    if ($result) {
        while($row = $result->fetch_assoc()) {
            $response['tasks'][] = $row;
        }
    }

    // Output JSON
    ob_end_clean();
    echo json_encode($response);

} catch (Throwable $e) {
    // This catches Fatal Errors (like 500s) and sends them as JSON
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}

if (isset($conn)) $conn->close();
?>