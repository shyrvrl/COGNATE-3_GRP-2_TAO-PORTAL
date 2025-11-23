<?php
include 'db_connect.php';

$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? '';

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

// Check if the user exists
$stmt = $conn->prepare("SELECT id FROM staff_accounts WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // --- THIS IS WHERE THE COMPLEX LOGIC WOULD GO ---
    // 1. Generate a unique, secure, and expiring token (e.g., using random_bytes).
    // 2. Store this token in a new database table (e.g., `password_resets`) along with the user's ID and an expiry timestamp.
    // 3. Construct a reset link (e.g., `https://your-site.com/ResetPassword.php?token=...`).
    // 4. Use an email library (like PHPMailer) to send the link to the user's email.
    
    // For now, we will just SIMULATE success.
    echo json_encode([
        'success' => true,
        'message' => 'If an account with that email exists, a password reset link has been sent.'
    ]);
} else {
    // We send the SAME success message even if the user doesn't exist.
    // This is a security measure to prevent "user enumeration" attacks.
    echo json_encode([
        'success' => true,
        'message' => 'If an account with that email exists, a password reset link has been sent.'
    ]);
}

$stmt->close();
$conn->close();
?>