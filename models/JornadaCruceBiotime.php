<?php

/** Cruce independiente y ajustes explícitos auditados; conserva firmas y cálculos contables. */
class JornadaCruceBiotime extends Conectar
{
    public function reportes($desde, $hasta, $empleado = null)
    {
        $sql = "SELECT d.jrd_id, d.jrf_id, d.jornada_id, d.jrd_jornada_version,
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
                  AND (d.jrd_snapshot->>'jornada_inicio')::timestamp::date
                      BETWEEN CAST(:desde AS date) AND CAST(:hasta AS date)";
        if ($empleado !== null) {
            $sql .= ' AND r.empleado_id = :empleado';
        }
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
    public static function comparar(array $filas, array $marcaciones, $tolerancia)
    {
        $zona = new DateTimeZone('America/Bogota');
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
                'marcaciones' => [], 'detalle' => '',
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
            // Agrupa los días compartidos, incluyendo ambos días de un turno nocturno.
            // No descarta marcaciones por su distancia en horas al horario firmado.
            $grupos = [];
            foreach ($jornadas as $i => $intervalo) {
                $desde = substr($salida[$i]['inicio'], 0, 10);
                $hasta = substr($salida[$i]['fin'], 0, 10);
                $ultimo = count($grupos) - 1;
                if ($ultimo < 0 || $desde > $grupos[$ultimo]['hasta']) {
                    $grupos[] = ['desde' => $desde, 'hasta' => $hasta, 'ids' => [$i]];
                } else {
                    $grupos[$ultimo]['hasta'] = max($grupos[$ultimo]['hasta'], $hasta);
                    $grupos[$ultimo]['ids'][] = $i;
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
                $numeroJornadas = count($grupo['ids']);
                $asignaciones = [];
                $revision = false;
                if ($numeroJornadas === 1 && $cantidad === 1) {
                    // Una evidencia única puede ser entrada o salida; jamás constituye un par.
                    $i = $grupo['ids'][0];
                    $extremo = abs($tiempos[0] - $jornadas[$i]['salida']) < abs($tiempos[0] - $jornadas[$i]['entrada'])
                        ? 'salida' : 'entrada';
                    $asignaciones[$i] = [$extremo => $tiempos[0]];
                } elseif ($cantidad === 2 * $numeroJornadas) {
                    // Cada par cronológico se consume una sola vez, incluso si está lejos del horario reportado.
                    $finAnterior = null;
                    foreach ($grupo['ids'] as $posicion => $i) {
                        $entrada = $tiempos[$posicion * 2];
                        $fin = $tiempos[$posicion * 2 + 1];
                        $diaEntrada = substr($candidatas[$entrada], 0, 10);
                        $diaSalida = substr($candidatas[$fin], 0, 10);
                        $desde = substr($salida[$i]['inicio'], 0, 10);
                        $hasta = substr($salida[$i]['fin'], 0, 10);
                        // Jornadas superpuestas o pares de otros días no permiten una asociación segura.
                        if (($finAnterior !== null && $jornadas[$i]['entrada'] < $finAnterior)
                            || $diaEntrada < $desde || $diaEntrada > $hasta
                            || $diaSalida < $desde || $diaSalida > $hasta) {
                            $revision = true;
                        }
                        $finAnterior = $jornadas[$i]['salida'];
                        $asignaciones[$i] = ['entrada' => $entrada, 'salida' => $fin];
                    }
                } elseif ($cantidad > 0) {
                    // Sin tipos de entrada/salida fiables, sobrantes o pares faltantes requieren revisión.
                    $revision = true;
                }
                foreach ($grupo['ids'] as $i) {
                    $item = &$salida[$i];
                    $item['marcaciones'] = array_values($candidatas);
                    if ($revision) {
                        $item['resultado'] = 'Revisión de marcaciones';
                        $item['detalle'] .= 'Las marcaciones disponibles no forman pares inequívocos para las jornadas firmadas. Se conservan todas para revisión manual.';
                    } else {
                        $elegidas = $asignaciones[$i] ?? [];
                        foreach ($elegidas as $extremo => $instante) {
                            $item[$extremo . '_bio'] = $candidatas[$instante];
                            $item['diferencia_' . $extremo] = round(($instante - $jornadas[$i][$extremo]) / 60, 2);
                        }
                        if (!$elegidas) {
                            $item['resultado'] = 'Sin marcaciones';
                            $item['detalle'] .= 'BioTime no devolvió marcaciones para los días de esta jornada. No implica ausencia laboral.';
                        } elseif (count($elegidas) === 1) {
                            $item['resultado'] = 'Marcación incompleta';
                        } else {
                            // Compara solo después de asociar: la tolerancia nunca elimina evidencias.
                            $item['resultado'] = abs($elegidas['entrada'] - $jornadas[$i]['entrada']) <= $tolerancia * 60
                                && abs($elegidas['salida'] - $jornadas[$i]['salida']) <= $tolerancia * 60
                                ? 'Horario correcto' : 'Diferencia de horario';
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
        usort($salida, function ($a, $b) {
            return [$a['empleado_id'], $a['inicio'], $a['reporte'], $a['jornada']]
                <=> [$b['empleado_id'], $b['inicio'], $b['reporte'], $b['jornada']];
        });
        return $salida;
    }

    /** Firma evidencia consultada con una clave privada de sesión, nunca enviada al navegador. */
    public static function autorizar_evidencia(array $fila, $clave, $usuario) {
        $datos = [
            'usuario' => (int)$usuario, 'expira' => time() + 1200,
            'jornada' => $fila['jornada'], 'reporte' => $fila['reporte'],
            'empleado_id' => $fila['empleado_id'], 'version' => $fila['version_actual'],
            'inicio_actual' => $fila['inicio_actual'], 'fin_actual' => $fila['fin_actual'],
            'estado' => $fila['estado_actual'], 'snapshot_hash' => $fila['snapshot_hash'],
            'entrada' => $fila['entrada_bio'], 'salida' => $fila['salida_bio']
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
