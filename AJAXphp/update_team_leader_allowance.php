<?php
header('Content-Type: application/json'); // Ensure JSON response

// Connect to the database
require_once("../db_connect.php");

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['team_leader_allowance_check'])) {
    // Get the employee ID and team leader allowance status from the POST data
    $employeeId = $_POST['employeeId'];
    $teamLeaderAllowance = $_POST['team_leader_allowance_check'];

    // Update the team_leader_allowance in the database
    $team_leader_update_sql = "UPDATE employees SET team_leader_allowance_check = ? WHERE employee_id = ?";
    $team_leader_update_stmt = $conn->prepare($team_leader_update_sql);

    // Check if preparation was successful
    if ($team_leader_update_stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Statement preparation failed: ' . $conn->error]);
        exit();
    }

    $team_leader_update_stmt->bind_param("ii", $teamLeaderAllowance, $employeeId); // Both are integers

    // Execute the statement and check for success
    if ($team_leader_update_stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update team leader allowance']);
    }

    $team_leader_update_stmt->close();
}

if (($_SERVER['REQUEST_METHOD'] === 'POST') && isset($_POST['team_leader_allowance'])) {
    $employeeId = $_POST['employeeId'];
    $teamLeaderAllowance = $_POST['team_leader_allowance'];

    // Update the team_leader_allowance in the database
    $team_leader_allowance_rate_sql = "UPDATE employees SET team_leader_allowance = ? WHERE employee_id = ?";
    $team_leader_allowance_rate_stmt = $conn->prepare($team_leader_allowance_rate_sql);

    // Check if preparation was successful
    if ($team_leader_allowance_rate_stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Statement preparation failed: ' . $conn->error]);
        exit();
    }

    $team_leader_allowance_rate_stmt->bind_param("di", $teamLeaderAllowance, $employeeId);

    // Execute the statement and check for success
    if ($team_leader_allowance_rate_stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update team leader allowance rate.']);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trainer_allowance_check'])) {
    // Get the employee ID and trainer allowance status from the POST data
    $employeeId = $_POST['employeeId'];
    $trainerAllowance = $_POST['trainer_allowance_check'];

    // Update the trainer_allowance in the database
    $trainer_update_sql = "UPDATE employees SET trainer_allowance_check = ? WHERE employee_id = ?";
    $trainer_update_stmt = $conn->prepare($trainer_update_sql);

    // Check if preparation was successful
    if ($trainer_update_stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Statement preparation failed: ' . $conn->error]);
        exit();
    }

    $trainer_update_stmt->bind_param("ii", $trainerAllowance, $employeeId); // Both are integers

    // Execute the statement and check for success
    if ($trainer_update_stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update trainer allowance']);
    }

    $trainer_update_stmt->close();
}

