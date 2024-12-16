<?php

require_once 'Database.php';

class NewsModel {
    private $db;

    public function __construct() {
        // Asegúrate de usar getInstance() antes de getConnection()
        $this->db = Database::getInstance()->getConnection();
    }

    // Crear una noticia
    public function createNews($title, $content, $author, $date) {
        $query = "INSERT INTO news (title, content, author, date) VALUES (:title, :content, :author, :date)";
        $stmt = $this->db->prepare($query);
    
        // Asignar valores usando bindValue
        $stmt->bindValue(':title', $title, PDO::PARAM_STR);
        $stmt->bindValue(':content', $content, PDO::PARAM_STR);
        $stmt->bindValue(':author', $author, PDO::PARAM_STR);
        $stmt->bindValue(':date', $date, PDO::PARAM_STR);
    
        $stmt->execute();
    
        return $this->db->lastInsertId(); // Devuelve el ID del último registro insertado
    }

    // Agregar imágenes a una noticia
    public function createImages($newsId, $images) {
        $query = "INSERT INTO images (newsId, url) VALUES (:newsId, :url)";
        $stmt = $this->db->prepare($query);
    
        foreach ($images as $image) {
            // Asignar valores usando bindValue para cada iteración
            $stmt->bindValue(':newsId', $newsId, PDO::PARAM_INT);
            $stmt->bindValue(':url', $image, PDO::PARAM_STR);
            $stmt->execute();
        }
    
        return true;
    }

    // Obtener todas las noticias con sus imágenes
    public function getAllNews($offset, $limit, $title = null, $status = null, $startDate = null, $endDate = null) {
        $conditions = [];
        $params = [];
    
        if ($title) {
            $conditions[] = "n.title LIKE :title";
            $params[':title'] = "%" . $title . "%";
        }
    
        if ($status !== null) {
            $conditions[] = "n.status = :status";
            $params[':status'] = $status;
        }
    
        if ($startDate) {
            $conditions[] = "n.date >= :startDate";
            $params[':startDate'] = $startDate;
        }
    
        if ($endDate) {
            $conditions[] = "n.date <= :endDate";
            $params[':endDate'] = $endDate;
        }
    
        $whereClause = count($conditions) > 0 ? "WHERE " . implode(" AND ", $conditions) : "";
    
        $query = "
            SELECT n.*, GROUP_CONCAT(i.url) AS images
            FROM news n
            LEFT JOIN images i ON n.id = i.newsId
            $whereClause
            GROUP BY n.id
            ORDER BY n.id DESC
            LIMIT :offset, :limit";
    
        $stmt = $this->db->prepare($query);
    
        // Bind dynamic parameters
        foreach ($params as $key => $value) {
            if (is_int($value)) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
        }
    
        // Bind offset and limit
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    
        $stmt->execute();
    
        $news = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['images'] = $row['images'] ? explode(",", $row['images']) : [];
            $news[] = $row;
        }
    
        // Count total rows
        $countQuery = "SELECT COUNT(*) AS total FROM news n $whereClause";
        $countStmt = $this->db->prepare($countQuery);
    
        foreach ($params as $key => $value) {
            if (is_int($value)) {
                $countStmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $countStmt->bindValue($key, $value, PDO::PARAM_STR);
            }
        }
    
        $countStmt->execute();
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
        return [
            'news' => $news,
            'total' => $total,
        ];
    }
    

    // Obtener una noticia por su ID
    public function getNewsById($id) {
        $query = "
            SELECT n.*, GROUP_CONCAT(i.url) AS images
            FROM news n
            LEFT JOIN images i ON n.id = i.newsId
            WHERE n.id = :id
            GROUP BY n.id";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(":id", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return null;
        }

        $row = $result->fetch_assoc();
        $row['images'] = $row['images'] ? explode(",", $row['images']) : [];
        return $row;
    }

    // Actualizar una noticia
    public function updateNews($id, $title, $content, $author, $date) {
        $query = "UPDATE news SET title = ?, content = ?, author = ?, date = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ssssi", $title, $content, $author, $date, $id);
        return $stmt->execute();
    }

    // Eliminar una noticia
    public function deleteNews($id) {
        $query = "DELETE FROM news WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(":id", $id,PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Cambiar estado de una noticia
    public function setState($id, $status) {
        $query = "UPDATE news SET status = :stat WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(":stat", $status, PDO::PARAM_INT);
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Eliminar una imagen por su URL
    public function deleteImageByUrl($url) {
        $query = "DELETE FROM images WHERE url = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $url);
        return $stmt->execute();
    }
}
