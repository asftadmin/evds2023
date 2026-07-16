<?php
require_once("../../config/conexion.php");

if (isset($_SESSION["user_id"])) {
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php require_once("../MainHead/head.php"); ?>
    <title>Reporte de evaluación de desempeño</title>
    <style>
        /* El ancho se limita al control del formulario para no ensanchar el dropdown flotante de Select2. */
        .filtro-select2,
        .filtro-select2 .form-group {
            min-width: 0;
        }
        .filtro-select2 .select2-container {
            width: 100% !important;
            max-width: 100%;
        }
        /* Los textos extensos se parten dentro del dropdown sin producir desbordamiento horizontal. */
        .select2-results__option {
            overflow-wrap: anywhere;
            white-space: normal;
        }
        .tipo-evaluacion { margin-right: .35rem; margin-bottom: .35rem; }
        @media (max-width: 575.98px) {
            .acciones-reporte .btn { width: 100%; margin-bottom: .5rem; }
        }
    </style>
</head>
<body class="hold-transition sidebar-mini">
    <div class="wrapper">
        <?php require_once("../MainNav/nav.php"); ?>
        <?php require_once("../MainMenu/menu.php"); ?>

        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-8">
                            <h1>Reporte individual de evaluación de desempeño</h1>
                        </div>
                    </div>
                </div>
            </section>

            <section class="content">
                <div class="container-fluid pb-4">
                    <div class="card card-info card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Criterios de consulta</h3>
                        </div>
                        <div class="card-body">
                            <div class="row align-items-end">
                                <div class="col-lg-3 col-md-4 filtro-select2">
                                    <div class="form-group">
                                        <label for="periodo">Periodo <span class="text-danger">*</span></label>
                                        <select id="periodo" class="form-control" required>
                                            <option value="">Seleccione un periodo</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-7 col-md-6 filtro-select2">
                                    <div class="form-group">
                                        <label for="empleado_id">Empleado activo <span class="text-danger">*</span></label>
                                        <select id="empleado_id" class="form-control" required></select>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-2 acciones-reporte">
                                    <div class="form-group">
                                        <button type="button" id="btn_buscar" class="btn btn-info btn-block">
                                            <i class="fas fa-search"></i> Buscar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="resultado_reporte" class="card card-success card-outline" style="display: none;">
                        <div class="card-header">
                            <h3 class="card-title">Información del colaborador</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <small class="text-muted d-block">Documento</small>
                                    <span id="resultado_documento" class="font-weight-bold"></span>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <small class="text-muted d-block">Nombre</small>
                                    <span id="resultado_nombre" class="font-weight-bold"></span>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <small class="text-muted d-block">Cargo</small>
                                    <span id="resultado_cargo" class="font-weight-bold"></span>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <small class="text-muted d-block">Periodo</small>
                                    <span id="resultado_periodo" class="font-weight-bold"></span>
                                </div>
                                <div class="col-12">
                                    <small class="text-muted d-block mb-1">Evaluaciones encontradas</small>
                                    <div id="resultado_tipos"></div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-right acciones-reporte">
                            <button type="button" id="btn_pdf" class="btn btn-danger" disabled>
                                <i class="fas fa-file-pdf"></i> Generar reporte PDF
                            </button>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <?php require_once("../MainFooter/footer.php"); ?>
    </div>

    <?php require_once("../MainJS/JS.php"); ?>
    <script src="reporte_desempeno.js"></script>
</body>
</html>
<?php
} else {
    header("Location:" . Conectar::ruta() . "index.php");
}
?>
