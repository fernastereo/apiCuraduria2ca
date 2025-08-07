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

}