<?php
class LogoutController {
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];

        session_regenerate_id(true);

        session_destroy();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, 
                $params["path"], $params["domain"] ?? '', 
                $params["secure"] ?? false, $params["httponly"] ?? false
            );
        }
        header("Location: /e-storage/public/index.php");
        exit;
    }
}
?>
