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

    <div class="background-color">
        <div class="container">
            <div class="d-flex justify-content-center align-itmes-center min-vh-100">
                <div class="card rounded p-5 border-0 shadow-lg">

                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const employeeIdInput = document.getElementById("employeeId");
        const usernameInput = document.getElementById("username");
        const passwordInput = document.getElementById("password");
        const systemRole = document.getElementById("role");

        employeeIdInput.addEventListener('change', function () {
            const selectedEmployee = employeeIdInput.options[employeeIdInput.selectedIndex].text;
            console.log(selectedEmployee);

            // Regular expression to match the pattern "Name (ID)"
            const regex = /^(.+?)\s*\((\d+)\)$/;
            const match = selectedEmployee.match(regex);

            if (match) {
                const fullName = match[1];
                const id = match[2];

                const nameParts =fullName.split(' ');
                console.log(fullName);
                console.log(nameParts[0]);

                // Set the value of the username input field
                usernameInput.value =nameParts[0].toLowerCase() + nameParts[1].toLowerCase();
                passwordInput.value =nameParts[0].toLowerCase() + "_" + id;
            }
        })
    })
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Edit button click event handler
        document.querySelectorAll('.editRoleBtn').forEach(function (btn) {
            btn.addEventListener('click',function 
        })
    })
</script>

</html>