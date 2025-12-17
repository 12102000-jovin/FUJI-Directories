<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('../db_connect.php');
require_once('../status_check.php');

date_default_timezone_set('Australia/Sydney');

$folder_name = "Human Resources";
require_once("../group_role_check.php");

// Sorting & Pagination
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'employee_id';
$order = isset($_GET['order']) ? $_GET['order'] : 'ASC';
$records_per_page = isset($_GET['recordsPerPage']) ? intval($_GET['recordsPerPage']) : 40;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $records_per_page;

// Search term
$searchTerm = isset($_GET['search']) ? trim($conn->real_escape_string($_GET['search'])) : '';

// Arrays to hold selected filter values
$selected_departments = [];
$filterApplied = false;

$whereClause = "is_active = 1";

if (isset($_GET['apply_filters'])) {
    if (isset($_GET['department']) && is_array($_GET['department'])) {
        $selected_departments = $_GET['department'];

        // Sanitize the department values
        $sanitized_departments = array_map(function ($dept) use ($conn) {
            return "'" . $conn->real_escape_string($dept) . "'";
        }, $selected_departments);

        $department_list = implode(',', $sanitized_departments);
        $whereClause .= " AND department IN ($department_list)";
        $filterApplied = true;
    }
}

// --- Step 1: Get all employees (active only) ---
$allEmployees = $conn->query("SELECT employee_id, first_name, last_name, nickname, payroll_type FROM employees WHERE $whereClause");

// --- Step 2: Get all QA documents (policies + competencies) ---
$qaDocs = [];
$qa_sql = "SELECT qa_document, document_name 
           FROM quality_assurance 
           WHERE qa_document LIKE '09-HR-PO-%' OR qa_document LIKE '11-WH-WI-%'";
$qa_result = $conn->query($qa_sql);
if ($qa_result) {
    while ($row = $qa_result->fetch_assoc()) {
        $qaDocs[$row['qa_document']] = $row['document_name'];
    }
}

// --- Step 3: Find matching document IDs by search term ---
$matchedDocIDs = [];
if (!empty($searchTerm)) {
    $searchLower = strtolower($searchTerm);
    foreach ($qaDocs as $docID => $docName) {
        if (stripos($docID, $searchTerm) !== false || stripos(strtolower($docName), $searchLower) !== false) {
            $matchedDocIDs[] = $docID;
        }
    }
}

// Get all QA documents for display mapping
$policies_sql = "SELECT qa_document, document_name FROM quality_assurance WHERE qa_document LIKE '09-HR-PO-%'";
$policies_result = $conn->query($policies_sql);

$competency_sql = "SELECT qa_document, document_name FROM quality_assurance WHERE qa_document LIKE '11-WH-WI-%'";
$competency_result = $conn->query($competency_sql);

$policyDocumentNames = [];
$competencyDocumentNames = [];

if ($policies_result) {
    while ($docRow = $policies_result->fetch_assoc()) {
        $policyDocumentNames[$docRow['qa_document']] = $docRow['document_name'];
    }
}
if ($competency_result) {
    while ($docRow = $competency_result->fetch_assoc()) {
        $competencyDocumentNames[$docRow['qa_document']] = $docRow['document_name'];
    }
}

// Store all policies in array for consistent counting
$allPolicies = [];
if ($policies_result) {
    $policies_result->data_seek(0);
    while ($policyRow = $policies_result->fetch_assoc()) {
        $allPolicies[$policyRow['qa_document']] = $policyRow['document_name'];
    }
}

// --- Step 4: Build matched employee list with counts ---
$employeeMatches = [];

