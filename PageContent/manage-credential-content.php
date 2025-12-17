<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$config = include ('./../config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];

// Check if employee_id is set
if (isset($_GET["employee_id"])) {
    $employeeId = $_GET["employee_id"];
} else {
    die("Employee ID is missing.");
}

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Prepare and execute query safely
$user_credential_sql = $conn->prepare("SELECT * FROM users WHERE employee_id = ?");
$user_credential_sql->bind_param("i", $employeeId);
$user_credential_sql->execute();
$user_credential_result = $user_credential_sql->get_result();

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['username']) && isset($_POST['password'])) {
    $username = $_POST["username"];
    $password = $_POST["password"];
    $confirmPassword = $_POST["confirmPassword"];

    // Check if the passwords match
    if ($password !== $confirmPassword) {
        echo "Passwords do not match.";
        exit();
    }

    $edit_credential_sql = $conn->prepare("UPDATE users SET username = ?, `password` = ? WHERE employee_id = ?");
    if ($edit_credential_sql) {
        $edit_credential_sql->bind_param("ssi", $username, $password, $employeeId);
        $edit_credential_sql->execute();

        if ($edit_credential_sql->affected_rows > 0) {
            echo "<script> alert('Credentials updated succesfully.')</script>";
            echo '<script>window.location.replace("http://' . $serverAddress . '/' . $projectName . '/login.php");</script>';
        } else {
            echo "<script> alert('No changes are made.')</script>";
        }

        $edit_credential_sql->close();
    } else {
        echo "Failed to prepare the SQL statement.";
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Credential</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="./../style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .password-toggle {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            cursor: pointer;
        }
    </style>
</head>

<body style="background-color: #eef3f9;">
    <div class="container-fluid">

        <!-- <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a
                        href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/index.php">Home</a>
                </li>
                <li class="breadcrumb-item fw-bold signature-color">Manage Credential</li>
            </ol>
        </nav> -->
        <?php if ($user_credential_result->num_rows > 0): ?>
            <div class="row mt-5 pt-5 px-5 px-md-0">
                <?php while ($row = $user_credential_result->fetch_assoc()): ?>
                    <form class="col-12 col-md-8 mx-auto bg-white p-5 rounded-3 shadow-lg" method="POST">
                        <h2 class="fw-bold text-center mb-5">Manage Credential</h2>

                        <p id="confirmPasswordFeedback" class="error-message bg-danger text-center text-white p-1"
                            style="display:none; font-size: 1.5vh; width:100%; border-radius: 0.8vh;"></p>
                        <div class="form-group">
                            <label for="username" class="fw-bold">Username</label>
                            <input type="text" class="form-control" id="usernameInput" name="username"
                                value="<?php echo htmlspecialchars($row['username']); ?>">
                        </div>
                        <div class="form-group mt-3 position-relative">
                            <label for="password" class="fw-bold">Password</label>
                            <input type="password" class="form-control" id="password" name="password"
                                value="">
                            <a class="position-absolute top-50 end-0 mt-1 me-2 signature-color" type="button" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                        <div class="form-group mt-3 position-relative">
                            <label for="confirmPassword" class="fw-bold">Confirm Password</label>
                            <input type="password" class="form-control" id="confirmPassword" name="confirmPassword">
                            <a class="position-absolute top-50 end-0 mt-1 me-2 signature-color" type="button" id="toggleConfirmPassword">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn signature-btn mt-3" id="updateCredentialButton">Save</button>
                        </div>
                    </form>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const togglePassword = document.querySelector("#togglePassword");
            const passwordInput = document.querySelector("#password");
            const toggleConfirmPassword = document.querySelector("#toggleConfirmPassword");
            const confirmPasswordInput = document.querySelector("#confirmPassword");
            const updateCredentialButton = document.querySelector("#updateCredentialButton");
            const confirmPasswordFeedback = document.querySelector("#confirmPasswordFeedback");
            const usernameInput = document.querySelector("#usernameInput");

            function togglePasswordVisibility(input, icon) {
                if (input.type === "password") {
                    input.type = "text";
                    icon.classList.remove("fa-eye");
                    icon.classList.add("fa-eye-slash");
                } else {
                    input.type = "password";
                    icon.classList.remove("fa-eye-slash");
                    icon.classList.add("fa-eye");
                }
            }

            function checkInput(event) {
                if (!usernameInput.value || !passwordInput.value || !confirmPasswordInput.value) {
                    confirmPasswordFeedback.style.display = "block";
                    confirmPasswordFeedback.innerHTML = "Please fill in all the input fields.";
                    event.preventDefault();
                } else if (passwordInput.value !== confirmPasswordInput.value) {
                    confirmPasswordFeedback.style.display = "block";
                    confirmPasswordFeedback.innerHTML = "Passwords do not match.";
                    event.preventDefault();
                } else {
                    confirmPasswordFeedback.style.display = "none";
                }
            }

            togglePassword.addEventListener("click", () => togglePasswordVisibility(passwordInput, togglePassword.querySelector("i")));
            toggleConfirmPassword.addEventListener("click", () => togglePasswordVisibility(confirmPasswordInput, toggleConfirmPassword.querySelector("i")));
            updateCredentialButton.addEventListener("click", (event) => checkInput(event));
        });
    </script>
</body>
</html>