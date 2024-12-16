<?php
require_once 'Database.php';
require_once 'AuthController.php';

$db = (new Database())->connect();
$authController = new AuthController($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $endpoint = $_GET['endpoint'] ?? '';

    switch ($endpoint) {
        case 'login':
            $data = json_decode(file_get_contents("php://input"), true);
            echo json_encode($authController->login($data));
            break;

        case 'register':
            $data = json_decode(file_get_contents("php://input"), true);
            echo json_encode($authController->register($data));
            break;

        case 'forgot-password':
            $data = json_decode(file_get_contents("php://input"), true);
            echo json_encode($authController->forgotPassword($data));
            break;

        case 'reset-password':
            $token = $_GET['token'] ?? '';
            $data = json_decode(file_get_contents("php://input"), true);
            echo json_encode($authController->resetPassword($token, $data['password']));
            break;

        default:
            http_response_code(404);
            echo json_encode(['status' => 404, 'message' => 'Endpoint no encontrado']);
    }
}
?>
