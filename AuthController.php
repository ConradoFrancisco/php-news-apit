<?php
require_once 'UserModel.php';
require_once 'vendor/autoload.php';
use Firebase\JWT\JWT;

class AuthController {
    private $userModel;
    private $jwtSecret = "your_jwt_secret";

    public function __construct($db) {
        $this->userModel = new UserModel($db);
    }

    public function login($data) {
        $email = $data['email'];
        $password = $data['password'];

        $user = $this->userModel->findByEmail($email);
        if (!$user) {
            return ['status' => 404, 'message' => 'Usuario no encontrado'];
        }

        if (!password_verify($password, $user['password'])) {
            return ['status' => 401, 'message' => 'Contraseña incorrecta'];
        }

        $payload = ['id' => $user['id'], 'role' => $user['role'], 'exp' => time() + (60 * 60)];
        $token = JWT::encode($payload, $this->jwtSecret, 'HS256');

        return ['status' => 200, 'token' => $token, 'user' => $user];
    }

    public function register($data) {
        $name = $data['name'];
        $email = $data['email'];
        $password = password_hash($data['password'], PASSWORD_BCRYPT);

        if ($this->userModel->findByEmail($email)) {
            return ['status' => 409, 'message' => 'El correo ya está en uso'];
        }

        $userId = $this->userModel->createUser($name, $email, $password);
        return ['status' => 201, 'message' => 'Usuario registrado', 'userId' => $userId];
    }

    public function forgotPassword($data) {
        $email = $data['email'];
        $user = $this->userModel->findByEmail($email);

        if (!$user) {
            return ['status' => 404, 'message' => 'Usuario no encontrado'];
        }

        $resetToken = JWT::encode(['userId' => $user['id'], 'exp' => time() + 3600], $this->jwtSecret, 'HS256');
        return ['status' => 200, 'resetToken' => $resetToken];
    }

    public function resetPassword($token, $newPassword) {
        $decoded = JWT::decode($token, $this->jwtSecret, ['HS256']);
        $userId = $decoded->userId;

        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $this->userModel->updatePassword($userId, $hashedPassword);
        return ['status' => 200, 'message' => 'Contraseña actualizada'];
    }
}
?>
