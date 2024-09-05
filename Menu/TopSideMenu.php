<!DOCTYPE html>
<html lang="en">

<head>
    <title>FUJI Directories</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="shortcut icon" type="image/x-icon" href="Images/FE-logo-icon.ico" />
    <style>
        html,
        body {
            overflow-x: hidden;
            width: 100%;
            box-sizing: border-box;
            background-color: #eef3f9;
        }

        .sidebar {
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1001;
        }

        .sticky-top-menu {
            position: sticky;
            top: 0;
            z-index: 1000;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .list-unstyled li:hover {
            background-color: #043f9d;
            color: white !important;
        }

        #side-menu {
            width: 3.5rem;
            transition: width 0.2s ease;
        }
    </style>
</head>

<body>
    <div class="row d-none d-md-flex">
        <div class="col-auto pe-0 sidebar">
            <div class="d-flex flex-column bg-white min-vh-100 shadow-lg" id="side-menu" style="width:64px">
                <div class="d-flex justify-content-between align-items-center mt-1" id="menu-toggle">
                    <img src="../Images/FE-logo.png" class="m-2" style="width:3rem; cursor:pointer"
                        onclick="toggleNav()" />
                    <i class="btn fa-solid fa-xmark me-3 close-button d-none" id="close-menu" onclick="closeNav()"></i>
                </div>
                <ul class="list-unstyled mt-3 text-center">
                    <li class="mx-2 py-2 p-1 rounded fw-bold text-dark" id="home-icon">
                        <i class="fa-solid fa-house-chimney"></i>
                    </li>
                </ul>
            </div>
        </div>

        <div class="col p-0">
            <div class="sticky-top-menu">
                <div class="bg-white">
                    <div class="d-flex justify-content-between py-3 ms-2 me-5">
                        <h5 class="m-0 fw-bold" style="color: #043f9d;" id="top-menu-title"></h5>
                        <div>Profile</div>
                    </div>
                </div>
            </div>
            <?php require ("../Pages/home-index.php") ?>
        </div>
    </div>
    <div class="d-md-none">
        <div class="container-fluid bg-white shadow-sm">
            <div class="d-flex align-items-center">
                <button class="navbar-toggler btn" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                    aria-expanded="false" aria-label="Toggle navigation">
                    <i class="fa fa-bars fa-lg"></i>
                </button>
                <div class="d-flex align-items-center">
                    <img src="../Images/FE-logo.png" class="ms-2 py-3" style="width:3rem" />
                    <div class="vr mx-2 my-3"></div>
                    <h5 id="top-menu-title-small" style="color: #043f9d;" class="m-0 fw-bold"></h5>
                </div>
            </div>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="#">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Link</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">Dropdown</a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="#">Action</a></li>
                            <li><a class="dropdown-item" href="#">Another action</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="#">Something else here</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
        <?php require("../Pages/home-index.php") ?>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const menuToggle = document.getElementById("side-menu");
        const closeMenu = document.getElementById("close-menu");

        function toggleNav() {
            if (menuToggle.style.width === "250px") {
                menuToggle.style.width = "64px";
                closeMenu.classList.add("d-none");
            } else {
                menuToggle.style.width = "250px";
                closeMenu.classList.remove("d-none");
            }
        }

        function closeNav() {
            menuToggle.style.width = "64px";
            closeMenu.classList.add("d-none");
        }

        document.addEventListener("DOMContentLoaded", function () {
            const documentTitle = document.title;
            console.log(documentTitle);

            const topMenuTitle = document.getElementById("top-menu-title");
            topMenuTitle.textContent = documentTitle;

            const topMenuTitleSmall = document.getElementById("top-menu-title-small");
            topMenuTitleSmall.textContent = documentTitle;
        })

    </script>
</body>

</html>