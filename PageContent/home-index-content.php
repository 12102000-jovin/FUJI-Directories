<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$config = include ('./../config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];


// SQL Query to get the folders
$folders_sql = "SELECT DISTINCT f.*
FROM folders f
JOIN groups_folders gf ON f.folder_id = gf.folder_id
JOIN users_groups ug ON gf.group_id = ug.group_id
JOIN users u ON ug.user_id = u.user_id
JOIN employees e ON e.employee_id = u.employee_id
WHERE e.employee_id = $employee_id;
";
$folders_result = $conn->query($folders_sql);
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
        .card-body:hover {
            background-color: #3665b1;
            cursor: pointer;
        }
        .card-body{
            background-color: #043f9d !important;
        }

        .sidebar {
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar::-webkit-scrollbar {
            display: none;
        }

        .sticky-top-menu {
            position: sticky;
            top: 0;
            z-index: 1000;
            background-color: white;
        }
    </style>
</head>

<body style="background-color: #eef3f9;">
    <div class="container-fluid">
        <?php if ($folders_result->num_rows > 0): ?>
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
                <?php while ($row = $folders_result->fetch_assoc()): ?>
                    <?php
                    $initials = '';
                    $words = explode(' ', $row['folder_name']); // Split the folder name into words
                    foreach ($words as $word) {
                        $initials .= strtoupper(substr($word, 0, 1)); // Extract the first letter of each word
                    }
                    ?>
                    <div class="col">
                        <a href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/<?= strtolower($initials) ?>-index.php" class="text-decoration-none">
                            <div class="card text-white">
                                <div class="card-body signature-bg-color text-center rounded py-4 position-relative"
                                    role="button">                
                                    <i class="fa-solid fa-folder fa-6x position-relative text-warning" style="z-index: 1;"></i>
                                    <h5 class="card-title position-absolute top-50 start-50 translate-middle pb-4 fw-bold"
                                        style="z-index: 2;"><?php echo $initials ?></h5>
                                    <p class="card-text fw-bold mt-2"><?php echo $row['folder_name'] ?></p>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="d-flex justify-content-center">
                    <div class="alert alert-danger col-md-6 text-center" role="alert">
                        You have no access to any folder.
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>