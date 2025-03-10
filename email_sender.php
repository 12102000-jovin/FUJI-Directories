<?php
require_once 'vendor/autoload.php';// Include the Composer autoload file
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailSender
{
    private $mail;

    public function __construct()
    {
        $this->mail = new PHPMailer(true);
        $this->configureSMTP();
    }

    private function configureSMTP()
    {
        $this->mail->isSMTP();
        $this->mail->Host = 'smtp.gmail.com';
        $this->mail->SMTPAuth = true;
        $this->mail->Username = 'qa.smbeharwal@gmail.com'; // SMTP username
        $this->mail->Password = 'chut cion lgel eumi'; // SMTP password
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port = 587;
    }

    public function sendEmail($to, $toName, $subject, $body)
    {
        try {
            $this->mail->setFrom('qa.smbeharwal@gmail.com', 'Quality Assurances');
            $this->mail->addAddress($to, $toName);
            $this->mail->Subject = $subject;

            // Set the email format to HTML
            $this->mail->isHTML(true);

            $this->mail->Body = $body;
            $this->mail->send();
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$this->mail->ErrorInfo}";
        }
    }
}
?>