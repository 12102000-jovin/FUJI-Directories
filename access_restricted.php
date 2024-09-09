<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login page if not logged in
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Restricted</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body style="background-color: #eef3f9;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">

                <div class="alert alert-danger mt-5 text-center">
                    <h1>Access Restricted</h1>
                    <p>Sorry, you do not have permission to access this page.</p>
                    <a href="Pages/index.php" class="btn btn-primary">Go to Home</a>
                </div>
            </div>
        </div>
    </div>
</body>

</html>