<?php
// index.php - Punto de entrada principal de la API

// Cargar los archivos de configuración y funciones
require_once 'config/database.php';
require_once 'functions/auth_functions.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/UserController.php';
require_once 'controllers/TurnoController.php';

// Configuración de headers para API REST
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Manejo de solicitudes OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Obtener método HTTP y endpoint solicitado
$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));
$endpoint = $request[0] ?? '';

// Instanciar controladores
$authController = new AuthController();
$userController = new UserController();
$turnoController = new TurnoController();

// Respuesta por defecto
$response = [
    'status' => 'error',
    'message' => 'Endpoint no válido'
];

// Enrutamiento de la API
switch ($endpoint) {
    case 'register':
        if ($method === 'POST') {
            $response = $authController->register();
        }
        break;
        
    case 'login':
        if ($method === 'POST') {
            $response = $authController->login();
        }
        break;
        
    case 'user':
        if ($method === 'GET') {
            $response = $userController->getUserInfo();
        }
        break;
        
    case 'menu':
        if ($method === 'GET') {
            $response = $userController->getUserInfo();
        }
        break;

    case 'verify-token':
        if($method === 'GET'){
            $response = $authController->verifyToken();
        }
        break;

    case 'turnos':
        // Si hay un segundo segmento en la URL, es el ID del turno
        $turno_id = $request[1] ?? null;
        
        if ($turno_id) {
            // Rutas para un turno específico
            switch ($method) {
                case 'GET':
                    $response = $turnoController->getById($turno_id);
                    break;
                case 'PUT':
                    $response = $turnoController->update($turno_id);
                    break;
                case 'DELETE':
                    $response = $turnoController->delete($turno_id);
                    break;
            }
        } else {
            // Rutas para la colección de turnos
            switch ($method) {
                case 'GET':
                    $response = $turnoController->getAll();
                    break;
                case 'POST':
                    $response = $turnoController->create();
                    break;
            }
        }
        break;

    case 'logout':
        if ($method === 'POST') {
            $response = $authController->logout();
        }
        break;
}

// Devolver respuesta en formato JSON
echo json_encode($response);