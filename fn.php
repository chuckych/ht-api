<?php
// ini_set('memory_limit', '500M');
header("Content-Type: application/json");
header('Access-Control-Allow-Origin: *');
$time_start = timeStart(); // Inicio
$pathLog  = __DIR__ . '/logs/'; // path de Logs Api
cleanFile($pathLog, 1, '.log'); // Elimina logs de los ultimos 7 días.
$iniData = (getIni(__DIR__ . '/data.php'));
// print_r($iniData).exit;
$_SERVER['HTTP_TOKEN'] = $_SERVER['HTTP_TOKEN'] ?? '';
$dataC = checkToken($_SERVER['HTTP_TOKEN'], $iniData); // valida el token

/**
 * Datos de la cuenta
 */
$dataCompany  = array(
    'host'     => $dataC['DBHost'],
    'user'     => $dataC['DBUser'],
    'pass'     => $dataC['DBPass'],
    'db'       => $dataC['DBName'],
    'homehost' => $dataC['host'],
);

// print_r($dataCompany).exit;
/**
 * Devuelve valores separados por @separator de un array
 * @array {array} array de datos
 * @key {string} key a procesar
 * @separator {string} separador del valor
 */
function implodeArrayByKey(array $array, $key, $separator = ',')
{
    if ($array && $key) {
        $i = array_unique(array_column($array, $key));
        $i = implode("$separator", $i);
        return $i;
    }
    return false;
}
/**
 * convierte decimales en horas
 * @dec {float} numero decimal
 */
function decimalToTime($dec)
{
    // start by converting to seconds
    $s = ($dec * 3600);
    // we're given hours, so let's get those the easy way
    $h = floor($dec);
    // since we've "calculated" hours, let's remove them from the seconds variable
    $s -= $h * 3600;
    // calculate minutes left
    $m = floor($s / 60);
    // remove those from seconds as well
    $s -= $m * 60;
    // return the time formatted HH:MM:SS
    // return lz($hours).":".lz($minutes).":".lz($seconds);
    return lz($h) . ":" . lz($m);
}
/**
 * @regTipo {int} valor
 */
function filtrarObjetoArr($array, $key, $valor) // Funcion para filtrar un objeto
{
    $a = array();
    if ($array && $key && $valor) {
        foreach ($array as $v) {
            if ($v[$key] === $valor) {
                $a[] = $v;
            }
        }
    }
    return $a;
}
function filtrarObjetoArr2($array, $key, $key2, $valor, $valor2) // Funcion para filtrar un objeto
{
    $a = array();
    if ($array && $key && $key2 && $valor && $valor2) {
        foreach ($array as $v) {
            if ($v[$key] === $valor && $v[$key2] === $valor2) {
                $a[] = $v;
            }
        }
        // $a = array_filter($array, function ($e) use ($key, $key2, $valor, $valor2) {
        //     return $e[$key] === $valor && $e[$key2] === $valor2;
        // });
        // foreach ($a as $key => $x) {
        //     $a[] = $x;
        // }
    }
    return $a;
}
// lz = leading zero
function lz($num)
{
    return (strlen($num) < 2) ? "0{$num}" : $num;
}
/** 
 * @param {String} Zona Horaria. Por defecto America/Argentina/Buenos_Aires
 */
function tz($tz = 'America/Argentina/Buenos_Aires')
{
    return date_default_timezone_set($tz);
}
/**
 * @param {String} Idioma. Por defecto es_ES
 */
function tzLang($tzLang = "es_ES")
{
    return setlocale(LC_TIME, $tzLang);
}
function dateTimeNow()
{
    tz();
    // $t = date("Y-m-d H:i:s");
    $t = explode(" ", microtime());
    $t = date("Y-m-d H:i:s", $t[1]) . substr((string)$t[0], 1, 4);
    return $t;
}
function errorReport()
{
    if ($_SERVER['SERVER_NAME'] == 'localhost') { // Si es localhost
        error_reporting(E_ALL); // Muestra todos los errores
        ini_set('display_errors', '1'); // Muestra todos los errores
    } else {
        error_reporting(E_ALL);
        ini_set('display_errors', '0');
    }
}
/** 
 * @url Ruta del archivo INI de configuracion de cuentas
 */
