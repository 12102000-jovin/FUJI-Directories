<?php
session_start();

$config = include ('config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];

// Check if the user is not logged in, then redirect to the login page
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: http://$serverAddress/$projectName/Login.php");
    exit();
}

// Check if the user wants to logout or not
if (isset($_GET['logout']) && ($_GET['logout']) === 'true') {
    // Terminate all the session
    session_destroy();
    header("Location: http://$serverAddress/$projectName/Login.php");
    exit();
}


// Check if the user is logged in and has an active session
if (isset($_SESSION['username']) && isset($_SESSION['logged_in'])) {
    // Check if the last activity timestamp is set
    if (isset($_SESSION['last_activity'])) {
        // Set the inactivity threshold (in seconds)
        $inactivityThreshold = 7200; // (adjust as needed)

        // Calculate the time difference between the current time and the last activity
        $lastActivityTime = strtotime($_SESSION['last_activity']);
        $currentTime = time();
        $timeDifference = $currentTime - $lastActivityTime;

        // Check if the user has been inactive for a certain amount of time
        if ($timeDifference > $inactivityThreshold) {
            // Perform any necessary action (e.g., log out the user)
            session_unset();
            session_destroy();

            // Redirect back to the form page with an error message
            $error_message = "Your session has expired please login again.";
            header("Location: http://$serverAddress/$projectName/Login.php?error=" . urlencode($error_message));
            exit();
        }
    }

    // Update the last activity timestamp
    $_SESSION['last_activity'] = date('Y-m-d H:i:s');
}
