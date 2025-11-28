<?php
require_once("../config/conexion.php");
require_once("../models/Firma.php");

// Iniciar clase
$firma = new Firma();

switch ($_GET["op"]) {

    /* ============================================================
        GUARDAR O ACTUALIZAR FIRMA DEL USUARIO
       ============================================================ */
    case "guardar":

        // Validar si viene la firma
        if (!isset($_POST["firma_base64"])) {
            echo json_encode([
                "success" => false,
                "message" => "No se recibió la imagen de la firma."
            ]);
            exit;
        }

        $user_id = $_SESSION["id_empl"];           // Jefe o empleado logueado
        $firma_base64 = $_POST["firma_base64"];    // Firma enviada desde canvas

        // =============================
        // LIMPIAR Y NORMALIZAR BASE64
        // =============================

        // Quitar encabezado: data:image/png;base64,
        $firma_limpia = preg_replace('#^data:image/\w+;base64,#i', '', $firma_base64);

        // Base64 NO permite espacios → convertirlos
        $firma_limpia = str_replace(' ', '+', $firma_limpia);

        // Validar que sea base64 válido
        if (base64_decode($firma_limpia, true) === false) {
            echo json_encode([
                "success" => false,
                "message" => "La firma está corrupta o no es base64 válido."
            ]);
            exit;
        }

        // =============================
        // VERIFICAR SI EL USUARIO YA TIENE UNA FIRMA
        // =============================
        $firmaExistente = $firma->get_by_user_id($user_id);

        if ($firmaExistente) {

            // =============================
            // ACTUALIZAR FIRMA
            // =============================

            $firma->update_firma($user_id, $firma_limpia);

            echo json_encode([
                "success" => true,
                "message" => "Firma actualizada correctamente."
            ]);
            exit;
        } else {

            // =============================
            // INSERTAR NUEVA FIRMA
            // =============================

            $firma->insert_firma($user_id, $firma_limpia);

            echo json_encode([
                "success" => true,
                "message" => "Firma registrada correctamente."
            ]);
            exit;
        }

        break;



    /* ============================================================
        OBTENER FIRMA DEL USUARIO (Opcional para vista perfil)
       ============================================================ */
    case "obtener":

        $user_id = $_SESSION["id_empl"];

        $data = $firma->get_by_user_id($user_id);

        echo json_encode([
            "success" => true,
            "data" => $data
        ]);
        break;
}