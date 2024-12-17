<?php

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        $host = 'localhost'; // Cambia por tu host
        $dbname = 'c2012330_desa'; // Cambia por tu base de datos
        $username = 'c2012330_desa'; // Cambia por tu usuario
        $password = 'zuVEzono31'; // Cambia por tu contraseña

        try {
            $this->connection = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Error al conectar a la base de datos: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }
}
