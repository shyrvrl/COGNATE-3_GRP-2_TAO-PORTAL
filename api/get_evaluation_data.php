<?php
// Error Handling Setup
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');
ob_start(); // Start buffer to catch unexpected HTML output

try {
    session_start();
    
    // Connect to Database
    if (file_exists('../db_connect.php')) include '../db_connect.php';
    elseif (file_exists('db_connect.php')) include 'db_connect.php';
    else throw new Exception("Database connection file not found");

    $application_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($application_id <= 0) throw new Exception("Invalid Application ID");

    $response = [
        'details' => null,
        'ai_results' => [], // Changed from null to array
        'human_checklist' => []
    ];

    // 1. Get Applicant Details
    $stmt = $conn->prepare("SELECT student_name, application_no, choice1_program FROM applications WHERE id = ?");
    $stmt->bind_param("i", $application_id);
    $stmt->execute();
    $response['details'] = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$response['details']) throw new Exception("Applicant not found");

    // 2. Get AI Analysis Results (Granular: Row per Document)
    // We fetch ALL rows for this applicant
    $query = "SELECT document_type, filter_blurred, filter_cropped, filter_file_size, 
                     program_specific_screening, grade_requirements_screening, check_autofill_completeness 
              FROM ai_document_analysis 
              WHERE application_id = ?";
              
    $stmt = $conn->prepare($query);
    if (!$stmt) throw new Exception("DB Error (AI Table): " . $conn->error);
    
    $stmt->bind_param("i", $application_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Re-structure data: Key = Document Name, Value = Array of checks
    while ($row = $result->fetch_assoc()) {
        $docType = $row['document_type'];
        // Remove document_type from the internal array to keep it clean, or keep it.
        $response['ai_results'][$docType] = $row;
    }
    $stmt->close();

    // 3. Get Human Checklist
    $stmt = $conn->prepare("SELECT document_title, evaluation_status FROM human_evaluation_checklist WHERE application_id = ?");
    $stmt->bind_param("i", $application_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        // Key format expected by JS: "DocumentName::CheckLabel"
        $response['human_checklist'][$row['document_title']] = $row['evaluation_status'];
    }
    $stmt->close();

    // Output JSON
    ob_end_clean();
    echo json_encode($response);

} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => $e->getMessage()]);
}

if (isset($conn)) $conn->close();
?>