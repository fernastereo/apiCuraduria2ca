<?php
// controllers/ExpedienteController.php

class ExpedienteController {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }

    /**
     * Obtener expediente por número de radicado y vigencia
     * @param string $idradicado Número de radicado del expediente
     * @param int $vigencia Año de vigencia
     * @return array Respuesta con la información del expediente
     */
    public function getByIdYear($idradicado, $vigencia) {
        try {
            $query = "SELECT 
                        id,
                        idradicado,
                        vigencia,
                        fecharadicado,
                        solicitante,
                        direccion,
                        barrio,
                        tipolicencia,
                        modalidad,
                        estado,
                        fechaultimoestado
                    FROM in_expediente
                    WHERE idradicado = :idradicado 
                    AND vigencia = :vigencia";

            $stmt = $this->db->prepare($query);
            $stmt->execute([
                'idradicado' => $idradicado,
                'vigencia' => $vigencia
            ]);

            $expediente = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($expediente) {
                // Formatear fechas
                $expediente['fecharadicado'] = $expediente['fecharadicado'] ? date('Y-m-d', strtotime($expediente['fecharadicado'])) : null;
                $expediente['fechaultimoestado'] = $expediente['fechaultimoestado'] ? date('Y-m-d', strtotime($expediente['fechaultimoestado'])) : null;

                return [
                    'status' => 'success',
                    'data' => $expediente
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Expediente no encontrado'
                ];
            }
        } catch (PDOException $e) {
            return [
                'status' => 'error',
                'message' => 'Error al consultar el expediente: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Recibe los datos de un expediente por POST, debe extraer el iddel json, buscar el expediente por id, si existe el expediente actualizar el campo estado y fechaultimoestado, con los datos correspondientes del json, si no existe el registro debe crearlo con los datos del json
     */
    public function update($radicado) {

        $data = array_map('trim', explode(';', $radicado));
        
        $input = [
            "id" => $data[0] ?? null,
            "idradicado" => $data[1] ?? null,
            "fecharadicado" => $data[2] ? date('Y-m-d', strtotime($data[2])) : null,
            "vigencia" => $data[3] ?? null,
            "solicitante" => $data[4] ?? null,
            "direccion" => $data[5] ?? null,
            "barrio" => $data[6] ?? null,
            "tipolicencia" => $data[7] ?? null,
            "modalidad" => $data[8] ?? null,
            "estado" => $data[9] ?? null,
            "fechaultimoestado" => $data[10] ? date('Y-m-d', strtotime($data[10])) : null
        ];

        if (!isset($input['id'])) {
            return [
                'status' => 'error',
                'message' => 'El campo id es obligatorio',
            ];
        }

        try {
            // Verificar si el expediente existe
            $query = "SELECT id FROM in_expediente WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->execute(['id' => $input['id']]);
            $existingExpediente = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingExpediente) {
                // Actualizar expediente existente
                $updateQuery = "UPDATE in_expediente SET 
                                    estado = :estado,
                                    fechaultimoestado = :fechaultimoestado
                                WHERE id = :id";

                $stmt = $this->db->prepare($updateQuery);
                $stmt->execute([
                    'estado' => $input['estado'] ?? null,
                    'fechaultimoestado' => isset($input['fechaultimoestado']) ? date('Y-m-d', strtotime($input['fechaultimoestado'])) : null,
                    'id' => $input['id'],
                ]);

                return [
                    'status' => 'success',
                    'message' => 'API: Expediente actualizado correctamente'
                ];
            } else {
                // Crear nuevo expediente
                $insertQuery = "INSERT INTO in_expediente 
                                    (id, idradicado, vigencia, fecharadicado, solicitante, direccion, barrio, tipolicencia, modalidad, estado, fechaultimoestado)
                                VALUES 
                                    (:id, :idradicado, :vigencia, :fecharadicado, :solicitante, :direccion, :barrio, :tipolicencia, :modalidad, :estado, :fechaultimoestado)";

                $stmt = $this->db->prepare($insertQuery);
                $stmt->execute([
                    'id' => $input['id'],
                    'idradicado' => $input['idradicado'] ?? null,
                    'vigencia' => $input['vigencia'] ?? null,
                    'fecharadicado' => isset($input['fecharadicado']) ? date('Y-m-d', strtotime($input['fecharadicado'])) : null,
                    'solicitante' => $input['solicitante'] ?? null,
                    'direccion' => $input['direccion'] ?? null,
                    'barrio' => $input['barrio'] ?? null,
                    'tipolicencia' => $input['tipolicencia'] ?? null,
                    'modalidad' => $input['modalidad'] ?? null,
                    'estado' => $input['estado'] ?? null,
                    'fechaultimoestado' => isset($input['fechaultimoestado']) ? date('Y-m-d', strtotime($input['fechaultimoestado'])) : null,
                ]);

                return [
                    'status' => 'success',
                    'message' => 'API: Expediente creado correctamente'
                ];
            }
        } catch (PDOException $e) {
            return [
                'status' => 'error',
                'message' => 'Error al actualizar/crear el expediente: ' . $e->getMessage()
            ];
        }
    }
}