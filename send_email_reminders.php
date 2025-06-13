<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set the timezone to Sydney
date_default_timezone_set('Australia/Sydney');

require_once 'vendor/autoload.php';

// Database connection
require_once 'db_connect.php';
require_once 'email_sender.php';

// ========================= Get the employees ========================= 
$employees_sql = "SELECT employee_id, first_name, last_name, email, visa_expiry_date FROM employees";
$employees_result = $conn->query($employees_sql);

$employees = [];

while ($employee_row = $employees_result->fetch_assoc()) {
    $visa_expiry_date = !empty($employee_row['visa_expiry_date']) ? new DateTime($employee_row['visa_expiry_date']) : null;

    $employees[$employee_row['employee_id']] = [
        'first_name' => $employee_row['first_name'],
        'last_name' => $employee_row['last_name'],
        'email' => $employee_row['email'],
        'visa_expiry_date' => $visa_expiry_date
    ];
}

$currentDate = new DateTime();

// ========================= Check Visa Expiry Dates =========================
if (date('N') == 1) { // 1 = Monday
    foreach ($employees as $employee_id => $employee) {
        $expiryDate = $employee['visa_expiry_date'];

        if ($expiryDate instanceof DateTime) {
            $interval = $currentDate->diff($expiryDate);
            $daysLeft = $interval->format('%r%a');

            if ($daysLeft < 30) {
                $hrOfficerEmail = 'thi.tran@smbeharwal.fujielectric.com';
                $recipientName = 'Thi Tran';

                $visaEmailSender = new emailSender();
                $visaEmailSender->sendEmail(
                    $hrOfficerEmail,
                    $recipientName,
                    "Visa Expiry Alert (" . $employee['first_name'] . " " . $employee['last_name'] . ") : Action Required",
                    "
                    <p>Dear $recipientName,</p>
                    <p>This is to inform you that the visa of <strong>{$employee['first_name']} {$employee['last_name']}</strong> (Employee ID: $employee_id) will expire in <strong>$daysLeft days</strong> on <strong>{$expiryDate->format('Y-m-d')}</strong>.</p>
                    <p>Please take the necessary actions.</p>
                    <p>This email is sent automatically. Please do not reply.</p>
                    <p>Best regards,<br></p>
                    "
                );
                unset($visaEmailSender);
            }
        }
    }
}

// ========================= Check CAPA Expiry Dates ========================= 
$capa_sql = "SELECT * FROM capa";
$capa_result = $conn->query($capa_sql);

