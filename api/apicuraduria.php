<?php

// Definimos los recursos disponibles
$allowedResourceTypes = [
  'resoluciones',
  'resolucion',
  'radicados',
  'radicacion',
  'publicacion',
  'publicaciones',
  'valla',
  'pago',
  'pqr'
];

// Validamos que el recurso este disponible
$resourceType = $_GET['resource_type'];
$resourceCur = $_GET['resource_cur'];

if (!in_array($resourceType, $allowedResourceTypes)) {
  die;
}

// Definir cadena de conexion de acuerdo a a la curaduria que hace la peticion
require '../vendor/autoload.php';
$config = require('config.php');
$HOST = $config['HOST'];
$PATH_AWS = $config['PATH_AWS'] . $resourceCur . '/';
$DB = $config['DB'];
$USER = $config['USER'];
$PASS = $config['PASS'];
$MAILTO = $config['MAILTO'];
$MAILFROM = $config['MAILFROM'];

// Se indica al cliente que lo que recibirá es un json
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

switch ($resourceType) {
  case 'resolucion':
    $resourceRes = array_key_exists('resource_data1', $_GET) ? $_GET['resource_data1'] : null;
    $resourceVig = array_key_exists('resource_data2', $_GET) ? $_GET['resource_data2'] : null;
    // Generamos la respuesta asumiendo que el pedido es correcto
    switch (strtoupper($_SERVER['REQUEST_METHOD'])) {
      case 'GET':
        echo resolucion($resourceRes, $resourceVig);
        break;

      default:
        # code...
        break;
    }
    break;

  case 'radicacion':
    $resourceRad = array_key_exists('resource_data1', $_GET) ? $_GET['resource_data1'] : null;
    $resourceVig = array_key_exists('resource_data2', $_GET) ? $_GET['resource_data2'] : null;
    // Generamos la respuesta asumiendo que el pedido es correcto
    switch (strtoupper($_SERVER['REQUEST_METHOD'])) {
      case 'GET':
        echo radicacion($resourceRad, $resourceVig);
        break;

      default:
        # code...
        break;
    }
    break;

  case 'radicados';
  case 'resoluciones':
    $fechaini = array_key_exists('resource_data1', $_GET) ? $_GET['resource_data1'] : null;
    $fechafin = array_key_exists('resource_data2', $_GET) ? $_GET['resource_data2'] : null;
    // Generamos la respuesta asumiendo que el pedido es correcto
    switch (strtoupper($_SERVER['REQUEST_METHOD'])) {
      case 'GET':
        echo resoluciones($fechaini, $fechafin);
        break;

      default:
        # code...
        break;
    }
    break;

  case 'publicacion':
    // Generamos la respuesta asumiendo que el pedido es correcto
    switch (strtoupper($_SERVER['REQUEST_METHOD'])) {
      case 'POST':
        echo publicacion();
        break;
      case 'GET':
        echo consecutivo("idpublicaciones", "publicaciones");
        break;
      default:
        # code...
        break;
    }
    break;

  case 'publicaciones':
    $fechaini = array_key_exists('resource_data1', $_GET) ? $_GET['resource_data1'] : null;
    $fechafin = array_key_exists('resource_data2', $_GET) ? $_GET['resource_data2'] : null;
    // Generamos la respuesta asumiendo que el pedido es correcto
    switch (strtoupper($_SERVER['REQUEST_METHOD'])) {
      case 'GET':
        echo publicaciones($fechaini, $fechafin);
        break;

      default:
        # code...
        break;
    }
    break;

  case 'valla':
    // Generamos la respuesta asumiendo que el pedido es correcto
    switch (strtoupper($_SERVER['REQUEST_METHOD'])) {
      case 'POST':
        echo valla();
        break;
      case 'GET':

        break;
      default:
        # code...
        break;
    }
    break;

  case 'pago':
    // Generamos la respuesta asumiendo que el pedido es correcto
    switch (strtoupper($_SERVER['REQUEST_METHOD'])) {
      case 'POST':
        echo pago();
        break;
      case 'GET':

        break;
      default:
        # code...
        break;
    }
    break;

  case 'pqr':
    switch (strtoupper($_SERVER['REQUEST_METHOD'])) {
      case 'POST':
        echo pqr();
        break;
      case 'GET':

        break;
      default:
        # code...
        break;
    }
    break;
  default:
    # code...
    break;
}

