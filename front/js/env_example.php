<?php
  header('Content-Type: application/javascript; charset=UTF-8');
  header('Cache-Control: no-store');
  $env = strtolower(getenv('APP_ENV') ?: 'prod');

  $CONFIG = [
    'local' => [
      'API_URL' => '', // ruta de la api a partir del host
      'AWS_URL' => '', // URL de la carpeta de archivos en AWS S3
    ],
    'stage' => [
      'API_URL' => '',
      'AWS_URL' => '',
    ],
    'prod' => [
      'API_URL' => '',
      'AWS_URL' => '',
    ],
  ];
  $current = $CONFIG[$env] ?? $CONFIG['prod'];
  echo 'window.__ENV=' . json_encode(['ENV'=>$env] + $current, JSON_UNESCAPED_SLASHES) . ';';