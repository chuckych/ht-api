<?php
require __DIR__ . '../../fn.php';
header("Content-Type: application/json");
ini_set('max_execution_time', 900); //900 seconds = 15 minutes
tz();
tzLang();
errorReport();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '../../vendor/phpmailer/phpmailer/src/Exception.php';
require  __DIR__ . '../../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require __DIR__ . '../../vendor/phpmailer/phpmailer/src/SMTP.php';

require __DIR__ . '../../vendor/autoload.php';

$mail = new PHPMailer(true);
$pathLogOK = __DIR__ . '../../logs/' . date('Ymd') . '_sendMailOK.log'; // ruta del archivo de Log de errores
$pathLogError = __DIR__ . '../../logs/' . date('Ymd') . '_sendMailError.log'; // ruta del archivo de Log de errores

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    http_response_code(400);
    (response(array(), 0, 'Invalid Request Method: ' . $_SERVER['REQUEST_METHOD'], 400, $time_start, 0, 0));
    exit;
}

$dp = ($_REQUEST); // dataPayload
$dp = file_get_contents("php://input");

if (strlen($dp) > 0 && isValidJSON($dp)) {
    $dp = json_decode($dp, true);
} else {
    isValidJSON($dp);
    http_response_code(400);
    (response(array(), 0, 'Invalid json Payload', 400, $time_start, 0, 0));
}

$dp['subjet'] = ($dp['subjet']) ?? '';
$dp['subjet'] = vp($dp['subjet'], 'subjet', 'str', 100);

$dp['to'] = ($dp['to']) ?? '';
$dp['to'] = vp($dp['to'], 'to', 'str', 100);

$dp['replyTo'] = ($dp['replyTo']) ?? '';
$dp['replyTo'] = vp($dp['replyTo'], 'replyTo', 'str', 100);

$dp['body'] = ($dp['body']) ?? '';
$dp['body'] = vp($dp['body'], 'body', '', 0);

checkDP($dp['subjet'], "subjet", $time_start);
checkDP($dp['to'], 'to', $time_start);
checkDP($dp['replyTo'], 'replyTo', $time_start);
checkDP($dp['body'], 'body', $time_start);

$dp['to']      = ($dp['to'] == 'wf-ch') ? 'vacaciones@hrprocess.com.ar': $dp['to'];
$dp['replyTo'] = ($dp['replyTo'] == 'wf-ch') ? 'vacaciones@hrprocess.com.ar': $dp['replyTo'];

try {
    // Server settings
    $mail->setLanguage('es', __DIR__ . '../../vendor/phpmailer/phpmailer/language/');
    $mail->SMTPDebug   = 0; // Enable verbose debug output
    // $mail->SMTPDebug   = SMTP::DEBUG_SERVER; // Enable verbose debug output
    $mail->isSMTP(); // Send using SMTP
    $mail->Host        = 'smtp.hostinger.com.ar'; // Set the SMTP server to send through
    $mail->SMTPAuth    = true; // Enable SMTP authentication
    $mail->Username    = 'notificacion@helpticket.com.ar';  // SMTP username
    $mail->Password    = 'U7h2uQYe89k9kzy'; // SMTP password
    $mail->SMTPSecure  = 'SSL'; // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
    // $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port        = '587'; // TCP port to connect to
    // Recipients
    $mail->SMTPOptions = array('ssl' => array('verify_peer_name' => false)); // Permite conexiones seguras
    $mail->SMTPAutoTLS = false; // Permite conexiones seguras
    $mail->addAddress($dp['to']); // Add a recipient
    $mail->addReplyTo($dp['replyTo']); // Responder a
    $mail->setFrom('notificacion@helpticket.com.ar', 'Help Ticket'); // Quien envia el correo
    $mail->Subject = $dp['subjet'];
    $mail->Body = nl2br($dp['body']);
    $mail->AltBody = strip_tags($dp['body']);

    $mail->isHTML(true); // Set email format to HTML
    $mail->CharSet = 'UTF-8'; // Activo condificacción utf-8
    $mail->send();

    $msjLog = ("Correo enviado correctamente a $dp[to]\nBody: \n".(strip_tags($dp['body']))."");
    $msj = ("Correo enviado correctamente a $dp[to]");

    writeLog($msjLog, $pathLogOK); // escribir en el log de errores el error
    http_response_code(200);
    (response(array($msj), 0, 'OK', 200, $time_start, 0, 0));
    exit;
} catch (Exception $e) {
    $msj = ("El correo a {$dp['to']} no pudo ser enviado. Mailer Error: {$mail->ErrorInfo}");
    writeLog($msj, $pathLogError); // escribir en el log de errores el error
    http_response_code(400);
    (response(array($msj), 0, 'Error', 400, $time_start, 0, 0));
    exit;
}
