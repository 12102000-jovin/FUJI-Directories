<?php
require_once '/Applications/XAMPP/xamppfiles/htdocs/FUJI-Directoriess/vendor/autoload.php';// Include the Composer autoload file
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailSender {
    private $mail;

    public function __construct() {
        $this->mail = new PHPMailer(true);
        $this->configureSMTP();
    }

    private function configureSMTP() {
        $this->mail->isSMTP();
        $this->mail->Host = 'smtp.gmail.com';
        $this->mail->SMTPAuth = true;
        $this->mail->Username = 'jovinhampton@gmail.com'; // SMTP username
        $this->mail->Password = 'qurp ubnx zcnl ldxl'; // SMTP password
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port = 587;
    }

    public function sendEmail($to, $toName, $subject, $body) {
        try {
            $this->mail->setFrom('jovinhampton@gmail.com', 'Jovin Hampton');
            $this->mail->addAddress($to, $toName);
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;
            $this->mail->send();
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$this->mail->ErrorInfo}";
        }
    }
}
?>
