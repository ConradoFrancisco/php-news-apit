<?php

require_once 'NewsController.php';

$newsController = new NewsController();

// Rutas para manejar las noticias
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('@/api/news/?$@', $_SERVER['REQUEST_URI'])) {
    // Crear noticia
    $newsController->create($_POST);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('@/api/news/?$@', $_SERVER['REQUEST_URI'])) {
    // Obtener todas las noticias
    $queryParams = $_GET;
    $newsController->getAll($queryParams);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('@/api/news/(\d+)/?$@', $_SERVER['REQUEST_URI'], $matches)) {
    // Obtener noticia por ID
    $id = $matches[1];
    $newsController->getById($id);
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT' && preg_match('@/api/news/(\d+)/?$@', $_SERVER['REQUEST_URI'], $matches)) {
    // Actualizar noticia
    parse_str(file_get_contents("php://input"), $putVars);
    $id = $matches[1];
    $newsController->update($id, $putVars);
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && preg_match('@/api/news/(\d+)/?$@', $_SERVER['REQUEST_URI'], $matches)) {
    // Eliminar noticia
    $id = $matches[1];
    $newsController->delete($id);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('@/api/news/(\d+)/state/?$@', $_SERVER['REQUEST_URI'], $matches)) {
    // Cambiar estado de noticia
    parse_str(file_get_contents("php://input"), $postVars);
    $id = $matches[1];
    $newsController->setState($id, $postVars);
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && preg_match('@/api/news/images/?$@', $_SERVER['REQUEST_URI'])) {
    // Eliminar imagen de noticia
    parse_str(file_get_contents("php://input"), $postVars);
    $newsController->deleteImage($postVars);
}
