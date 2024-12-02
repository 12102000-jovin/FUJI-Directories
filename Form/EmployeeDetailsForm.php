<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connect to the database
require_once("../db_connect.php");

$config = include('../config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];

$errors = [];

// Get all the visa status
$visa_status_sql = "SELECT * FROM visa";
$visa_status_result = $conn->query($visa_status_sql);

// Get all the position
$position_sql = "SELECT * FROM position";
$position_result = $conn->query($position_sql);

// Get all the department
$department_sql = "SELECT * FROM department";
$department_result = $conn->query($department_sql);

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['firstName']) && isset($_POST['lastName'])) {
    // ================ P E R S O N A L   D E T A I L S ================ 
    if (empty($_POST['firstName'])) {
        $errors['firstName'] = "First Name is required";
    } else {
        $firstName = $_POST['firstName'];
    }

    if (empty($_POST['lastName'])) {
        $errors['lastName'] = "Last Name is required";
    } else {
        $lastName = $_POST['lastName'];
    }

    $nickname = isset($_POST["nickname"]) ? $_POST["nickname"] : null;

    if (empty($_POST['gender'])) {
        $errors['gender'] = "Gender is required";
    } else {
        $gender = $_POST['gender'];
    }

    if (empty($_POST['dob'])) {
        $errors['dob'] = "Date of Birth is required";
    } else {
        $dob = $_POST['dob'];
    }

    // Validate and process visa status
    if (empty($_POST['visaStatus'])) {
        $errors['visaStatus'] = "Visa Status is required";
    } else {
        $visaStatus = $_POST['visaStatus'];

        // Check for "Other" visa status
        if ($visaStatus === "Other") {
            if (empty($_POST['otherVisaStatus'])) {
                $errors['otherVisaStatus'] = "Other Visa Status is required";
            } else {
                $otherVisaStatus = $_POST['otherVisaStatus'];

                // Check if otherVisaStatus already exists in the visa table
                $check_visa_sql = "SELECT visa_id FROM visa WHERE visa_name = ?";
                $check_visa_stmt = $conn->prepare($check_visa_sql);
                $check_visa_stmt->bind_param("s", $otherVisaStatus);
                $check_visa_stmt->execute();
                $check_visa_stmt->bind_result($visa_id);
                $check_visa_stmt->fetch();
                $check_visa_stmt->close();

                if ($visa_id) {
                    // `otherVisaStatus` already exists, use the existing `visa_id`
                    $visaStatus = $visa_id;
                } else {
                    // `otherVisaStatus` does not exist, insert it into the visa table
                    $insert_visa_sql = "INSERT INTO visa (visa_name) VALUES (?)";
                    $insert_visa_stmt = $conn->prepare($insert_visa_sql);
                    $insert_visa_stmt->bind_param("s", $otherVisaStatus);

                    if ($insert_visa_stmt->execute()) {
                        // Get the auto-incremented ID of the newly inserted visa status
                        $visa_id = $conn->insert_id;
                        $visaStatus = $visa_id;
                    } else {
                        echo "Error: " . $insert_visa_stmt->error;
                        $insert_visa_stmt->close();

                    }
                    $insert_visa_stmt->close();
                }
            }
        }
    }
    $visaExpiryDate = isset($_POST["visaExpiryDate"]) ? $_POST["visaExpiryDate"] : null;

    // ================ C O N T A C T S ================ 
    if (empty($_POST["address"])) {
        $errors['address'] = "Address is required";
    } else {
        $address = $_POST["address"];
    }

    $email = isset($_POST['email']) && $_POST['email'] !== "" ? $_POST['email'] : null;

    $personalEmail = isset($_POST['personalEmail']) && $_POST['personalEmail'] !== "" ? $_POST['personalEmail'] : null;

    if (empty($_POST["phoneNumber"])) {
        $errors['phoneNumber'] = "Phone Number is required";
    } else {
        $phoneNumber = $_POST["phoneNumber"];
    }

    $vehicleNumberPlate = isset($_POST['vehicleNumberPlate']) && $_POST['vehicleNumberPlate'] !== "" ? $_POST['vehicleNumberPlate'] : null;

    if (empty($_POST["emergencyContact"])) {
        $errors['emergencyContact'] = "Emergency Contact is required";
    } else {
        $emergencyContact = $_POST["emergencyContact"];
    }

    if (empty($_POST["emergencyContactName"])) {
        $errors['emergencyContactName'] = "Emergency Contact Name is required";
    } else {
        $emergencyContactName = $_POST["emergencyContactName"];
    }

    if (empty($_POST["emergencyContactRelationship"])) {
        $errors['emergencyContactRelationship'] = "Emergency Contact Relationship is required";
    } else {
        $emergencyContactRelationship = $_POST["emergencyContactRelationship"];
    }

    // ================ E M P L O Y M E N T   D E T A I L S ================ 
    if (empty($_POST["employeeId"])) {
        $errors['employeeId'] = "Employee ID is required";
    } else {
        $employeeId = str_pad($_POST["employeeId"], 3, "0", STR_PAD_LEFT); // Ensures 3 digits
    }

    if (empty($_POST["startDate"])) {
        $errors['startDate'] = "Start Date is required";
    } else {
        $startDate = $_POST["startDate"];
    }

    if (empty($_POST["employmentType"])) {
        $errors['employmentType'] = "Employment Type is required";
    } else {
        $employmentType = $_POST["employmentType"];
    }

    if (empty($_POST["department"])) {
        $errors['department'] = "Department is required";
    } else {
        $department = $_POST["department"];

        // Check for "Other" department
        if ($department === "Other") {
            if (empty($_POST['otherDepartment'])) {
                $errors['department'] = "Other Department is required";
            } else {
                $otherDepartment = $_POST['otherDepartment'];

                // Check if otherDepartment already exists in the department table
                $check_department_sql = "SELECT department_id FROM department WHERE department_name = ?";
                $check_department_stmt = $conn->prepare($check_department_sql);
                $check_department_stmt->bind_param("s", $otherDepartment);
                $check_department_stmt->execute();
                $check_department_stmt->bind_result($department_id);
                $check_department_stmt->fetch();
                $check_department_stmt->close();

                if ($department_id) {
                    // `otherDepartment` already exists, use the existing `department_id`
                    $department = $department_id;
                } else {
                    // `otherDepartment` does not exist, insert it into the department table
                    $insert_department_sql = "INSERT INTO department(department_name) VALUES (?)";
                    $insert_department_stmt = $conn->prepare($insert_department_sql);
                    $insert_department_stmt->bind_param("s", $otherDepartment);

                    if ($insert_department_stmt->execute()) {
                        // Get the auto-incremented ID of the newly inserted department status
                        $department_id = $conn->insert_id;
                        $department = $department_id;
                    } else {
                        echo "Error: " . $insert_department_stmt->error;
                        $insert_department_stmt->close();
                    }
                    $insert_department_stmt->close();
                }
            }
        }
    }
    if (empty($_POST["section"])) {
        $errors['section'] = "Section is required";
    } else {
        $section = $_POST["section"];
    }

    if (empty($_POST["position"])) {
        $errors['position'] = "Position is required";
    } else {
        $position = $_POST["position"];

        // Check for "Other" position
        if ($position === "Other") {
            if (empty($_POST['otherPosition'])) {
                $errors['position'] = "Other Position is required";
            } else {
                $otherPosition = $_POST['otherPosition'];

                // Check if otherPosition already exists in the position table
                $check_position_sql = "SELECT position_id FROM position WHERE position_name = ?";
                $check_position_stmt = $conn->prepare($check_position_sql);
                $check_position_stmt->bind_param("s", $otherPosition);
                $check_position_stmt->execute();
                $check_position_stmt->bind_result($position_id);
                $check_position_stmt->fetch();
                $check_position_stmt->close();

                if ($position_id) {
                    // `otherPosition` already exists, use the existing `position_id`
                    $position = $position_id;
                } else {
                    // `otherPosition` does not exist, insert it into the position table
                    $insert_position_sql = "INSERT INTO position (position_name) VALUES (?)";
                    $insert_position_stmt = $conn->prepare($insert_position_sql);
                    $insert_position_stmt->bind_param("s", $otherPosition);

                    if ($insert_position_stmt->execute()) {
                        // Get the auto-incremented ID of the newly inserted position status
                        $position_id = $conn->insert_id;
                        $position = $position_id;
                    } else {
                        echo "Error: " . $insert_position_stmt->error;
                        $insert_position_stmt->close();
                    }
                    $insert_position_stmt->close();
                }
            }
        }
    }

    if (empty($_POST["payrollType"])) {
        $errors['payrollType'] = "payrollType is required";
    } else {
        $payrollType = $_POST["payrollType"];
    }

    $payRate = isset($_POST["payRate"]) ? $_POST["payRate"] : null;
    $annualSalary = isset($_POST["annualSalary"]) ? $_POST["annualSalary"] : null;

    // ================ B A N K I N G,  S U P E R,  &  T A X  D E T A I L S ================
    if (empty($_POST["bankBuildingSociety"])) {
        $errors['bankBuildingSociety'] = "Bank/Building Society is required";
    } else {
        $bankBuildingSociety = $_POST["bankBuildingSociety"];
    }

    if (empty($_POST["bsb"])) {
        $errors['bsb'] = "BSB is required";
    } else {
        $bsb = $_POST["bsb"];
    }

    if (empty($_POST["accountNumber"])) {
        $errors['accountNumber'] = "Account Number is required";
    } else {
        $accountNumber = $_POST["accountNumber"];
    }

    if (empty($_POST["superannuationFundName"])) {
        $errors['superannuationFundName'] = "Superannuation Fund Name is required";
    } else {
        $superannuationFundName = $_POST["superannuationFundName"];
    }

    if (empty($_POST["uniqueSuperannuationIdentifier"])) {
        $errors['uniqueSuperannuationIdentifier'] = "Unique Superannuation Identifier is required";
    } else {
        $uniqueSuperannuatioIdentifier = $_POST["uniqueSuperannuationIdentifier"];
    }

    if (empty($_POST["superannuationMemberNumber"])) {
        $errors['superannuationMemberNumber'] = "Superannuation Member Number is required";
    } else {
        $superannuationMemberNumber = $_POST["superannuationMemberNumber"];
    }

    if (empty($_POST["taxFileNumber"])) {
        $errors['taxFileNumber'] = "Tax File Number is required";
    } else {
        $taxFileNumber = $_POST["taxFileNumber"];
    }

    if (empty($_POST["higherEducationLoanProgramme"])) {
        $higherEducationLoanProgramme = 0; // default to 0 if not set
    } else {
        $higherEducationLoanProgramme = $_POST["higherEducationLoanProgramme"];
    }

    if (empty($_POST["financialSupplementDebt"])) {
        $financialSupplementDebt = 0; // default to 0 if not set
    } else {
        $financialSupplementDebt = $_POST["financialSupplementDebt"];
    }

    // Tool allowance
    if (isset($_POST['toolAllowance'])) {
        $toolAllowance = 1;
    } else {
        $toolAllowance = 0;
    }

    // Validate file upload
    if (isset($_FILES["profileImage"]) && $_FILES["profileImage"]["error"] == 0) {
        $profileImage = $_FILES["profileImage"];

        // Get the employee ID
        $employeeId = $_POST["employeeId"];

        // Extract file extension
        $imageExtension = pathinfo($profileImage["name"], PATHINFO_EXTENSION);

        // Generate the new filename based on employee ID
        $newFileName = $employeeId . '_profile.' . $imageExtension;

        $imagePath = "D:\\FSMBEH-Data\\09 - HR\\04 - Wage Staff\\" . $employeeId . "\\02 - Resume, ID and Qualifications\\" . $newFileName;
        move_uploaded_file($profileImage["tmp_name"], $imagePath);

        // Encode the image before insertion
        $encodedImage = base64_encode(file_get_contents($imagePath));
    } else {
        $encodedImage = ''; // Default empty image if no profile image is uploaded
    }

    // Set visa_expiry_date to null if the visaStatus is "Citizen" or "Permanent Resident"
    if ($visaStatus === "Citizen" || $visaStatus === "Permanent Resident") {
        $visaExpiryDate = null;
    }

    // Check if employee with the same employee id already exists
    $check_employee_sql = "SELECT COUNT(*) FROM employees WHERE employee_id = ?";
    $check_employee_stmt = $conn->prepare($check_employee_sql);
    $check_employee_stmt->bind_param("i", $employeeId);
    $check_employee_stmt->execute();
    $check_employee_stmt->bind_result($employee_count);
    $check_employee_stmt->fetch();
    $check_employee_stmt->close();

    echo $employee_count;

    if ($employee_count > 0) {
        echo "<script> alert('An employee with this employee Id already exists.')</script>";
    } else if (empty($errors)) {
        // If there are no errors, proceed with database insertion
        // Prepare and execute SQL statement to insert data into 'employees' table
        $sql = "INSERT INTO employees (first_name, last_name, nickname, gender, dob, visa, visa_expiry_date , address, email, personal_email, phone_number, plate_number, emergency_contact_phone_number, emergency_contact_name, emergency_contact_relationship, employee_id, start_date, employment_type, department, section, position, bank_building_society, bsb, account_number, superannuation_fund_name, unique_superannuation_identifier, superannuation_member_number, tax_file_number, higher_education_loan_programme, financial_supplement_debt, profile_image, payroll_type, tool_allowance) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssssssssssssisssssssssiissi", $firstName, $lastName, $nickname, $gender, $dob, $visaStatus, $visaExpiryDate, $address, $email, $personalEmail, $phoneNumber, $vehicleNumberPlate, $emergencyContact, $emergencyContactName, $emergencyContactRelationship, $employeeId, $startDate, $employmentType, $department, $section, $position, $bankBuildingSociety, $bsb, $accountNumber, $superannuationFundName, $uniqueSuperannuatioIdentifier, $superannuationMemberNumber, $taxFileNumber, $higherEducationLoanProgramme, $financialSupplementDebt, $encodedImage, $payrollType, $toolAllowance);
        // Execute the prepared statement for inserting into the 'employees' table
        if ($stmt->execute()) {
            echo "Employee data inserted successfully.";
            echo "<script> alert('Employee data inserted successfully.')</script>";
        } else {
            echo "Error: " . $stmt->error;
        }
    } else {
        print_r($errors);
    }

    $currentDate = date('Y-m-d');

    if ($payrollType === "wage") {
        // Prepare and execute SQL statement to insert data into 'wages' table
        $insert_wages_sql = "INSERT INTO wages(employee_id, amount, date)  VALUES (?, ?, ?)";
        $insert_wages_result = $conn->prepare($insert_wages_sql);
        $insert_wages_result->bind_param("sds", $employeeId, $payRate, $currentDate);

        // Execute the prepared statement for inserting into the 'wages' table
        if ($insert_wages_result->execute()) {
            echo "New wages record inserted successfully.";
            // Redirect after successful insertion
            echo '<script>window.location.replace("http://' . $serverAddress . '/' . $projectName . '/Pages/employee-list-index.php");</script>';
        } else {
            echo "Error: " . $insert_wages_result->error;
        }
    } else if ($payrollType === "salary") {
        // Prepare and execute SQL statement to insert data into 'salary' table
        $insert_salaries_sql = "INSERT INTO salaries(employee_id, amount, date) VALUES (?, ?, ?)";
        $insert_salaries_result = $conn->prepare($insert_salaries_sql);
        $insert_salaries_result->bind_param("sds", $employeeId, $annualSalary, $currentDate);

        // Execute the prepared statement for inserting into the 'wages' table
        if ($insert_salaries_result->execute()) {
            echo "New salaries record inserted successfully.";
            // Redirect after successful insertion
            echo '<script>window.location.replace("http://' . $serverAddress . '/' . $projectName . '/Pages/employee-list-index.php");</script>';
        } else {
            echo "Error: " . $insert_salaries_result->error;
        }
    }

    // Close prepared statements and database connection
    $stmt->close();
    $conn->close();
}
?>

