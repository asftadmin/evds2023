<?php
// Prueba aislada: no conecta a PostgreSQL ni consulta BioTime.
class Conectar {}
require_once __DIR__ . '/../models/JornadaCruceBiotime.php';
function verificarCruce($condicion, $mensaje) {
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
    echo "OK: $mensaje\n";
}
$fila = [
    'jrd_id' => 1, 'jrf_id' => 1, 'jornada_id' => 1, 'empleado_id' => 1,
    'empleado' => 'Prueba', 'documento' => '123', 'jrd_jornada_version' => 1,
    'jornada_version' => 2, 'jornada_inicio' => '2026-09-01 08:00:00',
    'jornada_fin' => '2026-09-01 17:00:00'
];
$fila['jrd_snapshot'] = json_encode([
    'jornada_inicio' => $fila['jornada_inicio'], 'jornada_fin' => $fila['jornada_fin'],
    'jornada_ubicacion' => 'Sede principal'
]);
function marcaCruce($hora, $fecha = '2026-09-01', $documento = '123') {
    return (object)['emp_code' => $documento, 'att_date' => $fecha, 'punch_time' => $hora];
}
$marcas = [marcaCruce('08:00:00'), marcaCruce('17:00:00')];
$resultado = JornadaCruceBiotime::comparar([$fila], $marcas, 0)[0];
verificarCruce($resultado['resultado'] === 'Horario correcto', 'La versión incrementada al firmar no altera la coincidencia');
verificarCruce(JornadaCruceBiotime::comparar([$fila], [], 0)[0]['resultado'] === 'Sin marcaciones', 'Consulta completa sin marcaciones');
verificarCruce(JornadaCruceBiotime::comparar([$fila], [$marcas[0]], 0)[0]['resultado'] === 'Marcación incompleta', 'Una marcación no crea un par');
$demora = [marcaCruce('08:04:00'), marcaCruce('17:00:00')];
verificarCruce(JornadaCruceBiotime::comparar([$fila], $demora, 0)[0]['resultado'] === 'Diferencia de horario', 'Detecta diferencia de entrada');
verificarCruce(JornadaCruceBiotime::comparar([$fila], $demora, 5)[0]['resultado'] === 'Horario correcto', 'Aplica tolerancia solo al cruce');
verificarCruce(JornadaCruceBiotime::comparar([$fila], array_merge($marcas, [$marcas[0]]), 0)[0]['resultado'] === 'Horario correcto', 'Deduplica marcas idénticas');
verificarCruce(JornadaCruceBiotime::comparar([$fila], array_merge($marcas, [marcaCruce('12:00:00')]), 0)[0]['resultado'] === 'Revisión de marcaciones', 'Conserva marcaciones sobrantes para revisión sin inventar pares');
$cambiada = $fila;
$cambiada['jornada_inicio'] = '2026-09-01 09:00:00';
$r = JornadaCruceBiotime::comparar([$cambiada], $marcas, 0)[0];
verificarCruce($r['resultado'] === 'Jornada modificada' && $r['inicio'] === $fila['jornada_inicio'], 'Conserva el horario firmado y avisa si cambió la jornada');
$otra = $fila;
$otra['jrd_id'] = 2;
verificarCruce(JornadaCruceBiotime::comparar([$fila, $otra], $marcas, 0)[0]['resultado'] === 'Revisión de marcaciones', 'Evita reutilizar automáticamente marcas entre reportes');
$noche = $fila;
$noche['jornada_fin'] = '2026-09-02 05:00:00';
$noche['jrd_snapshot'] = json_encode(['jornada_inicio' => $fila['jornada_inicio'], 'jornada_fin' => $noche['jornada_fin']]);
verificarCruce(JornadaCruceBiotime::comparar([$noche], [$marcas[0], marcaCruce('05:00:00', '2026-09-02')], 0)[0]['resultado'] === 'Horario correcto', 'Asocia la salida del siguiente día');
verificarCruce(JornadaCruceBiotime::comparar([$fila], [marcaCruce('08:00:00', '2026-09-01', '999')], 0)[0]['resultado'] === 'Sin marcaciones', 'No mezcla documentos');

