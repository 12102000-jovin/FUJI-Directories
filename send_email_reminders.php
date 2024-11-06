<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once 'vendor/autoload.php';

// Database connection
require_once 'db_connect.php';
require_once 'email_sender.php';

// ========================= Get the employees ========================= 
$employees_sql = "SELECT employee_id, first_name, last_name, email, visa_expiry_date FROM employees";
$employees_result = $conn->query($employees_sql);

$employees = [];

// Store employee data in an associative array with employee_id as the key
while ($employee_row = $employees_result->fetch_assoc()) {
    // Check if visa_expiry_date is not null or empty
    $visa_expiry_date = !empty($employee_row['visa_expiry_date']) ? new DateTime($employee_row['visa_expiry_date']) : null;

    $employees[$employee_row['employee_id']] = [
        'first_name' => $employee_row['first_name'],
        'last_name' => $employee_row['last_name'],
        'email' => $employee_row['email'],
        'visa_expiry_date' => $visa_expiry_date
    ];
}

// Display loaded employees
echo "<h2>Loaded Employees:</h2>";
foreach ($employees as $id => $employee) {
    echo "ID: $id, Name: {$employee['first_name']} {$employee['last_name']}, Email: {$employee['email']}, Visa Expiry: " . ($employee['visa_expiry_date'] ? $employee['visa_expiry_date']->format('Y-m-d') : 'N/A') . "<br>";
}

$emailSender = new emailSender();

// ========================= Check Visa Expiry Dates =========================
$currentDate = new DateTime();
foreach ($employees as $employee_id => $employee) {
    $expiryDate = $employee['visa_expiry_date'];

    // Check if $expiryDate is a valid DateTime object
    if ($expiryDate instanceof DateTime) {
        $interval = $currentDate->diff($expiryDate);
        $daysLeft = $interval->format('%r%a');

        echo "Days left for employee ID $employee_id: $daysLeft<br>";

        // Check if the expiry date is within 30 days
        if ($daysLeft < 30) {
            echo "Preparing to send email to $recipientEmail for employee $employee_id.<br>";
            $recipientEmail = 'jovin.hampton@smbeharwal.fujielectric.com';
            $recipientName = 'Thi Tran';

            // Send the email notification
            $emailSender->sendEmail(
                to: $recipientEmail,
                toName: $recipientName,
                subject: "Visa Expiry Alert (" . $employee['first_name'] . " " . $employee['last_name'] . ") : Action Required",
                body: "
                <p> Dear $recipientName,</p>

                <p>This is to inform you that the visa of <strong>{$employee['first_name']} {$employee['last_name']}</strong> (Employee ID: $employee_id) will expire in <strong>$daysLeft days</strong> on <strong>{$expiryDate->format('Y-m-d')}</strong>.</p>

                <p>Please take the necessary actions.</p>
                <p>This email is sent automatically. Please do not reply.</p>

                <p>Best regards,<br></p>
                "
            );
        }
    }
}

// ========================= Check CAPA Expiry Dates ========================= 

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