function radicacion($id = null, $vigencia = null)
{

  try {
    $con = new PDO('mysql:host=' . $GLOBALS["HOST"] . ';dbname=' . $GLOBALS["DB"], $GLOBALS["USER"], $GLOBALS["PASS"]);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!is_null($id) && !is_null($vigencia)) {
      $query = "select e.radicacion, e.fecharad, e.solicitante, e.direccion, e.modalidad, e.estado from expediente e where e.idradicacion= :id and e.vigencia= :vigencia;";
    }

    $stmt = $con->prepare($query);
    $stmt->execute(array(':id' => $id, ':vigencia' => $vigencia));
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
      $resoluciones = ['response' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    } else {
      $resoluciones = ['response' => 'error', 'message' => 'No se encontró el registro solicitado. Por favor comuniquese con nosotros a cualquiera de nuestras lineas de atención'];
    }
  } catch (PDOException $e) {
    $resoluciones = ['response' => 'error', 'message' => 'Error conectando con la base de datos: ' . $e->getMessage()];
  }

  return json_encode($resoluciones);
}

function resolucion($id = null, $vigencia = null)
{
  try {
    $con = new PDO('mysql:host=' . $GLOBALS["HOST"] . ';dbname=' . $GLOBALS["DB"], $GLOBALS["USER"], $GLOBALS["PASS"]);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!is_null($id) && !is_null($vigencia)) {
      $query = "select e.radicacion, e.solicitante, e.direccion, e.modalidad, LPAD(x.resolucion, 4, 0) as resolucion, x.fecharesol, concat('" . $GLOBALS["PATH_AWS"] . "', x.archivo) as archivo from expediente e, expedidos x where x.idexpediente=e.idexpediente and x.resolucion= :id and year(x.fecharesol)= :vigencia;";
      if ($GLOBALS["resourceCur"] == '2va') {
        //TO DO: cambiar el query para VA. Debe consultar por vigencia del radicado y no por fecha de resolucion
        //probar con la 357 del 2020 (corregir la fecha) y 2021
        $query = "select e.radicacion, e.solicitante, e.direccion, e.modalidad, LPAD(x.resolucion, 4, 0) as resolucion, x.fecharesol, concat('" . $GLOBALS["PATH_AWS"] . "', x.archivo) as archivo from expediente e, expedidos x where x.idexpediente=e.idexpediente and x.resolucion= :id and year(x.fecharesol)= :vigencia;";
      }
    }

    $stmt = $con->prepare($query);
    $stmt->execute(array(':id' => $id, ':vigencia' => $vigencia));
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
      $resoluciones = ['response' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    } else {
      $resoluciones = ['response' => 'error', 'message' => 'No se encontró el registro solicitado. Por favor comuniquese con nosotros a cualquiera de nuestras lineas de atención'];
    }
  } catch (PDOException $e) {
    $resoluciones = ['response' => 'error', 'message' => 'Error conectando con la base de datos: ' . $e->getMessage()];
  }

  return json_encode($resoluciones);
}

