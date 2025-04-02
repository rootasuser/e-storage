<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../../app/models/UserModel.php';
require_once __DIR__ . '/../../app/controllers/AuthController.php';

$userModel = new UserModel();
$auth = new AuthController($userModel);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <title>E-Storage</title>
    <link rel="stylesheet" href="../public/assets/css/base.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo-title">
            <img src="../public/assets/images/logo.jpg" alt="Logo" class="custom-logo-img">
            <h1>E-Storage</h1>
        </div>
    </nav>

    <div class="container">
        <div class="right-content">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="error-message"><?= htmlspecialchars($_SESSION['error']); ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="success-message"><?= htmlspecialchars($_SESSION['success']); ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <div id="login-form">
                <h2>SIGN IN</h2>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                    <input type="text" name="usernameOrEmail" placeholder="Username or Email" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <button type="submit" name="userLogin">Login</button>
                </form>
                <div class="mb-3">
                <p>Not registered? <a href="#" onclick="switchForm()">Sign Up</a></p>
                </div>
            </div>

            <div id="register-form" class="hidden">
                <h2>Signup Account</h2>
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                    <input type="file" name="profile_pic" id="profile-pic">
                    <input type="text" name="first_name" placeholder="First Name" required>
                    <input type="text" name="middle_name" placeholder="Middle Name">
                    <input type="text" name="last_name" placeholder="Last Name" required>
                    <select name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                    <input type="text" name="username" placeholder="Username" required>
                    <input type="email" name="email" placeholder="Email" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <input type="hidden" name="role" value="Student">
                    <input type="password" name="confirm_password" placeholder="Confirm password" required>
                    <button type="submit" name="userRegister">Register</button>
                </form>
                <div class="mb-3">
                <p>Already have an account? <a href="#" onclick="switchForm()">Sign In</a></p>
                </div>
              
            </div>
        </div>
    </div>
    
    <script src="./../public/assets/js/form.switch.js" defer></script>
</body>
</html>
