<?php 

require_once ("../config/conexion.php");
require_once("../models/TipoDoc.php");


$tipo_documento = new TipoDocumento();

switch ($_GET["op"]) {

    case 'comboTipoDocumento':

        $datos = $tipo_documento->listar_tipo_documento();
        if(is_array($datos)==true and count($datos)>0){
            
            $html = "<option disabled selected required>--Selecciona el Tipo de documento--</option>";
            foreach ($datos as $row) {
                $html.="<option value='".$row['codi_tpdc']."'>".$row['nomb_tpdc']."</option>";
            }
            echo $html;
        }



    break;

}      
