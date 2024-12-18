<?php
require_once 'AuthController.php';

$authController = new AuthController();

header('Content-Type: application/json; charset=utf-8');

// Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('@/api/auth/login$@', $_SERVER['REQUEST_URI'])) {
    $data = json_decode(file_get_contents("php://input"), true);
    $authController->login($data);
    exit;
}

// Crear usuario temporal
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('@/api/auth/create-temp-user$@', $_SERVER['REQUEST_URI'])) {
    $authController->createTemporaryUser();
    exit;
}

// 3. Forgot Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('@/api/auth/forgot-password$@', $_SERVER['REQUEST_URI'])) {
    header('Content-Type: application/json; charset=utf-8');
    $data = json_decode(file_get_contents("php://input"), true);
    $authController->forgotPassword($data);
    exit;
}

// 4. Reset Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('@/api/auth/reset-password/(\w+)$@', $_SERVER['REQUEST_URI'], $matches)) {
    header('Content-Type: application/json; charset=utf-8');
    $token = $matches[1];
    $data = json_decode(file_get_contents("php://input"), true);
    $authController->resetPassword($token, $data['password']);
    exit;
}

// 5. Validar Token (opcional, para verificar si un token es válido)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('@/api/auth/validate-reset-token$@', $_SERVER['REQUEST_URI'])) {
    $token = $_GET['token'] ?? '';
    $authController->validateResetToken($token);
    exit;
}

// 6. Cambiar Contraseña
if ($_SERVER['REQUEST_METHOD'] === 'PUT' && preg_match('@/api/auth/change-password$@', $_SERVER['REQUEST_URI'])) {
    header('Content-Type: application/json; charset=utf-8');
    $data = json_decode(file_get_contents("php://input"), true);
    $authHeader = getallheaders()['Authorization'] ?? '';
    $authController->changePassword($authHeader, $data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('@/api/auth/reset-password$@', $_SERVER['REQUEST_URI'])) {
    $data = json_decode(file_get_contents("php://input"), true);
    $authController->resetPassword($data);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('@/api/auth/validate-token$@', $_SERVER['REQUEST_URI'])) {
    $data = json_decode(file_get_contents("php://input"), true);
    $authController->validateToken($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('@/api/auth/create-user$@', $_SERVER['REQUEST_URI'])) {
    $data = json_decode(file_get_contents("php://input"), true);
    $authController->createUser($data);
    exit;
}

?>
