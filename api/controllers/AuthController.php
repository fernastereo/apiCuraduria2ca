<?php
// controllers/AuthController.php

class AuthController {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }
    
    public function register() {
        // Obtener datos del body
        $data = json_decode(file_get_contents("php://input"), true);
        
        // Validar datos
        if (!isset($data['username']) || !isset($data['password']) || !isset($data['email'])) {
            return [
                'status' => 'error',
                'message' => 'Faltan campos requeridos'
            ];
        }
        
        // Limpiar y validar datos
        $username = htmlspecialchars(strip_tags($data['username']));
        $name = htmlspecialchars(strip_tags($data['name']));
        $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
        $password = $data['password'];
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'status' => 'error',
                'message' => 'Email inválido'
            ];
        }
        
        // Hash del password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            // Verificar si ya existe el usuario o email
            $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->rowCount() > 0) {
                return [
                    'status' => 'error',
                    'message' => 'El usuario o email ya está registrado'
                ];
            }
            
            // Insertar nuevo usuario
            $sql = "INSERT INTO users (username, email, password, name) VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$username, $email, $password_hash, $name]);
            
            return [
                'status' => 'success',
                'message' => 'Usuario registrado correctamente',
                'user_id' => $this->db->lastInsertId()
            ];
        } catch (\PDOException $e) {
            return [
                'status' => 'error',
                'message' => 'Error en la base de datos: ' . $e->getMessage()
            ];
        }
    }
    
    public function login() {
        // Obtener datos del body
        $data = json_decode(file_get_contents("php://input"), true);
        
        // Validar datos
        if (!isset($data['username']) || !isset($data['password'])) {
            return [
                'status' => 'error',
                'message' => 'Faltan campos requeridos'
            ];
        }
        
        // Limpiar datos
        $username = htmlspecialchars(strip_tags($data['username']));
        $password = $data['password'];
        
        try {
            // Buscar usuario
            $stmt = $this->db->prepare("SELECT id, password FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($password, $user['password'])) {
                return [
                    'status' => 'error',
                    'message' => 'Credenciales inválidas'
                ];
            }
            
            // Generar token
            $token = generateToken();
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Guardar token en la base de datos
            $sql = "INSERT INTO auth_tokens (user_id, token, expires_at) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$user['id'], $token, $expires_at]);
            
            return [
                'status' => 'success',
                'message' => 'Login exitoso',
                'token' => $token,
                'user' => $user['id'],
                'expires_at' => $expires_at
            ];
        } catch (\PDOException $e) {
            return [
                'status' => 'error',
                'message' => 'Error en la base de datos: ' . $e->getMessage()
            ];
        }
    }
    
    public function logout() {
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
            $stmt = $this->db->prepare("DELETE FROM auth_tokens WHERE token = ?");
            $stmt->execute([$token]);
            
            return [
                'status' => 'success',
                'message' => 'Sesión cerrada correctamente'
            ];
        } catch (\PDOException $e) {
            return [
                'status' => 'error',
                'message' => 'Error en la base de datos: ' . $e->getMessage()
            ];
        }
    }

    public function verifyToken() {
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
        
        return [
            'status' => 'success',
            'message' => 'valid token',
            'data' => $user_id
        ];
    }
}