<?php
// api/get_applications.php (FINAL, VERIFIED, AND CORRECTED)
session_start();
include 'db_connect.php';

// --- Security Check: Ensure user is logged in ---
if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get info for the currently logged-in user
$current_user_id = $_SESSION['user_id'];
$current_user_role = $_SESSION['user_role'];

// --- Initialize the response object ---
$response = [
    'applications' => [],
    'filter_options' => [
        'programs' => [],
        'statuses' => []
    ],
    'current_user_id' => $current_user_id, // COMMA WAS MISSING HERE
    'current_user_role' => $current_user_role
];

// --- Part 1: Load Filter Options ---
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

// --- Part 2: Build the Main Query ---
$main_query = "
    SELECT 
        app.id, app.application_no, app.student_name, app.choice1_program, 
        app.submission_timestamp, app.application_status, 
        app.evaluator_id,
        sa.full_name AS evaluator_name 
    FROM applications AS app
    LEFT JOIN staff_accounts AS sa ON app.evaluator_id = sa.id
    WHERE 1=1
";
$params = [];
$types = '';

// Apply filters from URL parameters
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
    if ($_GET['assignment_status'] === 'unassigned') {
        $main_query .= " AND app.evaluator_id IS NULL";
    } elseif ($_GET['assignment_status'] === 'assigned') {
        $main_query .= " AND app.evaluator_id IS NOT NULL";
    } 
    elseif ($_GET['assignment_status'] === 'me') {
        $main_query .= " AND app.evaluator_id = ?";
        $params[] = $current_user_id; // $current_user_id is already defined at the top
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

$main_query .= " ORDER BY app.submission_timestamp ASC";

// Execute the query
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

// --- Send the final JSON response ---
echo json_encode($response);
$conn->close();
?>