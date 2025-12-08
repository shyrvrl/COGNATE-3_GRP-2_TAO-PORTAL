<?php

// 1. Prevent HTML errors from breaking the JSON response
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');
session_start();

// 2. Robust Database Connection
if (file_exists('../db_connect.php')) {
    include '../db_connect.php';
} elseif (file_exists('db_connect.php')) {
    include 'db_connect.php';
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection file not found']);
    exit;
}

// --- Security Check: Ensure user is logged in ---
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// --- Initialize the final response object ---
$response = [
    'summary_stats' => [],
    'status_breakdown' => [],
    'top_programs' => []
];

// --- 0. [NEW] Fetch Application Deadline from System Settings ---
// We do this first so we can include it in the summary stats
$deadline_date = '2025-12-05'; // Default fallback if DB is empty
$settings_sql = "SELECT application_deadline FROM system_settings WHERE id = 1";
$settings_result = $conn->query($settings_sql);

if ($settings_result && $settings_result->num_rows > 0) {
    $row = $settings_result->fetch_assoc();
    if (!empty($row['application_deadline'])) {
        $deadline_date = $row['application_deadline'];
    }
}

// --- 1. Calculate the Summary Card Statistics ---
$summary_sql = "
    SELECT
        COUNT(*) AS total_applications,
        SUM(CASE WHEN application_status = 'Approved' THEN 1 ELSE 0 END) AS approved_count
    FROM applications
";
$summary_result = $conn->query($summary_sql);
if ($summary_result) {
    $stats = $summary_result->fetch_assoc();
    
    // Calculate Admission Rate
    $total_apps = (int)$stats['total_applications'];
    $approved_apps = (int)$stats['approved_count'];
    $admission_rate = ($total_apps > 0) ? ($approved_apps / $total_apps) * 100 : 0;

    $response['summary_stats'] = [
        'total_applications' => $total_apps,
        'admission_rate' => round($admission_rate, 1), // Round to one decimal place
        'deadline_date' => $deadline_date 
    ];
}

// --- 2. Calculate the Status Breakdown ---
$status_sql = "
    SELECT
        application_status,
        COUNT(*) AS status_count
    FROM applications
    WHERE application_status != 'Documents Incomplete' -- Exclude this status as requested
    GROUP BY application_status
";
$status_result = $conn->query($status_sql);
$total_for_percentage = $response['summary_stats']['total_applications'];
if ($status_result) {
    while ($row = $status_result->fetch_assoc()) {
        $count = (int)$row['status_count'];
        $percentage = ($total_for_percentage > 0) ? ($count / $total_for_percentage) * 100 : 0;
        
        $response['status_breakdown'][] = [
            'status' => $row['application_status'],
            'count' => $count,
            'percentage' => round($percentage, 1)
        ];
    }
}

// --- 3. Calculate Top Program Applications ---
$program_sql = "
    SELECT
        choice1_program,
        COUNT(*) AS program_count,
        (SUM(CASE WHEN application_status = 'Approved' THEN 1 ELSE 0 END) / COUNT(*)) * 100 AS acceptance_rate
    FROM applications
    GROUP BY choice1_program
    ORDER BY program_count DESC
    LIMIT 5 -- Get the top 5 programs
";
$program_result = $conn->query($program_sql);
if ($program_result) {
    while ($row = $program_result->fetch_assoc()) {
        $response['top_programs'][] = [
            'program' => $row['choice1_program'],
            'count' => (int)$row['program_count'],
            'acceptance_rate' => round($row['acceptance_rate']) // Round to nearest whole number
        ];
    }
}

echo json_encode($response);
$conn->close();
?>