function resoluciones($fechaini = null, $fechafin = null)
{

  try {
    $con = new PDO('mysql:host=' . $GLOBALS["HOST"] . ';dbname=' . $GLOBALS["DB"], $GLOBALS["USER"], $GLOBALS["PASS"]);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (is_null($fechaini) && is_null($fechafin)) {
      $resoluciones = ['response' => 'error', 'message' => 'Por favor especifique un rango de fechas válido'];
    } else {
      $query = "select e.radicacion, e.solicitante, e.direccion, e.modalidad, LPAD(x.resolucion, 4, 0) as resolucion, x.fecharesol, concat('" . $GLOBALS["PATH_AWS"] . "', x.archivo) as archivo from expediente e, expedidos x where x.idexpediente=e.idexpediente and x.fecharesol between :fechaini and :fechafin order by x.fecharesol desc;";
    }

    $stmt = $con->prepare($query);
    $stmt->execute(array(':fechaini' => $fechaini, ':fechafin' => $fechafin));
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
      $resoluciones = ['response' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    } else {
      $resoluciones = ['response' => 'error', 'message' => 'No se encontró el registro solicitado. Por favor comuniquese con nosotros a cualquiera de nuestras lineas de atención'];
    }
  } catch (PDOException $e) {
    $resoluciones = ['response' => 'error', 'message' => 'Error conectando con la base de datos: ' . $e->getMessage()];
  }

  return json_encode($resoluciones);
}

function consecutivo($id, $table)
{

  $con = new PDO('mysql:host=' . $GLOBALS["HOST"] . ';dbname=' . $GLOBALS["DB"], $GLOBALS["USER"], $GLOBALS["PASS"]);
  $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  $query = "select max($id) as maximo from $table";

  $stmt = $con->prepare($query);
  $stmt->execute();
  if ($stmt->rowCount() > 0) {
    $consecutivo = $stmt->fetchColumn() + 1;
  } else {
    $consecutivo = 1;
  }

  return str_pad($consecutivo, 5, "0", STR_PAD_LEFT);
}

function publicacion()
{
  $json = file_get_contents('php://input');
  $publicaciones[] = json_decode($json, true);

  try {
    $con = new PDO('mysql:host=' . $GLOBALS["HOST"] . ';dbname=' . $GLOBALS["DB"], $GLOBALS["USER"], $GLOBALS["PASS"]);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $idpublicaciones = consecutivo("idpublicaciones", "publicaciones");
    $fecha = date('Y-m-d');
    $fechapublicacion = $_POST["fechapublicacion"]; // $publicaciones[0]['fechapublicacion'];
    $referencia = $_POST["referencia"]; //$publicaciones[0]['referencia'];
    $archivo = "pub/{$idpublicaciones}.pdf";
    $estado = $_POST["estado"]; //$publicaciones[0]['estado'];
    $idtipopublicacion = 2;

    if (isset($_FILES['publicacionFile'])) {

      $query = "insert into publicaciones (idpublicaciones, fecha, fechapublicacion, referencia, archivo, estado, idtipopublicacion) values (:idpublicaciones, :fecha, :fechapublicacion, :referencia, :archivo, :estado, :idtipopublicacion)";

      $stmt = $con->prepare($query);
      $stmt->bindValue(':idpublicaciones', $idpublicaciones);
      $stmt->bindValue(':fecha', $fecha);
      $stmt->bindValue(':fechapublicacion', $fechapublicacion);
      $stmt->bindValue(':referencia', $referencia);
      $stmt->bindValue(':archivo', $archivo);
      $stmt->bindValue(':estado', $estado);
      $stmt->bindValue(':idtipopublicacion', $idtipopublicacion);

      $stmt->execute();

      $temp_file_location = $_FILES['publicacionFile']['tmp_name'];

      require '../vendor/autoload.php';
      $config = require('config.php');

      $s3 = new Aws\S3\S3Client([
        'region'  => 'us-west-1',
        'version' => 'latest',
        'credentials' => [
          'key'    => $config['AWS_KEY'],
          'secret' => $config['AWS_SECRET'],
        ]
      ]);

      $result = $s3->putObject([
        'Bucket' => $config['BUCKET'],
        'Key'    => $GLOBALS["resourceCur"] . '/' . $archivo,
        'Body'   => 'body!',
        'SourceFile' => $temp_file_location,
        'ACL'    => 'public-read'
      ]);

      $publicacion = ['response' => 'success', 'message' => "Documento publicado con éxito", 'url' => "{$GLOBALS["PATH_AWS"]}{$archivo}"];
    }
  } catch (PDOException $e) {
    $publicacion = ['response' => 'danger', 'message' => 'Error conectando con la base de datos: ' . $e->getMessage(), 'url' => ""];
  }

  return json_encode($publicacion);
}

