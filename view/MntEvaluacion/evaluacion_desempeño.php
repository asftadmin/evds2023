<?php
require_once("../../config/conexion.php");

if (isset($_SESSION["user_id"])) {
?>

    <!DOCTYPE html>
    <html>
    <?php require_once("../MainHead/head.php"); ?>

    <body class="hold-transition sidebar-mini">
        <div class="wrapper">

            <?php require_once("../MainNav/nav.php"); ?>
            <?php require_once("../MainMenu/menu.php"); ?>

            <div class="content-wrapper">

                <section class="content-header">
                    <div class="container-fluid">
                        <h1>Evaluación de Desempeño</h1>
                    </div>
                </section>

                <section class="content">
                    <div class="container-fluid">

                        <form id="form_evaluacion_desempeno">

                            <input type="hidden" id="empleado_logueado_id" value="<?php echo $_SESSION["id_empl"]; ?>">

                            <div class="card card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">Registro de evaluación</h3>
                                </div>

                                <div class="card-body">

                                    <div class="row">

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Tipo de evaluación <span class="text-danger">*</span></label>
                                                <select id="tipo_evaluacion" name="tipo_evaluacion"
                                                    class="form-control select2" required>
                                                    <option value="">Seleccione</option>
                                                    <option value="AUTOEVALUACION">Autoevaluación</option>
                                                    <option value="COEVALUACION">Coevaluación</option>
                                                    <option value="SUBEVALUACION">Subevaluación</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-4" id="grupo_empleado">
                                            <div class="form-group">
                                                <label>Empleado evaluado <span class="text-danger">*</span></label>
                                                <select id="empleado_id" name="empleado_id" class="form-control select2"
                                                    required>
                                                    <option value="">Seleccione</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Periodo de evaluación <span class="text-danger">*</span></label>
                                                <select id="anio" name="anio" class="form-control select2" required>
                                                    <option value="">Seleccione</option>
                                                    <option value="2025">2025</option>
                                                </select>
                                            </div>
                                        </div>

                                    </div>

                                    <div class="alert alert-info">
                                        <strong>Escala de calificación:</strong><br>
                                        5 Excelente - Supera lo esperado, genera valor adicional y es ejemplo para
                                        otros.<br>
                                        4 Bueno - Cumple adecuadamente y de manera constante con lo esperado.<br>
                                        3 Aceptable - Cumple parcialmente, pero requiere seguimiento o mejora.<br>
                                        2 Bajo - Presenta fallas frecuentes que afectan el proceso o el equipo.<br>
                                        1 Crítico - Incumple de forma significativa o genera riesgos, sobrecostos o
                                        afectaciones importantes.
                                    </div>

                                    <div id="contenedor_preguntas"></div>

                                </div>

                                <div class="card-footer text-right">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Finalizar evaluación
                                    </button>
                                </div>
                            </div>

                        </form>

                    </div>
                </section>

            </div>

            <?php require_once("../MainFooter/footer.php"); ?>

        </div>

        <?php require_once("../MainJS/JS.php"); ?>
        <script src="evaluacion_desempeno.js"></script>

    </body>

    </html>

<?php
} else {
    header("Location:" . Conectar::ruta() . "index.php");
}
?>