<?php 

require_once ("../config/conexion.php");
require_once("../models/Grafico.php");

$grafico = new Grafico();

switch ($_GET["op"]) {
    case 'listar':
        $datos = $grafico->get_grafico($_POST["idEmpleado"]);

        // print_r($datos);

        // Inicializar arrays para etiquetas y valores
        $labels = [];
        $valores = [];


        // Procesar los resultados
        foreach ($datos as $dato) {
            $labels[] = $dato['nombre_mes'];  // Etiquetas (Meses)
            $valores[] = $dato['total'];  // Valores (Ventas)
        }

        // Retornar los datos en formato JSON
    header('Content-Type: application/json'); // Asegúrate de establecer el tipo de contenido
    echo json_encode([
        'labels' => $labels,
        'valores' => $valores
    ]);

    break;
    

}



?>