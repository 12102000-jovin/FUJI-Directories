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
            $dateRaised = new DateTime($row['date_raised']);
            $currentDate = new DateTime();
            $capaOwner = $row['capa_owner'];
            $assignedTo = $row['assigned_to'];
            $status = $row['status'];
            $interval = $currentDate->diff($targetCloseDate);
            $daysLeft = $interval->format('%r%a');

            $timeframeInterval = $dateRaised->diff($targetCloseDate);
            $timeframeDays = $timeframeInterval->format('%r%a');

            $daysLeftReminder = floor(0.10 * $timeframeDays);  // Rounding to nearest integer

            // Check if the days left is 30 and send email to the capa_owner
            if ($daysLeft == 30 && isset($employees[$capaOwner]) && $status === "Open") {
                $ownerEmail = $employees[$capaOwner]['email'];
                $ownerName = $employees[$capaOwner]['first_name'] . ' ' . $employees[$capaOwner]['last_name'];

                $assignedToEmail = $employees[$assignedTo]['email'];
                $assignedName = $employees[$assignedTo]['first_name'] . ' ' . $employees[$assignedTo]['last_name'];

                // Send the email to the capa_owner
                $emailSender->sendEmail(
                    $ownerEmail, // Recipient email
                    $ownerName, // Recipient name
                    'CAPA Reminder: 30 Days Left', // Subject
                    body: "
                        <p>Dear $ownerName,</p>

                        <p>This is a reminder that the CAPA document with ID <strong> {$row['capa_document_id']} </strong> has <strong> 30 days left </strong> before its target close date. </p>

                        <p><strong>Details:</strong></p>
                        <ul>
                            <li><strong>Date Raised:</strong><b> {$row['date_raised']}</b></li>
                            <li><strong>Severity:</strong><b> {$row['severity']}</b></li>
                            <li><strong>Raised Against:</strong><b> {$row['raised_against']} </b></li>
                            <li><strong>CAPA Owner: $ownerName </strong></li>
                            <li><strong>Assigned To: $assignedName </strong></li>
                        </ul>
                        <p>Please take the necessary actions regarding this document.</p>
                        <p>This email is send automatically. Please do not reply.</p>
                        <p>Best regards,<br></p>
                    "
                );

                // Send the email to the Assigned To
                $emailSender->sendEmail(
                    $assignedToEmail, // Recipient email
                    $assignedName, // Recipient name
                    'CAPA Reminder: 30 Days Left', // Subject
                    "
                        <p> Dear $assignedName,</p>

                        <p>This is a reminder that the CAPA document assigned to you, with ID <strong> {$row['capa_document_id']} </strong> has <strong> 30 days left </strong> before its target close date. </p>

                        <p><strong>Details:</strong></p>
                        <ul>
                            <li><strong>Date Raised:</strong><b> {$row['date_raised']} </b></li>
                            <li><strong>Severity:</strong><b> {$row['severity']}</b></li>
                            <li><strong>Raised Against:</strong><b> {$row['raised_against']}</b></li>
                            <li><strong>CAPA Owner: $ownerName </strong></li>
                            <li><strong>Assigned To: $assignedName</strong></li>
                        </ul>
                        <p>Please take the necessary actions regarding this document.</p>
                        <p>This email is send automatically. Please do not reply.</p>
                        <p>Best regards,<br></p>
                    "
                );
            } else if ($daysLeft == $daysLeftReminder && $status === "Open") {
                $ownerEmail = $employees[$capaOwner]['email'];
                $ownerName = $employees[$capaOwner]['first_name'] . ' ' . $employees[$capaOwner]['last_name'];

                $assignedToEmail = $employees[$assignedTo]['email'];
                $assignedName = $employees[$assignedTo]['first_name'] . ' ' . $employees[$assignedTo]['last_name'];

                // Send the email to the capa_owner
                $emailSender->sendEmail(
                    $ownerEmail, // Recipient email
                    $ownerName, // Recipient name
                    'CAPA Reminder: ' . $daysLeftReminder . ' Days Left', // Subject
                    body: "
                        <p>Dear $ownerName,</p>

                        <p>This is a reminder that the CAPA document with ID <strong> {$row['capa_document_id']} </strong> has <strong> $daysLeftReminder days left </strong> before its target close date. </p>

                        <p><strong>Details:</strong></p>
                        <ul>
                            <li><strong>Date Raised:</strong><b> {$row['date_raised']}</b></li>
                            <li><strong>Severity:</strong><b> {$row['severity']}</b></li>
                            <li><strong>Raised Against:</strong><b> {$row['raised_against']} </b></li>
                            <li><strong>CAPA Owner: $ownerName </strong></li>
                            <li><strong>Assigned To: $assignedName </strong></li>
                        </ul>
                        <p>Please take the necessary actions regarding this document.</p>
                        <p>This email is send automatically. Please do not reply.</p>
                        <p>Best regards,<br></p>
                    "
                );

                // Send the email to the Assigned To
                $emailSender->sendEmail(
                    $ownerEmail, // Recipient email
                    $ownerName, // Recipient name
                    'CAPA Reminder: ' . $daysLeftReminder . ' Days Left', // Subject
                    "
                        <p>Dear $assignedName,</p>

                        <p>This is a reminder that the CAPA document assigned to you, with ID <strong> {$row['capa_document_id']} </strong> has <strong> $daysLeftReminder days left </strong> before its target close date. </p>

                        <p><strong>Details:</strong></p>

                        <ul> 
                            <li><strong>Date Raised: </strong><b> {$row['date_raised']}</b></li>
                            <li><strong>Severity: </strong><b> {$row['severity']} </b></li>
                            <li><strong>Raised Against:</strong><b> {$row['raised_against']} </b></li>
                            <li><strong>CAPA Owner: $ownerName </strong></li>
                            <li><strong>Assigned To: $assignedName </strong></li>
                        </ul>
                        <p>Please take the necessary actions regarding this document.</p>
                        <p>This email is send automatically. Please do not reply.</p>
                        <p>Best regards,<br></p>
                    "
                );
                error_log("Employee with ID $capaOwner not found.");
            } else if ($daysLeft == 0 && $status === "Open") {
                $ownerEmail = $employees[$capaOwner]['email'];
                $ownerName = $employees[$capaOwner]['first_name'] . ' ' . $employees[$capaOwner]['last_name'];

                $assignedToEmail = $employees[$assignedTo]['email'];
                $assignedName = $employees[$assignedTo]['first_name'] . ' ' . $employees[$assignedTo]['last_name'];

                // Send the email to the capa_owner
                $emailSender->sendEmail(
                    $ownerEmail, // Recipient Email
                    $ownerName, // Recipient Name
                    'CAPA Overdue: Action Required Immediately', // Subject
                    body: "
                    <p>Dear $ownerName,</p>

                    <p>This is a reminder that the CAPA document with ID <strong>{$row['capa_document_id']}</strong> is <strong>overdue today</strong>. </p>

                    <p><strong>Details:</strong></p>
                    <ul>
                        <li><strong>Date Raised:</strong><b> {$row['date_raised']}</b></li>
                        <li><strong>Severity:</strong><b> {$row['severity']}</b></li>
                        <li><strong>Raised Against:</strong><b> {$row['raised_against']} </b></li>
                        <li><strong>CAPA Owner: $ownerName </strong></li>
                        <li><strong>Assigned To: $assignedName </strong></li>
                    </ul>
                
                    <p>Please address this overdue CAPA document immediately.</p>
                    <p>This email is sent automatically. Please do not reply.</p>
                
                    <p>Best regards,<br></p>
                    "
                );

                // Send the email to the Assigned To
                $emailSender->sendEmail(
                    $assignedToEmail, // Recipient Email
                    $assignedName, // Recipient Name
                    'CAPA Overdue: Action Required Immediately', // Subject
                    "       
                    <p>Dear $assignedName,</p>

                    <p>This is a reminder that the CAPA document assigned to you, with ID <strong> {$row['capa_document_id']}</strong> is <strong> overdue today</strong>. </p>

                    <p><strong>Details:</strong></p>
                    <ul>
                        <li><strong>Date Raised:</strong><b> {$row['date_raised']}</b></li>
                        <li><strong>Severity:</strong><b> {$row['severity']} </b></li>
                        <li><strong>Raised Against:</strong><b> {$row['raised_against']}</b></li>
                        <li><strong>CAPA Owner: $ownerName </strong></li>
                        <li><strong>Assigned To: $assignedName </strong></li>
                    </ul>

                    <p> Please address this overdue CAPA document immediately.</p>
                    <p> This email is sent automatically. Please do not reply.</p>

                    <p> Best regards,<br></p>
                    "
                );
            } else if ($daysLeft < 0 && $status === "Open") {
                // Set timezone to Sydney, Australia
                date_default_timezone_set('Australia/Sydney');

                // Get the current week of the year (Monday = 1, Sunday = 7)
                $currentWeek = date("N");

                if ($currentWeek === "1") {

                    $ownerEmail = $employees[$capaOwner]['email'];
                    $ownerName = $employees[$capaOwner]['first_name'] . ' ' . $employees[$capaOwner]['last_name'];

                    $assignedToEmail = $employees[$assignedTo]['email'];
                    $assignedName = $employees[$assignedTo]['first_name'] . ' ' . $employees[$assignedTo]['last_name'];

                    $absDaysLeft = abs($daysLeft);

                    // Send the email to the capa_owner
                    $emailSender->sendEmail(
                        $ownerEmail, // Recipient email
                        $ownerName, // Recipient name
                        'CAPA Overdue: Action Required Immediately', // Subject
                        body: "
                        <p>Dear $ownerName,</p>
                    
                        <p>This is a reminder that the CAPA document with ID <strong>{$row['capa_document_id']}</strong> is <strong>overdue</strong>. It was originally scheduled to be closed <strong>{$absDaysLeft} days ago</strong>.</p>
                    
                        <p><strong>Details:</strong></p>
                        <ul>
                            <li><strong>Date Raised:</strong><b> {$row['date_raised']}</b></li>
                            <li><strong>Severity:</strong><b> {$row['severity']}</b></li>
                            <li><strong>Raised Against:</strong><b> {$row['raised_against']} </b></li>
                            <li><strong>CAPA Owner: $ownerName </strong></li>
                            <li><strong>Assigned To: $assignedName </strong></li>
                        </ul>
                    
                        <p>Please address this overdue CAPA document immediately.</p>
                        <p>This email is sent automatically. Please do not reply.</p>
                    
                        <p>Best regards,<br></p>
                        "
                    );

                    // Send the emeial to the assigned to
                    $emailSender->sendEmail(
                        $assignedToEmail, // Recipient email
                        $assignedName, // Recipient name
                        'CAPA Overdue: Action Required Immidiately', // Subject
                        "
                        <p>This is a reminder that the CAPA document with ID <strong> {$row['capa_document_id']}</strong> is <strong>overdue</strong>. It was originally scheduled to be closed <strong>{$absDaysLeft} days ago</strong>.</p>

                        <p><strong>Details:</strong></p>
                        <ul> 
                            <li><strong>Date Raised:</strong><b> {$row['date_raised']}</b></li>
                            <li><strong>Severity:</strong><b> {$row['severity']}</b></li>
                            <li><strong>Raised Against:</strong><b> {$row['raised_against']} </b></li>
                            <li><strong>CAPA Owner: $ownerName </strong></li>
                            <li><strong>Assigned To: $assignedName </strong></li>
                        </ul>

                        <p> Please address this overdue CAPA document immediately. </p>
                        <p> This email is sent automatically. Please do not reply. </p>

                        <p> Best regards,<br></p>
                        "
                    );
                }
                error_log("Employee with ID $capaOwner not found.");
            }
        }
    }
}
