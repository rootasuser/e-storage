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
        imagecolorallocate($im, rand(0, 100), rand(150, 255), rand(0, 100)),   // Green
        imagecolorallocate($im, rand(0, 100), rand(0, 100), rand(150, 255)),   // Blue
        imagecolorallocate($im, rand(150, 255), rand(0, 100), rand(0, 100))    // Red
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
    <title>Dashboard - <?php echo $system_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<nav class="fixed top-0 z-50 w-full bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700">
  <div class="px-3 py-3 lg:px-5 lg:pl-3">
    <div class="flex items-center justify-between">
      <div class="flex items-center justify-start rtl:justify-end">
        <button data-drawer-target="logo-sidebar" data-drawer-toggle="logo-sidebar" aria-controls="logo-sidebar" type="button" class="inline-flex items-center p-2 text-sm text-gray-500 rounded-lg sm:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600">
            <span class="sr-only">Open sidebar</span>
            <svg class="w-6 h-6" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
               <path clip-rule="evenodd" fill-rule="evenodd" d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zm0 10.5a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5a.75.75 0 01-.75-.75zM2 10a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 10z"></path>
            </svg>
         </button>
        <a href="?page=analytics" class="flex ms-2 md:me-24">
          <img src="../../../public/assets/images/logo.jpg" class="h-8 me-3" alt=" Logo" style="border-radius: 50%;" />
          <span class="self-center text-xl font-semibold sm:text-2xl whitespace-nowrap dark:text-white"><?php echo $system_title; ?></span>
        </a>
      </div>
      <div class="flex items-center">
          <div class="flex items-center ms-3">
            <div>
              <button type="button" class="flex text-sm bg-gray-800 rounded-full focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600" aria-expanded="false" data-dropdown-toggle="dropdown-user">
                <span class="sr-only">Open user menu</span>
                <img class="w-8 h-8 rounded-full" src="<?= $user['profile_pic'] ? 'data:image/jpeg;base64,' . base64_encode($user['profile_pic']) : $profileImage ?>" alt="user photo">
              </button>
            </div>
            <div class="z-50 hidden my-4 text-base list-none bg-white divide-y divide-gray-100 rounded-sm shadow-sm dark:bg-gray-700 dark:divide-gray-600" id="dropdown-user">
              <div class="px-4 py-3" role="none">
                <p class="text-sm text-gray-900 dark:text-white" role="none">
                <?= htmlspecialchars($user['first_name']) . ' ' . htmlspecialchars($user['last_name']); ?>
                </p>
                <p class="text-sm font-medium text-gray-900 truncate dark:text-gray-300" role="none">
                <?= htmlspecialchars($user['email']); ?>
                </p>
              </div>
              <ul class="py-1" role="none">
                <li>
                  <a href="?page=account" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Account</a>
                </li>
                <li>
                  <a href="/e-storage/public/logout" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Sign out</a>
                </li>
              </ul>
            </div>
          </div>
        </div>
    </div>
  </div>
</nav>
<aside id="logo-sidebar" class="fixed top-0 left-0 z-40 w-64 h-screen pt-20 transition-transform -translate-x-full bg-white border-r border-gray-200 sm:translate-x-0 dark:bg-gray-800 dark:border-gray-700" aria-label="Sidebar">
   <div class="h-full px-3 pb-4 overflow-y-auto bg-white dark:bg-gray-800">
      <ul class="space-y-2 font-medium">
         <li>
            <a href="?page=analytics" class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group">
               <i class="fas fa-tachometer-alt w-5 h-5 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
               <span class="ms-3"><?php echo $dashboard_name; ?></span>
            </a>
         </li>
         <li>
            <a href="?page=research_title" class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group">
               <i class="fas fa-book-open w-5 h-5 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
               <span class="flex-1 ms-3 whitespace-nowrap"><?php echo $courses_name; ?></span>
            </a>
         </li>
         <li>
            <a href="?page=documents" class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group">
               <i class="fas fa-file-alt w-5 h-5 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
               <span class="flex-1 ms-3 whitespace-nowrap"><?php echo $requests_name; ?></span>
            </a>
         </li>
         <li>
            <a href="?page=logs" class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group">
               <i class="fas fa-history w-5 h-5 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
               <span class="flex-1 ms-3 whitespace-nowrap"><?php echo $activity_name; ?></span>
            </a>
         </li>
         <li>
            <a href="?page=users" class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group">
               <i class="fas fa-users w-5 h-5 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
               <span class="flex-1 ms-3 whitespace-nowrap"><?php echo $user_management_name; ?></span>
            </a>
         </li>
      </ul>
   </div>
</aside>
<main class="mt-20 p-4 sm:ml-64">
        <div class="container mx-auto">
        <?php
            $page = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'analytics';
            $allowedPages = ['analytics', 'research_title', 'documents', 'search_and_filter', 'reports', 'logs', 'users', 'account'];
            if (!in_array($page, $allowedPages, true)) { $page = '404'; }
            $viewFile = __DIR__ . '/templates/' . $page . '.php';
            if (is_readable($viewFile)) { include $viewFile; } else { http_response_code(404); echo '<h2>404 - Page Not Found</h2>'; }
        ?>
        </div>
    </main>

<script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
</body>
</html>
