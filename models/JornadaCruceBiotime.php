<?php

/** Cruce independiente y ajustes explícitos auditados; conserva firmas y cálculos contables. */
class JornadaCruceBiotime extends Conectar
{
    public function reportes($desde, $hasta, $empleado = null)
    {
        // Incluye los vecinos firmados consecutivos aunque el filtro corte el bloque.
        $sql = "WITH RECURSIVE detalles AS (
                    SELECT d.jrd_id, r.empleado_id,
                        (d.jrd_snapshot->>'jornada_inicio')::timestamp AS inicio,
                        (d.jrd_snapshot->>'jornada_fin')::timestamp AS fin
                    FROM jornada_reporte_detalle d
                    INNER JOIN jornada_reportes_firma r ON r.jrf_id = d.jrf_id
                    WHERE r.jrf_estado = 'FIRMADO'";
        if ($empleado !== null) {
            $sql .= ' AND r.empleado_id = :empleado';
        }
        $sql .= "), continuidad AS (
                    SELECT * FROM detalles WHERE inicio::date
                        BETWEEN CAST(:desde AS date) AND CAST(:hasta AS date)
                    UNION
                    SELECT v.* FROM detalles v
                    INNER JOIN continuidad c ON c.empleado_id = v.empleado_id
                        AND (v.inicio = c.fin OR v.fin = c.inicio)
                )
                SELECT d.jrd_id, d.jrf_id, d.jornada_id, d.jrd_jornada_version,
                    d.jrd_snapshot, j.jornada_version, j.jornada_inicio, j.jornada_fin,
                    e.je_codigo AS estado_codigo,
                    emp.id_empl AS empleado_id, emp.cedu_empl AS documento,
                    emp.nomb_empl AS empleado
                FROM jornada_reporte_detalle d
                INNER JOIN jornada_reportes_firma r ON r.jrf_id = d.jrf_id
                INNER JOIN jornadas_trabajo j ON j.jornada_id = d.jornada_id
                INNER JOIN jornada_estados e ON e.je_id = j.jornada_estado_id
                INNER JOIN empleados emp ON emp.id_empl = r.empleado_id
                WHERE r.jrf_estado = 'FIRMADO'
                  AND d.jrd_id IN (SELECT jrd_id FROM continuidad)";
        $sql .= ' ORDER BY emp.nomb_empl, d.jrd_id';
        $stmt = parent::Conexion()->prepare($sql);
        $stmt->bindValue(':desde', $desde);
        $stmt->bindValue(':hasta', $hasta);
        if ($empleado !== null) {
            $stmt->bindValue(':empleado', $empleado, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Solo acepta respuestas completas; una falla nunca equivale a ausencia. */
    public function marcaciones($desde, $hasta)
    {
        $resultado = [];
        $pagina = 1;
        $esperados = null;
        do {
            $respuesta = CurlController::requestBiotime(http_build_query([
                'start_date' => $desde,
                'end_date' => $hasta,
                'departments' => 1,
                'areas' => 2,
                'page' => $pagina,
                'page_size' => 1000
            ]), 'GET');
            if (
                !is_object($respuesta) || !isset($respuesta->data, $respuesta->count)
                || !is_array($respuesta->data) || !is_numeric($respuesta->count)
            ) {
                throw new RuntimeException('BioTime no entregó una respuesta válida. Intente nuevamente.');
            }
            $total = (int)$respuesta->count;
            if ($total < 0 || ($esperados !== null && $esperados !== $total)) {
                throw new RuntimeException('Las marcaciones cambiaron durante la consulta. Repita el cruce.');
            }
            $esperados = $total;

            if ($total > 20000 || (empty($respuesta->data) && count($resultado) < $total)) {
                throw new RuntimeException('No se pudo completar la consulta BioTime. Reduzca el periodo.');
            }

            foreach ($respuesta->data as $registro) {
                if (
                    !is_object($registro) || empty($registro->emp_code)
                    || empty($registro->att_date) || empty($registro->punch_time)
                ) {
                    throw new RuntimeException('BioTime devolvió una marcación incompleta. No se realizó el cruce.');
                }

                $resultado[] = $registro;
            }

            $pagina++;
        } while (count($resultado) < $esperados);
        if (count($resultado) !== $esperados) {
            throw new RuntimeException('La cantidad de marcaciones BioTime no coincide con su respuesta.');
        }
        return $resultado;
    }

    /** Asocia pares cronológicos por empleado y días de la jornada, sin alterar liquidaciones. */
    public static function comparar(array $filas, array $marcaciones, $tolerancia, $hasta = null)
    {
        $zona = new DateTimeZone('America/Bogota');
        $cierrePeriodo = null;
        if ($hasta !== null) {
            $finPeriodo = DateTimeImmutable::createFromFormat('!Y-m-d', $hasta, $zona);
            if (!$finPeriodo || $finPeriodo->format('Y-m-d') !== $hasta) {
                throw new InvalidArgumentException('El cierre del periodo no es válido.');
            }
            $cierrePeriodo = $finPeriodo->modify('+1 day')->getTimestamp();
        }
        $indice = [];
        foreach ($marcaciones as $m) {
            $documento = preg_replace('/\D+/', '', (string)$m->emp_code);
            $hora = trim((string)$m->punch_time);
            $texto = preg_match('/^\d{4}-\d{2}-\d{2}[ T]/', $hora)
                ? $hora : $m->att_date . ' ' . $hora;
            try {
                $fecha = new DateTimeImmutable($texto, $zona);
            } catch (Exception $e) {
                throw new RuntimeException('BioTime devolvió una fecha de marcación no válida.');
            }
            // Un mismo instante y documento constituye una sola evidencia.
            $indice[$documento][$fecha->getTimestamp()] = $fecha->setTimezone($zona)->format('Y-m-d H:i:s');
        }
        $salida = [];
        $intervalos = [];
        $modificadas = [];
        foreach ($filas as $fila) {
            $snapshot = json_decode($fila['jrd_snapshot'], true);
            $i = count($salida);
            $salida[$i] = [
                'reporte' => (int)$fila['jrf_id'], 'jornada' => (int)$fila['jornada_id'],
                'empleado_id' => (int)$fila['empleado_id'], 'empleado' => $fila['empleado'],
                'documento' => $fila['documento'], 'inicio' => '', 'fin' => '', 'ubicacion' => '',
                'entrada_bio' => '', 'salida_bio' => '', 'diferencia_entrada' => null,
                'diferencia_salida' => null, 'resultado' => 'Revisión de marcaciones',
                'marcaciones' => [], 'marcaciones_adicionales' => [], 'detalle' => '',
                'marcaciones_pendientes' => [], 'extremo_pendiente' => '',
                'continuidad_entrada' => false, 'continuidad_salida' => false,
                'limite_compartido_entrada' => false, 'limite_compartido_salida' => false,
                'corte_periodo_salida' => false, 'salida_bio_real' => '',
                'version_firmada' => (int)$fila['jrd_jornada_version'],
                'version_actual' => (int)$fila['jornada_version'],
                'inicio_actual' => $fila['jornada_inicio'], 'fin_actual' => $fila['jornada_fin'],
                'estado_actual' => $fila['estado_codigo'] ?? '',
                'snapshot_hash' => hash('sha256', $fila['jrd_snapshot'])
            ];
            try {
                if (empty($snapshot['jornada_inicio']) || empty($snapshot['jornada_fin'])) {
                    throw new RuntimeException('Copia incompleta');
                }
                $inicio = new DateTimeImmutable($snapshot['jornada_inicio'], $zona);
                $fin = new DateTimeImmutable($snapshot['jornada_fin'], $zona);
                if ($fin <= $inicio) {
                    throw new RuntimeException('Intervalo inválido');
                }
            } catch (Exception $e) {
                $salida[$i]['detalle'] = 'La copia firmada no contiene un intervalo válido para comparar.';
                continue;
            }
            $salida[$i]['inicio'] = $inicio->format('Y-m-d H:i:s');
            $salida[$i]['fin'] = $fin->format('Y-m-d H:i:s');
            $salida[$i]['ubicacion'] = $snapshot['jornada_ubicacion'] ?? '';
            $documento = preg_replace('/\D+/', '', (string)$fila['documento']);
            $intervalos[$documento][$i] = ['entrada' => $inicio->getTimestamp(), 'salida' => $fin->getTimestamp()];
            // La versión cambia también al firmar; el diagnóstico conserva siempre el snapshot.
            if ($inicio != new DateTimeImmutable($fila['jornada_inicio'], $zona)
                || $fin != new DateTimeImmutable($fila['jornada_fin'], $zona)) {
                $modificadas[$i] = true;
                $salida[$i]['detalle'] = 'El horario actual difiere de la copia firmada. Se comparó únicamente la copia firmada. ';
            }
        }
        foreach ($intervalos as $documento => $jornadas) {
            // Orden determinístico de jornadas; nunca se seleccionan evidencias por tolerancia.
            uksort($jornadas, function ($a, $b) use ($jornadas, $salida) {
                return [$jornadas[$a]['entrada'], $jornadas[$a]['salida'], $salida[$a]['reporte'], $salida[$a]['jornada']]
                    <=> [$jornadas[$b]['entrada'], $jornadas[$b]['salida'], $salida[$b]['reporte'], $salida[$b]['jornada']];
            });
            // Une cortes exactos a cualquier hora con un único vecino por extremo.
            // Las copias duplicadas o superpuestas conservan el diagnóstico de revisión.
            $inicios = $finales = [];
            foreach ($jornadas as $i => $intervalo) {
                $inicios[$intervalo['entrada']][] = $i;
                $finales[$intervalo['salida']][] = $i;
            }
            $bloques = [];
            foreach ($jornadas as $i => $intervalo) {
                $ultimo = count($bloques) - 1;
                $corte = $intervalo['entrada'];
                if ($ultimo >= 0 && $bloques[$ultimo]['salida'] === $corte
                    && $salida[$bloques[$ultimo]['ids'][0]]['empleado_id'] === $salida[$i]['empleado_id']
                    && count($inicios[$corte]) === 1 && count($finales[$corte] ?? []) === 1) {
                    $bloques[$ultimo]['salida'] = $intervalo['salida'];
                    $bloques[$ultimo]['ids'][] = $i;
                } else {
                    $bloques[] = $intervalo + ['ids' => [$i]];
                }
            }
            // Agrupa los días compartidos, incluyendo ambos días de un turno nocturno.
            // No descarta marcaciones por su distancia en horas al horario firmado.
            $grupos = [];
            foreach ($bloques as $b => $intervalo) {
                $desde = substr($salida[$intervalo['ids'][0]]['inicio'], 0, 10);
                $hasta = substr($salida[$intervalo['ids'][count($intervalo['ids']) - 1]]['fin'], 0, 10);
                $ultimo = count($grupos) - 1;
                if ($ultimo < 0 || $desde > $grupos[$ultimo]['hasta']) {
                    $grupos[] = ['desde' => $desde, 'hasta' => $hasta, 'ids' => [$b]];
                } else {
                    $grupos[$ultimo]['hasta'] = max($grupos[$ultimo]['hasta'], $hasta);
                    $grupos[$ultimo]['ids'][] = $b;
                }
            }
            $marcas = $indice[$documento] ?? [];
            ksort($marcas);
            foreach ($grupos as $grupo) {
                $candidatas = array_filter($marcas, function ($texto) use ($grupo) {
                    $dia = substr($texto, 0, 10);
                    return $dia >= $grupo['desde'] && $dia <= $grupo['hasta'];
                });
                $tiempos = array_keys($candidatas);
                $cantidad = count($tiempos);
                $comparaciones = array_map(function ($b) use ($bloques) { return $bloques[$b]; }, $grupo['ids']);
                $revision = false;
                if (count($comparaciones) === 1 && $cantidad === 1) {
                    // Una evidencia única puede ser entrada o salida; jamás constituye un par.
                    $extremo = abs($tiempos[0] - $comparaciones[0]['salida']) < abs($tiempos[0] - $comparaciones[0]['entrada'])
                        ? 'salida' : 'entrada';
                    $comparaciones[0]['marcas'] = [$extremo => $tiempos[0]];
                } elseif ($cantidad > 0) {
                    // Busca una única distribución que consuma todas las marcas: pares reales
                    // por jornada, bloques sin marcas internas, o una combinación de ambos.
                    $pares = self::asociar_pares($comparaciones, $candidatas, $jornadas, $salida);
                    if ($pares === null && count($comparaciones) === 1 && count($comparaciones[0]['ids']) === 1) {
                        // Una jornada con extremos inequívocos puede compararse aunque tenga marcas intermedias.
                        $parConAdicionales = self::extremos_con_adicionales($comparaciones[0], $candidatas);
                        if ($parConAdicionales !== null) $pares = [$parConAdicionales];
                    }
                    if (count(array_filter($comparaciones, function ($b) { return count($b['ids']) > 1; })) > 0) {
                        $rescates = self::bloques_con_adicionales($comparaciones, $candidatas, $jornadas, $salida);
                        if ($pares === null) {
                            $pares = $rescates;
                        } else {
                            // Un conteo exacto tampoco justifica desplazar los extremos reales
                            // del bloque para consumir marcas que pertenecen a otros horarios.
                            $porJornada = [];
                            foreach ($pares as $par) {
                                $porJornada[$par['ids'][0]]['entrada'] = $par['marcas']['entrada'];
                                $porJornada[$par['ids'][count($par['ids']) - 1]]['salida'] = $par['marcas']['salida'];
                            }
                            $mejora = false;
                            $sinEmpeorar = true;
                            foreach ($rescates as $rescate) {
                                if ($rescate['revision']) { $sinEmpeorar = false; break; }
                                foreach (['entrada', 'salida'] as $extremo) {
                                    $id = $extremo === 'entrada' ? $rescate['ids'][0] : $rescate['ids'][count($rescate['ids']) - 1];
                                    $anterior = abs($porJornada[$id][$extremo] - $rescate[$extremo]);
                                    $nueva = abs($rescate['marcas'][$extremo] - $rescate[$extremo]);
                                    if ($nueva > $anterior) $sinEmpeorar = false;
                                    if ($nueva < $anterior) $mejora = true;
                                }
                            }
                            if ($sinEmpeorar && $mejora) $pares = $rescates;
                        }
                    }
                    if ($pares === null) {
                        $revision = true;
                    } else {
                        $comparaciones = $pares;
                    }
                }
                $comparaciones = self::identificar_pendientes($comparaciones, $candidatas, $revision);
                foreach ($comparaciones as $bloque) {
                    $ids = $bloque['ids'];
                    foreach ($ids as $posicion => $i) {
                        $item = &$salida[$i];
                        $item['marcaciones'] = array_values($candidatas);
                        $item['marcaciones_adicionales'] = $bloque['adicionales'] ?? [];
                        $item['continuidad_entrada'] = $posicion > 0;
                        $item['continuidad_salida'] = $posicion < count($ids) - 1;
                        if (count($ids) > 1) {
                            $item['detalle'] .= 'Bloque continuo: ' . $salida[$ids[0]]['inicio'] . ' a '
                                . $salida[$ids[count($ids) - 1]]['fin'] . '. Jornadas: '
                                . implode(', ', array_map(function ($id) use ($salida) { return $salida[$id]['jornada']; }, $ids))
                                . '. Cortes internos: '
                                . implode(', ', array_map(function ($id) use ($salida) { return $salida[$id]['inicio']; }, array_slice($ids, 1)))
                                . '. ';
                        }
                        if (!empty($bloque['pendientes'])) {
                            $item['marcaciones_pendientes'] = $bloque['pendientes'];
                            $item['resultado'] = 'Marcación pendiente de identificar';
                            $item['detalle'] .= 'Quedó una marcación sin pareja dentro del intervalo firmado: '
                                . implode(', ', $bloque['pendientes']) . '. No se modificaron los pares ya comparados. ';
                            if (!empty($bloque['parcial'])) {
                                foreach ($bloque['parcial'] as $extremo => $instante) {
                                    $item[$extremo . '_bio'] = $candidatas[$instante];
                                    $item['diferencia_' . $extremo] = round(($instante - $jornadas[$i][$extremo]) / 60, 2);
                                    $item['extremo_pendiente'] = $extremo;
                                }
                                $item['resultado'] = 'Marcación incompleta';
                                $item['detalle'] .= 'Se muestra provisionalmente como ' . $item['extremo_pendiente']
                                    . ' por cercanía al horario firmado. Falta confirmar si es entrada o salida'
                                    . ' y revisar el turno anterior. La otra marcación sigue pendiente; no se habilitan ajustes con esta propuesta.';
                            } else {
                                $item['detalle'] .= 'La marca tiene más de una interpretación posible. No se asignó a entrada ni salida ni se habilitan ajustes.';
                            }
                        } elseif ($revision || !empty($bloque['revision'])) {
                            $item['resultado'] = 'Revisión de marcaciones';
                            $item['detalle'] .= 'Las marcaciones disponibles no forman pares inequívocos para las jornadas firmadas. Se conservan todas para revisión manual.';
                        } else {
                            $elegidas = $bloque['marcas'] ?? [];
                            foreach ($elegidas as $extremo => $instante) {
                                $corte = $jornadas[$i][$extremo];
                                if ($item['continuidad_' . $extremo] && isset($bloque['compartidas'][$corte])) {
                                    $item['limite_compartido_' . $extremo] = true;
                                    $item['continuidad_' . $extremo] = false;
                                    $item[$extremo . '_bio'] = $candidatas[$corte];
                                    $item['diferencia_' . $extremo] = 0.0;
                                    continue;
                                }
                                if ($item['continuidad_' . $extremo]) continue;
                                $item[$extremo . '_bio'] = $candidatas[$instante];
                                $item['diferencia_' . $extremo] = round(($instante - $jornadas[$i][$extremo]) / 60, 2);
                            }
                            if (!$elegidas) {
                                $item['resultado'] = 'Sin marcaciones';
                                $item['detalle'] .= 'BioTime no devolvió marcaciones para los días de esta jornada. No implica ausencia laboral.';
                            } elseif (count($elegidas) === 1) {
                                $item['resultado'] = 'Marcación incompleta';
                            } else {
                                // Un par válido respalda el corte interno, sin convertirlo en evidencia ajustable.
                                foreach (['entrada', 'salida'] as $extremo) {
                                    if ($item['continuidad_' . $extremo]) $item['diferencia_' . $extremo] = 0.0;
                                }
                                if (count($ids) > 1) {
                                    $item['detalle'] .= 'Cortes validados: diferencia 00:00. ';
                                    if (!empty($bloque['compartidas'])) {
                                        $item['detalle'] .= count($bloque['compartidas']) . ' marca(s) en límites compartidos. ';
                                    }
                                }
                                // Al cerrar el periodo, la salida posterior respalda la continuidad
                                // hasta las 00:00; se conserva como soporte y no como ajuste de esta jornada.
                                if ($cierrePeriodo !== null && $jornadas[$i]['salida'] === $cierrePeriodo
                                    && $elegidas['entrada'] < $cierrePeriodo && $elegidas['salida'] > $cierrePeriodo) {
                                    $item['corte_periodo_salida'] = true;
                                    $item['continuidad_salida'] = false;
                                    $item['salida_bio_real'] = $candidatas[$elegidas['salida']];
                                    $item['salida_bio'] = '';
                                    $item['diferencia_salida'] = 0.0;
                                    $item['detalle'] .= 'Corte de periodo: ' . $item['fin'] . '. Salida real BioTime: '
                                        . $item['salida_bio_real'] . '. La continuación desde el corte hasta la salida real'
                                        . ' corresponde al periodo siguiente. La diferencia de salida es 00:00 por el corte;'
                                        . ' no es una marcación ni permite ajustar la salida de esta jornada. ';
                                }
                                $item['resultado'] = abs($item['diferencia_entrada']) <= $tolerancia
                                    && abs($item['diferencia_salida']) <= $tolerancia
                                    ? 'Horario correcto' : 'Diferencia de horario';
                                if (count($ids) === 1) {
                                    $item['detalle'] .= 'Se asignó un par real de marcaciones BioTime a esta jornada.';
                                }
                                if ($item['marcaciones_adicionales']) {
                                    $item['detalle'] .= ' ' . $item['resultado'] . '. '
                                        . count($item['marcaciones_adicionales']) . ' marcaciones pendientes de revisión (ver lista).';
                                    $item['resultado'] = count($item['marcaciones_adicionales']) === 1
                                        ? 'Marcación adicional por revisar' : 'Marcaciones adicionales por revisar';
                                }
                            }
                        }
                        // Conserva el aviso de cambio y el resultado de la comparación en el detalle.
                        if (isset($modificadas[$i])) {
                            $item['detalle'] .= ' Resultado del cruce de la copia firmada: ' . $item['resultado'] . '.';
                            $item['resultado'] = 'Jornada modificada';
                        }
                        unset($item);
                    }
                }
            }
        }
        usort($salida, function ($a, $b) {
            return [$a['empleado_id'], $a['inicio'], $a['reporte'], $a['jornada']]
                <=> [$b['empleado_id'], $b['inicio'], $b['reporte'], $b['jornada']];
        });
        return $salida;
    }

    /** Agrega evidencia parcial únicamente a jornadas sin resolver; no modifica pares existentes. */
    private static function identificar_pendientes(array $bloques, array $candidatas, $revision)
    {
        $usadas = [];
        $hayPares = false;
        foreach ($bloques as $bloque) {
            if (!$revision && empty($bloque['revision']) && count($bloque['marcas'] ?? []) === 2) $hayPares = true;
            foreach ($bloque['marcas'] ?? [] as $instante) $usadas[$instante] = true;
            foreach ($bloque['compartidas'] ?? [] as $instante => $valor) $usadas[$instante] = true;
        }
        if (!$hayPares) return $bloques;
        $sinAsignar = array_diff_key($candidatas, $usadas);
        foreach ($bloques as $b => &$bloque) {
            if ((!$revision && empty($bloque['revision'])) || count($bloque['ids']) !== 1) continue;
            $internas = array_filter($sinAsignar, function ($instante) use ($bloque) {
                return $instante >= $bloque['entrada'] && $instante <= $bloque['salida'];
            }, ARRAY_FILTER_USE_KEY);
            if (count($internas) !== 1) continue;
            $instante = array_keys($internas)[0];
            // Si existen otras candidatas para alguno de los extremos, conserva
            // la revisión anterior: esto solo agrega una evidencia realmente aislada.
            $aislada = true;
            foreach (['entrada', 'salida'] as $extremo) {
                foreach ($candidatas as $otraMarca => $texto) {
                    if ($otraMarca !== $instante
                        && abs($otraMarca - $bloque[$extremo]) <= abs($instante - $bloque[$extremo])) $aislada = false;
                }
            }
            if (!$aislada) continue;
            $bloque['pendientes'] = array_values($internas);
            $compite = false;
            foreach ($bloques as $otro => $vecino) {
                if ($otro === $b) continue;
                if ($instante >= $vecino['entrada'] && $instante <= $vecino['salida']) $compite = true;
            }
            $distanciaEntrada = abs($instante - $bloque['entrada']);
            $distanciaSalida = abs($instante - $bloque['salida']);
            if (!$compite && $distanciaEntrada !== $distanciaSalida) {
                $extremo = $distanciaEntrada < $distanciaSalida ? 'entrada' : 'salida';
                $bloque['parcial'] = [$extremo => $instante];
            }
        }
        unset($bloque);
        return $bloques;
    }

    /** Rescata bloques inequívocos sin consumir marcas que compitan con otras jornadas. */
    private static function bloques_con_adicionales(array $bloques, array $candidatas, array $jornadas, array $salida)
    {
        $porBloque = [];
        foreach ($bloques as $b => $bloque) {
            $ids = $bloque['ids'];
            $desde = substr($salida[$ids[0]]['inicio'], 0, 10);
            $hasta = substr($salida[$ids[count($ids) - 1]]['fin'], 0, 10);
            $porBloque[$b] = array_filter($candidatas, function ($texto) use ($desde, $hasta) {
                $dia = substr($texto, 0, 10);
                return $dia >= $desde && $dia <= $hasta;
            });
        }
        $masCercana = function ($objetivo, $marcas) {
            $mejor = null;
            $distancia = PHP_INT_MAX;
            $empate = false;
            foreach ($marcas as $instante => $texto) {
                $diferencia = abs($instante - $objetivo);
                if ($diferencia < $distancia) {
                    $mejor = $instante;
                    $distancia = $diferencia;
                    $empate = false;
                } elseif ($diferencia === $distancia) {
                    $empate = true;
                }
            }
            return $empate ? null : $mejor;
        };
        $usadas = [];
        foreach ($bloques as $b => &$bloque) {
            $bloque['revision'] = true;
            $entrada = $masCercana($bloque['entrada'], $porBloque[$b]);
            $fin = $masCercana($bloque['salida'], $porBloque[$b]);
            if ($entrada === null || $fin === null || $entrada >= $fin
                || isset($usadas[$entrada]) || isset($usadas[$fin])) continue;
            $seguro = true;
            // Los extremos respaldan el bloque; cada marca exacta en un corte
            // se registra una sola vez como límite compartido por las filas vecinas.
            $compartidas = [];
            foreach (array_slice($bloque['ids'], 1) as $id) {
                $corte = $jornadas[$id]['entrada'];
                if ($entrada >= $corte || $fin <= $corte) $seguro = false;
                if (isset($candidatas[$corte])) $compartidas[$corte] = true;
            }
            foreach ($bloques as $otro => $vecino) {
                if ($otro === $b) continue;
                if ($bloque['entrada'] < $vecino['salida'] && $bloque['salida'] > $vecino['entrada']) $seguro = false;
                if ($entrada < $vecino['salida'] && $fin > $vecino['entrada']) $seguro = false;
                foreach ($vecino['ids'] as $id) {
                    foreach ($jornadas[$id] as $objetivo) {
                        foreach ([$entrada, $fin] as $instante) {
                            if (!isset($porBloque[$otro][$instante])) continue;
                            $hayMasCercana = false;
                            foreach ($porBloque[$otro] as $marca => $texto) {
                                if (abs($marca - $objetivo) < abs($instante - $objetivo)) {
                                    $hayMasCercana = true;
                                    break;
                                }
                            }
                            if (!$hayMasCercana) $seguro = false;
                        }
                    }
                }
            }
            if (!$seguro) continue;
            $bloque['revision'] = false;
            $bloque['marcas'] = ['entrada' => $entrada, 'salida' => $fin];
            $bloque['compartidas'] = $compartidas;
            $usadas[$entrada] = $usadas[$fin] = true;
            foreach ($compartidas as $instante => $valor) $usadas[$instante] = true;
        }
        unset($bloque);
        $adicionales = array_values(array_diff_key($candidatas, $usadas));
        foreach ($bloques as &$bloque) {
            if (!$bloque['revision']) $bloque['adicionales'] = $adicionales;
        }
        unset($bloque);
        return $bloques;
    }

    /** Compara una jornada con marcas intermedias sin ocultarlas ni resolver su significado. */
    private static function extremos_con_adicionales(array $bloque, array $candidatas)
    {
        $tiempos = array_keys($candidatas);
        if (count($tiempos) < 3) return null;
        $entrada = $tiempos[0];
        $fin = $tiempos[count($tiempos) - 1];
        foreach ($tiempos as $instante) {
            if ($instante !== $entrada && abs($instante - $bloque['entrada']) <= abs($entrada - $bloque['entrada'])) return null;
            if ($instante !== $fin && abs($instante - $bloque['salida']) <= abs($fin - $bloque['salida'])) return null;
        }
        $adicionales = array_slice($tiempos, 1, -1);
        foreach ($adicionales as $instante) {
            if ($instante < $bloque['entrada'] || $instante > $bloque['salida']) return null;
        }
        $bloque['marcas'] = ['entrada' => $entrada, 'salida' => $fin];
        $bloque['adicionales'] = array_map(function ($instante) use ($candidatas) { return $candidatas[$instante]; }, $adicionales);
        return $bloque;
    }

    /** Distribuye pares cronológicos sin omitir marcas ni elegir entre varias soluciones. */
    private static function asociar_pares(array $bloques, array $candidatas, array $jornadas, array $salida)
    {
        $tiempos = array_keys($candidatas);
        if (count($tiempos) % 2 !== 0) return null;
        $filas = [];
        $finAnterior = null;
        foreach ($bloques as $b => $bloque) {
            foreach ($bloque['ids'] as $id) {
                if ($finAnterior !== null && $jornadas[$id]['entrada'] < $finAnterior) return null;
                $finAnterior = $jornadas[$id]['salida'];
                $filas[] = ['id' => $id, 'bloque' => $b];
            }
        }
        $numeroPares = intdiv(count($tiempos), 2);
        if ($numeroPares < count($bloques) || $numeroPares > count($filas)) return null;
        $memo = [];
        $buscar = function ($posicion, $par) use (&$buscar, &$memo, $filas, $tiempos, $candidatas, $jornadas, $salida, $numeroPares) {
            $clave = $posicion . ':' . $par;
            if (isset($memo[$clave])) return $memo[$clave];
            if ($posicion === count($filas)) return $par === $numeroPares ? [[]] : [];
            if ($par >= $numeroPares) return [];
            $entrada = $tiempos[$par * 2];
            $fin = $tiempos[$par * 2 + 1];
            $primero = $filas[$posicion]['id'];
            $desde = substr($salida[$primero]['inicio'], 0, 10);
            $soluciones = [];
            $ids = [];
            for ($ultimo = $posicion; $ultimo < count($filas)
                && $filas[$ultimo]['bloque'] === $filas[$posicion]['bloque']; $ultimo++) {
                $id = $filas[$ultimo]['id'];
                $ids[] = $id;
                $hasta = substr($salida[$id]['fin'], 0, 10);
                if (substr($candidatas[$entrada], 0, 10) < $desde || substr($candidatas[$entrada], 0, 10) > $hasta
                    || substr($candidatas[$fin], 0, 10) < $desde || substr($candidatas[$fin], 0, 10) > $hasta) continue;
                // Los pares reales a cada lado de un corte deben respetar ese corte.
                if ($posicion > 0 && $filas[$posicion - 1]['bloque'] === $filas[$posicion]['bloque']
                    && $entrada < $jornadas[$primero]['entrada']) continue;
                if ($ultimo + 1 < count($filas) && $filas[$ultimo + 1]['bloque'] === $filas[$ultimo]['bloque']
                    && $fin > $jornadas[$id]['salida']) continue;
                // Un par continuo debe abarcar todos los cortes que pretende respaldar.
                if ($ultimo > $posicion && ($entrada >= $jornadas[$primero]['salida']
                    || $fin <= $jornadas[$id]['entrada'])) continue;
                $segmento = ['ids' => $ids, 'entrada' => $jornadas[$primero]['entrada'],
                    'salida' => $jornadas[$id]['salida'], 'marcas' => ['entrada' => $entrada, 'salida' => $fin]];
                foreach ($buscar($ultimo + 1, $par + 1) as $resto) {
                    $soluciones[] = array_merge([$segmento], $resto);
                    if (count($soluciones) === 2) return $memo[$clave] = $soluciones;
                }
            }
            return $memo[$clave] = $soluciones;
        };
        $soluciones = $buscar(0, 0);
        return count($soluciones) === 1 ? $soluciones[0] : null;
    }

    /** Firma evidencia consultada con una clave privada de sesión, nunca enviada al navegador. */
    public static function autorizar_evidencia(array $fila, $clave, $usuario) {
        $datos = [
            'usuario' => (int)$usuario, 'expira' => time() + 1200,
            'jornada' => $fila['jornada'], 'reporte' => $fila['reporte'],
            'empleado_id' => $fila['empleado_id'], 'version' => $fila['version_actual'],
            'inicio_actual' => $fila['inicio_actual'], 'fin_actual' => $fila['fin_actual'],
            'estado' => $fila['estado_actual'], 'snapshot_hash' => $fila['snapshot_hash'],
            'entrada' => ($fila['extremo_pendiente'] ?? '') === 'entrada' || !empty($fila['limite_compartido_entrada']) ? '' : $fila['entrada_bio'],
            'salida' => !empty($fila['corte_periodo_salida']) || ($fila['extremo_pendiente'] ?? '') === 'salida'
                || !empty($fila['limite_compartido_salida']) ? '' : $fila['salida_bio']
        ];
        $texto = base64_encode(json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        return $texto . '.' . hash_hmac('sha256', $texto, $clave);
    }

    /** Rechaza evidencias manipuladas, vencidas, de otra sesión/usuario o sin el extremo elegido. */
    public static function validar_evidencia($token, $extremo, $clave, $usuario) {
        if (!in_array($extremo, ['entrada', 'salida'], true)) {
            throw new InvalidArgumentException('Seleccione entrada o salida BioTime.');
        }
        $partes = explode('.', $token);
        if ($clave === '' || count($partes) !== 2
            || !hash_equals(hash_hmac('sha256', $partes[0], $clave), $partes[1])) {
            throw new RuntimeException('La evidencia no es válida. Ejecute nuevamente el cruce.');
        }
        $datos = json_decode(base64_decode($partes[0], true), true);
        if (!is_array($datos) || (int)($datos['usuario'] ?? 0) !== (int)$usuario
            || (int)($datos['expira'] ?? 0) < time() || empty($datos[$extremo])) {
            throw new RuntimeException('No hay evidencia vigente para este ajuste. Ejecute nuevamente el cruce.');
        }
        return $datos;
    }

    /** Valida la versión y el estado, y prepara exclusivamente el extremo solicitado. */
    public static function preparar_ajuste(array $actual, array $evidencia, $extremo) {
        if (!in_array($extremo, ['entrada', 'salida'], true) || empty($evidencia[$extremo])) {
            throw new InvalidArgumentException('No existe la marcación solicitada.');
        }
        if ((int)$actual['jornada_version'] !== (int)$evidencia['version']
            || $actual['je_codigo'] !== $evidencia['estado']
            || $actual['jornada_inicio'] !== $evidencia['inicio_actual']
            || $actual['jornada_fin'] !== $evidencia['fin_actual']) {
            throw new RuntimeException('La jornada cambió desde la consulta. Ejecute nuevamente el cruce.');
        }
        if (!in_array($actual['je_codigo'], ['APROBADO', 'PENDIENTE_LIQUIDACION', 'LIQUIDADO'], true)) {
            throw new RuntimeException('El estado actual de la jornada no admite este ajuste contable.');
        }
        $campo = $extremo === 'entrada' ? 'jornada_inicio' : 'jornada_fin';
        $zona = new DateTimeZone('America/Bogota');
        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $evidencia[$extremo], $zona);
        if (!$fecha || $fecha->format('Y-m-d H:i:s') !== $evidencia[$extremo]) {
            throw new InvalidArgumentException('La fecha de la evidencia no es válida.');
        }
        $nueva = $actual;
        $nueva[$campo] = $evidencia[$extremo];
        if (new DateTimeImmutable($nueva['jornada_fin'], $zona) <= new DateTimeImmutable($nueva['jornada_inicio'], $zona)) {
            throw new RuntimeException('El ajuste dejaría la salida anterior o igual a la entrada. Ajuste primero el otro extremo, si corresponde.');
        }
        return ['campo' => $campo, 'nueva' => $nueva];
    }

    /** Cambia un solo horario en transacción; versión, fecha y auditoría siguen el mecanismo existente. */
    public function ajustar_desde_biotime($token, $extremo, $clave, $usuario) {
        $evidencia = self::validar_evidencia($token, $extremo, $clave, $usuario);
        $db = parent::Conexion();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        try {
            $db->beginTransaction();
            // Misma serialización por empleado utilizada en las ediciones de Jornada.
            $stmt = $db->prepare('SELECT pg_advisory_xact_lock(?)');
            $stmt->execute([(int)$evidencia['empleado_id']]);
            $stmt = $db->prepare('SELECT j.*, e.je_codigo FROM jornadas_trabajo j
                INNER JOIN jornada_estados e ON e.je_id = j.jornada_estado_id
                WHERE j.jornada_id = ? AND j.empleado_id = ? FOR UPDATE OF j');
            $stmt->execute([(int)$evidencia['jornada'], (int)$evidencia['empleado_id']]);
            $actual = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$actual) {
                throw new RuntimeException('No se encontró la jornada correspondiente a la evidencia.');
            }
            // Verifica pertenencia y firma sin sobrescribir ni regenerar el snapshot histórico.
            $stmt = $db->prepare("SELECT d.jrd_snapshot, d.jrd_jornada_version
                FROM jornada_reporte_detalle d
                INNER JOIN jornada_reportes_firma r ON r.jrf_id = d.jrf_id
                WHERE d.jornada_id = ? AND d.jrf_id = ? AND r.empleado_id = ?
                  AND r.jrf_estado = 'FIRMADO' FOR SHARE OF d, r");
            $stmt->execute([$evidencia['jornada'], $evidencia['reporte'], $evidencia['empleado_id']]);
            $firmada = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$firmada || !hash_equals($evidencia['snapshot_hash'], hash('sha256', $firmada['jrd_snapshot']))) {
                throw new RuntimeException('El reporte firmado cambió. Ejecute nuevamente el cruce.');
            }
            $ajuste = self::preparar_ajuste($actual, $evidencia, $extremo);
            $campo = $ajuste['campo']; // Lista cerrada, nunca se recibe un nombre SQL del cliente.
            $nueva = $ajuste['nueva'];
            if ($actual[$campo] === $nueva[$campo]) {
                $db->commit();
                return ['jornada' => (int)$actual['jornada_id'], 'sin_cambios' => true];
            }
            // Conserva la regla vigente de no superponer jornadas activas del empleado.
            $stmt = $db->prepare("SELECT j.jornada_id FROM jornadas_trabajo j
                INNER JOIN jornada_estados e ON e.je_id = j.jornada_estado_id
                WHERE j.empleado_id = ? AND j.jornada_id <> ?
                  AND e.je_codigo NOT IN ('RECHAZADO', 'ANULADO')
                  AND j.jornada_inicio < CAST(? AS timestamp)
                  AND j.jornada_fin > CAST(? AS timestamp) LIMIT 1");
            $stmt->execute([$actual['empleado_id'], $actual['jornada_id'], $nueva['jornada_fin'], $nueva['jornada_inicio']]);
            if ($stmt->fetchColumn() !== false) {
                throw new RuntimeException('El ajuste se superpone con otra jornada registrada.');
            }
            $stmt = $db->prepare("UPDATE jornadas_trabajo SET $campo = ?,
                jornada_version = jornada_version + 1, jornada_fecha_actualizacion = CURRENT_TIMESTAMP
                WHERE jornada_id = ? AND jornada_version = ? RETURNING *");
            $stmt->execute([$nueva[$campo], $actual['jornada_id'], $actual['jornada_version']]);
            $guardada = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$guardada) {
                throw new RuntimeException('La jornada cambió durante el ajuste. Repita el cruce.');
            }
            $stmt = $db->prepare('INSERT INTO jornada_auditoria (jornada_id, jaud_accion,
                jaud_estado_anterior, jaud_estado_nuevo, jaud_datos_anteriores,
                jaud_datos_nuevos, jaud_motivo, jaud_usuario_id)
                VALUES (?, ?, ?, ?, CAST(? AS jsonb), CAST(? AS jsonb), ?, ?)');
            $datosNuevos = $guardada;
            $datosNuevos['evidencia_biotime'] = $evidencia;
            $datosNuevos['extremo_ajustado'] = $extremo;
            $datosNuevos['version_firmada'] = (int)$firmada['jrd_jornada_version'];
            $stmt->execute([$actual['jornada_id'], 'AJUSTAR_' . strtoupper($extremo) . '_BIOTIME_CONTABILIDAD',
                $actual['je_codigo'], $actual['je_codigo'],
                json_encode($actual, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                json_encode($datosNuevos, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'Ajuste posterior a la firma confirmado por Contabilidad con evidencia BioTime; snapshot firmado intacto.',
                (int)$usuario]);
            $db->commit();
            return ['jornada' => (int)$actual['jornada_id'], 'version_actual' => (int)$guardada['jornada_version'],
                'inicio_actual' => $guardada['jornada_inicio'], 'fin_actual' => $guardada['jornada_fin']];
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }
}
