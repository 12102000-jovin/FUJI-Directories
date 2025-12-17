<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$config = include('./../config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];

// Define department emails
$department_emails = [
    'Research & Development' => 'rnd@smbeharwal.fujielectric.com',
    'Accounts' => 'accounts@smbeharwal.fujielectric.com',
    'Sales / Estimating' => 'sales@smbeharwal.fujielectric.com',
    'Engineering' => 'harwal.engineering@smbeharwal.fujielectric.com',
    'Site' => 'site@smbeharwal.fujielectric.com',
    'Electrical' => 'electrical@smbeharwal.fujielectric.com'
];

// SQL Employees for employees that has work phone number / extension number
$employees_sql = "SELECT 
employees.employee_id,
employees.first_name, 
employees.last_name, 
employees.nickname,
employees.position, 
employees.email, 
employees.extension_num,
employees.work_phone_number,
position.position_name,
department.department_name
FROM employees 
JOIN position ON employees.position = position.position_id 
JOIN department ON employees.department = department.department_id
WHERE extension_num IS NOT NULL OR work_phone_number IS NOT NULL OR email IS NOT NULL AND is_active = 1
ORDER BY department.department_name, employees.employee_id, position.position_name";
$employees_result = $conn->query($employees_sql);

// Fetch all employees into an array first
$employees = [];
while ($row = $employees_result->fetch_assoc()) {
    $employees[] = $row;
}

// Group employees by department
$departments = [];
foreach ($employees as $employee) {
    $dept_name = $employee["department_name"];
    if (!isset($departments[$dept_name])) {
        $departments[$dept_name] = [];
    }
    $departments[$dept_name][] = $employee;
}

// Split departments between left and right columns
$left = [];
$right = [];
$left_count = 0;
$right_count = 0;

foreach ($departments as $dept_name => $dept_employees) {
    $dept_size = count($dept_employees);

    // Decide which column gets this department
    if ($left_count <= $right_count) {
        $left[$dept_name] = $dept_employees;
        $left_count += $dept_size;
    } else {
        $right[$dept_name] = $dept_employees;
        $right_count += $dept_size;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Home</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="./../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="shortcut icon" type="image/x-icon" href="Images/FE-logo-icon.ico" />

    <style>
        .table-responsive {
            transform: scale(0.75);
            transform-origin: top left;
            width: 133.3%;
        }

        .pagination .page-item.active .page-link {
            background-color: #043f9d;
            border-color: #043f9d;
            color: white;
        }

        .pagination .page-link {
            color: black
        }
    </style>
</head>

<body>
    <div class="container-fluid mt-5" id="staffDirectory">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center">
                <h3 class="fw-bold mb-0">Internal Staff Directory </h3>
                <!-- <span class="badge bg-danger ms-2"> <i class="fa-solid fa-person-digging me-1"></i>In progress</span> -->
            </div>
            <!-- <div class="d-flex align-items-center">
                <div class="input-group me-2">
                    <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="search" class="form-control" id="searchEmployees" name="search"
                        placeholder="Search Employees">
                </div>
            </div> -->
        </div>
        <div class="row">
            <!-- Left column -->
            <div class="col-12 col-xl-6">
                <?php foreach ($left as $dept_name => $dept_employees): ?>
                    <table class="table table-bordered table-sm table-hovered" style="table-layout: fixed;">
                        <colgroup>
                            <col style="width: 24%;">
                            <col style="width: 24%;">
                            <col style="width: 34%;">
                            <col style="width: 4%;">
                            <col style="width: 14%;">
                        </colgroup>
                        <thead class="table-dark">
                            <tr>
                                <td colspan="5">
                                    <div class="d-flex">
                                        <small class="fw-bold"><?php echo htmlspecialchars($dept_name); ?></small>
                                        <?php if (isset($department_emails[$dept_name])): ?>
                                            <br>
                                            <small class="department-email"><span class="ms-1">-
                                                    <a href="mailto:<?php echo $department_emails[$dept_name]; ?>"
                                                        class="text-light"> </span>
                                                <?php echo $department_emails[$dept_name]; ?>
                                                </a>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dept_employees as $row): ?>
                                <tr style="font-size: 0.6rem;">
                                    <td class="text-wrap text-break align-middle">
                                        <small style="font-size: 0.6rem;">
                                            <?php
                                            $fullName = htmlspecialchars($row["first_name"] . " " . $row["last_name"]);
                                            if (!empty($row["nickname"])) {
                                                $fullName .= " (" . htmlspecialchars($row["nickname"]) . ")";
                                            }
                                            echo $fullName;
                                            ?>
                                        </small>
                                    </td>
                                    <td class="text-wrap text-break align-middle">
                                        <small
                                            style="font-size: 0.6rem;"><?php echo htmlspecialchars($row["position_name"]); ?></small>
                                    </td>
                                    <td class="text-wrap text-break align-middle">
                                        <div class="d-flex align-items-center w-100">
                                            <?php if (!empty($row["email"])): ?>
                                                <small class="flex-grow-1" style="font-size: 0.6rem;">
                                                    <a href="mailto:<?php echo htmlspecialchars($row["email"]); ?>">
                                                        <?php echo htmlspecialchars($row["email"]); ?>
                                                    </a>
                                                </small>
                                                <i class="fa-regular fa-copy ms-2 fa-xs copy-icon" style="cursor:pointer;"
                                                    onclick="copyToClipboard(this, '<?php echo htmlspecialchars($row["email"]); ?>')"></i>
                                                <small class="copy-feedback text-success ms-1"
                                                    style="display:none; font-size: 0.5rem;">Copied</small>
                                            <?php else: ?>
                                                <small style="font-size: 0.6rem;">N/A</small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="text-wrap text-break align-middle">
                                        <small
                                            style="font-size: 0.6rem;"><?php echo !empty($row["extension_num"]) ? htmlspecialchars($row["extension_num"]) : "N/A"; ?></small>
                                    </td>
                                    <td class="text-wrap text-break align-middle">
                                        <div class="d-flex align-items-center w-100">
                                            <?php if (!empty($row["work_phone_number"])): ?>
                                                <small class="flex-grow-1"
                                                    style="font-size: 0.6rem;"><?php echo htmlspecialchars($row["work_phone_number"]); ?></small>
                                                <i class="fa-regular fa-copy ms-2 fa-xs copy-icon" style="cursor:pointer;"
                                                    onclick="copyToClipboard(this, '<?php echo htmlspecialchars($row["work_phone_number"]); ?>')"></i>
                                                <small class="copy-feedback text-success ms-1"
                                                    style="display:none; font-size: 0.5rem;">Copied</small>
                                            <?php else: ?>
                                                <small style="font-size: 0.6rem;">N/A</small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endforeach; ?>
            </div>

            <!-- Right column -->
            <div class="col-12 col-xl-6">
                <?php foreach ($right as $dept_name => $dept_employees): ?>
                    <table class="table table-bordered table-sm table-hovered" style="table-layout: fixed;">
                        <colgroup>
                            <col style="width: 24%;">
                            <col style="width: 24%;">
                            <col style="width: 34%;">
                            <col style="width: 4%;">
                            <col style="width: 14%;">
                        </colgroup>
                        <thead class="table-dark">
                            <tr>
                                <td colspan="5">
                                    <div class="d-flex">
                                        <small class="fw-bold"><?php echo htmlspecialchars($dept_name); ?></small>
                                        <?php if (isset($department_emails[$dept_name])): ?>
                                            <br>
                                            <small class="department-email"><span class="ms-1">-
                                                    <a href="mailto:<?php echo $department_emails[$dept_name]; ?>"
                                                        class="text-light"> </span>
                                                <?php echo $department_emails[$dept_name]; ?>
                                                </a>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dept_employees as $row): ?>
                                <tr style="font-size: 0.6rem;">
                                    <td class="text-wrap text-break align-middle">
                                        <small style="font-size: 0.6rem;">
                                            <?php
                                            $fullName = htmlspecialchars($row["first_name"] . " " . $row["last_name"]);
                                            if (!empty($row["nickname"])) {
                                                $fullName .= " (" . htmlspecialchars($row["nickname"]) . ")";
                                            }
                                            echo $fullName;
                                            ?>
                                        </small>
                                    </td>
                                    <td class="text-wrap text-break align-middle">
                                        <small
                                            style="font-size: 0.6rem;"><?php echo htmlspecialchars($row["position_name"]); ?></small>
                                    </td>
                                    <td class="text-wrap text-break align-middle">
                                        <div class="d-flex align-items-center w-100">
                                            <?php if (!empty($row["email"])): ?>
                                                <small class="flex-grow-1" style="font-size: 0.6rem;">
                                                    <a href="mailto:<?php echo htmlspecialchars($row["email"]); ?>">
                                                        <?php echo htmlspecialchars($row["email"]); ?>
                                                    </a>
                                                </small>
                                                <i class="fa-regular fa-copy ms-2 fa-xs copy-icon" style="cursor:pointer;"
                                                    onclick="copyToClipboard(this, '<?php echo htmlspecialchars($row["email"]); ?>')"></i>
                                                <small class="copy-feedback text-success ms-1"
                                                    style="display:none; font-size: 0.5rem;">Copied</small>
                                            <?php else: ?>
                                                <small style="font-size: 0.6rem;">N/A</small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="text-wrap text-break align-middle">
                                        <small
                                            style="font-size: 0.6rem;"><?php echo !empty($row["extension_num"]) ? htmlspecialchars($row["extension_num"]) : "N/A"; ?></small>
                                    </td>
                                    <td class="text-wrap text-break align-middle">
                                        <div class="d-flex align-items-center w-100">
                                            <?php if (!empty($row["work_phone_number"])): ?>
                                                <small class="flex-grow-1"
                                                    style="font-size: 0.6rem;"><?php echo htmlspecialchars($row["work_phone_number"]); ?></small>
                                                <i class="fa-regular fa-copy ms-2 fa-xs copy-icon" style="cursor:pointer;"
                                                    onclick="copyToClipboard(this, '<?php echo htmlspecialchars($row["work_phone_number"]); ?>')"></i>
                                                <small class="copy-feedback text-success ms-1"
                                                    style="display:none; font-size: 0.5rem;">Copied</small>
                                            <?php else: ?>
                                                <small style="font-size: 0.6rem;">N/A</small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        function copyToClipboard(icon, text) {
            // Method 1: Modern clipboard API (preferred)
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(() => {
                    showCopyFeedback(icon);
                }).catch(err => {
                    // If modern API fails, fall back to legacy method
                    fallbackCopyToClipboard(icon, text);
                });
            } else {
                // Method 2: Legacy method for older browsers or HTTP
                fallbackCopyToClipboard(icon, text);
            }
        }

        function fallbackCopyToClipboard(icon, text) {
            // Create a temporary textarea element
            const textArea = document.createElement("textarea");
            textArea.value = text;

            // Make the textarea invisible
            textArea.style.position = "fixed";
            textArea.style.left = "-999999px";
            textArea.style.top = "-999999px";
            document.body.appendChild(textArea);

            // Select and copy
            textArea.focus();
            textArea.select();

            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    showCopyFeedback(icon);
                } else {
                    console.error('Fallback copy method failed');
                }
            } catch (err) {
                console.error('Fallback copy method failed:', err);
            }

            // Clean up
            document.body.removeChild(textArea);
        }

        function showCopyFeedback(icon) {
            const feedback = icon.nextElementSibling; // small "Copied"

            // Change icon to check mark
            const originalClass = icon.className;
            icon.className = 'fa-solid fa-check ms-2 text-success';

            // Show the "Copied" text
            feedback.style.display = 'inline';

            // Revert back after 1 second
            setTimeout(() => {
                icon.className = originalClass; // restore clipboard icon
                feedback.style.display = 'none'; // hide text
            }, 1000);
        }
    </script>

</body>

</html>