if ($capa_result->num_rows > 0) {
    while ($row = $capa_result->fetch_assoc()) {
        if (!empty($row['target_close_date'])) {
            $targetCloseDate = new DateTime($row['target_close_date']);
            $dateRaised = new DateTime($row['date_raised']);
            $currentDate = new DateTime();
            $capaDocumentId = $row['capa_document_id'];
            $capaOwner = $row['capa_owner'];
            $assignedTo = $row['assigned_to'];
            $status = $row['status'];
            $interval = $currentDate->diff($targetCloseDate);
            $daysLeft = $interval->format('%r%a');
            $daysLeft = ($daysLeft == 0) ? '0' : $daysLeft;
            
            $timeframeInterval = $dateRaised->diff($targetCloseDate);
            $timeframeDays = $timeframeInterval->format('%r%a');
            $daysLeftReminder = floor(0.10 * $timeframeDays);

            if (!isset($employees[$capaOwner]) || !isset($employees[$assignedTo])) {
                error_log("Employee with ID $capaOwner or $assignedTo not found for CAPA $capaDocumentId");
                continue;
            }

            $ownerEmail = $employees[$capaOwner]['email'];
            $ownerName = $employees[$capaOwner]['first_name'] . ' ' . $employees[$capaOwner]['last_name'];
            $assignedToEmail = $employees[$assignedTo]['email'];
            $assignedName = $employees[$assignedTo]['first_name'] . ' ' . $employees[$assignedTo]['last_name'];

            // 30 Days Left Reminder
            if ($daysLeft == 30 && $status === "Open") {
                // Send to Owner
                $ownerEmailSender = new emailSender();
                $ownerEmailSender->sendEmail(
                    $ownerEmail,
                    $ownerName,
                    'CAPA Reminder: 30 Days Left',
                    "
                    <p>Dear $ownerName,</p>
                    <p>This is a reminder that the CAPA document with ID <strong>{$row['capa_document_id']}</strong> has <strong>30 days left</strong> before its target close date.</p>
                    <p><strong>Details:</strong></p>
                    <ul>
                        <li><strong>Date Raised:</strong> {$row['date_raised']}</li>
                        <li><strong>Severity:</strong> {$row['severity']}</li>
                        <li><strong>Raised Against:</strong> {$row['raised_against']}</li>
                        <li><strong>CAPA Owner:</strong> $ownerName</li>
                        <li><strong>Assigned To:</strong> $assignedName</li>
                    </ul>
                    <p>Please take the necessary actions regarding this document.</p>
                    <p>This email is sent automatically. Please do not reply.</p>
                    <p>Best regards,<br></p>
                    "
                );
                unset($ownerEmailSender);

                // Send to Assigned (if different)
                if ($ownerEmail !== $assignedToEmail) {
                    $assignedEmailSender = new emailSender();
                    $assignedEmailSender->sendEmail(
                        $assignedToEmail,
                        $assignedName,
                        'CAPA Reminder: 30 Days Left',
                        "
                        <p>Dear $assignedName,</p>
                        <p>This is a reminder that the CAPA document assigned to you, with ID <strong>{$row['capa_document_id']}</strong> has <strong>30 days left</strong> before its target close date.</p>
                        <p><strong>Details:</strong></p>
                        <ul>
                            <li><strong>Date Raised:</strong> {$row['date_raised']}</li>
                            <li><strong>Severity:</strong> {$row['severity']}</li>
                            <li><strong>Raised Against:</strong> {$row['raised_against']}</li>
                            <li><strong>CAPA Owner:</strong> $ownerName</li>
                            <li><strong>Assigned To:</strong> $assignedName</li>
                        </ul>
                        <p>Please take the necessary actions regarding this document.</p>
                        <p>This email is sent automatically. Please do not reply.</p>
                        <p>Best regards,<br></p>
                        "
                    );
                    unset($assignedEmailSender);
                }
            } 
            // 10% Timeframe Reminder
            elseif ($daysLeft == $daysLeftReminder && $status === "Open") {
                $ownerEmailSender = new emailSender();
                $ownerEmailSender->sendEmail(
                    $ownerEmail,
                    $ownerName,
                    'CAPA Reminder: ' . $daysLeftReminder . ' Days Left',
                    "
                    <p>Dear $ownerName,</p>
                    <p>This is a reminder that the CAPA document with ID <strong>{$row['capa_document_id']}</strong> has <strong>$daysLeftReminder days left</strong> before its target close date.</p>
                    <p><strong>Details:</strong></p>
                    <ul>
                        <li><strong>Date Raised:</strong> {$row['date_raised']}</li>
                        <li><strong>Severity:</strong> {$row['severity']}</li>
                        <li><strong>Raised Against:</strong> {$row['raised_against']}</li>
                        <li><strong>CAPA Owner:</strong> $ownerName</li>
                        <li><strong>Assigned To:</strong> $assignedName</li>
                    </ul>
                    <p>Please take the necessary actions regarding this document.</p>
                    <p>This email is sent automatically. Please do not reply.</p>
                    <p>Best regards,<br></p>
                    "
                );
                unset($ownerEmailSender);

                if ($ownerEmail !== $assignedToEmail) {
                    $assignedEmailSender = new emailSender();
                    $assignedEmailSender->sendEmail(
                        $assignedToEmail,
                        $assignedName,
                        'CAPA Reminder: ' . $daysLeftReminder . ' Days Left',
                        "
                        <p>Dear $assignedName,</p>
                        <p>This is a reminder that the CAPA document assigned to you, with ID <strong>{$row['capa_document_id']}</strong> has <strong>$daysLeftReminder days left</strong> before its target close date.</p>
                        <p><strong>Details:</strong></p>
                        <ul>
                            <li><strong>Date Raised:</strong> {$row['date_raised']}</li>
                            <li><strong>Severity:</strong> {$row['severity']}</li>
                            <li><strong>Raised Against:</strong> {$row['raised_against']}</li>
                            <li><strong>CAPA Owner:</strong> $ownerName</li>
                            <li><strong>Assigned To:</strong> $assignedName</li>
                        </ul>
                        <p>Please take the necessary actions regarding this document.</p>
                        <p>This email is sent automatically. Please do not reply.</p>
                        <p>Best regards,<br></p>
                        "
                    );
                    unset($assignedEmailSender);
                }
            } 
            // Due Today (0 days left)
            elseif ($daysLeft == 0 && $status === "Open") {
                $ownerEmailSender = new emailSender();
                $ownerEmailSender->sendEmail(
                    $ownerEmail,
                    $ownerName,
                    'CAPA Overdue: Action Required Immediately',
                    "
                    <p>Dear $ownerName,</p>
                    <p>This is a reminder that the CAPA document with ID <strong>{$row['capa_document_id']}</strong> is <strong>overdue today</strong>.</p>
                    <p><strong>Details:</strong></p>
                    <ul>
                        <li><strong>Date Raised:</strong> {$row['date_raised']}</li>
                        <li><strong>Severity:</strong> {$row['severity']}</li>
                        <li><strong>Raised Against:</strong> {$row['raised_against']}</li>
                        <li><strong>CAPA Owner:</strong> $ownerName</li>
                        <li><strong>Assigned To:</strong> $assignedName</li>
                    </ul>
                    <p>Please address this overdue CAPA document immediately.</p>
                    <p>This email is sent automatically. Please do not reply.</p>
                    <p>Best regards,<br></p>
                    "
                );
                unset($ownerEmailSender);

                if ($ownerEmail !== $assignedToEmail) {
                    $assignedEmailSender = new emailSender();
                    $assignedEmailSender->sendEmail(
                        $assignedToEmail,
                        $assignedName,
                        'CAPA Overdue: Action Required Immediately',
                        "
                        <p>Dear $assignedName,</p>
                        <p>This is a reminder that the CAPA document assigned to you, with ID <strong>{$row['capa_document_id']}</strong> is <strong>overdue today</strong>.</p>
                        <p><strong>Details:</strong></p>
                        <ul>
                            <li><strong>Date Raised:</strong> {$row['date_raised']}</li>
                            <li><strong>Severity:</strong> {$row['severity']}</li>
                            <li><strong>Raised Against:</strong> {$row['raised_against']}</li>
                            <li><strong>CAPA Owner:</strong> $ownerName</li>
                            <li><strong>Assigned To:</strong> $assignedName</li>
                        </ul>
                        <p>Please address this overdue CAPA document immediately.</p>
                        <p>This email is sent automatically. Please do not reply.</p>
                        <p>Best regards,<br></p>
                        "
                    );
                    unset($assignedEmailSender);
                }
            } 
            // Overdue (negative days left)
            elseif ($daysLeft < 0 && $status === "Open") {
                if (date('N') === "4") { // Only on Mondays
                    $absDaysLeft = abs($daysLeft);

                    $ownerEmailSender = new emailSender();
                    $ownerEmailSender->sendEmail(
                        $ownerEmail,
                        $ownerName,
                        'CAPA Overdue: Action Required Immediately',
                        "
                        <p>Dear $ownerName,</p>
                        <p>This is a reminder that the CAPA document with ID <strong>{$row['capa_document_id']}</strong> is <strong>overdue</strong>. It was originally scheduled to be closed <strong>{$absDaysLeft} days ago</strong>.</p>
                        <p><strong>Details:</strong></p>
                        <ul>
                            <li><strong>Date Raised:</strong> {$row['date_raised']}</li>
                            <li><strong>Severity:</strong> {$row['severity']}</li>
                            <li><strong>Raised Against:</strong> {$row['raised_against']}</li>
                            <li><strong>CAPA Owner:</strong> $ownerName</li>
                            <li><strong>Assigned To:</strong> $assignedName</li>
                        </ul>
                        <p>Please address this overdue CAPA document immediately.</p>
                        <p>This email is sent automatically. Please do not reply.</p>
                        <p>Best regards,<br></p>
                        "
                    );
                    unset($ownerEmailSender);

                    if ($ownerEmail !== $assignedToEmail) {
                        $assignedEmailSender = new emailSender();
                        $assignedEmailSender->sendEmail(
                            $assignedToEmail,
                            $assignedName,
                            'CAPA Overdue: Action Required Immediately',
                            "
                            <p>Dear $assignedName,</p>
                            <p>This is a reminder that the CAPA document assigned to you, with ID <strong>{$row['capa_document_id']}</strong> is <strong>overdue</strong>. It was originally scheduled to be closed <strong>{$absDaysLeft} days ago</strong>.</p>
                            <p><strong>Details:</strong></p>
                            <ul>
                                <li><strong>Date Raised:</strong> {$row['date_raised']}</li>
                                <li><strong>Severity:</strong> {$row['severity']}</li>
                                <li><strong>Raised Against:</strong> {$row['raised_against']}</li>
                                <li><strong>CAPA Owner:</strong> $ownerName</li>
                                <li><strong>Assigned To:</strong> $assignedName</li>
                            </ul>
                            <p>Please address this overdue CAPA document immediately.</p>
                            <p>This email is sent automatically. Please do not reply.</p>
                            <p>Best regards,<br></p>
                            "
                        );
                        unset($assignedEmailSender);
                    }
                }
            }
        }
    }
}