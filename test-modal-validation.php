<?php
require '../vendor/autoload.php'; // Adjust path if necessary
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendEmail($recipient, $subject, $body) {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'jovinhampton@gmail.com'; 
    $mail->Password = 'qurp ubnx zcnl ldxl'; // Use environment variables or secure method for passwords
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
    $mail->Port = 587;

    $mail->setFrom('jovinhampton@gmail.com', 'Jovin Hampton');
    $mail->addAddress($recipient);

    $mail->Subject = $subject;
    $mail->Body = $body;

    try {
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Check if the request is an AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_email') {
    $recipient = $_POST['recipient'];
    $subject = $_POST['subject'];
    $body = $_POST['body'];

    $success = sendEmail($recipient, $subject, $body);
    echo json_encode(['success' => $success]);
}
?>
