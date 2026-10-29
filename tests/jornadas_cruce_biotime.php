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
verificarCruce(JornadaCruceBiotime::comparar([$fila], array_merge($marcas, [marcaCruce('12:00:00')]), 0)[0]['resultado'] === 'Marcación adicional por revisar', 'Compara los extremos únicos y conserva la marca intermedia para revisión');
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

// Continuidad a medianoche: dos registros diarios y un único par real.
$antesMedianoche = filaCruce($fila, 255, '2026-10-06 20:30:00', '2026-10-07 00:00:00');
$despuesMedianoche = filaCruce($fila, 256, '2026-10-07 00:00:00', '2026-10-07 16:00:00');
$marcasContinuas = [marcaCruce('20:27:00', '2026-10-06'), marcaCruce('16:04:00', '2026-10-07')];
$r = JornadaCruceBiotime::comparar([$despuesMedianoche, $antesMedianoche], $marcasContinuas, 5);
verificarCruce(count($r) === 2 && $r[0]['resultado'] === 'Horario correcto' && $r[1]['resultado'] === 'Horario correcto',
    'Las jornadas 255 y 256 usan un par para el bloque continuo');
verificarCruce($r[0]['entrada_bio'] === '2026-10-06 20:27:00' && $r[0]['diferencia_entrada'] === -3.0
    && $r[1]['salida_bio'] === '2026-10-07 16:04:00' && $r[1]['diferencia_salida'] === 4.0,
    'Asigna diferencias reales únicamente a los extremos del bloque');
verificarCruce($r[0]['salida_bio'] === '' && $r[1]['entrada_bio'] === ''
    && $r[0]['diferencia_salida'] === 0.0 && $r[1]['diferencia_entrada'] === 0.0
    && $r[0]['continuidad_salida'] && $r[1]['continuidad_entrada'],
    'Un corte respaldado por un par válido tiene diferencia cero sin inventar marcas');
verificarCruce($r[0]['fin'] === '2026-10-07 00:00:00' && $r[1]['inicio'] === '2026-10-07 00:00:00'
    && strpos($r[0]['detalle'], '255, 256') !== false, 'Conserva jornadas diarias y explica el bloque');
verificarCruce($r === JornadaCruceBiotime::comparar([$antesMedianoche, $despuesMedianoche], array_reverse($marcasContinuas), 5),
    'La continuidad no depende del orden de entrada');
$sinTolerancia = JornadaCruceBiotime::comparar([$antesMedianoche, $despuesMedianoche], $marcasContinuas, 0);
verificarCruce($sinTolerancia[0]['resultado'] === 'Diferencia de horario' && $sinTolerancia[1]['resultado'] === 'Diferencia de horario',
    'El resultado del bloque aplica tolerancia a los dos extremos reales');
$claveContinuidad = 'prueba-continuidad';
$tokenContinuidad = JornadaCruceBiotime::autorizar_evidencia($r[0], $claveContinuidad, 7);
$rechazoCorte = false;
try { JornadaCruceBiotime::validar_evidencia($tokenContinuidad, 'salida', $claveContinuidad, 7); }
catch (RuntimeException $e) { $rechazoCorte = true; }
verificarCruce($rechazoCorte && JornadaCruceBiotime::validar_evidencia($tokenContinuidad, 'entrada', $claveContinuidad, 7)['entrada'] === $r[0]['entrada_bio'],
    'La evidencia autoriza la entrada real y rechaza ajustes del corte interno');
$r = JornadaCruceBiotime::comparar([$antesMedianoche, $despuesMedianoche], [$marcasContinuas[0]], 5);
verificarCruce($r[0]['resultado'] === 'Marcación incompleta' && $r[1]['resultado'] === 'Marcación incompleta'
    && $r[1]['salida_bio'] === '' && $r[0]['diferencia_salida'] === null && $r[1]['diferencia_entrada'] === null,
    'Una entrada sola no completa el bloque ni respalda diferencias cero');
$r = JornadaCruceBiotime::comparar([$antesMedianoche, $despuesMedianoche], [], 5);
verificarCruce($r[0]['resultado'] === 'Sin marcaciones' && $r[1]['resultado'] === 'Sin marcaciones'
    && $r[0]['diferencia_salida'] === null && $r[1]['diferencia_entrada'] === null,
    'Bloque sin marcas conserva diagnóstico de ausencia de evidencia y diferencias vacías');
$extrasContinuas = array_merge($marcasContinuas, [marcaCruce('08:00:00', '2026-10-07')]);
$r = JornadaCruceBiotime::comparar([$antesMedianoche, $despuesMedianoche], $extrasContinuas, 5);
verificarCruce($r[0]['resultado'] === 'Marcación adicional por revisar' && $r[1]['resultado'] === 'Marcación adicional por revisar'
    && count($r[0]['marcaciones']) === 3 && $r[0]['entrada_bio'] === '2026-10-06 20:27:00'
    && $r[0]['diferencia_salida'] === 0.0 && $r[1]['diferencia_entrada'] === 0.0,
    'Un bloque con extremos únicos conserva la marca adicional y valida su corte');
