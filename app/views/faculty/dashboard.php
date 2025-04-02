<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '../../../models/UserModel.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../../public/index.php");
    exit;
}

$userModel = new UserModel();
$user = $userModel->getUserById($_SESSION['user']['id']);

function getInitials($firstName, $lastName) {
    return strtoupper(substr($firstName, 0, 1)) . strtoupper(substr($lastName, 0, 1));
}

function generateAvatar($initials) {
    $width = 100;
    $height = 100;
    $image = imagecreatetruecolor($width, $height);
    $colorChoice = rand(1, 3);

    switch ($colorChoice) {
        case 1:
            $bgColor = imagecolorallocate($image, rand(0, 100), rand(150, 255), rand(0, 100));
            break;
        case 2:
            $bgColor = imagecolorallocate($image, rand(0, 100), rand(0, 100), rand(150, 255));
            break;
        case 3:
            $bgColor = imagecolorallocate($image, rand(150, 255), rand(0, 100), rand(0, 100));
            break;
        default:
            $bgColor = imagecolorallocate($image, 255, 255, 255);
            break;
    }

    $textColor = imagecolorallocate($image, 255, 255, 255);
    imagefill($image, 0, 0, $bgColor);
    $font = 5;
    $bbox = imagefontwidth($font) * strlen($initials);
    $textHeight = imagefontheight($font);
    $x = ($width - $bbox) / 2;
    $y = ($height - $textHeight) / 2;
    imagestring($image, $font, $x, $y, $initials, $textColor);

    ob_start();
    imagepng($image);
    $imageData = ob_get_clean();
    imagedestroy($image);

    return 'data:image/png;base64,' . base64_encode($imageData);
}

if (empty($user['profile_pic'])) {
    $initials = getInitials($user['first_name'], $user['last_name']);
    $profileImage = generateAvatar($initials);
} else {
    $profileImage = htmlspecialchars($user['profile_pic']);
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <title>Faculty Dashboard - E-Storage</title>
    <link rel="stylesheet" href="../../public/assets/css/base.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo-title">
            <img src="../../public/assets/images/logo.jpg" alt="Logo" class="custom-logo-img">
            <h1>Faculty Dashboard</h1>
        </div>
    </nav>

    <div class="container">
        <h2>Welcome, <?= htmlspecialchars($user['first_name']) . ' ' . htmlspecialchars($user['last_name']); ?>!</h2>
        <p>Email: <?= htmlspecialchars($user['email']); ?></p>
        <p>Role: <?= htmlspecialchars($user['role']); ?></p>
        <p>Gender: <?= htmlspecialchars($user['gender']); ?></p>

        <!-- Display Profile Picture or Generated Initials -->
        <img src="<?= $profileImage; ?>" alt="Profile Picture" width="100" height="100" style="border-radius: 50%;">

        <a href="/e-storage/public/logout">Logout</a>
    </div>
</body>
</html>
