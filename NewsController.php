<?php

require_once 'NewsModel.php';
function handleUploadedImages($filesArray) {
    $uploadedImages = [];
    // Iterar sobre las imágenes enviadas
    for ($i = 0; $i < count($filesArray['name']); $i++) {
        if ($filesArray['error'][$i] === UPLOAD_ERR_OK) { // Verifica si no hay errores
            $uploadedImages[] = [
                'name' => $filesArray['name'][$i],
                'type' => $filesArray['type'][$i],
                'tmp_name' => $filesArray['tmp_name'][$i],
                'size' => $filesArray['size'][$i],
            ];
        }
    }
    return $uploadedImages;
}
class NewsController {
    private $newsModel;

    public function __construct() {
        $this->newsModel = new NewsModel();
    }

    // Crear una noticia
    public function create($data, $files) {
        try {
            // Extraer datos del cuerpo
            $title = $data['title'];
            $content = $data['content'];
            $author = $data['author'];
            $date = $data['date'];
    
            // Validar datos básicos
            if (!$title || !$content) {
                http_response_code(400);
                echo json_encode(["message" => "El título y el contenido son obligatorios"]);
                return;
            }
    
            // Verificar si se subió una imagen
            if (!isset($files['image']) || $files['image']['error'] !== UPLOAD_ERR_OK) {
                http_response_code(400);
                echo json_encode(["message" => "La imagen es obligatoria o tiene errores"]);
                return;
            }
    
            // Procesar la imagen subida
            $image = $files['image'];
            $targetDir = __DIR__ . "/uploads/";
            $fileName = uniqid() . "-" . basename($image['name']);
            $targetPath = $targetDir . $fileName;
    
            // Mover el archivo al directorio deseado
            if (!move_uploaded_file($image['tmp_name'], $targetPath)) {
                throw new Exception("Error al mover la imagen");
            }
    
            // Guardar noticia en la base de datos
            $newsId = $this->newsModel->createNews($title, $content, $author, $date);
    
            // Guardar la URL de la imagen asociada a la noticia
            $this->newsModel->createImages($newsId, ["/uploads/" . $fileName]);
    
            echo json_encode(["message" => "Noticia creada con éxito", "newsId" => $newsId, "image" => "/uploads/" . $fileName]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error al crear la noticia", "error" => $e->getMessage()]);
        }
    }
    private function processUploadedImages($images, $newsId) {
        if (isset($_FILES['images'])) {
            $uploadDir = __DIR__ . "/uploads/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
        
            foreach ($_FILES['images']['tmp_name'] as $index => $tmpName) {
                if (!empty($tmpName)) {
                    $fileName = time() . '-' . basename($_FILES['images']['name'][$index]);
                    $uploadPath = $uploadDir . $fileName;
        
                    if (move_uploaded_file($tmpName, $uploadPath)) {
                        $uploadedFiles[] = "/uploads/" . $fileName;
                    } else {
                        echo "Error al subir el archivo: " . $_FILES['images']['name'][$index];
                    }
                }
                $uploadedUrls = [];
        foreach ($images['tmp_name'] as $index => $tmpName) {
            if ($images['error'][$index] === UPLOAD_ERR_OK) {
                $fileName = time() . '-' . basename($images['name'][$index]);
                $uploadPath = $uploadDir . $fileName;
    
                if (move_uploaded_file($tmpName, $uploadPath)) {
                    // Guardar la URL de la imagen en la base de datos
                    $imageUrl = "/uploads/$fileName";
                    $this->newsModel->createImages($newsId, [$imageUrl]);
                    $uploadedUrls[] = $imageUrl;
                }
            }
        }
        $foto = $_FILES['images'];
        return $foto;
            }
        } else {
            echo "No se recibieron imágenes";
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
            /* return json_encode(["noticias" => $result['news'], "total" => $result['total']]); */
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error al obtener noticias", "error" => $e->getMessage()]);
        }
    }

    // Obtener una noticia por ID
    public function getById($id) {
        try {
            header("Content-Type: application/json; charset=UTF-8");
            
            $news = $this->newsModel->getNewsById($id);
    
            if (!$news) {
                http_response_code(404);
                echo json_encode(["message" => "Noticia no encontrada"]);
                return;
            }
    
            echo json_encode(["noticia" => $news]); // Asegúrate de imprimir solo UNA respuesta
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error al obtener la noticia", "error" => $e->getMessage()]);
        }
    }

    // Actualizar una noticia
    public function update($id, $data,$imgs) {
        try {
            $title = $data['title'];
            $content = $data['content'];
            $author = $data['author'];
            $date = $data['date'];
    
            $existingImages = $request['existingImages'] ?? [];
            $files = $imgs['images'] ?? null;

            if (!$title || !$content) {
                http_response_code(400);
                echo json_encode(["message" => "El título y el contenido son obligatorios","datos" => $data]);
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
            // Obtener el estado del array de datos y convertirlo a entero
            $status = isset($data['status']) ? intval($data['status']) : null;
    
            if ($status === null) {
                http_response_code(400);
                echo json_encode(["message" => "El estado es obligatorio"]);
                return;
            }
    
            // Validar ID
            if (!is_numeric($id)) {
                http_response_code(400);
                echo json_encode(["message" => "ID inválido"]);
                return;
            }
    
            // Actualizar el estado en la base de datos
            $this->newsModel->setState($id, $status);
    
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
    public function uploadImage() {
        try {
            // Verificar si el archivo fue subido correctamente
            if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                http_response_code(400);
                echo json_encode(["message" => "Error al subir la imagen"]);
                return;
            }
    
            $file = $_FILES['image'];
            $uploadDir = __DIR__ . "/uploads/"; // Directorio donde se guardarán las imágenes
    
            // Crear el directorio si no existe
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
    
            // Generar un nombre único para el archivo
            $fileName = time() . '-' . basename($file['name']);
            $uploadPath = $uploadDir . $fileName;
    
            // Mover el archivo desde la ubicación temporal a la carpeta de destino
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                echo json_encode(["message" => "Imagen subida con éxito", "url" => "/uploads/$fileName"]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Error al mover la imagen"]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error al procesar la imagen", "error" => $e->getMessage()]);
        }
    }
    public function createTemporaryUser() {
        try {
            
            $this->newsModel->createTemporaryUser();
            // Respuesta
            echo json_encode(["message" => "Usuario temporal creado con éxito"]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error al crear usuario temporal", "error" => $e->getMessage()]);
        }
    }
    
}
