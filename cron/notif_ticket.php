<?php

$_GET['Token']  = $_GET['Token'] ?? '';
$_GET['subjet'] = $_GET['subjet'] ?? ''; // Ej. Resumen de Ticket's abiertos
$_GET['agrup']  = $_GET['agrup'] ?? ''; // Ej. Asign, usuario
$_GET['cierra'] = $_GET['cierra'] ?? ''; // Ej. cierra
$_GET['asignado'] = $_GET['asignado'] ?? ''; // Ej. cierra
$_GET['order'] = $_GET['order'] ?? ''; // Ej. cierra

// $_SERVER['HTTP_TOKEN'] = "b71afe7854f942ec7379687c7f7e9871a52245ac";
$_SERVER['HTTP_TOKEN'] = $_GET['Token'];

require __DIR__ . '../../fn.php';
header("Content-Type: application/json");
ini_set('max_execution_time', 900); // 900 seconds = 15 minutes
tz();
tzLang();
errorReport();
/** Parametros para enviar correo */
$dataParams = array(
    "subjet"   => $_GET['subjet'],
    "agrup"    => $_GET['agrup'],
    "cierra"   => $_GET['cierra'],
    "asignado" => $_GET['asignado'],
    "order"    => $_GET['order']
);
/** */
$url = "$dataCompany[hostApi]notif/openTicket.php"; // url api
$d = (requestApi($url, $_SERVER['HTTP_TOKEN'], $dataParams, 10));

print_r($d);
