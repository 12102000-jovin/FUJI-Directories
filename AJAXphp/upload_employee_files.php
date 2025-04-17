<?php
header('Content-Type: application/json');

// Connect to the database
require_once("../db_connect.php");

// Make sure the request is a POST request
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get the form data
    $empIdToUploadEmployeeFile = $_POST["empIdToUploadEmployeeFile"];
    $empNameToUploadEmployeeFile = $_POST["empNameToUploadEmployeeFile"];
    $employeeFileDirectory = $_POST["employeeFileDirectory"];
    $employeeFileName = $_POST["employeeFileName"];
    $fileUploadDateInputValue = $_POST["fileUploadDateInputValue"];
    $employeeFileToSubmit = $_FILES["employeeFileToSubmit"];

    // Base directory for file storage
    $directory = "D:\\FSMBEH-Data\\09 - HR\\";

    // Prepare and execute the query to get the payroll type
    $payroll_type_sql = "SELECT payroll_type FROM employees WHERE employee_id = ?";
    if ($payroll_type_result = $conn->prepare($payroll_type_sql)) {
        $payroll_type_result->bind_param("s", $empIdToUploadEmployeeFile);
        $payroll_type_result->execute();
        $payroll_type_result->bind_result($emp_payroll_type);

        // Fetch the result
        if ($payroll_type_result->fetch()) {
            if ($emp_payroll_type === "wage") {
                $directory .= "04 - Wage Staff\\" . $employeeFileDirectory;
            }
        } else {
            // If no result is found, set a default value or handle the error
            $emp_payroll_type = 'Unknown';
        }
    } else {
        // Handle query preparation failure
        $emp_payroll_type = 'Error fetching payroll type';
    }

    // Ensure the directory exists and has proper permissions
    if (!file_exists($directory)) {
        if (!mkdir($directory, 0777, true)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to create directory. Please check folder permissions.'
            ]);
            exit;
        }
    }

    // Check if the file was uploaded without errors
    if ($employeeFileToSubmit['error'] === UPLOAD_ERR_OK) {
        // Set the file destination path (including file name)
        $targetFile = $directory . DIRECTORY_SEPARATOR . $employeeFileName . '.' . pathinfo($employeeFileToSubmit['name'], PATHINFO_EXTENSION);

        // Move the uploaded file to the target directory
        if (move_uploaded_file($employeeFileToSubmit['tmp_name'], $targetFile)) {
            // File uploaded successfully
            $response = [
                'status' => 'success',
                'message' => "File uploaded successfully: " . $employeeFileName
            ];
        } else {
            // Error during file upload
            $response = [
                'status' => 'error',
                'message' => "Error uploading file '$employeeFileName'."
            ];
        }
    } else {
        // Handle file upload error
        $response = [
            'status' => 'error',
            'message' => "File upload error: " . $employeeFileToSubmit['error']
        ];
    }

    // Send JSON response
    echo json_encode($response);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method.'
    ]);
}
?>
