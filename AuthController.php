<?php
require_once 'UserModel.php';
require_once 'vendor/autoload.php';
use Firebase\JWT\JWT;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Firebase\JWT\Key;

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

        $token = bin2hex(random_bytes(32)); // Token seguro
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
        // Guardar token en la base de datos
       $this->userModel->insertToken($email,$token,$expiresAt);

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
            $resetUrl = "https://dssolucionesdigitales.com.ar/gibson-2/reset-password/?token=$token";
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

    public function validateToken($data) {
        $token = $data['token'] ?? null;
    
        if (!$token) {
            http_response_code(400);
            echo json_encode(["message" => "Token no proporcionado"]);
            exit;
        }
    
        // Buscar el token en la base de datos
        $tokenData = $this->userModel->findToken($token);
    
        if (!$tokenData || $tokenData['used'] == 1) {
            http_response_code(400);
            echo json_encode(["message" => "Token inválido o ya fue utilizado"]);
            exit;
        }
    
        // Verificar si ha expirado
        if (strtotime($tokenData['expires_at']) < time()) {
            http_response_code(401);
            echo json_encode(["message" => "Token expirado"]);
            exit;
        }
    
        echo json_encode(["message" => "Token válido"]);
    }
    public function resetPassword($data) {
        $token = $data['token'] ?? null;
        $newPassword = $data['password'] ?? null;
    
        if (!$token || !$newPassword) {
            http_response_code(400);
            echo json_encode(["message" => "Token y nueva contraseña son obligatorios"]);
            exit;
        }
    
        // Obtener el token de la base de datos
        $tokenData = $this->userModel->findToken($token);
    
        if (!$tokenData || $tokenData['used'] == 1) {
            http_response_code(400);
            echo json_encode(["message" => "Token inválido o ya fue utilizado"]);
            exit;
        }
    
        if (strtotime($tokenData['expires_at']) < time()) {
            http_response_code(400);
            echo json_encode(["message" => "El token ha expirado"]);
            exit;
        }
    
        // Actualizar la contraseña
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $this->userModel->updatePasswordByEmail($tokenData['email'], $hashedPassword);
    
        $this->userModel->markTokenAsUsed($token);
        echo json_encode(["message" => "Contraseña restablecida con éxito"]);
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

    public function createUser($data){
        $name = $data['userName'];
        $email = $data['email'];
        $password = $data['password'];
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        try{
            $this->userModel->createUser($name,$email,$hashedPassword,'user');
            http_response_code(201);
            echo json_encode(['status' => 201,"message" =>"usuario creado correctamente","data" => $data]);
        }catch(Exception $e){
            http_response_code(500);
            echo json_encode(['status' => 500, 'message' => 'Error al crear el usuario']);
        }
    }

}
?>
