<?php
class Router {
    public function run() {
        $url = isset($_GET['url']) ? rtrim($_GET['url'], '/') : 'home/index';
        $url = explode('/', $url);
        
        if ($url[0] === 'logout') {
            require_once __DIR__ . '/../controllers/LogoutController.php';
            $logoutController = new LogoutController();
            $logoutController->logout();
            exit;
        }

        $controllerName = ucfirst($url[0]) . "Controller"; 
        $method = isset($url[1]) ? $url[1] : "index";

        $controllerPath = "../app/controllers/" . $controllerName . ".php";

        if (file_exists($controllerPath)) {
            require_once $controllerPath;
            $controller = new $controllerName();

            if (method_exists($controller, $method)) {
                call_user_func_array([$controller, $method], []);
            } else {
                $_SESSION['404'] = "Method '$method' not found in $controllerName!";
            }
        } else {
            $_SESSION['404'] = "Controller '$controllerName' not found!";
        }
    }
}
