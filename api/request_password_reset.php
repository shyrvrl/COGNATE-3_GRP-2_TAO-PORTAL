<?php

// Define your SMTP credentials here. 
// When you move to a new laptop, this file goes with you, keeping it working.
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'testing.admission.batstateu@gmail.com'); // CHANGE THIS to the dedicated system email
define('SMTP_PASS', 'upkt zsfh akva mhnk');          // CHANGE THIS to the 16-char App Password
define('SMTP_PORT', 587);
define('FROM_NAME', 'TAO Portal System');            // The name people see (Professional)
define('FROM_EMAIL', 'noreply@admission.edu');       // We mask the sender so they can't reply

header('Content-Type: application/json');
require_once 'db_connect.php';

// Load PHPMailer classes (Adjust paths if your folder structure is different)
require 'PhpMailer/PhpMailer/Exception.php';
require 'PhpMailer/PhpMailer/PHPMailer.php';
require 'PhpMailer/PhpMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$data = json_decode(file_get_contents("php://input"), true);
$email = $data['email'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
    exit;
}

// 1. Check if email exists
$stmt = $conn->prepare("SELECT id, full_name FROM staff_accounts WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    
    // 2. Generate secure token
    $token = bin2hex(random_bytes(16)); // 32 characters
    $tokenHash = hash('sha256', $token); // Store the HASH in DB, not raw token
    $expiry = date("Y-m-d H:i:s", time() + 60 * 30); // Expires in 30 minutes

    // 3. Save to DB
    $updateStmt = $conn->prepare("UPDATE staff_accounts SET reset_token_hash = ?, reset_token_expires_at = ? WHERE id = ?");
    $updateStmt->bind_param("ssi", $tokenHash, $expiry, $user['id']);
    $updateStmt->execute();

    // 4. Send Email via PHPMailer
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        // Recipients
        // We set the "From" to the Gmail account (required by Google)
        // But we set "FromName" to look professional
        $mail->setFrom(SMTP_USER, FROM_NAME);
        $mail->addAddress($email, $user['full_name']);
        
        // NO REPLY configuration
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
                    <p>We received a request to reset your password for the TAO Portal.</p>
                    <p>Click the button below to reset it (valid for 30 minutes):</p>
                    <a href='{$resetLink}' style='background-color: #0086C9; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Reset Password</a>
                    <p style='margin-top: 20px; font-size: 12px; color: #888;'>If you did not request this, please ignore this email.</p>
                </div>
            </div>
        ";
        $mail->AltBody = "Click this link to reset your password: $resetLink";

        $mail->send();
    } catch (Exception $e) {
        // Log error but don't show specific mailer error to user for security
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
    }
}

// ALWAYS return success message to prevent Email Enumeration attacks
// (Don't tell hackers if the email exists or not)
echo json_encode(['success' => true, 'message' => 'If that email exists, a reset link has been sent.']);
?>