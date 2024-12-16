<?php

require_once 'NewsModel.php';

class NewsController {
    private $newsModel;

    public function __construct() {
        $this->newsModel = new NewsModel();
    }

    // Crear una noticia
    public function create($request) {
        try {
            $title = $request['title'] ?? null;
            $content = $request['content'] ?? null;
            $author = $request['author'] ?? null;
            $date = $request['date'] ?? null;
            $files = $_FILES['images'] ?? null;

            if (!$title || !$content) {
                http_response_code(400);
                echo json_encode(["message" => "El título y el contenido son obligatorios"]);
                return;
            }

            // Crear la noticia
            $newsId = $this->newsModel->createNews($title, $content, $author, $date);

            // Procesar imágenes
            if ($files && $files['tmp_name'][0]) {
                $imagePaths = [];
                foreach ($files['tmp_name'] as $index => $tmpName) {
                    $fileName = uniqid() . "-" . basename($files['name'][$index]);
                    $uploadPath = "uploads/" . $fileName;
                    move_uploaded_file($tmpName, $uploadPath);
                    $imagePaths[] = $uploadPath;
                }
                $this->newsModel->createImages($newsId, $imagePaths);
            }
            header("Content-Type: application/json; charset=UTF-8");
            echo json_encode(["message" => "Noticia creada con éxito", "newsId" => $newsId]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error al crear noticia", "error" => $e->getMessage()]);
        }
    }

    // Obtener todas las noticias
    public function getAll($queryParams) {
        
        try {
            $offset = isset($queryParams['offset']) ? intval($queryParams['offset']) : 0;
            $limit = isset($queryParams['limit']) ? intval($queryParams['limit']) : 10;
            $title = $queryParams['title'] ?? null;
            $status = isset($queryParams['status']) ? (int) $queryParams['status'] : null;
            $startDate = $queryParams['startDate'] ?? null;
            $endDate = $queryParams['endDate'] ?? null;
            $result = $this->newsModel->getAllNews($offset, $limit, $title, $status, $startDate, $endDate);
            header("Content-Type: application/json; charset=UTF-8");
            echo json_encode(["noticias" => $result['news'], "total" => $result['total'],"queryParams" => $queryParams]);
            return json_encode(["noticias" => $result['news'], "total" => $result['total']]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error al obtener noticias", "error" => $e->getMessage()]);
        }
    }

    // Obtener una noticia por ID
    public function getById($id) {
        try {
            $news = $this->newsModel->getNewsById($id);

            if (!$news) {
                http_response_code(404);
                echo json_encode(["message" => "Noticia no encontrada"]);
                return;
            }

            echo json_encode($news);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error al obtener noticia", "error" => $e->getMessage()]);
        }
    }

    // Actualizar una noticia
    public function update($id, $request) {
        try {
            $title = $request['title'] ?? null;
            $content = $request['content'] ?? null;
            $author = $request['author'] ?? null;
            $date = $request['date'] ?? null;
            $existingImages = $request['existingImages'] ?? [];
            $files = $_FILES['images'] ?? null;

            if (!$title || !$content) {
                http_response_code(400);
                echo json_encode(["message" => "El título y el contenido son obligatorios"]);
                return;
            }

            $this->newsModel->updateNews($id, $title, $content, $author, $date);

            // Procesar nuevas imágenes
            if ($files && $files['tmp_name'][0]) {
                $imagePaths = [];
                foreach ($files['tmp_name'] as $index => $tmpName) {
                    $fileName = uniqid() . "-" . basename($files['name'][$index]);
                    $uploadPath = "uploads/" . $fileName;
                    move_uploaded_file($tmpName, $uploadPath);
                    $imagePaths[] = $uploadPath;
                }
                $this->newsModel->createImages($id, $imagePaths);
            }

            // Eliminar imágenes que no están en `existingImages`
            $currentImages = $this->newsModel->getImagesByNewsId($id);
            $imagesToDelete = array_diff($currentImages, $existingImages);

            foreach ($imagesToDelete as $imageUrl) {
                $this->newsModel->deleteImageByUrl($imageUrl);
                if (file_exists($imageUrl)) {
                    unlink($imageUrl);
                }
            }

            echo json_encode(["message" => "Noticia actualizada con éxito"]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error al actualizar noticia", "error" => $e->getMessage()]);
        }
    }

    // Eliminar una noticia
    public function delete($id) {
        try {
            $this->newsModel->deleteNews($id);
            echo json_encode(["message" => "Noticia eliminada con éxito"]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error al eliminar noticia", "error" => $e->getMessage()]);
        }
    }

    // Cambiar estado de una noticia
    public function setState($id, $data) {
        try {
            $status = $data['status'] ?? null;
            if ($status === null) {
                http_response_code(400);
                echo json_encode(["message" => "El estado es obligatorio",$status]);
                return;
            }
    
            // Validar ID
            if (!is_numeric($id)) {
                http_response_code(400);
                echo json_encode(["message" => "ID inválido"]);
                return;
            }
    
            // Cambiar el estado en la base de datos
            $this->newsModel->setState($status, $id);
    
            if ($status == 1) {
                echo json_encode(["message" => "Noticia publicada con éxito"]);
            } else {
                echo json_encode(["message" => "Noticia dada de baja"]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error al cambiar el estado", "error" => $e->getMessage()]);
        }
    }
    
}
