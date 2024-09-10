<?php
$user_id = $_SESSION['user_id'];

// Prepare the SQL statement with joins
$system_role_sql = "
    SELECT
        role
    FROM 
        users 
    WHERE 
        user_id = ?";

// Prepare the statement
$stmt = $conn->prepare($system_role_sql);
$stmt->bind_param("i", $user_id);

// Execute the statement
$stmt->execute();

// Fetch the result
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Echo the role
    $systemRole = $row['role'];
    // echo "System Role: " . $systemRole;

    if ($systemRole === "restricted" ) {
        echo "<script>
                    window.location.href = 'http://$serverAddress/$projectName/access_restricted.php';
                  </script>";
    }
} else {
    echo "No role found for this user in the specified folder.";
}

?>