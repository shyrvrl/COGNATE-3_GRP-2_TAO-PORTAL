<?php

ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');
ob_start();

try {
    session_start();

    // 1. Database Connection
    if (file_exists('../db_connect.php')) include '../db_connect.php';
    elseif (file_exists('db_connect.php')) include 'db_connect.php';
    else throw new Exception("Database connection file not found.");

    // 2. Auth Check
    if (!isset($_SESSION['logged_in'])) {
        http_response_code(403);
        throw new Exception("Unauthorized");
    }

    // 3. Get Parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 5; // Number of notices per page
    $offset = ($page - 1) * $limit;
    
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $filter = isset($_GET['filter']) ? trim($_GET['filter']) : '';

    // 4. Build Query Dynamic Parts
    $whereClauses = [];
    $params = [];
    $types = "";

    // Search Logic (Title or Content)
    if (!empty($search)) {
        $whereClauses[] = "(notice_title LIKE ? OR notice_content LIKE ?)";
        $searchTerm = "%" . $search . "%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "ss";
    }

    // Filter Logic (notice_type: blue/green/red)
    if (!empty($filter) && $filter !== 'all') {
        $whereClauses[] = "notice_type = ?";
        $params[] = $filter;
        $types .= "s";
    }

    // Combine WHERE clauses
    $sqlWhere = "";
    if (count($whereClauses) > 0) {
        $sqlWhere = "WHERE " . implode(" AND ", $whereClauses);
    }

    // 5. Get Total Count (for Pagination)
    $countSql = "SELECT COUNT(*) as total FROM system_notices $sqlWhere";
    $stmt = $conn->prepare($countSql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $totalResult = $stmt->get_result()->fetch_assoc();
    $totalRecords = $totalResult['total'];
    $totalPages = ceil($totalRecords / $limit);
    $stmt->close();

    // 6. Fetch Data
    $dataSql = "SELECT * FROM system_notices $sqlWhere ORDER BY created_at DESC LIMIT ? OFFSET ?";
    
    // Add limit/offset params
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";

    $stmt = $conn->prepare($dataSql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $notices = [];
    while ($row = $result->fetch_assoc()) {
        $notices[] = $row;
    }
    $stmt->close();

    // 7. Return JSON
    ob_end_clean();
    echo json_encode([
        'notices' => $notices,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords
        ]
    ]);

} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => $e->getMessage()]);
}

if (isset($conn)) $conn->close();
?>