$otroTurno = filaCruce($fila, 257, '2026-10-07 19:00:00', '2026-10-07 23:00:00');
$r = JornadaCruceBiotime::comparar([$otroTurno, $despuesMedianoche, $antesMedianoche], array_merge($marcasContinuas,
    [marcaCruce('19:00:00', '2026-10-07'), marcaCruce('23:00:00', '2026-10-07')]), 5);
verificarCruce($r[0]['resultado'] === 'Horario correcto' && $r[1]['resultado'] === 'Horario correcto'
    && $r[2]['entrada_bio'] === '2026-10-07 19:00:00', 'Combina un bloque nocturno y otro turno sin reutilizar marcas');
$conHueco = filaCruce($fila, 256, '2026-10-07 00:01:00', '2026-10-07 16:00:00');
$r = JornadaCruceBiotime::comparar([$antesMedianoche, $conHueco], $marcasContinuas, 5);
verificarCruce($r[0]['resultado'] === 'Revisión de marcaciones' && !$r[0]['continuidad_salida'], 'No une jornadas separadas por un hueco');
$r = JornadaCruceBiotime::comparar([$primera, filaCruce($fila, 8, '2026-09-01 12:00:00', '2026-09-01 17:00:00')], $marcas, 0);
verificarCruce($r[0]['resultado'] === 'Horario correcto' && $r[0]['continuidad_salida'] && $r[1]['continuidad_entrada']
    && $r[0]['diferencia_salida'] === 0.0 && $r[1]['diferencia_entrada'] === 0.0,
    'La continuidad también reconoce un corte exacto a mediodía');
$r = JornadaCruceBiotime::comparar([$antesMedianoche, $despuesMedianoche, $despuesMedianoche], $marcasContinuas, 5);
verificarCruce($r[0]['resultado'] === 'Revisión de marcaciones' && !$r[0]['continuidad_salida'], 'Vecinos duplicados impiden inferir continuidad inequívoca');
$tercerDia = filaCruce($fila, 258, '2026-10-08 00:00:00', '2026-10-08 06:00:00');
$diaCompleto = filaCruce($fila, 256, '2026-10-07 00:00:00', '2026-10-08 00:00:00');
$r = JornadaCruceBiotime::comparar([$tercerDia, $diaCompleto, $antesMedianoche],
    [$marcasContinuas[0], marcaCruce('06:00:00', '2026-10-08')], 5);
verificarCruce(count($r) === 3 && $r[1]['continuidad_entrada'] && $r[1]['continuidad_salida']
    && $r[1]['entrada_bio'] === '' && $r[1]['salida_bio'] === '' && $r[2]['resultado'] === 'Horario correcto',
    'Reconoce cadenas de varios cortes diarios sin fabricar evidencia intermedia');

// Caso real: turno diurno y dos jornadas con marcaciones reales junto a medianoche.
$diurnaSeptiembre = filaCruce($fila, 241, '2026-09-24 06:00:00', '2026-09-24 13:30:00');
$nocheSeptiembre = filaCruce($fila, 245, '2026-09-24 21:00:00', '2026-09-25 00:00:00');
$madrugadaSeptiembre = filaCruce($fila, 246, '2026-09-25 00:00:00', '2026-09-25 09:00:00');
$seisMarcas = [marcaCruce('05:32:00', '2026-09-24'), marcaCruce('13:23:00', '2026-09-24'),
    marcaCruce('20:49:00', '2026-09-24'), marcaCruce('23:58:00', '2026-09-24'),
    marcaCruce('00:00:00', '2026-09-25'), marcaCruce('08:58:00', '2026-09-25')];
$tresJornadas = [$diurnaSeptiembre, $nocheSeptiembre, $madrugadaSeptiembre];
$r = JornadaCruceBiotime::comparar($tresJornadas, $seisMarcas, 0);
verificarCruce(array_column($r, 'diferencia_entrada') === [-28.0, -11.0, 0.0]
    && array_column($r, 'diferencia_salida') === [-7.0, -2.0, -2.0], 'Caso 241/245/246: asigna los tres pares reales y sus diferencias');
verificarCruce($r[1]['salida_bio'] === '2026-09-24 23:58:00' && $r[2]['entrada_bio'] === '2026-09-25 00:00:00'
    && !$r[1]['continuidad_salida'] && !$r[2]['continuidad_entrada'], 'Las marcas reales de medianoche prevalecen sobre el corte virtual');
$tokenRealMedianoche = JornadaCruceBiotime::autorizar_evidencia($r[1], $claveContinuidad, 7);
verificarCruce(JornadaCruceBiotime::validar_evidencia($tokenRealMedianoche, 'salida', $claveContinuidad, 7)['salida'] === '2026-09-24 23:58:00',
    'La salida real junto al corte se conserva como evidencia ajustable');
verificarCruce($r === JornadaCruceBiotime::comparar(array_reverse($tresJornadas), array_reverse($seisMarcas), 0),
    'La asociación de pares reales es determinística');
$rTolerancia = JornadaCruceBiotime::comparar($tresJornadas, $seisMarcas, 15);
verificarCruce($rTolerancia[1]['resultado'] === 'Horario correcto' && $rTolerancia[2]['resultado'] === 'Horario correcto'
    && $rTolerancia[1]['salida_bio'] === $r[1]['salida_bio'], 'La tolerancia no cambia la asignación de los pares reales');
