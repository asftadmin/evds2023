<?php

require_once("../config/conexion.php");
require_once("../models/Certificados.php");


$certificado = new Certificados();


switch ($_GET["op"]) {
    case 'generarCertificado':

        if (isset($_POST['id_empl'])) {

            $id_empl = $_POST['id_empl'];
            require '../view/PDF/certificados.php';
        } else {
            echo "ID de empleado no recibido.";
        }


        break;
}
