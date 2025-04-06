<?php
// controllers/UserController.php

class UserController {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }
    
    public function getUserInfo() {
        $token = getAuthToken();
        
        if (!$token) {
            http_response_code(401);
            return [
                'status' => 'error',
                'message' => 'Token de autenticación no proporcionado'
            ];
        }
        
        $user_id = verifyValidToken($token);
        
        if (!$user_id) {
            http_response_code(401);
            return [
                'status' => 'error',
                'message' => 'Token inválido o expirado'
            ];
        }
        
        try {
            $stmt = $this->db->prepare("SELECT username, email, name, avatar FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user_info = $stmt->fetch();
            
            return [
                'status' => 'success',
                'message' => 'Datos del usuario obtenidos',
                'data' => $user_info
            ];
        } catch (\PDOException $e) {
            return [
                'status' => 'error',
                'message' => 'Error en la base de datos: ' . $e->getMessage()
            ];
        }
    }
}