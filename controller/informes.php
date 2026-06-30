<?php

require_once("../config/conexion.php");
require_once("../models/Informes.php");
require_once("curl.php");

$informes = new Informes();

function informes_json_response($payload, $status_code = 200) {
    http_response_code($status_code);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($payload);
    exit;
}

function informes_normalizar_documento($valor) {
    return preg_replace("/\D+/", "", trim((string)$valor));
}

function informes_formato_hora($valor) {
    if ($valor === null || $valor === "") {
        return "";
    }

    try {
        return (new DateTime((string)$valor))->format("H:i:s");
    } catch (Exception $e) {
        return (string)$valor;
    }
}

function informes_minutos_a_horas($minutos, $con_signo = false) {
    $minutos = (int)round($minutos);
    $signo = "";

    if ($con_signo) {
        if ($minutos > 0) {
            $signo = "+";
        } elseif ($minutos < 0) {
            $signo = "-";
        }
    }

    $abs = abs($minutos);
    $horas = floor($abs / 60);
    $mins = $abs % 60;

    return $signo . $horas . ":" . str_pad($mins, 2, "0", STR_PAD_LEFT);
}

function informes_valor_horas_a_minutos($valor) {
    if ($valor === null || $valor === "") {
        return null;
    }

    $valor = trim((string)$valor);
    if ($valor === "") {
        return null;
    }

    if (strpos($valor, ":") !== false) {
        $partes = explode(":", $valor);
        return ((int)($partes[0] ?? 0) * 60) + (int)($partes[1] ?? 0);
    }

    $normalizado = str_replace(",", ".", $valor);
    if (is_numeric($normalizado)) {
        return (int)round(((float)$normalizado) * 60);
    }

    return null;
}

function informes_calcular_minutos_permiso($row) {
    $minutos = informes_valor_horas_a_minutos($row["permiso_total_horas"] ?? null);
    if ($minutos !== null && $minutos > 0) {
        return $minutos;
    }

    $fecha = $row["permiso_fecha"] ?? "";
    $salida = $row["permiso_hora_salida"] ?? "";
    $ingreso = $row["permiso_hora_entrada"] ?? "";

    if ($fecha === "" || $salida === "" || $ingreso === "") {
        return 0;
    }

    try {
        $inicio = new DateTime($fecha . " " . informes_formato_hora($salida));
        $fin = new DateTime($fecha . " " . informes_formato_hora($ingreso));

        if ($fin < $inicio && (int)($row["permiso_turno_nocturno"] ?? 0) === 1) {
            $fin->modify("+1 day");
        }

        if ($fin < $inicio) {
            return 0;
        }

        return (int)floor(($fin->getTimestamp() - $inicio->getTimestamp()) / 60);
    } catch (Exception $e) {
        return 0;
    }
}

function informes_calcular_minutos_biotime($fecha, $ingreso, $salida) {
    if ($ingreso === null || $salida === null || $ingreso === "" || $salida === "") {
        return null;
    }

    try {
        $inicio = new DateTime($fecha . " " . informes_formato_hora($ingreso));
        $fin = new DateTime($fecha . " " . informes_formato_hora($salida));

        if ($fin < $inicio) {
            $fin->modify("+1 day");
        }

        return (int)floor(($fin->getTimestamp() - $inicio->getTimestamp()) / 60);
    } catch (Exception $e) {
        return null;
    }
}

function informes_resumen_biotime_dia($fecha, $horas) {
    $horas = array_values(array_filter(array_unique((array)$horas)));
    sort($horas);

    if (count($horas) === 0) {
        return [
            "ingreso" => null,
            "salida" => null,
            "minutos" => null
        ];
    }

    if (count($horas) === 1) {
        return [
            "ingreso" => informes_formato_hora($horas[0]),
            "salida" => null,
            "minutos" => null
        ];
    }

    $ingreso = informes_formato_hora($horas[0]);
    $salida = informes_formato_hora(end($horas));

    return [
        "ingreso" => $ingreso,
        "salida" => $salida,
        "minutos" => informes_calcular_minutos_biotime($fecha, $ingreso, $salida)
    ];
}

function informes_consultar_biotime_mes($fecha_inicio, $fecha_fin, $documento) {
    $documento = informes_normalizar_documento($documento);
    $marcaciones = [];
    $pagina = 1;
    $tam_pag = 1000;

    do {
        $url = "start_date=" . urlencode($fecha_inicio);
        $url .= "&end_date=" . urlencode($fecha_fin);
        $url .= "&departments=1";
        $url .= "&areas=2";
        $url .= "&page={$pagina}";
        $url .= "&page_size={$tam_pag}";

        $response = CurlController::requestBiotime($url, "GET");

        if (isset($response->data) && is_array($response->data)) {
            foreach ($response->data as $row) {
                $emp_code = informes_normalizar_documento($row->emp_code ?? "");

                if ($documento !== "" && $emp_code !== $documento) {
                    continue;
                }

                $fecha = "";
                if (!empty($row->att_date)) {
                    $fecha = date("Y-m-d", strtotime($row->att_date));
                }

                $hora = informes_formato_hora($row->punch_time ?? "");

                if ($emp_code === "" || $fecha === "" || $hora === "") {
                    continue;
                }

                if (!isset($marcaciones[$emp_code][$fecha])) {
                    $marcaciones[$emp_code][$fecha] = [];
                }

                $marcaciones[$emp_code][$fecha][] = $hora;
            }
        }

        $total_registros = isset($response->count) ? (int)$response->count : 0;
        $total_paginas = ($total_registros > 0) ? (int)ceil($total_registros / $tam_pag) : 1;
        $pagina++;
    } while ($pagina <= $total_paginas);

    foreach ($marcaciones as $doc => $fechas) {
        foreach ($fechas as $fecha => $horas) {
            $horas = array_values(array_unique($horas));
            sort($horas);
            $marcaciones[$doc][$fecha] = $horas;
        }
    }

    return $marcaciones;
}

