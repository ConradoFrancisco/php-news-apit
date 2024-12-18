<?php

require_once 'NewsController.php';

$newsController = new NewsController();

// Rutas para manejar las noticias
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('@/api/news$@', $_SERVER['REQUEST_URI'])) {
    $newsController->create($_POST, $_FILES); // Pasar $_POST y $_FILES
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('@/api/news/?@', $_SERVER['REQUEST_URI'])) {
    // Obtener todas las noticias
    $queryParams = $_GET;
    $newsController->getAll($queryParams);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('@/api/single/(\d+)$@', $_SERVER['REQUEST_URI'], $matches)) {
    $id = $matches[1];
    $newsController->getById($id);
    exit;
}

// Cambiar el estado de la noticia
if ($_SERVER['REQUEST_METHOD'] === 'PUT' && preg_match('@/api/news/(\d+)/status@', $_SERVER['REQUEST_URI'], $matches)) {
    $id = $matches[1]; // Extraer el ID de la noticia desde la URL

    // Leer el cuerpo de la solicitud y decodificar JSON
    $body = file_get_contents("php://input");
    $putData = json_decode($body, true); // Decodificar a un array asociativo

    if ($putData === null) {
        // Error al decodificar JSON
        http_response_code(400);
        echo json_encode(["message" => "Datos del cuerpo inválidos"]);
        exit;
    }

    // Pasar los datos al controlador
    $newsController->setState($id, $putData);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('@/api/temp-create-user$@', $_SERVER['REQUEST_URI'])) {
    $newsController->createTemporaryUser();
    exit;
}
// Actualizar noticia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('@/api/update/(\d+)$@', $_SERVER['REQUEST_URI'], $matches)) {
    $id = $matches[1];
    $newsController->update($id, $_POST, $_FILES);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && preg_match('@/api/news/delete/(\d+)/?@', $_SERVER['REQUEST_URI'], $matches)) {
    // Eliminar noticia
    $id = $matches[1];
    $newsController->delete($id);
}


if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && preg_match('@/api/news/images/?@', $_SERVER['REQUEST_URI'])) {
    // Eliminar imagen de noticia
    $data = json_decode(file_get_contents("php://input"), true);
    $newsController->deleteImage($data);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('@/api/upload@', $_SERVER['REQUEST_URI'])) {
    $newsController->uploadImage();
    exit;
}
