<?php
session_start();
require_once __DIR__ . '/../models/UserModel.php'; 

class DashboardController {
    private $userModel;

    public function __construct() {
        $this->userModel = new UserModel(); 
    }

    public function getUserDetails() {
        if (!isset($_SESSION['user'])) {
            header("Location: ../public/index.php"); 
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $user = $this->userModel->getUserById($userId);

        if (!$user) {
            die("Error: User ID $userId not found in database.");
        }

        return $user;
    }
}
?>
