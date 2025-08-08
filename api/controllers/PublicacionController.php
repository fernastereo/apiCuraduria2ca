<?php

class PublicacionController {
    private $db;
    private $pdo;

    public function __construct() {
        $this->db = new Database();
        $this->pdo = $this->db->connect();
    }

    public function getById($id) {
        try {
            $query = "SELECT p.*, tp.descripcion as tipo_descripcion 
                     FROM publicaciones p 
                     LEFT JOIN tipopublicacion tp ON p.tipopublicacion_id = tp.id 
                     WHERE p.id = :id";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute(['id' => $id]);
            
            if ($publicacion = $stmt->fetch()) {
                return [
                    'status' => 'success',
                    'data' => $publicacion
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Publicación no encontrada'
                ];
            }
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error al obtener la publicación: ' . $e->getMessage()
            ];
        }
    }

    public function getAll() {
        try {
            $query = "SELECT p.*, tp.descripcion as tipo_descripcion 
                     FROM publicaciones p 
                     LEFT JOIN tipopublicacion tp ON p.tipopublicacion_id = tp.id 
                     ORDER BY p.fecha DESC";
            
            $stmt = $this->pdo->query($query);
            $publicaciones = $stmt->fetchAll();
            
            return [
                'status' => 'success',
                'data' => $publicaciones
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error al obtener las publicaciones: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener publicaciones por rango de fechas
     * @param string $fechaInicio Fecha inicial en formato YYYY-MM-DD
     * @param string $fechaFin Fecha final en formato YYYY-MM-DD
     * @return array Respuesta con las publicaciones encontradas
     */
    public function getByDateRange($fechaInicio, $fechaFin) {
        try {
            // Validar formato de fechas
            if (!$this->validateDate($fechaInicio) || !$this->validateDate($fechaFin)) {
                return [
                    'status' => 'error',
                    'message' => 'Formato de fecha inválido. Use YYYY-MM-DD'
                ];
            }

            // Validar que fecha inicio no sea mayor que fecha fin
            if (strtotime($fechaInicio) > strtotime($fechaFin)) {
                return [
                    'status' => 'error',
                    'message' => 'La fecha inicial no puede ser mayor que la fecha final'
                ];
            }

            $query = "SELECT p.*, tp.descripcion as tipo_descripcion 
                     FROM publicaciones p 
                     LEFT JOIN tipopublicacion tp ON p.tipopublicacion_id = tp.id 
                     WHERE p.fechapublicacion BETWEEN :fecha_inicio AND :fecha_fin 
                     ORDER BY p.fechapublicacion DESC";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin
            ]);
            
            $publicaciones = $stmt->fetchAll();
            
            return [
                'status' => 'success',
                'data' => $publicaciones,
                'total' => count($publicaciones),
                'rango' => [
                    'inicio' => $fechaInicio,
                    'fin' => $fechaFin
                ]
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error al obtener las publicaciones: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validar formato de fecha
     * @param string $date Fecha a validar
     * @return bool true si la fecha es válida
     */
    private function validateDate($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    public function create() {

        try {

            // Validar y obtener los datos del POST
            $data = [
              'fecha' => $_POST['fecha'] ?? null,
              'fechapublicacion' => $_POST['fechapublicacion'] ?? null,
              'referencia' => $_POST['referencia'] ?? null,
              'estado' => $_POST['estado'] ?? null,
              'tipopublicacion_id' => $_POST['tipopublicacion_id'] ?? null,
              'archivo' => uniqid().'.pdf' //Generar un nombre único para el archivo
            ];

            // Validar que haya un archivo subido
            if (!isset($_FILES['publicacionFile']) || $_FILES['publicacionFile']['error'] !== UPLOAD_ERR_OK) {
              return [
                'status' => 'error',
                'message' => 'No se recibió el archivo o hubo un error en la subida'
              ];
            }
            
            if (!$this->validatePublicacionData($data)) {
                return [
                    'status' => 'error',
                    'message' => 'Datos de publicación inválidos o incompletos',
                    'received_data' => $data,
                    'required_fields' => ['fecha', 'fechapublicacion', 'referencia', 'estado', 'tipopublicacion_id']
                ];
            }            

            // Preparar la consulta SQL
            $query = "INSERT INTO publicaciones (fecha, fechapublicacion, referencia, archivo, estado, tipopublicacion_id) 
                    VALUES (:fecha, :fechapublicacion, :referencia, :archivo, :estado, :tipopublicacion_id)";
            
            $stmt = $this->pdo->prepare($query);
            $result = $stmt->execute([
                'fecha' => $data['fecha'],
                'fechapublicacion' => $data['fechapublicacion'],
                'referencia' => $data['referencia'],
                'archivo' => $data['archivo'],
                'estado' => $data['estado'],
                'tipopublicacion_id' => $data['tipopublicacion_id']
            ]);

            require '../vendor/autoload.php';

            $config = require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config.php';

            $s3 = new Aws\S3\S3Client([
                'version' => 'latest',
                'region' => 'us-east-2',
                'credentials' => [
                    'key' => $config['AWS_KEY'],
                    'secret' => $config['AWS_SECRET']
                ]
            ]);

            $resultS3 = $s3->putObject([
                'Bucket' => $config['BUCKET'],
                'Key' => 'notificaciones/' . $data['archivo'],
                'Body' => 'body!',
                'SourceFile' => $_FILES['publicacionFile']['tmp_name'],
                'ACL' => 'public-read'
            ]);

            if ($result) {
                $newId = $this->pdo->lastInsertId();
                return [
                    'status' => 'success',
                    'message' => 'Publicación creada exitosamente',
                    'data' => array_merge(['id' => $newId], $data),
                    's3' => $resultS3
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Error al crear la publicación'
                ];
            }
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error al crear la publicación: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
    }

    public function update($id) {
        try {
            $data = [
                'fecha' => $_POST['fecha'] ?? null,
                'fechapublicacion' => $_POST['fechapublicacion'] ?? null,
                'referencia' => $_POST['referencia'] ?? null,
                'estado' => $_POST['estado'] ?? null,
                'tipopublicacion_id' => $_POST['tipopublicacion_id'] ?? null
            ];

            if (!$this->validatePublicacionData($data)) {
                return [
                    'status' => 'error',
                    'message' => 'Datos de publicación inválidos o incompletos',
                    'received_data' => $_POST,
                    'files' => $_FILES
                ];
            }
            
            // Verificar si existe la publicación y obtener el archivo actual
            $checkQuery = "SELECT id, archivo FROM publicaciones WHERE id = :id";
            $checkStmt = $this->pdo->prepare($checkQuery);
            $checkStmt->execute(['id' => $id]);
            
            $publicacion = $checkStmt->fetch(PDO::FETCH_ASSOC);
            if (!$publicacion) {
                return [
                    'status' => 'error',
                    'message' => 'Publicación no encontrada'
                ];
            }

            // Mantener el nombre del archivo actual
            $data['archivo'] = $publicacion['archivo'];

            // Preparar la consulta SQL
            $query = "UPDATE publicaciones 
                     SET fecha = :fecha,
                         fechapublicacion = :fechapublicacion,
                         referencia = :referencia,
                         estado = :estado,
                         tipopublicacion_id = :tipopublicacion_id
                     WHERE id = :id";
            
            $stmt = $this->pdo->prepare($query);
            $result = $stmt->execute([
                'id' => $id,
                'fecha' => $data['fecha'],
                'fechapublicacion' => $data['fechapublicacion'],
                'referencia' => $data['referencia'],
                'estado' => $data['estado'],
                'tipopublicacion_id' => $data['tipopublicacion_id']
            ]);

            // Si hay un nuevo archivo, actualizarlo en S3 manteniendo el mismo nombre
            if(isset($_FILES['publicacionFile']) && $_FILES['publicacionFile']['error'] === UPLOAD_ERR_OK){
                require '../vendor/autoload.php';
                $config = require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config.php';
                $s3 = new Aws\S3\S3Client([
                    'version' => 'latest',
                    'region' => 'us-east-2',
                    'credentials' => [
                        'key' => $config['AWS_KEY'],
                        'secret' => $config['AWS_SECRET']
                    ]
                ]);
    
                // Usar el nombre de archivo existente
                $resultS3 = $s3->putObject([
                    'Bucket' => $config['BUCKET'],
                    'Key' => 'notificaciones/' . $data['archivo'], // Mantener el mismo nombre
                    'Body' => 'body!',
                    'SourceFile' => $_FILES['publicacionFile']['tmp_name'],
                    'ACL' => 'public-read'
                ]);
            }

            if ($result) {
                return [
                    'status' => 'success',
                    'message' => 'Publicación actualizada exitosamente',
                    'data' => array_merge(['id' => $id], $data)
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Error al actualizar la publicación'
                ];
            }
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error al actualizar la publicación: ' . $e->getMessage(),
                'debug' => [
                    'post' => $_POST,
                    'files' => $_FILES
                ]
            ];
        }
    }

    public function delete($id) {
      try {          
          // Verificar si existe la publicación y obtener el archivo actual
          $checkQuery = "SELECT id, archivo FROM publicaciones WHERE id = :id";
          $checkStmt = $this->pdo->prepare($checkQuery);
          $checkStmt->execute(['id' => $id]);
          
          $publicacion = $checkStmt->fetch(PDO::FETCH_ASSOC);
          if (!$publicacion) {
              return [
                  'status' => 'error',
                  'message' => 'Publicación no encontrada'
              ];
          }

          // Preparar la consulta SQL
          $query = "UPDATE publicaciones 
                  SET estado = :estado
                  WHERE id = :id";
          
          $stmt = $this->pdo->prepare($query);
          $result = $stmt->execute([
              'id' => $id,
              'estado' => 3,
          ]);

          if ($result) {
              return [
                  'status' => 'success',
                  'message' => 'Publicación archivada exitosamente',
              ];
          } else {
              return [
                  'status' => 'error',
                  'message' => 'Error al archivar la publicación'
              ];
          }
      } catch (Exception $e) {
          return [
              'status' => 'error',
              'message' => 'Error al archivar la publicación: ' . $e->getMessage(),
              'debug' => [
                  'post' => $_POST,
                  'files' => $_FILES
              ]
          ];
      }
  }

    private function validatePublicacionData($data) {
        // Validar que todos los campos requeridos estén presentes
        $required_fields = ['fecha', 'fechapublicacion', 'referencia', 'estado', 'tipopublicacion_id'];
        
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return false;
            }
        }

        // Validar formato de fechas
        if (!strtotime($data['fecha']) || !strtotime($data['fechapublicacion'])) {
            return false;
        }

        // Validar que el estado sea un número
        if (!is_numeric($data['estado'])) {
            return false;
        }

        // Validar que tipopublicacion_id sea un número entero positivo
        if (!is_numeric($data['tipopublicacion_id']) || $data['tipopublicacion_id'] <= 0) {
            return false;
        }

        return true;
    }
} 