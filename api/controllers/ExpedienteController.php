<?php
// controllers/ExpedienteController.php

class ExpedienteController {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }
    
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
        $numturno = $this->generateTurnoConsecutivo($vigencia);
        $objeto_id = isset($data['objeto_id']) ? htmlspecialchars(strip_tags($data['objeto_id'])) : $this->getDefaultID('in_objeto');
        $tipovivienda_id = isset($data['tipovivienda_id']) ? htmlspecialchars(strip_tags($data['tipovivienda_id'])) : $this->getDefaultID('in_tipovivienda');
        $direccion = htmlspecialchars(strip_tags($data['direccion']));
        $user_id = htmlspecialchars(strip_tags($data['user_id']));
        $estado_id = 1;
        
        try {
            $this->db->beginTransaction();

            // Insertar nuevo expediente
            $sql = "INSERT INTO in_expediente (fecha, hora, vigencia, numturno, objeto_id, tipovivienda_id, direccion, user_id, estado_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"; 
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fecha, $hora, $vigencia, $numturno, $objeto_id, $tipovivienda_id, $direccion, $user_id, $estado_id]);
            
            $id = $this->db->lastInsertId();

            $sql = "INSERT INTO in_estadoexpediente (fecha, hora, expediente_id, user_id, estado_id)
            VALUES (?, ?, ?, ?, ?)"; 
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fecha, $hora, $id, $user_id, $estado_id]);

            // Guardar modalidades si existen
            if (!empty($data['modalidades']) && is_array($data['modalidades'])) {
                $stmt = $this->db->prepare("INSERT INTO in_modalidadexpediente (expediente_id, tipomodalidad_id) VALUES (?, ?)");
                
                foreach ($data['modalidades'] as $tipomodalidad_id) {
                    $stmt->execute([$id, $tipomodalidad_id]);
                }
            }else{
                // Si no se proporcionan modalidades, insertar una por defecto
                $tipomodalidad_id = $this->getDefaultID('in_tipomodalidad');
                if (!$tipomodalidad_id) {
                    throw new Exception('No se encontró una modalidad por defecto');
                }
                $stmt = $this->db->prepare("INSERT INTO in_modalidadexpediente (expediente_id, tipomodalidad_id) VALUES (?, ?)");
                $stmt->execute([$id, $tipomodalidad_id]); // 1 es el ID de la modalidad por defecto
            }

            // Guardar responsables si existen
            if (!empty($data['responsables']) && is_array($data['responsables'])) {
                foreach ($data['responsables'] as $responsableData) {
                    // Verificar si el responsable ya existe
                    $stmt = $this->db->prepare("SELECT id FROM in_responsable WHERE documento = ?");
                    $stmt->execute([$responsableData['documento']]);
                    $responsableExistente = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $responsable_id = $responsableExistente ? $responsableExistente['id'] : null;
                    
                    if (!$responsableExistente) {
                        // Si no existe, crear nuevo responsable
                        $stmt = $this->db->prepare("INSERT INTO in_responsable (
                                            nombre, tipodocumento_id, documento, telefono, email
                                        ) VALUES (?, ?, ?, ?, ?)");
                                        
                        $stmt->execute([
                            $responsableData['nombre'],
                            $responsableData['tipodocumento_id'],
                            $responsableData['documento'],
                            $responsableData['telefono'],
                            $responsableData['email'],
                        ]);
                        
                        $responsable_id = $this->db->lastInsertId();
                    }
                    
                    // Asociar responsable al expediente
                    $stmt = $this->db->prepare("INSERT INTO in_responsableexpediente (
                                        expediente_id, tiporesponsable_id, responsable_id
                                    ) VALUES (?, ?, ?)");
                                    
                    $stmt->execute([
                        $id,
                        $responsableData['tiporesponsable_id'],
                        $responsable_id
                    ]);
                }
            }

            $this->db->commit();
            return [
                'status' => 'success',
                'message' => 'Turno creado correctamente',
                'expediente_id' => $id,
                'numturno' => $numturno,
                'vigencia' => $vigencia
            ];
        } catch (\PDOException $e) {
            $this->db->rollBack();
            return [
                'status' => 'error',
                'message' => 'Error en la base de datos: ' . $e->getMessage()
            ];
        }
    }
    
    // READ - Obtener todos los expedientes o un expediente específico
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
            $offset = isset($_GET['offset']) ? max(0, intval($_GET['offset'])) : 0;

            // Determinar el tipo de búsqueda si existe
            $searchType = isset($_GET['search']) ? $this->determineSearchType($_GET['search']) : null;

            // Consulta con todos los joins
            $complSql = " ORDER BY fecha DESC, vigencia DESC, numturno DESC, id ASC LIMIT :limit OFFSET :offset"; 
            $sql = $this->buildExpedienteQuery($complSql, $searchType);
            $sqlParams = $this->getQueryParams($searchType);

            // Ordenación y paginación
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
            $totalCount = $this->countTotalExpedientes($searchType);
            $itemInit = $offset + 1;
            $itemEnd = ($offset + $perPage) > $totalCount ? $totalCount : ($offset + $perPage);
            
            // Construir respuesta
            return [
                'status' => 'success',
                'expedientes' => $expedientes,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'item_init' => $itemInit,
                    'item_end' => $itemEnd,
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
    
    // READ - Obtener un expediente específico por ID
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
            
            $complSql = " WHERE id = :id ORDER BY fecha ASC, id ASC";
            $sql = $this->buildExpedienteQuery($complSql);
            
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
                'expediente' => $expedientes[0],
            ];
        } catch (\PDOException $e) {
            return [
                'status' => 'error',
                'message' => 'Error en la base de datos: ' . $e->getMessage()
            ];
        }
    }
    
    // UPDATE - Actualizar un expediente existente
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

        try {
            // Verificar si el expediente existe
            $stmt = $this->db->prepare("SELECT id FROM in_expediente WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                return [
                    'status' => 'error',
                    'message' => 'Expediente no encontrado'
                ];
            }

            // Obtener datos del body
            $data = json_decode(file_get_contents("php://input"), true);
            
            // Validar datos
            if (empty($data)) {
                return [
                    'status' => 'error',
                    'message' => 'No se proporcionaron datos para actualizar'
                ];
            }

            $this->db->beginTransaction();

            // Actualizar expediente
            $updateFields = [];
            $params = [];

            if (isset($data['objeto_id'])) {
                $updateFields[] = "objeto_id = ?";
                $params[] = htmlspecialchars(strip_tags($data['objeto_id']));
            }

            if (isset($data['tipovivienda_id'])) {
                $updateFields[] = "tipovivienda_id = ?";
                $params[] = htmlspecialchars(strip_tags($data['tipovivienda_id']));
            }

            if (isset($data['direccion'])) {
                $updateFields[] = "direccion = ?";
                $params[] = htmlspecialchars(strip_tags($data['direccion']));
            }

            if (isset($data['documentacion'])) {
                $updateFields[] = "documentacion = ?";
                $params[] = $data['documentacion']; // No sanitizamos longtext
            }

            if (isset($data['notas'])) {
                $updateFields[] = "notas = ?";
                $params[] = $data['notas']; // No sanitizamos longtext
            }

            if (isset($data['altura'])) {
                $updateFields[] = "altura = ?";
                $params[] = floatval($data['altura']);
            }

            if (isset($data['descripcion'])) {
                $updateFields[] = "descripcion = ?";
                $params[] = $data['descripcion']; // No sanitizamos longtext
            }

            if (isset($data['observaciones'])) {
                $updateFields[] = "observaciones = ?";
                $params[] = $data['observaciones']; // No sanitizamos longtext
            }

            // Si hay campos para actualizar
            if (!empty($updateFields)) {
                $params[] = $id;
                $sql = "UPDATE in_expediente SET " . implode(", ", $updateFields) . " WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
            }

            // Actualizar modalidades si se proporcionan
            if (isset($data['modalidades']) && is_array($data['modalidades'])) {
                // Eliminar modalidades existentes
                $stmt = $this->db->prepare("DELETE FROM in_modalidadexpediente WHERE expediente_id = ?");
                $stmt->execute([$id]);

                // Insertar nuevas modalidades
                $stmt = $this->db->prepare("INSERT INTO in_modalidadexpediente (expediente_id, tipomodalidad_id) VALUES (?, ?)");
                foreach ($data['modalidades'] as $tipomodalidad_id) {
                    $stmt->execute([$id, $tipomodalidad_id]);
                }
            }

            // Actualizar responsables si se proporcionan
            if (isset($data['responsables']) && is_array($data['responsables'])) {
                // Eliminar responsables existentes
                $stmt = $this->db->prepare("DELETE FROM in_responsableexpediente WHERE expediente_id = ?");
                $stmt->execute([$id]);

                foreach ($data['responsables'] as $responsableData) {
                    // Verificar si el responsable ya existe
                    $stmt = $this->db->prepare("SELECT id FROM in_responsable WHERE documento = ?");
                    $stmt->execute([$responsableData['documento']]);
                    $responsableExistente = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $responsable_id = null;
                    
                    if ($responsableExistente) {
                        // Si existe, actualizar sus datos
                        $responsable_id = $responsableExistente['id'];
                        $stmt = $this->db->prepare("UPDATE in_responsable SET 
                                            nombre = ?, 
                                            tipodocumento_id = ?, 
                                            telefono = ?, 
                                            email = ?
                                        WHERE id = ?");
                        $stmt->execute([
                            $responsableData['nombre'],
                            $responsableData['tipodocumento_id'],
                            $responsableData['telefono'],
                            $responsableData['email'],
                            $responsable_id
                        ]);
                    } else {
                        // Si no existe, crear nuevo responsable
                        $stmt = $this->db->prepare("INSERT INTO in_responsable (
                                            nombre, tipodocumento_id, documento, telefono, email
                                        ) VALUES (?, ?, ?, ?, ?)");
                                        
                        $stmt->execute([
                            $responsableData['nombre'],
                            $responsableData['tipodocumento_id'],
                            $responsableData['documento'],
                            $responsableData['telefono'],
                            $responsableData['email'],
                        ]);
                        
                        $responsable_id = $this->db->lastInsertId();
                    }
                    
                    // Asociar responsable al expediente
                    $stmt = $this->db->prepare("INSERT INTO in_responsableexpediente (
                                        expediente_id, tiporesponsable_id, responsable_id
                                    ) VALUES (?, ?, ?)");
                                    
                    $stmt->execute([
                        $id,
                        $responsableData['tiporesponsable_id'],
                        $responsable_id
                    ]);
                }
            }

            $this->db->commit();
            return [
                'status' => 'success',
                'message' => 'Expediente actualizado correctamente'
            ];
        } catch (\PDOException $e) {
            $this->db->rollBack();
            return [
                'status' => 'error',
                'message' => 'Error en la base de datos: ' . $e->getMessage()
            ];
        }
    }

    public function updateExpedienteFormulario($id) {
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
            // Obtener datos actuales del expediente
            $stmt = $this->db->prepare("SELECT id, objeto_id FROM in_expediente WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                return [
                    'status' => 'error',
                    'message' => 'Expediente no encontrado'
                ];
            }

            $expedienteActual = $stmt->fetch(PDO::FETCH_ASSOC);

            // Obtener modalidades actuales
            $stmt = $this->db->prepare("SELECT tipomodalidad_id FROM in_modalidadexpediente WHERE expediente_id = ?");
            $stmt->execute([$id]);
            $modalidadesActuales = $stmt->fetchAll(PDO::FETCH_COLUMN);
            sort($modalidadesActuales); // Ordenar para comparación posterior

            // Obtener datos del body
            $data = json_decode(file_get_contents("php://input"), true);

            // Validar datos
            if (empty($data)) {
                return [
                    'status' => 'error',
                    'message' => 'No se proporcionaron datos para actualizar'
                ];
            }

            $this->db->beginTransaction();

            $cambiosRealizados = false;

            // Verificar y actualizar objeto_id si hay cambios
            if (isset($data['objeto_id']) && $data['objeto_id'] != $expedienteActual['objeto_id']) {
                $sql = "UPDATE in_expediente SET objeto_id = ? WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$data['objeto_id'], $id]);
                $cambiosRealizados = true;
            }

            // Verificar y actualizar modalidades si hay cambios
            if (isset($data['modalidades']) && is_array($data['modalidades'])) {
                $nuevasModalidades = $data['modalidades'];
                sort($nuevasModalidades); // Ordenar para comparación

                // Comparar arrays ordenados
                if ($modalidadesActuales != $nuevasModalidades) {
                    // Eliminar modalidades existentes
                    $stmt = $this->db->prepare("DELETE FROM in_modalidadexpediente WHERE expediente_id = ?");
                    $stmt->execute([$id]);

                    // Insertar nuevas modalidades
                    $stmt = $this->db->prepare("INSERT INTO in_modalidadexpediente (expediente_id, tipomodalidad_id) VALUES (?, ?)");
                    foreach ($nuevasModalidades as $tipomodalidad_id) {
                        $stmt->execute([$id, $tipomodalidad_id]);
                    }
                    $cambiosRealizados = true;
                }
            }

            // Solo actualizar estado si hubo cambios
            if ($cambiosRealizados) {
                $estado_id = 3;
                $fecha = date('Y-m-d');
                $hora = date('H:i:s');
                $sql = "INSERT INTO in_estadoexpediente (fecha, hora, expediente_id, user_id, estado_id)
                VALUES (?, ?, ?, ?, ?)"; 
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$fecha, $hora, $id, $user_id, $estado_id]);

                $sql = "UPDATE in_expediente SET estado_id = ? WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$estado_id, $id]);
            }

            $this->db->commit();
            return [
                'status' => 'success',
                'message' => $cambiosRealizados ? 'Expediente actualizado correctamente' : 'No se detectaron cambios en el expediente'
            ];
        } catch (\PDOException $e) {
            $this->db->rollBack();
            return [
                'status' => 'error',
                'message' => 'Error en la base de datos: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Determina el tipo de búsqueda basado en el valor proporcionado
     * @param string $search Valor a analizar
     * @return string Tipo de búsqueda ('number', 'date' o 'text')
     */
    private function determineSearchType($search) {
        // Si es un número entero, asumimos que es una búsqueda por número de turno
        if (ctype_digit($search)) {
            return 'number';
        }
        
        // Si coincide con el formato de fecha YYYY-MM-DD
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $search)) {
            return 'date';
        }
        
        // En cualquier otro caso, asumimos que es una búsqueda por texto
        return 'text';
    }

    /**
     * Construye la consulta SQL base para expedientes
     * @param string $complSql Complemento de la consulta SQL
     * @param string|null $searchType Tipo de búsqueda ('number', 'date' o 'text')
     * @return string Consulta SQL
     */
    private function buildExpedienteQuery($complSql = "", $searchType = null) {
        $sql = "SELECT e.*, o.nombre as objeto_nombre, tv.nombre as tipovivienda_nombre, 
                    me.tipomodalidad_id, tm.nombre as tipomodalidad_nombre, 
                    tm.tipolicencia_id, tl.nombre as tipolicencia_nombre, 
                    re.tiporesponsable_id, tr.nombre as tiporesponsable_nombre, 
                    re.responsable_id, r.nombre as responsable_nombre, r.tipodocumento_id, 
                    r.documento, r.telefono, r.email, 
                    td.nombre as tipodocumento_nombre,
                    s.nombre as estado_nombre, s.class as estado_class
                FROM (
                        SELECT * FROM in_expediente ie
                        $complSql
                    ) e, in_objeto o, in_tipovivienda tv, 
                    in_modalidadexpediente me, in_tipomodalidad tm, in_tipolicencia tl, 
                    in_responsableexpediente re, in_tiporesponsable tr, 
                    in_responsable r, in_tipodocumento td, in_estado s
                WHERE e.objeto_id = o.id
                AND e.tipovivienda_id = tv.id
                AND e.id = me.expediente_id
                AND me.tipomodalidad_id = tm.id
                AND tm.tipolicencia_id = tl.id
                AND e.id = re.expediente_id
                AND re.tiporesponsable_id = tr.id
                AND re.responsable_id = r.id
                AND r.tipodocumento_id = td.id
                AND e.estado_id = s.id
                ORDER BY e.vigencia DESC, e.numturno DESC, e.fecha ASC, e.hora ASC";

        // Agregar condición de búsqueda si existe
        if (isset($_GET['search']) && $searchType) {
            switch($searchType) {
                case 'number':
                    $sql .= " AND e.numturno = :search";
                    break;
                case 'date':
                    $sql .= " AND DATE(e.fecha) = :search";
                    break;
                case 'text':
                    $sql .= " AND (r.nombre LIKE :search1 OR e.direccion LIKE :search2)";
                    break;
            }
        }

        return $sql;
    }

    /**
     * Obtiene los parámetros de filtro para la consulta
     * @param string|null $searchType Tipo de búsqueda ('number', 'date' o 'text')
     * @return array Parámetros para bindear a la consulta
     */
    private function getQueryParams($searchType = null) {
        $params = [];
        
        if (isset($_GET['search']) && $searchType) {
            $value = $_GET['search'];
            if($searchType === 'text'){
                $params[':search1'] = "%$value%";
                $params[':search2'] = "%$value%";
            }else{
                $params[':search'] = $value;
            }
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
                    'numturno' => $row['numturno'],
                    'vigencia' => $row['vigencia'],
                    'direccion' => $row['direccion'],
                    'documentacion' => $row['documentacion'],
                    'notas' => $row['notas'],
                    'altura' => $row['altura'],
                    'descripcion' => $row['descripcion'],
                    'observaciones' => $row['observaciones'],
                    'objeto' => [
                        'id' => $row['objeto_id'],
                        'nombre' => $row['objeto_nombre'],
                    ],
                    'estado' => [
                        'id' => $row['estado_id'],
                        'nombre' => $row['estado_nombre'],
                        'class' => $row['estado_class'],
                    ],
                    'tipovivienda' => [
                        'id' => $row['tipovivienda_id'],
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
        $tipomodalidad_id = $row['tipomodalidad_id'];

        foreach($expediente['modalidades'] as $modalidad){
            if($modalidad['id'] == $tipomodalidad_id){
                return; // Modalidad ya existe, no hacer nada
            }
        }

        $expediente['modalidades'][] = [
            'id' => $tipomodalidad_id,
            'nombre' => $row['tipomodalidad_nombre'],
            'tipolicencia' => [
                'id' => $row['tipolicencia_id'],
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
        $responsable_id = $row['responsable_id'];
        foreach($expediente['responsables'] as $responsable){
            if($responsable['id'] == $responsable_id){
                return; // Responsable ya existe, no hacer nada
            }
        }

        $expediente['responsables'][] = [
            'id' => $responsable_id,
            'nombre' => $this->capitalizarNombre($row['responsable_nombre']),
            'tiporesponsable' => [
                'id' => $row['tiporesponsable_id'],
                'nombre' => $row['tiporesponsable_nombre'],
            ],
            'documento' => $row['documento'],
            'telefono' => $row['telefono'],
            'email' => $row['email'],
            'tipodocumento' => [
                'id' => $row['tipodocumento_id'],
                'nombre' => $row['tipodocumento_nombre'],
            ]
        ];
    }

    /**
     * Cuenta el total de expedientes con los filtros aplicados
     * @param string|null $searchType Tipo de búsqueda ('number', 'date' o 'text')
     * @return int Total de expedientes
     */
    private function countTotalExpedientes($searchType = null) {
        // Contar el total para la paginación
        $sqlCount = "SELECT COUNT(DISTINCT e.id) as total 
                    FROM in_expediente e 
                    INNER JOIN in_responsableexpediente re ON e.id = re.expediente_id
                    INNER JOIN in_responsable r ON re.responsable_id = r.id
                    WHERE EXISTS (SELECT 1 FROM in_modalidadexpediente me WHERE e.id = me.expediente_id)";

        // Agregar condición de búsqueda si existe
        if (isset($_GET['search']) && $searchType) {
            switch($searchType) {
                case 'number':
                    $sqlCount .= " AND e.numturno = :search";
                    break;
                case 'date':
                    $sqlCount .= " AND DATE(e.fecha) = :search";
                    break;
                case 'text':
                    $sqlCount .= " AND (r.nombre LIKE :search1 OR e.direccion LIKE :search2)";
                    break;
            }
        }

        $sqlParams = $this->getQueryParams($searchType);

        // Preparar la consulta
        $stmtCount = $this->db->prepare($sqlCount);
        foreach ($sqlParams as $param => $value) {
            $stmtCount->bindValue($param, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        // // Aplicar los mismos filtros que en la consulta principal
        // if (isset($_GET['fecha'])) {
        //     $sqlCount .= " AND e.fecha = :fecha";
        // }

        // if (isset($_GET['search'])) {
        //     $sqlCount .= " AND (r.nombre LIKE :search OR e.numturno LIKE :search OR e.direccion LIKE :search)";
        // }
        
        // $stmtCount = $this->db->prepare($sqlCount);
        
        // if (isset($_GET['fecha'])) {
        //     $stmtCount->bindParam(':fecha', $_GET['fecha']);
        // }

        // if (isset($_GET['search'])) {
        //     $stmtCount->bindValue(':search', '%' . $_GET['search'] . '%');
        // }
        
        $stmtCount->execute();
        $result = $stmtCount->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    /**
     * Genera el numero de turno consecutivo por vigencia
     * @return int Número de turno consecutivo
     */
    private function generateTurnoConsecutivo($vigencia) {
        $sql = "SELECT numturno FROM in_expediente WHERE vigencia = ? ORDER BY numturno DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$vigencia]);
        $lastTurno = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $newTurno = 1;
        if ($lastTurno) {
            $newTurno = $lastTurno['numturno'] + 1;
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

    /**
     * Capitaliza la primera letra de cada palabra en un nombre completo
     * @param string $nombreCompleto - El nombre completo que se desea capitalizar
     * @return string - El nombre completo con la primera letra de cada palabra en mayúscula
     */
    private function capitalizarNombre($nombreCompleto) {
        // Verificar si el input es válido
        if (empty($nombreCompleto) || !is_string($nombreCompleto)) {
            return '';
        }
        
        // Eliminar espacios adicionales y convertir a minúsculas
        $nombreCompleto = trim(mb_strtolower($nombreCompleto));
        
        // Dividir el nombre en palabras
        $palabras = preg_split('/\s+/', $nombreCompleto);
        
        // Capitalizar la primera letra de cada palabra
        $palabrasCapitalizadas = array_map(function($palabra) {
            if (empty($palabra)) {
                return $palabra;
            }
            // Usar mb_convert_case para soporte de caracteres UTF-8 (como á, é, í, etc.)
            return mb_convert_case(mb_substr($palabra, 0, 1), MB_CASE_UPPER, 'UTF-8') . 
                mb_substr($palabra, 1);
        }, $palabras);
        
        // Unir las palabras y devolver el resultado
        return implode(' ', $palabrasCapitalizadas);
    }

}