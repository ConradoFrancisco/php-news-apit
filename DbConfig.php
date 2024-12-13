<?php
$usuario = 'root';
$contraseña= '';

$mbd = new PDO('mysql:host=localhost;dbname=authDB', $usuario, $contraseña);
$sql = "SELECT * from users ";
$stmt = $mbd->query($sql); 
    
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
$json = json_encode($usuarios);
var_dump($json);
?>