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
        $query = "INSERT INTO news (title, content, author, date) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ssss", $title, $content, $author, $date);
        $stmt->execute();
        return $this->db->insert_id;
    }

    // Agregar imágenes a una noticia
    public function createImages($newsId, $images) {
        $query = "INSERT INTO images (newsId, url) VALUES (?, ?)";
        $stmt = $this->db->prepare($query);

        foreach ($images as $image) {
            $stmt->bind_param("is", $newsId, $image);
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
            WHERE n.id = ?
            GROUP BY n.id";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $id);
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
        $query = "DELETE FROM news WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    // Cambiar estado de una noticia
    public function setState($id, $status) {
        $query = "UPDATE news SET status = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ii", $status, $id);
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
