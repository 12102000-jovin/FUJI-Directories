<?php 
$user_id = $_SESSION['user_id'];

// Prepare the SQL statement with joins
$sql = "
    SELECT 
        folders.folder_name, 
        users.user_id, 
        users_groups.role 
    FROM 
        users 
    JOIN 
        users_groups ON users.user_id = users_groups.user_id 
    JOIN 
        folders ON folders.folder_id = users_groups.group_id 
    WHERE 
        folders.folder_name = ? 
        AND users.user_id = ?";

// Prepare the statement
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $folder_name, $user_id);

// Execute the statement
$stmt->execute();

// Fetch the result
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Echo the role
    $role = $row['role'];
    // echo "Group Role: " . $role;

    if ($role === "restricted") {
        header("Location: http://$serverAddress/$projectName/access_restricted.php");
    }
} else {
    echo "No role found for this user in the specified folder.";
    header("Location: http://$serverAddress/$projectName/access_restricted.php");
}

?>