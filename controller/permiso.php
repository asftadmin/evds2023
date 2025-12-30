<?php

require_once("../config/conexion.php");
require_once("../models/Permiso.php");
require_once("../models/Firma.php");


$permiso = new Permiso();


/**
 * Genera un nombre único basado en timestamp
 */
/* function generarNombreUnico($nombre_original)
{
    $info = pathinfo($nombre_original);
    $base  = $info['filename'];
    $ext   = isset($info['extension']) ? "." . $info['extension'] : "";
    return $base . "_" . date("Ymd_His") . $ext;
} */


function ftp_mksubdirs_safe($ftp, $path) {
    $parts = explode('/', trim($path, '/'));
    $fullpath = "";

    foreach ($parts as $part) {

        if ($part == "") continue;

        $fullpath .= "/" . $part;

        // Intentar cambiar
        if (@ftp_chdir($ftp, $fullpath)) {
            // Existe → regresar a raíz y seguir
            ftp_chdir($ftp, "/");
            continue;
        }

        // Si no existe → intentar crearlo
        if (!@ftp_mkdir($ftp, $fullpath)) {
            return false; // No se pudo crear
        }
    }

    return true;
}




switch ($_GET["op"]) {

    case 'guardarPermiso':

        // ID del empleado logueado
        $id_empleado = $_POST["empleado_codi"];
        $fecha_permiso = $_POST["fecha_permiso"];
        $hora_salida = $_POST["timepicker_salida"];
        $hora_ingreso = $_POST["timepicker_entrada"];

        // Datos recibidos del formulario
        $motivo = $_POST["permiso_motivo"];
        $detalle = $_POST["permiso_detalle"];
        $firma_base64 = $_POST["firma"];

        // Llamar al modelo
        $resultado = $permiso->insertar_permiso($id_empleado, $fecha_permiso, $hora_salida, $hora_ingreso, $motivo, $detalle, $firma_base64);

        if ($resultado) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "error" => "No se pudo guardar el permiso."]);
        }



        break;


    case "listarSolicitudesPendientes":
        $empleado_id = $_SESSION["id_empl"];
        $datos = $permiso->get_solicitudes($empleado_id);
        $data = array();
        //$tickets = [];
        foreach ($datos as $solicitud) {
            $sub_array = array();
            $sub_array[] = $solicitud["nomb_empl"];
            $sub_array[] = date('d-m-Y', strtotime($solicitud["permiso_fecha"]));
            $sub_array[] = $solicitud["permiso_hora_salida"];
            $sub_array[] = $solicitud["permiso_hora_entrada"];
            $sub_array[] = $solicitud["tipo_nombre"];
            $sub_array[] = $solicitud["permiso_detalle"];

            $sub_array[] = '<div class="button-container text-center" >
                    <button type="button" onClick="aprobar(' . $solicitud["permiso_id"] . ');" id="' . $solicitud["permiso_id"] . '" class="btn btn-success btn-icon " >
                        <div><i class="fas fa-stamp"></i></div>
                    </button>
                    <button type="button" onClick="rechazar(' . $solicitud["permiso_id"] . ');" id="' . $solicitud["permiso_id"] . '" class="btn btn-danger btn-icon " >
                        <div><i class="fas fa-ban"></i></div>
                    </button>
                </div>';

            $data[] = $sub_array;
        }

        $results = array(
            "sEcho" => 1,
            "iTotalRecords" => count($data),
            "iTotalDisplayRecords" => count($data),
            "aaData" => $data
        );
        echo json_encode($results);



        break;

    case "listarSolicitudesJefe":
        $empleado_id = $_SESSION["id_empl"];
        $datos = $permiso->get_solicitudes_jefe($empleado_id);
        $data = array();
        //$tickets = [];
        foreach ($datos as $solicitud) {
            $sub_array = array();
            $sub_array[] = date('d-m-Y', strtotime($solicitud["permiso_fecha"]));
            $sub_array[] = $solicitud["tipo_nombre"];
            $sub_array[] = $solicitud["nomb_empl"];
            $estado = $solicitud["estado_permiso"];
            $badge = '';

            switch ($estado) {
                case 'Pendiente Aprobacion':
                    $badge = '<span class="badge bg-secondary">Pendiente Aprobacion</span>';
                    break;
                case 'Aprobado Jefe':
                    $badge = '<span class="badge bg-success">Aprobado Jefe</span>';
                    break;
                case 'Vbo. Gestion Humana':
                    $badge = '<span class="badge bg-warning">Vbo. Gestion Humana</span>';
                    break;
                case 'Aprobado con pendientes':
                    $badge = '<span class="badge bg-warning">Aprobado con pendientes</span>';
                    break;
                case 'Rechazado':
                    $badge = '<span class="badge bg-danger">Rechazado Jefe</span>';
                    break;
                case 'Cancelado Operacion':
                    $badge = '<span class="badge bg-danger">Cancelado Operacion</span>';
                    break;
                default:
                    $badge = '<span class="badge bg-dark">Desconocido</span>';
                    break;
            }

            $sub_array[] = '<div class="text-center">' . $badge . '</div>';
            $sub_array[] = '<div class="button-container text-center" >
                    <button type="button" onClick="verPermiso(' . $solicitud["permiso_id"] . ');" id="' . $solicitud["permiso_id"] . '" class="btn btn-dark btn-icon " >
                        <div><i class="fas fa-eye"></i></div>
                    </button>
                </div>';

            $data[] = $sub_array;
        }

        $results = array(
            "sEcho" => 1,
            "iTotalRecords" => count($data),
            "iTotalDisplayRecords" => count($data),
            "aaData" => $data
        );
        echo json_encode($results);



        break;

    case "listarSolicitudesRecursos":

        $empleado_id   = $_POST["empleado_id"] ?? "";
        $fecha_permiso = $_POST["fecha_permiso"] ?? "";

        $datos = $permiso->get_solicitudes_recursos($empleado_id, $fecha_permiso);
        $data = array();
        //$tickets = [];
        foreach ($datos as $solicitud) {
            $sub_array = array();
            $sub_array[] = $solicitud["empleado_nombre"];
            $sub_array[] = date('d-m-Y', strtotime($solicitud["permiso_fecha"]));
            $sub_array[] = $solicitud["tipo_nombre"];
            $sub_array[] = $solicitud["jefe_nombre"];
            $sub_array[] = (!empty($solicitud["fecha_actu_permiso"]) && $solicitud["fecha_actu_permiso"] != "0000-00-00 00:00:00")
                ? date('d-m-Y H:i:s', strtotime($solicitud["fecha_actu_permiso"]))
                : '';

            $estado = $solicitud["estado_permiso"];
            $badge = '';

            switch ($estado) {
                case 'Pendiente Aprobacion':
                    $badge = '<span class="badge bg-secondary">Pendiente Aprobacion</span>';
                    break;
                case 'Aprobado Jefe':
                    $badge = '<span class="badge bg-success">Aprobado Jefe</span>';
                    break;
                case 'Vbo. Gestion Humana':
                    $badge = '<span class="badge bg-warning">Vbo. Gestion Humana</span>';
                    break;
                case 'Aprobado con pendientes':
                    $badge = '<span class="badge bg-warning">Aprobado con pendientes</span>';
                    break;
                case 'Rechazado':
                    $badge = '<span class="badge bg-danger">Rechazado Jefe</span>';
                    break;
                case 'Cancelado Operacion':
                    $badge = '<span class="badge bg-danger">Cancelado Operacion</span>';
                    break;
                default:
                    $badge = '<span class="badge bg-dark">Desconocido</span>';
                    break;
            }

            $sub_array[] = '<div class="text-center">' . $badge . '</div>';

            $sub_array[] = '<div class="button-container text-center" >
                    <button type="button" onClick="ver(' . $solicitud["permiso_id"] . ');" id="' . $solicitud["permiso_id"] . '" class="btn btn-warning btn-icon " >
                        <div><i class="fas fa-edit"></i></div>
                    </button>
                    <button type="button" onClick="verTimeline(' . $solicitud["permiso_id"] . ');" id="' . $solicitud["permiso_id"] . '" class="btn btn-dark btn-icon " >
                        <div><i class="fas fa-stream"></i></div>
                    </button>
                    <button type="button" onClick="verPdf(' . $solicitud["permiso_id"] . ');" id="' . $solicitud["permiso_id"] . '" class="btn btn-danger btn-icon " >
                        <div><i class="fas fa-file-pdf"></i></div>
                    </button>
                </div>';

            $sub_array[] = (!empty($solicitud["permiso_creado"]) && $solicitud["permiso_creado"] != "0000-00-00 00:00:00")
                ? date('d-m-Y H:i:s', strtotime($solicitud["permiso_creado"]))
                : '';

            $sub_array[] = $solicitud["permiso_detalle"];

            $data[] = $sub_array;
        }

        $results = array(
            "sEcho" => 1,
            "iTotalRecords" => count($data),
            "iTotalDisplayRecords" => count($data),
            "aaData" => $data
        );
        echo json_encode($results);

        break;

    case "aprobarPermiso":

        $codigo_jefe = $_SESSION["id_empl"];

        $firma = new Firma();

        $firma_jefe = $firma->get_by_user_id($codigo_jefe);

        if (!$firma_jefe || empty($firma_jefe["firma_base64"])) {
            echo json_encode([
                "success" => false,
                "need_firma" => true,
                "message" => "Debe registrar su firma antes de aprobar el permiso."
            ]);
            exit;
        }

        $codigo_permiso = $_POST["codigo_permiso"];
        $codigo_empleado = $_SESSION["id_empl"];
        $datos = $permiso->update_aprobado($codigo_permiso, $codigo_empleado);

        if ($datos) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "error" => "No se pudo guardar el permiso."]);
        }


        break;


    case "rechazarPermiso":

        $codigo_permiso = $_POST["codigo_permiso"];
        $codigo_empleado = $_SESSION["id_empl"];
        $motivo = $_POST["motivo_rechazo"];
        $datos = $permiso->update_rechazo($codigo_permiso, $codigo_empleado, $motivo);

        if ($datos) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "error" => "No se pudo guardar el permiso."]);
        }


        break;

    case "detallePermiso":
        if (!isset($_GET['id'])) {
            echo json_encode(['status' => 'error', 'message' => 'ID no proporcionado']);
            exit;
        }

        $permisoID = $_GET['id'];
        $detalle = $permiso->get_detalle_permiso($permisoID);

        if (!$detalle) {
            echo json_encode([
                'status' => 'error',
                'message' => 'No se encontraron datos del permiso.'
            ]);
            exit;
        }

        $row = $detalle; // es solo un registro

        // Datos del empleado y jefe inmediato
        $empleado  = $row['empleado_nombre'];
        $jefe      = $row['jefe_nombre'];

        // HTML construido dinámicamente
        $html = '';

        $html .= '<div class="mailbox-read-info">';

        $html .= '<h3><b>Detalle de Permiso #' . htmlspecialchars($permisoID) . '</b></h3>';
        $html .= '<p><b>Empleado:</b> ' . htmlspecialchars($empleado) . '</p>';
        $html .= '<p><b>Jefe Inmediato:</b> ' . htmlspecialchars($jefe) . '</p>';

        $html .= '</div><hr>';


        /* =============================
        FORMULARIO EDITABLE (RRHH)
       ============================= */

        $html .= '<div class="mailbox-read-message">';

        // fecha
        $html .= '<label>Fecha Permiso:</label>';
        $html .= '<input type="date" id="permiso_fecha" name="permiso_fecha" class="form-control" data-target="#reservationdate_fecha" value="' . $row['permiso_fecha'] . '">';
        $html .= '<div class="input-group-append" data-target="#reservationdate_fecha" data-toggle="datetimepicker">';
        $html .= '</div>';

        // hora salida
        $html .= '<label class="mt-2">Hora Salida:</label>';
        $html .= '<input type="time" id="permiso_hora_salida" name="permiso_hora_salida" class="form-control" autocomplete="off" value="' . $row['permiso_hora_salida'] . '">';

        // hora entrada
        $html .= '<label class="mt-2">Hora Entrada:</label>';
        $html .= '<input type="time" id="permiso_hora_entrada" name="permiso_hora_entrada" class="form-control" autocomplete="off" value="' . $row['permiso_hora_entrada'] . '">';

        // motivo
        $html .= '<label class="mt-2">Motivo:</label>';
        $html .= '<select id="permiso_motivo" name="permiso_motivo" class="form-control" data-valorbd="' . $row["tipo_id"] . '">';
        //$html .= '<option value="' . $row['tipo_id'] . '">' . $row['tipo_nombre'] . '</option>';
        $html .= '</select>';
        //<option value='".$row['tipo_id']."'>".$row['tipo_nombre']."</option>

        //incapacidad oculto

        $html .= '<div id="bloqueIncapacidad" style="display:none;">';
        $html .= '<label class="mt-2">Tipo de Incapacidad:</label>';
        $html .= '<select id="incapacidad_id" name="incapacidad_id" class="form-control select2" data-valorbd="' . $row["perm_inca_id"] . '">';
        $html .= '<option value="">Seleccione una incapacidad</option>';
        $html .= '</select>';
        $html .= '</div>';

        // justificación 
        $html .= '<label class="mt-2">Justificación:</label>';
        $html .= '<textarea id="permiso_justificacion" name="permiso_justificacion" class="form-control">' . $row['permiso_detalle'] . '</textarea>';

        // Select estado
        $html .= '<label class="mt-3 fw-bold">Estado de la Solicitud:</label>';
        $html .= '<select id="permiso_estado" name="permiso_estado" class="form-control select2">
                <option value="3" ' . ($row['permiso_estado'] == 3 ? 'selected' : '') . '>V°B° Gestión Humana</option>
                <option value="4" ' . ($row['permiso_estado'] == 4 ? 'selected' : '') . '>Aprobado con Pendientes</option>
              </select>';

        $html .= '<hr><hr>';
        $html .= '<h3 class="card-title"><b>Información de ausentismo completada por Gestión Humana</b></h3></br>';

        // fecha
        $html .= '<label class="mt-2">Fecha Cierre Permiso:</label>';
        $html .= '<input type="date" id="permiso_fecha_cierre" name="permiso_fecha_cierre" class="form-control" data-target="#reservationdate_fecha" value="' . $row['permiso_fecha'] . '">';
        $html .= '<div class="input-group-append" data-target="#reservationdate_fecha" data-toggle="datetimepicker">';
        $html .= '</div>';

        $html .= '<label class="mt-2">Total Horas:</label>';
        $html .= '<input type="text" id="permiso_total_horas" name="permiso_total_horas" class="form-control" autocomplete="off" value="" readonly>';

        $html .= '</div>';
        $html .= '</form>';

        $html .= '<hr><hr>';
        $html .= '<h3 class="card-title"><b>Adjuntar soportes documentales de permisos</b></h3></br>';

        $html .= '<ul id="listaSoportes" class="list-group mt-3 mb-3"></ul>';

        $html .= '<form action="" class="dropzone" id="uploadZona">';
        $html .= '<div class="dz-default dz-message">';
        $html .= '<button class="dz-button" type="button">';
        $html .= '<i class="fas fa-cloud-upload-alt icon-super-upload"></i>';
        $html .= '<div style="font-size: 18px; font-weight: bold; margin-top: 10px; text-aling: center;"> Arrastra tus archivos o haz clic aquí </div>';
        $html .= '</button>';
        $html .= '</div>';

        $html .= '</form>';



        echo json_encode([
            'status' => 'success',
            'html' => $html
        ]);


        break;

    case "timeline":

        $permiso_id = $_POST["permiso_id"];

        // Modelo trae SOLO el permiso (1 registro)
        $respuesta = $permiso->get_permiso($permiso_id);
        $data = $respuesta[0];

        /*         echo "<pre>";
        print_r($data);
        echo "</pre>";
        exit; */

        // -----------------------------------------
        // FUNCIÓN DE ICONOS DENTRO DEL CONTROLLER
        // -----------------------------------------
        function obtenerIconoPorEstado($estado) {

            $iconos = [
                "1" => ["icon" => "fas fa-hourglass-half", "bg" => "bg-secondary"], // Pendiente
                "2" => ["icon" => "fas fa-check",          "bg" => "bg-success"], // Aprobado Jefe
                "3" => ["icon" => "fas fa-user-tie",       "bg" => "bg-primary"], // VoBo RRHH
                "4" => ["icon" => "fas fa-exclamation",    "bg" => "bg-warning"],    // Aprobado con pendientes
                "5" => ["icon" => "fas fa-exclamation",    "bg" => "bg-warning"],    // Aprobado con pendientes
                "6" => ["icon" => "fas fa-times",          "bg" => "bg-danger"],  // Rechazado Jefe
                "7" => ["icon" => "fas fa-ban",            "bg" => "bg-dark"],    // Cancelado Operación
            ];

            return $iconos[$estado] ?? ["icon" => "fas fa-info-circle", "bg" => "bg-primary"];
        }

        // -----------------------------------------
        // GENERACIÓN DEL TIMELINE
        // -----------------------------------------
        $html = "";


        // ------------------------
        // 1. PERMISO CREADO
        // ------------------------
        if (!empty($data["permiso_creado"])) {

            $fecha = date("d M Y", strtotime($data["permiso_creado"]));
            $hora  = date("h:i A", strtotime($data["permiso_creado"]));

            // El icono SIEMPRE debe ser el del estado 1 (solicitado)
            $ico = obtenerIconoPorEstado(1);

            // Texto correspondiente
            if ($data["permiso_estado"] >= 1) {
                $titulo = "Permiso Solicitado";
                $descripcion = "El empleado radicó la solicitud.";
            } else {
                // fallback seguro
                $titulo = "Registro del Permiso";
                $descripcion = "Se registró la radicación del permiso.";
            }

            // HTML del timeline
            $html .= '
                <div class="time-label">
                    <span class="' . $ico["bg"] . '">' . $fecha . '</span>
                </div>

                <div>
                    <i class="' . $ico["icon"] . ' ' . $ico["bg"] . '"></i>

                    <div class="timeline-item">
                        <span class="time"><i class="fas fa-clock"></i> ' . $hora . '</span>
                        <h3 class="timeline-header"><strong>' . $titulo . '</strong></h3>
                        <div class="timeline-body">
                            ' . $descripcion . '
                        </div>
                    </div>
                </div>
            ';
        }


        // ------------------------
        // 2. APROBACIÓN / RECHAZO DEL JEFE
        // ------------------------
        if (!empty($data["fecha_actu_permiso"])) {

            $fecha = date("d M Y", strtotime($data["fecha_actu_permiso"]));
            $hora  = date("h:i A", strtotime($data["fecha_actu_permiso"]));

            // Determinar si aprobó o rechazó
            if ($data["permiso_estado"] == 2) {

                // Aprobado por jefe
                $ico = obtenerIconoPorEstado(2);
                $titulo = "Aprobación del Jefe";
                $descripcion = "Aprobacion por el jefe inmediato.";
            } elseif ($data["permiso_estado"] == 6) {

                // Rechazado por jefe
                $ico = obtenerIconoPorEstado(6);
                $titulo = "Permiso Rechazado por el Jefe";
                $descripcion = !empty($data["rechazo_permiso"])
                    ? $data["rechazo_permiso"]
                    : "El jefe rechazó el permiso.";
            } else {
                // Si el estado no corresponde, no mostrar este bloque.
                $ico = obtenerIconoPorEstado(2);
                $titulo = "Gestión del Jefe";
                $descripcion = "Aprobacion realizada por el jefe.";
            }

            $html .= '
                <div class="time-label">
                    <span class="' . $ico["bg"] . '">' . $fecha . '</span>
                </div>

                <div>
                    <i class="' . $ico["icon"] . ' ' . $ico["bg"] . '"></i>

                    <div class="timeline-item">
                        <span class="time"><i class="fas fa-clock"></i> ' . $hora . '</span>
                        <h3 class="timeline-header"><strong>' . $titulo . '</strong></h3>
                        <div class="timeline-body">
                            ' . $descripcion . '
                        </div>
                    </div>
                </div>';
        }


        // --------------------------------
        // 3. VISTO BUENO DE RRHH
        // --------------------------------
        if (
            !empty($data["fecha_actu_rrhh"]) &&
            $data["fecha_actu_rrhh"] != "0000-00-00 00:00:00"
        ) {

            $fecha = date("d M Y", strtotime($data["fecha_actu_rrhh"]));
            $hora  = date("h:i A", strtotime($data["fecha_actu_rrhh"]));

            $ico = obtenerIconoPorEstado($data["permiso_estado"]);

            // ============================
            // TEXTO SEGÚN ESTADO
            // ============================
            if ($data["permiso_estado"] == '3') {
                $titulo = "V°B° Gestión Humana";
                $descripcion = "Gestión Humana validó y cerró el permiso.";
            } elseif ($data["permiso_estado"] == '4') {
                $titulo = "Aprobado con Pendientes";
                $descripcion = "El permiso fue aprobado, pero queda pendiente la carga de soportes.";
            } else {
                $titulo = "Actualización de Gestión Humana";
                $descripcion = "Se registró una actualización por parte de Gestión Humana.";
            }

            // ============================
            // TIMELINE HTML
            // ============================
            $html .= '
                <div class="time-label">
                    <span class="' . $ico["bg"] . '">' . $fecha . '</span>
                </div>

                <div>
                    <i class="' . $ico["icon"] . ' ' . $ico["bg"] . '"></i>

                    <div class="timeline-item">
                        <span class="time"><i class="fas fa-clock"></i> ' . $hora . '</span>
                        <h3 class="timeline-header"><strong>' . $titulo . '</strong></h3>
                        <div class="timeline-body">
                            ' . $descripcion . '
                        </div>
                    </div>
                </div>
            ';
        }



        // ------------------------
        // FIN DEL TIMELINE
        // ------------------------
        $html .= '
        <div>
            <i class="fas fa-clock bg-primary"></i>
        </div>
    ';

        echo $html;

        break;

    case "subirSoporte":

        $permiso_id   = $_POST["permiso_id"];

        $datosPermiso = $permiso->get_permiso_by_id($permiso_id);

        if (!$datosPermiso) {
            echo json_encode([
                "success" => false,
                "message" => "Permiso no encontrado."
            ]);
            exit;
        }

        $empleado_id  = $datosPermiso["empleado_id"];
        $nomb_empl    = str_replace(" ", "_", trim($datosPermiso["nomb_empl"]));

        // ===========================
        // 2. DATOS DEL ARCHIVO
        // ===========================

        $tmpFile      = $_FILES["file"]["tmp_name"];
        $fileName     = $_FILES["file"]["name"];

        $fecha        = date("Y-m-d");

        // Ruta remota final donde irá el archivo
        $remotePath     = "data01/permisos/$nomb_empl/$fecha";
        $remoteFullPath = "/$remotePath/$fileName";

        // ===========================
        // 3. CREAR CARPETAS VÍA FTP
        // ===========================

        $ftp_server   = "172.16.5.3";
        $ftp_user     = "asfaltart_admin";
        $ftp_pass     = "s1st3m4s19..";

        $ftp = ftp_connect($ftp_server);
        ftp_login($ftp, $ftp_user, $ftp_pass);

        // Modo pasivo recomendado
        ftp_pasv($ftp, true);

        // Crear recursivamente: data01/permisos/{empleado}/{fecha}
        // Crear recursivamente: data01/permisos/{empleado}/{fecha}
        if (!ftp_mksubdirs_safe($ftp, "data01/permisos/$nomb_empl/$fecha")) {
            echo json_encode([
                "success" => false,
                "message" => "No fue posible crear las carpetas en el NAS."
            ]);
            ftp_close($ftp);
            exit;
        }

        ftp_close($ftp);


        // ===========================
        // 2. SUBIR ARCHIVO CON WinSCP
        // ===========================

        $scriptPath = "C:\\xampp\\htdocs\\evds2023\\public\\winscp\\script_temp.txt";
        $winscpCom  = "C:\\xampp\\htdocs\\evds2023\\public\\winscp\\WinSCP.com";

        $scriptContent =
            "open ftp://$ftp_user:$ftp_pass@$ftp_server\n" .
            "put \"$tmpFile\" \"$remoteFullPath\"\n" .
            "exit\n";

        file_put_contents($scriptPath, $scriptContent);

        $cmd = "\"$winscpCom\" /ini=nul /script=\"$scriptPath\"";
        exec($cmd . " 2>&1", $output, $resultCode);

        unlink($scriptPath);


        if ($resultCode === 0) {

            $permiso->registrar_soporte_permiso(
                $permiso_id,
                $fileName,
                $remoteFullPath
            );

            echo json_encode([
                "success" => true,
                "message" => "Soporte subido correctamente"
            ]);
        } else {

            echo json_encode([
                "success" => false,
                "message" => "Error subiendo archivo",
                "debug"   => $output
            ]);
        }

        break;

    case "listarSoportes":

        $permiso_id = $_POST["permiso_id"];
        $data = $permiso->get_soportes_permiso($permiso_id);

        echo json_encode($data);
        break;

    // -------------------------
    // DESCARGAR ARCHIVO
    // -------------------------
    case "descargarSoporte":

        if (!isset($_GET["file"])) {
            echo "Archivo no especificado";
            exit;
        }

        $ruta_remota = $_GET["file"];
        $nombre_archivo = basename($ruta_remota);

        // Carpeta temporal local
        $temp_local = "C:\\xampp\\htdocs\\evds2023\\public\\temp\\";
        if (!is_dir($temp_local)) {
            mkdir($temp_local, 0777, true);
        }

        $ruta_local = $temp_local . $nombre_archivo;

        // Script temporal
        $scriptPath = "C:\\xampp\\htdocs\\evds2023\\public\\winscp\\script_descarga.txt";
        $winscpCom  = "C:\\xampp\\htdocs\\evds2023\\public\\winscp\\WinSCP.com";

        // Script FTP
        $scriptContent =
            "open ftp://asfaltart_admin:s1st3m4s19..@172.16.5.3\n" .
            "get \"$ruta_remota\" \"$ruta_local\"\n" .
            "exit\n";

        file_put_contents($scriptPath, $scriptContent);

        $cmd = "\"$winscpCom\" /ini=nul /script=\"$scriptPath\"";
        exec($cmd . " 2>&1", $output, $result);

        unlink($scriptPath);

        if (!file_exists($ruta_local)) {
            echo "No se pudo descargar el archivo.";
            exit;
        }

        // Enviar archivo al navegador
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"$nombre_archivo\"");
        header("Content-Length: " . filesize($ruta_local));

        readfile($ruta_local);

        unlink($ruta_local);


        break;

    case "updateRecursos":


        $permisoID = $_POST['permiso_id'];

        // ID del empleado logueado
        $fecha_permiso = $_POST["permiso_fecha"];
        $hora_salida = $_POST["permiso_hora_salida"];
        $hora_ingreso = $_POST["permiso_hora_entrada"];

        // Datos recibidos del formulario
        $motivo = $_POST["permiso_motivo"];
        $detalle = $_POST["permiso_justificacion"];
        $estado = $_POST["permiso_estado"];

        $rrhh_id     = $_SESSION["id_empl"];
        //$fecha_actu  = date("Y-m-d H:i:s");

        $fecha_cierre = $_POST["permiso_fecha_cierre"];

        $total_horas = $_POST["permiso_total_horas"];

        $incapacidad_id = $_POST["incapacidad_id"] ?? null;

        $resultado = $permiso->actualizar_permiso_rrhh(
            $permisoID,
            $fecha_permiso,
            $hora_salida,
            $hora_ingreso,
            $motivo,
            $detalle,
            $estado,
            $rrhh_id,
            $fecha_cierre,
            $total_horas,
            $incapacidad_id
        );


        if ($resultado) {
            echo json_encode(["success" => true, "message" => "Actualizado correctamente"]);
        } else {
            echo json_encode(["success" => false, "error" => "No se pudo guardar el permiso."]);
        }



        break;

    case "listarAusentismo":
        break;
}