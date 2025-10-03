<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("../db_connect.php");

if (isset($_POST['toggle_step'], $_POST['project_details_id'], $_POST['employee_id'])) {
    $project_details_id = intval($_POST['project_details_id']);
    $stepNumber = intval($_POST['toggle_step']);
    $employee_id = $_POST['employee_id'];

    // Map steps to database columns
    $stepColumns = [
        1 => 'drawing_issued_date',
        2 => 'programming_date',
        3 => 'ready_to_handover_date',
        4 => 'handed_over_to_electrical_date',
        5 => 'testing_date',
        6 => 'completed_date',
        7 => 'ready_date'
    ];

    $stepByColumns = [
        1 => 'drawing_issued_by',
        2 => 'programming_by',
        3 => 'ready_to_handover_by',
        4 => 'handed_over_to_electrical_by',
        5 => 'testing_by',
        6 => 'completed_by',
        7 => 'ready_by'
    ];

    if (!isset($stepColumns[$stepNumber])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid step']);
        exit;
    }

    $dateColumn = $stepColumns[$stepNumber];
    $byColumn = $stepByColumns[$stepNumber];

    // Check current value
    $stmt = $conn->prepare("SELECT $dateColumn, $byColumn FROM project_details WHERE project_details_id = ?");
    $stmt->bind_param("i", $project_details_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row) {
        echo json_encode(['status' => 'error', 'message' => 'Project not found']);
        exit;
    }

    if (!empty($row[$dateColumn])) {
        // Toggle OFF: Check if current user is the one who completed it
        if ($row[$byColumn] != $employee_id) {
            echo json_encode(['status' => 'error', 'message' => 'Only the person who completed this step can uncheck it.']);
            exit;
        }

        // toggle off: remove date and employee
        $update_sql = "UPDATE project_details SET $dateColumn = NULL, $byColumn = NULL WHERE project_details_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $project_details_id);
        $update_stmt->execute();

        $new_date = 'N/A';
    } else {
        // toggle on: set date and employee
        $update_sql = "UPDATE project_details SET $dateColumn = NOW(), $byColumn = ? WHERE project_details_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $employee_id, $project_details_id);
        $update_stmt->execute();

        // get employee name
        $name_stmt = $conn->prepare("SELECT first_name, last_name FROM employees WHERE employee_id = ?");
        $name_stmt->bind_param("s", $employee_id);
        $name_stmt->execute();
        $name_row = $name_stmt->get_result()->fetch_assoc();
        $name = $name_row ? $name_row['first_name'] . ' ' . $name_row['last_name'] : 'N/A';

        $new_date = date('d M Y') . ' - ' . $name;
    }

    echo json_encode(['status' => 'success', 'new_date' => $new_date]);
    exit;
}

if (isset($_POST['project_details_id'], $_POST['note'], $_POST['employee_id'])) {
    $id = intval($_POST['project_details_id']);
    $note = trim($_POST['note']);
    $employee_id = $_POST['employee_id']; // keep as string to preserve leading zeros if needed

    $sql = "UPDATE project_details SET hold_note=?, hold_by=?, hold_date=NOW() WHERE project_details_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $note, $employee_id, $id);

    if ($stmt->execute()) {
        //  Fetch employee name
        $emp_stmt = $conn->prepare("SELECT first_name, last_name FROM employees WHERE employee_id = ?");
        $emp_stmt->bind_param("s", $employee_id); // "s" to preserve leading zeros
        $emp_stmt->execute();
        $emp_res = $emp_stmt->get_result()->fetch_assoc();
        $emp_stmt->close();

        $employee_name = $emp_res ? $emp_res['first_name'] . ' ' . $emp_res['last_name'] : 'Unknown';

        echo json_encode([
            "success" => true,
            "employee_name" => $employee_name,
            "date" => date('d M Y')
        ]);
    } else {
        echo json_encode(["success" => false, "error" => $stmt->error]);
    }
    exit;
}

if (isset($_POST['unhold'], $_POST['project_details_id'], $_POST['employee_id'])) {
    $id = intval($_POST['project_details_id']);
    $employee_id = $_POST['employee_id'];

    // First, check who placed the hold
    $check_sql = "SELECT hold_by FROM project_details WHERE project_details_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result()->fetch_assoc();
    $check_stmt->close();

    // Verify authorization
    if ($check_result && $check_result['hold_by'] && $check_result['hold_by'] != $employee_id) {
        echo json_encode(["success" => false, "error" => "Only the person who placed this hold can remove it."]);
        exit;
    }

    $sql = "UPDATE project_details SET hold_note=NULL, hold_by=NULL, hold_date=NULL WHERE project_details_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $stmt->error]);
    }
    exit;
}

// ================== EDIT STEP DATES ==================
$data = json_decode(file_get_contents("php://input"), true);

if (!empty($data['projectDetailsId']) && !empty($data['steps'])) {
    $project_details_id = intval($data['projectDetailsId']);
    $steps = $data['steps'];

    $stepColumns = [
        1 => 'drawing_issued_date',
        2 => 'programming_date',
        3 => 'ready_to_handover_date',
        4 => 'handed_over_to_electrical_date',
        5 => 'testing_date',
        6 => 'completed_date',
        7 => 'ready_date'
    ];

    $stepByColumns = [
        1 => 'drawing_issued_by',
        2 => 'programming_by',
        3 => 'ready_to_handover_by',
        4 => 'handed_over_to_electrical_by',
        5 => 'testing_by',
        6 => 'completed_by',
        7 => 'ready_by'
    ];

    foreach ($steps as $step) {
        $stepIndex = intval($step['stepIndex']);
        $date = !empty($step['date']) ? $step['date'] : null;
        $employeeId = isset($step['employeeId']) ? $step['employeeId'] : null;

        if (!isset($stepColumns[$stepIndex]))
            continue;

        $dateColumn = $stepColumns[$stepIndex];
        $byColumn = $stepByColumns[$stepIndex];

        // Update DB
        if ($date) {
            $stmt = $conn->prepare("UPDATE project_details SET $dateColumn = ?, $byColumn = ? WHERE project_details_id = ?");
            $stmt->bind_param("ssi", $date, $employeeId, $project_details_id);
        } else {
            $stmt = $conn->prepare("UPDATE project_details SET $dateColumn = NULL, $byColumn = NULL WHERE project_details_id = ?");
            $stmt->bind_param("i", $project_details_id);
        }
        $stmt->execute();
        $stmt->close();

        // Fetch employee name
        if ($employeeId && $date) {
            $name_stmt = $conn->prepare("SELECT first_name, last_name FROM employees WHERE employee_id = ?");
            $name_stmt->bind_param("s", $employeeId);
            $name_stmt->execute();
            $name_row = $name_stmt->get_result()->fetch_assoc();
            $employeeName = $name_row ? $name_row['first_name'] . ' ' . $name_row['last_name'] : 'N/A';
            $new_date = date('d M Y', strtotime($date)) . ' - ' . $employeeName;
            $name_stmt->close();
        } else {
            $new_date = 'N/A';
        }
    }

    echo json_encode(['success' => true, 'new_date' => $new_date]);
    exit;
}

echo json_encode(["success" => false, "error" => "Invalid input"]);
?>