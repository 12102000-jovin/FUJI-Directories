<?php
    // ========================= E D I T  P R O F I L E  =========================
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        // Check if the form was submitted
        if (isset($_POST['editEmployeeProfile'])) {
            // Process the form data
            $employeeIdToEdit = $_POST['employeeIdToEdit'];
            $firstName = $_POST['firstName'];
            $lastName = $_POST['lastName'];
            $nickname = $_POST['nickname'];
            $gender = $_POST['gender'];
            $dob = $_POST['dob'];
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
            $visaExpiryDate = $_POST['visaExpiryDate'];
            $address = $_POST['address'];
            $email = !empty($_POST['email']) ? $_POST['email'] : null;
            $personalEmail = !empty($_POST['personalEmail']) ? $_POST['personalEmail'] : null;
            $phoneNumber = $_POST['phoneNumber'];
            $vehicleNumberPlate = isset($_POST['vehicleNumberPlate']) && $_POST['vehicleNumberPlate'] !== "" ? $_POST['vehicleNumberPlate'] : null;
            $emergencyContactName = $_POST['emergencyContactName'];
            $emergencyContact = $_POST['emergencyContact'];
            $emergencyContactRelationship = $_POST['emergencyContactRelationship'];
            $employeeId = $_POST['employeeId'];
            $startDate = $_POST['startDate'];
            $employmentType = $_POST['employmentType'];
        
            if (empty($_POST["department"])) {
                $errors['department'] = "Department is required";
            } else {
                $department = $_POST["department"];

                // Check for "Other" department
                if ($department === "Other") {
                    if(empty($_POST['otherDepartment'])) {
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

            $section = $_POST['section'];
           
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

            $dietaryRestrictions = $_POST["dietaryRestrictions"];
            $workShift = $_POST["workShift"];
            $lockerNumber = !empty($_POST["lockerNumber"]) ? $_POST["lockerNumber"] : null;
            $permanentDate = !empty($_POST['permanentDate']) ? $_POST['permanentDate'] : null;
            $payrollType = $_POST['payrollType'];
            $bankBuildingSociety = $_POST['bankBuildingSociety'];
            $bsb = $_POST['bsb'];
            $accountNumber = $_POST['accountNumber'];
            $uniqueSuperannuationIdentifier = $_POST['uniqueSuperannuationIdentifier'];
            $superannuationFundName = $_POST['superannuationFundName'];
            $superannuationMemberNumber = $_POST['superannuationMemberNumber'];
            $taxFileNumber = $_POST['taxFileNumber'];
            $higherEducationLoanProgramme = $_POST['higherEducationLoanProgramme'];
            $financialSupplementDebt = $_POST['financialSupplementDebt'];

            $edit_employee_detail_sql = "UPDATE employees SET first_name = ?, last_name = ?, nickname = ?, gender = ?, dob = ?, visa = ?, visa_expiry_date = ?, address = ?, email= ?, personal_email = ?, phone_number = ?, plate_number = ?, emergency_contact_name = ?, emergency_contact_phone_number = ?, emergency_contact_relationship = ?, start_date = ?, department = ?, section = ?, work_shift = ?, permanent_date = ?, locker_number = ?, employment_type = ?, position = ?, payroll_type = ?, bank_building_society = ?, bsb = ?, account_number = ?, superannuation_fund_name = ?, unique_superannuation_identifier = ?, superannuation_member_number = ?, tax_file_number = ?, higher_education_loan_programme = ?, financial_supplement_debt = ?, dietary_restrictions = ? WHERE employee_id = ?";
            $edit_employee_detail_result = $conn->prepare($edit_employee_detail_sql);
            $edit_employee_detail_result->bind_param("ssssssssssssssssssssssssssssssssisi", $firstName, $lastName, $nickname, $gender, $dob, $visaStatus, $visaExpiryDate, $address, $email, $personalEmail, $phoneNumber, $vehicleNumberPlate, $emergencyContactName, $emergencyContact, $emergencyContactRelationship, $startDate, $department, $section, $workShift, $permanentDate, $lockerNumber, $employmentType, $position, $payrollType, $bankBuildingSociety, $bsb, $accountNumber, $superannuationFundName, $uniqueSuperannuationIdentifier, $superannuationMemberNumber, $taxFileNumber, $higherEducationLoanProgramme, $financialSupplementDebt, $dietaryRestrictions, $employeeIdToEdit);

            if ($edit_employee_detail_result->execute()) {
                echo '<script>window.location.replace("' . $_SERVER['PHP_SELF'] . '?employee_id=' . urlencode(trim($employeeIdToEdit)) . '");</script>';
                exit();
            } else {
                echo "Error: " . $edit_employee_detail_result . "<br>" . $conn->error;
            }
        }
    }
?>

<!-- ================== Edit Profile Modal ================== -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileLabel"
                aria-hidden="true">
    <div class="modal-dialog modal-xl modal-fullscreen-lg-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProfileLabel">Edit Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="px-2 px-md-5">
                    <!-- ================== Edit Profile Form ================== -->
                    <div>
                        <div class="d-flex justify-content-between align-items-center">
                            <p class="signature-color fw-bold mt-4"> Personal Details </p>
                            <?php if ($isActive == 0) {
                                echo '<form method="POST"> <input type="hidden" name="employeeIdToActivate" value="' . $employeeId . '"/> <button class="btn btn-sm btn-success mt-2">Activate Employee</button></form>';
                            } else if ($isActive == 1) {
                                echo '<button class="btn btn-sm btn-danger mt-2" data-bs-toggle="modal" data-bs-target="#deactivateFormModal">Deactivate Employee</button>';
                            }
                            ?>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-12 d-flex align-items-center mb-4">
                                <div class="col-md-2 d-flex justify-content-center align-items-center">
                                    <?php if (!empty($profileImage)) { ?>
                                        <!-- Profile image -->
                                        <div class="bg-gradient shadow-lg rounded-circle"
                                            style="width: 100px; height: 100px; overflow: hidden;">
                                            <img src="data:image/jpeg;base64,<?php echo $profileImage; ?>"
                                                alt="Profile Image"
                                                class="profile-pic img-fluid rounded-circle"
                                                style="width: 100%; height: 100%; object-fit: cover;">
                                        </div>
                                    <?php } else { ?>
                                        <!-- Initials -->
                                        <div class="signature-bg-color shadow-lg rounded-circle text-white d-flex justify-content-center align-items-center"
                                            style="width: 100px; height: 100px;">
                                            <h3 class="p-0 m-0">
                                                <?php echo strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1)); ?>
                                            </h3>
                                        </div>
                                    <?php } ?>
                                </div>
                                <div class="col-md-10 ps-3">
                                    <div class="d-flex flex-column justify-content-center">
                                        <div class="d-flex">
                                            <form method="POST" class="col-md-12 "
                                                enctype="multipart/form-data" id="editProfileImageForm"
                                                novalidate>
                                                <input type="hidden" name="profileImageAction"
                                                    value="delete">
                                                <input type="hidden" name="profileImageToDeleteEmpId"
                                                    value="<?php echo $employeeId; ?>">
                                                <label for="profileImageToEdit" class="fw-bold">Profile
                                                    Image</label>
                                                <input type="file" id="profileImageToEdit"
                                                    name="profileImageToEdit"
                                                    class="form-control required">
                                                <div
                                                    class="invalid-feedback profile-image-error-message">
                                                </div>
                                                <button type="submit" class="btn btn-sm btn-danger mt-2"
                                                    name="deleteProfileImage">Delete Profile
                                                    Image</button>
                                                <button type="submit" class="btn btn-sm btn-dark mt-2"
                                                    name="changeProfileImage">Change Profile
                                                    Image</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-center">
                                <hr style="width:100%" />
                            </div>

                            <form method="POST" enctype="multipart/form-data" class="needs-validation"
                                id="editProfileForm" novalidate>
                                <input type="hidden" name="employeeIdToEdit"
                                    value="<?php echo $employeeId ?>">
                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label for="firstName" class="fw-bold">First Name</label>
                                        <input type="text" name="firstName" class="form-control"
                                            id="firstName"
                                            value="<?php echo (isset($firstName) ? $firstName : "") ?>"
                                            required>
                                        <div class="invalid-feedback">
                                            Please provide a first name.
                                        </div>
                                    </div>
                                    <div class="form-group col-md-4 mt-3 mt-md-0">
                                        <label for="lastName" class="fw-bold">Last Name</label>
                                        <input type="text" name="lastName" class="form-control"
                                            id="lastName"
                                            value="<?php echo (isset($lastName) ? $lastName : "") ?>"
                                            required>
                                        <div class="invalid-feedback">
                                            Please provide a last name.
                                        </div>
                                    </div>
                                    <div class="form-group col-md-4 mt-3 mt-md-0">
                                        <label for="nickname" class="fw-bold">Nickname</label>
                                        <input type="text" name="nickname" class="form-control"
                                            id="nickname"
                                            value="<?php echo (isset($nickname) ? $nickname : "") ?>">
                                    </div>
                                    <div class="form-group col-md-2 mt-3">
                                        <label for="gender" class="fw-bold">Gender</label>
                                        <select class="form-select" aria-label="gender" name="gender"
                                            required>
                                            <option disabled selected hidden></option>
                                            <option value="Male" <?php if (isset($gender) && $gender == "Male")
                                                echo "selected"; ?>>Male</option>
                                            <option value="Female" <?php if (isset($gender) && $gender == "Female")
                                                echo "selected"; ?>>Female
                                            </option>
                                            <option value="Other" <?php if (isset($gender) && $gender == "Other")
                                                echo "selected"; ?>>Other
                                            </option>
                                        </select>
                                        <div class="invalid-feedback">
                                            Please provide a date of birth.
                                        </div>
                                    </div>
                                    <div class="form-group col-md-2 mt-3">
                                        <label for="dob" class="fw-bold">Date of Birth</label>
                                        <input type="date" max="9999-12-31" name="dob" class="form-control" id="dob"
                                            value="<?php echo (isset($dob) ? $dob : "") ?>" required>
                                        <div class="invalid-feedback">
                                            Please provide a date of birth.
                                        </div>
                                    </div>
                                    <div class="form-group col-md-4 mt-3">
                                        <label for="visaStatus" class="fw-bold">Visa Status</label>
                                        <select class="form-select" aria-label="Visa Status"
                                            name="visaStatus" id="visaStatus" required>
                                            <option disabled selected hidden></option>
                                            <?php
                                            if ($visa_status_result->num_rows > 0) {
                                                while ($row = $visa_status_result->fetch_assoc()) {
                                                    $visaId = $row['visa_id'];
                                                    $visaName = $row['visa_name'];
                                                    // Determine if this option should be selected
                                                    $selected = (isset($visaStatus) && $visaStatus == $visaId) ? 'selected' : '';
                                                    ?>
                                                    <option value="<?= htmlspecialchars($visaId) ?>"
                                                        <?= $selected ?>><?= htmlspecialchars($visaName) ?>
                                                    </option>
                                                    <?php
                                                }
                                            }
                                            ?>
                                            <option value="Other">Other</option>
                                        </select>

                                        <div class="invalid-feedback">
                                            Please select a visa status.
                                        </div>
                                    </div>

                                    <div class="form-group col-md-4 mt-3 d-none"
                                        id="otherVisaStatusInput">
                                        <label for="otherVisaStatus" class="fw-bold">Other Visa
                                            Status</label>
                                        <input class="form-control" type="text" name="otherVisaStatus"
                                            id="otherVisaStatus">
                                        <div class="invalid-feedback">
                                            Please select the other visa status.
                                        </div>
                                    </div>

                                    <div class="form-group col-md-4 mt-3">
                                        <label for="visaExpiryDate" class="fw-bold">Visa Expiry
                                            Date</label>
                                        <div class="d-flex align-items-center">
                                            <div>
                                                <input type="date" max="9999-12-31" name="visaExpiryDate" class="form-control"
                                                    id="visaExpiryDate"
                                                    value="<?php echo (isset($visaExpiryDate) ? $visaExpiryDate : "") ?>"
                                                    required>
                                                <div class="invalid-feedback">
                                                    Please provide a visa expiry date.
                                                </div>
                                            </div>
                                            <a href="#" class="ms-2 d-none text-white bg-danger rounded-5 ps-1 pe-2 text-decoration-none fw-bold tooltips" data-bs-toggle="tooltip" 
                                                        data-bs-placement="right" title="Add 6 Months to expiry date" id="addVisaExpiryDateMonthBtn">
                                                <i class="fas fa-plus"></i> <small>Add</small>
                                            </a>
                                        </div>
                                    </div>   
                                    <div class="form-group col-md-4 mt-3">
                                        <label for="dietaryRestrictions" class="fw-bold">Dietary Restrictions</label>
                                        <div class="d-flex align-items-center">
                                            <input type="text" id="dietaryRestrictions" name="dietaryRestrictions" class="form-control" value="<?php echo (isset($dietaryRestrictions) && $dietaryRestrictions !== "" ? $dietaryRestrictions : "") ?>">
                                        </div>
                                    </div>     
                                </div>
                                <div class="row">
                                    <p class="signature-color fw-bold mt-5"> Contacts</p>
                                    <div class="form-group col-md-12">
                                        <label for="address" class="fw-bold">Address</label>
                                        <input type="text" class="form-control" id="address"
                                            name="address"
                                            value="<?php echo (isset($address) && $address !== "" ? $address : "") ?>"
                                            required>
                                        <div class="invalid-feedback">
                                            Please provide an address.
                                        </div>
                                    </div>
                                    <div class="form-group col-md-12 mt-3">
                                        <label for="email" class="fw-bold">Email</label>
                                        <input type="text" class="form-control" id="email" name="email"
                                            value="<?php echo (isset($email) && $email !== "" ? $email : "") ?>"
                                            >
                                        <div class="invalid-feedback">
                                            Please provide a valid email address
                                        </div>
                                    </div>
                                    <div class="form-group col-md-12 mt-3">
                                        <label for="personalEmail" class="fw-bold">Personal Email</label>
                                        <input type="text" class="form-control" id="personalEmail" name="personalEmail"
                                            value="<?php echo (isset($personalEmail) && $personalEmail !== "" ? $personalEmail : "" )?>">
                                    </div>
                                    <div class="form-group col-md-6 mt-3">
                                        <label for="phoneNumber" class="fw-bold">Mobile</label>
                                        <input type="text" class="form-control" id="phoneNumber"
                                            name="phoneNumber"
                                            value="<?php echo (isset($phoneNumber) && $phoneNumber !== "" ? $phoneNumber : "") ?>"
                                            required>
                                        <div class="invalid-feedback">
                                            Please provide a mobile number
                                        </div>
                                    </div>

                                    <div class="form-group col-md-6 mt-3">
                                        <label for="vehicleNumberPlate" class="fw-bold"><small>Vehicle
                                                Number Plate</small></label>
                                        <input type="text" class="form-control" id="vehicleNumberPlate"
                                            name="vehicleNumberPlate"
                                            value="<?php echo isset($vehicleNumberPlate) && $vehicleNumberPlate !== "" ? $vehicleNumberPlate : ""; ?>">
                                    </div>

                                    <p class="signature-color fw-bold mt-5"> Emergency Contacts</p>
                                    <div class="form-group col-md-6">
                                        <label for="emergencyContactName" class="fw-bold">Emergency
                                            Contact Name</label>
                                        <input type="text" class="form-control"
                                            id="emergencyContactName" name="emergencyContactName"
                                            value="<?php echo (isset($emergencyContactName) && $emergencyContactName !== "" ? $emergencyContactName : "") ?>"
                                            required>
                                        <div class="invalid-feedback">
                                            Please provide the emergency contact name.
                                        </div>
                                    </div>
                                    <div class="form-group col-md-6 mt-3 mt-md-0">
                                        <label for="emergencyContactRelationship"
                                            class="fw-bold">Emergency
                                            Contact Relationship </label>
                                        <input type="text" class="form-control"
                                            id="emergencyContactRelationship"
                                            name="emergencyContactRelationship"
                                            value="<?php echo (isset($emergencyContactRelationship) && $emergencyContactRelationship !== "" ? $emergencyContactRelationship : "") ?>"
                                            required>
                                        <div class="invalid-feedback">
                                            Please provide the emergency contact mobile number.
                                        </div>
                                    </div>
                                    <div class="form-group col-md-6 mt-3">
                                        <label for="emergencyContact" class="fw-bold">Emergency
                                            Contact Mobile</label>
                                        <input type="text" class="form-control" id="emergencyContact"
                                            name="emergencyContact"
                                            value="<?php echo (isset($emergencyContact) && $emergencyContact !== "" ? $emergencyContact : "") ?>"
                                            required>
                                        <div class="invalid-feedback">
                                            Please provide the emergency contact mobile number.
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <p class="signature-color fw-bold mt-5"> Employment Details</p>
                                    <div class="form-group col-md-3">
                                        <label for="employeeId" class="fw-bold">Employee Id</label>
                                        <input type="number" min="0" class="form-control" id="employeeId"
                                            name="employeeId"
                                            value="<?php echo (isset($employeeId) && $employeeId !== "" ? $employeeId : "") ?>"
                                            readonly required>
                                        <div class="invalid-feedback">
                                            Please provide an Employee Id.
                                        </div>
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="startDate" class="fw-bold mt-3 mt-md-0">Date
                                            Hired</label>
                                        <input type="date" max="9999-12-31" class="form-control" id="startDate" 
                                            name="startDate"
                                            value="<?php echo (isset($startDate) && $startDate !== "" ? $startDate : "") ?>"
                                            required>
                                        <div class="invalid-feedback">
                                            Please provide a hire date.
                                        </div>
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="employmentStatus"
                                            class="fw-bold mt-3 mt-md-0">Employment Type</label>
                                        <select class="form-select" aria-label="Employment Status"
                                            name="employmentType" required>
                                            <option disabled selected hidden></option>
                                            <option value="Full-Time" <?php if (isset($employmentType) && $employmentType == "Full-Time")
                                                echo "selected"; ?>>
                                                Full-Time
                                            </option>
                                            <option value="Part-Time" <?php if (isset($employmentType) && $employmentType == "Part-Time")
                                                echo "selected"; ?>>
                                                Part-Time
                                            </option>
                                            <option value="Casual" <?php if (isset($employmentType) && $employmentType == "Casual")
                                                echo "selected"; ?>>
                                                Casual
                                            </option>
                                        </select>
                                        <div class="invalid-feedback">
                                            Please provide an employment type.
                                        </div>
                                    </div>

                                    <div class="form-group col-md-3">
                                        <label for="department" class="fw-bold">Department</label>
                                        <select class="form-select" aria-label="deparment"
                                            name="department" id="department" required>
                                            <option disabled selected hidden></option>
                                            <?php
                                                if ($department_result->num_rows > 0) {
                                                    while ($row = $department_result->fetch_assoc()) {
                                                        $departmentId = $row['department_id'];
                                                        $departmentName = $row['department_name'];
                                                        // Determine if this option should be selected
                                                        $selected = (isset($department) && $department == $departmentId) ? 'selected' : '';
                                                        ?>
                                                        <option value="<?= htmlspecialchars($departmentId) ?>"
                                                            <?= $selected ?>><?= htmlspecialchars($departmentName) ?>
                                                        </option>
                                                        <?php
                                                    }
                                                }
                                            ?>
                                            <option value="Other">Other</option>
                                        </select>
                                        <div class="invalid-feedback">
                                            Please select a department.
                                        </div>
                                    </div>

                                    <div class="form-group col-md-3 mt-3 d-none" id="otherDepartmentInput">
                                        <label for="otherDepartment" class="fw-bold"><small>Other Department</small></label>
                                        <input class="form-control" type="text" name="otherDepartment" id="otherDepartment">
                                        <div class="invalid-feedback">
                                            Please select the other department.
                                        </div>
                                    </div>

                                    <div class="form-group col-md-3 mt-3" id="sectionField">
                                        <label for="section" class="fw-bold">Section</label>
                                        <select class="form-select" aria-label="Section" name="section"
                                            id="section" required>
                                            <option value="Office" <?php if (isset($section) && $section == "Office")
                                                echo "selected"; ?>>Office</option>
                                            <option value="Factory" <?php if (isset($section) && $section == "Factory")
                                                echo "selected"; ?>>Factory</option>
                                        </select>
                                        <div class="invalid-feedback">
                                            Please select a section.
                                        </div>
                                    </div>

                                    <div class="form-group col-md-3 mt-3" id="workShiftField">
                                        <label for="workShift" class="fw-bold">Work Shift</label>
                                        <select class="form-select" aria-label="workShift" name="workShift"
                                            id="workShift" required>
                                            <option value="Day" <?php if (isset($workShift) &&  $workShift == "Day") 
                                                echo "selected"; ?>>Day</option>
                                            <option value="Evening" <?php if (isset($workShift) &&  $workShift == "Evening") 
                                                echo "selected"; ?>>Evening</option>
                                            <option value="Night" <?php if (isset($workShift) &&  $workShift == "Night") 
                                                echo "selected"; ?>>Night</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-3 mt-3">
                                        <label for="position" class="fw-bold">Position</label>

                                        <select class="form-select" aria-label="Position"
                                            name="position" id="position" required>
                                            <option disabled selected hidden></option>
                                            <!-- <option value="Assembly Supervisor" <?php if ($position == 'Assembly Supervisor')
                                                echo 'selected'; ?>>
                                                Assembly Supervisor</option>
                                            <option value="Cleaner" <?php if ($position == 'Cleaner')
                                                echo 'selected'; ?>>Cleaner</option>
                                            <option value="Commissioning Engineer" <?php if ($position == 'Commissioning Engineer')
                                                echo 'selected'; ?>>
                                                Commissioning Engineer</option>
                                            <option value="Design Engineer" <?php if ($position == 'Design Engineer')
                                                echo 'selected'; ?>>Design Engineer</option>
                                            <option value="Electrical Factory Worker" <?php if ($position == 'Electrical Factory Worker')
                                                echo 'selected'; ?>>
                                                Electrical Factory Worker</option>
                                            <option value="Electrical Factory-Site Worker" <?php if ($position == 'Electrical Factory-Site Worker')
                                                echo 'selected'; ?>>Electrical Factory-Site Worker</option>
                                            <option value="Electrical Lead Trainer" <?php if ($position == 'Electrical Lead Trainer')
                                                echo 'selected'; ?>>
                                                Electrical Lead Trainer</option>
                                            <option value="Electrical Supervisor" <?php if ($position == 'Electrical Supervisor')
                                                echo 'selected'; ?>>
                                                Electrical Supervisor</option>
                                            <option value="Electrical Team Leader" <?php if ($position == 'Electrical Team Leader')
                                                echo 'selected'; ?>>
                                                Electrical Team Leader</option>
                                            <option value="Electrical Tradesman" <?php if ($position == 'Electrical Tradesman')
                                                echo 'selected'; ?>>
                                                Electrical Tradesman</option>
                                            <option value="Engineer" <?php if ($position == 'Engineer')
                                                echo 'selected'; ?>>Engineer</option>
                                            <option value="Engineering Support Officer" <?php if ($position == 'Engineering Support Officer')
                                                echo 'selected'; ?>>Engineering Support Officer</option>
                                            <option value="General Hand" <?php if ($position == 'General Hand')
                                                echo 'selected'; ?>>General Hand</option>
                                            <option value="Logistics Officer" <?php if ($position == 'Logistics Officer')
                                                echo 'selected'; ?>>
                                                Logistics Officer</option>
                                            <option value="Manufacturing Support Officer" <?php if ($position == 'Manufacturing Support Officer')
                                                echo 'selected'; ?>>Manufacturing Support Officer</option>
                                            <option value="Manufacturing Support Officer (IT)" <?php if ($position == 'Manufacturing Support Officer (IT)')
                                                echo 'selected'; ?>>Manufacturing Support Officer (IT)
                                            </option>
                                            <option value="Metal Fabrication Planner" <?php if ($position == 'Metal Fabrication Planner')
                                                echo 'selected'; ?>>
                                                Metal Fabrication Planner</option>
                                            <option value="Powder Coater" <?php if ($position == 'Powder Coater')
                                                echo 'selected'; ?>>Powder Coater</option>
                                            <option value="Project Engineer" <?php if ($position == 'Project Engineer')
                                                echo 'selected'; ?>>
                                                Project Engineer</option>
                                            <option value="Purchasing Officer" <?php if ($position == 'Purchasing Officer')
                                                echo 'selected'; ?>>
                                                Purchasing Officer</option>
                                            <option value="Quality Control Officer" <?php if ($position == 'Quality Control Officer')
                                                echo 'selected'; ?>>
                                                Quality Control Officer</option>
                                            <option value="Senior Project Engineer" <?php if ($position == 'Senior Project Engineer')
                                                echo 'selected'; ?>>
                                                Senior Project Engineer</option>
                                            <option value="Sheetmetal Factory Worker" <?php if ($position == 'Sheetmetal Factory Worker')
                                                echo 'selected'; ?>>
                                                Sheetmetal Factory Worker</option>
                                            <option value="Sheetmetal Programmer" <?php if ($position == 'Sheetmetal Programmer')
                                                echo 'selected'; ?>>
                                                Sheetmetal Programmer</option>
                                            <option value="Sheetmetal Programmer - R&D" <?php if ($position == 'Sheetmetal Programmer - R&D')
                                                echo 'selected'; ?>>Sheetmetal Programmer - R&D</option>
                                            <option value="Sheetmetal Supervisor" <?php if ($position == 'Sheetmetal Supervisor')
                                                echo 'selected'; ?>>
                                                Sheetmetal Supervisor</option>
                                            <option value="Sheetmetal Tradesman" <?php if ($position == 'Sheetmetal Tradesman')
                                                echo 'selected'; ?>>
                                                Sheetmetal Tradesman</option>
                                            <option value="Site Supervisor" <?php if ($position == 'Site Supervisor')
                                                echo 'selected'; ?>>Site Supervisor
                                            </option>
                                            <option value="Store Person" <?php if ($position == 'Store Person')
                                                echo 'selected'; ?>>Store Person</option>
                                            <option value="Store Supervisor" <?php if ($position == 'Store Supervisor')
                                                echo 'selected'; ?>>Store Supervisor
                                            </option>
                                            <option value="Truck Driver" <?php if ($position == 'Truck Driver')
                                                echo 'selected'; ?>>Truck Driver</option> -->
                                            <?php
                                            if ($position_result->num_rows > 0) {
                                                while ($row = $position_result->fetch_assoc()) {
                                                    $positionId = $row['position_id'];
                                                    $positionName = $row['position_name'];
                                                    // Determine if this option should be selected
                                                    $selected = (isset($position) && $position == $positionId) ? 'selected' : '';
                                                    ?>
                                                    <option value="<?= htmlspecialchars($positionId) ?>"
                                                        <?= $selected ?>><?= htmlspecialchars($positionName) ?>
                                                    </option>
                                                    <?php
                                                }
                                            }
                                            ?>
                                            <option value="Other">Other</option>
                                        </select>
                                        <div class="invalid-feedback">
                                            Please provide a position.
                                        </div>
                                    </div>

                                    <div class="form-group col-md-3 mt-3 d-none" id="otherPositionInput">
                                        <label for="otherPosition" class="fw-bold"><small>Other Position</small></label>
                                        <input class="form-control" type="text" name="otherPosition" id="otherPosition">
                                        <div class="invalid-feedback">
                                            Please select the other position.
                                        </div>
                                    </div>

                                    <div class="form-group col-md-3 mt-3">
                                        <label for="permanentDate" class="fw-bold mt-3 mt-md-0">Permanent Date</label>
                                        <input type="date" max="9999-12-31" class="form-control" id="permanentDate" 
                                            name="permanentDate"
                                            value="<?php echo (isset($permanentDate) && $permanentDate !== "" ? $permanentDate : "") ?>">
                                    </div>

                                    <div class="form-group col-md-3 mt-3">
                                        <label for="lockerNumber" class="fw-bold mt-3 mt-md-0">Locker Number</label>
                                        <input type="text" max="9999-12-31" class="form-control" id="lockerNumber" 
                                            name="lockerNumber"
                                            value="<?php echo (isset($lockerNumber) && $lockerNumber !== "" ? $lockerNumber : "") ?>">
                                    </div>

                                    <div class="form-group col-md-4 mt-3">
                                        <label for="payrollType" class="fw-bold">Payroll</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio"
                                                name="payrollType" id="wageRadio" value="wage" <?php echo (isset($payrollType) && $payrollType === "wage" ? "checked" : "") ?> required>
                                            <label class="form-check-label" for="wageRadio">Wage</label>

                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio"
                                                name="payrollType" id="salaryRadio" value="salary" <?php echo (isset($payrollType) && $payrollType === "salary" ? "checked" : "") ?> required>
                                            <label class="form-check-label"
                                                for="salaryRadio">Salary</label>
                                        </div>
                                    </div>
                                    <!-- <div class="form-group col-md-12 mt-3">
                                        <label for="policy" class="fw-bold">Upload Policy
                                            Files</label>
                                        <div class="input-group">
                                            <input type="file" id="policy" name="policy_files[]"
                                                class="form-control" multiple>
                                        </div>

                                    </div> -->
                                </div>
                                <div class="row">
                                    <p class="signature-color fw-bold mt-5"> Banking, Super and Tax
                                        Details
                                    </p>
                                    <div class="form-group col-md-12">
                                        <label for="bankBuildingSociety" class="fw-bold">Bank/Building
                                            Society</label>
                                        <input type="text" class="form-control" id="bankBuildingSociety"
                                            name="bankBuildingSociety"
                                            value="<?php echo (isset($bankBuildingSociety) && $bankBuildingSociety !== "" ? $bankBuildingSociety : "") ?>"
                                            required>
                                        <div class="invalid-feedback">
                                            Please provide the Bank/Building Society.
                                        </div>
                                    </div>
                                    <div class="form-group col-md-4 mt-3">
                                        <label for="bsb" class="fw-bold">BSB</label>
                                        <input type="text" class="form-control" id="bsb" name="bsb"
                                            value="<?php echo (isset($bsb) && $bsb !== "" ? $bsb : "") ?>"
                                            required>
                                        <div class="invalid-feedback">
                                            Please provide the BSB.
                                        </div>
                                    </div>
                                    <div class="form-group col-md-8 mt-3">
                                        <label for="accountNumber" class="fw-bold">Account
                                            Number</label>
                                        <input type="text" class="form-control" id="accountNumber"
                                            name="accountNumber"
                                            value="<?php echo (isset($accountNumber) && $accountNumber !== "" ? $accountNumber : "") ?>"
                                            required>
                                        <div class="invalid-feedback">
                                            Please provide the account number.
                                        </div>
                                    </div>
                                    <div class="form-group col-md-12 mt-3">
                                        <label for="superannuationFundName"
                                            class="fw-bold">Superannuation
                                            Fund Name</label>
                                        <input type="text" class="form-control"
                                            id="superannuationFundName" name="superannuationFundName"
                                            value="<?php echo (isset($superannuationFundName) && $superannuationFundName !== "" ? $superannuationFundName : "") ?>"
                                            required>
                                        <div class="invalid-feedback">
                                            Please provide the Superannuation Fund Name.
                                        </div>
                                    </div>
                                    <div class="form-group col-md-6 mt-3">
                                        <label for="uniqueSuperannuationIdentifier"
                                            class="fw-bold">Unique
                                            Superannuation
                                            Identifier</label>
                                        <input type="text" class="form-control"
                                            id="uniqueSuperannuationIdentifier"
                                            name="uniqueSuperannuationIdentifier"
                                            value="<?php echo (isset($uniqueSuperannuationIdentifier) && $uniqueSuperannuationIdentifier !== "" ? $uniqueSuperannuationIdentifier : "") ?>"
                                            required>
                                        <div class="invalid-feedback">
                                            Please provide the Unique Superannuation Identifier.
                                        </div>
                                    </div>

                                    <div class="form-group col-md-6 mt-3">
                                        <label for="superannuationMemberNumber"
                                            class="fw-bold">Superannuation Member Number</label>
                                        <input type="text" class="form-control"
                                            id="superannuationMemberNumber"
                                            name="superannuationMemberNumber"
                                            value="<?php echo (isset($superannuationMemberNumber) && $superannuationMemberNumber !== "" ? $superannuationMemberNumber : "") ?>"
                                            required>
                                        <div class="invalid-feedback">
                                            Please provide the Supperannuation Member Number.
                                        </div>
                                    </div>
                                    <div class="form-group col-md-6 mt-3">
                                        <label for="taxFileNumber" class="fw-bold">Tax File
                                            Number</label>
                                        <input type="text" class="form-control" id="taxFileNumber"
                                            name="taxFileNumber"
                                            value="<?php echo (isset($taxFileNumber) && $taxFileNumber !== "" ? $taxFileNumber : "") ?>"
                                            required>
                                        <div class="invalid-feedback">
                                            Please provide the tax file number.
                                        </div>
                                    </div>
                                    <div class="form-group col-md-12 mt-3">
                                        <div class="d-flex flex-column">
                                            <label for="higherEducationLoanProgramme"
                                                class="fw-bold"><small>Higher Education Loan
                                                    Programme?</small></label>
                                            <div class="btn-group col-3 col-md-2" role="group">
                                                <input type="radio" class="btn-check"
                                                    name="higherEducationLoanProgramme"
                                                    id="higherEducationLoanProgrammeYes" value="1"
                                                    autocomplete="off" <?php echo ($higherEducationLoanProgramme == 1) ? 'checked' : ''; ?> required>
                                                <label class="btn btn-sm btn-custom"
                                                    for="higherEducationLoanProgrammeYes"
                                                    style="color:#043f9d; border: 1px solid #043f9d">Yes</label>

                                                <input type="radio" class="btn-check"
                                                    name="higherEducationLoanProgramme"
                                                    id="higherEducationLoanProgrammeNo" value="0"
                                                    autocomplete="off" <?php echo ($higherEducationLoanProgramme == 0) ? 'checked' : ''; ?> required>
                                                <label class="btn btn-sm btn-custom"
                                                    for="higherEducationLoanProgrammeNo"
                                                    style="color:#043f9d; border: 1px solid #043f9d">No</label>
                                            </div>
                                        </div>
                                        <div class="invalid-feedback d-none"
                                            id="higherEducationLoanProgrammeInvalidFeedback">
                                            Please provide the Higher Education Loan Programme.
                                        </div>
                                    </div>
                                    <div class="form-group col-md-12 mt-3">
                                        <div class="d-flex flex-column">
                                            <label for="financialSupplementDebt"
                                                class="fw-bold"><small>Financial Supplement
                                                    Debt?</small></label>
                                            <div class="btn-group col-3 col-md-2" role="group">
                                                <input type="radio" class="btn-check"
                                                    name="financialSupplementDebt"
                                                    id="financialSupplementDebtYes" value="1"
                                                    autocomplete="off" <?php echo ($financialSupplementDebt == 1) ? 'checked' : ''; ?>
                                                    required>
                                                <label class="btn btn-sm btn-custom"
                                                    for="financialSupplementDebtYes"
                                                    style="color:#043f9d; border: 1px solid #043f9d">
                                                    Yes</label>

                                                <input type="radio" class="btn-check"
                                                    name="financialSupplementDebt"
                                                    id="financialSupplementDebtNo" value="0"
                                                    autocomplete="off" <?php echo ($financialSupplementDebt == 0) ? 'checked' : ''; ?>
                                                    required>
                                                <label class="btn btn-sm btn-custom"
                                                    for="financialSupplementDebtNo"
                                                    style="color:#043f9d; border: 1px solid #043f9d">No</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="invalid-feedback d-none"
                                        id="financialSuplementDebtInvalidFeedback">
                                        Please provide the Financial Supplement Debt.
                                    </div>
                                </div>
                                <div class="d-flex justify-content-center mt-5 mb-4">
                                    <button class="btn btn-dark" name="editEmployeeProfile">Edit
                                        Employee</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('.needs-validation');
        const financialSupplementDebtYes = document.getElementById("financialSupplementDebtYes");
        const financialSupplementDebtNo = document.getElementById("financialSupplementDebtNo");
        const financialSuplementDebtInvalidFeedback = document.getElementById("financialSuplementDebtInvalidFeedback");

        const higherEducationLoanProgrammeYes = document.getElementById("higherEducationLoanProgrammeYes");
        const higherEducationLoanProgrammeNo = document.getElementById("higherEducationLoanProgrammeNo");
        const higherEducationLoanProgrammeInvalidFeedback = document.getElementById("higherEducationLoanProgrammeInvalidFeedback");

        function checkFinancialSupplementDebtRadioSelection() {
            if (!financialSupplementDebtYes.checked && !financialSupplementDebtNo.checked) {
                financialSuplementDebtInvalidFeedback.classList.remove("d-none");
                financialSuplementDebtInvalidFeedback.classList.add("d-block");
                return false; // Indicates validation failure
            } else {
                financialSuplementDebtInvalidFeedback.classList.remove("d-block");
                financialSuplementDebtInvalidFeedback.classList.add("d-none");
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

        function validateForm() {
            let isValid = true;

            // Check radio button selections
            if (!checkFinancialSupplementDebtRadioSelection() || !checkHigherEducationLoanProgrammeRadioSelection()) {
                isValid = false;
            }

            // Check if the form itself is valid (HTML5 validation)
            if (!form.checkValidity()) {
                isValid = false;
            }

            return isValid;
        }

        form.addEventListener('submit', function (event) {
            checkFinancialSupplementDebtRadioSelection();
            checkHigherEducationLoanProgrammeRadioSelection();

            // Ensure all validations are checked
            if (!validateForm()) {
                event.preventDefault();
                event.stopPropagation();
                form.classList.add('was-validated');
            } else {
                form.classList.remove('was-validated');
            }
        }, false);

        // Add event listeners to both radio buttons for real-time validation
        financialSupplementDebtYes.addEventListener('change', checkFinancialSupplementDebtRadioSelection);
        financialSupplementDebtNo.addEventListener('change', checkFinancialSupplementDebtRadioSelection);
        higherEducationLoanProgrammeYes.addEventListener('change', checkHigherEducationLoanProgrammeRadioSelection);
        higherEducationLoanProgrammeNo.addEventListener('change', checkHigherEducationLoanProgrammeRadioSelection);
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const visaStatusSelect = document.getElementById("visaStatus");
        const visaExpiryDate = document.getElementById("visaExpiryDate");
        const otherVisaStatusInput = document.querySelector("#otherVisaStatusInput");
        const otherVisaStatus = document.querySelector("#otherVisaStatus");
        const dateHired = document.querySelector("#startDate");
        const addVisaExpiryDateMonthBtn = document.getElementById("addVisaExpiryDateMonthBtn"); 

        // Function to update visa expiry and other visa status input based on selected option
        function updateVisaFields() {
            const selectedOptionText = visaStatusSelect.options[visaStatusSelect.selectedIndex].text;
           
            console.log(`Selected Option Text: ${selectedOptionText}`);

            if (selectedOptionText === "Permanent Resident" || selectedOptionText === "Citizen") {
                visaExpiryDate.disabled = true;
                visaExpiryDate.required = false;
                otherVisaStatus.required = false;
                visaExpiryDate.value = "";
                otherVisaStatus.value = "";
                otherVisaStatusInput.classList.add("d-none");
                otherVisaStatusInput.classList.remove("d-block");
                addVisaExpiryDateMonthBtn.classList.add("d-none");
                addVisaExpiryDateMonthBtn .classList.remove("d-block");
            } else if (selectedOptionText === "Bridging" || selectedOptionText === "Working Holiday") {
                visaExpiryDate.required = true;
                visaExpiryDate.disabled = false;
                addVisaExpiryDateMonthBtn.classList.remove("d-none");
                addVisaExpiryDateMonthBtn.classList.add("d-block");
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
            } else {
                visaExpiryDate.disabled = false;
                visaExpiryDate.required = true;
                otherVisaStatus.required = false;
                // visaExpiryDate.value = "";
                otherVisaStatus.value = "";
                otherVisaStatusInput.classList.add("d-none");
                otherVisaStatusInput.classList.remove("d-block");
                addVisaExpiryDateMonthBtn.classList.add("d-none");
                addVisaExpiryDateMonthBtn .classList.remove("d-block");
            }
        }

        // Attach change event listener
        visaStatusSelect.addEventListener('change', updateVisaFields);

        // Initialize fields based on the currently selected option
        updateVisaFields();

        function updateVisaExpiryDateFields() {
            const selectedOptionText = visaStatusSelect.options[visaStatusSelect.selectedIndex].text;

            if (selectedOptionText === "Bridging" || selectedOptionText === "Working Holiday") { 
                addVisaExpiryDateMonthBtn.classList.remove("d-none");
                addVisaExpiryDateMonthBtn.classList.add("d-block");

               addVisaExpiryDateMonthBtn.addEventListener("click", function() {
                    const visaExpiryDateInput = document.getElementById("visaExpiryDate");

                    // Create a Date object based on the current value of the input
                    let visaExpiryDate = new Date(visaExpiryDateInput.value);
                    
                    if (!isNaN(visaExpiryDate)) { // Ensure it's a valid date
                        // Add 6 months to the date
                        visaExpiryDate.setMonth(visaExpiryDate.getMonth() + 6);

                        // Format the date as "YYYY-MM-DD" to set it as input value
                        const formattedExpiryDate = visaExpiryDate.toISOString().split("T")[0];
                        visaExpiryDateInput.value = formattedExpiryDate;

                        console.log(formattedExpiryDate);
                    } else {
                        console.error("Invalid date in visaExpiryDate input field");
                    }
                });

            } else {
                addVisaExpiryDateMonthBtn.classList.add("d-none");
                addVisaExpiryDateMonthBtn.classList.remove("d-block");
            }
        }

        updateVisaExpiryDateFields();
    });
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
    document.getElementById('editProfileImageForm').addEventListener('submit', function (event) {
        const profileImageToEdit = document.getElementById('profileImageToEdit');
        const allowedExtensions = /(\.jpg|\.jpeg|\.png)$/i;
        const filePath = profileImageToEdit.value;

        // Check which button was clicked
        const submitButton = event.submitter;

        // Only validate if "Change Profile Image" button was clicked
        if (submitButton.name === "changeProfileImage") {
            if (profileImageToEdit.files.length === 0) {
                event.preventDefault();
                profileImageToEdit.classList.add('is-invalid');
                document.querySelector('.profile-image-error-message').textContent = 'Please provide an image.';
            } else if (!allowedExtensions.exec(filePath)) {
                event.preventDefault();
                profileImageToEdit.classList.add('is-invalid');
                document.querySelector('.profile-image-error-message').textContent = 'Invalid file type. Please upload a JPG, JPEG, or PNG file.';
            } else {
                profileImageToEdit.classList.remove('is-invalid');
            }
        }
    });
</script>

<script>
    document.getElementById('addNewWageForm').addEventListener('submit', function (event) {
        const addNewWageForm = document.getElementById("addNewWageForm");

        if (!addNewWageForm.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
            // Add was-validated class to trigger Bootstrap validation styles
            addNewWageForm.classList.add('was-validated');
        } else {
            addNewWageForm.classList.remove('was-validated');
        }
    });
</script>

<script>
    document.getElementById('addNewSalaryForm').addEventListener('submit', function (event) {
        const addNewSalaryForm = document.getElementById("addNewSalaryForm");

        if (!addNewSalaryForm.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
            // Add was-validated class to trigger Bootstrap validation styles
            addNewSalaryForm.classList.add('was-validated');
        } else {
            addNewSalaryForm.classList.remove('was-validated');
        }
    });
</script>