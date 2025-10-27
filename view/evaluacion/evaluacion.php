<?php
require_once "../../config/conexion.php";
if (isset($_SESSION["user_id"])) {

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php require_once("../MainHead/head.php") ?>

    <!-- Select2 -->
    <link rel="stylesheet" href="../../public/plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="../../public/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
    <link rel="stylesheet" href="../../public/css/preop.css">
    <title>EVALUACIÓN DESEMPEÑO</title>


</head>

<body class="hold-transition sidebar-mini">
    <!-- Site wrapper -->
    <div class="wrapper">
        <!-- Navbar -->
        <?php require_once("../MainNav/nav.php") ?>
        <!-- /.navbar -->

        <?php require_once("../MainMenu/menu.php") ?>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>Evaluacion Desempeño 2024</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="#">Inicio</a></li>
                                <li class="breadcrumb-item active">Evaluacion</li>
                            </ol>
                        </div>
                    </div>
                </div><!-- /.container-fluid -->
            </section>

            <!-- Main content -->
            <section class="content">

                <!-- Default box -->
                <div class="card">



                    <!-- CONTENIDO-->

                    <form id="formulario_evaluacion" method="post">
                        <!-- CABECERA-->
                        <div class="card-header">

                            <input type="hidden" class="form-control" name="formulario_id" id="formulario_id"
                                value="<?php echo date('Ymd') . $_SESSION["user_nick"] ?>">

                            <div class="row">
                                <div class="col-sm-4">
                                    <!-- text input -->
                                    <div class="form-group">
                                        <label for="fech_eval">Fecha</label>
                                        <input type="text" class="form-control" name="fecha" id="fech_eval"
                                            value="<?php echo date("Y-m-d") ?>" readonly>
                                    </div>
                                </div>

                                <div class="col-sm-4">
                                    <!-- text input -->
                                    <div class="form-group">
                                        <label for="anio_eval">Año Evaluado</label>
                                        <input type="text" class="form-control" id="anio_eval" name="aaa_eval"
                                            value="2024" readonly>
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        <label>Tipo Evaluacion</label>
                                        <!-- radio -->
                                        <div class="form-group ">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="radioTipoEval"
                                                    id="radio_auto" value="A">
                                                <label class="form-check-label" for="radio_auto">Autoevaluacion</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" id="radio_coeva" type="radio"
                                                    name="radioTipoEval" value="C">
                                                <label class="form-check-label" for="radio_coeva">Coevaluacion</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" id="radio_subev" type="radio"
                                                    name="radioTipoEval" value="S">
                                                <label class="form-check-label" for="radio_subev">Sub Evaluacion</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>


                            </div>

                            <div class="row">

                               
                                <div class="col-sm-6">
                                    <!-- text input -->
                                    <div class="form-group">
                                        <label for="nomb_evaluad">Persona a evaluar:</label>
                                        <select class="select2" name="nomb_evaluad"
                                            id="nomb_evaluad" data-placeholder="Seleccionar empleado" style="width: 100%;"
                                            required>

                                        </select>
                                    </div>
                                </div>

                                <div class="col-sm-6">

                                    <input type="hidden" id="evaluador" name="evaluador"
                                        value="<?php echo $_SESSION["user_empl"] ?>" readonly>
                                    <!-- text input -->
                                    <div class="form-group">
                                        <label for="nombre_evaluador">Evaluador:</label>

                                        <input type="text" class="form-control"
                                            placeholder="<?php echo $_SESSION["nomb_empl"] ?>" id="nombre_evaluador"
                                            name="nombre_evaluador" disabled>
                                    </div>
                                </div>



                            </div>


                            <div class="row">





                            </div>


                        </div>
                        <div class="card-body">

                            <?php require_once("productividad.php") ?>
                            <?php require_once("laboral.php") ?>
                            <?php require_once('actitud.php') ?>
                            <?php require_once('liderazgo.php') ?>







            </section>
            <!-- /.content -->
        </div>
        <!-- /.content-wrapper -->

        <?php require_once("../MainFooter/footer.php") ?>

        <!-- Control Sidebar -->
        <aside class="control-sidebar control-sidebar-dark">
            <!-- Control sidebar content goes here -->
        </aside>
        <!-- /.control-sidebar -->
    </div>
    <!-- ./wrapper -->

    <?php require_once("../MainJS/JS.php") ?>


    <!-- Select2 -->
    <script src="../../public/plugins/select2/js/select2.full.min.js"></script>
    <script type="text/javascript" src="evaluacion.js"></script>
</body>

</html>
<?php
} else {
    header("location:" . Conectar::ruta() . "index.php");
}
?>