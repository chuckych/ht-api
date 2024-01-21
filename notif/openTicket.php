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

checkDP($dp['subjet'], "subjet", $time_start);

$dp['length'] = 9999;

$asunto = ($dp['subjet']);

$url     = "$dataCompany[hostApi]tickets/";
$dataApi = json_decode(requestApi($url, $token, $dp, 10), true);

// http_response_code(200);
// (response($dataApi, 0, 'OK', 200, $time_start, 0, 0));
// exit;

if ($dataApi['DATA']) {

    foreach ($dataApi['DATA'] as $v) {
        $data[] = array(
            "ID"          => $v['ID'],
            "Refer"       => $v['Refer'],
            "Fecha"       => fechFormat($v['Fecha'], 'd/m/Y H:i'),
            "Edad"        => $v['Edad'] ?? '',
            "FechM"       => ($v['FechM']) ? fechFormat($v['FechM'], 'd/m/Y H:i') : '',
            "EdadM"       => ($v['EdadM']),
            "User"        => $v['User']['str'],
            "Empre"       => $v['Empre']['str'],
            "NombreAsign" => $v['Asign']['nombre'] ?? '',
            "Asign"       => $v['Asign']['str'] . ' #-># ' . $v['Asign']['mail'],
            "Usuario"     => $v['User']['str'] . ' #-># ' . $v['User']['mail'],
            "AsignMail"   => $v['Asign']['mail'],
            "Estado"      => $v['Estado']['str'],
            "EstadoColor" => 'background-color:' . $v['Estado']['bg'] . ';color:' . $v['Estado']['tx'],
            "Prior"       => $v['Prior']['str'] ?? '',
            "Modul"       => $v['Modul']['str'] ?? '',
            "link"        => $v['link'] ?? '',
            "Respuesta"   => ($v['Respo'] == '0') ? ' | POR RESPONDER' : '',
        );
    }
    
    $dataAgrup = group_by($dp['agrup'], $data);

    foreach ($dataAgrup as $key => $da) {

        $bodyData = $url = $dataParametros = $dataApi = '';
        $dataKey     = explode(' #-># ', $key, 2);
        $responsable = $dataKey[0];
        $correo      = $dataKey[1];
        $date        = dateNow();
        $count = count($da);

        /** Body del mail */
        $bodyData .= '<div>';
        $bodyData .= "Hola <b>$responsable</b>\n";
        $bodyData .= "$asunto:\n";
        $bodyData .= "Total: ($count):\n";
        $bodyData .= "\n";
        foreach ($da as $d) {

            $bodyData .= "<hr>";
            $bodyData .= "Ticket <b>$d[ID]</b> $d[Respuesta]\n";
            $bodyData .= "Referencia: <b>$d[Refer]</b>.\n";
            $bodyData .= "Creado por: <b>$d[User] ($d[Empre])</b>.\n";
            $bodyData .= "Fecha alta: <b>$d[Fecha] ($d[Edad])</b>.\n";
            $bodyData .= "Estado: <span style='$d[EstadoColor]; padding-left:5px;padding-right:5px'>$d[Estado]</span>\n";
            $bodyData .= "Prioridad: <b>$d[Prior]</b>.\n";
            if ($d['Modul']) {
                $bodyData .= "Módulo: <b>$d[Modul]</b>.\n";
            }
            if ($d['FechM']) {
                $bodyData .= "Última interacción: <b>$d[FechM] ($d[EdadM])</b>\n";
            }
            $bodyData .= "Link: $d[link]\n";
        }

        $bodyData .= '</div>';
        /** Fin Body */

        /** Parametros para enviar correo */
        $dataParams = array(
            "subjet"  => $asunto,
            "to"      => $d['AsignMail'],
            "replyTo" => $dataCompany['emailCompany'],
            "body"    => $bodyData,
        );
        /** */
        $url = "$dataCompany[hostApi]sendMail/"; // url api de correo
        $d = json_decode(requestApi($url, $token, $dataParams, 10), true); // Enviamos correo

        $rs[] = array(
            "send" => $d['DATA'][0]
        );
    }
    http_response_code(200);
    (response($rs, 0, 'OK', 200, $time_start, 0, 0));
    exit;
}

http_response_code(200);
(response(array(), 0, 'OK', 200, $time_start, 0, 0));
exit;
