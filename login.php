<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require("db_connect.php");

$config = include('config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve user input
    $username = $_POST['username'];
    $password = $_POST['password'];

    $_SESSION['username'] = $username;

    // Check if input fields are empty
    if (empty($username) || empty($password)) {
        // Redirect back to the form page with an error message
        $error_message = "Please enter both username and password.";
        header("Location: http://$serverAddress/$projectName/login.php?error=" . urlencode($error_message));
        exit();
    }

    // Prepare statement and execute the SQL query
    $stmt_check = $conn->prepare("SELECT u.*, e.*, e.is_active
    FROM users u
    INNER JOIN employees e ON u.employee_id = e.employee_id
    WHERE BINARY u.username = ? AND u.password = ?
    ");
    $stmt_check->bind_param("ss", $username, $password);
    $stmt_check->execute();

    $result = $stmt_check->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if ($user['is_active'] == 1) {
            $_SESSION['username'] = $username;
            $_SESSION['logged_in'] = true;

            // Query to get user's role 
            $stmt_employee_id = $conn->prepare("SELECT employee_id, user_id, role FROM users WHERE BINARY users.username = ?");
            $stmt_employee_id->bind_param("s", $username);
            $stmt_employee_id->execute();

            $result_employee_id = $stmt_employee_id->get_result();

            if ($result_employee_id->num_rows > 0) {
                $employee_id = $result_employee_id->fetch_assoc();
                $_SESSION['employee_id'] = $employee_id['employee_id'];
                $_SESSION['systemRole'] = $employee_id['role'];
                $_SESSION['user_id'] = $employee_id['user_id'];
            }

            header("Location: http://$serverAddress/$projectName/Pages/index.php");
            exit();
        } else if ($user['is_active'] == 0) {
            // User is not active
            $access_error = "Access to your account has been disabled.";
            header("Location: login.php?error=" . urlencode($access_error));
            exit();
        }
    } else {
        // Redirect back to the form page with an error message
        $error_message = "Incorrect username or password.";
        header("Location: http://$serverAddress/$projectName/login.php?error=" . urlencode($error_message));
        exit();
    }

    // Close the statement and the database connection
    $stmt_check->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="shortcut icon" type="image/x-icon" href="Images/FE-logo-icon.ico" />
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>Login</title>
</head>

<body class="background-color">
    <div class="container">
        <div class="d-flex justify-content-center align-items-center min-vh-100">
            <div class="card rounded-3 p-5 border-0 shadow-lg">
                <div class="row">
                    <div class="col-md-6 d-flex flex-column justify-content-start p-5">
                        <img src="Images/FE-logo.png" alt="Logo" class="img-fluid">
                        <h2 class="fw-bold">
                            <img class="img-fluid" src="./Images/PowerFusionWordLogo.png" alt="PowerFusionLogo"
                                style="width: 20rem;" sizes="(max-width: 576px) 10rem, 20rem">
                        </h2>

                    </div>

                    <div class="col-md-6 d-flex flex-column justify-content-center">
                        <form name="loginForm" action="login.php" method="post">
                            <div class="mb-3">
                                <?php
                                // Check if an error message exists
                                if (isset($_GET['error'])) {
                                    $error_message = $_GET['error'];
                                    echo '<p class="error-message bg-danger text-center text-white p-1" style="font-size: 1.5vh; width:100%; border-radius: 0.8vh;">' . $error_message . '</p>';
                                }
                                ?>
                                <label for="username" class="form-label fw-bold">Username</label>
                                <input class="form-control" name="username" id="username">
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label fw-bold">Password</label>
                                <input type="password" name="password" class="form-control" id="password">
                            </div>
                            <div class="d-flex justify-content-center justify-content-md-start">
                                <button type="submit" class="btn signature-btn">Login</button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
</body>

</html>