// Casos de asociación, independientes de la tolerancia y de la lógica contable.
function filaCruce($base, $id, $inicio, $fin) {
    $base['jrd_id'] = $base['jornada_id'] = $id;
    $base['jornada_inicio'] = $inicio;
    $base['jornada_fin'] = $fin;
    $base['jrd_snapshot'] = json_encode(['jornada_inicio' => $inicio, 'jornada_fin' => $fin]);
    return $base;
}
$primera = filaCruce($fila, 1, '2026-09-01 08:00:00', '2026-09-01 12:00:00');
$segunda = filaCruce($fila, 2, '2026-09-01 16:00:00', '2026-09-01 22:00:00');
$dobles = [marcaCruce('07:57:00'), marcaCruce('12:04:00'), marcaCruce('15:55:00'), marcaCruce('22:03:00')];
$r = JornadaCruceBiotime::comparar([$segunda, $primera], $dobles, 5);
verificarCruce($r[0]['entrada_bio'] === '2026-09-01 07:57:00' && $r[0]['salida_bio'] === '2026-09-01 12:04:00'
    && $r[1]['entrada_bio'] === '2026-09-01 15:55:00' && $r[1]['salida_bio'] === '2026-09-01 22:03:00', 'Asocia dos jornadas del mismo día sin reutilizar evidencia');
verificarCruce($r === JornadaCruceBiotime::comparar([$primera, $segunda], array_reverse($dobles), 5), 'Resultado determinístico con entradas desordenadas');
$rSinTolerancia = JornadaCruceBiotime::comparar([$primera, $segunda], $dobles, 0);
verificarCruce($rSinTolerancia[0]['entrada_bio'] === $r[0]['entrada_bio']
    && $rSinTolerancia[1]['salida_bio'] === $r[1]['salida_bio'], 'La tolerancia no cambia la asociación');
$nocturna = filaCruce($fila, 3, '2026-09-19 22:00:00', '2026-09-20 07:00:00');
$r = JornadaCruceBiotime::comparar([$nocturna], [marcaCruce('21:58:00', '2026-09-19'), marcaCruce('07:03:00', '2026-09-20')], 5)[0];
verificarCruce($r['resultado'] === 'Horario correcto' && $r['diferencia_entrada'] === -2.0 && $r['diferencia_salida'] === 3.0, 'Turno nocturno del ejemplo');
$larga = filaCruce($fila, 4, '2026-09-01 06:00:00', '2026-09-01 19:00:00');
$r = JornadaCruceBiotime::comparar([$larga], [marcaCruce('05:55:00')], 5)[0];
verificarCruce($r['resultado'] === 'Marcación incompleta' && $r['diferencia_entrada'] === -5.0
    && $r['salida_bio'] === '' && $r['diferencia_salida'] === null, 'Una sola entrada conserva la diferencia sin inventar salida');
$r = JornadaCruceBiotime::comparar([$larga], [marcaCruce('19:03:00')], 5)[0];
verificarCruce($r['resultado'] === 'Marcación incompleta' && $r['entrada_bio'] === ''
    && $r['diferencia_salida'] === 3.0, 'Una sola marcación próxima a la salida se asigna como salida');
$r = JornadaCruceBiotime::comparar([$fila], [marcaCruce('07:58:00'), marcaCruce('08:02:00'), marcaCruce('17:00:00')], 5)[0];
verificarCruce($r['resultado'] === 'Revisión de marcaciones' && count($r['marcaciones']) === 3
    && $r['entrada_bio'] === '', 'Empate entre candidatas no inventa asociación');
$adelantada = filaCruce($fila, 5, '2026-09-01 03:00:00', '2026-09-01 16:00:00');
$r = JornadaCruceBiotime::comparar([$adelantada], [marcaCruce('03:30:00'), marcaCruce('16:00:00')], 5)[0];
verificarCruce($r['resultado'] === 'Diferencia de horario' && $r['diferencia_entrada'] === 30.0
    && $r['entrada_bio'] === '2026-09-01 03:30:00', 'Presenta horario evidenciado con diferencia de treinta minutos');