if (($_SERVER['REQUEST_METHOD'] === 'POST') && isset($_POST['trainer_allowance'])) {
    $employeeId = $_POST['employeeId'];
    $trainerAllowance = $_POST['trainer_allowance'];

    // Update the trainer_allowance in the database
    $trainer_allowance_rate_sql = "UPDATE employees SET trainer_allowance = ? WHERE employee_id = ?";
    $trainer_allowance_rate_stmt = $conn->prepare($trainer_allowance_rate_sql);

    // Check if preparation was successful
    if ($trainer_allowance_rate_stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Statement preparation failed: ' . $conn->error]);
        exit();
    }

    $trainer_allowance_rate_stmt->bind_param("di", $trainerAllowance, $employeeId);

    // Execute the statement and check for success
    if ($trainer_allowance_rate_stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update trainer allowance rate.']);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['supervisor_allowance_check'])) {
    // Get the employee ID and supervisor allowance status from the POST data
    $employeeId = $_POST['employeeId'];
    $supervisorAllowance = $_POST['supervisor_allowance_check'];

    // Update the supervisor_allowance in the database
    $supervisor_update_sql = "UPDATE employees SET supervisor_allowance_check = ? WHERE employee_id = ?";
    $supervisor_update_stmt = $conn->prepare($supervisor_update_sql);

    // Check if preparation was successful
    if ($supervisor_update_stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Statement preparation failed: ' . $conn->error]);
        exit();
    }

    $supervisor_update_stmt->bind_param("ii", $supervisorAllowance, $employeeId); // Both are integers

    // Execute the statement and check for success
    if ($supervisor_update_stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update supervisor allowance']);
    }

    $supervisor_update_stmt->close();
}

if (($_SERVER['REQUEST_METHOD'] === 'POST') && isset($_POST['supervisor_allowance'])) {
    $employeeId = $_POST['employeeId'];
    $supervisorAllowance = $_POST['supervisor_allowance'];

    // Update the supervisor_allowance in the database
    $supervisor_allowance_rate_sql = "UPDATE employees SET supervisor_allowance = ? WHERE employee_id = ?";
    $supervisor_allowance_rate_stmt = $conn->prepare($supervisor_allowance_rate_sql);

    // Check if preparation was successful
    if ($supervisor_allowance_rate_stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Statement preparation failed: ' . $conn->error]);
        exit();
    }

    $supervisor_allowance_rate_stmt->bind_param("di", $supervisorAllowance, $employeeId);

    // Execute the statement and check for success
    if ($supervisor_allowance_rate_stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update supervisor allowance rate.']);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['painter_allowance_check'])) {
    // Get the employee ID and painter allowance status from the POST data
    $employeeId = $_POST['employeeId'];
    $painterAllowance = $_POST['painter_allowance_check'];

    // Update the painter_allowance in the database
    $painter_update_sql = "UPDATE employees SET painter_allowance_check = ? WHERE employee_id = ?";
    $painter_update_stmt = $conn->prepare($painter_update_sql);

    // Check if preparation was successful
    if ($painter_update_stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Statement preparation failed: ' . $conn->error]);
        exit();
    }

    $painter_update_stmt->bind_param("ii", $painterAllowance, $employeeId); // Both are integers

    // Execute the statement and check for success
    if ($painter_update_stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update painter allowance']);
    }

    $painter_update_stmt->close();
}

if (($_SERVER['REQUEST_METHOD'] === 'POST') && isset($_POST['painter_allowance'])) {
    $employeeId = $_POST['employeeId'];
    $painterAllowance = $_POST['painter_allowance'];

    // Update the painter_allowance in the database
    $painter_allowance_rate_sql = "UPDATE employees SET painter_allowance = ? WHERE employee_id = ?";
    $painter_allowance_rate_stmt = $conn->prepare($painter_allowance_rate_sql);

    // Check if preparation was successful
    if ($painter_allowance_rate_stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Statement preparation failed: ' . $conn->error]);
        exit();
    }

    $painter_allowance_rate_stmt->bind_param("di", $painterAllowance, $employeeId);

    // Execute the statement and check for success
    if ($painter_allowance_rate_stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update painter allowance rate.']);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['machine_maintenance_allowance_check'])) {
    // Get the employee ID and machine maintenance allowance status from the POST data
    $employeeId = $_POST['employeeId'];
    $machineMaintenanceAllowance = $_POST['machine_maintenance_allowance_check'];

    // Update the machine_maintenance_allowance in the database
    $machine_maintenance_update_sql = "UPDATE employees SET machine_maintenance_allowance_check = ? WHERE employee_id = ?";
    $machine_maintenance_update_stmt = $conn->prepare($machine_maintenance_update_sql);

    // Check if preparation was successful
    if ($machine_maintenance_update_stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Statement preparation failed: ' . $conn->error]);
        exit();
    }

    $machine_maintenance_update_stmt->bind_param("ii", $machineMaintenanceAllowance, $employeeId); // Both are integers

    // Execute the statement and check for success
    if ($machine_maintenance_update_stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update machine maintenance allowance']);
    }

    $machine_maintenance_update_stmt->close();
}

if (($_SERVER['REQUEST_METHOD'] === 'POST') && isset($_POST['machine_maintenance_allowance'])) {
    $employeeId = $_POST['employeeId'];
    $machineMaintenanceAllowance = $_POST['machine_maintenance_allowance'];

    // Update the machine_maintenance_allowance in the database
    $machine_maintenance_allowance_rate_sql = "UPDATE employees SET machine_maintenance_allowance = ? WHERE employee_id = ?";
    $machine_maintenance_allowance_rate_stmt = $conn->prepare($machine_maintenance_allowance_rate_sql);

    // Check if preparation was successful
    if ($machine_maintenance_allowance_rate_stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Statement preparation failed: ' . $conn->error]);
        exit();
    }

    $machine_maintenance_allowance_rate_stmt->bind_param("di", $machineMaintenanceAllowance, $employeeId);

    // Execute the statement and check for success
    if ($machine_maintenance_allowance_rate_stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update machine maintenance allowance rate.']);
    }
}


// Close the database connection
$conn->close();
exit(); // Make sure to exit after sending a response
?>