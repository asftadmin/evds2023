<?php 

require_once("../config/conexion.php");
require_once("../models/Rol.php");

$rol = new Rol();

switch ($_GET["op"]) {


    case 'guardaryeditar':
        

            $rol->insertar_rol($_POST["nomb_rol"]);


    break;

    case 'listar':

        $datos = $rol->listar_rol();
        $data = Array();
        foreach ($datos as $row) {
            
            $sub_array = array();
            $sub_array []= $row["rol_id"];
            $sub_array []= $row["rol_nomb"];
            $sub_array [] = '<div class="button-container text-center" >
            <button type="button" onClick="editar('.$row["rol_id"].');" id="'.$row["rol_id"].'" class="btn btn-warning btn-icon " >
                <div><i class="fa fa-edit"></i></div>
            </button>
            <button type="button" onClick="permisos('.$row["rol_id"].');" id="'.$row["rol_id"].'" class="btn btn-info btn-icon" >
                <div><i class="fa fa-cogs fa-award"></i></div>
            </button>
            <button type="button" onClick="eliminar('.$row["rol_id"].');" id="'.$row["rol_id"].'" class="btn btn-danger btn-icon" >
                <div><i class="fa fa-trash"></i></div>
            </button>
            </div>';
            $data[] = $sub_array;

        }

        $results = array(

            "sEcho"=>1,
            "iTotalRecords"=>count($data),
            "iTotalDisplayRecords"=>count($data),
            "aaData"=>$data);
        
        echo json_encode($results);

    
    break;

    case 'mostrar':
    
        $datos=$rol->mostar_rol_x_id($_POST["rol_id"]);
        if(is_array($datos)==true and count($datos)>0){
            foreach($datos as $row){
                $output["rol_id"] = $row["rol_id"];
                $output["rol_nomb"] = $row["rol_nomb"];

            }

            echo json_encode($output);
        }
    
    
    break;

    case 'eliminar_rol':
    
        $rol->delete_rol($_POST["rol_id"]);
    
    
    break;
    

        
}



?>