<?php

$wc = $orderBy = '';

$dp = ($_REQUEST); // dataPayload
$dp = file_get_contents("php://input");

if (strlen($dp) > 0 && isValidJSON($dp)) {
    $dp = json_decode($dp, true);
} else {
    isValidJSON($dp);
    http_response_code(400);
    (response(array(), 0, 'Invalid json Payload', 400, $time_start, 0, 0));
}

$start  = start();
$length = length();

$dp['order']      = ($dp['order']) ?? [];
$dp['order']      = vp($dp['order'], 'order', 'strArray', 30);
$dp['recid']      = ($dp['recid']) ?? [];
$dp['recid']      = vp($dp['recid'], 'recid', 'strArray', 8);
$dp['id']         = ($dp['id']) ?? [];
$dp['id']         = vp($dp['id'], 'id', 'intArray', 11);
$dp['perfil'] = ($dp['perfil']) ?? [];
$dp['perfil'] = vp($dp['perfil'], 'perfil', 'intArray', 2);
$dp['empresa']    = ($dp['empresa']) ?? [];
$dp['empresa']    = vp($dp['empresa'], 'empresa', 'intArray', 11);
$dp['usuario']   = ($dp['usuario']) ?? [];
$dp['usuario']   = vp($dp['usuario'], 'usuario', 'intArray', 11);
$dp['asignado']   = ($dp['asignado']) ?? [];
$dp['asignado']   = vp($dp['asignado'], 'asignado', 'intArray', 11);
$dp['modulo']     = ($dp['modulo']) ?? [];
$dp['modulo']     = vp($dp['modulo'], 'modulo', 'intArray', 11);
$dp['proyecto']   = ($dp['proyecto']) ?? [];
$dp['proyecto']   = vp($dp['proyecto'], 'proyecto', 'intArray', 11);
$dp['estado']     = ($dp['estado']) ?? [];
$dp['estado']     = vp($dp['estado'], 'estado', 'intArray', 3);
$dp['prioridad']  = ($dp['prioridad']) ?? [];
$dp['prioridad']  = vp($dp['prioridad'], 'prioridad', 'intArray', 3);
$dp['cierra']     = ($dp['cierra']) ?? [];
$dp['cierra']     = vp($dp['cierra'], 'cierra', 'numArray01', 1);
$dp['pausa']      = ($dp['pausa']) ?? [];
$dp['pausa']      = vp($dp['pausa'], 'pausa', 'numArray01', 1);
$dp['respuesta']  = vp($dp['respuesta'], 'respuesta', 'numArray01', 1);
$dp['respuesta']  = ($dp['respuesta']) ?? [];
$dp['referencia'] = ($dp['referencia']) ?? '';
$dp['referencia'] = vp($dp['referencia'], 'referencia', 'str', 100);

$dp['fechaIni']  = ($dp['fechaIni']) ?? '';
$dp['fechaFin']  = ($dp['fechaFin']) ?? date('Y-m-d');

$dp['fechaIniM'] = ($dp['fechaIniM']) ?? '';
$dp['fechaFinM'] = ($dp['fechaFinM']) ?? date('Y-m-d');

$dp['fechaIniC'] = ($dp['fechaIniC']) ?? '';
$dp['fechaFinC'] = ($dp['fechaFinC']) ?? date('Y-m-d');

$dp['fechaIni']  = vp($dp['fechaIni'], 'fechaIni', 'str', 10);
$dp['fechaFin']  = vp($dp['fechaFin'], 'fechaFin', 'str', 10);

$dp['fechaIniM'] = vp($dp['fechaIniM'], 'fechaIniM', 'str', 10);
$dp['fechaFinM'] = vp($dp['fechaFinM'], 'fechaFinM', 'str', 10);

$dp['fechaIniC'] = vp($dp['fechaIniC'], 'fechaIniC', 'str', 10);
$dp['fechaFinC'] = vp($dp['fechaFinC'], 'fechaFinC', 'str', 10);

$dp['desdeDias'] = ($dp['desdeDias']) ?? '';
$dp['desdeDias'] = vp($dp['desdeDias'], 'desdeDias', 'int', 4);

$dp['hastaDias'] = ($dp['hastaDias']) ?? '';
$dp['hastaDias'] = vp($dp['hastaDias'], 'hastaDias', 'int', 4);

$arrDP = array(
    'id'         => $dp['id'], // ID de pedido {int} {array}
    'recid'      => $dp['recid'], // recid de pedido {int} {array}
    'estado'     => $dp['estado'], // ID de pedido {int} {array}
    'prioridad'  => $dp['prioridad'], // ID de prioridad del ticket {int} {array}
    'empresa'    => $dp['empresa'], // ID de empresa de creacion de ticket {int} {array}
    'usuario'    => $dp['usuario'], // ID de usuario ticket {int} {array}
    'asignado'   => $dp['asignado'], // ID de responsable ticket {int} {array}
    'modulo'     => $dp['modulo'], // ID de responsable ticket {int} {array}
    'proyecto'   => $dp['proyecto'], // ID de proyecto asignado al ticket {int} {array}
    'respuesta'  => $dp['respuesta'], // respuesta de ticket 1 = respuesta cliente; 0 = respuesta responsable  {int} {array}
    'referencia' => $dp['referencia'], // referencia de ticket {string array}
);

