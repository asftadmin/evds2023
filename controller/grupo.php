<?php 

require_once ("../config/conexion.php");
require_once("../models/Grupo.php");


$grupo = new Grupo();

switch ($_GET["op"]) {

    case 'guardaryeditar':

        if(empty($_POST["codigo_grupo"])) {
                $grupo->insertar_grupo($_POST["nomb_grupo"]);
        } 
        else {   
            $grupo->update_grupo( $_POST["codigo_grupo"],$_POST["nomb_grupo"], $_POST["esta_grupo"]);
        }
        




    break;

    case "listarGrupo" :

        $datos = $grupo->listar_grupo();

        $data = array();
        foreach ($datos as $row) {

            $sub_array = array();
            $sub_array[] = $row["codi_grem"];
            $sub_array[] = $row["nomb_grem"];
            $sub_array [] = '<div class="button-container text-center" >
                    <button type="button" onClick="editar('.$row["codi_grem"].');" id="'.$row["codi_grem"].'" class="btn btn-warning btn-icon " >
                        <div><i class="fa fa-edit"></i></div>
                    </button>
                    <button type="button" onClick="eliminar('.$row["codi_grem"].');" id="'.$row["codi_grem"].'" class="btn btn-danger btn-icon" >
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
        $datos = $grupo->listar_grupo_x_id($_POST["codigo_grupo"]);
        if(is_array($datos)==true and count($datos)>0){
            foreach($datos as $row){
                $output["codigo_grupo"] = $row["codi_grem"];
                $output["nombre_grupo"] = $row["nomb_grem"];
                $output["estado_grupo"] = $row["esta_grem"];
            }
            echo json_encode($output);
        }

    break;

    case 'eliminar_grupo':
    
        $grupo->delete_grupo($_POST["codigo_grupo"]);
    
    
    break;

    case 'comboGrupo':

        $datos = $grupo->get_grupo();
        if(is_array($datos)==true and count($datos)>0){
            
            $html = "<option disabled selected required>--Selecciona el Grupo--</option>";
            foreach ($datos as $row) {
                $html.="<option value='".$row['codi_grem']."'>".$row['nomb_grem']."</option>";
            }
            echo $html;
        }



    break;
    

}

?>