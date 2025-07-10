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

    /**
     * Obtener todos los tipos de licencia con sus modalidades
     * @return array Respuesta con los tipos de licencia y sus modalidades
     */
    public function getTiposLicencia() {
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
            $sql = "SELECT 
                        tl.id as tipolicencia_id, 
                        tl.codigo as tipolicencia_codigo,
                        tl.nombre as tipolicencia_nombre,
                        tm.id as tipomodalidad_id,
                        tm.codigo as tipomodalidad_codigo,
                        tm.nombre as tipomodalidad_nombre
                    FROM in_tipolicencia tl
                    LEFT JOIN in_tipomodalidad tm ON tl.id = tm.tipolicencia_id AND tm.tipo_registro = 'R'
                    WHERE tl.tipo_registro = 'R'
                    ORDER BY tl.orden, tl.nombre ASC, tm.nombre ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $tiposLicencia = [];
            foreach ($results as $row) {
                $licenciaId = $row['tipolicencia_id'];
                
                if (!isset($tiposLicencia[$licenciaId])) {
                    $tiposLicencia[$licenciaId] = [
                        'id' => $licenciaId,
                        'codigo' => $row['tipolicencia_codigo'],
                        'nombre' => $row['tipolicencia_nombre'],
                        'modalidades' => []
                    ];
                }
                
                if ($row['tipomodalidad_id']) {
                    $tiposLicencia[$licenciaId]['modalidades'][] = [
                        'id' => $row['tipomodalidad_id'],
                        'codigo' => $row['tipomodalidad_codigo'],
                        'nombre' => $row['tipomodalidad_nombre']
                    ];
                }
            }
            
            return [
                'status' => 'success',
                'data' => array_values($tiposLicencia),
                'total' => count($tiposLicencia)
            ];
            
        } catch (\PDOException $e) {
            return [
                'status' => 'error',
                'message' => 'Error en la base de datos: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener todos los objetos de licencia
     * @return array Respuesta con los objetos para un trámite de licencia
     */
    public function getObjetosLicencia() {
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
            $sql = "SELECT id, nombre FROM in_objeto WHERE tipo_registro = 'R' ORDER BY nombre ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $objetosLicencia = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'status' => 'success',
                'data' => $objetosLicencia,
                'total' => count($objetosLicencia)
            ];
        } catch (\PDOException $e) {
            return [
                'status' => 'error',
                'message' => 'Error en la base de datos: ' . $e->getMessage()
            ];
        }
    }
} 