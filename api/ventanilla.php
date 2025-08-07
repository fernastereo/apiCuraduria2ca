<?php
// ventanilla.php - Punto de entrada principal de la API

// Cargar los archivos de configuración y funciones
require_once 'config/database.php';
require_once 'functions/auth_functions.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/UserController.php';
require_once 'controllers/ExpedienteController.php';
require_once 'controllers/CatalogoController.php';
require_once 'controllers/PublicacionController.php';

// Configuración de headers para API REST
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

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
$expedienteController = new ExpedienteController();
$catalogoController = new CatalogoController();
$publicacionController = new PublicacionController();

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

    case 'verify-token':
        if($method === 'GET'){
            $response = $authController->verifyToken();
        }
        break;

    case 'expedientes':
        if ($method === 'GET') {
            // Consulta por radicado y vigencia
            if (isset($_GET['idradicado']) && isset($_GET['vigencia'])) {
                $response = $expedienteController->getByIdYear(
                    $_GET['idradicado'],
                    $_GET['vigencia']
                );
            }
        }
        break;

    case 'publicaciones':
        $publicacion_id = $request[1] ?? null;
        
        // Ruta para búsqueda por rango de fechas
        if($method === 'GET' && isset($_GET['fecha_inicio']) && isset($_GET['fecha_fin'])) {
            $response = $publicacionController->getByDateRange(
                $_GET['fecha_inicio'],
                $_GET['fecha_fin']
            );
        }
        // Rutas existentes
        else if($publicacion_id){
            if($method === 'GET'){
                $response = $publicacionController->getById($publicacion_id);
            }
            if($method === 'POST' && isset($_POST['_method']) && $_POST['_method'] === 'PUT'){
                $response = $publicacionController->update($publicacion_id);
            }
            if($method === 'DELETE'){
                $response = $publicacionController->delete($publicacion_id);
            }
        } else {
            if($method === 'GET'){
                $response = $publicacionController->getAll();
            }
            if($method === 'POST'){
                $response = $publicacionController->create();
            }
        }
        break;

    case 'tipos-publicacion':
        $tipo_id = $request[1] ?? null;
        
        if ($tipo_id) {
            switch ($method) {
                case 'GET':
                    $response = $catalogoController->getTipoPublicacionById($tipo_id);
                    break;
            }
        } else {
            switch ($method) {
                case 'GET':
                    $response = $catalogoController->getTiposPublicacion();
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