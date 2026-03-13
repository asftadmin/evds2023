<?php

require_once("../config/conexion.php");
require_once("../models/Empleados.php");
require_once("../models/Bonificaciones.php");
require_once("curl.php");
require_once("../config/helpers.php");

$bonificacion = new Bonificaciones();

switch ($_REQUEST["op"]) {

    case 'listarBonificaciones':
        $empleado_id = $_POST["empleado_id"];
        $datos = $bonificacion->get_bonificaciones($empleado_id);
        echo json_encode($datos);
        break;

    case 'guardarBonificacion':
        $empleado_id = $_POST["empleado_id"];
        $concepto    = $_POST["txt_concepto"]      ?? null;
        $valor       = $_POST["txt_valor"]         ?? null;
        $periocidad  = $_POST["select_periocidad"] != 'NULL' ? $_POST["select_periocidad"] : null;
        $fecha_ini   = convertirFecha($_POST["txt_fecha_inicio"] ?? '');
        $observ      = !empty($_POST["txt_observaciones"]) ? $_POST["txt_observaciones"] : null;

        if (empty($concepto) || empty($valor)) {
            echo json_encode(['success' => false, 'error' => 'Concepto y valor son obligatorios']);
            break;
        }

        // ── Limpiar valor en dos pasos ──
        $valor = $_POST["txt_valor"] ?? '0';
        //var_dump('ANTES: ' . $valor);

        $valor = preg_replace('/[^0-9.]/', '', $valor);
        //var_dump('DESPUES str1: ' . $valor);

        //exit;


        $resultado = $bonificacion->insertar_bonificacion($empleado_id, $concepto, $valor, $periocidad, $fecha_ini, $observ);
        echo json_encode([
            'success' => $resultado,
            'error'   => $resultado ? null : 'No se pudo guardar la bonificación'
        ]);
        break;

    case 'actualizarBonificacion':
        $bonif_id   = $_POST["bonif_id"];
        $concepto   = $_POST["txt_concepto"]      ?? null;
        $valor      = $_POST["txt_valor"]         ?? null;
        $periocidad = $_POST["select_periocidad"] != 'NULL' ? $_POST["select_periocidad"] : null;
        $fecha_ini  = convertirFecha($_POST["txt_fecha_inicio"] ?? '');
        $observ     = !empty($_POST["txt_observaciones"]) ? $_POST["txt_observaciones"] : null;
        $estado     = $_POST["bonif_estado"] ?? 1;

        $valor = str_replace(['.', ','], ['', '.'], $valor);

        $resultado = $bonificacion->update_bonificacion($bonif_id, $concepto, $valor, $periocidad, $fecha_ini, $observ, $estado);
        echo json_encode([
            'success' => $resultado,
            'error'   => $resultado ? null : 'No se pudo actualizar la bonificación'
        ]);
        break;

    case 'eliminarBonificacion':
        $bonif_id  = $_POST["bonif_id"];
        $resultado = $bonificacion->eliminar_bonificacion($bonif_id);
        echo json_encode([
            'success' => $resultado,
            'error'   => $resultado ? null : 'No se pudo eliminar la bonificación'
        ]);
        break;
}
