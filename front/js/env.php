<?php
  header('Content-Type: application/javascript; charset=UTF-8');
  header('Cache-Control: no-store');

  // Cargar el archivo .env desde la carpeta api
  $dotenv_path = __DIR__ . '/../../api/.env';
  if (file_exists($dotenv_path)) {
    $env_content = file_get_contents($dotenv_path);
    $lines = explode("\n", $env_content);
    foreach ($lines as $line) {
      if (trim($line) !== '' && strpos($line, '=') !== false) {
        list($key, $value) = explode('=', $line, 2);
        putenv(trim($key) . '=' . trim($value));
        $_ENV[trim($key)] = trim($value);
      }
    }
  }

  $env = strtolower(getenv('APP_ENV') ?: 'prod');
  $aws_url = getenv('PATH_AWS');
  $api_url = getenv('API_URL_FRONT');

  $CONFIG = [
    'local' => [
      'API_URL' => $api_url,
      'AWS_URL' => $aws_url,
    ],
    'stage' => [
      'API_URL' => $api_url,
      'AWS_URL' => $aws_url,
    ],
    'prod' => [
      'API_URL' => $api_url,
      'AWS_URL' => $aws_url,
    ],
  ];

  $current = $CONFIG[$env] ?? $CONFIG['prod'];
  echo 'window.__ENV=' . json_encode(['ENV'=>$env] + $current, JSON_UNESCAPED_SLASHES) . ';';