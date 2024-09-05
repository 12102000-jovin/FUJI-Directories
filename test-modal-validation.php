<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bootstrap Tabs Example</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>

    <?php $role = "user"; ?>

    <div class="container mt-3">
        <ul class="nav nav-pills nav-justified">
            <?php if ($role == "admin") { ?>
                <li class="nav-item">
                    <a class="nav-link active" id="home-tab" data-bs-toggle="tab" href="#home">Home</a>
                </li>
            <?php } ?>
            <?php if ($role == "user") { ?>
                <li class="nav-item">
                    <a class="nav-link" id="profile-tab" data-bs-toggle="tab" href="#profile">Profile</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="contact-tab" data-bs-toggle="tab" href="#contact">Contact</a>
                </li>
            <?php } ?>
        </ul>

        <div class="tab-content mt-3">
            <?php if ($role == "admin") { ?>
                <div class="tab-pane fade show active" id="home">
                    <h4>Home</h4>
                    <p>Content for the Home tab.</p>
                </div>
            <?php } ?>
            <?php if ($role == "user") { ?>
                <div class="tab-pane fade" id="profile">
                    <h4>Profile</h4>
                    <p>Content for the Profile tab.</p>
                </div>

                <div class="tab-pane fade show active" id="contact">
                    <h4>Contact</h4>
                    <p>Content for the Contact tab.</p>
                </div>
            <?php } ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>

</html>