$r = JornadaCruceBiotime::comparar($tresJornadas, [$seisMarcas[0], $seisMarcas[1], $seisMarcas[2], $seisMarcas[5]], 0);
verificarCruce($r[0]['diferencia_entrada'] === -28.0 && $r[1]['continuidad_salida'] && $r[2]['continuidad_entrada']
    && $r[1]['diferencia_salida'] === 0.0 && $r[2]['diferencia_entrada'] === 0.0,
    'Sin marcas internas conserva el par diurno y cruza la noche como bloque');

// Un bloque de tres jornadas puede contener un corte real y otro por continuidad.
$mezclaFilas = [$antesMedianoche, $diaCompleto, $tercerDia];
$mezclaMarcas = [marcaCruce('20:27:00', '2026-10-06'), marcaCruce('23:58:00', '2026-10-06'),
    marcaCruce('00:00:00', '2026-10-07'), marcaCruce('06:00:00', '2026-10-08')];
$r = JornadaCruceBiotime::comparar($mezclaFilas, $mezclaMarcas, 5);
verificarCruce(!$r[0]['continuidad_salida'] && !$r[1]['continuidad_entrada']
    && $r[1]['continuidad_salida'] && $r[2]['continuidad_entrada']
    && $r[1]['diferencia_salida'] === 0.0 && $r[2]['diferencia_entrada'] === 0.0,
    'Combina un corte con marcas reales y otro sin marcas en una misma cadena');
$r = JornadaCruceBiotime::comparar([$antesMedianoche, $despuesMedianoche],
    [marcaCruce('01:00:00', '2026-10-07'), marcaCruce('16:00:00', '2026-10-07')], 5);
verificarCruce($r[0]['resultado'] === 'Revisión de marcaciones' && $r[0]['diferencia_salida'] === null,
    'Un par que no abarca medianoche no valida el corte continuo');
$r = JornadaCruceBiotime::comparar($mezclaFilas,
    [marcaCruce('20:27:00', '2026-10-06'), marcaCruce('00:00:00', '2026-10-07'),
     marcaCruce('00:00:00', '2026-10-08'), marcaCruce('06:00:00', '2026-10-08')], 5);
verificarCruce(count(array_filter($r, function ($item) { return $item['resultado'] === 'Horario correcto'; })) === 3
    && $r[0]['limite_compartido_salida'] && $r[1]['limite_compartido_entrada']
    && $r[1]['limite_compartido_salida'] && $r[2]['limite_compartido_entrada']
    && $r[1]['marcaciones_adicionales'] === [],
    'Las marcas exactas en dos cortes respaldan un bloque de tres jornadas sin duplicar eventos');

// Cierre del periodo 16 de septiembre a 15 de octubre, sin jornada siguiente firmada.
$diurnaOctubre = filaCruce($fila, 261, '2026-10-15 01:00:00', '2026-10-15 09:00:00');
$cierreOctubre = filaCruce($fila, 262, '2026-10-15 19:00:00', '2026-10-16 00:00:00');
$marcasCierre = [marcaCruce('00:51:00', '2026-10-15'), marcaCruce('08:50:00', '2026-10-15'),
    marcaCruce('18:57:00', '2026-10-15'), marcaCruce('13:50:00', '2026-10-16')];
$rCierre = JornadaCruceBiotime::comparar([$diurnaOctubre, $cierreOctubre], $marcasCierre, 0, '2026-10-15');
verificarCruce($rCierre[1]['corte_periodo_salida'] && $rCierre[1]['diferencia_salida'] === 0.0
    && $rCierre[1]['diferencia_entrada'] === -3.0 && $rCierre[1]['salida_bio'] === ''
    && $rCierre[1]['salida_bio_real'] === '2026-10-16 13:50:00',
    'El cierre del 15 de octubre corta a medianoche y conserva la salida real del 16');
verificarCruce(!$rCierre[0]['corte_periodo_salida'] && $rCierre[0]['salida_bio'] === '2026-10-15 08:50:00'
    && count($rCierre[1]['marcaciones']) === 4 && strpos($rCierre[1]['detalle'], 'periodo siguiente') !== false,
    'El corte no afecta el turno diurno y explica la continuación sin perder marcas');
$r = JornadaCruceBiotime::comparar([$diurnaOctubre, $cierreOctubre], $marcasCierre, 3, '2026-10-15');
verificarCruce($r[1]['resultado'] === 'Horario correcto', 'La salida del periodo siguiente no provoca diferencia de horario en el periodo cerrado');
verificarCruce($rCierre[1]['resultado'] === 'Diferencia de horario', 'Conserva la diferencia real de entrada aunque exista corte de periodo');
$tokenCierre = JornadaCruceBiotime::autorizar_evidencia($rCierre[1], $claveContinuidad, 7);
$rechazoCierre = false;
try { JornadaCruceBiotime::validar_evidencia($tokenCierre, 'salida', $claveContinuidad, 7); }
catch (RuntimeException $e) { $rechazoCierre = true; }
verificarCruce($rechazoCierre && JornadaCruceBiotime::validar_evidencia($tokenCierre, 'entrada', $claveContinuidad, 7)['entrada'] === '2026-10-15 18:57:00',
    'El corte de periodo no autoriza extender la salida, pero conserva el ajuste de entrada');