function publicaciones($fechaini = null, $fechafin = null)
{

  try {
    $con = new PDO('mysql:host=' . $GLOBALS["HOST"] . ';dbname=' . $GLOBALS["DB"], $GLOBALS["USER"], $GLOBALS["PASS"]);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (is_null($fechaini) && is_null($fechafin)) {
      $resoluciones = ['response' => 'error', 'message' => 'Por favor especifique un rango de fechas válido'];
    } else {
      $query = "select p.fechapublicacion, p.referencia, concat('" . $GLOBALS["PATH_AWS"] . "', p.archivo) as archivo, t.descripcion as tipopublicacion from publicaciones p, tipopublicacion t where p.idtipopublicacion = t.idtipopublicacion and p.fechapublicacion between :fechaini and :fechafin order by p.fechapublicacion desc;";
    }

    $stmt = $con->prepare($query);
    $stmt->execute(array(':fechaini' => $fechaini, ':fechafin' => $fechafin));
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
      $resoluciones = ['response' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    } else {
      $resoluciones = ['response' => 'error', 'message' => 'No se encontró el registro solicitado. Por favor comuniquese con nosotros a cualquiera de nuestras lineas de atención'];
    }
  } catch (PDOException $e) {
    $resoluciones = ['response' => 'error', 'message' => 'Error conectando con la base de datos: ' . $e->getMessage()];
  }

  return json_encode($resoluciones);
}

function valla()
{
  $json = file_get_contents('php://input');
  $valla[] = json_decode($json, true);

  try {

    $email = $_POST["email"];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return json_encode(['response' => 'danger', 'message' => "La dirección de correo ($email) no es válida.", 'url' => ""]);
    }

    $con = new PDO('mysql:host=' . $GLOBALS["HOST"] . ';dbname=' . $GLOBALS["DB"], $GLOBALS["USER"], $GLOBALS["PASS"]);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $idvalla = consecutivo("id", "vallas");
    $fileName = basename($_FILES["vallaFile"]["name"]);
    $fileName = str_replace(" ", "", $idvalla . $fileName);

    $fecha = date('Y-m-d');
    $proyecto = $_POST["proyecto"];
    $vigencia = $_POST["vigencia"];
    $comentarios = $_POST["comentarios"];

    $archivo = "vallas/{$fileName}";

    if (isset($_FILES['vallaFile'])) {

      $query = "insert into vallas (proyecto, vigencia, comentarios, archivo, email, fecha) values (:proyecto, :vigencia, :comentarios, :archivo, :email, :fecha)";

      $stmt = $con->prepare($query);
      $stmt->bindValue(':proyecto', $proyecto);
      $stmt->bindValue(':vigencia', $vigencia);
      $stmt->bindValue(':comentarios', $comentarios);
      $stmt->bindValue(':archivo', $archivo);
      $stmt->bindValue(':email', $email);
      $stmt->bindValue(':fecha', $fecha);

      $stmt->execute();

      $temp_file_location = $_FILES['vallaFile']['tmp_name'];

      require '../vendor/autoload.php';
      $config = require('config.php');

      $s3 = new Aws\S3\S3Client([
        'region'  => 'us-west-1',
        'version' => 'latest',
        'credentials' => [
          'key'    => $config['AWS_KEY'],
          'secret' => $config['AWS_SECRET'],
        ]
      ]);

      $result = $s3->putObject([
        'Bucket' => $config['BUCKET'],
        'Key'    => $GLOBALS["resourceCur"] . '/' . $archivo,
        'Body'   => 'body!',
        'SourceFile' => $temp_file_location,
        'ACL'    => 'public-read'
      ]);

      $vigencia = substr($vigencia, 2, 2);
      $proyecto = str_pad($proyecto, 4, "0", STR_PAD_LEFT);
      $to = $GLOBALS['MAILTO'];
      $subject = '***FOTO DE VALLA RECIBIDA';
      $message = "<h3>Se ha recibido una foto de la valla del proyecto <strong>{$GLOBALS['CODCUR']}-$vigencia-$proyecto</strong> a traves de la pagina web.</h3><br><br>Puede verlo en el siguiente link: {$GLOBALS["PATH_AWS"]}{$archivo}<br><br>";
      $message .= "Enviado por: $email.<br><br>";
      $message .= "Comentarios: $comentarios";
      $headers[] = 'MIME-Version: 1.0';
      $headers[] = 'Content-type: text/html; charset=iso-8859-1';   
      $headers[] = 'To: '.$to;
      $headers[] = "From: {$GLOBALS['SENDER']} <{$GLOBALS['MAILFROM']}>";

      mail($to, $subject, $message, implode("\r\n", $headers));
      $publicacion = ['response' => 'success', 'message' => "Información recibida con éxito", 'url' => "{$GLOBALS["PATH_AWS"]}{$archivo}"];
    }
  } catch (PDOException $e) {
    $publicacion = ['response' => 'danger', 'message' => 'Error conectando con la base de datos: ' . $e->getMessage(), 'url' => ""];
  }

  return json_encode($publicacion);
}

