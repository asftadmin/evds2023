<?php 

require_once("../config/conexionBiotime.php");
require_once("../models/Asistencia.php");

$asistencia = new Asistencia();

switch ($_GET["op"]) {


    case 'listarAsistencia':

        $fechainicio = $_GET['fechainicio'] ?? null;
        $fechafin = $_GET['fechafin'] ?? null;

        $datos = $asistencia->obtenerRegistrosAsistencia($fechainicio, $fechafin);

        $data = Array();
        foreach ($datos as $row) {
            $sub_array = array();
            $sub_array []= $row["Fecha"];
            $sub_array []= $row["Usuario"];
            $sub_array []= $row["HoraEntrada"];
            $sub_array []= $row["HoraSalida"];
            $sub_array []= $row["TiempoBruto"];
            $sub_array []= $row["TiempoLaboradoNeto"];
            $data[] = $sub_array;
        }

        $results = array(

            "sEcho"=>1,
            "iTotalRecords"=>count($data),
            "iTotalDisplayRecords"=>count($data),
            "aaData"=>$data);
        
        echo json_encode($results);
        

    break;

    
    

        
}



?>