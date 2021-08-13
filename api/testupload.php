<?php

// Include the SDK using the composer autoloader
require '../vendor/autoload.php';

$s3 = new Aws\S3\S3Client([
	'region'  => 'us-west-1',
	'version' => 'latest',
	'credentials' => [
	    'key'    => "",
	    'secret' => "",
	]
]);

// Send a PutObject request and get the result object.
$key = '2bq/pub/_nuevo.pdf';

$result = $s3->putObject([
	'Bucket' => 'web-curadurias',
	'Key'    => $key,
	'Body'   => 'this is the body!',
	'SourceFile' => 'C:\Users\Windows\Downloads\0322-2021.pdf', //-- use this if you want to upload a file from a local location
    'ACL'    => 'public-read'
]);

// Print the body of the result by indexing into the result object.
var_dump($result);