$r = JornadaCruceBiotime::comparar([$diurnaOctubre, $cierreOctubre], $marcasCierre, 0, '2026-10-31');
verificarCruce(!$r[1]['corte_periodo_salida'] && $r[1]['diferencia_salida'] === 830.0,
    'No aplica corte de periodo a una medianoche interior del rango');
$continuacionOctubre = filaCruce($fila, 263, '2026-10-16 00:00:00', '2026-10-16 13:30:00');
$r = JornadaCruceBiotime::comparar([$diurnaOctubre, $cierreOctubre, $continuacionOctubre], $marcasCierre, 3, '2026-10-15');
verificarCruce($r[1]['corte_periodo_salida'] && !$r[1]['continuidad_salida'] && $r[1]['resultado'] === 'Horario correcto'
    && $r[1]['salida_bio_real'] === '2026-10-16 13:50:00' && $r[2]['salida_bio'] === '2026-10-16 13:50:00',
    'El corte también funciona con una continuación firmada sin alterar su salida real');
foreach (['23:58:00', '00:00:00'] as $horaSalidaCierre) {
    $diaSalidaCierre = $horaSalidaCierre === '23:58:00' ? '2026-10-15' : '2026-10-16';
    $r = JornadaCruceBiotime::comparar([$cierreOctubre],
        [marcaCruce('18:57:00', '2026-10-15'), marcaCruce($horaSalidaCierre, $diaSalidaCierre)], 0, '2026-10-15');
    verificarCruce(!$r[0]['corte_periodo_salida'] && $r[0]['salida_bio'] !== '',
        'Salida real anterior o exacta al cierre no se sustituye: ' . $horaSalidaCierre);
}
$r = JornadaCruceBiotime::comparar([$cierreOctubre], [marcaCruce('18:57:00', '2026-10-15')], 0, '2026-10-15');
verificarCruce(!$r[0]['corte_periodo_salida'] && $r[0]['diferencia_salida'] === null,
    'Sin par completo no se valida el corte de periodo');
$r = JornadaCruceBiotime::comparar([$cierreOctubre],
    [marcaCruce('18:57:00', '2026-10-15'), marcaCruce('19:03:00', '2026-10-15'), marcaCruce('13:50:00', '2026-10-16')], 0, '2026-10-15');
verificarCruce($r[0]['resultado'] === 'Revisión de marcaciones' && !$r[0]['corte_periodo_salida']
    && $r[0]['diferencia_salida'] === null, 'Un caso ambiguo no obtiene diferencia cero por cerrar el periodo');

// Jornada del 2 de octubre: entrada y salida claras, con una marca intermedia pendiente.
$jornadaAdicional = filaCruce($fila, 280, '2026-10-02 05:54:00', '2026-10-02 15:15:00');
$tresMarcas = [marcaCruce('05:55:00', '2026-10-02'), marcaCruce('08:04:00', '2026-10-02'), marcaCruce('15:17:00', '2026-10-02')];
$rAdicional = JornadaCruceBiotime::comparar([$jornadaAdicional], $tresMarcas, 0)[0];
verificarCruce($rAdicional['entrada_bio'] === '2026-10-02 05:55:00' && $rAdicional['salida_bio'] === '2026-10-02 15:17:00'
    && $rAdicional['diferencia_entrada'] === 1.0 && $rAdicional['diferencia_salida'] === 2.0,
    'El 2 de octubre compara 05:55 y 15:17 con diferencias de uno y dos minutos');
verificarCruce($rAdicional['resultado'] === 'Marcación adicional por revisar'
    && $rAdicional['marcaciones_adicionales'] === ['2026-10-02 08:04:00'] && count($rAdicional['marcaciones']) === 3,
    'Identifica las 08:04 como adicional y conserva las tres marcas');
verificarCruce($rAdicional === JornadaCruceBiotime::comparar([$jornadaAdicional], array_reverse($tresMarcas), 0)[0],
    'Las marcaciones adicionales no dependen del orden recibido');
$r = JornadaCruceBiotime::comparar([$jornadaAdicional], $tresMarcas, 5)[0];
verificarCruce($r['resultado'] === 'Marcación adicional por revisar' && $r['entrada_bio'] === $rAdicional['entrada_bio']
    && strpos($r['detalle'], 'Horario correcto') !== false, 'La tolerancia no oculta la marca adicional ni cambia la asociación');
$tokenAdicional = JornadaCruceBiotime::autorizar_evidencia($rAdicional, $claveContinuidad, 7);
verificarCruce(JornadaCruceBiotime::validar_evidencia($tokenAdicional, 'entrada', $claveContinuidad, 7)['entrada'] === $rAdicional['entrada_bio']
    && JornadaCruceBiotime::validar_evidencia($tokenAdicional, 'salida', $claveContinuidad, 7)['salida'] === $rAdicional['salida_bio'],
    'Los extremos reales comparados conservan su evidencia para ajustes explícitos');
