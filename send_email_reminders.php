<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once 'vendor/autoload.php';

// Database connection
require_once 'db_connect.php';
require_once 'email_sender.php';

// ========================= Get the employees ========================= 
$employees_sql = "SELECT employee_id, first_name, last_name, email FROM employees";
$employees_result = $conn->query($employees_sql);

$employees = [];

// Store employee data in an associative array with employee_id as the key
while ($employee_row = $employees_result->fetch_assoc()) {
    $employees[$employee_row['employee_id']] = [
        'first_name' => $employee_row['first_name'],
        'last_name' => $employee_row['last_name'],
        'email' => $employee_row['email']
    ];
}

$emailSender = new emailSender();

// Retrieve CAPA data
$capa_sql = "SELECT * FROM capa";
$capa_result = $conn->query($capa_sql);

if ($capa_result->num_rows > 0) {
    while ($row = $capa_result->fetch_assoc()) {
        if (!empty($row['target_close_date'])) {
            $targetCloseDate = new DateTime($row['target_close_date']);
            $currentDate = new DateTime();
            $capaOwner = $row['capa_owner'];
            $interval = $currentDate->diff($targetCloseDate);
            $daysLeft = $interval->format('%r%a');

            // Check if the days left is 30 and send email to the capa_owner
            if ($daysLeft == 30 && isset($employees[$capaOwner])) {
                $ownerEmail = $employees[$capaOwner]['email'];
                $ownerName = $employees[$capaOwner]['first_name'] . ' ' . $employees[$capaOwner]['last_name'];

                // Send the email to the capa_owner
                $emailSender->sendEmail(
                    $ownerEmail, // Recipient email
                    $ownerName, // Recipient name
                    'CAPA Reminder: 30 Days Left', // Subject
                    "Reminder: The CAPA document with ID {$row['capa_document_id']} has 30 days left before its target close date." // Body
                );
            }
        }
    }
}
?>
