<?php
// controllers/CatalogoController.php

class CatalogoController {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }
    
    /**
     * Obtener todos los tipos de documento
     * @return array Respuesta con los tipos de documento
     */
    public function getTiposDocumento() {
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
            $sql = "SELECT id, codigo, nombre FROM in_tipodocumento ORDER BY id ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $tiposDocumento = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'status' => 'success',
                'data' => $tiposDocumento,
                'total' => count($tiposDocumento)
            ];
        } catch (\PDOException $e) {
            return [
                'status' => 'error',
                'message' => 'Error en la base de datos: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener todos los tipos de responsable
     * @return array Respuesta con los tipos de responsable
     */
    public function getTiposResponsable() {
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
            $sql = "SELECT id, nombre FROM in_tiporesponsable ORDER BY nombre ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $tiposResponsable = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'status' => 'success',
                'data' => $tiposResponsable,
                'total' => count($tiposResponsable)
            ];
        } catch (\PDOException $e) {
            return [
                'status' => 'error',
                'message' => 'Error en la base de datos: ' . $e->getMessage()
            ];
        }
    }
} 