function getIni($url) // obtiene el json de la url
{
    if (!file_exists($url)) { // Si no existe el archivo
        writeLog("No existe archivo \"$url\"", __DIR__ . "/logs/" . date('Ymd') . "_getIni.log", ''); // escribimos en el log
        return false; // devolvemos false
    }
    $data = file_get_contents($url); // obtenemos el contenido del archivo
    if (!$data) { // si el contenido está vacío
        writeLog("No hay informacion en el archivo \"$url\"", __DIR__ . "/logs/" . date('Ymd') . "_getIni.log", ''); // escribimos en el log
        return false; // devolvemos false
    }
    $data = parse_ini_file($url, true); // Obtenemos los datos del data.php
    return $data; // devolvemos el json
}
/**
 * @token {string} token api
 * @inidata {array} array data
 */
function checkToken($token, $iniData = array())
{
    $data = array();
    if ($iniData) {
        foreach ($iniData as $v) {
            if ($v['Token'] == $token) {
                $data = array(
                    $v
                );
                return $data[0];
                break;
            }
        }
        $r = 'Invalid Token';
        http_response_code(200);
        (response(array(), 0, $r, 200, timeStart(), 0, 0));
        exit;
    } else {
        http_response_code(400);
        (response(array(), 0, 'Required Data Ini', 400, timeStart(), 0, 0));
        exit;
    }
    return false;
}
/**
 * 
 * @data {array} response data
 * @total {int} count data
 * @msg {string} mensaje de respuesta default OK
 * @code {int} http_response_code
 * @tiempoScript {floatval} duración del srcipt, default 0
 * @idCompany {int} id de la cuenta
 */
