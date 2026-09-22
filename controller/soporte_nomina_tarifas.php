<?php

require_once __DIR__ . '/soporte_nomina_http.php';
require_once __DIR__ . '/../models/SoporteNominaTarifas.php';
$tarifas = new SoporteNominaTarifas();

// Encauza el CRUD sin SQL ni eliminación física en el controlador.
switch ($_GET['op'] ?? '') {
    case 'listar':
        responderJson(['success' => true, 'data' => $tarifas->listarTarifas()]);
        break;
    case 'mostrar':
        $id = filter_var($_POST['tarifa_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$id || !($tarifa = $tarifas->mostrarTarifa($id))) {
            throw new InvalidArgumentException('La configuración no existe.');
        }
        responderJson(['success' => true, 'data' => $tarifa]);
        break;
    case 'guardar':
    case 'actualizar':
        validarEscrituraSoporte();
        $anio = $_POST['tarifa_anio'] ?? '';
        $mes = $_POST['tarifa_mes'] ?? '';
        $alimentacion = $_POST['tarifa_alimentacion'] ?? '';
        $hospedaje = $_POST['tarifa_hospedaje'] ?? '';
        SoporteNominaTarifas::validarDatos($anio, $mes, $alimentacion, $hospedaje);
        $id = 0;
        if ($_GET['op'] === 'actualizar') {
            $id = filter_var($_POST['tarifa_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if (!$id) {
                throw new InvalidArgumentException('Identificador de tarifa inválido.');
            }
        }
        if ($tarifas->existePeriodo($anio, $mes, $id)) {
            responderJson(['success' => false, 'mensaje' => 'Ya existe una configuración para este periodo.'], 409);
        }
        if ($id) {
            $tarifas->actualizarTarifa($id, $anio, $mes, $alimentacion, $hospedaje);
        } else {
            $id = $tarifas->guardarTarifa($anio, $mes, $alimentacion, $hospedaje);
        }
        responderJson(['success' => true, 'tarifa_id' => $id, 'mensaje' => 'Configuración guardada correctamente.']);
        break;
    default:
        responderJson(['success' => false, 'mensaje' => 'Operación no válida.'], 400);
}
