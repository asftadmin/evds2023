<?php 

require_once ("../config/conexion.php");
require_once("../models/TipoPermiso.php");


$tipo_permiso = new TipoPermiso();

switch ($_GET["op"]) {

    case 'comboTipoPermiso':

        $datos = $tipo_permiso->listar_tipo_permiso();
        if(is_array($datos)==true and count($datos)>0){
            
            $html = "<option disabled selected required>--Selecciona el Motivo del permiso--</option>";
            foreach ($datos as $row) {
                $html.="<option value='".$row['tipo_id']."'>".$row['tipo_nombre']."</option>";
            }
            echo $html;
        }



    break;

}      