function pago()
{
  $json = file_get_contents('php://input');
  $pago[] = json_decode($json, true);

  try {

    $email = $_POST["email"];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return json_encode(['response' => 'danger', 'message' => "La dirección de correo ($email) no es válida.", 'url' => ""]);
    }

    $con = new PDO('mysql:host=' . $GLOBALS["HOST"] . ';dbname=' . $GLOBALS["DB"], $GLOBALS["USER"], $GLOBALS["PASS"]);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $idpago = consecutivo("id", "pagos");
    $fileName = basename($_FILES["pagoFile"]["name"]);
    $fileName = str_replace(" ", "", $idpago . $fileName);

    $fecha = date('Y-m-d');
    $proyecto = $_POST["proyecto"];
    $vigencia = $_POST["vigencia"];
    $comentarios = $_POST["comentarios"];

    $archivo = "pagos/{$fileName}";

    if (isset($_FILES['pagoFile'])) {

      $query = "insert into pagos (proyecto, vigencia, comentarios, archivo, email, fecha) values (:proyecto, :vigencia, :comentarios, :archivo, :email, :fecha)";

      $stmt = $con->prepare($query);
      $stmt->bindValue(':proyecto', $proyecto);
      $stmt->bindValue(':vigencia', $vigencia);
      $stmt->bindValue(':comentarios', $comentarios);
      $stmt->bindValue(':archivo', $archivo);
      $stmt->bindValue(':email', $email);
      $stmt->bindValue(':fecha', $fecha);

      $stmt->execute();

      $temp_file_location = $_FILES['pagoFile']['tmp_name'];

      require '../vendor/autoload.php';
      $config = require('config.php');

      $s3 = new Aws\S3\S3Client([
        'region'  => 'us-west-1',
        'version' => 'latest',
        'credentials' => [
          'key'    => $config['AWS_KEY'],
          'secret' => $config['AWS_SECRET'],
        ]
      ]);

      $result = $s3->putObject([
        'Bucket' => $config['BUCKET'],
        'Key'    => $GLOBALS["resourceCur"] . '/' . $archivo,
        'Body'   => 'body!',
        'SourceFile' => $temp_file_location,
        'ACL'    => 'public-read'
      ]);

      $vigencia = substr($vigencia, 2, 2);
      $proyecto = str_pad($proyecto, 4, "0", STR_PAD_LEFT);
      $to = $GLOBALS['MAILTO'];
      $subject = '***NUEVO COMPROBANTE DE PAGO RECIBIDO';
      $message = "<h3>Se ha recibido un nuevo comprobante de pago de expensas del proyecto <strong>{$GLOBALS['CODCUR']}-$vigencia-$proyecto</strong> a traves de la pagina web.</h3><br><br>Puede verlo en el siguiente link: {$GLOBALS["PATH_AWS"]}{$archivo}<br><br>";
      $message .= "Enviado por: $email.<br><br>";
      $message .= "Comentarios: $comentarios";
      $headers[] = 'MIME-Version: 1.0';
      $headers[] = 'Content-type: text/html; charset=iso-8859-1';   
      $headers[] = 'To: '.$to;
      $headers[] = "From: {$GLOBALS['SENDER']} <{$GLOBALS['MAILFROM']}>";

      mail($to, $subject, $message, implode("\r\n", $headers));
      $publicacion = ['response' => 'success', 'message' => "Información recibida con éxito", 'url' => "{$GLOBALS["PATH_AWS"]}{$archivo}"];
    }
  } catch (PDOException $e) {
    $publicacion = ['response' => 'danger', 'message' => 'Error conectando con la base de datos: ' . $e->getMessage(), 'url' => ""];
  }

  return json_encode($publicacion);
}

