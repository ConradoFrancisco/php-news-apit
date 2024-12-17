<?php
require_once 'UserModel.php';
require_once 'vendor/autoload.php';
use Firebase\JWT\JWT;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class AuthController {
    private $userModel;
    private $jwtSecret = "your_jwt_secret";

    public function __construct() {
        $this->userModel = new UserModel();
    }

    public function login($data) {
        $email = $data['email'];
        $password = $data['password'];
        
        
        $user = $this->userModel->findByEmail($email);
        if (!$user) {
            http_response_code(404);
            echo json_encode(['status' => 404, 'message' => 'Usuario no encontrado']);
            exit;
        }

        if (!password_verify($password, $user['password'])) {
            http_response_code(401);
            echo json_encode(['status' => 401, 'message' => 'Contraseña incorrecta']);
            exit;
        }

        $payload = ['id' => $user['id'], 'role' => $user['role'], 'exp' => time() + (60 * 60)];
        $token = JWT::encode($payload, $this->jwtSecret, 'HS256');

        http_response_code(200);
        echo json_encode(['status' => 200, 'token' => $token, 'user' => $user]);
        exit;
    }

    public function forgotPassword($data) {
        $email = $data['email'];

        // Buscar el usuario por email
        $user = $this->userModel->findByEmail($email);
        if (!$user) {
            http_response_code(404);
            echo json_encode(['status' => 404, 'message' => 'Usuario no encontrado']);
            exit;
        }

        // Crear un token con expiración de 1 hora
        $resetToken = JWT::encode(
            ['userId' => $user['id'], 'exp' => time() + 3600],
            $this->jwtSecret,
            'HS256'
        );

        // Enviar correo con PHPMailer
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->CharSet = 'UTF-8';
            $mail->Host = 'sandbox.smtp.mailtrap.io'; // Servidor de Mailtrap
            $mail->SMTPAuth = true;
            $mail->Username = '5085d1ab75ba14'; // Tus credenciales de Mailtrap
            $mail->Password = '872ee55221e32b';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 2525;

            $mail->setFrom('no-reply@example.com', 'Soporte');
            $mail->addAddress($email);

            // Configurar el mensaje
            $resetUrl = "http://localhost:5173/gibson-2/reset-password/$resetToken";
            $mail->isHTML(true);
            $mail->Subject = 'Restablecer contraseña';
            $mail->Body = "
                <h1>Restablecimiento de contraseña</h1>
                <p>Hola, {$user['name']}.</p>
                <p>Haga clic en el siguiente enlace para restablecer su contraseña. El enlace expirará en 1 hora:</p>
                <a href='{$resetUrl}'>Restablecer contraseña</a>
                <p>Si no solicitaste este correo, ignóralo.</p>
            ";

            $mail->send();
            echo json_encode(['status' => 200, 'message' => 'Correo enviado con éxito']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 500, 'message' => 'Error al enviar el correo', 'error' => $mail->ErrorInfo]);
        }
    }


    public function createTemporaryUser() {
        if ($this->userModel->createTemporaryUser()) {
            http_response_code(201);
            echo json_encode(['status' => 201, 'message' => 'Usuario admin creado']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 500, 'message' => 'Error al crear el usuario admin']);
        }
        exit;
    }
}
?>
