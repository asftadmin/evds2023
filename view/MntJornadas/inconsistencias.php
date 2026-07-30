<?php
require_once "../../config/conexion.php";
if (!isset($_SESSION["user_id"])) {
    header("location:" . Conectar::ruta() . "index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<?php require_once("../MainHead/head.php"); ?>
<link rel="stylesheet" href="jornadas.css">
<title>Inconsistencias Jornadas</title>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <?php require_once("../MainNav/nav.php"); ?>
    <?php require_once("../MainMenu/menu.php"); ?>
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid"><h1>
                <i class="fas fa-exclamation-triangle mr-2"></i>Inconsistencias Jornadas
            </h1></div>
        </section>
        <section class="content">
            <div class="container-fluid">
                <div id="alerta-contexto" class="alert alert-info py-2">
                    <i class="fas fa-lock mr-1"></i>
                    <span id="texto-contexto">Validando acceso contable...</span>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-5">
                                <label for="filtro_fechas">Periodo</label>
                                <input type="text" class="form-control" id="filtro_fechas">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button class="btn btn-info" id="btn-consultar">
                                    <i class="fas fa-search mr-1"></i>Consultar
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table id="tabla-inconsistencias" class="table table-bordered table-striped" width="100%">
                                <thead><tr>
                                    <th>Empleado</th><th>Fecha</th><th>Entrada</th>
                                    <th>Salida</th><th>Horas</th><th>Ubicación</th>
                                    <th>Detalle</th><th>Estado</th>
                                </tr></thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <?php require_once("../MainFooter/footer.php"); ?>
</div>
<?php require_once("../MainJS/JS.php"); ?>
<script src="inconsistencias.js"></script>
</body>
</html>
