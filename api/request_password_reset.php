<?php
// api/request_password_reset.php

// 1. Prevent HTML errors
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');

// --- CONFIGURATION ---
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'testing.admission.batstateu@gmail.com'); 
define('SMTP_PASS', 'upkt zsfh akva mhnk'); 
define('SMTP_PORT', 587);
define('FROM_NAME', 'TAO Portal System');           
define('FROM_EMAIL', 'noreply@admission.edu');      

// 2. Database Connection
if (file_exists('../db_connect.php')) require_once '../db_connect.php';
elseif (file_exists('db_connect.php')) require_once 'db_connect.php';
else {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

// 3. Load PHPMailer
if (file_exists('PhpMailer/PhpMailer/PHPMailer.php')) {
    require 'PhpMailer/PhpMailer/Exception.php';
    require 'PhpMailer/PhpMailer/PHPMailer.php';
    require 'PhpMailer/PhpMailer/SMTP.php';
} else {
    require '../PhpMailer/PhpMailer/Exception.php';
    require '../PhpMailer/PhpMailer/PHPMailer.php';
    require '../PhpMailer/PhpMailer/SMTP.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 4. Get Input
$data = json_decode(file_get_contents("php://input"), true);
$email = $data['email'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
    exit;
}

try {
    // 5. Check if email exists
    $stmt = $conn->prepare("SELECT id, full_name FROM staff_accounts WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // 6. Generate Token
        $token = bin2hex(random_bytes(16)); 
        $tokenHash = hash('sha256', $token); 
        $expiry = date("Y-m-d H:i:s", time() + 60 * 30); // 30 mins

        // 7. Save to DB
        $updateStmt = $conn->prepare("UPDATE staff_accounts SET reset_token_hash = ?, reset_token_expires_at = ? WHERE id = ?");
        $updateStmt->bind_param("ssi", $tokenHash, $expiry, $user['id']);
        
        if (!$updateStmt->execute()) {
            throw new Exception("Failed to update database token.");
        }

        // 8. Send Email
        $mail = new PHPMailer(true);

        // --- FIXED: ADD THESE OPTIONS TO BYPASS SSL CERTIFICATE CHECK ---
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        // -------------------------------------------------------------

        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        // Recipients
        $mail->setFrom(SMTP_USER, FROM_NAME);
        $mail->addAddress($email, $user['full_name']);
        $mail->addReplyTo('no-reply@university.edu', 'No Reply');

        // Content
        $resetLink = "http://localhost/TAO_Portal/ResetPassword.html?token=" . $token;

        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request';
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; padding: 20px; background-color: #f4f4f4;'>
                <div style='background-color: white; padding: 20px; border-radius: 5px;'>
                    <h2 style='color: #333;'>Password Reset</h2>
                    <p>Hello {$user['full_name']},</p>
                    <p>We received a request to reset your password.</p>
                    <p>Click the button below to proceed (valid for 30 minutes):</p>
                    <a href='{$resetLink}' style='background-color: #0086C9; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Reset Password</a>
                </div>
            </div>
        ";
        $mail->AltBody = "Reset Link: $resetLink";

        $mail->send();

        echo json_encode([
            'success' => true, 
            'message' => 'Account found! A reset link has been sent to ' . $email
        ]);

    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Error: This email address is not registered in our system.'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    // Returning the actual error for debugging
    echo json_encode([
        'success' => false, 
        'message' => 'System Error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>