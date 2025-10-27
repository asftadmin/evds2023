<?php 

require_once ("../config/conexion.php");
require_once("../models/Cargo.php");


$cargo = new Cargo();

switch ($_GET["op"]) {

    case "guardaryeditar":

        if(empty($_POST["codigo_cargo"])) {
            $cargo->insertar_cargo($_POST["nomb_cargo"],$_POST["grupo_empl"]);
        } 
        else {   
            $cargo->update_cargo( $_POST["codigo_cargo"],$_POST["nomb_cargo"], $_POST["esta_cargo"]);
        }
        
    break;

    case "listarCargo" :

        $datos = $cargo->listar_cargo();

        $data = array();
        foreach ($datos as $row) {

            $sub_array = array();
            $sub_array[] = $row["codi_carg"];
            $sub_array[] = $row["nomb_carg"];
            $sub_array[] = $row["nomb_grem"];
            $sub_array [] = '<div class="button-container text-center" >
                    <button type="button" onClick="editar('.$row["codi_carg"].');" id="'.$row["codi_carg"].'" class="btn btn-warning btn-icon " >
                        <div><i class="fa fa-edit"></i></div>
                    </button>
                    <button type="button" onClick="eliminar('.$row["codi_carg"].');" id="'.$row["codi_carg"].'" class="btn btn-danger btn-icon" >
                        <div><i class="fa fa-trash"></i></div>
                    </button>
                </div>';
            $data[] = $sub_array;

        }


        $resultado = array(
            "sEcho" => 1,
            "iTotalRecords" => count($data),
            "iTotalDisplayRecords" => count($data),
            "aaData" => $data
        );
        echo json_encode($resultado);

    break;

    case 'mostrar':
        $datos = $cargo->listar_cargo_x_id($_POST["codigo_cargo"]);
        if(is_array($datos)==true and count($datos)>0){
            foreach($datos as $row){
                $output["codigo_cargo"] = $row["codi_carg"];
                $output["nombre_cargo"] = $row["nomb_carg"];
                $output["esta_grupo"] = $row["esta_carg"];
            }
            echo json_encode($output);
        }

    break;

    case 'eliminar_cargo':
    
        $cargo->delete_cargo($_POST["codigo_cargo"]);
    
    
    break;

    case 'comboCargo':

        $datos = $cargo->listar_cargo();
        if(is_array($datos)==true and count($datos)>0){
            
            $html = "<option disabled selected required>--Selecciona el Cargo--</option>";
            foreach ($datos as $row) {
                $html.="<option value='".$row['codi_carg']."'>".$row['nomb_carg']." - ".$row['nomb_grem']."</option>";
            }
            echo $html;
        }



    break;

    

}

?>