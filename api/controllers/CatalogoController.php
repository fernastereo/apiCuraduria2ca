<?php
// controllers/CatalogoController.php

class CatalogoController {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }

    /**
     * Obtener todos los tipos de publicación
     * @return array Respuesta con los tipos de publicación
     */
    public function getTiposPublicacion() {
        try {
            $sql = "SELECT id, descripcion FROM tipopublicacion ORDER BY descripcion ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $tiposPublicacion = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'status' => 'success',
                'data' => $tiposPublicacion,
                'total' => count($tiposPublicacion)
            ];
        } catch (\PDOException $e) {
            return [
                'status' => 'error',
                'message' => 'Error en la base de datos: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener un tipo de publicación específico por ID
     * @param int $id ID del tipo de publicación
     * @return array Respuesta con el tipo de publicación
     */
    public function getTipoPublicacionById($id) {
        // Verificar autenticación
        $token = getAuthToken();
        
        if (!$token || !($user_id = verifyValidToken($token))) {
            http_response_code(401);
            return [
                'status' => 'error',
                'message' => 'No autorizado'
            ];
        }

        try {
            $sql = "SELECT id, descripcion FROM tipopublicacion WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $id]);
            $tipoPublicacion = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$tipoPublicacion) {
                return [
                    'status' => 'error',
                    'message' => 'Tipo de publicación no encontrado'
                ];
            }
            
            return [
                'status' => 'success',
                'data' => $tipoPublicacion
            ];
        } catch (\PDOException $e) {
            return [
                'status' => 'error',
                'message' => 'Error en la base de datos: ' . $e->getMessage()
            ];
        }
    }

} 