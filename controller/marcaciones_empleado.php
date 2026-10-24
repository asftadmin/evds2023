<?php
ob_start();
ini_set('display_errors', '0');
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/MarcacionesEmpleado.php';
require_once __DIR__ . '/curl.php';

function marcaciones_responder($datos, $estado = 200)
{
    if (ob_get_length()) ob_clean();
    http_response_code($estado);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $usuario_id = isset($_SESSION['user_id']) ? filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT) : false;
    if (!$usuario_id || $usuario_id <= 0) {
        marcaciones_responder(array('success' => false, 'message' => 'Debe iniciar sesión.'), 401);
    }
    if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
        marcaciones_responder(array('success' => false, 'message' => 'Utilice GET para consultar sus marcaciones.'), 405);
    }
    foreach (array('empleado_id', 'empleado', 'documento', 'emp_code', 'user_id') as $campo) {
        if (isset($_GET[$campo])) throw new InvalidArgumentException('La consulta solo permite las marcaciones del usuario autenticado.');
    }
    $fechas = array();
    $zona = new DateTimeZone('America/Bogota');
    foreach (array('desde', 'hasta') as $campo) {
        $valor = isset($_GET[$campo]) && is_string($_GET[$campo]) ? $_GET[$campo] : '';
        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $valor, $zona);
        if (!$fecha || $fecha->format('Y-m-d') !== $valor) throw new InvalidArgumentException('Seleccione un periodo válido.');
        $fechas[$campo] = $fecha;
    }
    if ($fechas['desde'] > $fechas['hasta'] || (int) $fechas['desde']->diff($fechas['hasta'])->days > 61) {
        throw new InvalidArgumentException('El periodo debe estar ordenado y no superar 62 días.');
    }
    $operacion = isset($_GET['op']) && is_string($_GET['op']) ? $_GET['op'] : 'consultar';
    if (!in_array($operacion, array('consultar', 'pdf'), true)) throw new InvalidArgumentException('Operación no válida.');
    $modelo = new MarcacionesEmpleado();
    if ($operacion === 'pdf') {
        $snapshot = $modelo->preparar_pdf($usuario_id, $fechas['desde']->format('Y-m-d'), $fechas['hasta']->format('Y-m-d'));
        require_once __DIR__ . '/../view/PDF/jornadas_formato.php';
        $pdf = je_crear_pdf(array($snapshot), array('jlot_nombre' => 'Mis marcaciones BioTime'));
        $contenido = $pdf->Output('', 'S');
        marcaciones_responder(array('success' => true, 'data' => array(
            'archivo' => 'GH-F-19_BioTime_' . $fechas['desde']->format('Ymd') . '_' . $fechas['hasta']->format('Ymd') . '.pdf',
            'pdf_base64' => base64_encode($contenido)
        )));
    }
    $datos = $modelo->consultar_propias($usuario_id, $fechas['desde']->format('Y-m-d'), $fechas['hasta']->format('Y-m-d'));
    marcaciones_responder(array('success' => true, 'data' => $datos));
} catch (InvalidArgumentException $e) {
    marcaciones_responder(array('success' => false, 'message' => $e->getMessage()), 422);
} catch (DomainException $e) {
    marcaciones_responder(array('success' => false, 'message' => $e->getMessage()), 403);
} catch (PDOException $e) {
    error_log('Marcaciones propias PostgreSQL: ' . $e->getMessage());
    marcaciones_responder(array('success' => false, 'message' => 'No fue posible validar su empleado.'), 500);
} catch (RuntimeException $e) {
    marcaciones_responder(array('success' => false, 'message' => $e->getMessage()), 503);
} catch (Throwable $e) {
    error_log('Marcaciones propias BioTime: ' . $e->getMessage());
    marcaciones_responder(array('success' => false, 'message' => 'No fue posible consultar sus marcaciones.'), 500);
}
