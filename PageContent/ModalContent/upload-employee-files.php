<p class="p-2 bg-success text-center fw-bold mb-2 text-white rounded-2 d-none" id="employeeFileDirectory"></p>
<p class="p-2 signature-bg-color text-white rounded-2 text-center fw-bold mb-2 d-none" id="employeeFileName"></p>

<form method="POST" enctype="multipart/form-data" id="uploadEmployeeFileForm">
    <input type="hidden" name="employeeFileDirectory" id="hiddenEmployeeFileDirectory">
    <input type="hidden" name="employeeFileName" id="hiddenEmployeeFileName">

    <input type="hidden" name="empIdToUploadEmployeeFile" id="empIdToUploadEmployeeFile"
        value="<?php echo $employeeId ?>">
    <input type="hidden" name="empNameToUploadEmployeeFile" id="empNameToUploadEmployeeFile"
        value="<?php echo $firstName . " " . $lastName ?>">
    <div class="row">
        <div class="form-group col-md-12">
            <label for="selectedEmployeeFolder" class="fw-bold">Employee Folder</label>
            <select class="form-select" name="selectedEmployeeFolder" id="selectedEmployeeFolder" required>
                <option value="">Select Employee Folder</option>
                <option value="Employee Documents">00 - Employee Documents</option>
                <option value="Induction and Training Documents">01 - Induction and
                    Training Documents </option>
                <option value="Resume, ID and Qualifications">02 - Resume, ID and
                    Qualifications</option>
                <option value="Accounts">03 - Accounts</option>
                <option value="Leave">04 - Leave</option>
                <option value="HR Actions">05 - HR Actions</option>
                <option value="Work Compensation">06 - Work Compensation</option>
                <option value="Exit Information">07 - Exit Information </option>
            </select>
        </div>
        <!-- ======================== 00 - Employee Documents ========================  -->
        <div>
            <div class="form-group col-md-12 mt-3 d-none" id="employeeDocumentSelect">
                <label for="selectedEmployeeDocuments" class="fw-bold">00 - Employee
                    Documents</label>
                <select class="form-select" name="selectedEmployeeDocuments" id="selectedEmployeeDocuments">
                    <option value="">Select Employee Documents</option>
                    <option value="Employment Documents">01 - Employment Documents
                    </option>
                    <option value="Policies">02 - Policies</option>
                    <option value="Pay Review">03 - Pay Review</option>
                    <option value="Evaluations">04 - Evaluations</option>
                </select>
            </div>
            <div class="form-group col-md-12 mt-3 d-none" id="employmentDocumentSelect">
                <label for="selectedEmploymentDocuments" class="fw-bold">01 - Employment
                    Documents</label>
                <select class="form-select" name="selectedEmploymentDocuments" id="selectedEmploymentDocuments">
                    <option value="">Select Employment Documents</option>
                    <option value="Employment Application">Employment Application
                    </option>
                    <option value="Letter of Employment (Wage)">Letter of Employment
                        (Wage)
                    </option>
                    <option value="Revised Letter of Employment (Wage)">Revised Letter
                        of
                        Employment (Wage)</option>
                    <option value="Letter of Employment (Salary)">Letter of Employment
                        (Salary)</option>
                    <option value="Revised Letter of Employment (Salary)">Revised Letter
                        of
                        Employment (Salary)</option>
                    <option value="Letter of Employment (Casual)">Letter of Employment
                        (Casual)</option>
                    <option value="Job Description">Job Description</option>
                </select>
            </div>
            <div class="form-group col-md-12 mt-3 d-none" id="jobDescriptionSelect">
                <label for="selectedJobDescription" class="fw-bold">Job
                    Description</label>
                <?php
                // Query for job description documents
                $job_description_sql = "SELECT qa_document, document_name, `type` FROM quality_assurance WHERE `type` = 'Job Description' ORDER BY qa_document";
                $job_description_result = $conn->query($job_description_sql);
                ?>
                <select class="form-select" name="selectedJobDescription" id="selectedJobDescription">
                    <option value="">Select Job Description</option>
                    <?php if ($job_description_result->num_rows > 0) {
                        while ($row = $job_description_result->fetch_assoc()) { ?>
                            <option value="<?= htmlspecialchars($row['qa_document']) ?>">
                                <?= htmlspecialchars($row['qa_document']) . " (" . htmlspecialchars($row['document_name']) . ")" ?>
                            </option>
                        <?php }
                    } ?>
                </select>
            </div>
            <div class="form-group col-md-12 mt-3 d-none" id="policiesSelect">
                <label for="selectedPolicies" class="fw-bold">Policy</label>
                <?php
                // Query for policy files that match the pattern
                $policy_sql = "SELECT qa_document, document_name FROM quality_assurance WHERE qa_document LIKE '09-HR-PO-%' ORDER BY qa_document";
                $policy_result = $conn->query($policy_sql);
                ?>
                <select class="form-select" name="selectedPolicies" id="selectedPolicies">
                    <option value="">Select Policy</option>
                    <?php if ($policy_result->num_rows > 0) {
                        while ($row = $policy_result->fetch_assoc()) { ?>
                            <option value="<?= htmlspecialchars($row['qa_document']) ?>">
                                <?= htmlspecialchars($row['qa_document']) . " (" . htmlspecialchars($row['document_name']) . ")" ?>
                            </option>
                        <?php }
                    } ?>
                </select>
            </div>
            <div class="form-group col-md-12 mt-3 d-none" id="payReviewSelect">
                <label for="selectedPayReview" class="fw-bold">Pay Review</label>
                <select class="form-select" name="selectedPayReview" id="selectedPayReview">
                    <option value="">Select Pay Review </option>
                    <option value="Employee Performance and Pay Review Sheet Metal">
                        Employee
                        Performance and Pay Review Sheet Metal</option>
                    <option value="Employee Performance and Pay Review Electrical">
                        Employee
                        Performance and Pay Review Electrical</option>
                    <option value="Employee Performance and Pay Review Operations Support">
                        Employee Performance and Pay Review Operations Support</option>
                    <option value="Pay Review (Wage)">Pay Review (Wage)</option>
                    <option value="Pay Review (Salary)">Pay Review (Salary)</option>
                    <option value="Bonuses">Bonuses</option>
                </select>
            </div>
            <div class="form-group col-md-12 mt-3 d-none" id="evaluationsSelect">
                <label for="selectedEvaluations" class="fw-bold">Evaluations</label>
                <select class="form-select" name="selectedEvaluations" id="selectedEvaluations">
                    <option value="">Select Evaluations</option>
                    <option value="Factory Staff Evaluation Report">Factory Staff
                        Evaluation
                        Report</option>
                    <option value="Office Staff Evaluation Report">Office Staff Evaluation Report
                    </option>
                </select>
            </div>
        </div>
        <!-- ======================== 01 - Induction and Training Documents ========================  -->
        <div>
            <div class="form-group col-md-12 mt-3 d-none" id="inductionTrainingtSelect">
                <label for="selectedInductionTraining" class="fw-bold">01 - Induction
                    and Training Documents</label>
                <select class="form-select" name="selectedInductionTraining" id="selectedInductionTraining">
                    <option value="">Select Induction and Training Documents</option>
                    <option value="Employee Induction">Employee Induction</option>
                    <option value="Employee Induction Quiz">Employee Induction Quiz
                    </option>
                    <option value="Machinery Competency Register">Machinery Competency
                        Register</option>
                    <option value="Key and Alarm Record">Key and Alarm Record</option>
                    <option value="Uniform Record">Uniform Record</option>
                    <option value="Employee Loan (Tools)">Employee Loan (Tools)</option>
                </select>
            </div>
        </div>
        <!-- ======================== 02 - Resume, ID and Qualifications ========================  -->
        <div>
            <div class="form-group col-md-12 mt-3 d-none" id="resumeIdQualificationsSelect">
                <label for="selectedResumeIdQualifications" class="fw-bold">02 - Resume,
                    ID and Qualifications</label>
                <select class="form-select" name="selectedResumeIdQualifications" id="selectedResumeIdQualifications">
                    <option value="">Select Resume, ID and Qualifications Documents
                    </option>
                    <option value="Birth Certificate">Birth Certificate</option>
                    <option value="CoE">CoE</option>
                    <option value="ID">ID</option>
                    <option value="Passport">Passport</option>
                    <option value="Qualifications">Qualifications</option>
                    <option value="Resume">Resume</option>
                    <option value="Vevo">Vevo</option>
                    <option value="Visa">Visa</option>
                </select>
            </div>
        </div>
        <!-- ======================== 03 - Accounts ========================  -->
        <div>
            <div class="form-group col-md-12 mt-3 d-none" id="accountsSelect">
                <label for="selectedAccounts" class="fw-bold">03 - Accounts</label>
                <select class="form-select" name="selectedAccounts" id="selectedAccounts">
                    <option value="">Select Accounts Document</option>
                    <option value="Employee Details">Employee Details</option>
                    <option value="Tax File Number (Verified)">Tax File Number
                        (Verified)</option>
                    <option value="Superannuation (Verified)">Superannuation (Verified)
                    </option>
                    <option value="Tax File Number (Signed)">Tax File Number (Signed)
                    </option>
                    <option value="Superannuation (Signed)">Superannuation (Signed)
                    </option>
                </select>
            </div>
        </div>
        <!-- ======================== 04 - Leave ========================  -->
        <div>
            <div class="form-group col-md-12 mt-3 d-none" id="leaveSelect">
                <label for="selectedLeave" class="fw-bold">04 - Leave</label>
                <select class="form-select" name="selectedLeave" id="selectedLeave">
                    <option value="">Select Leave Document</option>
                    <option value="Medical Certificate">Medical Certificate</option>
                    <option value="Personal Leave">Personal Leave</option>
                    <option value="Annual Leave">Annual Leave</option>
                    <option value="Working From Home">Working From Home</option>
                    <option value="Long Service Leave">Long Service Leave</option>
                </select>
            </div>
        </div>
        <!-- ======================== 05 - HR Actions ========================  -->
        <div>
            <div class="form-group col-md-12 mt-3 d-none" id="hrActionsSelect">
                <label for="selectedHrActions" class="fw-bold">05 - HR Actions</label>
                <select class="form-select" name="selectedHrActions" id="selectedHrActions">
                    <option value="">Select HR Actions Document</option>
                    <option value="Statement of Event">Statement of Event</option>
                    <option value="Employee Warning">Employee Warning</option>
                    <option value="Safety Violation Warning">Safety Violation Warning
                    </option>
                </select>
            </div>
        </div>
        <!-- ======================== 06 - Work Compensation ========================  -->
        <div>
            <div class="form-group col-md-12 mt-3 d-none" id="workCompensationSelect">
                <label for="selectedWorkCompensation" class="fw-bold">06 - Work
                    Compensation</label>
                <select class="form-select" name="selectedWorkCompensation" id="selectedWorkCompensation">
                    <option value="">Select Work Compensation Document</option>
                    <option value="Incident Form">Incident Form</option>
                    <option value="Certificate of Capacity">Certificate of Capacity
                    </option>
                </select>
            </div>
        </div>
        <!-- ======================== 07 - Exit Information ========================  -->
        <div>
            <div class="form-group col-md-12 mt-3 d-none" id="exitInformationSelect">
                <label for="selectedExitInformation" class="fw-bold">07 - Exit
                    Information</label>
                <select class="form-select" name="selectedExitInformation" id="selectedExitInformation">
                    <option value="">Select Exit Information Document</option>
                    <option value="Employee Exit">Employee Exit</option>
                    <option value="Acknowledgement of Resignation">Acknowledgement of
                        Resignation</option>
                </select>
            </div>
        </div>
        <!-- ======================== File Upload Date ========================  -->
        <div>
            <div class="form-group col-md-12 mt-3 d-none" id="fileUploadDateInput">
                <label for="fileUploadDateInputValue" class="fw-bold">File Upload Date</label>
                <input type="date" class="form-control" name="fileUploadDateInputValue" id="fileUploadDateInputValue"
                    value="<?php echo date('Y-m-d'); ?>">
            </div>

            <!-- Drag and Drop area -->
            <div class="border rounded-2 p-4 text-center mt-3 d-none" id="employeeFileDropZone">
                <p class="mb-0">Drag & Drop your documents here or <br>
                    <button class="btn btn-primary btn-sm mt-2" type="button"
                        onclick="document.getElementById('employeeFileInput').click()">Browse
                        Files</button>
                </p>
            </div>
        </div>

        <input type="file" id="employeeFileInput" name="employeeFileToSubmit" class="d-none" required />
        <div id="employeeFileList" class="mt-3"></div>
    </div>
    <div class="d-flex justify-content-center mt-3">
        <button class="btn btn-dark" name="uploadEmployeeFile">Upload</button>
    </div>
