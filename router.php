<?php

$matches=[];
if(preg_match('/\/([^\/]+)\/([^\/]+)\/([^\/]+)\/([^\/]+)\/([^\/]+)\/([^\/]+)/',$_SERVER["REQUEST_URI"],$matches))
{
    $_GET['resource_type']=$matches[3];    
    $_GET['resource_cur']=$matches[4];
    $_GET['resource_res']=$matches[5];
    $_GET['resource_vig']=$matches[6];
    error_log(print_r($matches,1));
    require 'apicuraduria.php';
}elseif(preg_match('/\/([^\/]+)\/([^\/]+)\/([^\/]+)\/([^\/]+)/',$_SERVER["REQUEST_URI"],$matches))
{
    $_GET['resource_type']=$matches[3];    
    $_GET['resource_cur']=$matches[4];
    error_log(print_r($matches,1));
    require 'apicuraduria.php';
}else if(preg_match('/\/([^\/]+)\/?/',$_SERVER["REQUEST_URI"],$matches))
{
    $_GET['resource_type']=$matches[1];        
    error_log(print_r($matches,1));
    require 'apicuraduria.php';
}else
{
    error_log('No matches');
    http_response_code(404);
}

?>