<?php
session_start();
include 'db_connect.php';
// ... (Full Security Check for Logged-in User) ...

$input = json_decode(file_get_contents('php://input'), true);
$application_id = $input['application_id'] ?? null;
$checklist_data = $input['checklist'] ?? [];
$final_status = $input['final_status'] ?? null;
$comments = $input['comments'] ?? null;
$evaluator_id = $_SESSION['user_id']; // The current user is the evaluator

// ... (Validation for all required fields) ...

// Use a transaction to ensure all updates succeed or none do
$conn->begin_transaction();
try {
    // 1. Update/Insert Human Checklist Data
    $stmt = $conn->prepare("
        INSERT INTO human_evaluation_checklist (application_id, document_title, evaluation_status) 
        VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE evaluation_status = VALUES(evaluation_status)
    ");
    foreach ($checklist_data as $doc_title => $status) {
        $stmt->bind_param("iss", $application_id, $doc_title, $status);
        $stmt->execute();
    }
    $stmt->close();

    // 2. Update the Application Status, Comments, and Evaluator ID
    $stmt = $conn->prepare("
        UPDATE applications 
        SET application_status = ?, reviewer_comments = ?, evaluator_id = ?
        WHERE id = ?
    ");
    $stmt->bind_param("ssii", $final_status, $comments, $evaluator_id, $application_id);
    $stmt->execute();
    $stmt->close();

    // If we reach here, all queries were successful
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Evaluation submitted successfully.']);

} catch (mysqli_sql_exception $exception) {
    $conn->rollback(); // Revert all changes on error
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database update failed.']);
}
$conn->close();
?>