</form>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const empIdToUploadEmployeeFile = document.getElementById("empIdToUploadEmployeeFile").value;
        const empNameToUploadEmployeeFile = document.getElementById("empNameToUploadEmployeeFile").value;
        const employeeFileName = document.getElementById("employeeFileName");
        const employeeFileDirectory = document.getElementById("employeeFileDirectory");
        const fileUploadDateInput = document.getElementById("fileUploadDateInput");
        const fileUploadDateInputValue = document.getElementById("fileUploadDateInputValue").value;

        fileUploadDateInput.addEventListener("change", function (event) {
            currentDateValue = event.target.value;
            console.log("Selected date: " + currentDateValue);

            if (selectedPayReview.value.trim() !== "") {
                fileUploadDateInput.classList.remove("d-none");
                employeeFileDropZone.classList.remove("d-none");

                if (selectedPayReview.value.trim() === "Employee Performance and Pay Review Sheet Metal") {
                    employeeFileName.textContent = currentDateValue + "-09-HR-ER-020 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                } else if (selectedPayReview.value.trim() === "Employee Performance and Pay Review Electrical") {
                    employeeFileName.textContent = currentDateValue + "-09-HR-ER-021 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                } else if (selectedPayReview.value.trim() === "Employee Performance and Pay Review Operations Support") {
                    employeeFileName.textContent = currentDateValue + "-09-HR-ER-022 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                } else if (selectedPayReview.value.trim() === "Pay Review (Wage)") {
                    employeeFileName.textContent = currentDateValue + "-09-HR-ER-009 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                } else if (selectedPayReview.value.trim() === "Pay Review (Salary)") {
                    employeeFileName.textContent = currentDateValue + "-09-HR-ER-007 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                } else if (selectedPayReview.value.trim() === "Bonuses") {
                    employeeFileName.textContent = currentDateValue + "-09-HR-ER-008 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                }
            }

            if (selectedPolicies.value.trim() !== "") {
                fileUploadDateInput.classList.remove("d-none");
                employeeFileDropZone.classList.remove("d-none");
                employeeFileName.textContent = currentDateValue + "-" + selectedPolicies.value.trim() + " (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
            }
            checkEmployeeFileNameVisibility()

            if (selectedEvaluations.value.trim() !== "") {
                fileUploadDateInput.classList.remove("d-none");
                employeeFileDropZone.classList.remove("d-none");
                if (selectedEvaluations.value.trim() === "Factory Staff Evaluation Report") {
                    employeeFileName.textContent = currentDateValue + "-09-HR-ER-016 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                } else if (selectedEvaluations.value.trim() === "Office Staff Evaluation Report") {
                    employeeFileName.textContent = currentDateValue + "-09-HR-ER-017 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                }
            }

            if (selectedEmploymentDocuments.value.trim() !== "Job Description") {
                fileUploadDateInput.classList.remove("d-none");
                employeeFileDropZone.classList.remove("d-none");
                if (selectedEmploymentDocuments.value.trim() === "Employment Application") {
                    employeeFileName.textContent = currentDateValue + "-09-HR-FO-001 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                } else if (selectedEmploymentDocuments.value.trim() === "Letter of Employment (Wage)") {
                    employeeFileName.textContent = currentDateValue + "-09-HR-FO-002 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                } else if (selectedEmploymentDocuments.value.trim() === "Revised Letter of Employment (Wage)") {
                    employeeFileName.textContent = currentDateValue + "-09-HR-FO-003 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                } else if (selectedEmploymentDocuments.value.trim() === "Letter of Employment (Salary)") {
                    employeeFileName.textContent = currentDateValue + "-09-HR-FO-004 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                } else if (selectedEmploymentDocuments.value.trim() === "Revised Letter of Employment (Salary)") {
                    employeeFileName.textContent = currentDateValue + "-09-HR-FO-005 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                } else if (selectedEmploymentDocuments.value.trim() === "Letter of Employment (Casual)") {
                    employeeFileName.textContent = currentDateValue + "-09-HR-FO-006 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                }
            }

            if (selectedEmployeeFolder.value.trim() !== "" && selectedInductionTraining.value.trim() !== "") {
                fileUploadDateInput.classList.remove("d-none");
                employeeFileDropZone.classList.remove("d-none");

                if (selectedInductionTraining.value.trim() === "Employee Induction") {
                    employeeFileName.textContent = currentDateValue + "-09-HR-ER-001 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                } else if (selectedInductionTraining.value.trim() === "Employee Induction Quiz") {
                    employeeFileName.textContent = currentDateValue + "-09-HR-ER-003 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                } else if (selectedInductionTraining.value.trim() === "Key and Alarm Record") {
                    employeeFileName.textContent = currentDateValue + "-09-HR-ER-005 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                } else if (selectedInductionTraining.value.trim() === "Uniform Record") {
                    employeeFileName.textContent = currentDateValue + "-09-HR-ER-011 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                } else if (selectedInductionTraining.value.trim() === "Employee Loan (Tools)") {
                    employeeFileName.textContent = currentDateValue + "-09-HR-ER-013 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                }
            }

            if (selectedEmployeeFolder.value.trim() !== "" && selectedResumeIdQualifications.value.trim() !== "") {
                fileUploadDateInput.classList.remove("d-none");
                employeeFileDropZone.classList.remove("d-none");
                if (selectedResumeIdQualifications.value.trim() === "Birth Certificate") {
                    employeeFileName.textContent = currentDateValue + "-Birth-Cert (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                } else if (selectedResumeIdQualifications.value.trim() === "CoE") {
                    employeeFileName.textContent = currentDateValue + "-CoE (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                } else if (selectedResumeIdQualifications.value.trim() === "ID") {
                    employeeFileName.textContent = currentDateValue + "-ID (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                } else if (selectedResumeIdQualifications.value.trim() === "Passport") {
                    employeeFileName.textContent = currentDateValue + "-Passport (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                } else if (selectedResumeIdQualifications.value.trim() === "Qualifications") {
                    employeeFileName.textContent = currentDateValue + "-Qualifications (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                } else if (selectedResumeIdQualifications.value.trim() === "Resume") {
                    employeeFileName.textContent = currentDateValue + "-Resume (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                } else if (selectedResumeIdQualifications.value.trim() === "Vevo") {
                    employeeFileName.textContent = currentDateValue + "-Vevo (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                } else if (selectedResumeIdQualifications.value.trim() === "Visa") {
                    employeeFileName.textContent = currentDateValue + "-Visa (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                }
            }

            if (selectedEmployeeFolder.value.trim() !== "" && selectedAccounts.value.trim() !== "") {
                fileUploadDateInput.classList.remove("d-none");
                employeeFileDropZone.classList.remove("d-none");
                if (selectedAccounts.value.trim() === "Employee Details") {
                    employeeFileName.textContent = currentDateValue + "-09-HR-ER-002 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                } else if (selectedAccounts.value.trim() === "Tax File Number (Verified)") {
                    employeeFileName.textContent = currentDateValue + "-TFN (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Verified";
                } else if (selectedAccounts.value.trim() === "Superannuation (Verified)") {
                    employeeFileName.textContent = currentDateValue + "-Super (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Verified";
                } else if (selectedAccounts.value.trim() === "Tax File Number (Signed)") {
                    employeeFileName.textContent = currentDateValue + "-TFN (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                } else if (selectedAccounts.value.trim() === "Superannuation (Signed)") {
                    employeeFileName.textContent = currentDateValue + "-Super (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                }
            }

            if (selectedEmployeeFolder.value.trim() !== "" && selectedLeave.value.trim() !== "") {
                fileUploadDateInput.classList.remove("d-none");
                employeeFileDropZone.classList.remove("d-none");

                if (selectedLeave.value.trim() === "Medical Certificate") {
                    employeeFileName.textContent = currentDateValue + "-Doc-Cert (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                    subFolder = "00 - Personal Leave\\";
                } else if (selectedLeave.value.trim() === "Personal Leave") {
                    employeeFileName.textContent = currentDateValue + "-09-HR-FO-007 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                    subFolder = "00 - Personal Leave\\";
                } else if (selectedLeave.value.trim() === "Annual Leave") {
                    employeeFileName.textContent = currentDateValue + "-09-HR-FO-007 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                    subFolder = "01 - Annual Leave\\";
                } else if (selectedLeave.value.trim() === "Working From Home") {
                    employeeFileName.textContent = currentDateValue + "-09-HR-FO-007 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                    subFolder = "02 - Working From Home\\";
                } else if (selectedLeave.value.trim() === "Long Service Leave") {
                    employeeFileName.textContent = currentDateValue + "-09-HR-FO-007 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                    subFolder = "03 - Long Service Leave\\";
                }
            }

            if (selectedEmployeeFolder.value.trim() !== "" && selectedHrActions.value.trim() !== "") {
                fileUploadDateInput.classList.remove("d-none");
                employeeFileDropZone.classList.remove("d-none");
                if (selectedHrActions.value.trim() === "Statement of Event") {
                    employeeFileName.textContent = currentDateValue + "-09-HR-FO-013 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                } else if (selectedHrActions.value.trim() === "Employee Warning") {
                    employeeFileName.textContent = currentDateValue + "-09-HR-ER-012 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                } else if (selectedHrActions.value.trim() === "Safety Violation Warning") {
                    employeeFileName.textContent = currentDateValue + "-09-HR-ER-018 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                }
            }

            if (selectedEmployeeFolder.value.trim() !== "" && selectedWorkCompensation.value.trim() !== "") {
                fileUploadDateInput.classList.remove("d-none");
                employeeFileDropZone.classList.remove("d-none");
                if (selectedWorkCompensation.value.trim() === "Incident Form") {
                    employeeFileName.textContent = currentDateValue + "-11-WH-FO-002 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                } else if (selectedWorkCompensation.value.trim() === "Certificate of Capacity") {
                    employeeFileName.textContent = currentDateValue + "-Certificate of Capacity (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                }
            }

            if (selectedEmployeeFolder.value.trim() !== "" && selectedExitInformation.value.trim() !== "") {
                fileUploadDateInput.classList.remove("d-none");
                employeeFileDropZone.classList.remove("d-none");
                if (selectedExitInformation.value.trim() === "Employee Exit") {
                    employeeFileName.textContent = currentDateValue + "-09-HR-ER-009 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                } else if (selectedExitInformation.value.trim() === "Acknowledgement of Resignation") {
                    employeeFileName.textContent = currentDateValue + "-09-HR-ER-014 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                }
            }
        });


        const employeeFileDropZone = document.getElementById("employeeFileDropZone");
        const employeeFileInput = document.getElementById("employeeFileInput").value;

        const selectedEmployeeFolder = document.getElementById("selectedEmployeeFolder");
        const employeeDocumentSelect = document.getElementById("employeeDocumentSelect");
        const selectedEmployeeDocuments = document.getElementById("selectedEmployeeDocuments");
        const employmentDocumentSelect = document.getElementById("employmentDocumentSelect");
        const selectedEmploymentDocuments = document.getElementById("selectedEmploymentDocuments");
        const jobDescriptionSelect = document.getElementById("jobDescriptionSelect");
        const selectedJobDescription = document.getElementById("selectedJobDescription");
        const policiesSelect = document.getElementById("policiesSelect");
        const selectedPolicies = document.getElementById("selectedPolicies");
        const payReviewSelect = document.getElementById("payReviewSelect");
        const selectedPayReview = document.getElementById("selectedPayReview");
        const evaluationsSelect = document.getElementById("evaluationsSelect");
        const selectedEvaluations = document.getElementById("selectedEvaluations");
        const inductionTrainingSelect = document.getElementById("inductionTrainingtSelect");
        const selectedInductionTraining = document.getElementById("selectedInductionTraining");
        const resumeIdQualificationsSelect = document.getElementById("resumeIdQualificationsSelect");
        const selectedResumeIdQualifications = document.getElementById("selectedResumeIdQualifications");
        const accountsSelect = document.getElementById("accountsSelect");
        const selectedAccounts = document.getElementById("selectedAccounts");
        const leaveSelect = document.getElementById("leaveSelect");
        const selectedLeave = document.getElementById("selectedLeave");
        const hrActionsSelect = document.getElementById("hrActionsSelect");
        const selectedHrActions = document.getElementById("selectedHrActions");
        const workCompensationSelect = document.getElementById("workCompensationSelect");
        const selectedWorkCompensation = document.getElementById("selectedWorkCompensation");
        const exitInformationSelect = document.getElementById("exitInformationSelect");
        const selectedExitInformation = document.getElementById("selectedExitInformation");

        function checkEmployeeFileNameVisibility() {
            if (employeeFileName.textContent.trim() === "") {
                employeeFileName.classList.add("d-none");
            } else {
                employeeFileName.classList.remove("d-none");
            }
        }

        if (
            selectedEmployeeFolder &&
            employeeDocumentSelect &&
            selectedEmployeeDocuments &&
            employmentDocumentSelect &&
            selectedEmploymentDocuments &&
            jobDescriptionSelect &&
            selectedJobDescription &&
            policiesSelect &&
            selectedPolicies &&
            payReviewSelect &&
            selectedPayReview &&
            evaluationsSelect &&
            selectedEvaluations &&
            inductionTrainingSelect &&
            selectedInductionTraining &&
            resumeIdQualificationsSelect &&
            selectedResumeIdQualifications &&
            accountsSelect &&
            selectedAccounts &&
            leaveSelect &&
            selectedLeave &&
            hrActionsSelect &&
            selectedHrActions &&
            workCompensationSelect &&
            selectedWorkCompensation &&
            exitInformationSelect &&
            selectedExitInformation
        ) {

            let baseDirectoryPath = "";
            selectedEmployeeFolder.addEventListener("change", function () {
                // ==================== E M P L O Y E E  F O L D E R  (S E L E C T I O N) ====================

                // Reset the path to ensure no residual data
                baseDirectoryPath = "";

                // Reset all selects initially
                employeeDocumentSelect.classList.add("d-none");
                employmentDocumentSelect.classList.add("d-none");
                jobDescriptionSelect.classList.add("d-none");
                policiesSelect.classList.add("d-none");
                payReviewSelect.classList.add("d-none");
                evaluationsSelect.classList.add("d-none");
                inductionTrainingSelect.classList.add("d-none");
                resumeIdQualificationsSelect.classList.add("d-none");
                accountsSelect.classList.add("d-none");
                leaveSelect.classList.add("d-none");
                hrActionsSelect.classList.add("d-none");
                workCompensationSelect.classList.add("d-none");
                exitInformationSelect.classList.add("d-none");
                fileUploadDateInput.classList.add("d-none");
                employeeFileDropZone.classList.add("d-none");

                // Reset all required attributes
                selectedEmployeeDocuments.removeAttribute("required");
                selectedEmploymentDocuments.removeAttribute("required");
                selectedJobDescription.removeAttribute("required");
                selectedPolicies.removeAttribute("required");
                selectedPayReview.removeAttribute("required");
                selectedEvaluations.removeAttribute("required");
                selectedInductionTraining.removeAttribute("required");
                selectedResumeIdQualifications.removeAttribute("required");
                selectedAccounts.removeAttribute("required");
                selectedLeave.removeAttribute("required");
                selectedHrActions.removeAttribute("required");
                selectedWorkCompensation.removeAttribute("required");
                selectedExitInformation.removeAttribute("required");

                // Reset all values when a different selection is made
                selectedEmployeeDocuments.value = "";
                selectedEmploymentDocuments.value = "";
                selectedJobDescription.value = "";
                selectedPolicies.value = "";
                selectedPayReview.value = "";
                selectedEvaluations.value = "";
                selectedInductionTraining.value = "";
                selectedResumeIdQualifications.value = "";
                selectedAccounts.value = "";
                selectedLeave.value = "";
                selectedHrActions.value = "";
                selectedWorkCompensation.value = "";
                selectedExitInformation.value = "";
                employeeFileInput.value = "";
                employeeFileName.textContent = "";
                updateEmployeeFileList([]);

                if (selectedEmployeeFolder.value.trim() === "Employee Documents") {
                    employeeDocumentSelect.classList.remove("d-none");
                    selectedEmployeeDocuments.setAttribute("required", "true");
                    baseDirectoryPath = "\\" + empIdToUploadEmployeeFile + "\\00 - Employee Documents\\";
                    employeeFileDirectory.textContent = baseDirectoryPath;
                    employeeFileDirectory.classList.remove("d-none");
                }
                else if (selectedEmployeeFolder.value.trim() === "Induction and Training Documents") {
                    inductionTrainingSelect.classList.remove("d-none");
                    selectedInductionTraining.setAttribute("required", "true");
                    baseDirectoryPath = "\\" + empIdToUploadEmployeeFile + "\\01 - Induction and Training Documents\\";
                    employeeFileDirectory.textContent = baseDirectoryPath;
                    employeeFileDirectory.classList.remove("d-none");
                }
                else if (selectedEmployeeFolder.value.trim() === "Resume, ID and Qualifications") {
                    resumeIdQualificationsSelect.classList.remove("d-none");
                    selectedResumeIdQualifications.setAttribute("required", "true");
                    baseDirectoryPath = "\\" + empIdToUploadEmployeeFile + "\\02 - Resume, ID and Qualifications\\";
                    employeeFileDirectory.textContent = baseDirectoryPath;
                    employeeFileDirectory.classList.remove("d-none");
                }
                else if (selectedEmployeeFolder.value.trim() === "Accounts") {
                    accountsSelect.classList.remove("d-none");
                    selectedAccounts.setAttribute("required", "true");
                    baseDirectoryPath = "\\" + empIdToUploadEmployeeFile + "\\03 - Accounts\\";
                    employeeFileDirectory.textContent = baseDirectoryPath;
                    employeeFileDirectory.classList.remove("d-none");
                }
                else if (selectedEmployeeFolder.value.trim() === "Leave") {
                    leaveSelect.classList.remove("d-none");
                    selectedLeave.setAttribute("required", "true");
                    baseDirectoryPath = "\\" + empIdToUploadEmployeeFile + "\\04 - Leave\\";
                    employeeFileDirectory.textContent = baseDirectoryPath;
                    employeeFileDirectory.classList.remove("d-none");
                }
                else if (selectedEmployeeFolder.value.trim() === "HR Actions") {
                    hrActionsSelect.classList.remove("d-none");
                    selectedHrActions.setAttribute("required", "true");
                    baseDirectoryPath = "\\" + empIdToUploadEmployeeFile + "\\05 - HR Actions\\";
                    employeeFileDirectory.textContent = baseDirectoryPath;
                    employeeFileDirectory.classList.remove("d-none");
                }
                else if (selectedEmployeeFolder.value.trim() === "Work Compensation") {
                    workCompensationSelect.classList.remove("d-none");
                    selectedWorkCompensation.setAttribute("required", "true");
                    baseDirectoryPath = "\\" + empIdToUploadEmployeeFile + "\\06 - Work Compensation\\";
                    employeeFileDirectory.textContent = baseDirectoryPath;
                    employeeFileDirectory.classList.remove("d-none");
                }
                else if (selectedEmployeeFolder.value.trim() === "Exit Information") {
                    exitInformationSelect.classList.remove("d-none");
                    selectedExitInformation.setAttribute("required", "true");
                    baseDirectoryPath = "\\" + empIdToUploadEmployeeFile + "\\07 - Exit Information\\";
                    employeeFileDirectory.textContent = baseDirectoryPath;
                    employeeFileDirectory.classList.remove("d-none");
                }
                else if (selectedEmployeeFolder.value.trim() === "") {
                    employeeFileDirectory.classList.add("d-none");
                }

                checkEmployeeDocumentsFilled();
                checkEmployeeFileNameVisibility();
            });

            // ==================== 00 - E M P L O Y E E  D O C U M E N T S  (S E L E C T I O N) ====================
            selectedEmployeeDocuments.addEventListener("change", function () {

                let subFolder = ""; // Variable to store the subfolder path

                // Reset the subfolder text
                employeeFileDirectory.textContent = baseDirectoryPath;

                employmentDocumentSelect.classList.add("d-none");
                policiesSelect.classList.add("d-none");
                jobDescriptionSelect.classList.add("d-none");
                payReviewSelect.classList.add("d-none");
                evaluationsSelect.classList.add("d-none");
                fileUploadDateInput.classList.add("d-none");
                employeeFileDropZone.classList.add("d-none");

                selectedEmploymentDocuments.removeAttribute("required");
                selectedPolicies.removeAttribute("required");
                selectedPayReview.removeAttribute("required");
                selectedEvaluations.removeAttribute("required");

                selectedEmploymentDocuments.value = "";
                selectedPolicies.value = "";
                selectedPayReview.value = "";
                selectedEvaluations.value = "";

                if (selectedEmployeeDocuments.value.trim() === "Employment Documents") {
                    employmentDocumentSelect.classList.remove("d-none");
                    selectedEmploymentDocuments.setAttribute("required", "true");
                    subFolder = "01 - Employment Documents\\";
                } else if (selectedEmployeeDocuments.value.trim() === "Policies") {
                    policiesSelect.classList.remove("d-none");
                    selectedPolicies.setAttribute("required", "true");
                    subFolder = "02 - Policies\\";
                } else if (selectedEmployeeDocuments.value.trim() === "Pay Review") {
                    payReviewSelect.classList.remove("d-none");
                    selectedPayReview.setAttribute("required", "true");
                    subFolder = "03 - Pay Review\\";
                } else if (selectedEmployeeDocuments.value.trim() === "Evaluations") {
                    evaluationsSelect.classList.remove("d-none");
                    selectedEvaluations.setAttribute("required", "true");
                    subFolder = "04 - Evaluations\\";
                }

                // Combine base directory and subfolder
                if (subFolder) {
                    employeeFileDirectory.textContent += subFolder;
                }

                checkEmployeeDocumentsFilled()
                checkEmployeeFileNameVisibility()
            });

            selectedEmploymentDocuments.addEventListener("change", function () {
                if (selectedEmploymentDocuments.value.trim() === "Job Description") {
                    jobDescriptionSelect.classList.remove("d-none");
                    selectedJobDescription.setAttribute("required", "true");
                    selectedJobDescription.value = "";
                } else {
                    jobDescriptionSelect.classList.add("d-none");
                    selectedJobDescription.removeAttribute("required");
                    selectedJobDescription.value = "";
                }
                checkEmployeeDocumentsFilled()
                checkEmployeeFileNameVisibility()
            });

            selectedJobDescription.addEventListener("change", function () {
                if (selectedJobDescription.value.trim() !== "") {
                    fileUploadDateInput.classList.remove("d-none");
                    employeeFileDropZone.classList.remove("d-none");
                    employeeFileName.textContent = fileUploadDateInputValue + "-" + selectedJobDescription.value.trim() + " (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                } else if (selectedJobDescription.value.trim() === "") {
                    fileUploadDateInput.classList.add("d-none");
                    employeeFileDropZone.classList.add("d-none");
                    employeeFileName.textContent = "";
                }
                checkEmployeeFileNameVisibility()
            });

            selectedPolicies.addEventListener("change", function () {
                if (selectedPolicies.value.trim() !== "") {
                    fileUploadDateInput.classList.remove("d-none");
                    employeeFileDropZone.classList.remove("d-none");
                    employeeFileName.textContent = fileUploadDateInputValue + "-" + selectedPolicies.value.trim() + " (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                } else if (selectedPolicies.value.trim() === "") {
                    fileUploadDateInput.classList.add("d-none");
                    employeeFileDropZone.classList.add("d-none");
                    employeeFileName.textContent = "";
                }
                checkEmployeeFileNameVisibility()
            });

            selectedPayReview.addEventListener("change", function () {
                if (selectedPayReview.value.trim() !== "") {
                    fileUploadDateInput.classList.remove("d-none");
                    employeeFileDropZone.classList.remove("d-none");

                    if (selectedPayReview.value.trim() === "Employee Performance and Pay Review Sheet Metal") {
                        employeeFileName.textContent = fileUploadDateInputValue + "-09-HR-ER-020 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                    } else if (selectedPayReview.value.trim() === "Employee Performance and Pay Review Electrical") {
                        employeeFileName.textContent = fileUploadDateInputValue + "-09-HR-ER-021 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                    } else if (selectedPayReview.value.trim() === "Employee Performance and Pay Review Operations Support") {
                        employeeFileName.textContent = fileUploadDateInputValue + "-09-HR-ER-022 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                    } else if (selectedPayReview.value.trim() === "Pay Review (Wage)") {
                        employeeFileName.textContent = fileUploadDateInputValue + "-09-HR-ER-009 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                    } else if (selectedPayReview.value.trim() === "Pay Review (Salary)") {
                        employeeFileName.textContent = fileUploadDateInputValue + "-09-HR-ER-007 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                    } else if (selectedPayReview.value.trim() === "Bonuses") {
                        employeeFileName.textContent = fileUploadDateInputValue + "-09-HR-ER-008 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                    }
                } else if (selectedPayReview.value.trim() === "") {
                    fileUploadDateInput.classList.add("d-none");
                    employeeFileDropZone.classList.add("d-none");
                    employeeFileName.textContent = "";
                }
                checkEmployeeFileNameVisibility()
            });

            selectedEvaluations.addEventListener("change", function () {
                if (selectedEvaluations.value.trim() !== "") {
                    fileUploadDateInput.classList.remove("d-none");
                    employeeFileDropZone.classList.remove("d-none");
                    if (selectedEvaluations.value.trim() === "Factory Staff Evaluation Report") {
                        employeeFileName.textContent = fileUploadDateInputValue + "-09-HR-ER-016 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                    } else if (selectedEvaluations.value.trim() === "Office Staff Evaluation Report") {
                        employeeFileName.textContent = fileUploadDateInputValue + "-09-HR-ER-017 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                    }
                } else if (selectedEvaluations.value.trim() === "") {
                    fileUploadDateInput.classList.add("d-none");
                    employeeFileDropZone.classList.add("d-none");
                    employeeFileName.textContent = "";
                }
                checkEmployeeFileNameVisibility()
            });

            function checkEmployeeDocumentsFilled() {
                employeeFileName.textContent = "";
                if (selectedEmployeeFolder.value.trim() !== "" && selectedEmployeeDocuments.value.trim() !== "" && selectedEmploymentDocuments.value.trim() !== "") {
                    if (selectedEmploymentDocuments.value.trim() !== "Job Description") {
                        fileUploadDateInput.classList.remove("d-none");
                        employeeFileDropZone.classList.remove("d-none");
                        if (selectedEmploymentDocuments.value.trim() === "Employment Application") {
                            employeeFileName.textContent = fileUploadDateInputValue + "-09-HR-FO-001 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                        } else if (selectedEmploymentDocuments.value.trim() === "Letter of Employment (Wage)") {
                            employeeFileName.textContent = fileUploadDateInputValue + "-09-HR-FO-002 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                        } else if (selectedEmploymentDocuments.value.trim() === "Revised Letter of Employment (Wage)") {
                            employeeFileName.textContent = fileUploadDateInputValue + "-09-HR-FO-003 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                        } else if (selectedEmploymentDocuments.value.trim() === "Letter of Employment (Salary)") {
                            employeeFileName.textContent = fileUploadDateInputValue + "-09-HR-FO-004 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                        } else if (selectedEmploymentDocuments.value.trim() === "Revised Letter of Employment (Salary)") {
                            employeeFileName.textContent = fileUploadDateInputValue + "-09-HR-FO-005 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                        } else if (selectedEmploymentDocuments.value.trim() === "Letter of Employment (Casual)") {
                            employeeFileName.textContent = fileUploadDateInputValue + "-09-HR-FO-006 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                        }
                    } else if (selectedEmploymentDocuments.value.trim() === "") {
                        fileUploadDateInput.classList.add("d-none");
                        employeeFileDropZone.classList.add("d-none");
                    }
                }
            }

            // ==================== 01 - I N D U C T I O N  A N D  T R A I N I N G  D O C U M E N T S (S E L E C T I O N) ====================
            selectedInductionTraining.addEventListener("change", function () {
                if (selectedEmployeeFolder.value.trim() !== "" && selectedInductionTraining.value.trim() !== "") {
                    fileUploadDateInput.classList.remove("d-none");
                    employeeFileDropZone.classList.remove("d-none");

                    if (selectedInductionTraining.value.trim() === "Employee Induction") {
                        employeeFileName.textContent = fileUploadDateInputValue + "-09-HR-ER-001 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                    } else if (selectedInductionTraining.value.trim() === "Employee Induction Quiz") {
                        employeeFileName.textContent = fileUploadDateInputValue + "-09-HR-ER-003 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                    } else if (selectedInductionTraining.value.trim() === "Key and Alarm Record") {
                        employeeFileName.textContent = fileUploadDateInputValue + "-09-HR-ER-005 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                    } else if (selectedInductionTraining.value.trim() === "Uniform Record") {
                        employeeFileName.textContent = fileUploadDateInputValue + "-09-HR-ER-011 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                    } else if (selectedInductionTraining.value.trim() === "Employee Loan (Tools)") {
                        employeeFileName.textContent = fileUploadDateInputValue + "-09-HR-ER-013 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                    }
                } else if (selectedInductionTraining.value.trim() === "") {
                    fileUploadDateInput.classList.add("d-none");
                    employeeFileDropZone.classList.add("d-none");
                    employeeFileName.textContent = "";
                }
                checkEmployeeFileNameVisibility()
            });

            // ==================== 02 - R E S U M E, I D  A N D  Q U A L I F I C A T I O N S  (S E L E C T I O N) ====================
            selectedResumeIdQualifications.addEventListener("change", function () {
                if (selectedEmployeeFolder.value.trim() !== "" && selectedResumeIdQualifications.value.trim() !== "") {
                    fileUploadDateInput.classList.remove("d-none");
                    employeeFileDropZone.classList.remove("d-none");
                    if (selectedResumeIdQualifications.value.trim() === "Birth Certificate") {
                        employeeFileName.textContent = fileUploadDateInputValue + "-Birth-Cert (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                    } else if (selectedResumeIdQualifications.value.trim() === "CoE") {
                        employeeFileName.textContent = fileUploadDateInputValue + "-CoE (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                    } else if (selectedResumeIdQualifications.value.trim() === "ID") {
                        employeeFileName.textContent = fileUploadDateInputValue + "-ID (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                    } else if (selectedResumeIdQualifications.value.trim() === "Passport") {
                        employeeFileName.textContent = fileUploadDateInputValue + "-Passport (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                    } else if (selectedResumeIdQualifications.value.trim() === "Qualifications") {
                        employeeFileName.textContent = fileUploadDateInputValue + "-Qualifications (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                    } else if (selectedResumeIdQualifications.value.trim() === "Resume") {
                        employeeFileName.textContent = fileUploadDateInputValue + "-Resume (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                    } else if (selectedResumeIdQualifications.value.trim() === "Vevo") {
                        employeeFileName.textContent = fileUploadDateInputValue + "-Vevo (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                    } else if (selectedResumeIdQualifications.value.trim() === "Visa") {
                        employeeFileName.textContent = fileUploadDateInputValue + "-Visa (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                    }
                } else if (selectedResumeIdQualifications.value.trim() === "") {
                    fileUploadDateInput.classList.add("d-none");
                    employeeFileDropZone.classList.add("d-none");
                    employeeFileName.textContent = "";
                }
                checkEmployeeFileNameVisibility()
            });

            // ==================== 03 - A C C O U N T S  (S E L E C T I O N) ====================
            selectedAccounts.addEventListener("change", function () {
                if (selectedEmployeeFolder.value.trim() !== "" && selectedAccounts.value.trim() !== "") {
                    fileUploadDateInput.classList.remove("d-none");
                    employeeFileDropZone.classList.remove("d-none");
                    if (selectedAccounts.value.trim() === "Employee Details") {
                        employeeFileName.textContent = fileUploadDateInputValue + "-09-HR-ER-002 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                    } else if (selectedAccounts.value.trim() === "Tax File Number (Verified)") {
                        employeeFileName.textContent = fileUploadDateInputValue + "-TFN (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Verified";
                    } else if (selectedAccounts.value.trim() === "Superannuation (Verified)") {
                        employeeFileName.textContent = fileUploadDateInputValue + "-Super (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Verified";
                    } else if (selectedAccounts.value.trim() === "Tax File Number (Signed)") {
                        employeeFileName.textContent = fileUploadDateInputValue + "-TFN (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                    } else if (selectedAccounts.value.trim() === "Superannuation (Signed)") {
                        employeeFileName.textContent = fileUploadDateInputValue + "-Super (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                    }
                } else if (selectedAccounts.value.trim() === "") {
                    fileUploadDateInput.classList.add("d-none");
                    employeeFileDropZone.classList.add("d-none");
                    employeeFileName.textContent = "";
                }
                checkEmployeeFileNameVisibility()
            });

            // ==================== 04 - L E A V E (S E L E C T I O N) ====================
            selectedLeave.addEventListener("change", function () {
                let subFolder = ""; // Variable to store the subfolder path

                // Reset the subfolder text
                employeeFileDirectory.textContent = baseDirectoryPath;

                if (selectedEmployeeFolder.value.trim() !== "" && selectedLeave.value.trim() !== "") {
                    fileUploadDateInput.classList.remove("d-none");
                    employeeFileDropZone.classList.remove("d-none");

                    if (selectedLeave.value.trim() === "Medical Certificate") {
                        employeeFileName.textContent = fileUploadDateInputValue + "-Doc-Cert (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                        subFolder = "00 - Personal Leave\\";
                    } else if (selectedLeave.value.trim() === "Personal Leave") {
                        employeeFileName.textContent = fileUploadDateInputValue + "-09-HR-FO-007 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                        subFolder = "00 - Personal Leave\\";
                    } else if (selectedLeave.value.trim() === "Annual Leave") {
                        employeeFileName.textContent = fileUploadDateInputValue + "-09-HR-FO-007 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                        subFolder = "01 - Annual Leave\\";
                    } else if (selectedLeave.value.trim() === "Working From Home") {
                        employeeFileName.textContent = fileUploadDateInputValue + "-09-HR-FO-007 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                        subFolder = "02 - Working From Home\\";
                    } else if (selectedLeave.value.trim() === "Long Service Leave") {
                        employeeFileName.textContent = fileUploadDateInputValue + "-09-HR-FO-007 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                        subFolder = "03 - Long Service Leave\\";
                    }
                } else if (selectedLeave.value.trim() === "") {
                    fileUploadDateInput.classList.add("d-none");
                    employeeFileDropZone.classList.add("d-none");
                    employeeFileName.textContent = "";
                }

                // Combine base directory and subfolder
                if (subFolder) {
                    employeeFileDirectory.textContent += subFolder;
                }
                checkEmployeeFileNameVisibility()
            });

            // ==================== 05 - H R  A C T I O N S (S E L E C T I O N) ====================
            selectedHrActions.addEventListener("change", function () {
                if (selectedEmployeeFolder.value.trim() !== "" && selectedHrActions.value.trim() !== "") {
                    fileUploadDateInput.classList.remove("d-none");
                    employeeFileDropZone.classList.remove("d-none");
                    if (selectedHrActions.value.trim() === "Statement of Event") {
                        employeeFileName.textContent = fileUploadDateInputValue + "-09-HR-FO-013 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                    } else if (selectedHrActions.value.trim() === "Employee Warning") {
                        employeeFileName.textContent = fileUploadDateInputValue + "-09-HR-ER-012 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                    } else if (selectedHrActions.value.trim() === "Safety Violation Warning") {
                        employeeFileName.textContent = fileUploadDateInputValue + "-09-HR-ER-018 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                    }
                } else if (selectedHrActions.value.trim() === "") {
                    fileUploadDateInput.classList.add("d-none");
                    employeeFileDropZone.classList.add("d-none");
                    employeeFileName.textContent = "";
                }
                checkEmployeeFileNameVisibility()
            });

            // ==================== 06 - W O R K  C O M P E N S A T I O N (S E L E C T I O N) ====================
            selectedWorkCompensation.addEventListener("change", function () {
                if (selectedEmployeeFolder.value.trim() !== "" && selectedWorkCompensation.value.trim() !== "") {
                    fileUploadDateInput.classList.remove("d-none");
                    employeeFileDropZone.classList.remove("d-none");
                    if (selectedWorkCompensation.value.trim() === "Incident Form") {
                        employeeFileName.textContent = fileUploadDateInputValue + "-11-WH-FO-002 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                    } else if (selectedWorkCompensation.value.trim() === "Certificate of Capacity") {
                        employeeFileName.textContent = fileUploadDateInputValue + "-Certificate of Capacity (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                    }
                } else if (selectedWorkCompensation.value.trim() === "") {
                    fileUploadDateInput.classList.add("d-none");
                    employeeFileDropZone.classList.add("d-none");
                    employeeFileName.textContent = "";
                }
                checkEmployeeFileNameVisibility()
            })

            // ==================== 07 - E X I T  I N F O R M A T I O N (S E L E C T I O N) ====================
            selectedExitInformation.addEventListener("change", function () {
                if (selectedEmployeeFolder.value.trim() !== "" && selectedExitInformation.value.trim() !== "") {
                    fileUploadDateInput.classList.remove("d-none");
                    employeeFileDropZone.classList.remove("d-none");
                    if (selectedExitInformation.value.trim() === "Employee Exit") {
                        employeeFileName.textContent = fileUploadDateInputValue + "-09-HR-ER-009 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                    } else if (selectedExitInformation.value.trim() === "Acknowledgement of Resignation") {
                        employeeFileName.textContent = fileUploadDateInputValue + "-09-HR-ER-014 (" + empIdToUploadEmployeeFile + " " + empNameToUploadEmployeeFile + ") Signed";
                    }
                } else if (selectedExitInformation.value.trim() === "") {
                    fileUploadDateInput.classList.add("d-none");
                    employeeFileDropZone.classList.add("d-none");
                    employeeFileName.textContent = "";
                }
                checkEmployeeFileNameVisibility()
            })
        }
    });

</script>

<script>
    const employeeFileDropZone = document.getElementById('employeeFileDropZone');
    const employeeFileInput = document.getElementById('employeeFileInput');
    const employeeFileList = document.getElementById('employeeFileList');

    employeeFileDropZone.addEventListener('dragover', function (event) {
        event.preventDefault();
        employeeFileDropZone.classList.add('bg-light');
    });

    employeeFileDropZone.addEventListener('dragleave', function () {
        employeeFileDropZone.classList.remove('bg-light');
    });

    employeeFileDropZone.addEventListener('drop', function (event) {
        event.preventDefault();
        employeeFileDropZone.classList.remove('bg-light');
        const files = event.dataTransfer.files;
        updateEmployeeFileInput(files);
    });

    employeeFileInput.addEventListener('change', function (event) {
        updateEmployeeFileList(event.target.files);
    });

    function updateEmployeeFileInput(files) {
        const dataTransfer = new DataTransfer();

        // Add existing files (if any)
        for (let i = 0; i < employeeFileInput.files.length; i++) {
            dataTransfer.items.add(employeeFileInput.files[i]);
        }

        // Add new files
        for (let i = 0; i < files.length; i++) {
            dataTransfer.items.add(files[i]);
        }

        employeeFileInput.files = dataTransfer.files;
        updateEmployeeFileList(employeeFileInput.files);
    }

    function updateEmployeeFileList(files) {
        employeeFileList.innerHTML = ''; // Clear the list

        for (let i = 0; i < files.length; i++) {
            const listItem = document.createElement('div');
            listItem.className = 'd-flex justify-content-between align-items-center border p-2 mb-2';
            listItem.innerHTML = `
                <span>${files[i].name}</span>
                <button class="btn btn-danger btn-sm" type="button" onclick="removeEmployeeFile(${i})">Remove</button>
            `;
            employeeFileList.appendChild(listItem);
        }
    }

    function removeEmployeeFile(index) {
        const dataTransfer = new DataTransfer();

        for (let i = 0; i < employeeFileInput.files.length; i++) {
            if (i !== index) {
                dataTransfer.items.add(employeeFileInput.files[i]);
            }
        }

        employeeFileInput.files = dataTransfer.files;
        updateEmployeeFileList(employeeFileInput.files);
    }
</script>

<script>
    document.getElementById("uploadEmployeeFileForm").addEventListener("submit", function () {
        const directoryText = document.getElementById("employeeFileDirectory").textContent.trim();
        const fileNameText = document.getElementById("employeeFileName").textContent.trim();

        document.getElementById("hiddenEmployeeFileDirectory").value = directoryText;
        document.getElementById("hiddenEmployeeFileName").value = fileNameText;
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const form = document.getElementById("uploadEmployeeFileForm");
        const selectedEmployeeFolder = document.getElementById("selectedEmployeeFolder");

        form.addEventListener("submit", function (e) {
            e.preventDefault();

            const formData = new FormData(form);

            // Make sure you're appending necessary data if it's not part of the form
            formData.append("uploadEmployeeFile", "true"); // Or any other necessary field

            fetch("../AJAXphp/upload_employee_files.php", {
                method: "POST",
                body: formData
            })
                .then(response => response.json()) // parse the JSON response
                .then(data => {
                    console.log("Success:", data);
                    // Show the message from the JSON response
                    alert(data.message);

                    selectedEmployeeFolder.value = "";  // Reset the selected folder if needed

                    // Trigger the change event programmatically
                    selectedEmployeeFolder.dispatchEvent(new Event('change'));
                })
                .catch(error => {
                    // Handle error
                    console.error("Error:", error);
                    alert("Something went wrong.");
                });
        });
    });

</script>