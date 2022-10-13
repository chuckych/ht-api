<?php
require __DIR__ . '../../fn.php';
header("Content-Type: application/json");
ini_set('max_execution_time', 900); //900 seconds = 15 minutes
tz();
tzLang();
errorReport();

if ($_SERVER['REQUEST_METHOD'] != 'POST' && $_SERVER['REQUEST_METHOD'] != 'GET') {
    http_response_code(400);
    (response(array(), 0, 'Invalid Request Method: ' . $_SERVER['REQUEST_METHOD'], 400, $time_start, 0, 0));
    exit;
}

require __DIR__ . './wc.php';

// print_r($wc);exit;

$columnas[] = "$tabla.id, $tabla.recid, $tabla.referencia, $tabla.usuario, $tabla.empresa, $tabla.asignado, $tabla.estado, $tabla.prioridad, $tabla.modulo, $tabla.tipo, $tabla.proyecto, $tabla.leido, $tabla.respuesta, $tabla.fecha, $tabla.fecha_mod, $tabla.fecha_cierre "; // Columnas de Ticket

$tablaJoin = 'usuarios';
$join .= " INNER JOIN $tablaJoin ON $tabla.usuario = $tablaJoin.uid";
$columnas[] = "$tablaJoin.nombre, $tablaJoin.apellido, $tablaJoin.mail, $tablaJoin.perfil as 'perfil_user'"; // Columnas de usuarios de creacion de ticket

$tablaJoin = 'empresas';
$join .= " INNER JOIN $tablaJoin ON $tabla.empresa = $tablaJoin.id";
$columnas[] = "$tablaJoin.empresa AS 'empresaStr'"; //  Columnas de empresa de creacion de ticket

$tablaJoin = 'usuarios u2';
$join .= " INNER JOIN $tablaJoin ON $tabla.asignado = u2.uid";
$columnas[] = "u2.nombre AS 'nombreAsign', u2.apellido AS 'apellidoAsign', u2.mail AS 'mailAsign', u2.perfil as 'perfil_asign'"; //  Columnas de usuarios de responsable de ticket

$tablaJoin = 'estados';
$join .= " INNER JOIN $tablaJoin ON $tabla.estado = $tablaJoin.id";
$columnas[] = "$tablaJoin.nombre AS 'estadoStr', $tablaJoin.bgcolor, $tablaJoin.txcolor, $tablaJoin.defecto as 'estadoDefecto', $tablaJoin.cierra as 'estadoCierra', $tablaJoin.pausa as 'estadoPausa', $tablaJoin.recid as 'recidEstado'"; //  Columnas de estado de ticket

$tablaJoin = 'prioridad';
$join .= " LEFT JOIN $tablaJoin ON $tabla.prioridad = $tablaJoin.id";
$columnas[] = "$tablaJoin.nombre AS 'prioridadStr'"; //  Columnas de prioridad de ticket

$tablaJoin = 'modulos';
$join .= " LEFT JOIN $tablaJoin ON $tabla.modulo = $tablaJoin.id";
$columnas[] = "$tablaJoin.nombre AS 'modulosStr'"; //  Columnas de modulo de ticket

$tablaJoin = 'proyectos';
$join .= " LEFT JOIN $tablaJoin ON $tabla.proyecto = $tablaJoin.id";
$columnas[] = "$tablaJoin.nombre AS 'proyectoStr', $tablaJoin.descripcion AS 'proyectoDesc', $tablaJoin.comentarios AS 'proyectoComent'"; //  Columnas de proyecto asociado al ticket

$columnas = implode(',', $columnas);
$query = "SELECT $columnas FROM $tabla $join WHERE $tabla.id > 0";
$queryCount = "SELECT count(1) as 'count' FROM $tabla $join WHERE $tabla.id > 0";

if ($wc) {
    $query .= $wc;
    $queryCount .= $wc;
}

$stmtCount = $dbApiQuery($queryCount)[0]['count'] ?? '';
$query .= " ORDER BY $orderBy";
$query .= " LIMIT " . $start . " ," . $length . " ";

// print_r($query).exit;

$stmt = $dbApiQuery($query) ?? '';

