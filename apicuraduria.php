<?php

// Definimos los recursos disponibles
$allowedResourceTypes = [
  'resoluciones',
  'radicados'
];

// Validamos que el recurso este disponible
$resourceType = $_GET['resource_type'];
$resourceCur = $_GET['resource_cur'];
$resourceRes = array_key_exists('resource_res', $_GET) ? $_GET['resource_res'] : null;
$resourceVig = array_key_exists('resource_vig', $_GET) ? $_GET['resource_vig'] : null;

if (!in_array($resourceType, $allowedResourceTypes)) {
  die;
}

// Definir cadena de conexion de acuerdo a a la curaduria que hace la peticion
$HOST = '198.71.227.95';
$PATH_AWS = 'https://web-curadurias.s3-us-west-1.amazonaws.com/' . $resourceCur . '/';

switch ($resourceCur) {
  case '1sm':
    $DB = 'curad1';
    $USER = 'consulta';
    $PASS = 'aA0987!1';
    break;
  case '2bq':
    $DB = 'curad2bq';
    $USER = 'usuariocurad';
    $PASS = '12345678';
    break;
  case '1ca':
    $DB = 'curad1ca';
    $USER = 'consulta1ca';
    $PASS = 'aA0987!1';
    break;
  case '2va':
    $DB = 'curaduria2va';
    $USER = 'curaduria2va';
    $PASS = 'Glfu4#95';
    break;         
  default:
    # code...
    break;
}
// Se indica al cliente que lo que recibirá es un json
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Generamos la respuesta asumiendo que el pedido es correcto
switch (strtoupper($_SERVER['REQUEST_METHOD'])) {
  case 'GET':
    echo resolucion($resourceRes, $resourceVig);
    break;
  
  default:
    # code...
    break;
}

function resolucion($id = null, $vigencia = null){

  try{
    $con = new PDO('mysql:host=' . $GLOBALS["HOST"] . ';dbname=' . $GLOBALS["DB"], $GLOBALS["USER"], $GLOBALS["PASS"]);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if (is_null($id) && is_null($vigencia)) {
      $query = "select e.radicacion, e.solicitante, e.direccion, e.modalidad, x.resolucion, x.fecharesol, concat('" . $GLOBALS["PATH_AWS"] . "', x.archivo) as archivo from expediente e, expedidos x where x.idexpediente=e.idexpediente;";
    }else{
      $query = "select e.radicacion, e.solicitante, e.direccion, e.modalidad, x.resolucion, x.fecharesol, concat('" . $GLOBALS["PATH_AWS"] . "', x.archivo) as archivo from expediente e, expedidos x where x.idexpediente=e.idexpediente and x.resolucion= :id and year(x.fecharesol)= :vigencia;";
    }

    $stmt = $con->prepare($query);
    $stmt->execute(array(':id' => $id, ':vigencia' => $vigencia ));
    $stmt->execute();
    if($stmt->rowCount() > 0){
      $resoluciones = ['response' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }else{
      $resoluciones = ['response' => 'error', 'message' => 'No se encontró el registro solicitado. Por favor comuniquese con nosotros a cualquiera de nuestras lineas de atención'];
    }

  } catch(PDOException $e) {
    $resoluciones = ['response' => 'error', 'message' => 'Error conectando con la base de datos: ' . $e->getMessage()];
  }

  return json_encode($resoluciones);
}