$r = JornadaCruceBiotime::comparar([$jornadaAdicional], array_merge($tresMarcas, [marcaCruce('12:00:00', '2026-10-02')]), 0)[0];
verificarCruce($r['resultado'] === 'Marcaciones adicionales por revisar' && count($r['marcaciones_adicionales']) === 2
    && count($r['marcaciones']) === 4, 'Conserva varias marcas intermedias sin resolverlas automáticamente');
$r = JornadaCruceBiotime::comparar([$jornadaAdicional],
    [marcaCruce('05:55:00', '2026-10-02'), marcaCruce('15:13:00', '2026-10-02'), marcaCruce('15:17:00', '2026-10-02')], 0)[0];
verificarCruce($r['resultado'] === 'Revisión de marcaciones' && $r['entrada_bio'] === '' && $r['salida_bio'] === '',
    'Un empate entre salidas mantiene revisión sin escoger extremos');
$r = JornadaCruceBiotime::comparar([$jornadaAdicional],
    [marcaCruce('05:55:00', '2026-10-02'), marcaCruce('15:17:00', '2026-10-02'), marcaCruce('23:00:00', '2026-10-02')], 0)[0];
verificarCruce($r['resultado'] === 'Revisión de marcaciones' && $r['salida_bio'] === '',
    'Una marca exterior no se oculta para escoger una salida intermedia');
$r = JornadaCruceBiotime::comparar([$cierreOctubre],
    [marcaCruce('18:57:00', '2026-10-15'), marcaCruce('22:00:00', '2026-10-15'), marcaCruce('00:02:00', '2026-10-16')], 0, '2026-10-15')[0];
verificarCruce($r['corte_periodo_salida'] && $r['diferencia_salida'] === 0.0 && $r['resultado'] === 'Marcación adicional por revisar'
    && $r['marcaciones_adicionales'] === ['2026-10-15 22:00:00'], 'El corte válido de periodo conserva el aviso de la marca adicional');

// Bloque 434/435: hay marcas de otros horarios dentro de los dos días consultados.
$bloqueDiegoInicio = filaCruce($fila, 434, '2026-10-06 19:00:00', '2026-10-07 00:00:00');
$bloqueDiegoFin = filaCruce($fila, 435, '2026-10-07 00:00:00', '2026-10-07 07:00:00');
$marcasDiego = [marcaCruce('00:24:00', '2026-10-06'), marcaCruce('08:19:00', '2026-10-06'),
    marcaCruce('16:05:00', '2026-10-06'), marcaCruce('19:11:00', '2026-10-06'),
    marcaCruce('07:00:00', '2026-10-07'), marcaCruce('19:15:00', '2026-10-07')];
$filasDiego = [$bloqueDiegoInicio, $bloqueDiegoFin];
$rDiego = JornadaCruceBiotime::comparar($filasDiego, $marcasDiego, 0);
verificarCruce($rDiego[0]['entrada_bio'] === '2026-10-06 19:11:00' && $rDiego[0]['diferencia_entrada'] === 11.0
    && $rDiego[1]['salida_bio'] === '2026-10-07 07:00:00' && $rDiego[1]['diferencia_salida'] === 0.0,
    'Bloque 434/435: compara 19:11 a 07:00 sin emparejar marcas ajenas por orden');
verificarCruce($rDiego[0]['diferencia_salida'] === 0.0 && $rDiego[1]['diferencia_entrada'] === 0.0
    && $rDiego[0]['salida_bio'] === '' && $rDiego[1]['entrada_bio'] === '',
    'El bloque validado conserva el corte de medianoche sin inventar marcas');
verificarCruce(count($rDiego[0]['marcaciones']) === 6 && count($rDiego[0]['marcaciones_adicionales']) === 4
    && $rDiego[0]['resultado'] === 'Marcaciones adicionales por revisar'
    && $rDiego[1]['marcaciones_adicionales'] === $rDiego[0]['marcaciones_adicionales'],
    'Conserva las seis marcas y destaca las cuatro pendientes en ambas jornadas');
verificarCruce($rDiego === JornadaCruceBiotime::comparar(array_reverse($filasDiego), array_reverse($marcasDiego), 0),
    'El rescate del bloque no depende del orden de jornadas ni de marcas');
$r = JornadaCruceBiotime::comparar($filasDiego, $marcasDiego, 20);
verificarCruce($r[0]['entrada_bio'] === $rDiego[0]['entrada_bio'] && $r[0]['resultado'] === 'Marcaciones adicionales por revisar',
    'La tolerancia no cambia el par ni oculta las marcas pendientes del bloque');
$turnoPrevioDiego = filaCruce($fila, 433, '2026-10-06 00:00:00', '2026-10-06 08:30:00');
$r = JornadaCruceBiotime::comparar(array_merge([$turnoPrevioDiego], $filasDiego), $marcasDiego, 0);
verificarCruce($r[0]['entrada_bio'] === '2026-10-06 00:24:00' && $r[0]['salida_bio'] === '2026-10-06 08:19:00'
    && $r[1]['entrada_bio'] === '2026-10-06 19:11:00' && $r[2]['salida_bio'] === '2026-10-07 07:00:00'
    && $r[1]['marcaciones_adicionales'] === ['2026-10-06 16:05:00', '2026-10-07 19:15:00'],
    'Separa otro turno del mismo día sin reutilizar sus marcas ni llamarlas adicionales');