$arrDPEstado = array(
    'cierra' => $dp['cierra'], // comportamiento de estado 1 = cerrado
    'pausa'  => $dp['pausa'], // comportamiento de estado 1 = pausado
);
$arrDPPerfilAsign = array(
    'perfil' => $dp['perfil'], // perfil asignado
);
$arrOrder = array(
    'order' => $dp['order'], // ORDER BY
);

$join = '';
$tabla = "ticket";

foreach ($arrDP as $key => $p) {
    $e = array();
    if (is_array($p)) {
        $v = '';
        $e = array_filter($p, function ($v) {
            return ($v !== false && !is_null($v) && ($v != '' || $v == '0'));
        });
        $e = array_unique($e);
        if (($e)) {
            if (count($e) > 1) {
                $e = "'" . implode("','", $e) . "'";
                $wc .= " AND $tabla.$key IN ($e)";
            } else {
                foreach ($e as $v) {
                    if ($v !== NULL) {
                        $wc .= " AND $tabla.$key = '$v'";
                    }
                }
            }
        }
    } else {
        if ($p) {
            if ($key == 'pending') {
                switch ($p) {
                    case '1':
                        $wc .= " AND $tabla.Cantidad > 0";
                        break;
                    case '2':
                        $wc .= " AND $tabla.Cantidad = 0";
                        break;
                }
            } else if ($key == 'referencia') {
                $wc .= " AND $tabla.$key LIKE '%$p%'";
            } else {
                $wc .= " AND $tabla.$key = '$p'";
            }
        }
    }
}
$tablaEsta = "estados";
foreach ($arrDPEstado as $key => $p) {
    $e = array();
    if (is_array($p)) {
        $v = '';
        $e = array_filter($p, function ($v) {
            return ($v !== false && !is_null($v) && ($v != '' || $v == '0'));
        });
        $e = array_unique($e);
        if (($e)) {
            if (count($e) > 1) {
                $e = "'" . implode("','", $e) . "'";
                $wc .= " AND $tablaEsta.$key IN ($e)";
            } else {
                foreach ($e as $v) {
                    if ($v !== NULL) {
                        $wc .= " AND $tablaEsta.$key = '$v'";
                    }
                }
            }
        }
    }
}
$tablaPerfilAsign = "u2";
foreach ($arrDPPerfilAsign as $key => $p) {
    $e = array();
    if (is_array($p)) {
        $v = '';
        $e = array_filter($p, function ($v) {
            return ($v !== false && !is_null($v) && ($v != '' || $v == '0'));
        });
        $e = array_unique($e);
        if (($e)) {
            if (count($e) > 1) {
                $e = "'" . implode("','", $e) . "'";
                $wc .= " AND $tablaPerfilAsign.$key IN ($e)";
            } else {
                foreach ($e as $v) {
                    if ($v !== NULL) {
                        $wc .= " AND $tablaPerfilAsign.$key = '$v'";
                    }
                }
            }
        }
    }
}
foreach ($arrOrder as $key => $p) {
    $e = array();
    if (is_array($p)) {
        $v = '';
        $e = array_filter($p, function ($v) {
            return ($v !== false && !is_null($v) && ($v != '' || $v == '0'));
        });
        $e = array_unique($e);
        if (($e)) {
            if (count($e) > 1) {
                $e = implode(",", $e);
                $orderBy .= $e;
            } else {
                foreach ($e as $v) {
                    if ($v !== NULL) {
                        $orderBy .= $v;
                    }
                }
            }
        }
    }
}

$wc .= wcFech($dp['fechaIni'], $dp['fechaFin'], 'ticket','fecha', "00:00", "23:59", $time_start);
$wc .= wcFech($dp['fechaIniM'], $dp['fechaFinM'], 'ticket','fecha_mod', "00:00", "23:59", $time_start);
$wc .= wcFech($dp['fechaIniC'], $dp['fechaFinC'], 'ticket','fecha_cierre', "00:00", "23:59", $time_start);

if ($dp['desdeDias']) {

    $dias        = intval($dp['desdeDias']);
    $date_now    = date('Y-m-d');
    $date_past   = strtotime("- $dias day", strtotime($date_now));
    $date_past   = date('Y-m-d', $date_past);
    $date_past  .= " 00:00";
    $date_now   .= " 23:59";

    $wc .= " AND ticket.fecha BETWEEN '$date_past' AND '$date_now'";
}
if ($dp['hastaDias']) {

    $dias        = intval($dp['hastaDias'])-1;
    $date_now    = date('Y-m-d');
    $date_past   = strtotime("- $dias day", strtotime($date_now));
    $date_past   = date('Y-m-d', $date_past);

    $wc .= " AND ticket.fecha < '$date_past'";
}

// print_r($wc);exit;
$orderBy = ($orderBy) ? $orderBy : 'ticket.id DESC';
