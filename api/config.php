<?php
// Retornar configuración desde variables de entorno ya cargadas
return [
    'HOST' => $_ENV['DB_HOST'],
    'PATH_AWS' => $_ENV['PATH_AWS'],
    'DB' => $_ENV['DB_NAME'],
    'USER' => $_ENV['DB_USER'],
    'PASS' => $_ENV['DB_PASS'],
    'MAILTO' => $_ENV['MAIL_TO'],
    'MAILFROM' => $_ENV['MAIL_FROM'],
    'AWS_KEY' => $_ENV['AWS_ACCESS_KEY_ID'],
    'AWS_SECRET' => $_ENV['AWS_SECRET_ACCESS_KEY'],
    'BUCKET' => $_ENV['AWS_BUCKET'],
    'BUCKET_FOLDER' => $_ENV['AWS_BUCKET_FOLDER'],
    'REGION' => $_ENV['AWS_REGION'],
    'APP_ENV' => $_SERVER['APP_ENV'],
];