$turnoCompetidor = filaCruce($fila, 436, '2026-10-06 16:00:00', '2026-10-06 19:10:00');
$r = JornadaCruceBiotime::comparar(array_merge([$turnoCompetidor], $filasDiego), $marcasDiego, 0);
verificarCruce($r[1]['resultado'] === 'Revisión de marcaciones' && $r[1]['entrada_bio'] === ''
    && $r[1]['diferencia_salida'] === null, 'Una marca que compite con la salida de otro turno impide validar el bloque');
$r = JornadaCruceBiotime::comparar($filasDiego, array_merge($marcasDiego, [marcaCruce('18:49:00', '2026-10-06')]), 0);
verificarCruce($r[0]['resultado'] === 'Revisión de marcaciones' && $r[0]['entrada_bio'] === '',
    'Entradas equidistantes del bloque requieren revisión');
$r = JornadaCruceBiotime::comparar($filasDiego, array_merge($marcasDiego, [marcaCruce('00:00:00', '2026-10-07')]), 0);
verificarCruce($r[0]['resultado'] === 'Marcaciones adicionales por revisar' && $r[0]['diferencia_salida'] === 0.0
    && $r[0]['limite_compartido_salida'] && $r[1]['limite_compartido_entrada']
    && $r[0]['salida_bio'] === '2026-10-07 00:00:00' && count($r[0]['marcaciones_adicionales']) === 4,
    'Una marca real en el corte respalda el límite y no se clasifica como adicional');

// Continuidad a las 07:00: la marca 07:29 es intermedia, no una salida y entrada reutilizadas.
$jornadasSiete = [filaCruce($fila, 427, '2026-10-01 15:00:00', '2026-10-02 00:00:00'),
    filaCruce($fila, 428, '2026-10-02 00:00:00', '2026-10-02 07:00:00'),
    filaCruce($fila, 429, '2026-10-02 07:00:00', '2026-10-02 16:00:00')];
$marcasSiete = [marcaCruce('00:12:00', '2026-09-30'), marcaCruce('06:45:00', '2026-09-30'),
    marcaCruce('12:47:00', '2026-09-30'), marcaCruce('01:02:00', '2026-10-01'),
    marcaCruce('15:25:00', '2026-10-01'), marcaCruce('07:29:00', '2026-10-02'), marcaCruce('16:04:00', '2026-10-02')];
$rSiete = JornadaCruceBiotime::comparar($jornadasSiete, $marcasSiete, 0);
verificarCruce($rSiete[0]['entrada_bio'] === '2026-10-01 15:25:00' && $rSiete[0]['diferencia_entrada'] === 25.0
    && $rSiete[2]['salida_bio'] === '2026-10-02 16:04:00' && $rSiete[2]['diferencia_salida'] === 4.0,
    'Bloque 427/428/429: compara 15:25 a 16:04 con diferencias de 25 y 4 minutos');
verificarCruce($rSiete[0]['diferencia_salida'] === 0.0 && $rSiete[1]['diferencia_entrada'] === 0.0
    && $rSiete[1]['diferencia_salida'] === 0.0 && $rSiete[2]['diferencia_entrada'] === 0.0
    && $rSiete[1]['salida_bio'] === '' && $rSiete[2]['entrada_bio'] === '',
    'Los cortes de medianoche y 07:00 tienen diferencia cero sin crear marcas ajustables');
verificarCruce(in_array('2026-10-02 07:29:00', $rSiete[1]['marcaciones_adicionales'], true)
    && $rSiete[1]['resultado'] === 'Marcaciones adicionales por revisar'
    && strpos($rSiete[1]['detalle'], '2026-10-02 07:00:00') !== false,
    'La marca 07:29 permanece pendiente y el detalle indica la hora real del corte');
verificarCruce($rSiete === JornadaCruceBiotime::comparar(array_reverse($jornadasSiete), array_reverse($marcasSiete), 0),
    'La continuidad a cualquier hora es determinística');
$tokenSiete = JornadaCruceBiotime::autorizar_evidencia($rSiete[2], $claveContinuidad, 7);
$rechazoSiete = false;
try { JornadaCruceBiotime::validar_evidencia($tokenSiete, 'entrada', $claveContinuidad, 7); }
catch (RuntimeException $e) { $rechazoSiete = true; }
verificarCruce($rechazoSiete && JornadaCruceBiotime::validar_evidencia($tokenSiete, 'salida', $claveContinuidad, 7)['salida'] === '2026-10-02 16:04:00',
    'El corte a las 07:00 no se autoriza como evidencia de entrada');
$inicioSiete = filaCruce($fila, 500, '2026-10-03 05:00:00', '2026-10-03 07:00:00');
$finSiete = filaCruce($fila, 501, '2026-10-03 07:00:00', '2026-10-03 12:00:00');
$r = JornadaCruceBiotime::comparar([$inicioSiete, $finSiete],
    [marcaCruce('04:59:00', '2026-10-03'), marcaCruce('06:58:00', '2026-10-03'),
     marcaCruce('07:01:00', '2026-10-03'), marcaCruce('12:02:00', '2026-10-03')], 0);
