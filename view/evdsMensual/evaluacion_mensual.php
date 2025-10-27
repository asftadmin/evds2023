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
                            <h1>Evaluacion Desempeño Mensual</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="#">Inicio</a></li>
                                <li class="breadcrumb-item active">Evaluacion Mensual</li>
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

                    <form id="formulario_evds_mensual" method="post">

                        <!-- CABECERA-->
                        <div class="card-header">

                            <input type="hidden" class="form-control" name="formulario_id_txt" id="formulario_id_txt"
                                value="<?php echo date('Ymd') . $_SESSION["user_nick"] ?>">

                            <div class="row">
                                <div class="col-sm-4">
                                    <!-- text input -->
                                    <div class="form-group">
                                        <label for="txt_fech_eval">Fecha</label>
                                        <input type="text" class="form-control" name="txt_fech_eval" id="txt_fech_eval"
                                            value="<?php echo date("Y-m-d") ?>" readonly>
                                    </div>
                                </div>

                                <div class="col-sm-4">
                                    <!-- text input -->
                                    <div class="form-group">
                                        <label for="txt_anio_eval">Año Evaluado</label>
                                        <input type="text" class="form-control" id="txt_anio_eval" name="txt_anio_eval"
                                            value="2025" readonly>
                                    </div>
                                </div>

                                <div class="col-sm-4">
                                    <!-- text input -->
                                    <label for="txt_mes_eval">Mes Evaluado</label>
                                    <select class="form-control " name="txt_mes_eval" id="txt_mes_eval"
                                        style="width: 100%;" required>

                                    </select>
                                </div>


                            </div> 

                            <div class="row">

                                <div class="col-sm-4">
                                    <div class="form-group">
                                        <label for="">Tipo Evaluacion</label>
                                        <!-- radio -->
                                        <div class="form-group ">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" id="radio_subev" type="radio"
                                                    name="radioTipoEval" value="S" checked>
                                                <label class="form-check-label" for="radio_subev">Sub Evaluacion</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-sm-4">
                                    <!-- text input -->
                                    <div class="form-group">
                                        <label for="txt_nomb_evaluad">Persona a evaluar:</label>
                                        <select class="select2" name="txt_nomb_evaluad" id="txt_nomb_evaluad"
                                            data-placeholder="Seleccionar Empleado" style="width: 100%;" required>

                                        </select>
                                    </div>
                                </div>

                                <div class="col-sm-4">

                                    <input type="hidden" id="txt_id_evaluador" name="txt_id_evaluador"
                                        value="<?php echo $_SESSION["id_empl"] ?>" readonly>
                                    <!-- text input -->
                                    <div class="form-group">
                                        <label for="txt_nombre_evaluador">Evaluador:</label>

                                        <input type="text" class="form-control"
                                            placeholder="<?php echo $_SESSION["nomb_empl"] ?>" id="txt_nombre_evaluador"
                                            name="txt_nombre_evaluador" disabled>
                                    </div>
                                </div>



                            </div>

                        </div>
                        <div class="card-body">
                            <p style="font-size: 36px; color:#009BA9; text-align: center;">Nota: Seleccione de 1 a 5,
                                siedo 1 la
                                calificación
                                mas baja y 5
                                la
                                calificación mas alta.
                            </p>
                            <div class="callout callout-info m-5" id="card5">

                                <div class="form-group row">
                                    <label for="pregunta1" class="col-sm-8 col-form-label text-justify">1. CUMPLE SU
                                        HORARIO
                                        LABORAL</label>
                                    <div class="col-sm-4">
                                        <select class="form-control " name="pregunta1" id="pregunta1"
                                            style="width: 100%;" required>
                                            <option selected="selected">Seleccione una respuesta</option>
                                            <option value="5">5</option>
                                            <option value="4">4</option>
                                            <option value="3">3</option>
                                            <option value="2">2</option>
                                            <option value="1">1</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="pregunta2" class="col-sm-8 col-form-label text-justify">2. USA
                                        CORRECTAMENTE
                                        SU DOTACIÓN Y ELEMENTOS DE PROTECCIÓN PERSONAL (EPP)</label>
                                    <div class="col-sm-4">
                                        <select class="form-control " name="pregunta2" id="pregunta2"
                                            style="width: 100%;" required>
                                            <option selected="selected">Seleccione una respuesta</option>
                                            <option value="5">5</option>
                                            <option value="4">4</option>
                                            <option value="3">3</option>
                                            <option value="2">2</option>
                                            <option value="1">1</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="pregunta3" class="col-sm-8 col-form-label text-justify">3. CUMPLE CON
                                        LAS
                                        ÓRDENES DE SUS SUPERIORES</label>
                                    <div class="col-sm-4">
                                        <select class="form-control " name="pregunta3" id="pregunta3"
                                            style="width: 100%;" required>
                                            <option selected="selected">Seleccione una respuesta</option>
                                            <option value="5">5</option>
                                            <option value="4">4</option>
                                            <option value="3">3</option>
                                            <option value="2">2</option>
                                            <option value="1">1</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="pregunta4" class="col-sm-8 col-form-label text-justify">4. CUIDA LOS
                                        EQUIPOS
                                        Y HERRAMIENTAS DE LA EMPRESA</label>
                                    <div class="col-sm-4">
                                        <select class="form-control " name="pregunta4" id="pregunta4"
                                            style="width: 100%;" required>
                                            <option selected="selected">Seleccione una respuesta</option>
                                            <option value="5">5</option>
                                            <option value="4">4</option>
                                            <option value="3">3</option>
                                            <option value="2">2</option>
                                            <option value="1">1</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="pregunta5" class="col-sm-8 col-form-label text-justify">5. CUMPLE A
                                        TIEMPO
                                        CON LOS PREOPERACIONALES, REPORTE DE EQUIPOS Y/O TAREAS ASIGNADAS</label>
                                    <div class="col-sm-4">
                                        <select class="form-control " name="pregunta5" id="pregunta5"
                                            style="width: 100%;" required>
                                            <option selected="selected">Seleccione una respuesta</option>
                                            <option value="5">5</option>
                                            <option value="4">4</option>
                                            <option value="3">3</option>
                                            <option value="2">2</option>
                                            <option value="1">1</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="pregunta6" class="col-sm-8 col-form-label text-justify">6. ASISTE A LAS
                                        CAPACITACIONES IMPARTIDAS POR EL PERSONAL SST (SEGURIDAD Y SALUD EN EL
                                        TRABAJO)</label>
                                    <div class="col-sm-4">
                                        <select class="form-control " name="pregunta6" id="pregunta6"
                                            style="width: 100%;" required>
                                            <option selected="selected">Seleccione una respuesta</option>
                                            <option value="5">5</option>
                                            <option value="4">4</option>
                                            <option value="3">3</option>
                                            <option value="2">2</option>
                                            <option value="1">1</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="pregunta7" class="col-sm-8 col-form-label text-justify">7. MANTIENE EL
                                        ORDEN
                                        Y EL ASEO EN SU SITIO DE TRABAJO</label>
                                    <div class="col-sm-4">
                                        <select class="form-control " name="pregunta7" id="pregunta7"
                                            style="width: 100%;" required>
                                            <option selected="selected">Seleccione una respuesta</option>
                                            <option value="5">5</option>
                                            <option value="4">4</option>
                                            <option value="3">3</option>
                                            <option value="2">2</option>
                                            <option value="1">1</option>
                                        </select>
                                    </div>
                                </div>



                            </div>


                        </div>

                        <div class="card-footer" id="cardFooter">
                            <div class="row justify-content-center">
                                <textarea id="txt_eval_observaciones" class="textarea" style="resize: none;"
                                    name="txt_eval_observaciones" rows="4" cols="100"
                                    placeholder="Escribe Aquí tus observaciones" autocapitalize="sentences"
                                    spellcheck="true" maxlength="2000"></textarea>
                            </div>

                            <div class="d-flex justify-content-center">
                                <button type="submit" id="btnEnviarMes" name="action" value="add"
                                    class="btn btn-bg btn-info mt-3">Enviar</button>
                            </div>


                        </div>

                    </form>


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
    <script type="text/javascript" src="evdsMes.js"></script>
</body>

</html>
<?php
} else {
    header("location:" . Conectar::ruta() . "index.php");
}
?>