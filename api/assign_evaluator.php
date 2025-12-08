<?php
session_start();
include 'db_connect.php';

// --- Security Check ---
if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

// Get the ID of the current evaluator and the applicant ID from the request
$current_user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$application_id = $input['application_id'] ?? null;

if (empty($application_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Application ID is required.']);
    exit;
}

// --- Database Update ---
// This is a CRITICAL query. It will only update the row IF evaluator_id is currently NULL.
// This prevents two evaluators from assigning the same applicant at the same time.
$stmt = $conn->prepare("
    UPDATE applications 
    SET evaluator_id = ? 
    WHERE id = ? AND evaluator_id IS NULL
");
$stmt->bind_param("ii", $current_user_id, $application_id);
$stmt->execute();

// Check if the update was successful (i.e., if a row was affected)
if ($stmt->affected_rows > 0) {
    echo json_encode([
        'success' => true, 
        'message' => 'You have successfully assigned this applicant to yourself.',
        'evaluator_name' => $_SESSION['user_name'] // Send back the name to update the UI
    ]);
} else {
    // This means the evaluator_id was NOT NULL, so someone else already took it.
    http_response_code(409); // 409 Conflict
    echo json_encode(['success' => false, 'message' => 'This applicant has already been assigned to another evaluator.']);
}

$stmt->close();
$conn->close();
?>