<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once '/vendor/autoload.php';

// Database connection
require_once '/db_connect.php';
require_once '/email_sender.php';

$emailSender = new emailSender();

// Retrieve CAPA data
$capa_sql = "SELECT * FROM capa";
$capa_result = $conn->query($capa_sql);

if ($capa_result->num_rows > 0) {
    while ($row = $capa_result->fetch_assoc()) {
        if (!empty($row['target_close_date'])) {
            $targetCloseDate = new DateTime($row['target_close_date']);
            $currentDate = new DateTime();
            $interval = $currentDate->diff($targetCloseDate);
            $daysLeft = $interval->format('%r%a');

            // For testing, we'll check if the days left is 30 and send email
            if ($daysLeft == 30) {
                $emailSender->sendEmail(
                    'jovin.hampton@smbeharwal.fujielectric.com', // Recipient email
                    'Jovin Hampton', // Recipient name
                    'CAPA Reminder: 30 Days Left', // Subject
                    "Reminder: The CAPA document with ID {$row['capa_document_id']} has 30 days left before its target close date." // Body
                );
            }
        }
    }
}
?>
