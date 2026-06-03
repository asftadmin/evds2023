<?php

require_once("../config/conexion.php");
require_once("../models/BioPro.php");
require_once("curl.php");

$biopro = new BioPro();


switch ($_GET["op"]) {

    case "comboEmpleadosActivos":
        $datos = $biopro->listarEmpleadosActivos();

        $html = '<option value="">Todos los empleados</option>';
        if (is_array($datos) && count($datos) > 0) {
            foreach ($datos as $row) {
                $cedula = htmlspecialchars($row['cedu_empl'] ?? '', ENT_QUOTES, 'UTF-8');
                $nombre = htmlspecialchars($row['nomb_empl'] ?? '', ENT_QUOTES, 'UTF-8');
                $html .= '<option value="' . $cedula . '">' . $nombre . '</option>';
            }
        }

        echo $html;
        break;

    case "listarAsistenciaBioPro":

        $data = array();
        $pagina = 1;
        $tamPag = 1000;

        $fechainicio = $_GET['fechainicio'] ?? date('Y-m-d');
        $fechafin    = $_GET['fechafin'] ?? date('Y-m-d');

        // Filtro por empleado (solo dígitos)
        $empleadoFiltro = $_GET['empleado'] ?? '';
        $empleadoFiltro = preg_replace('/\D+/', '', trim((string)$empleadoFiltro));

        $marcaciones = [];

        do {

            $url  = "start_date={$fechainicio}";
            $url .= "&end_date={$fechafin}";
            $url .= "&departments=1";
            $url .= "&areas=2";
            $url .= "&page={$pagina}";
            $url .= "&page_size={$tamPag}";

            $response = CurlController::requestBiotime($url, "GET");

            if (isset($response->data) && is_array($response->data)) {

                foreach ($response->data as $row) {

                    $emp_code = preg_replace('/\D+/', '', (string)($row->emp_code ?? ''));
                    $fecha    = $row->att_date ?? '';
                    $hora     = $row->punch_time ?? '';

                    if ($emp_code === '' || $fecha === '' || $hora === '') {
                        continue;
                    }

                    // Si hay filtro por empleado, solo guardo ese emp_code (optimiza y evita problemas)
                    if ($empleadoFiltro !== '' && $emp_code !== $empleadoFiltro) {
                        continue;
                    }

                    $marcaciones[$emp_code][$fecha][] = $hora;
                }
            }

            $totalRegistros = $response->count ?? 0;
            $totalPaginas = ($totalRegistros > 0) ? ceil($totalRegistros / $tamPag) : 1;

            $pagina++;
        } while ($pagina <= $totalPaginas);


        // Cruce con empleados activos
        foreach ($marcaciones as $emp_code => $fechas) {

            $empleado = $biopro->obtenerEmpleadoActivoPorDocumento($emp_code);

            if (!$empleado || !is_array($empleado)) {
                continue;
            }

            foreach ($fechas as $fecha => $horas) {

                sort($horas);

                $entrada = $horas[0];
                $salida  = end($horas);

                $entradaDate = new DateTime($fecha . ' ' . $entrada);
                $salidaDate  = new DateTime($fecha . ' ' . $salida);

                $interval = $entradaDate->diff($salidaDate);
                $tiempoBruto = $interval->format('%H:%I');

                $data[] = array(
                    $fecha,
                    $empleado['nomb_empl'],
                    $entrada,
                    $salida,
                    $tiempoBruto,
                    $tiempoBruto
                );
            }
        }

        echo json_encode(array(
            "sEcho" => 1,
            "iTotalRecords" => count($data),
            "iTotalDisplayRecords" => count($data),
            "aaData" => $data
        ));

        break;

    case "dashboardAsistencia":


        $fechainicio = $_GET['fechainicio'] ?? date('Y-m-d');
        $fechafin    = $_GET['fechafin'] ?? date('Y-m-d');
        $empleadoFiltro = preg_replace('/\D/', '', ($_GET['empleado'] ?? ''));
        $metrica = trim($_GET['metrica'] ?? 'arrival_hist');

        $horaObjetivo = "08:00";
        $toleranciaMin = 5;
        $horaLimite = date('H:i', strtotime($horaObjetivo . " +{$toleranciaMin} minutes"));

        $docsActivos = $biopro->listarDocumentosActivos();      // array de cedu_empl

        // limpiar: solo strings/enteros no vacíos
        $docsActivos = array_values(array_filter($docsActivos, function ($v) {
            return (is_string($v) || is_int($v)) && trim((string)$v) !== '';
        }));

        $idxActivo = array_flip($docsActivos);

        $pagina = 1;
        $tamPag = 1000;

        $marcaciones = [];              // [emp][fecha] = [horas...]
        $eventosAPI = [];               // para ausentismo por area
        $empArea = [];                  // [emp] => cost_centers_name
        $presentesPorFechaArea = [];    // [fecha][area][emp] => true

        do {

            $url  = "start_date={$fechainicio}";
            $url .= "&end_date={$fechafin}";
            $url .= "&departments=1";
            $url .= "&areas=2";
            $url .= "&page={$pagina}";
            $url .= "&page_size={$tamPag}";

            $response = CurlController::requestBiotime($url, "GET");

            if (isset($response->data) && is_array($response->data)) {

                foreach ($response->data as $row) {

                    $emp_code = $row->emp_code ?? null;
                    $fecha    = $row->att_date ?? null;
                    $hora     = $row->punch_time ?? null;

                    if (!$emp_code || !$fecha || !$hora) {
                        continue;
                    }

                    if ($empleadoFiltro !== '' && $emp_code !== $empleadoFiltro) {
                        continue;
                    }

                    if (!isset($idxActivo[$emp_code])) {
                        continue;
                    }

                    $marcaciones[$emp_code][$fecha][] = $hora;

                    if ($metrica === 'absenteeism_by_area') {
                        $eventosAPI[] = $row;

                        $area = trim($row->cost_centers_name ?? '');
                        if ($area === '') {
                            $area = 'SIN AREA';
                        }

                        $empArea[$emp_code] = $area;
                        $presentesPorFechaArea[$fecha][$area][$emp_code] = true;
                    }
                }
            }

            $totalRegistros = $response->count ?? 0;
            $totalPaginas = ($totalRegistros > 0) ? ceil($totalRegistros / $tamPag) : 1;

            $pagina++;
        } while ($pagina <= $totalPaginas);


        header('Content-Type: application/json');

        /*         if ($metrica === 'arrival_hist') {

            $entradas = [];

            foreach ($marcaciones as $emp => $fechas) {
                foreach ($fechas as $fecha => $horas) {
                    sort($horas);
                    $entradas[] = $horas[0];
                }
            }

            $buckets = [
                '07:00-07:30' => 0,
                '07:30-08:00' => 0,
                '08:00-08:30' => 0,
                '08:30-09:00' => 0,
                '09:00+'      => 0,
            ];

            foreach ($entradas as $h) {
                if ($h >= '07:00' && $h < '07:30') $buckets['07:00-07:30']++;
                else if ($h >= '07:30' && $h < '08:00') $buckets['07:30-08:00']++;
                else if ($h >= '08:00' && $h < '08:30') $buckets['08:00-08:30']++;
                else if ($h >= '08:30' && $h < '09:00') $buckets['08:30-09:00']++;
                else $buckets['09:00+']++;
            }

            echo json_encode([
                'success' => true,
                'labels'  => array_keys($buckets),
                'values'  => array_values($buckets),
            ]);
            exit;
        } */

        /*         if ($metrica === 'arrival_hist') {

            if ($empleadoFiltro === '') {
                echo json_encode([
                    'success' => false,
                    'error' => 'Debe ingresar un empleado para esta métrica.'
                ]);
                exit;
            }

            $diasHabiles = [];

            $start = new DateTime($fechainicio);
            $end = new DateTime($fechafin);
            $end->modify('+1 day');

            $period = new DatePeriod($start, new DateInterval('P1D'), $end);

            foreach ($period as $dt) {
                $diaSemana = (int) $dt->format('N');

                if ($diaSemana >= 1 && $diaSemana <= 5) {
                    $diasHabiles[] = $dt->format('Y-m-d');
                }
            }

            $labels = [];
            $entradasMin = [];
            $objetivoMin = [];
            $entradasTexto = [];

            $horaObjetivoMin = 8 * 60;

            foreach ($diasHabiles as $fecha) {

                $labels[] = $fecha;
                $objetivoMin[] = $horaObjetivoMin;

                if (!isset($marcaciones[$empleadoFiltro][$fecha])) {
                    $entradasMin[] = null;
                    $entradasTexto[] = 'Sin marcación';
                    continue;
                }

                $horas = $marcaciones[$empleadoFiltro][$fecha];
                sort($horas);

                $entrada = $horas[0];

                $partes = explode(':', $entrada);
                $hora = (int) $partes[0];
                $minuto = (int) $partes[1];

                $entradaMin = ($hora * 60) + $minuto;

                $entradasMin[] = $entradaMin;
                $entradasTexto[] = $entrada;
            }

            echo json_encode([
                'success' => true,
                'labels' => $labels,
                'entradas_min' => $entradasMin,
                'objetivo_min' => $objetivoMin,
                'entradas_texto' => $entradasTexto,
                'hora_objetivo' => '08:00'
            ]);
            exit;
        }

        if ($metrica === 'punctuality_rate') {

            $diasHabiles = [];

            $start = new DateTime($fechainicio);
            $end = new DateTime($fechafin);
            $end->modify('+1 day');

            $period = new DatePeriod($start, new DateInterval('P1D'), $end);

            foreach ($period as $dt) {
                $diaSemana = (int) $dt->format('N'); // 1 lunes, 7 domingo

                if ($diaSemana >= 1 && $diaSemana <= 5) {
                    $diasHabiles[] = $dt->format('Y-m-d');
                }
            }

            $totalDiasHabiles = count($diasHabiles);

            $empleadosEvaluados = [];

            if ($empleadoFiltro !== '') {
                if (isset($idxActivo[$empleadoFiltro])) {
                    $empleadosEvaluados[] = $empleadoFiltro;
                }
            } else {
                $empleadosEvaluados = array_keys($idxActivo);
            }

            $totalLaborables = count($empleadosEvaluados) * $totalDiasHabiles;

            $diasATiempo = 0;
            $diasTarde = 0;
            $diasSinMarcacion = 0;

            foreach ($empleadosEvaluados as $emp) {

                foreach ($diasHabiles as $fecha) {

                    if (!isset($marcaciones[$emp][$fecha])) {
                        $diasSinMarcacion++;
                        continue;
                    }

                    $horas = $marcaciones[$emp][$fecha];
                    sort($horas);

                    $entrada = $horas[0];

                    if ($entrada <= $horaLimite) {
                        $diasATiempo++;
                    } else {
                        $diasTarde++;
                    }
                }
            }

            $rate = ($totalLaborables > 0)
                ? round(($diasATiempo / $totalLaborables) * 100, 1)
                : 0;

            echo json_encode([
                'success' => true,
                'rate' => $rate,
                'total' => $totalLaborables,
                'dias_a_tiempo' => $diasATiempo,
                'late' => $diasTarde,
                'dias_sin_marcacion' => $diasSinMarcacion,
                'dias_habiles' => $totalDiasHabiles,
                'hora_limite' => $horaLimite
            ]);
            exit;
        } */

        if ($metrica === 'arrival_hist') {

            if ($empleadoFiltro === '') {
                echo json_encode([
                    'success' => false,
                    'error' => 'Debe ingresar un empleado para esta métrica.'
                ]);
                exit;
            }

            $diasHabiles = [];

            $start = new DateTime($fechainicio);
            $end = new DateTime($fechafin);
            $end->modify('+1 day');

            $period = new DatePeriod($start, new DateInterval('P1D'), $end);

            foreach ($period as $dt) {
                $diaSemana = (int) $dt->format('N');

                if ($diaSemana >= 1 && $diaSemana <= 5) {
                    $diasHabiles[] = $dt->format('Y-m-d');
                }
            }

            $labels = [];
            $entradasMin = [];
            $objetivoMin = [];
            $entradasTexto = [];
            $pointColors = [];
            $detalle = [];

            $horaObjetivoMin = 8 * 60;
            $horaLimiteMin = (8 * 60) + 5;

            foreach ($diasHabiles as $fecha) {

                $labels[] = $fecha;
                $objetivoMin[] = $horaObjetivoMin;

                if (!isset($marcaciones[$empleadoFiltro][$fecha])) {

                    $entradasMin[] = null;
                    $entradasTexto[] = 'Sin marcación';
                    $pointColors[] = '#6c757d';

                    $detalle[] = [
                        'fecha' => $fecha,
                        'entrada' => '---',
                        'estado' => 'SIN MARCACION'
                    ];

                    continue;
                }

                $horas = $marcaciones[$empleadoFiltro][$fecha];
                sort($horas);

                $entrada = $horas[0];

                $partes = explode(':', $entrada);
                $hora = (int) $partes[0];
                $minuto = (int) $partes[1];

                $entradaMin = ($hora * 60) + $minuto;

                $estado = ($entradaMin <= $horaLimiteMin) ? 'A TIEMPO' : 'TARDE';
                $color = ($entradaMin <= $horaLimiteMin) ? '#28a745' : '#dc3545';

                $entradasMin[] = $entradaMin;
                $entradasTexto[] = $entrada;
                $pointColors[] = $color;

                $detalle[] = [
                    'fecha' => $fecha,
                    'entrada' => $entrada,
                    'estado' => $estado
                ];
            }

            echo json_encode([
                'success' => true,
                'labels' => $labels,
                'entradas_min' => $entradasMin,
                'objetivo_min' => $objetivoMin,
                'entradas_texto' => $entradasTexto,
                'point_colors' => $pointColors,
                'detalle' => $detalle,
                'hora_objetivo' => '08:00',
                'hora_limite' => '08:05'
            ]);
            exit;
        }

        if ($metrica === 'punctuality_rate') {

            $diasHabiles = [];

            $start = new DateTime($fechainicio);
            $end = new DateTime($fechafin);
            $end->modify('+1 day');

            $period = new DatePeriod($start, new DateInterval('P1D'), $end);

            foreach ($period as $dt) {
                $diaSemana = (int) $dt->format('N'); // 1 lunes, 7 domingo

                if ($diaSemana >= 1 && $diaSemana <= 5) {
                    $diasHabiles[] = $dt->format('Y-m-d');
                }
            }

            $totalDiasHabiles = count($diasHabiles);
            $empleadosEvaluados = [];

            if ($empleadoFiltro !== '') {
                if (isset($idxActivo[$empleadoFiltro])) {
                    $empleadosEvaluados[] = $empleadoFiltro;
                }
            } else {
                $empleadosEvaluados = array_keys($idxActivo);
            }

            $totalLaborables = count($empleadosEvaluados) * $totalDiasHabiles;
            $diasATiempo = 0;
            $diasTarde = 0;
            $diasSinMarcacion = 0;

            foreach ($empleadosEvaluados as $emp) {
                foreach ($diasHabiles as $fecha) {

                    if (!isset($marcaciones[$emp][$fecha])) {
                        $diasSinMarcacion++;
                        continue;
                    }

                    $horas = $marcaciones[$emp][$fecha];
                    sort($horas);

                    $entrada = $horas[0];

                    if ($entrada <= $horaLimite) {
                        $diasATiempo++;
                    } else {
                        $diasTarde++;
                    }
                }
            }

            $rate = ($totalLaborables > 0)
                ? round(($diasATiempo / $totalLaborables) * 100, 1)
                : 0;

            echo json_encode([
                'success' => true,
                'rate' => $rate,
                'total' => $totalLaborables,
                'dias_a_tiempo' => $diasATiempo,
                'late' => $diasTarde,
                'dias_sin_marcacion' => $diasSinMarcacion,
                'dias_habiles' => $totalDiasHabiles,
                'hora_limite' => $horaLimite
            ]);
            exit;
        }

        /*         if ($metrica === 'punctuality_rate') {

            $total = 0;
            $late  = 0;

            foreach ($marcaciones as $emp => $fechas) {
                foreach ($fechas as $fecha => $horas) {
                    sort($horas);
                    $entrada = $horas[0];
                    $total++;
                    if ($entrada > $horaLimite) {
                        $late++;
                    }
                }
            }

            $rate = ($total > 0) ? round((($total - $late) / $total) * 100, 1) : 0;

            echo json_encode([
                'success' => true,
                'rate'    => $rate,
                'total'   => $total,
                'late'    => $late,
                'hora_limite' => $horaLimite
            ]);
            exit;
        } */

        if ($metrica === 'hours_by_employee') {

            $horasTotalesMin = []; // [emp] => minutos

            foreach ($marcaciones as $emp => $fechas) {
                foreach ($fechas as $fecha => $horas) {
                    sort($horas);
                    $entrada = $horas[0];
                    $salida  = end($horas);

                    $ini = new DateTime($fecha . " " . $entrada);
                    $fin = new DateTime($fecha . " " . $salida);

                    $diff = $ini->diff($fin);
                    $minutos = ($diff->h * 60) + $diff->i;

                    if (!isset($horasTotalesMin[$emp])) {
                        $horasTotalesMin[$emp] = 0;
                    }
                    $horasTotalesMin[$emp] += $minutos;
                }
            }

            $rows = [];
            foreach ($horasTotalesMin as $emp => $min) {

                $empleado = $biopro->obtenerEmpleadoActivoPorDocumento($emp);
                if (!$empleado) {
                    continue;
                }

                $h = floor($min / 60);
                $m = $min % 60;

                $rows[] = [
                    'nombre'      => $empleado['nomb_empl'],
                    'documento'   => $emp,
                    'total_horas' => sprintf('%02d:%02d', $h, $m),
                    'total_min'   => $min
                ];
            }

            usort($rows, function ($a, $b) {
                return $b['total_min'] <=> $a['total_min'];
            });

            foreach ($rows as &$r) {
                unset($r['total_min']);
            }

            echo json_encode([
                'success' => true,
                'rows'    => $rows
            ]);
            exit;
        }

        if ($metrica === 'absenteeism_by_area') {

            $start = new DateTime($fechainicio);
            $end = new DateTime($fechafin);
            $end->modify('+1 day');
            $period = new DatePeriod($start, new DateInterval('P1D'), $end);

            $activosPorArea = []; // [area][emp] = true
            foreach ($empArea as $emp => $area) {
                if (!isset($activosPorArea[$area])) {
                    $activosPorArea[$area] = [];
                }
                $activosPorArea[$area][$emp] = true;
            }

            $presentByArea = [];
            $absentByArea  = [];

            foreach ($activosPorArea as $area => $emps) {

                $presentByArea[$area] = 0;
                $absentByArea[$area]  = 0;

                foreach ($period as $dt) {

                    $f = $dt->format('Y-m-d');

                    $totalActivosArea = count($emps);
                    $presentesArea = 0;

                    if (isset($presentesPorFechaArea[$f][$area])) {
                        $presentesArea = count($presentesPorFechaArea[$f][$area]);
                    }

                    $presentByArea[$area] += $presentesArea;
                    $absentByArea[$area]  += max(0, $totalActivosArea - $presentesArea);
                }
            }

            arsort($absentByArea);
            $labels = array_keys($absentByArea);

            $presentData = [];
            $absentData  = [];
            foreach ($labels as $area) {

                $presentes = $presentByArea[$area] ?? 0;
                $ausentes  = $absentByArea[$area] ?? 0;

                $total = $presentes + $ausentes;

                if ($total > 0) {

                    $porcPresentes = round(($presentes / $total) * 100, 1);
                    $porcAusentes  = round(($ausentes / $total) * 100, 1);
                } else {

                    $porcPresentes = 0;
                    $porcAusentes  = 0;
                }

                $presentData[] = $porcPresentes;
                $absentData[] = $porcAusentes;
            }

            echo json_encode([
                'success' => true,
                'labels'  => $labels,
                'series'  => [
                    ['label' => 'Presentes', 'data' => $presentData],
                    ['label' => 'Ausentes',  'data' => $absentData],
                ]
            ]);
            exit;
        }

        echo json_encode(['success' => false, 'error' => 'Metrica no soportada']);
        break;
}
