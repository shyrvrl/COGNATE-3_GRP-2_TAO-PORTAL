<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

$data = json_decode(file_get_contents("php://input"), true);
$token = $data['token'] ?? '';
$newPassword = $data['new_password'] ?? '';

if (empty($token) || empty($newPassword)) {
    echo json_encode(['success' => false, 'message' => 'Missing token or password.']);
    exit;
}

if (strlen($newPassword) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
    exit;
}

$tokenHash = hash('sha256', $token);

// 1. Find user with valid token and expiry time in future
$sql = "SELECT id FROM staff_accounts WHERE reset_token_hash = ? AND reset_token_expires_at > NOW()";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $tokenHash);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired token. Please request a new reset link.']);
    exit;
}

$user = $result->fetch_assoc();
$userId = $user['id'];

// 2. Hash new password
$newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

// 3. Update password AND clear the token (so it can't be used again)
$updateSql = "UPDATE staff_accounts SET password_hash = ?, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE id = ?";
$updateStmt = $conn->prepare($updateSql);
$updateStmt->bind_param("si", $newPasswordHash, $userId);

if ($updateStmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Password updated.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
?>