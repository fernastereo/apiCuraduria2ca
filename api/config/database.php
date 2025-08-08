<?php
// config/database.php

class Database {
    // Propiedades de la base de datos
    private $charset = 'utf8mb4';
    private $HOST;
    private $DB;
    private $USER;
    private $PASS;
    private $pdo;

    public function __construct() {
      // Cargar configuración desde archivo externo
      // Ajusta la ruta según donde esté ubicado tu archivo env.php
        $config = require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config.php';
        
        // Asignar valores de configuración
        $this->HOST = $config['HOST'];
        $this->DB = $config['DB'];
        $this->USER = $config['USER'];
        $this->PASS = $config['PASS'];    
    }
    
    // Conexión a la base de datos
    public function connect() {
        if ($this->pdo == null) {
            $dsn = "mysql:host={$this->HOST};dbname={$this->DB};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            
            try {
                $this->pdo = new PDO($dsn, $this->USER, $this->PASS, $options);
            } catch (\PDOException $e) {
                throw new \PDOException($e->getMessage(), $e->getCode());
            }
        }
        
        return $this->pdo;
    }
}