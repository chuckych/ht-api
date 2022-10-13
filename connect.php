<?php
/** CONNECTAR DATABASE */
$serverName = $dataCompany['host'];
$db         = $dataCompany['db'];
$user       = $dataCompany['user'];
$pass       = $dataCompany['pass'];
/** */
$dsn = "mysql:host=$serverName;dbname=$db;charset=UTF8";

try {
	$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
	$connpdo =  new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
	$msj = die($e->getMessage());
    $pathLog = __DIR__ . '/logs/' . date('Ymd') . '_errorDBQuery.log'; // ruta del archivo de Log de errores
    writeLog(PHP_EOL . 'Message: ' . json_encode($e->getMessage(), JSON_UNESCAPED_UNICODE) . PHP_EOL . 'Source: ' . '"' . $_SERVER['REQUEST_URI'] . '"', $pathLog); // escribir en el log de errores el error
    http_response_code(400);
    (response(array(), 0, $e->getMessage(), 400, timeStart(), 0, ''));
    exit;
}