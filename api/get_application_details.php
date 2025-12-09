<?php
// api/get_application_details.php

// 1. Setup Error Handling (Prevent HTML errors from breaking JSON)
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');

// Start output buffering to catch any unexpected whitespace or warnings
ob_start();

try {
    session_start();

    // 2. Database Connection
    // Check multiple paths to ensure connection works
    if (file_exists('../db_connect.php')) {
        include '../db_connect.php';
    } elseif (file_exists('db_connect.php')) {
        include 'db_connect.php';
    } else {
        throw new Exception("Database connection file not found.");
    }

    // 3. Validate Input
    $application_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($application_id <= 0) {
        throw new Exception("Invalid Application ID provided.");
    }

    $response = [
        'details' => null,
        'documents' => []
    ];

    // 4. Fetch MAIN Application Details
    // REVISION: Restored "SELECT *" to ensure ALL columns (address, email, etc.) are fetched.
    // Also add computed student_name field for frontend compatibility
    $stmt = $conn->prepare("SELECT *, CONCAT(last_name, ', ', first_name, 
               CASE WHEN middle_name IS NOT NULL AND middle_name != '' THEN CONCAT(' ', middle_name) ELSE '' END,
               CASE WHEN name_extension IS NOT NULL AND name_extension != '' THEN CONCAT(' ', name_extension) ELSE '' END) AS student_name FROM applications WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Database prepare error: " . $conn->error);
    }
    
    $stmt->bind_param("i", $application_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $response['details'] = $result->fetch_assoc();
    $stmt->close();

    if (!$response['details']) {
        throw new Exception("Applicant not found.");
    }

    // [Helper Logic] ensure 'application_status' exists for the JS, regardless of column name
    // We check common column names (admission_status, status, application_status)
    if (isset($response['details']['admission_status'])) {
        $response['details']['application_status'] = $response['details']['admission_status'];
    } elseif (isset($response['details']['status'])) {
        $response['details']['application_status'] = $response['details']['status'];
    } elseif (!isset($response['details']['application_status'])) {
        // Default if no status column found
        $response['details']['application_status'] = 'For Evaluation';
    }

    // 5. Fetch Evaluation Status Map
    // We need to fetch the results from 'human_evaluation_checklist' to determine
    // if a document is Approved (Green) or Rejected (Red).
    $evalMap = [];
    $stmt = $conn->prepare("SELECT document_title, evaluation_status FROM human_evaluation_checklist WHERE application_id = ?");
    // Only run this if the table exists (prevents crash if you haven't created the table yet)
    if ($stmt) {
        $stmt->bind_param("i", $application_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            // The keys are stored like "Certificate of Enrollment::Blurred File Detection"
            $evalMap[$row['document_title']] = $row['evaluation_status'];
        }
        $stmt->close();
    }

    // 6. Fetch Documents and Calculate Status
    // We select ID, type, and path. Status is calculated below.
    $stmt = $conn->prepare("SELECT id, document_type, file_path FROM documents WHERE application_id = ?");
    $stmt->bind_param("i", $application_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($doc = $result->fetch_assoc()) {
        $docTitle = $doc['document_type'];
        
        // --- LOGIC TO DETERMINE STATUS ---
        // We look through the $evalMap to see if there are any checks for this document.
        // If ANY check is "Fail", the document is "Rejected".
        // If ANY check is "Pending", the document is "Pending".
        // If checks exist and ALL are "Pass", the document is "Approved".
        
        $isRejected = false;
        $isPending = false;
        $hasChecks = false;

        foreach ($evalMap as $key => $status) {
            // Check if the evaluation key belongs to this document.
            // Example: "Grades Form 1::Blurred File" starts with "Grades Form 1"
            if (strpos($key, $docTitle) === 0) {
                $hasChecks = true;
                if ($status === 'Fail') {
                    $isRejected = true;
                }
                if ($status === 'Pending') {
                    $isPending = true;
                }
            }
        }

        // Assign final status string based on flags
        if ($hasChecks) {
            if ($isRejected) {
                $finalStatus = 'Rejected';
            } elseif ($isPending) {
                $finalStatus = 'Pending';
            } else {
                $finalStatus = 'Approved';
            }
        } else {
            // No evaluation records found for this document yet
            $finalStatus = 'For Review';
        }

        // Add the calculated status to the document object
        $doc['file_status'] = $finalStatus;
        
        // Add to response
        $response['documents'][] = $doc;
    }
    $stmt->close();

    // 7. Get AI analysis results (Optional, but included in your previous request)
    $stmt = $conn->prepare("SELECT * FROM ai_document_analysis WHERE application_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $application_id);
        $stmt->execute();
        $result = $stmt->get_result();
        // Fetch all rows because AI analysis is now multi-row (one per document)
        $ai_data = [];
        while($ai_row = $result->fetch_assoc()) {
            $ai_data[] = $ai_row;
        }
        $response['analysis'] = $ai_data;
        $stmt->close();
    }

    // 8. Clean Output Buffer and Send JSON
    ob_end_clean();
    echo json_encode($response);

} catch (Exception $e) {
    // Handle Errors gracefully
    ob_end_clean();
    http_response_code(500); // Internal Server Error
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}

// Close connection
if (isset($conn)) {
    $conn->close();
}
?>