// La consulta externa conserva los parámetros y nunca interpreta un error como ausencia.
// Regresión del caso real: ambas marcas se usan aunque excedan ampliamente el horario reportado.
$real = filaCruce($fila, 6, '2026-08-27 08:00:00', '2026-08-27 13:00:00');
$evidenciasReales = [marcaCruce('04:17', '2026-08-27'), marcaCruce('20:31', '2026-08-27')];
$r = JornadaCruceBiotime::comparar([$real], $evidenciasReales, 0)[0];
verificarCruce($r['entrada_bio'] === '2026-08-27 04:17:00' && $r['salida_bio'] === '2026-08-27 20:31:00'
    && $r['diferencia_entrada'] === -223.0 && $r['diferencia_salida'] === 451.0
    && $r['resultado'] === 'Diferencia de horario', 'Caso real 04:17–20:31 conserva ambas marcaciones: -223 y +451 minutos');
$rTolerancia = JornadaCruceBiotime::comparar([$real], $evidenciasReales, 60)[0];
verificarCruce($rTolerancia['entrada_bio'] === $r['entrada_bio'] && $rTolerancia['salida_bio'] === $r['salida_bio'], 'Cambiar tolerancia no excluye evidencias lejanas');
$r = JornadaCruceBiotime::comparar([$primera], [marcaCruce('18:00'), marcaCruce('23:00')], 0)[0];
verificarCruce($r['entrada_bio'] === '2026-09-01 18:00:00' && $r['salida_bio'] === '2026-09-01 23:00:00'
    && $r['resultado'] === 'Diferencia de horario', 'Dos marcas del mismo día forman el par aunque ambas estén más cerca de la salida');
$r = JornadaCruceBiotime::comparar([$primera, $segunda], array_slice($dobles, 0, 3), 5);
verificarCruce($r[0]['resultado'] === 'Revisión de marcaciones' && $r[1]['resultado'] === 'Revisión de marcaciones'
    && count($r[0]['marcaciones']) === 3 && $r[0]['entrada_bio'] === '', 'Cantidad impar en varias jornadas requiere revisión de todas las evidencias');
$r = JornadaCruceBiotime::comparar([$primera, $segunda], array_merge($dobles, [marcaCruce('23:30')]), 5);
verificarCruce($r[0]['resultado'] === 'Revisión de marcaciones' && count($r[1]['marcaciones']) === 5, 'No descarta marcas sobrantes para fabricar pares');
$diaSiguiente = filaCruce($fila, 7, '2026-09-02 08:00:00', '2026-09-02 12:00:00');
$r = JornadaCruceBiotime::comparar([$primera, $diaSiguiente], [marcaCruce('08:00'), marcaCruce('12:00')], 0);
verificarCruce($r[0]['resultado'] === 'Horario correcto' && $r[1]['resultado'] === 'Sin marcaciones', 'No traslada evidencias de un día a otra jornada diurna');
$r = JornadaCruceBiotime::comparar([$primera], $dobles, 5)[0];
verificarCruce($r['resultado'] === 'Revisión de marcaciones' && count($r['marcaciones']) === 4, 'Una jornada con cuatro marcas conserva todas para revisión');

// El transporte existente se verifica con respuestas simuladas, sin acceso de red.
class CurlController {
    public static $respuesta;
    public static $parametros;
    public static function requestBiotime($url, $metodo) {
        parse_str($url, self::$parametros);
        return self::$respuesta;
    }
}
$modeloCruce = new JornadaCruceBiotime();
CurlController::$respuesta = (object)['data' => [], 'count' => 0];
verificarCruce($modeloCruce->marcaciones('2026-09-01', '2026-09-02') === []
    && CurlController::$parametros['departments'] === '1'
    && CurlController::$parametros['areas'] === '2', 'Conserva departamentos y áreas configurados');
CurlController::$respuesta = null;
$rechazada = false;
try { $modeloCruce->marcaciones('2026-09-01', '2026-09-02'); }
catch (RuntimeException $e) { $rechazada = true; }
verificarCruce($rechazada, 'Fallo BioTime genera error y no ausencia');
