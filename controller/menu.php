<?php 

    require_once("../config/conexion.php");
    require_once("../models/Menu.php");

    $menu = new Menu();

    switch ($_GET["op"]) {
        case 'listar':

            $datos=$menu->mostrar_menu_x_rol($_POST["rol_id"]);
            $data=Array();
            foreach ($datos as $row) {
                $sub_array = array();
                $sub_array[]= $row["menu_nomb"];
                if($row["perm_usua"] == "Si"){
                    $sub_array[] = '<div class="button-container text-center" >
                                        <button type="button" onClick="desactivar('.$row["perm_id"].');" id="'.$row["perm_id"].'" class="btn btn-info btn-icon" >'.$row["perm_usua"].'</button>
                                    </div>';

                }else{
                    $sub_array[] = '<div class="button-container text-center" >
                                         <button type="button" onClick="activar('.$row["perm_id"].');" id="'.$row["perm_id"].'" class="btn btn-danger btn-icon" >'.$row["perm_usua"].'</button>
                                    </div>';
                }

                $data[] = $sub_array;

            }


            $results = array(

                "sEcho"=>1,
                "iTotalRecords"=>count($data),
                "iTotalDisplayRecords"=>count($data),
                "aaData"=>$data);
            
            echo json_encode($results);



        break;

        case 'activar':
            $menu->activar_menu($_POST["perm_id"]);
        break;

        case 'desactivar':
            $menu->desactivar_menu($_POST["perm_id"]);
        break;
        

    }


?>