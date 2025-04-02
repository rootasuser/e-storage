<?php
if (session_status() == PHP_SESSION_NONE) {
   session_start();
}
require_once __DIR__ . '/../../models/UserModel.php';
require_once __DIR__ . '/../../core/ok.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../../public/index.php");
    exit;
}

$userModel = new UserModel();
$user = $userModel->getUserById($_SESSION['user']['id']);

function getInitials($first, $last) {
    return strtoupper(substr($first, 0, 1) . substr($last, 0, 1));
}

function generateAvatar($initials) {
    $w = $h = 30;
    $im = imagecreatetruecolor($w, $h);
    $colors = [
        imagecolorallocate($im, rand(0, 100), rand(150, 255), rand(0, 100)),   
        imagecolorallocate($im, rand(0, 100), rand(0, 100), rand(150, 255)),  
        imagecolorallocate($im, rand(150, 255), rand(0, 100), rand(0, 100))    
    ];
    $bg = $colors[array_rand($colors)];
    imagefill($im, 0, 0, $bg);
    $font = 5;
    $x = ($w - imagefontwidth($font) * strlen($initials)) / 2;
    $y = ($h - imagefontheight($font)) / 2;
    imagestring($im, $font, $x, $y, $initials, imagecolorallocate($im, 255, 255, 255));
    ob_start();
    imagepng($im);
    $data = ob_get_clean();
    imagedestroy($im);
    return 'data:image/png;base64,' . base64_encode($data);
}

$profileImage = empty($user['profile_pic'])
    ? generateAvatar(getInitials($user['first_name'], $user['last_name']))
    : htmlspecialchars($user['profile_pic']);
?>



<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="devwin">

    <title>Dashboard</title>

    <!-- Custom fonts for this template-->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="../../../public/assets/css/m-windev.css" rel="stylesheet">

</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <ul class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar" style="background-color: #00296b;">

            <!-- Sidebar - Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="?page=analytics">
   
                <div class="sidebar-brand-text mx-3"><sup>STUDENT PANEL</sup></div>
            </a>

            <!-- Divider -->
            <hr class="sidebar-divider my-0">

            <!-- Nav Item - Dashboard -->
            <li class="nav-item active">
                <a class="nav-link" href="?page=analytics">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span></a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

           
            <!-- Heading -->
            <div class="sidebar-heading">
                Data Management
            </div>

          

            <!-- Nav Item -  -->
            <li class="nav-item">
                <a class="nav-link" href="?page=browse">
                    <i class="fas fa-fw fa-globe"></i>
                    <span>Browse Research</span></a>
            </li>

            <!-- Nav Item -  -->
            <li class="nav-item">
                <a class="nav-link" href="?page=requests">
                    <i class="fas fa-fw fa-code-pull-request"></i>
                    <span>Requests</span></a>
            </li>

 <!-- Nav Item -  -->
 <li class="nav-item">
                <a class="nav-link" href="?page=bookmarks">
                    <i class="fas fa-fw fa-bookmark"></i>
                    <span>Bookmarks</span></a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider d-none d-md-block">

            <!-- Sidebar Toggler (Sidebar) -->
            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>

        

        </ul>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

                    <!-- Sidebar Toggle (Topbar) -->
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                  
                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ml-auto">

                       
                     

                        <div class="topbar-divider d-none d-sm-block"></div>

                        <!-- Nav Item - User Information -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?= htmlspecialchars($user['first_name']) . ' ' . htmlspecialchars($user['last_name']); ?></span>
                                <img class="img-profile rounded-circle"
                                    src="<?= $user['profile_pic'] ? 'data:image/jpeg;base64,' . base64_encode($user['profile_pic']) : $profileImage ?>">
                            </a>
                            <!-- Dropdown - User Information -->
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="?page=profile">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Profile
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="/e-storage/public/logout" data-toggle="modal" data-target="#logoutModal">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Logout
                                </a>
                            </div>
                        </li>

                    </ul>

                </nav>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">
             

                    <?php
            $page = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'analytics';
            $allowedPages = ['analytics', 'browse', 'requests', 'bookmarks', 'profile', 'logs', 'users', 'account'];
            if (!in_array($page, $allowedPages, true)) { $page = '404'; }
            $viewFile = __DIR__ . '/templates/' . $page . '.php';
            if (is_readable($viewFile)) { include $viewFile; } else { http_response_code(404); echo '<h2>404 - Page Not Found</h2>'; }
        ?>
            
                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

         

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-primary" href="/e-storage/public/logout">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="../../../public/assets/jQuery/jquery.min.js"></script>
    <script src="../../../public/assets/bootstrap/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="../../../public/assets/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="../../../public/assets/js/m-windev.js"></script>



</body>

</html>

