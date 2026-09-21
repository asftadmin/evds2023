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
<title>Liquidación de Horas</title>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <?php require_once("../MainNav/nav.php"); ?>
    <?php require_once("../MainMenu/menu.php"); ?>
    <div class="content-wrapper">
        <section class="content-header">
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
                <div class="row mb-2">
                    <div class="col-sm-7">
                        <h1><i class="fas fa-calculator mr-2"></i>Liquidación de Horas</h1>
                    </div>
                    <div class="col-sm-5">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="../home/home2.php">Inicio</a></li>
                            <li class="breadcrumb-item active">Liquidación</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>
        <section class="content">
            <div class="container-fluid">
                <div id="alerta-contexto" class="alert alert-info py-2">
                    <i class="fas fa-lock mr-1"></i>
                    <span id="texto-contexto">Validando acceso contable...</span>
                </div>
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-filter mr-1"></i>Periodo, trabajador y estado
                        </h3>
                        <div class="card-tools">
                            <button
                                type="button"
                                class="btn btn-sm btn-secondary"
                                id="btn-parametrizacion"
                            >
                                <i class="fas fa-cogs mr-1"></i>
                                Ver parametrización
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="filtro_fechas">Periodo</label>
                                <input type="text" class="form-control" id="filtro_fechas">
                            </div>
                            <div class="col-md-4">
                                <label for="empleado_id">Trabajador</label>
                                <select class="form-control select2" id="empleado_id" style="width:100%">
                                    <option value="">Todos los trabajadores</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="estado_liquidacion">Estado de clasificación</label>
                                <select class="form-control" id="estado_liquidacion">
                                    <option value="">Todos los estados</option>
                                    <option value="PENDIENTE">Pendiente</option>
                                    <option value="CLASIFICADA">Clasificada</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="estado_jornada">Estado de jornada</label>
                                <select class="form-control" id="estado_jornada">
                                    <option value="">Todos los estados</option>
                                    <option value="BORRADOR">Borrador</option>
                                    <option value="PENDIENTE_APROBACION">Pendiente de aprobación</option>
                                    <option value="APROBADO">Aprobado</option>
                                    <option value="PENDIENTE_LIQUIDACION">Pendiente de liquidación</option>
                                    <option value="LIQUIDADO">Liquidada</option>
                                    <option value="RECHAZADO">Rechazado</option>
                                    <option value="PENDIENTE_CORRECCION">Pendiente de corrección</option>
                                    <option value="CORREGIDO">Corregido</option>
                                    <option value="ANULADO">Anulado</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end flex-wrap mt-2">
                                <button type="button" class="btn btn-info mr-2" id="btn-consultar">
                                    <i class="fas fa-search mr-1"></i>Consultar
                                </button>
                                <button type="button" class="btn btn-secondary" id="btn-restablecer">
                                    <i class="fas fa-undo mr-1"></i>Restablecer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-list mr-1"></i>Jornadas aprobadas
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center flex-wrap mb-2">
                            <button type="button" class="btn btn-success mr-3" id="btn-liquidar-seleccionadas" disabled>
                                <i class="fas fa-calculator mr-1"></i>Liquidar seleccionadas
                            </button>
                            <span id="conteo-liquidacion" class="text-muted" aria-live="polite">0 seleccionadas</span>
                        </div>
                        <p class="small text-muted">
                            La casilla del encabezado selecciona las filas de todas las páginas del resultado filtrado.
                            Si una fila ya tiene segmentos, se solicitará confirmar su recálculo.
                        </p>
                        <div id="resultado-liquidacion" class="alert d-none" role="status" aria-live="polite"></div>
                        <div class="table-responsive">
                            <table id="tabla-liquidacion" class="table table-bordered table-striped table-hover" width="100%"
                                data-usuario-id="<?php echo (int) $_SESSION['user_id']; ?>">
                                <thead>
                                    <tr>
                                        <th>
                                            <input type="checkbox" id="seleccionar-liquidacion"
                                                aria-label="Seleccionar todas las jornadas filtradas">
                                        </th>
                                        <th>Empleado</th>
                                        <th>Fecha</th>
                                        <th>Entrada</th>
                                        <th>Salida</th>
                                        <th>Horas ordinarias</th>
                                        <th>Ubicación</th>
                                        <th>Horas extras autorizadas</th>
                                        <th>Estado de clasificación</th>
                                        <th>Estado de jornada</th>
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
<?php require_once("../MainJS/JS.php"); ?>
<script src="liquidacion.js"></script>
</body>
</html>
