<?php
session_start(); // Start the session at the very beginning
include 'db_connect.php';

$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
    exit;
}

$stmt = $conn->prepare("SELECT id, full_name, password_hash, role FROM staff_accounts WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    // Verify the password against the stored hash
    if (password_verify($password, $user['password_hash'])) {
        // Password is correct. Store user info in the session.
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['logged_in'] = true;

        echo json_encode(['success' => true, 'message' => 'Login successful.']);
    } else {
        // Incorrect password
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid credentials.']);
    }
} else {
    // User not found
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid credentials.']);
}

$stmt->close();
$conn->close();
?>