foreach ($stmt as $v) {
    $proyecto = array();
    if ($v['proyecto']) {
        $proyecto = array(
            "id"   => intval($v['proyecto']),
            "str"  => trim($v['proyectoStr']),
            "desc" => trim($v['proyectoDesc']),
            // "come" => trim($v['proyectoComent']),
        );
    }
    $usuario = array();
    if ($v['usuario']) {
        $usuario = array(
            "id"     => intval($v['usuario']),
            "str"    => trim($v['nombre'] . ' ' . $v['apellido']),
            "mail"   => trim($v['mail']),
            "perfil" => intval($v['perfil_user']),
        );
        if ($dp['header']) {
            $usuarioHeader = array(
                "id"     => intval($v['usuario']),
                "str"    => trim($v['nombre'] . ' ' . $v['apellido']),
            );
        }
    }
    $modulo = array();
    if ($v['modulo']) {
        $modulo = array(
            "id"    => intval($v['modulo']),
            "str"   => trim($v['modulosStr']),
        );
    }
    $empresa = array();
    if ($v['empresa']) {
        $empresa = array(
            "id"  => intval($v['empresa']),
            "str" => ($v['empresaStr']),
        );
    }
    $asignado = array();
    if ($v['asignado']) {
        $asignado = array(
            "id"     => intval($v['asignado']),
            "str"    => trim($v['nombreAsign'] . ' ' . $v['apellidoAsign']),
            "mail"   => ($v['mailAsign']),
            "perfil" => intval($v['perfil_asign']),
        );
        if ($dp['header']) {
            $asignadoHeader = array(
                "id"  => intval($v['asignado']),
                "str" => trim($v['nombreAsign'] . ' ' . $v['apellidoAsign']),
            );
        }
    }
    $prioridad = array();
    if ($v['prioridad']) {
        $prioridad = array(
            "id"    => intval($v['prioridad']),
            "str"   => trim($v['prioridadStr']),
        );
    }
    $estado = array();
    if ($v['estado']) {
        $estado = array(
            "id"      => intval($v['estado']),
            "str"     => trim($v['estadoStr']),
            "bg"      => trim('#' . $v['bgcolor']),
            "tx"      => trim('#' . $v['txcolor']),
            "defecto" => intval($v['estadoDefecto']),
            "cierra"  => intval($v['estadoCierra']),
            "pausa"   => intval($v['estadoPausa']),
            "recid"   => ($v['recidEstado']),
        );
        if ($dp['header']) {
            $estadoHeader = array(
                "id"      => intval($v['estado']),
                "str"     => trim($v['estadoStr']),
                "bg"      => trim('#' . $v['bgcolor']),
                "tx"      => trim('#' . $v['txcolor']),
            );
        }
    }
    $EdadStr = calculaEdadStr($v['fecha']);
    $EdadStrC = '';
    if ($v['fecha_cierre'] != '0000-00-00 00:00:00') {
        $EdadStrC = calculaEdadStr($v['fecha_cierre']);
    }
    $Duracion = '';
    if ($v['fecha_cierre'] != '0000-00-00 00:00:00') {
        $Duracion = calculaEdadStrDiff($v['fecha'], $v['fecha_cierre']);
    }
    $EdadModC = '';
    if ($v['fecha_mod'] != '0000-00-00 00:00:00') {
        $EdadModC = calculaEdadStr($v['fecha_mod']);
    }
    if ($dp['header']) {
        $data[] = array(
            "ID"     => intval($v['id']), // numero de ticket
            "Recid"  => $v['recid'], // recid del ticket
            "Refer"  => trim($v['referencia']), // referencia del ticket
            "Fecha"  => $v['fecha'], // fecha de carga del ticket
            "User"   => $usuarioHeader,
            "Asign"  => $asignadoHeader,
            "esta"   => $estadoHeader,
            "Prior" => $prioridad, // prioridad del ticket
            "cierra" => intval($v['estadoCierra']),
            "pausa"  => intval($v['estadoPausa']),
            "FechM"  => $v['fecha_mod'], // lastupdate del ticket
            "FechC"  => $v['fecha_cierre'], // fecha de cierre del ticket
            "EdadM"  => $EdadModC, // Edad desde fecha de cierre del ticket
            "EdadC"  => $EdadStrC, // Edad desde fecha de cierre del ticket
            "Tiempo" => $Duracion, // duración del ticket
        );
    } else {
        $data[] = array(
            "ID"    => intval($v['id']), // numero de ticket
            "Recid" => $v['recid'], // recid del ticket
            "Refer" => trim($v['referencia']), // referencia del ticket
            "Fecha" => $v['fecha'], // fecha de carga del ticket
            "Edad" => $EdadStr, // fecha de carga del ticket
            "User"  => $usuario, // usuario de creacion del ticket
            "Empre" => $empresa, // empresa de creacion del ticket
            "Asign" => $asignado, // responsable asignado del ticket
            "Estad" => $estado, // estado del ticket
            "Prior" => $prioridad, // prioridad del ticket
            "Modul" => $modulo, // modulo cargado del ticket
            "Proye" => $proyecto, // proyecto asignado del ticket
            "Tipo"  => intval($v['tipo']), // tipo de ticket
            "Respo" => intval($v['respuesta']), // estado de respuesta del ticket 0 = por responder; 1 = eserando respuesta
            "FechM" => $v['fecha_mod'], // lastupdate del ticket
            "FechC" => $v['fecha_cierre'], // fecha de cierre del ticket
            "EdadM" => $EdadModC, // Edad desde fecha de cierre del ticket
            "EdadC" => $EdadStrC, // Edad desde fecha de cierre del ticket
            "Tiempo" => $Duracion, // duración del ticket
        );
    }
}

if (empty($stmt)) {
    http_response_code(200);
    (response('', 0, 'OK', 200, $time_start, 0, 0));
    exit;
}
$countData    = count($data);
http_response_code(200);
(response($data, $stmtCount, 'OK', 200, $time_start, $countData, 0));
exit;
