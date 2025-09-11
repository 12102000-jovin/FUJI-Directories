<?php
$user_id = $_SESSION['user_id'];
$employeeId = $_GET["employee_id"] ?? null;
$loginEmployeeId = $_SESSION["employee_id"];

// echo "This is the user id: " . $user_id;
// echo "This is the employee_id: ", $employeeId . "<br>";
// echo "This is the login employee_id: " . $loginEmployeeId;

// Prepare the SQL statement with joins
$sql = "
    SELECT 
        folders.folder_name, 
        users.user_id, 
        users_groups.role,
        position.position_name
    FROM 
        users 
    JOIN 
        users_groups ON users.user_id = users_groups.user_id 
    JOIN 
        groups_folders ON users_groups.group_id = groups_folders.group_id 
    JOIN 
        folders ON groups_folders.folder_id = folders.folder_id 
    JOIN 
        employees ON users.employee_id = employees.employee_id
    JOIN 
        position ON employees.position = position.position_id
    WHERE 
        folders.folder_name = ? 
        AND users.user_id = ?";

// Prepare the statement
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $folder_name, $user_id);

// Execute the statement
$stmt->execute();

// Check for SQL errors
if ($stmt->error) {
    echo "SQL Error: " . $stmt->error;
}

// Fetch the result
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Get the user's role
    $role = $row['role'];
    $position_name = $row['position_name'];

    // Check the role and employee ID conditions
    if ($role === "restricted") {
        // Check if employeeId is set and not null
        if (!isset($employeeId) || $employeeId === null) {
            // Redirect if employeeId is null
            header("Location: http://$serverAddress/$projectName/access_restricted.php");
            exit();
        } else if ($employeeId !== $loginEmployeeId) {
            // Redirect if employeeId does not match loginEmployeeId
            header("Location: http://$serverAddress/$projectName/access_restricted.php");
            exit();
        }
        // If employeeId matches loginEmployeeId, do nothing (pass)
    }
} else {
    $role = null;
    if ($employeeId !== $loginEmployeeId) {
        // Redirect if employeeId does not match loginEmployeeId
        header("Location: http://$serverAddress/$projectName/access_restricted.php");
        exit();
    }
}
?>