if ($allEmployees && $allEmployees->num_rows > 0) {
    while ($row = $allEmployees->fetch_assoc()) {
        $employeeId = $row['employee_id'];
        $firstName = $row['first_name'];
        $lastName = $row['last_name'];
        $payrollType = $row['payroll_type'];

        $baseDir = ($payrollType === 'wage')
            ? 'D:\\FSMBEH-Data\\09 - HR\\04 - Wage Staff\\'
            : 'D:\\FSMBEH-Data\\09 - HR\\05 - Salary Staff\\';

        $policyDir = $baseDir . $employeeId . '\\00 - Employee Documents\\02 - Policies';
        $compDir = $baseDir . $employeeId . '\\01 - Induction and Training Documents';

        // Calculate counts for this employee
        $policyCount = 0;
        $compCount = 0;
        $employeePolicyFiles = [];

        // Count policies - COUNT DUPLICATES ONLY ONCE
        if ($policyDir && is_dir($policyDir)) {
            $policyFiles = scandir($policyDir);
            $uniquePolicyDocs = []; // Track unique policy documents

            foreach ($policyFiles as $file) {
                if (preg_match('/(09-HR-PO-\d+)/', $file, $matches)) {
                    $docKey = $matches[1];
                    if (isset($policyDocumentNames[$docKey])) {
                        // Only count each document type once, even if multiple files exist
                        if (!in_array($docKey, $uniquePolicyDocs)) {
                            $uniquePolicyDocs[] = $docKey;
                            $employeePolicyFiles[] = $docKey;
                            $policyCount++;
                        }
                    }
                }
            }
        }

        // Count missing policies
        $missingPoliciesCount = 0;
        if (!empty($allPolicies)) {
            $missingPoliciesCount = count($allPolicies) - count($employeePolicyFiles);
            $missingPoliciesCount = max(0, $missingPoliciesCount);
        }

        // Count work instructions - COUNT DUPLICATES ONLY ONCE
        if ($compDir && is_dir($compDir)) {
            $compFiles = scandir($compDir);
            $uniqueCompDocs = []; // Track unique competency documents

            foreach ($compFiles as $file) {
                if (preg_match('/(11-WH-WI-\d+)/', $file, $matches)) {
                    $docKey = $matches[1];
                    if (isset($competencyDocumentNames[$docKey])) {
                        // Only count each document type once, even if multiple files exist
                        if (!in_array($docKey, $uniqueCompDocs)) {
                            $uniqueCompDocs[] = $docKey;
                            $compCount++;
                        }
                    }
                }
            }
        }

        $hasMatch = false;

        // Search by document IDs (if applicable)
        if (!empty($matchedDocIDs)) {
            foreach ($matchedDocIDs as $docID) {
                if (
                    (is_dir($policyDir) && preg_grep("/$docID/i", scandir($policyDir))) ||
                    (is_dir($compDir) && preg_grep("/$docID/i", scandir($compDir)))
                ) {
                    $hasMatch = true;
                    break;
                }
            }
        }

        // Search by employee name or ID
        if (
            $hasMatch ||
            stripos($employeeId, $searchTerm) !== false ||
            stripos($firstName, $searchTerm) !== false ||
            stripos($lastName, $searchTerm) !== false
        ) {
            // Add the counts to the employee data
            $row['policy_count'] = $policyCount;
            $row['missing_policies_count'] = $missingPoliciesCount;
            $row['comp_count'] = $compCount;
            $employeeMatches[] = $row;
        }
    }
}

// --- Step 5: Sorting ---
usort($employeeMatches, function ($a, $b) use ($sort, $order) {
    // Handle numeric sorting for count fields
    if (in_array($sort, ['policy_count', 'missing_policies_count', 'comp_count'])) {
        $valueA = (int) $a[$sort];
        $valueB = (int) $b[$sort];
    } else {
        $valueA = strtolower($a[$sort]);
        $valueB = strtolower($b[$sort]);
    }

    if ($order === 'ASC') {
        return $valueA <=> $valueB;
    } else {
        return $valueB <=> $valueA;
    }
});

// --- Step 6: Pagination ---
$total_records = count($employeeMatches);
$total_pages = ceil($total_records / $records_per_page);
$paginatedEmployees = array_slice($employeeMatches, $offset, $records_per_page);

$urlParams = $_GET;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Work Instructions and Policies</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="shortcut icon" type="image/x-icon" href="../Images/FE-logo-icon.ico" />
    <style>
        .table thead th {
            background-color: #043f9d;
            color: white;
            border: 1px solid #043f9d !important;
        }

        .pagination .page-item.active .page-link {
            background-color: #043f9d;
            border-color: #043f9d;
            color: white;
        }

        .pagination .page-link {
            color: black
        }

        .sortable-header {
            cursor: pointer;
        }

        .sortable-header:hover {
            background-color: #022a6d !important;
        }
    </style>
</head>

