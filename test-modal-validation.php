<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once 'vendor/autoload.php';

// Database connection
require_once 'db_connect.php';
require_once 'email_sender.php';

$emailSender = new emailSender();


// Send the email to the capa_owner
$emailSender->sendEmail(
    'jovin.hampton@smbeharwal.fujielectric.com', // Recipient email
    'Test Name', // Recipient name
    'CAPA Reminder: 30 Days Left', // Subject
    "Reminder: The CAPA document with ID  has 30 days left before its target close date." // Body
);
