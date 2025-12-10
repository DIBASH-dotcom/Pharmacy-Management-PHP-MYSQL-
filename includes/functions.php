<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// PHPMailer manual inclusion
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendEmail($to, $subject, $body, $altBody = '')
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->SMTPDebug = 0;        // Disable debug output (set to 2 for debug)
        $mail->Debugoutput = 'html';
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = ''; // Your Gmail username
        $mail->Password = '';          // Your Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('desktop555555@gmail.com', 'Pharmacy System');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = $altBody ?: strip_tags($body);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Email Error: ' . $mail->ErrorInfo);
        return 'Mailer Error: ' . $mail->ErrorInfo;  // Return error message for display or logging
    }
}

// Function to send staff credentials email
function sendStaffCredentialsEmail($toEmail, $fullName, $username, $password)
{
    $subject = "Your Pharmacy System Staff Account Credentials";

    $body = "
        <h2>Welcome to the Pharmacy System</h2>
        <p>Dear {$fullName},</p>
        <p>Your staff account has been created. Below are your login credentials:</p>
        <ul>
            <li><strong>Username:</strong> {$username}</li>
            <li><strong>Password:</strong> {$password}</li>
        </ul>
        <p>Please log in and change your password after your first login.</p>
        <p>Login here: <a href='http://localhost/PHARMANCY/login.php'>Pharmacy Login</a></p>
        <p>Regards,<br>Pharmacy Admin</p>
    ";

    $altBody = "Dear {$fullName},\n\nYour staff account has been created.\nUsername: {$username}\nPassword: {$password}\nPlease login and change your password after first login.\nLogin: http://localhost/PHARMANCY/login.php";

    return sendEmail($toEmail, $subject, $body, $altBody);
}
?>