<body class="background-color">
    <?php require("../Menu/NavBar.php") ?>
    <div class="container-fluid px-md-5 mb-5 mt-4">
        <div class="row mb-3 mt-3">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center">
                <div class="col-12 col-sm-8 col-lg-5 d-flex justify-content-between align-items-center mb-3 mb-sm-0">
                    <form method="GET" id="searchForm" class="d-flex align-items-center w-100">
                        <?php
                        foreach ($urlParams as $key => $value) {
                            if ($key === 'search')
                                continue; // skip search because it has its own input
                            // If the param is an array (e.g. filters with multiple values), add one hidden input per value
                            if (is_array($value)) {
                                foreach ($value as $v) {
                                    echo '<input type="hidden" name="' . htmlspecialchars($key) . '[]" value="' . htmlspecialchars($v) . '">';
                                }
                            } else {
                                echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
                            }
                        }
                        ?>
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="input-group me-2 flex-grow-1">
                                <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                                <input type="search" class="form-control" id="searchDocuments" name="search"
                                    placeholder="Search Documents" value="<?php echo htmlspecialchars($searchTerm) ?>">
                            </div>
                            <button class="btn" type="submit"
                                style="background-color:#043f9d; color: white; transition: 0.3s ease !important;">Search</button>
                            <div class="btn btn-danger ms-2">
                                <a class="dropdown-item" href="#" onclick="clearURLParameters()">Clear</a>
                            </div>
                            <button type="button" class="btn text-white ms-2 bg-dark" data-bs-toggle="modal"
                                data-bs-target="#filterProjectModal">
                                <p class="text-nowrap mb-0 pb-0">Filter by <i class="fa-solid fa-filter py-1"></i></p>
                            </button>
                        </div>
                    </form>
                </div>
                <button class="btn btn-dark" onclick="printDocuments()"><i
                        class="fa-solid fa-print me-1"></i>Print</button>
            </div>
        </div>

        <?php foreach ($urlParams as $key => $value): ?>
            <?php if (!empty($value) && $key === 'search'): ?>
                <span class="badge rounded-pill signature-bg-color text-white me-2 mb-2">
                    <strong><span class="text-warning">Search: </span><?php echo htmlspecialchars($value); ?></strong>
                    <a href="?<?php
                    $filteredParams = $_GET;
                    unset($filteredParams['search']);
                    echo http_build_query($filteredParams);
                    ?>" class="text-white ms-1"><i class="fa-solid fa-times"></i></a>
                </span>

            <?php elseif ($key === 'department' && is_array($value)): ?>
                <?php
                // Fetch all department names in one query
                $department_ids = array_map('intval', $value);
                $placeholders = str_repeat('?,', count($department_ids) - 1) . '?';
                $department_sql = "SELECT department_id, department_name FROM department WHERE department_id IN ($placeholders)";
                $stmt = $conn->prepare($department_sql);
                $stmt->bind_param(str_repeat('i', count($department_ids)), ...$department_ids);
                $stmt->execute();
                $result = $stmt->get_result();

                $department_names = [];
                while ($row = $result->fetch_assoc()) {
                    $department_names[$row['department_id']] = $row['department_name'];
                }
                $stmt->close();

                // Display badges
                foreach ($value as $department_id):
                    $department_name = $department_names[$department_id] ?? "Department $department_id";
                    ?>
                    <span class="badge rounded-pill signature-bg-color text-white me-2 mb-2">
                        <strong><span class="text-warning">Department:</span>
                            <?php echo htmlspecialchars($department_name); ?></strong>
                        <a href="?<?php
                        $filteredParams = $_GET;
                        $filteredParams['department'] = array_diff($filteredParams['department'], [$department_id]);
                        echo http_build_query($filteredParams);
                        ?>" class="text-white ms-1">
                            <i class="fa-solid fa-times"></i>
                        </a>
                    </span>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endforeach; ?>

        <?php if ($filterApplied): ?>
            <div class="alert <?php echo ($total_records == 0) ? 'alert-danger' : 'alert-info'; ?>">
                <?php if ($total_records > 0): ?>
                    <strong>Total Results:</strong>
                    <span class="fw-bold text-decoration-underline me-2"> <?php echo $total_records ?></span>
                <?php else: ?>
                    <strong>No results found for the selected filters.</strong>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="table-responsive rounded-3 shadow-lg bg-light m-0">
            <table class="table table-bordered table-hover mb-0 pb-0">
                <thead>
                    <tr>
                        <?php if ($role === "full control" || $role === "modify 1" || $role === "read") { ?>
                            <th style="min-width: 50px;"></th>
                        <?php } ?>
                        <th class="py-2 align-middle text-center sortable-header"
                            onclick="updateSort('employee_id', '<?= $sort === 'employee_id' && $order === 'ASC' ? 'DESC' : 'ASC' ?>')">
                            Employee ID
                            <i
                                class="fa-solid fa-sort fa-md ms-1 <?= $sort === 'employee_id' ? 'text-warning' : '' ?>"></i>
                            <?php if ($sort === 'employee_id'): ?>
                                <i
                                    class="fa-solid fa-arrow-<?= $order === 'ASC' ? 'up' : 'down' ?> fa-xs ms-1 text-warning"></i>
                            <?php endif; ?>
                        </th>
                        <th class="py-2 align-middle text-center sortable-header"
                            onclick="updateSort('first_name', '<?= $sort === 'first_name' && $order === 'ASC' ? 'DESC' : 'ASC' ?>')">
                            First Name
                            <i
                                class="fa-solid fa-sort fa-md ms-1 <?= $sort === 'first_name' ? 'text-warning' : '' ?>"></i>
                            <?php if ($sort === 'first_name'): ?>
                                <i
                                    class="fa-solid fa-arrow-<?= $order === 'ASC' ? 'up' : 'down' ?> fa-xs ms-1 text-warning"></i>
                            <?php endif; ?>
                        </th>
                        <th class="py-2 align-middle text-center sortable-header"
                            onclick="updateSort('last_name', '<?= $sort === 'last_name' && $order === 'ASC' ? 'DESC' : 'ASC' ?>')">
                            Last Name
                            <i
                                class="fa-solid fa-sort fa-md ms-1 <?= $sort === 'last_name' ? 'text-warning' : '' ?>"></i>
                            <?php if ($sort === 'last_name'): ?>
                                <i
                                    class="fa-solid fa-arrow-<?= $order === 'ASC' ? 'up' : 'down' ?> fa-xs ms-1 text-warning"></i>
                            <?php endif; ?>
                        </th>
                        <th class="py-2 align-middle text-center sortable-header"
                            onclick="updateSort('nickname', '<?= $sort === 'nickname' && $order === 'ASC' ? 'DESC' : 'ASC' ?>')">
                            Nickname
                            <i
                                class="fa-solid fa-sort fa-md ms-1 <?= $sort === 'nickname' ? 'text-warning' : '' ?>"></i>
                            <?php if ($sort === 'nickname'): ?>
                                <i
                                    class="fa-solid fa-arrow-<?= $order === 'ASC' ? 'up' : 'down' ?> fa-xs ms-1 text-warning"></i>
                            <?php endif; ?>
                        </th>
                        <th class="py-2 align-middle text-center sortable-header"
                            onclick="updateSort('policy_count', '<?= $sort === 'policy_count' && $order === 'ASC' ? 'DESC' : 'ASC' ?>')">
                            Policies
                            <i
                                class="fa-solid fa-sort fa-md ms-1 <?= $sort === 'policy_count' ? 'text-warning' : '' ?>"></i>
                            <?php if ($sort === 'policy_count'): ?>
                                <i
                                    class="fa-solid fa-arrow-<?= $order === 'ASC' ? 'up' : 'down' ?> fa-xs ms-1 text-warning"></i>
                            <?php endif; ?>
                        </th>
                        <th class="py-2 align-middle text-center sortable-header"
                            onclick="updateSort('comp_count', '<?= $sort === 'comp_count' && $order === 'ASC' ? 'DESC' : 'ASC' ?>')">
                            Work Instructions
                            <i
                                class="fa-solid fa-sort fa-md ms-1 <?= $sort === 'comp_count' ? 'text-warning' : '' ?>"></i>
                            <?php if ($sort === 'comp_count'): ?>
                                <i
                                    class="fa-solid fa-arrow-<?= $order === 'ASC' ? 'up' : 'down' ?> fa-xs ms-1 text-warning"></i>
                            <?php endif; ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($paginatedEmployees)) { ?>
                        <?php $counter = 0; ?>
                        <?php foreach ($paginatedEmployees as $row): ?>
                            <?php
                            $employeeId = $row['employee_id'];
                            $uniqueId = 'detailsRow' . $counter;
                            $payrollType = $row['payroll_type'];

                            $baseDir = ($payrollType === 'wage')
                                ? 'D:\\FSMBEH-Data\\09 - HR\\04 - Wage Staff\\'
                                : 'D:\\FSMBEH-Data\\09 - HR\\05 - Salary Staff\\';

                            $policiesDirectory = $baseDir . $employeeId . '\\00 - Employee Documents\\02 - Policies';
                            $competencyDirectory = $baseDir . $employeeId . '\\01 - Induction and Training Documents';
                            ?>
                            <tr>
                                <?php if ($role === "full control" || $role === "modify 1" || $role === "read") { ?>
                                    <td class="d-flex justify-content-center py-1 align-middle text-center">
                                        <button class="btn" data-bs-toggle="collapse" data-bs-target="#<?= $uniqueId ?>">
                                            <i class="fa-solid fa-file-lines text-warning"></i>
                                        </button>
                                    </td>
                                <?php } ?>
                                <td class="py-1 align-middle text-center"><?= htmlspecialchars($employeeId) ?></td>
                                <td class="py-1 align-middle text-center"><?= htmlspecialchars($row["first_name"]) ?></td>
                                <td class="py-1 align-middle text-center"><?= htmlspecialchars($row["last_name"]) ?></td>
                                <td class="py-1 align-middle text-center" <?= !empty($row["nickname"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= !empty(htmlspecialchars($row["nickname"])) ? htmlspecialchars($row["nickname"]) : "N/A" ?>
                                </td>
                                <td class="py-1 align-middle text-center"><?= $row['policy_count'] ?></td>
                                <td class="py-1 align-middle text-center"><?= $row['comp_count'] ?></td>
                            </tr>

                            <!-- Collapsible Details -->
                            <tr class="collapse bg-light" id="<?= $uniqueId ?>">
                                <td colspan="100%">
                                    <div class="p-3">
                                        <div class="row">
                                            <!-- Policies -->
                                            <div class="col-md-6">
                                                <?php
                                                // Recalculate for the detailed view (with full data)
                                                $detailedPolicyCount = 0;
                                                $employeePolicyFilesDetailed = [];
                                                $sortedPolicyDocuments = [];

                                                if ($policiesDirectory && is_dir($policiesDirectory)) {
                                                    $policyFiles = scandir($policiesDirectory);
                                                    $uniquePolicyDocsDetailed = [];

                                                    foreach ($policyFiles as $file) {
                                                        if (preg_match('/(09-HR-PO-\d+)/', $file, $matches)) {
                                                            $docKey = $matches[1];
                                                            if (isset($policyDocumentNames[$docKey])) {
                                                                // Only include each document type once in the detailed view
                                                                if (!in_array($docKey, $uniquePolicyDocsDetailed)) {
                                                                    $uniquePolicyDocsDetailed[] = $docKey;
                                                                    $sortedPolicyDocuments[$docKey] = [
                                                                        'file' => $file,
                                                                        'documentName' => $policyDocumentNames[$docKey]
                                                                    ];
                                                                    $employeePolicyFilesDetailed[] = $docKey;
                                                                    $detailedPolicyCount++;
                                                                }
                                                            }
                                                        }
                                                    }
                                                    ksort($sortedPolicyDocuments);
                                                }

                                                // Find missing policies for detailed view
                                                $missingPolicies = [];
                                                foreach ($allPolicies as $docKey => $docName) {
                                                    if (!in_array($docKey, $employeePolicyFilesDetailed)) {
                                                        $missingPolicies[$docKey] = $docName;
                                                    }
                                                }
                                                ?>
                                                <h6 class="section-title d-flex align-items-center">
                                                    Policies
                                                    <span class="badge bg-success ms-2"><?= $detailedPolicyCount ?></span>
                                                    <?php if (!empty($missingPolicies)): ?>
                                                        <span class="badge bg-danger ms-1"
                                                            title="Missing Policies"><?= count($missingPolicies) ?></span>
                                                    <?php endif; ?>
                                                </h6>

                                                <!-- Existing Policies -->
                                                <?php if (!empty($sortedPolicyDocuments)): ?>
                                                    <div class="mb-3">
                                                        <div class="table-responsive">
                                                            <table class="table table-bordered table-hover mb-0">
                                                                <thead>
                                                                    <tr>
                                                                        <th class="col-3">Document ID</th>
                                                                        <th>Document Name</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php foreach ($sortedPolicyDocuments as $docKey => $doc): ?>
                                                                        <tr>
                                                                            <td class="col-3">
                                                                                <a href="http://<?= htmlspecialchars($serverAddress) . '/' . htmlspecialchars($projectName) ?>/open-policies-file.php?employee_id=<?= htmlspecialchars($employeeId) ?>&folder=00 - Employee Documents&file=<?= htmlspecialchars($doc['file']) ?>"
                                                                                    target="_blank"
                                                                                    class="btn btn-link text-decoration-underline fw-bold p-0">
                                                                                    <?= htmlspecialchars($docKey) ?>
                                                                                </a>
                                                                            </td>
                                                                            <td><?= htmlspecialchars($doc['documentName']) ?></td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <p class='text-muted m-0'>No Policy Files Found</p>
                                                <?php endif; ?>

                                                <!-- Missing Policies -->
                                                <?php if (!empty($missingPolicies)): ?>
                                                    <div class="mt-3">
                                                        <small class="text-danger fw-bold">Missing Policies:</small>
                                                        <div class="table-responsive">
                                                            <table class="table table-bordered table-hover mb-0">
                                                                <thead>
                                                                    <tr>
                                                                        <th class="col-3">Document ID</th>
                                                                        <th>Document Name</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php foreach ($missingPolicies as $docKey => $docName): ?>
                                                                        <tr class="table-danger">
                                                                            <td class="text-danger col-3">
                                                                                <?= htmlspecialchars($docKey) ?>
                                                                            </td>
                                                                            <td class="text-danger"><?= htmlspecialchars($docName) ?>
                                                                            </td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if (!$policiesDirectory || !is_dir($policiesDirectory)): ?>
                                                    <p class='text-muted m-0'>Policies directory not found</p>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Work Instructions -->
                                            <div class="col-md-6">
                                                <?php
                                                $detailedCompCount = 0;
                                                $sortedCompDocuments = [];
                                                if ($competencyDirectory && is_dir($competencyDirectory)) {
                                                    $compFiles = scandir($competencyDirectory);
                                                    $uniqueCompDocsDetailed = [];

                                                    foreach ($compFiles as $file) {
                                                        if (preg_match('/(11-WH-WI-\d+)/', $file, $matches)) {
                                                            $docKey = $matches[1];
                                                            if (isset($competencyDocumentNames[$docKey])) {
                                                                // Only include each document type once in the detailed view
                                                                if (!in_array($docKey, $uniqueCompDocsDetailed)) {
                                                                    $uniqueCompDocsDetailed[] = $docKey;
                                                                    $sortedCompDocuments[$docKey] = [
                                                                        'file' => $file,
                                                                        'documentName' => $competencyDocumentNames[$docKey]
                                                                    ];
                                                                    $detailedCompCount++;
                                                                }
                                                            }
                                                        }
                                                    }
                                                    ksort($sortedCompDocuments);
                                                }
                                                ?>
                                                <h6 class="section-title d-flex align-items-center">
                                                    Work Instructions & Competencies
                                                    <span class="badge bg-success ms-2"><?= $detailedCompCount ?></span>
                                                </h6>
                                                <?php
                                                if ($competencyDirectory && is_dir($competencyDirectory)) {
                                                    if (!empty($sortedCompDocuments)) {
                                                        echo '<div class="table-responsive"><table class="table table-bordered table-hover mb-0"><thead><tr><th class="col-3">Document ID</th><th>Document Name</th></tr></thead><tbody>';
                                                        foreach ($sortedCompDocuments as $docKey => $doc) {
                                                            echo '<tr><td class="col-3"><a href="http://' . htmlspecialchars($serverAddress) . '/' . htmlspecialchars($projectName) . '/open-machine-competency-file.php?employee_id=' . htmlspecialchars($employeeId) . '&folder=01 - Induction and Training Documents&file=' . htmlspecialchars($doc['file']) . '" target="_blank" class="btn btn-link text-decoration-underline fw-bold p-0">' . htmlspecialchars($docKey) . '</a></td>';
                                                            echo '<td>' . htmlspecialchars($doc['documentName']) . '</td></tr>';
                                                        }
                                                        echo '</tbody></table></div>';
                                                    } else {
                                                        echo "<p class='text-muted m-0'>No Work Instruction/Competency Files Found</p>";
                                                    }
                                                } else {
                                                    echo "<p class='text-muted m-0'>Competencies directory not found</p>";
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php $counter++; ?>
                        <?php endforeach; ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="6" class="text-center py-3">No employees found.</td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
            <!-- Pagination Controls -->
            <div class="d-flex justify-content-end mt-3 pe-2">
                <div class="d-flex align-items-center me-2">
                    <p>Rows Per Page: </p>
                </div>

                <form method="GET" class="me-2">
                    <select class="form-select" name="recordsPerPage" id="recordsPerPage"
                        onchange="updateURLWithRecordsPerPage()">
                        <option value="40" <?php echo $records_per_page == 40 ? 'selected' : ''; ?>>40</option>
                        <option value="50" <?php echo $records_per_page == 50 ? 'selected' : ''; ?>>50</option>
                        <option value="100" <?php echo $records_per_page == 100 ? 'selected' : ''; ?>>100</option>
                    </select>
                </form>

                <!-- Pagination controls -->
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <!-- First Page Button -->
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" onclick="updatePage(1); return false;" aria-label="First"
                                    style="cursor: pointer">
                                    <span aria-hidden="true">&laquo;&laquo;</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link">&laquo;&laquo;</span>
                            </li>
                        <?php endif; ?>

                        <!-- Previous Button -->
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" onclick="updatePage(<?php echo $page - 1 ?>); return false;"
                                    aria-label="Previous" style="cursor: pointer">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link">&laquo;</span>
                            </li>
                        <?php endif; ?>

                        <!-- Page Numbers -->
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);

                        if ($end_page - $start_page < 4) {
                            $start_page = max(1, $end_page - 4);
                        }

                        for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" onclick="updatePage(<?php echo $i ?>); return false;"
                                    style="cursor: pointer">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <!-- Next Button -->
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" onclick="updatePage(<?php echo $page + 1; ?>); return false;"
                                    aria-label="Next" style="cursor: pointer">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link">&raquo;</span>
                            </li>
                        <?php endif; ?>

                        <!-- Last Page Button -->
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" onclick="updatePage(<?php echo $total_pages; ?>); return false;"
                                    aria-label="Last" style="cursor: pointer">
                                    <span aria-hidden="true">&raquo;&raquo;</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link">&raquo;&raquo;</span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <div class="modal fade" id="filterProjectModal" tabindex="-1" aria-labelledby="filterProjectModal"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filter</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="GET">
                        <?php
                        // Preserve current search param
                        if (!empty($searchTerm)) {
                            echo '<input type="hidden" name="search" value="' . htmlspecialchars($searchTerm) . '">';
                        }
                        // Also preserve other params like sort/order/page if needed
                        if (!empty($sort)) {
                            echo '<input type="hidden" name="sort" value="' . htmlspecialchars($sort) . '">';
                        }
                        if (!empty($order)) {
                            echo '<input type="hidden" name="order" value="' . htmlspecialchars($order) . '">';
                        }
                        if (!empty($page)) {
                            echo '<input type="hidden" name="page" value="' . htmlspecialchars($page) . '">';
                        }
                        ?>
                        <div class="row">
                            <div class="col-12">
                                <h5 class="signature-color fw-bold">Department</h5>
                                <?php
                                $department_sql = "SELECT * FROM department";
                                $department_result = $conn->query($department_sql);
                                $selected_departments = isset($_GET['department']) ? $_GET['department'] : [];
                                if ($department_result->num_rows > 0) { ?>
                                    <?php while ($row = $department_result->fetch_assoc()) { ?>
                                        <p class="mb-0 pb-0">
                                            <input type="checkbox" class="form-check-input"
                                                id="department_<?php echo $row['department_id']; ?>" name="department[]"
                                                value="<?php echo $row['department_id']; ?>" <?php echo in_array($row['department_id'], $selected_departments) ? 'checked' : ''; ?> />
                                            <label
                                                for="department_<?php echo $row['department_id']; ?>"><?php echo $row['department_name']; ?></label>
                                        </p>
                                    <?php } ?>
                                <?php } else { ?>
                                    <p>No departments found.</p>
                                <?php } ?>
                            </div>
                            <div class="d-flex justify-content-center mt-4">
                                <button class="btn btn-secondary me-1" type="button"
                                    data-bs-dismiss="modal">Cancel</button>
                                <button class="btn btn-dark" type="submit" name="apply_filters">Apply Filter</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function updateSort(sortField, sortOrder) {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('sort', sortField);
            urlParams.set('order', sortOrder);
            urlParams.set('page', '1'); // Reset to first page when sorting

            window.location.href = '?' + urlParams.toString();
        }

        function updatePage(newPage) {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('page', newPage);

            window.location.href = '?' + urlParams.toString();
        }

        function updateURLWithRecordsPerPage() {
            const recordsPerPage = document.getElementById('recordsPerPage').value;
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('recordsPerPage', recordsPerPage);
            urlParams.set('page', '1'); // Reset to first page when changing records per page

            window.location.href = '?' + urlParams.toString();
        }

        function clearURLParameters() {
            window.location.href = window.location.pathname;
        }
    </script>

    <script>
        function printDocuments() {
            // Create a new window for printing
            const printWindow = window.open('', '_blank');

            // Start building the print content
            let printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Employee Policies and Work Instructions</title>
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    margin: 10px;
                    line-height: 1.2;
                    color: #333;
                    background: white;
                    font-size: 11px;
                }
                .print-container {
                    max-width: 100%;
                    margin: 0 auto;
                }
                .header {
                    text-align: center;
                    margin-bottom: 10px;
                    padding-bottom: 8px;
                    border-bottom: 1px solid #043f9d;
                }
                .header h1 {
                    color: #043f9d;
                    margin: 0;
                    font-size: 16px;
                    font-weight: 600;
                }
                .print-info {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 10px;
                    padding: 5px;
                }
                .print-date {
                    font-weight: 600;
                    color: #043f9d;
                }
                .total-employees {
                    color: #043f9d;
                    font-weight: 600;
                }
                .employee-section { 
                    margin-bottom: 8px; 
                    page-break-inside: avoid;
                    padding: 6px;
                    border-bottom: 1px solid #eee;
                }
                .employee-name { 
                    font-weight: 700; 
                    font-size: 12px;
                    margin-bottom: 3px;
                    color: #2c3e50;
                }
                .nickname {
                    color: #666;
                    font-size: 10px;
                    font-weight: normal;
                    margin-left: 5px;
                }
                .document-line {
                    display: flex;
                    margin-bottom: 2px;
                }
                .section-label {
                    font-weight: 600;
                    min-width: 80px;
                    font-size: 10px;
                }
                .document-list {
                    font-family: 'Courier New', monospace;
                    font-size: 10px;
                    color: #495057;
                }
                .no-documents {
                    color: #999;
                    font-style: italic;
                    font-size: 10px;
                }
                @media print {
                    body { 
                        margin: 5px;
                        font-size: 10px;
                    }
                    .employee-section { 
                        page-break-inside: avoid;
                    }
                }
                @page {
                    margin: 0.3cm;
                }
            </style>
        </head>
        <body>
            <div class="print-container">
                <div class="header">
                    <h1>Employee Policies and Work Instructions</h1>
                </div>
                <div class="print-info">
                    <div class="print-date">
                        ${new Date().toLocaleDateString('en-AU')}
                    </div>
                    <div class="total-employees">${printData.length} employees</div>
                </div>
    `;

            // Get all employee data from PHP array
            <?php
            $printData = [];
            foreach ($paginatedEmployees as $row) {
                $employeeId = $row['employee_id'];
                $firstName = $row['first_name'];
                $lastName = $row['last_name'];
                $nickname = $row['nickname'];
                $payrollType = $row['payroll_type'];

                $baseDir = ($payrollType === 'wage')
                    ? 'D:\\FSMBEH-Data\\09 - HR\\04 - Wage Staff\\'
                    : 'D:\\FSMBEH-Data\\09 - HR\\05 - Salary Staff\\';

                $policiesDirectory = $baseDir . $employeeId . '\\00 - Employee Documents\\02 - Policies';
                $competencyDirectory = $baseDir . $employeeId . '\\01 - Induction and Training Documents';

                // Get policies
                $policies = [];
                if ($policiesDirectory && is_dir($policiesDirectory)) {
                    $policyFiles = scandir($policiesDirectory);
                    $uniquePolicyDocs = [];
                    foreach ($policyFiles as $file) {
                        if (preg_match('/(09-HR-PO-\d+)/', $file, $matches)) {
                            $docKey = $matches[1];
                            if (isset($policyDocumentNames[$docKey]) && !in_array($docKey, $uniquePolicyDocs)) {
                                $uniquePolicyDocs[] = $docKey;
                                $policies[] = $docKey;
                            }
                        }
                    }
                    sort($policies);
                }

                // Get work instructions
                $workInstructions = [];
                if ($competencyDirectory && is_dir($competencyDirectory)) {
                    $compFiles = scandir($competencyDirectory);
                    $uniqueCompDocs = [];
                    foreach ($compFiles as $file) {
                        if (preg_match('/(11-WH-WI-\d+)/', $file, $matches)) {
                            $docKey = $matches[1];
                            if (isset($competencyDocumentNames[$docKey]) && !in_array($docKey, $uniqueCompDocs)) {
                                $uniqueCompDocs[] = $docKey;
                                $workInstructions[] = $docKey;
                            }
                        }
                    }
                    sort($workInstructions);
                }

                $printData[] = [
                    'name' => $firstName . ' ' . $lastName,
                    'nickname' => $nickname,
                    'policies' => $policies,
                    'workInstructions' => $workInstructions
                ];
            }

            // Output the data as JavaScript
            echo 'const printData = ' . json_encode($printData) . ';';
            ?>

            // Build print content from the data
            printData.forEach((employee, index) => {
                const displayName = employee.name;
                const hasNickname = employee.nickname && employee.nickname !== 'N/A';

                printContent += `
                <div class="employee-section">
                    <div class="employee-name">
                        ${displayName}
                        ${hasNickname ? `<span class="nickname">(${employee.nickname})</span>` : ''}
                    </div>
        `;

                // Policies line
                printContent += `
                    <div class="document-line">
                        <span class="section-label">Policies:</span>
                        <span class="document-list">
        `;

                if (employee.policies.length > 0) {
                    printContent += `${employee.policies.join(', ')}`;
                } else {
                    printContent += `<span class="no-documents">none</span>`;
                }

                printContent += `</span></div>`;

                // Work Instructions line
                printContent += `
                    <div class="document-line">
                        <span class="section-label">Work Instructions:</span>
                        <span class="document-list">
        `;

                if (employee.workInstructions.length > 0) {
                    printContent += `${employee.workInstructions.join(', ')}`;
                } else {
                    printContent += `<span class="no-documents">none</span>`;
                }

                printContent += `</span></div>`;
                printContent += `</div>`;
            });

            printContent += `
            </div>
        </body>
        </html>
    `;

            // Write content to print window and trigger print
            printWindow.document.write(printContent);
            printWindow.document.close();

            // Print after a short delay to ensure content is loaded
            setTimeout(() => {
                printWindow.focus();
                printWindow.print();
                setTimeout(() => {
                    printWindow.close();
                }, 500);
            }, 300);
        }
    </script>

    <script>
        function printDocuments() {
            // Create a new window for printing
            const printWindow = window.open('', '_blank');

            // Start building the print content
            let printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Employee Policies and Work Instructions</title>
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    margin: 10px;
                    line-height: 1.2;
                    color: #333;
                    background: white;
                    font-size: 12px;
                }
                .employee-section { 
                    margin-bottom: 10px; 
                    padding: 5px;
                    border-bottom: 1px solid #eee;
                }
                .employee-name { 
                    font-weight: bold; 
                    margin-bottom: 3px;
                }
                .document-line {
                    margin-bottom: 2px;
                }
                .section-label {
                    font-weight: bold;
                    display: inline-block;
                    width: 120px;
                }
                @media print {
                    body { margin: 5px; }
                }
            </style>
        </head>
        <body>
            <h2>Employee Policies and Work Instructions</h2>
            <p><strong>Printed:</strong> ${new Date().toLocaleDateString()}</p>
            <hr>
    `;

            <?php
            // Build the PHP data for printing
            $printContent = "";
            foreach ($paginatedEmployees as $row) {
                $employeeId = $row['employee_id'];
                $firstName = $row['first_name'];
                $lastName = $row['last_name'];
                $nickname = $row['nickname'];
                $payrollType = $row['payroll_type'];

                $baseDir = ($payrollType === 'wage')
                    ? 'D:\\FSMBEH-Data\\09 - HR\\04 - Wage Staff\\'
                    : 'D:\\FSMBEH-Data\\09 - HR\\05 - Salary Staff\\';

                $policiesDirectory = $baseDir . $employeeId . '\\00 - Employee Documents\\02 - Policies';
                $competencyDirectory = $baseDir . $employeeId . '\\01 - Induction and Training Documents';

                // Get policies
                $policies = [];
                if ($policiesDirectory && is_dir($policiesDirectory)) {
                    $policyFiles = scandir($policiesDirectory);
                    $uniquePolicyDocs = [];
                    foreach ($policyFiles as $file) {
                        if (preg_match('/(09-HR-PO-\d+)/', $file, $matches)) {
                            $docKey = $matches[1];
                            if (isset($policyDocumentNames[$docKey]) && !in_array($docKey, $uniquePolicyDocs)) {
                                $uniquePolicyDocs[] = $docKey;
                                $policies[] = $docKey;
                            }
                        }
                    }
                    sort($policies);
                }

                // Get work instructions
                $workInstructions = [];
                if ($competencyDirectory && is_dir($competencyDirectory)) {
                    $compFiles = scandir($competencyDirectory);
                    $uniqueCompDocs = [];
                    foreach ($compFiles as $file) {
                        if (preg_match('/(11-WH-WI-\d+)/', $file, $matches)) {
                            $docKey = $matches[1];
                            if (isset($competencyDocumentNames[$docKey]) && !in_array($docKey, $uniqueCompDocs)) {
                                $uniqueCompDocs[] = $docKey;
                                $workInstructions[] = $docKey;
                            }
                        }
                    }
                    sort($workInstructions);
                }

                // Build the employee section
                $displayName = htmlspecialchars($firstName . ' ' . $lastName);
                $nicknameDisplay = ($nickname && $nickname !== 'N/A') ? ' (' . htmlspecialchars($nickname) . ')' : '';

                $policiesList = !empty($policies) ? implode(', ', $policies) : 'none';
                $workInstructionsList = !empty($workInstructions) ? implode(', ', $workInstructions) : 'none';

                $printContent .= "
            printContent += `
                <div class=\"employee-section\">
                    <div class=\"employee-name\">{$displayName}{$nicknameDisplay}</div>
                    <div class=\"document-line\">
                        <span class=\"section-label\">Policies:</span> {$policiesList}
                    </div>
                    <div class=\"document-line\">
                        <span class=\"section-label\">Work Instructions:</span> {$workInstructionsList}
                    </div>
                </div>
            `;";
            }
            echo $printContent;
            ?>

            printContent += `
        </body>
        </html>
    `;

            // Write content to print window and trigger print
            printWindow.document.write(printContent);
            printWindow.document.close();

            // Print after a short delay to ensure content is loaded
            setTimeout(() => {
                printWindow.focus();
                printWindow.print();
                // Don't close immediately to allow user to cancel print
                setTimeout(() => {
                    printWindow.close();
                }, 1000);
            }, 500);
        }
    </script>
</body>

</html>