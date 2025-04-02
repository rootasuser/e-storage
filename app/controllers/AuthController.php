<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../models/UserModel.php';

class AuthController {
    private $userModel;

    public function __construct() {
        $this->userModel = new UserModel();
    }

    public function register() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
            $first_name = htmlspecialchars($_POST['first_name']);
            $middle_name = htmlspecialchars($_POST['middle_name']);
            $last_name = htmlspecialchars($_POST['last_name']);
            $gender = htmlspecialchars($_POST['gender']);
            $role = htmlspecialchars($_POST['role']);
            $username = htmlspecialchars($_POST['username']);
            $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];

            if ($password !== $confirm_password) {
                $_SESSION['error'] = "Passwords do not match.";
                header("Location: ../public/index.php");
                exit;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['error'] = "Invalid email format.";
                header("Location: ../public/index.php");
                exit;
            }

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $profile_pic = null;

            if (!empty($_FILES['profile_pic']['tmp_name'])) {
                $profile_pic = file_get_contents($_FILES['profile_pic']['tmp_name']);
            }

            $data = [
                'first_name' => $first_name,
                'middle_name' => $middle_name,
                'last_name' => $last_name,
                'gender' => $gender,
                'role' => $role,
                'username' => $username,
                'email' => $email,
                'password' => $hashed_password,
                'profile_pic' => $profile_pic
            ];

            if ($this->userModel->registerUser($data)) {
                $_SESSION['success'] = "Registration request success.";
                header("Location: ../public/index.php");
                exit;
            } else {
                $_SESSION['error'] = "Registration failed.";
                header("Location: ../public/index.php");
                exit;
            }
        }
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                $_SESSION['error'] = "Invalid CSRF token.";
                header("Location: ../public/index.php");
                exit;
            }

            if (empty($_POST['usernameOrEmail']) || empty($_POST['password'])) {
                $_SESSION['error'] = "All fields are required.";
                header("Location: ../public/index.php");
                exit;
            }

            $usernameOrEmail = htmlspecialchars($_POST['usernameOrEmail']);
            $password = $_POST['password'];

            $user = $this->userModel->loginUser($usernameOrEmail, $password);

            if (!$user) {
                $_SESSION['error'] = "Invalid login credentials.";
                header("Location: ../public/index.php");
                exit;
            }

            if (!isset($user['id'])) {
                $_SESSION['error'] = "Err retrieving user data.";
                header("Location: ../public/index.php");
                exit;
            }

            session_regenerate_id(true);
            $_SESSION['user'] = $user;

            switch ($user['role']) {
                case 'Admin':
                    header("Location: ../app/views/admin/dashboard.php");
                    break;
                case 'Student':
                    header("Location: ../app/views/student/dashboard.php");
                    break;
                case 'Faculty':
                    header("Location: ../app/views/faculty/dashboard.php");
                    break;
                default:
                    $_SESSION['error'] = "Unauthorized role.";
                    header("Location: ../public/index.php");
                    break;
            }
            exit;
        }
    }
}

$auth = new AuthController();

if (isset($_POST['userRegister'])) {
    $auth->register();
}

if (isset($_POST['userLogin'])) {
    $auth->login();
}
