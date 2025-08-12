<?php
$env = getenv('APP_ENV') ?: getenv('ENV');

$configs = [
    // Producción
    'prod' => [
        'HOST' => '', // URL de la base de datos
        'PATH_AWS' => '', // URL de la carpeta de archivos en AWS S3
        'DB' => '', // Nombre de la base de datos
        'USER' => '', // Usuario de la base de datos
        'PASS' => '', // Contraseña de la base de datos
        'MAILTO' => '', // Correo electrónico de destino
        'MAILFROM' => '', // Correo electrónico de origen
        'AWS_KEY' => '', // Clave de acceso a AWS
        'AWS_SECRET' => '', // Secreto de acceso a AWS
        'BUCKET' => '', // Nombre del bucket de AWS
        'BUCKET_FOLDER' => '', // Nombre de la carpeta en el bucket de AWS
        'REGION' => '', // Región de AWS
    ],
    // Staging
    'stage' => [
        'HOST' => '', // URL de la base de datos
        'PATH_AWS' => '', // URL de la carpeta de archivos en AWS S3
        'DB' => '', // Nombre de la base de datos
        'USER' => '', // Usuario de la base de datos
        'PASS' => '', // Contraseña de la base de datos
        'MAILTO' => '', // Correo electrónico de destino
        'MAILFROM' => '', // Correo electrónico de origen
        'AWS_KEY' => '', // Clave de acceso a AWS
        'AWS_SECRET' => '', // Secreto de acceso a AWS
        'BUCKET' => '', // Nombre del bucket de AWS
        'BUCKET_FOLDER' => '', // Nombre de la carpeta en el bucket de AWS
        'REGION' => '', // Región de AWS
    ],
    // Pruebas / local
    'local' => [
        'HOST' => '', // URL de la base de datos
        'PATH_AWS' => '', // URL de la carpeta de archivos en AWS S3
        'DB' => '', // Nombre de la base de datos
        'USER' => '', // Usuario de la base de datos
        'PASS' => '', // Contraseña de la base de datos
        'MAILTO' => '', // Correo electrónico de destino
        'MAILFROM' => '', // Correo electrónico de origen
        'AWS_KEY' => '', // Clave de acceso a AWS
        'AWS_SECRET' => '', // Secreto de acceso a AWS
        'BUCKET' => '', // Nombre del bucket de AWS
        'BUCKET_FOLDER' => '', // Nombre de la carpeta en el bucket de AWS
        'REGION' => '', // Región de AWS
    ],
];

return $configs[$env] ?? $configs['prod'];