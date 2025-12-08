<?php

// 1. Error Handling
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');

session_start();

// 2. Database Connection
if (file_exists('../db_connect.php')) include '../db_connect.php';
elseif (file_exists('db_connect.php')) include 'db_connect.php';
else {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// --- Security Check ---
if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$current_user_id = $_SESSION['user_id'];

// --- Fetch Role ---
$roleStmt = $conn->prepare("SELECT role FROM staff_accounts WHERE id = ?");
$roleStmt->bind_param("i", $current_user_id);
$roleStmt->execute();
$roleRes = $roleStmt->get_result()->fetch_assoc();
$current_user_role = $roleRes['role'] ?? 'Evaluator';
$roleStmt->close();

// --- Initialize Response ---
$response = [
    'applications' => [],
    'filter_options' => [
        'programs' => [],
        'statuses' => []
    ],
    'current_user_id' => $current_user_id,
    'current_user_role' => $current_user_role
];

// --- Part 1: Load Filters ---
$program_sql = "SELECT DISTINCT choice1_program FROM applications ORDER BY choice1_program ASC";
$program_result = $conn->query($program_sql);
if ($program_result) {
    while ($row = $program_result->fetch_assoc()) {
        $response['filter_options']['programs'][] = $row['choice1_program'];
    }
}

$status_sql = "SELECT DISTINCT application_status FROM applications ORDER BY application_status ASC";
$status_result = $conn->query($status_sql);
if ($status_result) {
    while ($row = $status_result->fetch_assoc()) {
        $response['filter_options']['statuses'][] = $row['application_status'];
    }
}

// --- Part 2: Main Query ---
$main_query = "
    SELECT 
        app.id, app.application_no, app.student_name, app.choice1_program, 
        app.submission_timestamp, app.application_status, 
        app.evaluator_id,
        app.last_updated, 
        sa.full_name AS evaluator_name 
    FROM applications AS app
    LEFT JOIN staff_accounts AS sa ON app.evaluator_id = sa.id
    WHERE 1=1
";
$params = [];
$types = '';

// Apply filters
if (!empty($_GET['status'])) {
    $main_query .= " AND app.application_status = ?";
    $params[] = $_GET['status'];
    $types .= 's';
}
if (!empty($_GET['program'])) {
    $main_query .= " AND app.choice1_program = ?";
    $params[] = $_GET['program'];
    $types .= 's';
}
if (!empty($_GET['assignment_status'])) {
    $as = $_GET['assignment_status'];
    if ($as === 'unassigned') {
        $main_query .= " AND app.evaluator_id IS NULL";
    } elseif ($as === 'assigned' || $as === 'assignedToAll') {
        $main_query .= " AND app.evaluator_id IS NOT NULL";
    } elseif ($as === 'me') {
        $main_query .= " AND app.evaluator_id = ?";
        $params[] = $current_user_id; 
        $types .= 'i'; 
    }
}
if (!empty($_GET['date_start'])) {
    $main_query .= " AND DATE(app.submission_timestamp) >= ?";
    $params[] = $_GET['date_start'];
    $types .= 's';
}
if (!empty($_GET['date_end'])) {
    $main_query .= " AND DATE(app.submission_timestamp) <= ?";
    $params[] = $_GET['date_end'];
    $types .= 's';
}
// ORDER BY app.last_updated ASC
// This puts the oldest updates at the top, and the NEWEST updates at the BOTTOM.
$main_query .= " ORDER BY app.last_updated ASC, app.id ASC";

// Execute
$stmt = $conn->prepare($main_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $response['applications'][] = $row;
}
$stmt->close();

echo json_encode($response);
$conn->close();
?>