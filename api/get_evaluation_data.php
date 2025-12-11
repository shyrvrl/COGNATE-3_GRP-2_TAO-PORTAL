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

    // 1.5. AUTOMATIC AI CHECK
    $docQuery = $conn->prepare("SELECT id, file_path, document_type FROM documents WHERE application_id = ?");
    $docQuery->bind_param("i", $application_id);
    $docQuery->execute();
    $documents = $docQuery->get_result();

    while ($doc = $documents->fetch_assoc()) {
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
        if(isset($row['match_details'])) {
            $row['match_details'] = json_decode($row['match_details'], true);
        }
        $response['ai_results'][$docType] = $row;
    }
    $stmt->close();

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

    // 5. PARSE DATA (ROBUST VERSION)
    $all_data = [];
    $extracted_flat = []; 
    $full_text = ""; 
    $page2_text = ""; 
    
    if (isset($ocr_data['result'])) {
        foreach ($ocr_data['result'] as $res_obj) {
            if (isset($res_obj['prediction'])) {
                foreach ($res_obj['prediction'] as $p) {
                    $page_idx = isset($p['page_no']) ? $p['page_no'] : -1;
                    
                    if (isset($p['ocr_text'])) {
                        $txt = " " . $p['ocr_text'];
                        $full_text .= $txt;
                        if ($page_idx == 1) $page2_text .= $txt;

                        if (isset($p['label'])) {
                            $label = strtoupper($p['label']);
                            $extracted_flat[$label] = $p['ocr_text'];
                            $all_data[$label][] = $p['ocr_text'];
                        }
                    }

                    if ($p['type'] === 'table' && isset($p['cells'])) {
                        foreach ($p['cells'] as $cell) {
                            if (!empty($cell['text'])) {
                                $full_text .= " " . $cell['text'];
                                $cell_page = isset($cell['page']) ? $cell['page'] : $page_idx;
                                if ($cell_page == 1) $page2_text .= " " . $cell['text'];

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
        // Name
        $db_name = $applicant['student_name']; 
        $db_name_clean = $norm($db_name);
        if (strpos($db_name, ',') !== false) { 
            $p = explode(',', $db_name); 
            if(count($p)>=2) $db_name_clean = $norm(trim($p[1]) . " " . trim($p[0])); 
        }
        $ocr_name = $extracted_flat['FULL_NAME'] ?? '';
        similar_text($db_name_clean, $norm($ocr_name), $n_score);
        if($n_score < 70) { $match_details['name'] = "Fail (DB: $db_name vs Doc: $ocr_name)"; $overall_pass = false; } else { $match_details['name'] = 'Pass'; }

        // Completion Year
        $db_year = trim($applicant['jhs_completion_year']);
        $ocr_year_jhs = $extracted_flat['JHS_COMPLETION_YEAR'] ?? '';
        $ocr_year_shs = $extracted_flat['SHS_COMPLETION_YEAR'] ?? '';
        $year_pass = false;
        if (!empty($db_year)) {
            if (strpos($ocr_year_jhs, $db_year) !== false) $year_pass = true;
            elseif (strpos($ocr_year_shs, $db_year) !== false) $year_pass = true;
            elseif (!empty($full_text) && strpos($full_text, $db_year) !== false) $year_pass = true;
        }
        if ($year_pass) $match_details['completion_year'] = "Pass";
        else { $match_details['completion_year'] = "Fail (DB: $db_year vs Doc: $ocr_year_jhs)"; $overall_pass = false; }

        // Grades (Tolerance 1.1 + Fallback)
        $check_jhs_fallback = function($label_key, $db_val, $ocr_key) use ($extracted_flat, $page2_text, &$match_details, &$overall_pass) {
            if (empty($db_val)) { $match_details[$label_key] = "N/A"; return; }
            $ocr_val = $extracted_flat[$ocr_key] ?? '';
            $pass = false;
            
            if (is_numeric($db_val) && is_numeric($ocr_val)) {
                if (abs(floatval($db_val) - floatval($ocr_val)) < 1.1) $pass = true;
            } elseif ($db_val == $ocr_val) $pass = true;

            if (!$pass) {
                $search_val = intval($db_val); 
                if (!empty($page2_text) && preg_match("/\b" . $search_val . "\b/", $page2_text)) {
                    $pass = true;
                    $match_details[$label_key] = "Pass (Found in Page 2)";
                }
            } else { $match_details[$label_key] = "Pass"; }

            if (!$pass) {
                $match_details[$label_key] = "Fail (DB: $db_val vs Doc: $ocr_val)";
                $overall_pass = false;
            }
        };
        $check_jhs_fallback('math_grade', $applicant['jhs_math_grade'], '10_MATH');
        $check_jhs_fallback('science_grade', $applicant['jhs_science_grade'], '10_SCI');
        $check_jhs_fallback('english_grade', $applicant['jhs_english_grade'], '10_ENG');
    } 
    // --- SHS LOGIC ---
    else {
        // Name
        $db_name = $applicant['student_name']; 
        if (strpos($db_name, ',') !== false) { $p = explode(',', $db_name); if(count($p)>=2) $db_name = trim($p[1]) . " " . trim($p[0]); }
        similar_text($norm($db_name), $norm($extracted_flat['FULL_NAME'] ?? ''), $n_score);
        if($n_score < 70) { $match_details['name'] = "Fail ($db_name vs Doc: " . ($extracted_flat['FULL_NAME']??'') . ")"; $overall_pass = false; } else { $match_details['name'] = 'Pass'; }
        
        $db_strand_only = $applicant['shs_strand']; // e.g., "STEM" or "ABM"
        $db_full_track = $applicant['shs_track'] . ' ' . $applicant['shs_strand'];
        
        $clean_db_strand = $norm($db_strand_only);
        $clean_db_full = $norm($db_full_track);
        $clean_ocr_strand = $norm($extracted_flat['TRACK_STRAND'] ?? '');
        
        $pass_strand = false;

        // Check A: Similarity
        similar_text($clean_db_full, $clean_ocr_strand, $t_score);
        if ($t_score >= 50) $pass_strand = true;

        // Check B: Substring (Does OCR contain the specific strand code? e.g. "stem")
        if (!$pass_strand && !empty($clean_db_strand)) {
            if (strpos($clean_ocr_strand, $clean_db_strand) !== false) $pass_strand = true;
        }

        // Check C: Acronym Mapping (If DB is "ABM" but Doc is "Accountancy...", map it)
        if (!$pass_strand) {
            $strand_map = [
                'stem'  => ['science', 'engineering', 'technology', 'math'],
                'abm'   => ['accountancy', 'business', 'management'],
                'humss' => ['humanities', 'social'],
                'gas'   => ['general', 'academic'],
                'tvl'   => ['technical', 'vocational']
            ];

            if (isset($strand_map[$clean_db_strand])) {
                // Check if ANY of the keywords for this strand exist in the OCR text
                $keywords = $strand_map[$clean_db_strand];
                foreach ($keywords as $kw) {
                    if (strpos($clean_ocr_strand, $kw) !== false) {
                        $pass_strand = true; 
                        break; 
                    }
                }
            }
        }

        if(!$pass_strand) { 
            $match_details['track_strand'] = "Fail (DB: $db_full_track vs Doc: " . ($extracted_flat['TRACK_STRAND']??'Missing') . ")"; 
            $overall_pass = false; 
        } else { 
            $match_details['track_strand'] = 'Pass'; 
        }
        // -------------------------------------------------------------

        // School
        similar_text($norm($applicant['shs_school_name']), $norm($extracted_flat['SCHOOL_NAME'] ?? ''), $s_score);
        if($s_score < 70) { $match_details['school_name'] = "Fail (DB: {$applicant['shs_school_name']} vs Doc: " . ($extracted_flat['SCHOOL_NAME']??'') . ")"; $overall_pass = false; } else { $match_details['school_name'] = 'Pass'; }

        // Subjects
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
            } else { $match_details[$db_label] = "Pass"; }

            if (!$pass) {
                $found_str = !empty($candidates) ? implode(", ", array_unique($candidates)) : "None";
                $match_details[$db_label] = "Fail (DB: $db_grade vs Doc: $found_str)";
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
    $final_status = $overall_pass ? 'Pass' : 'Fail';
    $json_details = json_encode($match_details);
    $json_dump = json_encode($ocr_data);

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
        $dummy = ''; $zero = 0;
        $ins = $conn->prepare("INSERT INTO ai_document_analysis (application_id, document_id, document_type, match_details, raw_ocr_data, data_consistency_check, ocr_status, extracted_name, extracted_school, name_match_score, school_match_score) VALUES (?, ?, ?, ?, ?, ?, 'completed', ?, ?, ?, ?)");
        $ins->bind_param("iissssssdd", $app_id, $doc['id'], $doc['document_type'], $json_details, $json_dump, $final_status, $dummy, $dummy, $zero, $zero);
        $ins->execute();
        $ins->close();
    }
}
?>
