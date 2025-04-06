<?php
// controllers/TurnoController.php

class TurnoController {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }
    
    // CREATE - Crear un nuevo turno
    public function create() {
        // Verificar autenticación
        $token = getAuthToken();
        
        if (!$token || !($user_id = verifyValidToken($token))) {
            http_response_code(401);
            return [
                'status' => 'error',
                'message' => 'No autorizado'
            ];
        }
        
        // Obtener datos del body
        $data = json_decode(file_get_contents("php://input"), true);
        
        // Validar datos
        if (!isset($data['fecha']) || !isset($data['hora']) ) {
            return [
                'status' => 'error',
                'message' => 'Faltan campos requeridos'
            ];
        }
        
        // Limpiar y validar datos
        $fecha = htmlspecialchars(strip_tags($data['fecha']));
        $hora = htmlspecialchars(strip_tags($data['hora']));
        $vigencia = date('Y');
        $idturno = $this->generateTurnoConsecutivo($vigencia);
        $id_objeto = isset($data['id_objeto']) ? htmlspecialchars(strip_tags($data['id_objeto'])) : $this->getDefaultID('in_objeto');
        $id_tipovivienda = isset($data['id_tipovivienda']) ? htmlspecialchars(strip_tags($data['id_tipovivienda'])) : $this->getDefaultID('in_tipovivienda');
        $direccion = htmlspecialchars(strip_tags($data['direccion']));
        $user_id = htmlspecialchars(strip_tags($data['user_id']));
        
        try {
            $this->db->beginTransaction();

            // Insertar nuevo turno
            $sql = "INSERT INTO in_expediente (fecha, hora, vigencia, idturno, id_objeto, id_tipovivienda, direccion, user_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)"; 
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fecha, $hora, $vigencia, $idturno, $id_objeto, $id_tipovivienda, $direccion, $user_id]);
            
            $id = $this->db->lastInsertId();

            // Guardar modalidades si existen
            if (!empty($data['modalidades']) && is_array($data['modalidades'])) {
                $stmt = $this->db->prepare("INSERT INTO in_modalidadexpediente (id_expediente, id_tipomodalidad) VALUES (?, ?)");
                
                foreach ($data['modalidades'] as $modalidadId) {
                    $stmt->execute([$id, $modalidadId]);
                }
            }else{
                // Si no se proporcionan modalidades, insertar una por defecto
                $modalidadId = $this->getDefaultID('in_tipomodalidad');
                if (!$modalidadId) {
                    throw new Exception('No se encontró una modalidad por defecto');
                }
                $stmt = $this->db->prepare("INSERT INTO in_modalidadexpediente (id_expediente, id_tipomodalidad) VALUES (?, ?)");
                $stmt->execute([$id, $modalidadId]); // 1 es el ID de la modalidad por defecto
            }

            // Guardar responsables si existen
            if (!empty($data['responsables']) && is_array($data['responsables'])) {
                foreach ($data['responsables'] as $responsableData) {
                    // Verificar si el responsable ya existe
                    $stmt = $this->db->prepare("SELECT id FROM in_responsable WHERE documento = ?");
                    $stmt->execute([$responsableData['documento']]);
                    $responsableExistente = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $responsableId = $responsableExistente['id'];
                    
                    if (!$responsableExistente) {
                        // Si no existe, crear nuevo responsable
                        $stmt = $this->db->prepare("INSERT INTO in_responsable (
                                            nombre, id_tipodocumento, documento, telefono, email
                                        ) VALUES (?, ?, ?, ?, ?)");
                                        
                        $stmt->execute([
                            $responsableData['nombre'],
                            $responsableData['documento'],
                            $responsableData['telefono'],
                            $responsableData['email'],
                            $responsableData['tipodocumento_id'],
                        ]);
                        
                        $responsableId = $this->db->lastInsertId();
                    }
                    
                    // Asociar responsable al expediente
                    $stmt = $this->db->prepare("INSERT INTO in_responsableexpediente (
                                        id_expediente, id_tiporesponsable, id_responsable
                                    ) VALUES (?, ?, ?)");
                                    
                    $stmt->execute([
                        $id,
                        $responsableData['tipodocumento_id'],
                        $responsableId
                    ]);
                }
            }

            $this->db->commit();
            return [
                'status' => 'success',
                'message' => 'Turno creado correctamente',
                'turno_id' => $id
            ];
        } catch (\PDOException $e) {
            $this->db->rollBack();
            return [
                'status' => 'error',
                'message' => 'Error en la base de datos: ' . $e->getMessage()
            ];
        }
    }
    
    // READ - Obtener todos los turnos o un turno específico
    public function getAll() {
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
            // parametros de paginacion
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $perPage = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 10;
            $offset = ($page - 1) * $perPage;

            // Consulta con todos los joins
            $sql = $this->buildExpedienteQuery();
            $sqlParams = $this->getQueryParams();

            // Ordenación y paginación
            $sql .= " ORDER BY e.fecha ASC, e.id ASC LIMIT :limit OFFSET :offset";
            $sqlParams[':limit'] = $perPage;
            $sqlParams[':offset'] = $offset;

            // Preparar la consulta
            $stmt = $this->db->prepare($sql);
            foreach ($sqlParams as $param => $value) {
                $stmt->bindValue($param, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            //Estructurar los datos
            $expedientes = $this->processExpedienteRows($rows);
            $totalCount = $this->countTotalExpedientes();
            
            // Construir respuesta
            return [
                'status' => 'success',
                'expedientes' => $expedientes,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total_items' => $totalCount,
                    'total_pages' => ceil($totalCount / $perPage)
                ]
            ];
        } catch (\PDOException $e) {
            return [
                'status' => 'error',
                'message' => 'Error en la base de datos: ' . $e->getMessage()
            ];
        }
    }
    
    // READ - Obtener un turno específico por ID
    public function getById($id) {
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
            if (!$id || !is_numeric($id)) {
                return [
                    'status' => 'error',
                    'message' => 'ID de expediente no válido'
                ];
            }

            $sql = $this->buildExpedienteQuery();
            $sql .= " AND e.id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($rows)) {
                http_response_code(404);
                return [
                    'status' => 'error',
                    'message' => 'Turno no encontrado'
                ];
            }

            $expedientes = $this->processExpedienteRows($rows);
            
            return [
                'status' => 'success',
                'turno' => $expedientes[0],
            ];
        } catch (\PDOException $e) {
            return [
                'status' => 'error',
                'message' => 'Error en la base de datos: ' . $e->getMessage()
            ];
        }
    }
    
    // UPDATE - Actualizar un turno existente
    public function update($id) {
        // Verificar autenticación
        $token = getAuthToken();
        
        if (!$token || !($user_id = verifyValidToken($token))) {
            http_response_code(401);
            return [
                'status' => 'error',
                'message' => 'No autorizado'
            ];
        }
        
        // Verificar primero si el turno existe
        try {
            $stmt = $this->db->prepare("SELECT id FROM turnos WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                return [
                    'status' => 'error',
                    'message' => 'Turno no encontrado'
                ];
            }
        } catch (\PDOException $e) {
            return [
                'status' => 'error',
                'message' => 'Error en la base de datos: ' . $e->getMessage()
            ];
        }
        
        // Obtener datos del body
        $data = json_decode(file_get_contents("php://input"), true);
        
        // Construir consulta dinámica para actualizar solo los campos proporcionados
        $updateFields = [];
        $params = [];
        
        if (isset($data['fecha'])) {
            $updateFields[] = "fecha = ?";
            $params[] = htmlspecialchars(strip_tags($data['fecha']));
        }
        
        if (isset($data['hora'])) {
            $updateFields[] = "hora = ?";
            $params[] = htmlspecialchars(strip_tags($data['hora']));
        }
        
        if (isset($data['descripcion'])) {
            $updateFields[] = "descripcion = ?";
            $params[] = htmlspecialchars(strip_tags($data['descripcion']));
        }
        
        if (isset($data['cliente_id'])) {
            $updateFields[] = "cliente_id = ?";
            $params[] = intval($data['cliente_id']);
        }
        
        if (isset($data['estado'])) {
            $updateFields[] = "estado = ?";
            $params[] = htmlspecialchars(strip_tags($data['estado']));
        }
        
        // Si no hay campos para actualizar
        if (empty($updateFields)) {
            return [
                'status' => 'error',
                'message' => 'No se proporcionaron campos para actualizar'
            ];
        }
        
        // Añadir el ID al final de los parámetros
        $params[] = $id;
        
        try {
            $sql = "UPDATE turnos SET " . implode(", ", $updateFields) . ", 
                    actualizado_en = NOW() WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return [
                'status' => 'success',
                'message' => 'Turno actualizado correctamente'
            ];
        } catch (\PDOException $e) {
            return [
                'status' => 'error',
                'message' => 'Error en la base de datos: ' . $e->getMessage()
            ];
        }
    }
    
    // DELETE - Eliminar un turno
    public function delete($id) {
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
            // Verificar si el turno existe
            $stmt = $this->db->prepare("SELECT id FROM turnos WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                return [
                    'status' => 'error',
                    'message' => 'Turno no encontrado'
                ];
            }
            
            // Eliminar el turno
            $stmt = $this->db->prepare("DELETE FROM turnos WHERE id = ?");
            $stmt->execute([$id]);
            
            return [
                'status' => 'success',
                'message' => 'Turno eliminado correctamente'
            ];
        } catch (\PDOException $e) {
            return [
                'status' => 'error',
                'message' => 'Error en la base de datos: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Construye la consulta SQL base para expedientes
     * @return string Consulta SQL
     */
    private function buildExpedienteQuery() {
        return "SELECT e.*, o.nombre as objeto_nombre, tv.nombre as tipovivienda_nombre, 
                    me.id_tipomodalidad, tm.nombre as tipomodalidad_nombre, 
                    tm.id_tipolicencia, tl.nombre as tipolicencia_nombre, 
                    re.id_tiporesponsable, tr.nombre as tiporesponsable_nombre, 
                    re.id_responsable, r.nombre as responsable_nombre, r.id_tipodocumento, 
                    r.documento, r.telefono, r.email, 
                    td.nombre as tipodocumento_nombre
                FROM in_expediente e, in_objeto o, in_tipovivienda tv, 
                    in_modalidadexpediente me, in_tipomodalidad tm, in_tipolicencia tl, 
                    in_responsableexpediente re, in_tiporesponsable tr, 
                    in_responsable r, in_tipodocumento td
                WHERE e.id_objeto = o.id
                AND e.id_tipovivienda = tv.id
                AND e.id = me.id_expediente
                AND me.id_tipomodalidad = tm.id
                AND tm.id_tipolicencia = tl.id
                AND e.id = re.id_expediente
                AND re.id_tiporesponsable = tr.id
                AND re.id_responsable = r.id
                AND r.id_tipodocumento = td.id";
    }

    /**
     * Obtiene los parámetros de filtro para la consulta
     * @return array Parámetros para bindear a la consulta
     */
    private function getQueryParams() {
        $params = [];
        
        if (isset($_GET['fecha'])) {
            $params[':fecha'] = $_GET['fecha'];
        }
        
        return $params;
    }

    /**
     * Procesa las filas de resultados para estructurar expedientes
     * @param array $rows Filas obtenidas de la consulta
     * @return array Expedientes estructurados
     */
    private function processExpedienteRows($rows) {
        $expedientes = [];
        $expedientesMap = [];

        foreach($rows as $row){
            $expedienteId = $row['id'];

            if(!isset($expedientesMap[$expedienteId])){
                $expediente = [
                    'id' => $row['id'],
                    'fecha' => $row['fecha'],
                    'hora' => $row['hora'],
                    'idturno' => $row['idturno'],
                    'vigencia' => $row['vigencia'],
                    'direccion' => $row['direccion'],
                    'documentacion' => $row['documentacion'],
                    'notas' => $row['notas'],
                    'altura' => $row['altura'],
                    'descripcion' => $row['descripcion'],
                    'observaciones' => $row['observaciones'],
                    'objeto' => [
                        'id' => $row['id_objeto'],
                        'nombre' => $row['objeto_nombre'],
                    ],
                    'tipovivienda' => [
                        'id' => $row['id_tipovivienda'],
                        'nombre' => $row['tipovivienda_nombre'],
                    ],
                    'modalidades' => [],
                    'responsables' => [],
                ];

                $expedientes[] = $expediente;
                $expedientesMap[$expedienteId] = count($expedientes) - 1;
            }

            //añadir modalildad si no existe ya
            $this->addModalidadIfNotExists($expedientes[$expedientesMap[$expedienteId]], $row);

            //añadir responsable si no existe ya
            $this->addResponsableIfNotExists($expedientes[$expedientesMap[$expedienteId]], $row);

        }

        return $expedientes;
    }

    /**
     * Añade una modalidad al expediente si no existe ya
     * @param array &$expediente Referencia al expediente a modificar
     * @param array $row Fila con datos de modalidad
     */
    private function addModalidadIfNotExists(&$expediente, $row){
        $modalidadId = $row['id_tipomodalidad'];

        foreach($expediente['modalidades'] as $modalidad){
            if($modalidad['id'] == $modalidadId){
                return; // Modalidad ya existe, no hacer nada
            }
        }

        $expediente['modalidades'][] = [
            'id' => $modalidadId,
            'nombre' => $row['tipomodalidad_nombre'],
            'tipolicencia' => [
                'id' => $row['id_tipolicencia'],
                'nombre' => $row['tipolicencia_nombre'],
            ]
        ];
    }

    /**
     * Añade un responsable al expediente si no existe ya
     * @param array &$expediente Referencia al expediente a modificar
     * @param array $row Fila con datos de responsable
     */
    private function addResponsableIfNotExists(&$expediente, $row) {
        $responsableId = $row['id_responsable'];
        foreach($expediente['responsables'] as $responsable){
            if($responsable['id'] == $responsableId){
                return; // Responsable ya existe, no hacer nada
            }
        }

        $expediente['responsables'][] = [
            'id' => $responsableId,
            'nombre' => $row['responsable_nombre'],
            'tiporesponsable' => [
                'id' => $row['id_tiporesponsable'],
                'nombre' => $row['tiporesponsable_nombre'],
            ],
            'documento' => $row['documento'],
            'telefono' => $row['telefono'],
            'email' => $row['email'],
            'tipodocumento' => [
                'id' => $row['id_tipodocumento'],
                'nombre' => $row['tipodocumento_nombre'],
            ]
        ];
    }

    /**
     * Cuenta el total de expedientes con los filtros aplicados
     * @return int Total de expedientes
     */
    private function countTotalExpedientes() {
        // Contar el total para la paginación (simplificado)
        $sqlCount = "SELECT COUNT(DISTINCT e.id) as total FROM in_expediente e 
        WHERE EXISTS (SELECT 1 FROM in_modalidadexpediente me WHERE e.id = me.id_expediente)
        AND EXISTS (SELECT 1 FROM in_responsableexpediente re WHERE e.id = re.id_expediente)";

        // Aplicar los mismos filtros que en la consulta principal
        if (isset($_GET['fecha'])) {
            $sqlCount .= " AND e.fecha = :fecha";
        }
        
        $stmtCount = $this->db->prepare($sqlCount);
        
        if (isset($_GET['fecha'])) {
            $stmtCount->bindParam(':fecha', $_GET['fecha']);
        }
        
        $stmtCount->execute();
        return $stmtCount->fetchColumn();
    }

    /**
     * Genera el numero de turno consecutivo por vigencia
     * @return int Número de turno consecutivo
     */
    private function generateTurnoConsecutivo($vigencia) {
        $sql = "SELECT idturno FROM in_expediente WHERE vigencia = ? ORDER BY idturno DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$vigencia]);
        $lastTurno = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $newTurno = 1;
        if ($lastTurno) {
            $newTurno = $lastTurno['idturno'] + 1;
        }

        return $newTurno;
    }

    /**
     * Obtiene el id por defecto de la tabla dada
     * @return int id por defecto
     */
    private function getDefaultID($table) {
        $sql = "SELECT id FROM $table WHERE tipo_registro='D'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['id'] ?? null;
    }
}