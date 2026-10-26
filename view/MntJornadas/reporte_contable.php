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

                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item"><a class="nav-link active" id="tab-lotes" data-toggle="tab" href="#panel-lotes" role="tab" aria-controls="panel-lotes" aria-selected="true">Lotes</a></li>
                    <li class="nav-item"><a class="nav-link" id="tab-horas-extras" data-toggle="tab" href="#panel-horas-extras" role="tab" aria-controls="panel-horas-extras" aria-selected="false">Exportar horas extras</a></li>
                </ul>
                <div class="tab-content">
                <div class="tab-pane fade show active" id="panel-lotes" role="tabpanel" aria-labelledby="tab-lotes">
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
                <div class="tab-pane fade" id="panel-horas-extras" role="tabpanel" aria-labelledby="tab-horas-extras">
                    <div class="card card-outline card-success">
                        <div class="card-body">
                            <p id="horas-resultado" class="mb-2" role="status">Seleccione un periodo y pulse Consultar.</p>
                            <div class="row align-items-end">
                                <div class="col-md-6 form-group">
                                    <label for="horas_periodo">Periodo (16 al 15)</label>
                                    <input type="text" id="horas_periodo" class="form-control" readonly>
                                </div>
                                <div class="col-md-6 form-group d-flex align-items-center flex-wrap">
                                    <button type="button" class="btn btn-primary" id="btn-consultar-horas" disabled><i class="fas fa-search mr-1"></i>Consultar</button>
                                    <div id="horas-exportar" class="ml-2"></div>
                                </div>
                            </div>
                            <span class="d-block small text-muted mb-1">Solo jornadas liquidadas. Cantidades en horas decimales (1,50 = 1 hora y 30 minutos).</span>
                            <span class="d-block small text-muted mb-2">Salario/IBC consultado en Siesa. Valor hora = IBC / 30 / 7.3333. Factores: diurnas 1,25; nocturnas 1,75; diurnas festivas 2,00; nocturnas festivas 2,50. El total excluye el recargo nocturno, pendiente de factor. Valores monetarios en COP.</span>
                            <div class="table-responsive">
                                <table id="tabla-horas-extras" class="table table-bordered table-striped" width="100%">
                                    <thead><tr>
                                        <th>Trabajador</th><th>Documento</th><th>Salario / IBC</th><th>Valor hora</th>
                                        <th>Recargos nocturnos</th><th>Valor recargo nocturno</th>
                                        <th>Horas extras diurnas</th><th>Valor extras diurnas</th>
                                        <th>Horas extras nocturnas</th><th>Valor extras nocturnas</th>
                                        <th>Horas diurnas festivas</th><th>Valor diurnas festivas</th>
                                        <th>Horas nocturnas festivas</th><th>Valor nocturnas festivas</th>
                                        <th>Total sin recargo nocturno</th>
                                    </tr></thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
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
<script src="periodo_jornadas.js"></script>
</body>
</html>