<head>
    <link rel="stylesheet" type="text/css" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="shortcut icon" type="image/x-icon" href="Images/FE-logo-icon.ico" />

    <style>
        /* Style for checked state */
        .btn-check:checked+.btn-custom {
            background-color: #043f9d !important;
            border-color: #043f9d !important;
            color: white !important;
            /* Text color when selected */
        }

        /* Optional: Adjust hover state if needed */
        .btn-custom:hover {
            background-color: #032b6b;
            border-color: #032b6b;
            color: white;
        }

        /* Optional: Adjust focus state if needed
        .btn-check:focus+.btn-custom {
            box-shadow: 0 0 0 0.25rem rgba(4, 63, 157, 0.5);
        } */

        #visaStatusOptionsModal {
            background-color: rgba(128, 128, 128, 0.50);
            /* Grey color with 75% opacity */
            backdrop-filter: blur(2px);
            /* Apply blur effect */
        }

        @media (max-width: 992px) {
            #visaStatusOptionsModal .modal-content {
                border-radius: 10px;
            }

            #visaStatusOptionsModal .modal-content {
                max-height: none;
                height: auto;
                overflow-y: auto;
            }
        }
    </style>
</head>

<div class="row-cols-1 row-cols-md-3">
    <div class="bg-white rounded col-12 col-md-12">

        <form method="POST" enctype="multipart/form-data" id="addEmployeeForm" class="needs-validation" novalidate>
            <div class="row">

                <div class="container px-5">
                    <p class="error-message alert alert-danger text-center d-none p-1" style="font-size: 1.5vh;"
                        id="duplicateErrorMessage"></p>
                </div>

                <div class="col-12 col-lg-6 px-md-5">

                    <!-- ================ P E R S O N A L   D E T A I L S ================  -->
                    <div class="row">
                        <p class="signature-color fw-bold">Personal Details</p>
                        <div class="form-group col-md-6">
                            <label for="firstName" class="fw-bold"><small>First Name</small></label>
                            <input type="text" name="firstName" class="form-control" id="firstName" required>
                            <div class="invalid-feedback">
                                Please provide a first name.
                            </div>
                        </div>

                        <div class="form-group col-md-6 mt-3 mt-md-0">
                            <label for="lastName" class="fw-bold"><small>Last Name</small></label>
                            <input type="text" name="lastName" class="form-control" id="lastName" required>
                            <div class="invalid-feedback">
                                Please provide a last name.
                            </div>
                        </div>

                        <div class="form-group col-md-3 mt-3">
                            <label for="nickname" class="fw-bold"><small>Nickname</small></label>
                            <input type="text" name="nickname" class="form-control" id="nickname">
                        </div>

                        <div class="form-group col-md-3 mt-3">
                            <label for="gender" class="fw-bold"><small>Gender</small></label>
                            <select class="form-select" aria-label="gender" name="gender" required>
                                <option disabled selected hidden> </option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                            <div class="invalid-feedback">
                                Please select a gender.
                            </div>
                        </div>

                        <div class="form-group col-md-6 mt-3">
                            <label for="dob" class="fw-bold"><small>Date of Birth</small></label>
                            <input type="date" max="9999-12-31" name="dob" class="form-control" id="dob" required>
                            <div class="invalid-feedback">
                                Please provide a date of birth.
                            </div>
                        </div>

                        <div class="form-group col-md-6 mt-3">
                            <label for="visaStatus" class="fw-bold"><small>Visa Status</small></label>
                            <select class="form-select" id="visaStatus" aria-label="Visa Status" name="visaStatus"
                                required>
                                <option disabled selected hidden> </option>
                                <?php
                                if ($visa_status_result->num_rows > 0) {
                                    while ($row = $visa_status_result->fetch_assoc()) {
                                        $visaId = $row['visa_id'];
                                        $visaName = $row['visa_name'];
                                        ?>
                                        <option value="<?= $visaId ?>"><?= $visaName ?></option>
                                    <?php }
                                } ?>
                                <option value="Other">Other</option>
                            </select>
                            <div class="invalid-feedback">
                                Please select a visa status.
                            </div>
                        </div>

                        <div class="form-group col-md-6 mt-3 d-none" id="otherVisaStatusInput">
                            <label for="otherVisaStatus" class="fw-bold"><small>Other Visa Status</small></label>
                            <input class="form-control" type="text" name="otherVisaStatus" id="otherVisaStatus">
                            <div class="invalid-feedback">
                                Please select the other visa status.
                            </div>
                        </div>

                        <div class="form-group col-md-6 mt-3">
                            <label for="visaExpiryDate" class="fw-bold"><small>Visa Expiry Date</small></label>
                            <input type="date" max="9999-12-31" name="visaExpiryDate" class="form-control"
                                id="visaExpiryDate">
                            <p class="text-danger d-none" id="visaNote"></p>
                            <div class="invalid-feedback">
                                Please provide a visa expiry date.
                            </div>
                        </div>

                        <div class="form-group col-md-12 mt-3">
                            <label for="profileImage" class="fw-bold"><small>Upload Profile Image</small></label>
                            <div class="input-group">
                                <input type="file" id="profileImage" name="profileImage" class="form-control">
                            </div>
                            <div class="invalid-feedback d-none" id="profileImageInvalidFeedback">
                                Invalid file type. Please upload a JPG, JPEG, or PNG file.
                            </div>
                        </div>
                    </div>

                    <!-- ================ C O N T A C T S ================  -->
                    <div class="row">
                        <p class="signature-color fw-bold mt-5">Contacts</p>
                        <div class="form-group col-md-12">
                            <label for="address" class="fw-bold"><small>Address</small></label>
                            <input type="text" class="form-control" id="address" name="address" required>
                            <div class="invalid-feedback">
                                Please provide an address.
                            </div>
                        </div>

                        <div class="form-group col-md-12 mt-3">
                            <label for="email" class="fw-bold"><small>Email</small></label>
                            <input type="text" class="form-control" id="email" name="email">
                            <div class="invalid-feedback">
                                Please provide a valid email address.
                            </div>
                        </div>

                        <div class="form-group col-md-12 mt-3">
                            <label for="personalEmail" class="fw-bold"><small>Personal Email</small></label>
                            <input type="text" class="form-control" id="personalEmail" name="personalEmail" required>
                            <div class="invalid-feedback">
                                Please provide a valid personal email address.
                            </div>
                        </div>

                        <div class="form-group col-md-6 mt-3">
                            <label for="phoneNumber" class="fw-bold"><small>Mobile</small></label>
                            <input type="text" class="form-control" id="phoneNumber" name="phoneNumber" required>
                            <div class="invalid-feedback">
                                Please provide a mobile number.
                            </div>
                        </div>

                        <div class="form-group col-md-6 mt-3">
                            <label for="vehicleNumberPlate" class="fw-bold"><small>Vehicle Number Plate</small></label>
                            <input type="text" class="form-control" id="vehicleNumberPlate" name="vehicleNumberPlate">
                        </div>
                    </div>

                    <!-- ================ E M E R G E N C Y  C O N T A C T S ================  -->
                    <div class="row">
                        <p class="signature-color fw-bold mt-5">Emergency Contact</p>

                        <div class="form-group col-md-6">
                            <label for="emergencyContactName" class="fw-bold"><small>Emergency Contact
                                    Name</small></label>
                            <input type="text" class="form-control" id="emergencyContactName"
                                name="emergencyContactName" required>
                            <div class="invalid-feedback">
                                Please provide the emergency contact name.
                            </div>
                        </div>

                        <div class="form-group col-md-6 mt-3 mt-md-0">
                            <label for="emergencyContact" class="fw-bold"><small>Emergency Contact
                                    Mobile</small></label>
                            <input type="text" class="form-control" id="emergencyContact" name="emergencyContact"
                                required>
                            <div class="invalid-feedback">
                                Please provide the emergency contact mobile number.
                            </div>
                        </div>

                        <div class="form-group col-md-6 mt-3">
                            <label for="emergencyContactRelationship"
                                class="fw-bold"><small>Relationship</small></label>
                            <input type="text" class="form-control" id="emergencyContactRelationship"
                                name="emergencyContactRelationship" required>
                            <div class="invalid-feedback">
                                Please provide the relationship with the emergency contact.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-6 px-md-5 mt-5 mt-lg-0">
                    <!-- ================ E M P L O Y M E N T   D E T A I L S ================  -->
                    <div class="row">
                        <p class="signature-color fw-bold">Employment Details</p>
                        <div class="form-group col-md-3">
                            <label for="employeeId" class="fw-bold"><small>Employee Id</small></label>
                            <input type="text" pattern="^\d{3}$" class="form-control" id="employeeId" name="employeeId"
                                required>
                            <div class="invalid-feedback">
                                Please provide an employee ID.
                            </div>
                        </div>

                        <div class="form-group col-md-5">
                            <label for="startDate" class="fw-bold mt-3 mt-md-0"><small>Date Hired</small></label>
                            <input type="date" max="9999-12-31" class="form-control" id="startDate" name="startDate"
                                required>
                            <div class="invalid-feedback">
                                Please provide a hire date.
                            </div>
                        </div>

                        <div class="form-group col-md-4">
                            <label for="employmentType" class="fw-bold mt-3 mt-md-0"><small>Employment
                                    Type</small></label>
                            <select class="form-select" aria-label="Employment Type" name="employmentType" required>
                                <option disabled selected hidden> </option>
                                <option value="Full-Time">Full-Time</option>
                                <option value="Part-Time">Part-Time</option>
                                <option value="Casual">Casual</option>
                            </select>
                            <div class="invalid-feedback">
                                Please select an employment type.
                            </div>
                        </div>

                        <div class="form-group col-md-4 mt-3">
                            <label for="department" class="fw-bold"><small>Department</small></label>
                            <select class="form-select" aria-label="Department" name="department" id="department"
                                required>
                                <option disabled selected hidden></option>
                                <?php
                                if ($department_result->num_rows > 0) {
                                    while ($row = $department_result->fetch_assoc()) {
                                        $departmentId = $row['department_id'];
                                        $departmentName = $row['department_name'];
                                        ?>
                                        <option value="<?= $departmentId ?>"> <?= $departmentName ?></option>
                                    <?php }
                                } ?>
                                <option value="Other">Other</option>
                            </select>
                            <div class="invalid-feedback">
                                Please select a department.
                            </div>
                        </div>

                        <div class="form-group col-md-4 mt-3 d-none" id="otherDepartmentInput">
                            <label for="otherDepartment" class="fw-bold"><small>Other Department</small></label>
                            <input class="form-control" type="text" name="otherDepartment" id="otherDepartment">
                            <div class="invalid-feedback">
                                Please select the other department.
                            </div>
                        </div>

                        <div class="form-group col-md-4 mt-3" id="sectionField">
                            <label for="section" class="fw-bold"><small>Section</small></label>
                            <select class="form-select" aria-label="Section" name="section" id="section" required>
                                <option disabled selected hidden></option>
                                <option value="Office">Office</option>
                                <option value="Factory">Factory</option>
                            </select>
                            <div class="invalid-feedback">
                                Please select a section.
                            </div>
                        </div>

                        <div class="form-group col-md-4 mt-3">
                            <label for="position" class="fw-bold"><small>Position</small></label>
                            <select class="form-select" aria-label="Position" name="position" id="position" required>
                                <option disabled selected hidden></option>
                                <?php
                                if ($position_result->num_rows > 0) {
                                    while ($row = $position_result->fetch_assoc()) {
                                        $positionId = $row['position_id'];
                                        $positionName = $row['position_name'];
                                        ?>
                                        <option value="<?= $positionId ?>"><?= $positionName ?></option>
                                    <?php }
                                }
                                ?>
                                <option value="Other">Other</option>
                            </select>
                            <!-- <input type="text" class="form-control" id="position" name="position" required> -->
                            <div class="invalid-feedback">
                                Please provide a position.
                            </div>
                        </div>

                        <div class="form-group col-md-4 mt-3 d-none" id="otherPositionInput">
                            <label for="otherPosition" class="fw-bold"><small>Other Position</small></label>
                            <input class="form-control" type="text" name="otherPosition" id="otherPosition">
                            <div class="invalid-feedback">
                                Please select the other position.
                            </div>
                        </div>

                        <div class="form-group col-md-6 mt-3">
                            <label for="payrollType" class="fw-bold"><small>Payroll</small></label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payrollType" id="wageRadio"
                                    value="wage" required>
                                <label class="form-check-label" for="wageRadio"> Wage </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payrollType" id="salaryRadio"
                                    value="salary" required>
                                <label class="form-check-label" for="salaryRadio"> Salary </label>
                            </div>
                        </div>

                        <!-- <div id="wageInput" class="d-none">
                            <p> You choose wage!</p>
                            <input type="text" id="wageTextInput" class="form-control" required>
                        </div> -->

                        <!-- <div id="salaryInput" class="d-none">
                            <p> You choose salary!</p>
                            <input type="text" id="salaryTextInput" class="form-control" required>
                        </div> -->

                        <div class="form-group col-md-6 mt-3 d-none" id="payRateInput">
                            <label for="payRate" class="fw-bold"><small>Pay Rate</small></label>
                            <div class="input-group">
                                <span class="input-group-text rounded-start">$</span>
                                <input type="number" min="0" step="any" class="form-control rounded-end" id="payRate"
                                    name="payRate" aria-describedby="payRate">
                                <div class="invalid-feedback">
                                    Please provide the Pay Rate.
                                </div>
                            </div>

                            <input class="form-check-input" type="checkbox" id="toolAllowanceCheckbox"
                                name="toolAllowance">
                            <label for="form-check-label mb-0 pb-0">
                                <small class="mt-2">Tool Allowance</small>
                            </label>
                        </div>

                        <div class="form-group col-md-6 mt-3 d-none" id="salaryInput">
                            <label for="annualSalary" class="fw-bold"><small>Annual Salary</small></label>
                            <div class="input-group">
                                <span class="input-group-text rounded-start">$</span>
                                <input type="number" min="0" step="any" class="form-control rounded-end"
                                    id="annualSalary" name="annualSalary" aria-describedby="annualSalary">
                                <div class="invalid-feedback">
                                    Please provide the Annual Salary.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ================ B A N K,  S U P E R,  &  T A X ================  -->
                    <div class="row">
                        <p class="signature-color fw-bold mt-5">Banking, Super and Tax Details</p>
                        <div class="form-group col-md-12">
                            <label for="bankBuildingSociety" class="fw-bold"><small>Bank/Building
                                    Society</small></label>
                            <input type="text" name="bankBuildingSociety" class="form-control" id="bankBuildingSociety"
                                required>
                            <div class="invalid-feedback">
                                Please provide the Bank/Building Society.
                            </div>
                        </div>

                        <div class="form-group col-md-4 mt-3">
                            <label for="bsb" class="fw-bold"><small>BSB</small></label>
                            <input type="number" name="bsb" class="form-control" id="bsb" required>
                            <div class="invalid-feedback">
                                Please provide the BSB.
                            </div>
                        </div>

                        <div class="form-group col-md-8 mt-3">
                            <label for="accountNumber" class="fw-bold"><small>Account Number</small></label>
                            <input type="number" name="accountNumber" class="form-control" id="accountNumber" required>
                            <div class="invalid-feedback">
                                Please provide the account number.
                            </div>
                        </div>

                        <div class="form-group col-md-12 mt-3">
                            <label for="superannuationFundName" class="fw-bold"><small>Superannuation Fund
                                    Name</small></label>
                            <input type="text" name="superannuationFundName" class="form-control"
                                id="superannuationFundName" required>
                            <div class="invalid-feedback">
                                Please provide the Superannuation Fund Name.
                            </div>
                        </div>

                        <div class="form-group col-md-6 mt-3">
                            <label for="uniqueSuperannuationIdentifier" class="fw-bold"><small>Unique Superannuation
                                    Identifier</small></label>
                            <input type="text" name="uniqueSuperannuationIdentifier" class="form-control"
                                id="uniqueSuperannuationIdentifier" required>
                            <div class="invalid-feedback">
                                Please provide the Unique Superannuation Identifier
                            </div>
                        </div>

                        <div class="form-group col-md-6 mt-3">
                            <label for="superannuationMemberNumber" class="fw-bold"><small>Superannuation Member
                                    Number</small></label>
                            <input type="text" name="superannuationMemberNumber" class="form-control"
                                id="superannuationMemberNumber" required>
                            <div class="invalid-feedback">
                                Please provide the Superannuation Member Number.
                            </div>
                        </div>

                        <div class="form-group col-md-6 mt-3">
                            <label for="taxFileNumber" class="fw-bold"><small>Tax File Number</small></label>
                            <input type="number" name="taxFileNumber" class="form-control" id="taxFileNumber" required>
                            <div class="invalid-feedback">
                                Please provide the tax file number.
                            </div>
                        </div>
                        <div class="form-group col-md-12 mt-3">
                            <div class="d-flex flex-column">
                                <label for="higherEducationLoanProgramme" class="fw-bold"><small>Higher Education Loan
                                        Programme?</small></label>
                                <div class="btn-group col-3 col-md-2" role="group">
                                    <input type="radio" class="btn-check" name="higherEducationLoanProgramme"
                                        id="higherEducationLoanProgrammeYes" value="1" autocomplete="off" required>
                                    <label class="btn btn-sm btn-custom" for="higherEducationLoanProgrammeYes"
                                        style="color:#043f9d; border: 1px solid #043f9d">Yes</label>
                                    <input type="radio" class="btn-check" name="higherEducationLoanProgramme"
                                        id="higherEducationLoanProgrammeNo" value="0" autocomplete="off" checked
                                        required>
                                    <label class="btn btn-sm btn-custom" for="higherEducationLoanProgrammeNo"
                                        style="color:#043f9d; border: 1px solid #043f9d">No</label>
                                </div>
                            </div>
                            <div class="invalid-feedback d-none" id="higherEducationLoanProgrammeInvalidFeedback">
                                Please provide the Higher Education Loan Programme.
                            </div>
                        </div>

                        <div class="form-group col-md-12 mt-3">
                            <div class="d-flex flex-column">
                                <label for="financialSupplementDebt" class="fw-bold"><small>Financial Supplement
                                        Debt?</small></label>
                                <div class="btn-group col-3 col-md-2" role="group">
                                    <input type="radio" class="btn-check btn-custom" name="financialSupplementDebt"
                                        id="financialSupplementDebtYes" value="1" autocomplete="off" required>
                                    <label class="btn btn-sm btn-custom" for="financialSupplementDebtYes"
                                        style="color:#043f9d; border: 1px solid #043f9d">Yes</label>
                                    <input type="radio" class="btn-check btn-custom" name="financialSupplementDebt"
                                        id="financialSupplementDebtNo" value="0" autocomplete="off" checked required>
                                    <label class="btn btn-sm btn-custom" for="financialSupplementDebtNo"
                                        style="color:#043f9d; border: 1px solid #043f9d">No</label>
                                </div>
                            </div>
                            <div class="invalid-feedback d-none" id="financialSupplementDebtInvalidFeedback">
                                Please provide the Financial Supplement Debt.
                            </div>
                        </div>


                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-center mt-5">
                <button class="btn btn-dark">Add Employee</button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('.needs-validation');
            const financialSupplementDebtYes = document.getElementById("financialSupplementDebtYes");
            const financialSupplementDebtNo = document.getElementById("financialSupplementDebtNo");
            const financialSupplementDebtInvalidFeedback = document.getElementById("financialSupplementDebtInvalidFeedback");

            const higherEducationLoanProgrammeYes = document.getElementById("higherEducationLoanProgrammeYes");
            const higherEducationLoanProgrammeNo = document.getElementById("higherEducationLoanProgrammeNo");
            const higherEducationLoanProgrammeInvalidFeedback = document.getElementById("higherEducationLoanProgrammeInvalidFeedback");

            const profileImageInput = document.getElementById("profileImage");
            const profileImageInvalidFeedback = document.getElementById("profileImageInvalidFeedback");

            const emailInput = document.getElementById("email");
            const personalEmailInput = document.getElementById("personalEmail");
            const employeeIdInput = document.getElementById("employeeId");
            const duplicateErrorMessage = document.getElementById('duplicateErrorMessage');

            // Function to check duplicate employee ID
            async function checkDuplicateEmployeeId() {
                const employeeId = employeeIdInput.value.trim();

                try {
                    const response = await fetch('../AJAXphp/check-emp-id-duplicate.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            employeeId: employeeId
                        })
                    });

                    const data = await response.text();
                    console.log('Server Response:', data);

                    if (data.includes("Duplicate employee ID found.")) {
                        duplicateErrorMessage.innerHTML = data;
                        duplicateErrorMessage.classList.remove("d-none");
                        duplicateErrorMessage.classList.add("d-block");
                        return false; // Duplicate found
                    } else if (data.includes("No duplicate found.")) {
                        duplicateErrorMessage.classList.remove("d-block");
                        duplicateErrorMessage.classList.add("d-none");
                        duplicateErrorMessage.innerHTML = ""; // Clear any previous messages
                        return true; // No duplicate
                    } else {
                        console.error('Unexpected response:', data);
                        duplicateErrorMessage.innerHTML = "Unexpected response from the server.";
                        return false; // Assume validation failure
                    }
                } catch (error) {
                    console.error('Fetch error:', error);
                    duplicateErrorMessage.innerHTML = "An error occurred while processing the request.";
                    return false; // Assume validation failure
                }
            }

            function checkEmailInput() {
                const emailInputValue = emailInput.value;
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (emailInputValue !== "") {
                    if (emailPattern.test(emailInputValue)) {
                        console.log('Valid email address');
                        emailInput.classList.remove('is-invalid');
                        emailInput.classList.add('is-valid');
                        return true;
                    } else {
                        console.log('Invalid email address');
                        emailInput.classList.remove('is-valid');
                        emailInput.classList.add('is-invalid');
                        return false;
                    }
                }
                return true;
            }

            function checkPersonalEmailInput() {
                const personalEmailInputValue = personalEmailInput.value;
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                if (emailPattern.test(personalEmailInputValue)) {
                    personalEmailInput.classList.remove('is-invalid');
                    personalEmailInput.classList.add('is-valid');
                    return true;
                } else {
                    personalEmailInput.classList.remove('is-valid');
                    personalEmailInput.classList.add('is-invalid');
                    return false;
                }
            }

            function checkProfileImageInput() {
                if (profileImageInput.files.length === 0) {
                    console.log("No file selected");
                    profileImageInvalidFeedback.classList.remove("d-block");
                    profileImageInvalidFeedback.classList.add("d-none");
                    return true;
                }

                const file = profileImageInput.files[0];
                const fileName = file.name.toLowerCase();
                const allowedExtensions = ['jpg', 'jpeg', 'png'];
                const fileExtension = fileName.split('.').pop();

                if (allowedExtensions.includes(fileExtension)) {
                    console.log('File is valid');
                    profileImageInvalidFeedback.classList.remove("d-block");
                    profileImageInvalidFeedback.classList.add("d-none");
                    profileImageInput.classList.remove('is-invalid');
                    profileImageInput.classList.add('is-valid');
                    return true;
                } else {
                    console.log('File is invalid');
                    profileImageInvalidFeedback.classList.remove("d-none");
                    profileImageInvalidFeedback.classList.add("d-block");
                    profileImageInput.classList.remove('is-valid');
                    profileImageInput.classList.add('is-invalid');
                    return false;
                }
            }

            function checkFinancialSupplementDebtRadioSelection() {
                if (!financialSupplementDebtYes.checked && !financialSupplementDebtNo.checked) {
                    financialSupplementDebtInvalidFeedback.classList.remove("d-none");
                    financialSupplementDebtInvalidFeedback.classList.add("d-block");
                    return false; // Indicates validation failure
                } else {
                    financialSupplementDebtInvalidFeedback.classList.remove("d-block");
                    financialSupplementDebtInvalidFeedback.classList.add("d-none");
                    return true; // Indicates validation success
                }
            }

            function checkHigherEducationLoanProgrammeRadioSelection() {
                if (!higherEducationLoanProgrammeYes.checked && !higherEducationLoanProgrammeNo.checked) {
                    higherEducationLoanProgrammeInvalidFeedback.classList.remove("d-none");
                    higherEducationLoanProgrammeInvalidFeedback.classList.add("d-block");
                    return false; // Indicates validation failure
                } else {
                    higherEducationLoanProgrammeInvalidFeedback.classList.remove("d-block");
                    higherEducationLoanProgrammeInvalidFeedback.classList.add("d-none");
                    return true; // Indicates validation success
                }
            }

            async function validateForm() {
                let isValid = true;

                // Check if the email is valid
                if (!checkEmailInput()) {
                    isValid = false;
                }

                // Check if the personal email is valid
                if (!checkPersonalEmailInput()) {
                    isValid = false;
                }

                // Check if the profile image is valid
                if (!checkProfileImageInput()) {
                    isValid = false;
                }

                // Check radio button selections
                if (!checkFinancialSupplementDebtRadioSelection() || !checkHigherEducationLoanProgrammeRadioSelection()) {
                    isValid = false;
                }

                // Check for duplicate employee ID
                if (!(await checkDuplicateEmployeeId())) {
                    isValid = false;
                }

                // Check if the form itself is valid (HTML5 validation)
                if (isValid && !form.checkValidity()) {
                    isValid = false;
                }

                return isValid;
            }

            form.addEventListener('submit', async function (event) {
                // Prevent default form submission
                event.preventDefault();
                event.stopPropagation();

                // Perform validation
                const isValid = await validateForm();

                if (isValid) {
                    // If form is valid, submit the form
                    form.submit();
                } else {
                    form.classList.add('was-validated');
                }
            }, false);

            // Add event listeners to elements for real-time validation
            financialSupplementDebtYes.addEventListener('change', checkFinancialSupplementDebtRadioSelection);
            financialSupplementDebtNo.addEventListener('change', checkFinancialSupplementDebtRadioSelection);
            higherEducationLoanProgrammeYes.addEventListener('change', checkHigherEducationLoanProgrammeRadioSelection);
            higherEducationLoanProgrammeNo.addEventListener('change', checkHigherEducationLoanProgrammeRadioSelection);
            emailInput.addEventListener('input', checkEmailInput);
            personalEmailInput.addEventListener('input', checkPersonalEmailInput);
            profileImageInput.addEventListener('change', checkProfileImageInput);
        });

    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const wageRadio = document.getElementById("wageRadio");
            const salaryRadio = document.getElementById("salaryRadio");
            const payRateInput = document.getElementById("payRateInput");
            const salaryInput = document.getElementById("salaryInput");
            const payRate = document.getElementById("payRate");
            const annualSalary = document.getElementById("annualSalary");

            function payrollType() {
                if (wageRadio.checked) {
                    console.log("Wage Radio Checked!");
                    payRateInput.classList.remove("d-none");
                    salaryInput.classList.add("d-none");
                    payRateInput.querySelector('input').required = true;
                    salaryInput.querySelector('input').required = false;
                    payRate.value = "";

                } else if (salaryRadio.checked) {
                    console.log("Salary Radio Checked!! ");
                    salaryInput.classList.remove("d-none");
                    payRateInput.classList.add("d-none");
                    salaryInput.querySelector('input').required = true;
                    payRateInput.querySelector('input').required = false;
                    annualSalary.value = "";
                }
            }

            wageRadio.addEventListener('change', payrollType);
            salaryRadio.addEventListener('change', payrollType);
        })
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const visaStatusSelect = document.getElementById("visaStatus");
            const visaExpiryDate = document.getElementById("visaExpiryDate");
            const otherVisaStatusInput = document.querySelector("#otherVisaStatusInput");
            const otherVisaStatus = document.querySelector("#otherVisaStatus");
            const bridgingVisaNote = document.querySelector("#bridgingVisaNote");
            const dateHired = document.querySelector("#startDate");
            const visaNote = document.querySelector("#visaNote");

            // Function to update visa expiry and other visa status input based on selected option
            function updateVisaFields() {
                const selectedOptionText = visaStatusSelect.options[visaStatusSelect.selectedIndex].text;

                if (selectedOptionText === "Permanent Resident" || selectedOptionText === "Citizen") {
                    visaExpiryDate.disabled = true;
                    visaExpiryDate.required = false;
                    otherVisaStatus.required = false;
                    visaExpiryDate.value = "";
                    otherVisaStatus.value = "";
                    otherVisaStatusInput.classList.add("d-none");
                    otherVisaStatusInput.classList.remove("d-block");
                    visaNote.classList.add("d-none");
                    visaNote.classList.remove("d-block");
                } else if (selectedOptionText === "Bridging") {
                    visaExpiryDate.required = true;
                    visaNote.innerHTML = "<small><sup>*</sup><b>Bridging visa</b> need to be checked every six months.</small>";
                    visaNote.classList.remove("d-none");
                    visaNote.classList.add("d-block");
                    visaExpiryDate.disabled = false;
                    visaExpiryDate.required = true;
                    otherVisaStatus.required = false;
                    visaExpiryDate.value = "";
                    otherVisaStatus.value = "";
                    otherVisaStatusInput.classList.add("d-none");
                    otherVisaStatusInput.classList.remove("d-block");
                } else if (selectedOptionText === "Working Holiday") {
                    visaExpiryDate.required = true;
                    visaNote.innerHTML = "<small><sup>*</sup><b>Working Holiday visa</b> need to be checked every six months.</small>";
                    visaNote.classList.remove("d-none");
                    visaNote.classList.add("d-block");
                    visaExpiryDate.disabled = false;
                    visaExpiryDate.required = true;
                    otherVisaStatus.required = false;
                    visaExpiryDate.value = "";
                    otherVisaStatus.value = "";
                    otherVisaStatusInput.classList.add("d-none");
                    otherVisaStatusInput.classList.remove("d-block");
                } else if (selectedOptionText === "Other") {
                    visaExpiryDate.disabled = false;
                    visaExpiryDate.required = true;
                    otherVisaStatus.required = true;
                    visaExpiryDate.value = "";
                    otherVisaStatus.value = "";
                    otherVisaStatusInput.classList.remove("d-none");
                    otherVisaStatusInput.classList.add("d-block");
                    visaNote.classList.add("d-none");
                    visaNote.classList.remove("d-block");
                } else {
                    visaExpiryDate.disabled = false;
                    visaExpiryDate.required = true;
                    otherVisaStatus.required = false;
                    visaExpiryDate.value = "";
                    otherVisaStatus.value = "";
                    otherVisaStatusInput.classList.add("d-none");
                    otherVisaStatusInput.classList.remove("d-block");
                    visaNote.classList.add("d-none");
                    visaNote.classList.remove("d-block");
                }
            }
            // Attach change event listener
            visaStatusSelect.addEventListener('change', updateVisaFields);

            // Initialize fields based on the currently selected option
            updateVisaFields();

            function updateVisaExpiryDateFields() {
                const selectedOptionText = visaStatusSelect.options[visaStatusSelect.selectedIndex].text;
                const hiredDate = new Date(dateHired.value)
                if (selectedOptionText === "Bridging" || selectedOptionText === "Working Holiday") {
                    // Add 6 months to the date
                    hiredDate.setMonth(hiredDate.getMonth() + 6);

                    // Format the date as "YYYY-MM-DD" to set it as input value
                    const formattedExpiryDate = hiredDate.toISOString().split("T")[0];
                    visaExpiryDate.value = formattedExpiryDate;
                }
            }

            // Attach change event listener
            dateHired.addEventListener('input', updateVisaExpiryDateFields);

            // Initialize visa expiry fields based on the currently selected option
            updateVisaExpiryDateFields();
        });

    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const positionSelect = document.getElementById("position");
            const otherPositionInput = document.querySelector("#otherPositionInput");
            const otherPosition = document.querySelector("#otherPosition");

            // Function to update position input based on selected option
            function updatePositionFields() {
                const selectedOptionText = positionSelect.options[positionSelect.selectedIndex].text;

                if (selectedOptionText === "Other") {
                    otherPosition.required = true;
                    otherPosition.value = "";
                    otherPositionInput.classList.remove("d-none");
                    otherPositionInput.classList.add("d-block");
                } else {
                    otherPosition.required = false;
                    otherPosition.value = "";
                    otherPositionInput.classList.add("d-none");
                    otherPositionInput.classList.remove("d-block");
                }
            }

            // Attach change event listener
            positionSelect.addEventListener('change', updatePositionFields);

            // Initialize fields based on the currently selected option
            updatePositionFields();
        })
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const departmentSelect = document.getElementById("department");
            const otherDepartmentInput = document.querySelector("#otherDepartmentInput");
            const otherDepartment = document.querySelector("#otherDepartment");

            // Function to update department department input based on the selected option
            function updateDepartmentFields() {
                const selectedOptionText = departmentSelect.options[departmentSelect.selectedIndex].text;

                if (selectedOptionText === "Other") {
                    otherDepartment.required = true;
                    otherDepartment.value = "";
                    otherDepartmentInput.classList.remove("d-none");
                    otherDepartmentInput.classList.add("d-block");
                } else {
                    otherDepartment.required = false;
                    otherDepartment.value = "";
                    otherDepartmentInput.classList.add("d-none");
                    otherDepartmentInput.classList.remove("d-block");
                }
            }

            // Attach change event listener
            departmentSelect.addEventListener('change', updateDepartmentFields)

            // Initialize fields based on the currently selected option
            updateDepartmentFields();
        })
    </script>

    <!-- <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toolAllowanceCheckbox = document.getElementById("toolAllowanceCheckbox");

            toolAllowanceCheckbox.addEventListener("change", function () {
                if (toolAllowanceCheckbox.checked === true) {
                    console.log("True");
                } else if (toolAllowanceCheckbox.checked === false) {
                    console.log("False");
                }
            })
        })
    </script> -->


</div>