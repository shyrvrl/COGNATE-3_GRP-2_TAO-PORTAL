<?php
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

    // 1. Get Applicant Details
    $query = "SELECT CONCAT(last_name, ', ', first_name, 
               CASE WHEN middle_name IS NOT NULL AND middle_name != '' THEN CONCAT(' ', middle_name) ELSE '' END,
               CASE WHEN name_extension IS NOT NULL AND name_extension != '' THEN CONCAT(' ', name_extension) ELSE '' END) AS student_name, 
                     application_no, choice1_program, 
                     birthdate, sex, 
                     jhs_math_grade, jhs_science_grade, jhs_english_grade, jhs_completion_year,
                     shs_school_name, shs_track, shs_strand,
                     shs_sem1_math_grade, shs_sem1_science_grade, shs_sem1_english_grade,
                     shs_sem2_math_grade, shs_sem2_science_grade, shs_sem2_english_grade,
                     first_name, middle_name, last_name, name_extension
              FROM applications WHERE id = ?";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $application_id);
    $stmt->execute();
    $response['details'] = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$response['details']) throw new Exception("Applicant not found");

    // 1.5. AUTOMATIC AI CHECK
    $docQuery = $conn->prepare("SELECT id, file_path, document_type FROM documents WHERE application_id = ?");
    $docQuery->bind_param("i", $application_id);
    $docQuery->execute();
    $documents = $docQuery->get_result();

    while ($doc = $documents->fetch_assoc()) {
        if (stripos($doc['document_type'], 'Form 137') !== false) {
            $checkStmt = $conn->prepare("SELECT id, ocr_status FROM ai_document_analysis WHERE application_id = ? AND document_type = ?");
            $checkStmt->bind_param("is", $application_id, $doc['document_type']);
            $checkStmt->execute();
            $analysis = $checkStmt->get_result()->fetch_assoc();
            
            // Run AI if not yet run or if status is pending/failed
            if (!$analysis || $analysis['ocr_status'] === 'pending' || $analysis['ocr_status'] === 'failed' || $analysis['ocr_status'] === NULL) {
                run_ai_analysis_internal($conn, $application_id, $doc, $response['details']);
            }
            $checkStmt->close();
        }
    }
    $docQuery->close();

    // 2. Get AI Analysis Results
    $query = "SELECT document_type, filter_blurred, filter_cropped,
                     program_specific_screening, grade_requirements_screening, check_autofill_completeness,
                     extracted_name, extracted_school, name_match_score, school_match_score,
                     raw_ocr_data, ocr_status, data_consistency_check, match_details
              FROM ai_document_analysis 
              WHERE application_id = ?";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $application_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $docType = $row['document_type'];
        if(isset($row['match_details'])) {
            $row['match_details'] = json_decode($row['match_details'], true);
        }
        
        // Force all AI results to "Pass" for Certificate of Enrollment and Grades Form 1
        if ($docType === 'Certificate of Enrollment' || $docType === 'Grades Form 1') {
            $row['filter_blurred'] = 'Pass';
            $row['filter_cropped'] = 'Pass';
            $row['program_specific_screening'] = 'Pass';
            $row['grade_requirements_screening'] = 'Pass';
            $row['check_autofill_completeness'] = 'Pass';
        }
        
        // Force blurred file detection to "Pass" for JHS Form 137 and SHS Form 137
        if ($docType === 'JHS Form 137' || $docType === 'SHS Form 137') {
            $row['filter_blurred'] = 'Pass';
        }
        
        $response['ai_results'][$docType] = $row;
    }
    $stmt->close();
    
    // Ensure "Pass" entries exist for Certificate of Enrollment and Grades Form 1 even if no AI analysis record exists
    $requiredDocs = ['Certificate of Enrollment', 'Grades Form 1'];
    foreach ($requiredDocs as $docType) {
        if (!isset($response['ai_results'][$docType])) {
            $response['ai_results'][$docType] = [
                'document_type' => $docType,
                'filter_blurred' => 'Pass',
                'filter_cropped' => 'Pass',
                'program_specific_screening' => 'Pass',
                'grade_requirements_screening' => 'Pass',
                'check_autofill_completeness' => 'Pass',
                'extracted_name' => null,
                'extracted_school' => null,
                'name_match_score' => 0,
                'school_match_score' => 0,
                'raw_ocr_data' => null,
                'ocr_status' => null,
                'data_consistency_check' => null,
                'match_details' => null
            ];
        } else {
            // Double-check to ensure all fields are "Pass" (in case they were missing)
            $response['ai_results'][$docType]['filter_blurred'] = 'Pass';
            $response['ai_results'][$docType]['filter_cropped'] = 'Pass';
            $response['ai_results'][$docType]['program_specific_screening'] = 'Pass';
            $response['ai_results'][$docType]['grade_requirements_screening'] = 'Pass';
            $response['ai_results'][$docType]['check_autofill_completeness'] = 'Pass';
        }
    }
    
    // Ensure blurred file detection is "Pass" for JHS Form 137 and SHS Form 137
    $form137Docs = ['JHS Form 137', 'SHS Form 137'];
    foreach ($form137Docs as $docType) {
        if (isset($response['ai_results'][$docType])) {
            $response['ai_results'][$docType]['filter_blurred'] = 'Pass';
        }
    }

    // 3. Get Human Checklist
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

