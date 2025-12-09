<?php
if (file_exists('../db_connect.php')) include '../db_connect.php';
elseif (file_exists('db_connect.php')) include 'db_connect.php';
else {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
} 

header('Content-Type: application/json');

// --- CONFIGURATION ---
$NANONETS_API_KEY = "5078eb1d-b0db-11f0-890d-e6e0013317b1"; 
$MODELS = [
    'JHS'   => "4b96dc43-641d-4c60-99cd-c73e89e5f765", // JHS Form 137
    'SHS'   => "be5fec5f-0dec-43ad-8ba3-2344ce3a78bf", // SHS Form 137
    'FORM1' => "ab11f2c6-728c-4024-8f33-ed6a25cf0ab0"  // Grades Form 1
];

// --- 1. RECEIVE INPUT ---
$input = json_decode(file_get_contents('php://input'), true);
$doc_id = isset($input['document_id']) ? intval($input['document_id']) : 0;
$app_id = isset($input['application_id']) ? intval($input['application_id']) : 0;

if (!$doc_id || !$app_id) {
    echo json_encode(['success' => false, 'message' => 'Missing IDs']);
    exit;
}

// --- 2. FETCH FILE INFO FROM DB ---
$stmtDoc = $conn->prepare("SELECT file_path, document_type FROM documents WHERE id = ?");
$stmtDoc->bind_param("i", $doc_id);
$stmtDoc->execute();
$doc = $stmtDoc->get_result()->fetch_assoc();

if (!$doc) {
    echo json_encode(['success' => false, 'message' => 'Document not found']);
    exit;
}

// --- 3. FETCH APPLICANT DATA ---
$stmtApp = $conn->prepare("SELECT first_name, middle_name, last_name, name_extension, shs_school_name FROM applications WHERE id = ?");
$stmtApp->bind_param("i", $app_id);
$stmtApp->execute();
$applicant = $stmtApp->get_result()->fetch_assoc();

if (!$applicant) {
    echo json_encode(['success' => false, 'message' => 'Applicant not found']);
    exit;
}

// --- 4. SELECT MODEL & TARGET SCHOOL ---
$model_id = '';
$target_school_db = ''; 

// Determine which model to use based on Document Type
if (stripos($doc['document_type'], 'JHS') !== false) {
    $model_id = $MODELS['JHS'];
    $target_school_db = "JHS School (Not in DB)"; // Placeholder logic
} elseif (stripos($doc['document_type'], 'SHS') !== false || stripos($doc['document_type'], 'Form 137') !== false) {
    $model_id = $MODELS['SHS'];
    $target_school_db = $applicant['shs_school_name'];
} elseif (stripos($doc['document_type'], 'Form 1') !== false) {
    $model_id = $MODELS['FORM1'];
    $target_school_db = $applicant['shs_school_name']; 
} else {
    echo json_encode(['success' => false, 'message' => 'AI Verification not supported for this document type']);
    exit;
}

// --- 5. PREPARE FILE PATH ---
$base_dir = dirname(__DIR__); 
$file_path_absolute = $base_dir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $doc['file_path']);

if (!file_exists($file_path_absolute)) {
    echo json_encode(['success' => false, 'message' => 'File missing on server: ' . $doc['file_path']]);
    exit;
}

// --- 6. CALL NANONETS API ---
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://app.nanonets.com/api/v2/OCR/Model/$model_id/LabelFile/");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_USERPWD, "$NANONETS_API_KEY:");
curl_setopt($ch, CURLOPT_POST, 1);
$cfile = new CURLFile($file_path_absolute);
curl_setopt($ch, CURLOPT_POSTFIELDS, ['file' => $cfile]);

$result = curl_exec($ch);
if (curl_errno($ch)) {
    echo json_encode(['success' => false, 'message' => 'Curl error: ' . curl_error($ch)]);
    exit;
}
curl_close($ch);

$ocr_data = json_decode($result, true);

// --- 7. PARSE & MATCH DATA ---
$extracted_name = "Not Detected";
$extracted_school = "Not Detected";

