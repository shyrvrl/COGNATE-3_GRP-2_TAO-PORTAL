<?php
include 'db_connect.php';

// 1. Get and Validate the Document ID
// We use intval to make sure it's a number, preventing SQL injection.
$document_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($document_id <= 0) {
    http_response_code(400); // Bad Request
    echo "Invalid Document ID.";
    exit;
}

// 2. Fetch the File Path from the Database
$stmt = $conn->prepare("SELECT file_path FROM documents WHERE id = ?");
$stmt->bind_param("i", $document_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404); // Not Found
    echo "Document not found in the database.";
    exit;
}

$row = $result->fetch_assoc();
$filePath = $row['file_path'];

// IMPORTANT: This file path is the one stored by the Student Portal.
// For this example to work, you MUST create a folder structure and a dummy file.
// For example, create a folder 'uploads' inside 'TAO_portal',
// and place a file named 'dummy.pdf' inside it.
// Then, update your database record's file_path to 'uploads/dummy.pdf'.
$serverFilePath = $_SERVER['DOCUMENT_ROOT'] . '/TAO_portal/' . $filePath;


// 3. Check if the file exists on the server
if (!file_exists($serverFilePath)) {
    http_response_code(404); // Not Found
    echo "File not found on the server. Path: " . htmlspecialchars($serverFilePath);
    exit;
}

// 4. Determine MIME type and serve the file
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $serverFilePath);
finfo_close($finfo);

// Set headers
header('Content-Type: ' . $mime_type);
header('Content-Length: ' . filesize($serverFilePath));

header("Permissions-Policy: fullscreen=(self)");

// This function reads the file and writes it to the output buffer
readfile($serverFilePath);

$stmt->close();
$conn->close();
exit;
?>