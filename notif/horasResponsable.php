<?php
require __DIR__ . '../../fn.php';
header("Content-Type: application/json");
ini_set('max_execution_time', 900); // 900 seconds = 15 minutes
tz();
tzLang();
errorReport();

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    http_response_code(400);
    (response(array(), 0, 'Invalid Request Method: ' . $_SERVER['REQUEST_METHOD'], 400, $time_start, 0, 0));
    exit;
}

$stmt = array();

$dp = ($_REQUEST); // dataPayload
$dp = file_get_contents("php://input");

if (strlen($dp) > 0 && isValidJSON($dp)) {
    $dp = json_decode($dp, true);
} else {
    isValidJSON($dp);
    http_response_code(400);
    (response(array(), 0, 'Invalid json Payload', 400, $time_start, 0, 0));
}

$dataApi['DATA']    = $dataApi['DATA'] ?? '';
$dataApi['MESSAGE'] = $dataApi['MESSAGE'] ?? '';

$dp['subjet'] = ($dp['subjet']) ?? '';
$dp['subjet'] = vp($dp['subjet'], 'subjet', 'str', 100);

$dp['agrup'] = ($dp['agrup']) ?? '';
$dp['agrup'] = vp($dp['agrup'], 'agrup', 'str', 20);

$dp['to'] = ($dp['to']) ?? '';
$dp['to'] = vp($dp['to'], 'to', 'str', 50);

$dp['day_past'] = ($dp['day_past']) ?? '';
$dp['day_past'] = vp($dp['day_past'], 'day_past', 'int', 2);

checkDP($dp['subjet'], "subjet", $time_start);

$dp['length'] = 9999;

$asunto = ($dp['subjet']);
$day_past = intval($dp['day_past']);
$day_pastStr = "-$day_past day";

$future = $day_past-1;
$date_futureStr = "+$future day";

$date_now = date('Y-m-d');
$date_past = strtotime($day_pastStr, strtotime($date_now));
$date_past = date('Ymd', $date_past);

$date_now = $date_past;
$date_future = strtotime($date_futureStr, strtotime($date_now));
$date_future = date('Ymd', $date_future);


$query = "SELECT CONCAT_WS(' ', usuarios.nombre, usuarios.apellido) as 'responsable', SUM(proy_horas.t_min) AS 'sum_duracion', SUM(.proy_horas.traslados_min) AS 'sum_traslados' FROM proy_horas INNER JOIN proyectos ON proy_horas.id_proyecto=.proyectos.id INNER JOIN usuarios ON proy_horas.responsable=.usuarios.uid WHERE proy_horas.id >'0' AND proy_horas.fecha_ini BETWEEN '$date_past' AND '$date_future' GROUP BY proy_horas.responsable ORDER BY sum_duracion DESC";

$stmt = $dbApiQuery($query) ?? '';

if ($stmt) {

    foreach ($stmt as $v) {
        $sum_duracion  = $v['sum_duracion'];
        $sum_traslados = $v['sum_traslados'];
        $responsable   = $v['responsable'];

        $dataHorasResp[] = array(
            'sum_duracion'  => MinHora($sum_duracion),
            'sum_traslados' => MinHora($sum_traslados),
            'sum_total'     => MinHora($sum_traslados + $sum_duracion),
            'responsable'   => $responsable,

        );
    }

        $bodyData = $url = $dataParametros = $dataApi = '';

        $correo     = $dp['to'];
        $date       = dateNow();
        $FechaDesde = fechFormat($date_past, 'd/m/Y');
        $FechaHasta = fechFormat($date_future, 'd/m/Y');

        /** Body del mail */
        $bodyData .= '<div>';
        $bodyData .= "$asunto\n";
        $bodyData .= "Desde el $FechaDesde al $FechaHasta\n";
        $bodyData .= "\n";

        foreach ($dataHorasResp as $key => $da) {
        
            $bodyData .= "<hr>";
            $bodyData .= "Responsable: <b>$da[responsable]</b>.\n";
            $bodyData .= "Horas: <b>$da[sum_duracion]</b>.\n";
            $bodyData .= "Traslados: <b>$da[sum_traslados]</b>.\n";
            $bodyData .= "Total: <b>$da[sum_total]</b>.\n";

        }

        $bodyData .= '</div>';
        /** Fin Body */

        /** Parametros para enviar correo */
        $dataParams = array(
            "subjet"  => $asunto,
            "to"      => $dp['to'],
            "replyTo" => $dataCompany['emailCompany'],
            "body"    => $bodyData,
        );

        /** */
        $url = "$dataCompany[hostApi]sendMail/"; // url api de correo
        $d = json_decode(requestApi($url, $token, $dataParams, 10), true); // Enviamos correo

        $rs[] = array(
            "send" => $d['DATA'][0]
        );
    http_response_code(200);
    (response($rs, 0, 'OK', 200, $time_start, 0, 0));
    exit;
}


http_response_code(200);
(response(array(), 0, 'OK', 200, $time_start, 0, 0));
exit;
