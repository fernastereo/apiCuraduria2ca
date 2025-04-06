<?php
// functions/auth_functions.php

// Función para generar un token aleatorio
function generateToken() {
    return bin2hex(random_bytes(32));
}

// Función para verificar si el token es válido
function verifyValidToken($token) {
    $database = new Database();
    $db = $database->connect();
    
    $sql = "SELECT user_id, expires_at FROM auth_tokens WHERE token = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$token]);
    $result = $stmt->fetch();
    
    if ($result && strtotime($result['expires_at']) > time()) {
        return $result['user_id'];
    }
    
    return false;
}

// Función para extraer el token de la cabecera de autorización
function getAuthToken() {
    $headers = getallheaders();
    $auth_header = $headers['Authorization'] ?? '';
    
    if (empty($auth_header) || !preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
        return false;
    }
    
    return $matches[1];
}