function pqr()
{
  // $json = file_get_contents('php://input');
  // $pago[] = json_decode($json, true);
  try {

    $email = $_POST["email"];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return json_encode(['response' => 'danger', 'message' => "La dirección de correo ($email) no es válida.", 'url' => ""]);
    }

    $con = new PDO('mysql:host=' . $GLOBALS["HOST"] . ';dbname=' . $GLOBALS["DB"], $GLOBALS["USER"], $GLOBALS["PASS"]);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    //$idpago = consecutivo("id", "pagos");
    //$fileName = basename($_FILES["pagoFile"]["name"]);
    //$fileName = str_replace(" ", "", $idpago . $fileName);

    $fecharecibido = date('Y-m-d');
    $nombre = $_POST["nombre"];
    $asunto = $_POST["asunto"];
    $comentario = $_POST["comentario"];

    // $archivo = "pagos/{$fileName}";


    $query = "insert into pqr (nombre, email, asunto, comentario, fecharecibido) values (:nombre, :email, :asunto, :comentario, :fecharecibido)";

    $stmt = $con->prepare($query);
    $stmt->bindValue(':nombre', $nombre);
    $stmt->bindValue(':email', $email);
    $stmt->bindValue(':asunto', $asunto);
    $stmt->bindValue(':comentario', $comentario);
    $stmt->bindValue(':fecharecibido', $fecharecibido);

    $stmt->execute();

    // $temp_file_location = $_FILES['pagoFile']['tmp_name']; 

    // require '../vendor/autoload.php';
    // $config = require('config.php');

    $to = $GLOBALS['MAILTO'];
    $subject = '***NUEVO PQR RECIBIDO';
    $message = "<h3>Se ha recibido un nuevo PQR a traves de la pagina web.</h3><br><br>El mensaje recibido es el siguiente:<br><br><h4>Asunto: $asunto</h4><br>$comentario<br><br>";
    $message .= "Enviado por: $nombre<br>E-mail: $email.<br><br>";
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=iso-8859-1';
    $headers[] = 'To: ' . $to;
    $headers[] = "From: {$GLOBALS['SENDER']} <{$GLOBALS['MAILFROM']}>";

    mail($to, $subject, $message, implode("\r\n", $headers));

    $publicacion = ['response' => 'success', 'message' => "Información recibida con éxito."];
  } catch (PDOException $e) {
    $publicacion = ['response' => 'danger', 'message' => 'Error conectando con la base de datos: ' . $e->getMessage(), 'url' => ""];
  }

  return json_encode($publicacion);
}