function run_ai_analysis_internal($conn, $app_id, $doc, $applicant) {
    
    // 1. CONFIGURATION
    $NANONETS_API_KEY = "0a5fdcd8-d44f-11f0-8582-ba97bbc8169d"; 
    $MODELS = [
        'JHS'   => "6aaa2e00-a46d-43b6-80a4-1501f0a21066", 
        'SHS'   => "a37018ed-3f0b-475a-a860-3f33607652e7", 
        'FORM1' => "77480625-8c5e-4381-a880-bbcbde38ef93"  
    ];

    // 2. MODEL SELECTION
    $is_jhs = stripos($doc['document_type'], 'JHS') !== false;
    $model_id = $is_jhs ? $MODELS['JHS'] : $MODELS['SHS'];
    if (stripos($doc['document_type'], 'Form 1') !== false) $model_id = $MODELS['FORM1'];

    // 3. FILE PATH
    $base_dir = dirname(__DIR__); 
    $file_path_absolute = $base_dir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $doc['file_path']);
    if (!file_exists($file_path_absolute)) return;

    // 4. API CALL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://app.nanonets.com/api/v2/OCR/Model/$model_id/LabelFile/?async=false");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERPWD, "$NANONETS_API_KEY:");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ['file' => new CURLFile($file_path_absolute)]);
    $result = curl_exec($ch);
    curl_close($ch);
    
    $ocr_data = json_decode($result, true);

    // 5. PARSE DATA (UPDATED: Iterate through ALL results, not just index 0)
    $all_data = [];
    $extracted_flat = []; 
    $full_text = ""; 
    $page2_text = ""; 
    
    if (isset($ocr_data['result'])) {
        // Iterate through all result objects (some APIs separate pages or models)
        foreach ($ocr_data['result'] as $res_obj) {
            
            if (isset($res_obj['prediction'])) {
                foreach ($res_obj['prediction'] as $p) {
                    
                    // Identify Page Number (0-based)
                    $page_idx = isset($p['page_no']) ? $p['page_no'] : -1;
                    
                    // --- HANDLE FIELDS ---
                    if (isset($p['ocr_text'])) {
                        $txt = " " . $p['ocr_text'];
                        $full_text .= $txt;
                        if ($page_idx == 1) $page2_text .= $txt; // Page 2 is index 1

                        if (isset($p['label'])) {
                            $label = strtoupper($p['label']);
                            $extracted_flat[$label] = $p['ocr_text'];
                            $all_data[$label][] = $p['ocr_text'];
                        }
                    }

                    // --- HANDLE TABLES ---
                    if ($p['type'] === 'table' && isset($p['cells'])) {
                        foreach ($p['cells'] as $cell) {
                            if (!empty($cell['text'])) {
                                $full_text .= " " . $cell['text'];
                                
                                // Table cells sometimes inherit page_no or have their own
                                $cell_page = isset($cell['page']) ? $cell['page'] : $page_idx;
                                if ($cell_page == 1) {
                                    $page2_text .= " " . $cell['text'];
                                }

                                if(!empty($cell['label'])) {
                                    $label = strtoupper($cell['label']);
                                    $all_data[$label][] = $cell['text'];
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    // 6. VERIFICATION LOGIC
    $match_details = [];
    $overall_pass = true;
    
    $norm = function($str) { return strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $str))); };
    
    $check = function($label, $db_val, $ocr_val, $is_fuzzy=false) use (&$match_details, &$overall_pass, $norm) {
        $pass = false;
        if (empty($db_val)) { $match_details[$label] = "N/A (No Data)"; return true; }
        $clean_db = $norm($db_val);
        $clean_ocr = $norm($ocr_val);
        if ($is_fuzzy) {
            similar_text($clean_db, $clean_ocr, $score);
            if ($score > 75) $pass = true;
        } else {
            if ($clean_db == $clean_ocr) $pass = true;
            if (is_numeric($db_val) && is_numeric($ocr_val)) {
                if (abs(floatval($db_val) - floatval($ocr_val)) < 0.1) $pass = true;
            }
        }
        $val_disp = empty($ocr_val) ? 'Missing' : $ocr_val;
        $match_details[$label] = $pass ? "Pass" : "Fail (DB: $db_val vs Doc: $val_disp)";
        if (!$pass) $overall_pass = false;
        return $pass;
    };

    // --- JHS LOGIC ---
    if ($is_jhs) {
        // 1. Name Check
        // Build name from separate fields
        $db_name = trim($applicant['last_name'] . ', ' . $applicant['first_name']);
        if (!empty($applicant['middle_name'])) {
            $db_name .= ' ' . $applicant['middle_name'];
        }
        if (!empty($applicant['name_extension'])) {
            $db_name .= ' ' . $applicant['name_extension'];
        }
        $db_name_clean = $norm($db_name);
        if (strpos($db_name, ',') !== false) { 
            $p = explode(',', $db_name); 
            if(count($p)>=2) $db_name_clean = $norm(trim($p[1]) . " " . trim($p[0])); 
        }
        
        $ocr_name = $extracted_flat['FULL_NAME'] ?? '';
        similar_text($db_name_clean, $norm($ocr_name), $n_score);
        
        if($n_score < 70) { 
            $match_details['name'] = "Fail (DB: $db_name vs Doc: " . ($ocr_name ?: 'Missing') . ")"; 
            $overall_pass = false; 
        } else { 
            $match_details['name'] = 'Pass'; 
        }

        // 2. Completion Year
        $db_year = trim($applicant['jhs_completion_year']);
        $ocr_year_jhs = $extracted_flat['JHS_COMPLETION_YEAR'] ?? '';
        $ocr_year_shs = $extracted_flat['SHS_COMPLETION_YEAR'] ?? '';
        
        $year_pass = false;
        $found_year = $ocr_year_jhs ?: $ocr_year_shs;
        
        if (!empty($db_year)) {
            if (strpos($ocr_year_jhs, $db_year) !== false) { $year_pass = true; $found_year = $ocr_year_jhs; }
            elseif (strpos($ocr_year_shs, $db_year) !== false) { $year_pass = true; $found_year = $ocr_year_shs; }
            elseif (!empty($full_text) && strpos($full_text, $db_year) !== false) { $year_pass = true; $found_year = "Found in Doc"; }
        }

        if ($year_pass) {
             $match_details['completion_year'] = "Pass";
        } else {
             $match_details['completion_year'] = "Fail (DB: $db_year vs Doc: $found_year)";
             $overall_pass = false;
        }

        // 3. Grades (Tolerance 1.1 + Robust Fallback)
        $check_jhs_fallback = function($label_key, $db_val, $ocr_key) use ($extracted_flat, $page2_text, &$match_details, &$overall_pass) {
            if (empty($db_val)) { $match_details[$label_key] = "N/A"; return; }
            
            $ocr_val = $extracted_flat[$ocr_key] ?? '';
            $pass = false;

            // Step A: Check Label (Tolerance 1.1 allows 86 to match 87)
            if (is_numeric($db_val) && is_numeric($ocr_val)) {
                if (abs(floatval($db_val) - floatval($ocr_val)) < 1.1) $pass = true;
            } elseif ($db_val == $ocr_val) {
                $pass = true;
            }

            // Step B: Fallback (Search text on Page 2)
            if (!$pass) {
                $search_val = intval($db_val); 
                if (!empty($page2_text) && preg_match("/\b" . $search_val . "\b/", $page2_text)) {
                    $pass = true;
                    $match_details[$label_key] = "Pass (Found in Page 2)";
                }
            } else {
                if($pass) $match_details[$label_key] = "Pass";
            }

            if (!$pass) {
                $match_details[$label_key] = "Fail (DB: $db_val vs Doc: " . ($ocr_val ?: 'Missing') . ")";
                $overall_pass = false;
            }
        };

        $check_jhs_fallback('math_grade', $applicant['jhs_math_grade'], '10_MATH');
        $check_jhs_fallback('science_grade', $applicant['jhs_science_grade'], '10_SCI');
        $check_jhs_fallback('english_grade', $applicant['jhs_english_grade'], '10_ENG');
    } 
    // --- SHS LOGIC ---
    else {
        // Build name from separate fields
        $db_name = trim($applicant['last_name'] . ', ' . $applicant['first_name']);
        if (!empty($applicant['middle_name'])) {
            $db_name .= ' ' . $applicant['middle_name'];
        }
        if (!empty($applicant['name_extension'])) {
            $db_name .= ' ' . $applicant['name_extension'];
        }
        if (strpos($db_name, ',') !== false) { $p = explode(',', $db_name); if(count($p)>=2) $db_name = trim($p[1]) . " " . trim($p[0]); }
        similar_text($norm($db_name), $norm($extracted_flat['FULL_NAME'] ?? ''), $n_score);
        if($n_score < 70) { $match_details['name'] = "Fail ($db_name vs " . ($extracted_flat['FULL_NAME']??'Missing') . ")"; $overall_pass = false; } else { $match_details['name'] = 'Pass'; }
        
        similar_text($norm($applicant['shs_track'] . ' ' . $applicant['shs_strand']), $norm($extracted_flat['TRACK_STRAND'] ?? ''), $t_score);
        if($t_score < 50) { $match_details['track_strand'] = "Fail (DB: {$applicant['shs_track']} {$applicant['shs_strand']} vs Doc: " . ($extracted_flat['TRACK_STRAND']??'Missing') . ")"; $overall_pass = false; } else { $match_details['track_strand'] = 'Pass'; }

        similar_text($norm($applicant['shs_school_name']), $norm($extracted_flat['SCHOOL_NAME'] ?? ''), $s_score);
        if($s_score < 70) { $match_details['school_name'] = "Fail (DB: {$applicant['shs_school_name']} vs Doc: " . ($extracted_flat['SCHOOL_NAME']??'Missing') . ")"; $overall_pass = false; } else { $match_details['school_name'] = 'Pass'; }

        $check_subject_mapped = function($db_label, $db_grade, $keywords) use ($all_data, $full_text, &$match_details, &$overall_pass) {
            if (empty($db_grade)) return;
            $candidates = [];
            foreach ($all_data as $label_key => $values) {
                foreach ($keywords as $kw) {
                    if (strpos($label_key, $kw) !== false) {
                        foreach($values as $v) {
                            $clean_v = preg_replace('/[^0-9.]/', '', $v);
                            if (is_numeric($clean_v)) $candidates[] = $clean_v;
                        }
                    }
                }
            }
            $pass = false;
            foreach ($candidates as $ocr_grade) {
                if (abs(floatval($db_grade) - floatval($ocr_grade)) < 1.0) { $pass = true; break; }
            }
            if (!$pass) {
                $g_int = intval($db_grade);
                if (preg_match("/\b" . $g_int . "\b/", $full_text)) {
                    $pass = true;
                    $match_details[$db_label] = "Pass (Found in text)";
                }
            } else {
                $match_details[$db_label] = "Pass";
            }
            if (!$pass) {
                $found_str = !empty($candidates) ? implode(", ", array_unique($candidates)) : "None";
                $match_details[$db_label] = "Fail (DB: $db_grade vs Found: $found_str)";
                $overall_pass = false;
            }
        };

        $math_keys = ['MATH', 'CALCULUS', 'STAT', 'ALGEBRA', 'GENMATH', 'STATPROB', 'BASICCAL', 'PRECAL', 'BUSSMATH'];
        $sci_keys = ['SCIENCE', 'BIO', 'CHEM', 'PHYSICS', 'EARTH', 'DRRR', 'GENBIO', 'GEN_BIO', 'GENCHEM', 'GENPHYS', 'PHYSCI', 'EARTHSCI'];
        $eng_keys = ['ENGLISH', 'ORAL', 'READ', 'WRITE', 'LIT', 'COM', 'ORALCOM', 'READ_WRITE', 'ENGAP'];

        $check_subject_mapped('Math', $applicant['shs_sem1_math_grade'], $math_keys);
        $check_subject_mapped('Science', $applicant['shs_sem1_science_grade'], $sci_keys);
        $check_subject_mapped('English', $applicant['shs_sem1_english_grade'], $eng_keys);
        if (!empty($applicant['shs_sem2_math_grade'])) $check_subject_mapped('Math_Sem2', $applicant['shs_sem2_math_grade'], $math_keys);
        if (!empty($applicant['shs_sem2_science_grade'])) $check_subject_mapped('Science_Sem2', $applicant['shs_sem2_science_grade'], $sci_keys);
        if (!empty($applicant['shs_sem2_english_grade'])) $check_subject_mapped('English_Sem2', $applicant['shs_sem2_english_grade'], $eng_keys);
    }

    // 7. SAVE TO DB
    // Set filter columns (#5-9) to "Pass" and save AI analysis results (#11-18)
    $final_status = $overall_pass ? 'Pass' : 'Fail';
    $json_details = json_encode($match_details);
    $json_dump = json_encode($ocr_data);
    
    // Extract name and school from OCR if available
    $extracted_name = $extracted_flat['FULL_NAME'] ?? '';
    $extracted_school = $extracted_flat['SCHOOL_NAME'] ?? '';
    $name_score = 0;
    $school_score = 0;
    
    // Calculate name match score if we have the name
    if (!empty($extracted_name) && !empty($applicant['last_name'])) {
        $norm = function($str) { return strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $str))); };
        $db_name = trim($applicant['last_name'] . ', ' . $applicant['first_name']);
        if (!empty($applicant['middle_name'])) {
            $db_name .= ' ' . $applicant['middle_name'];
        }
        if (!empty($applicant['name_extension'])) {
            $db_name .= ' ' . $applicant['name_extension'];
        }
        $db_name_clean = $db_name;
        if (strpos($db_name, ',') !== false) {
            $p = explode(',', $db_name);
            if (count($p) >= 2) {
                $db_name_clean = trim($p[1]) . " " . trim($p[0]);
            }
        }
        similar_text($norm($db_name_clean), $norm($extracted_name), $name_score);
    }
    
    $checkQ = $conn->prepare("SELECT id FROM ai_document_analysis WHERE application_id = ? AND document_type = ?");
    $checkQ->bind_param("is", $app_id, $doc['document_type']);
    $checkQ->execute();
    $exists = $checkQ->get_result()->fetch_assoc();
    $checkQ->close();

    $filter_pass = "Pass";
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
            ocr_status = ?,
            data_consistency_check = ?,
            match_details = ?
            WHERE id = ?");
        $ocr_status_completed = 'completed';
        $upd->bind_param("sssssisddsssssi", 
            $filter_pass, $filter_pass, $filter_pass, $filter_pass, $filter_pass,
            $doc['id'],
            $extracted_name,
            $extracted_school,
            $name_score,
            $school_score,
            $json_dump,
            $ocr_status_completed,
            $final_status,
            $json_details,
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
            raw_ocr_data, ocr_status, data_consistency_check, match_details
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $ocr_status_completed = 'completed';
        $ins->bind_param("isiisssssddsssss", 
            $app_id, 
            $doc['document_type'],
            $doc['id'],
            $filter_pass, $filter_pass, $filter_pass, $filter_pass, $filter_pass,
            $extracted_name,
            $extracted_school,
            $name_score,
            $school_score,
            $json_dump,
            $ocr_status_completed,
            $final_status,
            $json_details
        );
        $ins->execute();
        $ins->close();
    }
}
?>