// $start = start();
// $length = length();
// $response = function ($data = array(), $total = 0, $msg = 'OK', $code = 200, $time_start = 0, $count = 0, $idCompany = 0) use ($start,$length)
function response($data = array(), $total = 0, $msg = 'OK', $code = 200, $time_start = 0, $count = 0, $idCompany = 0)
{
    $code = intval($code);
    $start  = ($code != 400) ? start() : 0;
    $length  = ($code != 400) ? length() : 0;

    $time_end = microtime(true);
    $tiempoScript = number_format($time_end - $time_start, 4);

    $array = array(
        'RESPONSE_CODE' => http_response_code(intval($code)),
        'START'         => intval($start),
        'LENGTH'        => intval($length),
        'TOTAL'         => intval($total),
        'COUNT'         => intval($count),
        'MESSAGE'       => $msg,
        'TIME'          => floatval($tiempoScript),
        // 'REQUEST_URI'   => $_SERVER['REQUEST_URI'],
        'DATA' => $data,
    );

    echo json_encode($array, JSON_PRETTY_PRINT, JSON_ERROR_UTF8);

    $textParams = urldecode($_SERVER['REQUEST_URI']); // convert to string

    $ipAdress = $_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '';
    $agent    = $_SERVER['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $idCompany = $idCompany;

    $pathLog  = __DIR__ . '/logs/'; // path Log Api
    $nameLog  = date('Ymd') . '_request_' . padLeft($idCompany, 3, 0) . '.log'; // path Log Api
    /** start text log*/
    $TextLog = "\n REQUEST  = [ $textParams ]\n RESPONSE = [ RESPONSE_CODE=\"$array[RESPONSE_CODE]\" START=\"$array[START]\" LENGTH=\"$array[LENGTH]\" TOTAL=\"$array[TOTAL]\" COUNT=\"$array[COUNT]\" MESSAGE=\"$array[MESSAGE]\" TIME=\"$array[TIME]\" IP=\"$ipAdress\" AGENT=\"$agent\" ]\n----------";
    /** end text log*/
    writeLog($TextLog, $pathLog . $nameLog); // Log Api
    /** END LOG API CONFIG */
    exit;
}
/** 
 * @path {string} ruta de los archivos a eliminar
 * @dias {int} cantidad de días para atras de los archivos a mantener sin eliminar
 * @ext {string} extensión del archivo a eliminar
 */
function cleanFile($path, $dias, $ext) // borra los archivo a partir de una cantidad de días
{
    $files = glob($path . '*' . $ext); //obtenemos el nombre de todos los ficheros
    if ($files) {
        foreach ($files as $file) { // recorremos todos los ficheros.
            $lastModifiedTime = filemtime($file); // obtenemos la fecha de modificación del fichero
            $currentTime      = time(); // obtenemos la fecha actual
            $dateDiff         = dateDiff(date('Ymd', $lastModifiedTime), date('Ymd', $currentTime)); // obtenemos la diferencia de fechas
            ($dateDiff >= intval($dias)) ? unlink($file) : ''; //elimino el fichero
        }
    }
}
/** 
 * @query {string} query sql obligatorio
 */
$dbApiQuery = function ($query, $count = 0) use ($dataCompany) {
    if (!$query) {
        http_response_code(400);
        (response(array(), 0, 'empty query', 400, timeStart(), 0, $dataCompany['idCompany']));
        exit;
    }
    require __DIR__ . '/connect.php';
    try {
        $stmt = $connpdo->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    } catch (\Throwable $th) { // si hay error en la consulta
        $pathLog = __DIR__ . '/logs/' . date('Ymd') . '_errorDBQuery.log'; // ruta del archivo de Log de errores
        writeLog(PHP_EOL . 'Message: ' . json_encode($th->getMessage(), JSON_UNESCAPED_UNICODE) . PHP_EOL . 'Source: ' . '"' . $_SERVER['REQUEST_URI'] . '"', $pathLog); // escribir en el log de errores el error
    }
    $stmt = null;
    // try {
    //     $resultSet = array();
    //     $stmt = $conn->query($query);
    //     while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    //         $resultSet[] = $r;
    //     }
    //     return $resultSet;
    //     $stmt = null;
    //     $conn = null;
    // } catch (Exception $e) {
    //     $pathLog = __DIR__ . '/logs/' . date('Ymd') . '_errorMSQuery.log'; // ruta del archivo de Log de errores
    //     writeLog(PHP_EOL . 'Message: ' . json_encode($e->getMessage(), JSON_UNESCAPED_UNICODE) . PHP_EOL . 'Source: ' . '"' . $_SERVER['REQUEST_URI'] . '"', $pathLog); // escribir en el log de errores el error
    //     writeLog(PHP_EOL . 'Query: ' . $query, $pathLog); // escribir en el log de errores el error
    //     http_response_code(400);
    //     (response(array(), 0, $e->getMessage(), 400, timeStart(), 0, ''));
    //     exit;
    // }
};
$dbApiQuery2 = function ($query, $count = 0) use ($dataCompany) {
    if (!$query) {
        http_response_code(400);
        (response(array(), 0, 'empty query', 400, timeStart(), 0, $dataCompany['idCompany']));
        exit;
    }
    require __DIR__ . './connect.php';
    try {
        $stmt = $conn->query($query);
        if ($stmt) {
            $stmt = null;
            $conn = null;
            return true;
        } else {
            $stmt = null;
            $conn = null;
            return false;
        }
    } catch (Exception $e) {
        $pathLog = __DIR__ . '/logs/' . date('Ymd') . '_errorMSQuery.log'; // ruta del archivo de Log de errores
        writeLog(PHP_EOL . 'Message: ' . json_encode($e->getMessage(), JSON_UNESCAPED_UNICODE) . PHP_EOL . 'Source: ' . '"' . $_SERVER['REQUEST_URI'] . '"', $pathLog); // escribir en el log de errores el error
        writeLog(PHP_EOL . 'Query: ' . $query, $pathLog); // escribir en el log de errores el error
        http_response_code(400);
        (response(array(), 0, $e->getMessage(), 400, timeStart(), 0, ''));
        exit;
    }
};
/**
 * @text {string} texto del log
 * @path {string} ruta del archivo con su extension
 * @type {string} defecto false, value export = text sin fecha hora
 */
function writeLog($text, $path, $type = false)
{
    $date   = dateTimeNow();
    $text   = ($type == 'export') ? $text . "\n" : $date . ' ' . $text . "\n";
    file_put_contents($path, $text, FILE_APPEND | LOCK_EX);
}
/** 
 * @str {string} valor
 * @lenght {int} cantidad de caracteres
 * @pad {string} delimitador de caracteres, defecto '' (un espacio)
 */
function padLeft($str, $length, $pad = ' ')
{
    if ($str && $length) {
        return str_pad($str, intval($length), $pad, STR_PAD_LEFT);
    } else {
        return false;
    }
}
/**
 * @$date_1 {string} Fecha 1
 * @$date_2 {string} Fecha 2
 * @differenceFormat {string} default '%a'
 */
function dateDiff($date_1, $date_2, $differenceFormat = '%a') // diferencia en días entre dos fechas
{
    if ($date_1 && $date_2) {
        $datetime1 = date_create($date_1); // creo la fecha 1
        $datetime2 = date_create($date_2); // creo la fecha 2
        $interval = date_diff($datetime1, $datetime2); // obtengo la diferencia de fechas
        return $interval->format($differenceFormat); // devuelvo el número de días
    }
    return false;
}
function start()
{
    $p = $_REQUEST;
    $p = file_get_contents("php://input");
    $p = json_decode($p, true);
    $p['start'] = $p['start'] ?? '0';
    $start  = empty($p['start']) ? 0 : $p['start'];
    return intval($start);
}
function length()
{
    $p = $_REQUEST;
    $p = file_get_contents("php://input");
    $p = json_decode($p, true);
    $p['length'] = $p['length'] ?? '';
    $length = empty($p['length']) ? 10 : $p['length'];
    return intval($length);
}
/** 
 * @hora {string} hora en formato HH:MM
 */
function horaMin($hora)
{
    if ($hora) {
        $hora = explode(":", $hora);
        $MinHora = intval($hora[0]) * 60;
        $Min = intval($hora[1] ?? '');
        $Minutos = $MinHora + $Min;
        return $Minutos;
    }
    return false;
}
/**
 * @array {array} matriz para filtrar
 * @key {string} llave de la matriz a filtrar
 * @valor {string} valor de la llave
 */
function filtrarObjeto($array, $key, $valor) // Funcion para filtrar un objeto
{
    if ($array && $key && $valor) {
        $r = array_filter($array, function ($e) use ($key, $valor) {
            return $e[$key] === $valor;
        });
        foreach ($r as $key => $value) {
            return ($value);
        }
    }
    return false;
}
/** 
 * inicio en microsegundos 
 */
function timeStart()
{
    return microtime(true);
}
/**
 * @datetime {string} fecha hora
 * @format {string} default "Y-m-d"
 */
function fechFormat($dateTime, $format = 'Y-m-d')
{
    if ($dateTime) {
        if ($dateTime != '0000-00-00 00:00:00') {
            $x = date_create($dateTime);
            $x  = date_format($x, $format);
            return $x;
        } else {
            return false;
        }
    }
    return false;
}
/**
 * @key {string} parametro a controlar
 * @valor {string} or {int} valor a controlar
 * @type {string} si es string o int
 * @lenght {int} la cantidad maxima de caracteres
 */
function vp($value, $key, $type = 'str', $length = 1)
{
    if ($value) {
        if ($type == 'int') {
            if ($value) {
                if (!is_numeric($value)) {
                    http_response_code(400);
                    (response(array(), 0, "Parametro '$key' de ser {int}. Valor '$value'", 400, timeStart(), 0, 0));
                    exit;
                } else {
                    if (!filter_var($value, FILTER_VALIDATE_INT)) {
                        http_response_code(400);
                        (response(array(), 0, "Parametro '$key' de ser {int}. Valor = '$value'", 400, timeStart(), 0, 0));
                        exit;
                    }
                }
                if (strlen($value) > $length) {
                    http_response_code(400);
                    (response(array(), 0, "Parametro '$key' de ser menor o igual a '$length' caracteres. Valor '$value'", 400, timeStart(), 0, 0));
                    exit;
                }
                if (($value) < 0) {
                    http_response_code(400);
                    (response(array(), 0, "Parametro '$key' de ser mayor o igual a '1'. Valor '$value'", 400, timeStart(), 0, 0));
                    exit;
                }
            }
        }
        if ($type == 'int01') {
            if ($value) {
                switch ($value) {
                    case (!is_numeric($value)):
                        http_response_code(400);
                        (response(array(), 0, "Parametro '$key' debe ser {int}. Valor '$value'", 400, timeStart(), 0, 0));
                        exit;
                        break;
                    case (!filter_var($value, FILTER_VALIDATE_INT)):
                        http_response_code(400);
                        (response(array(), 0, "Parametro '$key' debe ser {int}. Valor = '$value'", 400, timeStart(), 0, 0));
                        exit;
                        break;
                    case (strlen($value) > $length):
                        http_response_code(400);
                        (response(array(), 0, "Parametro '$key' debe ser igual a '$length' caracter. Valor '$value'", 400, timeStart(), 0, 0));
                        exit;
                        break;
                    case (($value) < 0):
                        http_response_code(400);
                        (response(array(), 0, "Parametro '$key' debe ser mayor o igual a '1'. Valor '$value'", 400, timeStart(), 0, 0));
                        exit;
                        break;
                    case (($value) > 1):
                        http_response_code(400);
                        (response(array(), 0, "Parametro '$key' no puede ser mayor '1'. Valor '$value'", 400, timeStart(), 0, 0));
                        exit;
                        break;
                    default:
                        break;
                }
            }
        }
        if ($type == 'intArray') {
            if ($value) {
                if (!is_array($value)) {
                    http_response_code(400);
                    (response(array(), 0, "Parametro '$key' debe ser un {array}. Valor '$value'", 400, timeStart(), 0, 0));
                    exit;
                }
                foreach (array_unique($value) as $v) {
                    if ($v) {
                        if (!is_numeric($v)) {
                            http_response_code(400);
                            (response(array(), 0, "Parametro '$key' de ser {int}. Valor = '$v'", 400, timeStart(), 0, 0));
                            exit;
                        } else {
                            if (!filter_var($v, FILTER_VALIDATE_INT)) {
                                http_response_code(400);
                                (response(array(), 0, "Parametro '$key' de ser {int}. Valor = '$v'", 400, timeStart(), 0, 0));
                                exit;
                            }
                        }
                    }
                    if (($v) < 0) {
                        http_response_code(400);
                        (response(array(), 0, "Parametro '$key' de ser mayor o igual a '0'", 400, timeStart(), 0, 0));
                        exit;
                    }
                    if (strlen($v) > $length) {
                        http_response_code(400);
                        (response(array(), 0, "Parametro '$key' de ser menor o igual a '$length' caracteres. Valor '$v'", 400, timeStart(), 0, 0));
                        exit;
                    }
                }
            }
        }
        if ($type == 'intArrayM8') {
            if ($value) {
                if (!is_array($value)) {
                    http_response_code(400);
                    (response(array(), 0, "Parametro '$key' debe ser un {array}. Valor '$value'", 400, timeStart(), 0, 0));
                    exit;
                }
                foreach ($value as $v) {
                    if ($v) {
                        if (!is_numeric($v)) {
                            http_response_code(400);
                            (response(array(), 0, "Parametro '$key' de ser {int}. Valor = '$v'", 400, timeStart(), 0, 0));
                            exit;
                        } else {
                            if (!filter_var($v, FILTER_VALIDATE_INT)) {
                                http_response_code(400);
                                (response(array(), 0, "Parametro '$key' de ser {int}. Valor = '$v'", 400, timeStart(), 0, 0));
                                exit;
                            }
                        }
                    }
                    if (($v) < 0) {
                        http_response_code(400);
                        (response(array(), 0, "Parametro '$key' de ser mayor o igual a '0'", 400, timeStart(), 0, 0));
                        exit;
                    }
                    if (strlen($v) > $length) {
                        http_response_code(400);
                        (response(array(), 0, "Parametro '$key' de ser menor o igual a '$length' caracteres. Valor '$v'", 400, timeStart(), 0, 0));
                        exit;
                    }
                    if (($v) > 8) {
                        http_response_code(400);
                        (response(array(), 0, "Parametro '$key' de ser menor o igual a '8'", 400, timeStart(), 0, 0));
                        exit;
                    }
                }
            }
        }
        if ($type == 'intArrayM0') { // {int}mayor a 0
            if ($value) {
                if (!is_array($value)) {
                    http_response_code(400);
                    (response(array(), 0, "Parametro '$key' debe ser un {array}. Valor '$value'", 400, timeStart(), 0, 0));
                    exit;
                }
                foreach ($value as $v) {
                    if ($v) {
                        if (!is_numeric($v)) {
                            http_response_code(400);
                            (response(array(), 0, "Parametro '$key' de ser {int}. Valor = '$v'", 400, timeStart(), 0, 0));
                            exit;
                        }
                        if (!filter_var($v, FILTER_VALIDATE_INT)) {
                            http_response_code(400);
                            (response(array(), 0, "Parametro '$key' de ser {int}. Valor = '$v'", 400, timeStart(), 0, 0));
                            exit;
                        }
                        if ($v === 0) {
                            http_response_code(400);
                            (response(array(), 0, "Parametro '$key' de ser mayor a '0'", 400, timeStart(), 0, 0));
                            exit;
                        }
                        if ($v < 0) {
                            http_response_code(400);
                            (response(array(), 0, "Parametro '$key' no debe ser menor a '0'", 400, timeStart(), 0, 0));
                            exit;
                        }
                        if (strlen($v) > $length) {
                            http_response_code(400);
                            (response(array(), 0, "Parametro '$key' de ser menor o igual a '$length'. Valor '$v'", 400, timeStart(), 0, 0));
                            exit;
                        }
                    }
                }
            }
        }
        if ($type == 'numArray01') {
            if ($value) {
                if (!is_array($value)) {
                    http_response_code(400);
                    (response(array(), 0, "Parametro '$key' debe ser un {array}. Valor '$value'", 400, timeStart(), 0, 0));
                    exit;
                }
                foreach ($value as $v) {
                    if ($v) {
                        if (!is_numeric($v)) {
                            http_response_code(400);
                            (response(array(), 0, "Parametro '$key' de ser {int}. Valor = '$v'", 400, timeStart(), 0, 0));
                            exit;
                        }
                        if (($v) < 0) {
                            http_response_code(400);
                            (response(array(), 0, "Parametro '$key' de ser mayor o igual a '0'. Valor = '$v'", 400, timeStart(), 0, 0));
                            exit;
                        }
                        if (($v) > 1) {
                            http_response_code(400);
                            (response(array(), 0, "Parametro '$key' de '0' o '1'. Valor = '$v'", 400, timeStart(), 0, 0));
                            exit;
                        }
                        if (strlen($v) > $length) {
                            http_response_code(400);
                            (response(array(), 0, "Parametro '$key' de ser menor o igual a '$length'.Valor '$v'", 400, timeStart(), 0, 0));
                            exit;
                        }
                    }
                }
            }
        }
        if ($type == 'strArray') {
            if ($value) {
                if (!is_array($value)) {
                    http_response_code(400);
                    (response(array(), 0, "Parametro '$key' debe ser un {array}. Valor '$value'", 400, timeStart(), 0, 0));
                    exit;
                }
                foreach ($value as $v) {
                    if (strlen($v) > $length) {
                        if ($v) {
                            http_response_code(400);
                            (response(array(), 0, "Parametro '$key' de ser menor o igual a '$length'. Valor '$v'", 400, timeStart(), 0, 0));
                            exit;
                        }
                    }
                }
            }
        }
        if ($type == 'strArrayMMlength') {
            if ($value) {
                if (!is_array($value)) {
                    http_response_code(400);
                    (response(array(), 0, "Parametro '$key' debe ser un {array}. Valor '$value'", 400, timeStart(), 0, 0));
                    exit;
                }
                foreach ($value as $v) {
                    if ($v) {
                        if (strlen($v) <> $length) {
                            http_response_code(400);
                            (response(array(), 0, "Parametro '$key' debe contener '$length'. Valor '$v'", 400, timeStart(), 0, 0));
                            exit;
                        }
                    }
                }
            }
        }
        if ($type == 'str') {
            if ($value) {
                if (strlen($value) > $length) {
                    http_response_code(400);
                    (response(array(), 0, "Parametro '$key' de ser menor o igual a '$length' caracteres. Valor '$value", 400, timeStart(), 0, 0));
                    exit;
                }
            }
        }
    }
    return $value;
}
function isValidJSON($str)
{
    json_decode($str);
    return json_last_error() == JSON_ERROR_NONE;
}
function calculaEdad($fecha)
{
    if ($fecha) {
        $dia_actual = date("Y-m-d H:i");
        $edad_diff = date_diff(date_create($fecha), date_create($dia_actual));
        return $edad_diff;
    }
    return '';
}
function calculaDiff($f1, $f2)
{
    if ($f1 && $f2) {
        $edad_diff = date_diff(date_create($f1), date_create($f2));
        return $edad_diff;
    }
    return '';
}
function calculaEdadStr($fecha)
{
    if ($fecha) {
        $Edad = '';
        $anios = intval(calculaEdad(($fecha))->format('%y'));
        $meses = intval(calculaEdad(($fecha))->format('%m'));
        $dias  = intval(calculaEdad(($fecha))->format('%d'));
        $horas = intval(calculaEdad(($fecha))->format('%h'));
        $min   = intval(calculaEdad(($fecha))->format('%i'));

        if ($anios) {
            $Edad  .= ($anios > 1) ? "$anios años " : "$anios año ";
        }
        if ($meses) {
            $Edad  .= ($meses > 1) ? "$meses meses " : "$meses mes ";
        }
        if ($dias) {
            $Edad  .= ($dias > 1) ? "$dias días " : "$dias día ";
        }
        if ($horas) {
            $Edad  .= ($horas > 1) ? $horas . 'h ' : $horas . 'h ';
        }
        if ($min) {
            $Edad  .= ($min > 1) ? $min . 'm' : $min . 'm';
        }
        return trim($Edad);
    }
    return '';
}
function calculaEdadStrDiff($f1, $f2)
{
    if ($f1 && $f2) {
        $Edad = '';
        $anios = intval(calculaDiff($f1, $f2)->format('%y'));
        $meses = intval(calculaDiff($f1, $f2)->format('%m'));
        $dias  = intval(calculaDiff($f1, $f2)->format('%d'));
        $horas = intval(calculaDiff($f1, $f2)->format('%h'));
        $min   = intval(calculaDiff($f1, $f2)->format('%i'));

        if ($anios) {
            $Edad  .= ($anios > 1) ? "$anios años " : "$anios año ";
        }
        if ($meses) {
            $Edad  .= ($meses > 1) ? "$meses meses " : "$meses mes ";
        }
        if ($dias) {
            $Edad  .= ($dias > 1) ? "$dias días " : "$dias día ";
        }
        if ($horas) {
            $Edad  .= ($horas > 1) ? $horas . 'h ' : $horas . 'h ';
        }
        if ($min) {
            $Edad  .= ($min > 1) ? $min . 'm' : $min . 'm';
        }
        return trim($Edad);
    }
    return '';
}
/**
 * @fecha {string} parametro a controlar
 */
function validar_fecha($fecha) // Funcion para validar la fecha
{
    $valores = explode('-', $fecha); // Separo los valores de la fecha
    if (count($valores) === 3) { // Si los bloques de valores son 3 y la fecha es correcta
        $checkdate = checkdate(intval($valores[1]), intval($valores[2]), intval($valores[0])); // Chequeo la fecha
        return $checkdate; // Si no es correcta
    }
    return false; // Si no es correcta
}
function wcFech($f1, $f2, $tabla, $col, $h1, $h2, $time_start)
{
    if ($f1) {

        if (!validar_fecha($f1)) {
            http_response_code(400);
            (response(array(), 0, "Formato de fecha incorrecto. Valor $f1. (Formato valido = yyyy-mm-dd)", 400, $time_start, 0, 0));
            exit;
        }
        if (!validar_fecha($f2)) {
            http_response_code(400);
            (response(array(), 0, "Formato de fecha incorrecto. Valor $f2. (Formato valido = yyyy-mm-dd)", 400, $time_start, 0, 0));
            exit;
        }

        $f11 = intval(str_replace('-', '', $f1));
        $f22 = intval(str_replace('-', '', $f2));
        if ($f11 > $f22) {
            http_response_code(400);
            (response(array(), 0, "$col de inicio mayor a fecha de fin", 400, $time_start, 0, 0));
            exit;
        }
        $f1 .= ($h1) ? " $h1" : '';
        $f2 .= ($h2) ? " $h2" : '';
        return " AND $tabla.$col BETWEEN '$f1' AND '$f2'";
    }
    return '';
}