try {
    $op = $_REQUEST["op"] ?? "";

    switch ($op) {
        case "listarColaboradoresPermisosMes":
            $datos = $informes->listar_colaboradores_permisos_mes();
            $data = [];

            foreach ($datos as $row) {
                $documento = $row["cedu_empl"] ?? "";
                $nombre = $row["nomb_empl"] ?? "";

                $data[] = [
                    "id" => $row["id_empl"],
                    "text" => trim($nombre),
                    "documento" => $documento,
                    "nombre" => $nombre
                ];
            }

            informes_json_response([
                "success" => true,
                "data" => $data
            ]);
            break;

        case "consultaPermisosMes":
            $empleado_id = (int)($_POST["empleado_id"] ?? 0);
            $periodo = trim((string)($_POST["periodo"] ?? ""));

            if ($empleado_id <= 0) {
                informes_json_response([
                    "success" => false,
                    "error" => "Debe seleccionar un colaborador."
                ], 400);
            }

            if (!preg_match("/^\d{4}-\d{2}$/", $periodo)) {
                informes_json_response([
                    "success" => false,
                    "error" => "Debe seleccionar un mes y anio validos."
                ], 400);
            }

            $fecha_inicio = $periodo . "-01";
            $fecha_fin = date("Y-m-t", strtotime($fecha_inicio));

            $colaborador = $informes->get_colaborador_permisos_mes($empleado_id);
            if (!$colaborador) {
                informes_json_response([
                    "success" => false,
                    "error" => "No se encontro el colaborador seleccionado."
                ], 404);
            }

            $permisos = $informes->get_permisos_colaborador_mes($empleado_id, $fecha_inicio, $fecha_fin);
            $documento = informes_normalizar_documento($colaborador["cedu_empl"] ?? "");
            $marcaciones = informes_consultar_biotime_mes($fecha_inicio, $fecha_fin, $documento);

            $detalle = [];
            $total_minutos_permiso = 0;

            foreach ($permisos as $row) {
                $fecha_permiso = !empty($row["permiso_fecha"])
                    ? date("Y-m-d", strtotime($row["permiso_fecha"]))
                    : "";

                $doc = informes_normalizar_documento($row["cedu_empl"] ?? "");
                $horas_biotime = ($doc !== "" && $fecha_permiso !== "" && isset($marcaciones[$doc][$fecha_permiso]))
                    ? $marcaciones[$doc][$fecha_permiso]
                    : [];

                $salida_permiso = informes_formato_hora($row["permiso_hora_salida"] ?? "");
                $ingreso_permiso = informes_formato_hora($row["permiso_hora_entrada"] ?? "");

                $biotime_dia = informes_resumen_biotime_dia($fecha_permiso, $horas_biotime);
                $ingreso_biotime = $biotime_dia["ingreso"];
                $salida_biotime = $biotime_dia["salida"];

                $minutos_permiso = informes_calcular_minutos_permiso($row);
                $minutos_biotime = $biotime_dia["minutos"];
                $total_minutos_permiso += $minutos_permiso;

                $detalle[] = [
                    "fecha_permiso" => $fecha_permiso,
                    "hora_salida_permiso" => $salida_permiso,
                    "hora_ingreso_permiso" => $ingreso_permiso,
                    "hora_salida_biotime" => $salida_biotime ?: "Sin registro",
                    "hora_ingreso_biotime" => $ingreso_biotime ?: "Sin registro",
                    "total_horas_permiso" => informes_minutos_a_horas($minutos_permiso),
                    "total_horas_biotime" => ($minutos_biotime !== null) ? informes_minutos_a_horas($minutos_biotime) : "Sin registro",
                    "diferencia_horas" => ($minutos_biotime !== null) ? informes_minutos_a_horas($minutos_biotime - $minutos_permiso, true) : "Sin registro",
                    "motivo" => $row["motivo"] ?? "",
                    "estado" => $row["estado_permiso"] ?? "Sin estado",
                    "estado_codigo" => $row["permiso_estado"] ?? ""
                ];
            }

            informes_json_response([
                "success" => true,
                "resumen" => [
                    "colaborador" => $colaborador["nomb_empl"] ?? "",
                    "documento" => $colaborador["cedu_empl"] ?? "",
                    "total_permisos" => count($detalle),
                    "total_horas" => informes_minutos_a_horas($total_minutos_permiso),
                    "periodo" => $periodo
                ],
                "detalle" => $detalle,
                "message" => count($detalle) === 0 ? "No se encontraron permisos para el colaborador en el mes seleccionado." : ""
            ]);
            break;

        default:
            informes_json_response([
                "success" => false,
                "error" => "Operacion no valida."
            ], 400);
            break;
    }
} catch (Exception $e) {
    informes_json_response([
        "success" => false,
        "error" => "No se pudo procesar la consulta."
    ], 500);
}
