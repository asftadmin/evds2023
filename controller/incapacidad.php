<?php

require_once("../config/conexion.php");
require_once("../models/Incapacidad.php");


$incapacidad = new Incapacidad();

switch ($_GET["op"]) {

    case 'comboIncapacidades':
        $datos = $incapacidad->get_incapacidades();
        $html = '<option value="">Seleccione una incapacidad</option>';

        foreach ($datos as $row) {
            $html .= '<option value="' . $row["inca_id"] . '">' . htmlspecialchars($row["inca_codigo"] . " - " . $row["inca_nombre"]) . '</option>';
        }

        echo $html;
        break;
}
