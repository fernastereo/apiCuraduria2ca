<?php

class HealthController {
    private $db;
    private $env;

    public function __construct() {
        $config = require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config.php';
        $this->db = $config['DB'];
        $this->env = $config['APP_ENV'];
    }

    public function healthCheck() {
        return [
            'status' => 'success',
            'message' => 'API Curaduría 2 Cartagena',
            'version' => '1.0',
            'database' => $this->db,
            'environment' => $this->env,
            'endpoints' => [
                'auth' => [
                    'POST /login' => 'Iniciar sesión',
                    'POST /register' => 'Registrar usuario',
                    'GET /verify-token' => 'Verificar token',
                    'POST /logout' => 'Cerrar sesión'
                ],
                'expedientes' => [
                    'GET /expedientes' => 'Consultar expediente por radicado y vigencia'
                ],
                'publicaciones' => [
                    'GET /publicaciones' => 'Listar todas las publicaciones',
                    'POST /publicaciones' => 'Crear nueva publicación',
                    'GET /publicaciones/{id}' => 'Obtener publicación por ID',
                    'PUT /publicaciones/{id}' => 'Actualizar publicación',
                    'DELETE /publicaciones/{id}' => 'Eliminar publicación',
                    'GET /publicaciones?fecha_inicio=&fecha_fin=' => 'Buscar por rango de fechas'
                ],
                'catalogos' => [
                    'GET /tipos-publicacion' => 'Listar tipos de publicación',
                    'GET /tipos-publicacion/{id}' => 'Obtener tipo de publicación por ID'
                ],
                'sistema' => [
                    'GET /health-check' => 'Verificar estado del sistema'
                ]
            ]
        ];
    }
}