verificarCruce(!$r[0]['continuidad_salida'] && !$r[1]['continuidad_entrada']
    && $r[0]['salida_bio'] === '2026-10-03 06:58:00' && $r[1]['entrada_bio'] === '2026-10-03 07:01:00',
    'Con pares reales alrededor de las 07:00 respeta las marcaciones diarias');
$conHuecoSiete = filaCruce($fila, 501, '2026-10-03 07:01:00', '2026-10-03 12:00:00');
$r = JornadaCruceBiotime::comparar([$inicioSiete, $conHuecoSiete],
    [marcaCruce('05:00:00', '2026-10-03'), marcaCruce('12:00:00', '2026-10-03')], 0);
verificarCruce($r[0]['resultado'] === 'Revisión de marcaciones' && !$r[0]['continuidad_salida'],
    'La continuidad a cualquier hora sigue exigiendo coincidencia exacta sin huecos');

// Marca aislada pendiente junto a pares ya resueltos: no altera los cruces anteriores.
$madrugadaPendiente = filaCruce($fila, 432, '2026-10-06 00:00:00', '2026-10-06 01:00:00');
$diaResuelto = filaCruce($fila, 433, '2026-10-06 08:00:00', '2026-10-06 16:00:00');
$basePendiente = [$diaResuelto, $bloqueDiegoInicio, $bloqueDiegoFin];
$antesPendiente = JornadaCruceBiotime::comparar($basePendiente, $marcasDiego, 0);
$filasPendientes = array_merge([$madrugadaPendiente], $basePendiente);
$rPendiente = JornadaCruceBiotime::comparar($filasPendientes, $marcasDiego, 0);
verificarCruce($rPendiente[0]['resultado'] === 'Marcación incompleta' && $rPendiente[0]['entrada_bio'] === '2026-10-06 00:24:00'
    && $rPendiente[0]['diferencia_entrada'] === 24.0 && $rPendiente[0]['salida_bio'] === ''
    && $rPendiente[0]['diferencia_salida'] === null && $rPendiente[0]['extremo_pendiente'] === 'entrada',
    'La jornada 00:00 a 01:00 muestra las 00:24 como entrada provisional sin inventar salida');
verificarCruce(array_slice($rPendiente, 1) === $antesPendiente,
    'La marcación pendiente no altera los pares, diferencias, cortes ni avisos ya resueltos');
verificarCruce($rPendiente[0]['marcaciones_pendientes'] === ['2026-10-06 00:24:00']
    && count($rPendiente[0]['marcaciones']) === 6 && strpos($rPendiente[0]['detalle'], 'provisionalmente') !== false,
    'Conserva toda la evidencia e indica que debe confirmarse si la marca es entrada o salida');
verificarCruce($rPendiente === JornadaCruceBiotime::comparar(array_reverse($filasPendientes), array_reverse($marcasDiego), 0),
    'Las marcas aisladas pendientes no dependen del orden de entrada');
$tokenPendiente = JornadaCruceBiotime::autorizar_evidencia($rPendiente[0], $claveContinuidad, 7);
foreach (['entrada', 'salida'] as $extremoPendiente) {
    $rechazoPendiente = false;
    try { JornadaCruceBiotime::validar_evidencia($tokenPendiente, $extremoPendiente, $claveContinuidad, 7); }
    catch (RuntimeException $e) { $rechazoPendiente = true; }
    verificarCruce($rechazoPendiente, 'Una evidencia provisional no autoriza ajustar ' . $extremoPendiente);
}
$marcasEmpatePendiente = $marcasDiego;
$marcasEmpatePendiente[0] = marcaCruce('00:30:00', '2026-10-06');
$r = JornadaCruceBiotime::comparar($filasPendientes, $marcasEmpatePendiente, 0);
verificarCruce($r[0]['resultado'] === 'Marcación pendiente de identificar' && $r[0]['entrada_bio'] === ''
    && $r[0]['salida_bio'] === '' && $r[0]['diferencia_entrada'] === null && $r[0]['diferencia_salida'] === null
    && $r[0]['marcaciones_pendientes'] === ['2026-10-06 00:30:00'],
    'Una marca equidistante permanece visible sin decidir si es entrada o salida');
$marcasSalidaPendiente = $marcasDiego;
$marcasSalidaPendiente[0] = marcaCruce('00:50:00', '2026-10-06');
$r = JornadaCruceBiotime::comparar($filasPendientes, $marcasSalidaPendiente, 0);
verificarCruce($r[0]['resultado'] === 'Marcación incompleta' && $r[0]['entrada_bio'] === ''
    && $r[0]['salida_bio'] === '2026-10-06 00:50:00' && $r[0]['diferencia_salida'] === -10.0
    && $r[0]['extremo_pendiente'] === 'salida', 'Una salida aislada cercana conserva diferencia sin inventar entrada');

// Caso 31/32: una única marca real a medianoche respalda las dos filas vecinas.
$filasCompartidas = [filaCruce($fila, 31, '2026-09-21 20:00:00', '2026-09-22 00:00:00'),
    filaCruce($fila, 32, '2026-09-22 00:00:00', '2026-09-22 18:22:00')];
