<?php
require_once "../../config/conexion.php";
if (!isset($_SESSION["user_id"])) {
    header("location:" . Conectar::ruta() . "index.php");
    exit;
}
if (empty($_SESSION["csrf_jornadas"])) {
    $_SESSION["csrf_jornadas"] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="es">
<?php require_once("../MainHead/head.php"); ?>
<link rel="stylesheet" href="jornadas.css">
<title>Reportes Contables de Jornadas</title>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <?php require_once("../MainNav/nav.php"); ?>
    <?php require_once("../MainMenu/menu.php"); ?>
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <h1>
                    <i class="fas fa-file-invoice-dollar mr-2"></i>
                    Reportes Contables de Jornadas
                </h1>
            </div>
        </section>
        <section class="content">
            <div class="container-fluid">
                <input
                    type="hidden"
                    id="csrf_token"
                    value="<?php echo htmlspecialchars(
                        $_SESSION["csrf_jornadas"],
                        ENT_QUOTES,
                        "UTF-8"
                    ); ?>"
                >
                <div id="alerta-contexto" class="alert alert-info py-2">
                    <i class="fas fa-lock mr-1"></i>
                    <span id="texto-contexto">Validando acceso contable...</span>
                </div>

                <div class="card card-outline card-info">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-layer-group mr-1"></i>
                            Lotes documentales
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-end">
                            <div class="col-md-5">
                                <label for="nombre_lote">Nombre del lote</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="nombre_lote"
                                    maxlength="120"
                                    placeholder="Ej. Nómina julio 2026"
                                >
                            </div>
                            <div class="col-md-3">
                                <label for="fecha_corte">Fecha de corte</label>
                                <input type="date" class="form-control" id="fecha_corte">
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-info" id="btn-crear-lote">
                                    <i class="fas fa-plus mr-1"></i>Preparar lote
                                </button>
                            </div>
                        </div>
                        <small class="form-text text-muted mt-2">
                            El sistema propone un periodo distinto por empleado a partir
                            del último cierre; para el primer cierre usa su primera jornada.
                        </small>

                        <div class="table-responsive mt-3">
                            <table
                                id="tabla-lotes"
                                class="table table-bordered table-striped"
                                width="100%"
                            >
                                <thead>
                                    <tr>
                                        <th>Creado</th>
                                        <th>Lote</th>
                                        <th>Versión</th>
                                        <th>Corte</th>
                                        <th>Estado</th>
                                        <th>Empleados</th>
                                        <th>Listos</th>
                                        <th>Observaciones</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card card-outline card-primary d-none" id="card-detalle">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-users mr-1"></i>
                            <span id="titulo-lote">Detalle del lote</span>
                        </h3>
                        <div class="card-tools" id="acciones-lote"></div>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-light border py-2">
                            <strong>Lectura de estados:</strong>
                            Listo y Sin novedad permiten cerrar. Pendiente requiere
                            aprobación o clasificación. Bloqueado requiere corregir
                            inconsistencias.
                        </div>
                        <div class="table-responsive">
                            <table
                                id="tabla-empleados-lote"
                                class="table table-bordered table-striped"
                                width="100%"
                            >
                                <thead>
                                    <tr>
                                        <th>Empleado</th>
                                        <th>Documento</th>
                                        <th>Desde</th>
                                        <th>Hasta</th>
                                        <th>Origen</th>
                                        <th>Estado</th>
                                        <th>Jornadas</th>
                                        <th>Pendientes</th>
                                        <th>Horas</th>
                                        <th>Diagnóstico</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
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

<div class="modal fade" id="modal-periodo" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajustar periodo del empleado</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="periodo_fila_id">
                <div class="form-group">
                    <label>Empleado</label>
                    <input type="text" class="form-control" id="periodo_empleado" readonly>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="periodo_desde">Desde</label>
                        <input type="date" class="form-control" id="periodo_desde">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="periodo_hasta">Hasta</label>
                        <input type="date" class="form-control" id="periodo_hasta">
                    </div>
                </div>
                <div class="form-group mb-0">
                    <label for="periodo_motivo">Motivo del ajuste</label>
                    <textarea
                        class="form-control"
                        id="periodo_motivo"
                        rows="3"
                        maxlength="500"
                    ></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="btn-guardar-periodo">
                    Guardar y validar
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once("../MainJS/JS.php"); ?>
<script src="reporte_contable.js"></script>
</body>
</html>
