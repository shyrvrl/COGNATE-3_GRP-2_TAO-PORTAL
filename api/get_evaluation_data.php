<?php
// api/get_evaluation_data.php

// Error Handling Setup
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');
ob_start(); 

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
        'ai_results' => [],
        'human_checklist' => []
    ];

    // 1. Get Applicant Details (CORRECTED COLUMN NAMES)
    // We select specific columns to ensure we have the data for AI matching
    $query = "SELECT student_name, application_no, choice1_program, 
                     birthdate, sex, 
                     jhs_math_grade, jhs_science_grade, jhs_english_grade, jhs_completion_year,
                     shs_school_name, shs_track, shs_strand,
                     shs_sem1_math_grade, shs_sem1_science_grade, shs_sem1_english_grade,
                     shs_sem2_math_grade, shs_sem2_science_grade, shs_sem2_english_grade
              FROM applications WHERE id = ?";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $application_id);
    $stmt->execute();
    $response['details'] = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$response['details']) throw new Exception("Applicant not found");

    // -------------------------------------------------------------------------
    // 1.5. AUTOMATIC AI CHECK
    // -------------------------------------------------------------------------
    $docQuery = $conn->prepare("SELECT id, file_path, document_type FROM documents WHERE application_id = ?");
    $docQuery->bind_param("i", $application_id);
    $docQuery->execute();
    $documents = $docQuery->get_result();

    while ($doc = $documents->fetch_assoc()) {
        // Only run for JHS Form 137 or SHS Form 137
        if (stripos($doc['document_type'], 'Form 137') !== false) {
            
            $checkStmt = $conn->prepare("SELECT id, data_consistency_check FROM ai_document_analysis WHERE application_id = ? AND document_type = ?");
            $checkStmt->bind_param("is", $application_id, $doc['document_type']);
            $checkStmt->execute();
            $analysis = $checkStmt->get_result()->fetch_assoc();
            
            // Run AI if not yet run or pending
            if (!$analysis || $analysis['data_consistency_check'] === 'Pending' || $analysis['data_consistency_check'] === NULL) {
                run_ai_analysis_internal($conn, $application_id, $doc, $response['details']);
            }
            $checkStmt->close();
        }
    }
    $docQuery->close();
    // -------------------------------------------------------------------------

    // 2. Get AI Analysis Results
    $query = "SELECT document_type, filter_blurred, filter_cropped, 
                     program_specific_screening, grade_requirements_screening, check_autofill_completeness,
                     data_consistency_check, match_details 
              FROM ai_document_analysis 
              WHERE application_id = ?";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $application_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $docType = $row['document_type'];
        // Decode the match details for frontend use if needed
        if(isset($row['match_details'])) {
            $row['match_details'] = json_decode($row['match_details'], true);
        }
        $response['ai_results'][$docType] = $row;
    }
    $stmt->close();

    // 3. Get Human Checklist (CORRECTED COLUMN NAMES)
    // Using 'document_title' as the key and 'evaluation_status' as value
    $stmt = $conn->prepare("SELECT document_title, evaluation_status FROM human_evaluation_checklist WHERE application_id = ?");
    $stmt->bind_param("i", $application_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $response['human_checklist'][$row['document_title']] = $row['evaluation_status'];
    }
    $stmt->close();

    ob_end_clean();
    echo json_encode($response);

} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => $e->getMessage()]);
}
if (isset($conn)) $conn->close();