$marcasCompartidas = [marcaCruce('20:04:00', '2026-09-21'), marcaCruce('00:00:00', '2026-09-22'),
    marcaCruce('18:22:00', '2026-09-22')];
$rCompartidas = JornadaCruceBiotime::comparar($filasCompartidas, $marcasCompartidas, 0);
verificarCruce($rCompartidas[0]['entrada_bio'] === '2026-09-21 20:04:00'
    && $rCompartidas[0]['diferencia_entrada'] === 4.0 && $rCompartidas[0]['diferencia_salida'] === 0.0
    && $rCompartidas[1]['diferencia_entrada'] === 0.0 && $rCompartidas[1]['diferencia_salida'] === 0.0,
    'Jornadas 31/32: compara 20:04, límite 00:00 y salida 18:22 con diferencias +4/0 y 0/0');
verificarCruce($rCompartidas[0]['salida_bio'] === $rCompartidas[1]['entrada_bio']
    && $rCompartidas[0]['limite_compartido_salida'] && $rCompartidas[1]['limite_compartido_entrada']
    && !$rCompartidas[0]['continuidad_salida'] && count($rCompartidas[0]['marcaciones']) === 3
    && $rCompartidas[0]['marcaciones_adicionales'] === [],
    'El límite real se referencia desde ambas filas conservando tres eventos únicos');
verificarCruce($rCompartidas[0]['resultado'] === 'Diferencia de horario' && $rCompartidas[1]['resultado'] === 'Horario correcto',
    'Cada jornada conserva el resultado de sus propios extremos');
verificarCruce($rCompartidas === JornadaCruceBiotime::comparar(array_reverse($filasCompartidas), array_reverse($marcasCompartidas), 0),
    'Los límites compartidos no dependen del orden recibido');
foreach ([[0, 'salida'], [1, 'entrada']] as $casoCompartido) {
    [$posicionCompartida, $extremoCompartido] = $casoCompartido;
    $tokenCompartido = JornadaCruceBiotime::autorizar_evidencia($rCompartidas[$posicionCompartida], $claveContinuidad, 7);
    $rechazoCompartido = false;
    try { JornadaCruceBiotime::validar_evidencia($tokenCompartido, $extremoCompartido, $claveContinuidad, 7); }
    catch (RuntimeException $e) { $rechazoCompartido = true; }
    verificarCruce($rechazoCompartido, 'El límite compartido no permite ajustar aisladamente ' . $extremoCompartido);
}
$contextoCompartido = array_merge([filaCruce($fila, 30, '2026-09-21 02:00:00', '2026-09-21 12:00:00')], $filasCompartidas,
    [filaCruce($fila, 33, '2026-09-22 19:30:00', '2026-09-23 10:30:00'),
     filaCruce($fila, 34, '2026-09-23 21:00:00', '2026-09-24 12:30:00')]);
$evidenciaCompartida = array_merge($marcasCompartidas,
    [marcaCruce('01:56:00', '2026-09-21'), marcaCruce('11:54:00', '2026-09-21'),
     marcaCruce('19:49:00', '2026-09-22'), marcaCruce('10:32:00', '2026-09-23'),
     marcaCruce('21:09:00', '2026-09-23'), marcaCruce('12:28:00', '2026-09-24')]);
$r = JornadaCruceBiotime::comparar($contextoCompartido, $evidenciaCompartida, 0);
verificarCruce($r[1]['entrada_bio'] === '2026-09-21 20:04:00' && $r[2]['salida_bio'] === '2026-09-22 18:22:00'
    && $r[2]['resultado'] === 'Horario correcto' && $r[1]['marcaciones_adicionales'] === [],
    'Las nueve marcas de la imagen se distribuyen entre sus bloques sin bloquear el límite de medianoche');

$r = JornadaCruceBiotime::comparar([$inicioSiete, $finSiete],
    [marcaCruce('04:59:00', '2026-10-03'), marcaCruce('07:00:00', '2026-10-03'),
     marcaCruce('12:02:00', '2026-10-03')], 0);
verificarCruce($r[0]['limite_compartido_salida'] && $r[1]['limite_compartido_entrada']
    && $r[0]['salida_bio'] === '2026-10-03 07:00:00' && $r[1]['entrada_bio'] === '2026-10-03 07:00:00',
    'La misma regla reconoce límites reales compartidos a las 07:00');
$r = JornadaCruceBiotime::comparar($filasCompartidas, array_merge($marcasCompartidas, [$marcasCompartidas[1]]), 0);
verificarCruce($r === $rCompartidas, 'La duplicación de origen de un límite real no crea nuevos eventos');
foreach ([array_slice($marcasCompartidas, 1), array_slice($marcasCompartidas, 0, 2)] as $sinExtremoCompartido) {
    $r = JornadaCruceBiotime::comparar($filasCompartidas, $sinExtremoCompartido, 0);
    verificarCruce($r[0]['resultado'] === 'Revisión de marcaciones' && $r[0]['diferencia_salida'] === null
        && !$r[0]['limite_compartido_salida'], 'Una marca interna no sustituye un extremo faltante del bloque');
}

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
