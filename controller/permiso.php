<?php

require_once("../config/conexion.php");
require_once("../models/Permiso.php");


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
                    $badge = '<span class="badge bg-warning">Vbo. Gestion Humana</span>';
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

        $datos = $permiso->get_solicitudes_recursos();
        $data = array();
        //$tickets = [];
        foreach ($datos as $solicitud) {
            $sub_array = array();
            $sub_array[] = $solicitud["empleado_nombre"];
            $sub_array[] = date('d-m-Y', strtotime($solicitud["permiso_fecha"]));
            $sub_array[] = $solicitud["tipo_nombre"];
            $sub_array[] = $solicitud["jefe_nombre"];
            $sub_array[] = $solicitud["fecha_actu_permiso"];

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
                    $badge = '<span class="badge bg-warning">Vbo. Gestion Humana</span>';
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

    case "aprobarPermiso":

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
        $html .= '<input type="date" id="permiso_fecha" class="form-control" value="' . $row['permiso_fecha'] . '">';

        // hora salida
        $html .= '<label class="mt-2">Hora Salida:</label>';
        $html .= '<input type="time" id="permiso_hora_salida" class="form-control" value="' . $row['permiso_hora_salida'] . '">';

        // hora entrada
        $html .= '<label class="mt-2">Hora Entrada:</label>';
        $html .= '<input type="time" id="permiso_hora_entrada" class="form-control" value="' . $row['permiso_hora_entrada'] . '">';

        // motivo
        $html .= '<label class="mt-2">Motivo:</label>';
        $html .= '<textarea id="permiso_motivo" class="form-control">' . $row['tipo_nombre'] . '</textarea>';

        // justificación
        $html .= '<label class="mt-2">Justificación:</label>';
        $html .= '<textarea id="permiso_justificacion" class="form-control">' . $row['permiso_detalle'] . '</textarea>';

        // Select estado
        $html .= '<label class="mt-3 fw-bold">Estado de la Solicitud:</label>';
        $html .= '<select id="permiso_estado" class="form-control select2">
                <option value="3" ' . ($row['permiso_estado'] == 3 ? 'selected' : '') . '>V°B° Gestión Humana</option>
                <option value="4" ' . ($row['permiso_estado'] == 4 ? 'selected' : '') . '>Aprobado con Pendientes</option>
              </select>';

        $html .= '<button class="btn btn-success mt-3" onclick="guardarDetallePermiso(' . $permisoID . ')">
                Guardar Cambios
              </button>';

        $html .= '</div>';

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
                "1" => ["icon" => "fas fa-hourglass-half", "bg" => "bg-warning"], // Pendiente
                "2" => ["icon" => "fas fa-check",          "bg" => "bg-success"], // Aprobado Jefe
                "3" => ["icon" => "fas fa-user-tie",       "bg" => "bg-primary"], // VoBo RRHH
                "4" => ["icon" => "fas fa-exclamation",    "bg" => "bg-info"],    // Aprobado con pendientes
                "5" => ["icon" => "fas fa-exclamation",    "bg" => "bg-info"],    // Aprobado con pendientes
                "6" => ["icon" => "fas fa-times",          "bg" => "bg-danger"],  // Rechazado Jefe
                "7" => ["icon" => "fas fa-ban",            "bg" => "bg-dark"],    // Cancelado Operación
            ];

            return $iconos[$estado] ?? ["icon" => "fas fa-info-circle", "bg" => "bg-secondary"];
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

            $html .= '
            <div class="time-label">
                <span class="bg-blue">' . $fecha . '</span>
            </div>

            <div>
                <i class="fas fa-user bg-blue"></i>

                <div class="timeline-item">
                    <span class="time"><i class="fas fa-clock"></i> ' . $hora . '</span>
                    <h3 class="timeline-header"><strong>Permiso Solicitado</strong></h3>
                    <div class="timeline-body">
                        El empleado radicó la solicitud.
                    </div>
                </div>
            </div>
        ';
        }


        // ------------------------
        // 2. APROBACIÓN / RECHAZO DEL JEFE
        // ------------------------
        if (!empty($data["fecha_actu_permiso"])) {

            $ico = obtenerIconoPorEstado($data["permiso_estado"]);

            $fecha = date("d M Y", strtotime($data["fecha_actu_permiso"]));
            $hora  = date("h:i A", strtotime($data["fecha_actu_permiso"]));

            // Texto dinámico según estado
            if ($data["permiso_estado"] == "6") {
                $titulo = "Permiso Rechazado por Jefe";

                // Mostrar motivo del rechazo
                $detalle = "
            <strong>Motivo del rechazo:</strong><br>
            " . (!empty($data["rechazo_permiso"]) ? nl2br($data["rechazo_permiso"]) : "No especificado.") . "
        ";
            } else {
                $titulo = "Aprobación del Jefe";
                $detalle = "Acción realizada por el jefe inmediato.";
            }

            $html .= '
            <div class="time-label">
                <span class="bg-success">' . $fecha . '</span>
            </div>

            <div>
                <i class="' . $ico["icon"] . ' ' . $ico["bg"] . '"></i>

                <div class="timeline-item">
                    <span class="time"><i class="fas fa-clock"></i> ' . $hora . '</span>
                    <h3 class="timeline-header"><strong>' . $titulo . '</strong></h3>
                    <div class="timeline-body">' . $detalle . '</div>
                </div>
            </div>
        ';
        }


        // --------------------------------
        // 3. VISTO BUENO DE RRHH
        // --------------------------------
        /*         if (!empty($data["fecha_actu_rrhh"])) {

            $fecha = date("d M Y", strtotime($data["fecha_actu_rrhh"]));
            $hora  = date("h:i A", strtotime($data["fecha_actu_rrhh"]));

            $html .= '
            <div class="time-label">
                <span class="bg-primary">' . $fecha . '</span>
            </div>

            <div>
                <i class="fas fa-user-tie bg-primary"></i>

                <div class="timeline-item">
                    <span class="time"><i class="fas fa-clock"></i> ' . $hora . '</span>
                    <h3 class="timeline-header"><strong>Visto Bueno de Gestión Humana</strong></h3>
                    <div class="timeline-body">
                        Gestión Humana validó y cerró el permiso.
                    </div>
                </div>
            </div>
        ';
        } */


        // ------------------------
        // FIN DEL TIMELINE
        // ------------------------
        $html .= '
        <div>
            <i class="fas fa-clock bg-gray"></i>
        </div>
    ';

        echo $html;

        break;

    case "subirSoporte":

        $empleado_id  = $_SESSION["id_empl"];
        $nomb_empl    = str_replace(" ", "_", trim($_SESSION["nomb_empl"]));

        $permiso_id   = $_POST["permiso_id"];

        $tmpFile      = $_FILES["file"]["tmp_name"];
        $fileName     = $_FILES["file"]["name"];

        $fecha        = date("Y-m-d");

        // Ruta remota final donde irá el archivo
        $remotePath     = "data01/permisos/$nomb_empl/$fecha";
        $remoteFullPath = "/$remotePath/$fileName";

        // ===========================
        // 1. CREAR CARPETAS VÍA FTP
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

        $scriptPath = "D:\\xampp\\htdocs\\evds2023\\public\\winscp\\script_temp.txt";
        $winscpCom  = "D:\\xampp\\htdocs\\evds2023\\public\\winscp\\WinSCP.com";

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
        $temp_local = "D:\\xampp\\htdocs\\evds2023\\public\\temp\\";
        if (!is_dir($temp_local)) {
            mkdir($temp_local, 0777, true);
        }

        $ruta_local = $temp_local . $nombre_archivo;

        // Script temporal
        $scriptPath = "D:\\xampp\\htdocs\\evds2023\\public\\winscp\\script_descarga.txt";
        $winscpCom  = "D:\\xampp\\htdocs\\evds2023\\public\\winscp\\WinSCP.com";

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
}