// =============================================================================
// HELPER: INTERNAL AI ANALYSIS (UPDATED FOR SPECIFIC LOGIC)
// =============================================================================
function run_ai_analysis_internal($conn, $app_id, $doc, $applicant) {
    $NANONETS_API_KEY = "5078eb1d-b0db-11f0-890d-e6e0013317b1"; 
    $MODELS = [
        'JHS' => "4b96dc43-641d-4c60-99cd-c73e89e5f765", 
        'SHS' => "be5fec5f-0dec-43ad-8ba3-2344ce3a78bf"
    ];

    $is_jhs = stripos($doc['document_type'], 'JHS') !== false;
    $model_id = $is_jhs ? $MODELS['JHS'] : $MODELS['SHS'];

    // File Path Logic
    $base_dir = dirname(__DIR__); 
    $file_path_absolute = $base_dir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $doc['file_path']);
    if (!file_exists($file_path_absolute)) return;

    // Nanonets Call
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://app.nanonets.com/api/v2/OCR/Model/$model_id/LabelFile/");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERPWD, "$NANONETS_API_KEY:");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ['file' => new CURLFile($file_path_absolute)]);
    $result = curl_exec($ch);
    curl_close($ch);
    $ocr_data = json_decode($result, true);

    // Helper to normalize strings
    $norm = function($str) { return strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $str))); };
    $match_details = [];
    $overall_pass = true;

    // Helper Check Function
    $check = function($label, $db_val, $ocr_val, $is_fuzzy=false) use (&$match_details, &$overall_pass, $norm) {
        $pass = false;
        if (empty($db_val)) {
            // If DB value is empty, we can't verify, usually skip or mark N/A. Here we skip failing it.
             $match_details[$label] = "N/A (No Data)";
             return true;
        }

        if ($is_fuzzy) {
            similar_text($norm($db_val), $norm($ocr_val), $score);
            if ($score > 80) $pass = true;
        } else {
            // Strict-ish check (dates, grades)
            if ($norm($db_val) == $norm($ocr_val)) $pass = true;
            // Handle Grades (85.00 vs 85)
            if (is_numeric($db_val) && is_numeric($ocr_val)) {
                if (abs(floatval($db_val) - floatval($ocr_val)) < 0.1) $pass = true;
            }
        }
        
        $match_details[$label] = $pass ? "Pass" : "Fail (DB: $db_val vs Doc: $ocr_val)";
        if (!$pass) $overall_pass = false;
        return $pass;
    };

    // Extract common fields
    $extracted = [];
    if (isset($ocr_data['result'][0]['prediction'])) {
        foreach ($ocr_data['result'][0]['prediction'] as $f) {
            $extracted[$f['label']] = $f['ocr_text'];
        }
    }
    // Full text for subject searching
    $full_text = isset($ocr_data['result'][0]['ocr_text']) ? $ocr_data['result'][0]['ocr_text'] : '';

    // --- JHS LOGIC ---
    if ($is_jhs) {
        // 1. Name (Fuzzy)
        $db_name = $applicant['student_name']; 
        // Swap surname if comma exists
        if (strpos($db_name, ',') !== false) {
             $p = explode(',', $db_name);
             if(count($p)>=2) $db_name = trim($p[1]) . " " . trim($p[0]);
        }
        similar_text($norm($db_name), $norm($extracted['FULL_NAME'] ?? ''), $n_score);
        if($n_score < 70) { $match_details['name'] = "Fail ($db_name vs " . ($extracted['FULL_NAME']??'Missing') . ")"; $overall_pass = false; } else { $match_details['name'] = 'Pass'; }

        // 2. Birthdate & Sex
        $check('birthdate', $applicant['birthdate'], $extracted['BIRTHDATE'] ?? '');
        $check('sex', $applicant['sex'], $extracted['SEX'] ?? '');

        // 3. JHS Grades (Math, Sci, Eng)
        $check('math_grade', $applicant['jhs_math_grade'], $extracted['MATH_GRADE'] ?? '');
        $check('science_grade', $applicant['jhs_science_grade'], $extracted['SCI_GRADE'] ?? '');
        $check('english_grade', $applicant['jhs_english_grade'], $extracted['ENG_GRADE'] ?? '');

        // 4. Completion Year
        $check('completion_year', $applicant['jhs_completion_year'], $extracted['COMPLETION_YEAR'] ?? '');
    } 
    // --- SHS LOGIC ---
    else {
        // 1. Name, Bday, Sex
        $db_name = $applicant['student_name']; 
        if (strpos($db_name, ',') !== false) {
             $p = explode(',', $db_name);
             if(count($p)>=2) $db_name = trim($p[1]) . " " . trim($p[0]);
        }
        similar_text($norm($db_name), $norm($extracted['FULL_NAME'] ?? ''), $n_score);
        if($n_score < 70) { $match_details['name'] = "Fail ($db_name vs " . ($extracted['FULL_NAME']??'Missing') . ")"; $overall_pass = false; } else { $match_details['name'] = 'Pass'; }
        
        $check('birthdate', $applicant['birthdate'], $extracted['BIRTHDATE'] ?? '');
        $check('sex', $applicant['sex'], $extracted['SEX'] ?? '');

        // 2. Track & Strand & School (Fuzzy)
        similar_text($norm($applicant['shs_track'] . ' ' . $applicant['shs_strand']), $norm($extracted['TRACK_STRAND'] ?? ''), $t_score);
        // Lower threshold for track/strand as OCR often captures extra words
        if($t_score < 50) { $match_details['track_strand'] = "Fail (DB: {$applicant['shs_track']} {$applicant['shs_strand']})"; $overall_pass = false; } else { $match_details['track_strand'] = 'Pass'; }

        similar_text($norm($applicant['shs_school_name']), $norm($extracted['SCHOOL_NAME'] ?? ''), $s_score);
        if($s_score < 70) { $match_details['school_name'] = "Fail (DB: {$applicant['shs_school_name']})"; $overall_pass = false; } else { $match_details['school_name'] = 'Pass'; }

        // 3. ALL SUBJECTS (Sem 1 & Sem 2)
        // We look for the grade in the text.
        $subjects_to_check = [
            'Sem1_Math' => $applicant['shs_sem1_math_grade'],
            'Sem1_Sci' => $applicant['shs_sem1_science_grade'],
            'Sem1_Eng' => $applicant['shs_sem1_english_grade'],
            'Sem2_Math' => $applicant['shs_sem2_math_grade'],
            'Sem2_Sci' => $applicant['shs_sem2_science_grade'],
            'Sem2_Eng' => $applicant['shs_sem2_english_grade']
        ];

        foreach($subjects_to_check as $subj => $grade) {
            if(empty($grade)) continue;
            // Check if the grade exists in the OCR text
            if (strpos($full_text, (string)$grade) === false) {
                $match_details[$subj] = "Fail (Grade $grade not found)";
                $overall_pass = false;
            } else {
                $match_details[$subj] = "Pass";
            }
        }
    }

    $final_status = $overall_pass ? 'Pass' : 'Fail';
    $json_details = json_encode($match_details);
    $json_dump = json_encode($ocr_data);

    // DB Update
    $checkQ = $conn->prepare("SELECT id FROM ai_document_analysis WHERE application_id = ? AND document_type = ?");
    $checkQ->bind_param("is", $app_id, $doc['document_type']);
    $checkQ->execute();
    $exists = $checkQ->get_result()->fetch_assoc();
    $checkQ->close();

    if ($exists) {
        $upd = $conn->prepare("UPDATE ai_document_analysis SET match_details=?, raw_ocr_data=?, data_consistency_check=?, ocr_status='completed' WHERE id=?");
        $upd->bind_param("sssi", $json_details, $json_dump, $final_status, $exists['id']);
        $upd->execute();
        $upd->close();
    } else {
        // Fallback INSERT if not exists (though usually it exists from upload)
        $ins = $conn->prepare("INSERT INTO ai_document_analysis (application_id, document_id, document_type, match_details, raw_ocr_data, data_consistency_check, ocr_status) VALUES (?, ?, ?, ?, ?, ?, 'completed')");
        $ins->bind_param("iissss", $app_id, $doc['id'], $doc['document_type'], $json_details, $json_dump, $final_status);
        $ins->execute();
        $ins->close();
    }
}
?>