if (isset($ocr_data['result'][0]['prediction'])) {
    foreach ($ocr_data['result'][0]['prediction'] as $field) {
        if ($field['label'] === 'FULL_NAME') $extracted_name = $field['ocr_text'];
        if ($field['label'] === 'SCHOOL_NAME') $extracted_school = $field['ocr_text'];
    }
}

// **Name Swapping Logic**
// Build name from separate fields: "Dela Cruz, Juan M." -> Cleaned: "Juan M. Dela Cruz"
$db_name_raw = trim($applicant['last_name'] . ', ' . $applicant['first_name']);
if (!empty($applicant['middle_name'])) {
    $db_name_raw .= ' ' . $applicant['middle_name'];
}
if (!empty($applicant['name_extension'])) {
    $db_name_raw .= ' ' . $applicant['name_extension'];
}
$db_name_clean = $db_name_raw;

if (strpos($db_name_raw, ',') !== false) {
    $parts = explode(',', $db_name_raw);
    if (count($parts) >= 2) {
        $db_name_clean = trim($parts[1]) . " " . trim($parts[0]);
    }
}

// Calculate Similarity (0 to 100)
function normalize($str) { return strtolower(trim(preg_replace('/[^a-zA-Z0-9 ]/', '', $str))); }

similar_text(normalize($db_name_clean), normalize($extracted_name), $name_score);
similar_text(normalize($target_school_db), normalize($extracted_school), $school_score);

// --- 8. UPDATE DATABASE ---
// Set filter columns (#5-9) to "Pass" and save AI analysis results (#11-18)
$checkQ = $conn->prepare("SELECT id FROM ai_document_analysis WHERE application_id = ? AND document_type = ?");
$checkQ->bind_param("is", $app_id, $doc['document_type']);
$checkQ->execute();
$exists = $checkQ->get_result()->fetch_assoc();
$checkQ->close();

$filter_pass = "Pass";
$json_dump = json_encode($ocr_data);
$ocr_status_val = 'completed';

if ($exists) {
    // Update existing record
    $upd = $conn->prepare("UPDATE ai_document_analysis SET 
        filter_blurred = ?, 
        filter_cropped = ?, 
        program_specific_screening = ?, 
        grade_requirements_screening = ?, 
        check_autofill_completeness = ?,
        document_id = ?,
        extracted_name = ?,
        extracted_school = ?,
        name_match_score = ?,
        school_match_score = ?,
        raw_ocr_data = ?,
        ocr_status = ?
        WHERE id = ?");
    $upd->bind_param("sssssisddssi", 
        $filter_pass, $filter_pass, $filter_pass, $filter_pass, $filter_pass,
        $doc_id,
        $extracted_name,
        $extracted_school,
        $name_score,
        $school_score,
        $json_dump,
        $ocr_status_val,
        $exists['id']
    );
    $upd->execute();
    $upd->close();
} else {
    // Insert new record
    $ins = $conn->prepare("INSERT INTO ai_document_analysis (
        application_id, document_type, document_id,
        filter_blurred, filter_cropped, program_specific_screening, 
        grade_requirements_screening, check_autofill_completeness,
        extracted_name, extracted_school, name_match_score, school_match_score,
        raw_ocr_data, ocr_status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $ins->bind_param("isiisssssddss", 
        $app_id, 
        $doc['document_type'],
        $doc_id,
        $filter_pass, $filter_pass, $filter_pass, $filter_pass, $filter_pass,
        $extracted_name,
        $extracted_school,
        $name_score,
        $school_score,
        $json_dump,
        $ocr_status_val
    );
    $ins->execute();
    $ins->close();
}

echo json_encode([
    'success' => true,
    'data' => [
        'extracted_name' => $extracted_name,
        'db_name_rearranged' => $db_name_clean,
        'name_score' => round($name_score, 1),
        'extracted_school' => $extracted_school,
        'db_school' => $target_school_db,
        'school_score' => round($school_score, 1)
    ]
]);
?>