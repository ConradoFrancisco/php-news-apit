<?php
require_once 'Database.php';

class UserModel {
    private $db;

    public function __construct() {
        // Usar el mismo método que en el NewsModel para la conexión
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByEmail($email) {
        $query = "SELECT * FROM users WHERE email = :email";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByName($name) {
        $query = "SELECT * FROM users WHERE name = :name";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    public function insertToken($email,$token,$expiresAt){

        $query = "INSERT INTO password_resets (email, token, expires_at) VALUES (:email, :token, :expires_at)";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->bindValue(':token', $token, PDO::PARAM_STR);
        $stmt->bindValue(':expires_at', $expiresAt, PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function markTokenAsUsed($token) {
        $query = "UPDATE password_resets SET used = 1 WHERE token = :token";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(":token", $token, PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function createUser($name, $email, $password, $role = 'user') {
        $query = "INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->bindValue(':password', $password, PDO::PARAM_STR);
        $stmt->bindValue(':role', $role, PDO::PARAM_STR);
        $stmt->execute();
        return $this->db->lastInsertId();
    }

    public function updatePassword($id, $newPassword) {
        $query = "UPDATE users SET password = :password WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':password', $newPassword, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function createTemporaryUser() {
        $name = "admin";
        $email = "conradofrancisco96@gmail.com";
        $password = password_hash("admin", PASSWORD_DEFAULT);
        $role = "admin";

        $query = "INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->bindValue(':password', $password, PDO::PARAM_STR);
        $stmt->bindValue(':role', $role, PDO::PARAM_STR);
        return $stmt->execute();
    }
    public function findToken($token) {
        $query = "SELECT * FROM password_resets WHERE token = :token";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':token', $token, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Actualizar contraseña por email
    public function updatePasswordByEmail($email, $newPassword) {
        $query = "UPDATE users SET password = :password WHERE email = :email";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':password', $newPassword, PDO::PARAM_STR);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        return $stmt->execute();
    }
}
?>
