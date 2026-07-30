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
<title>Configuración Jornadas</title>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <?php require_once("../MainNav/nav.php"); ?>
    <?php require_once("../MainMenu/menu.php"); ?>

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-7">
                        <h1>
                            <i class="fas fa-calendar-alt mr-2"></i>
                            Fechas Especiales
                        </h1>
                    </div>
                    <div class="col-sm-5">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item">
                                <a href="../home/home2.php">Inicio</a>
                            </li>
                            <li class="breadcrumb-item active">Configuración</li>
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

                <div class="alert alert-warning py-2">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    Cambiar una fecha especial deja pendientes de recalcular las
                    liquidaciones que atraviesen ese día.
                </div>

                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-plus-circle mr-1"></i>
                            Registrar fecha especial
                        </h3>
                    </div>
                    <form id="form-festivo" autocomplete="off">
                        <div class="card-body">
                            <input
                                type="hidden"
                                id="csrf_token"
                                value="<?php echo htmlspecialchars(
                                    $_SESSION["csrf_jornadas"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>"
                            >
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="fecha">Fecha</label>
                                    <input
                                        type="date"
                                        class="form-control"
                                        id="fecha"
                                        required
                                    >
                                </div>
                                <div class="col-md-8">
                                    <label for="descripcion">Descripción</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="descripcion"
                                        maxlength="160"
                                        placeholder="Opcional"
                                    >
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary" id="btn-guardar">
                                <i class="fas fa-save mr-1"></i>Guardar
                            </button>
                            <button type="button" class="btn btn-default" id="btn-limpiar">
                                <i class="fas fa-eraser mr-1"></i>Limpiar
                            </button>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-calendar-check mr-1"></i>
                            Calendario configurado
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="anio">Año</label>
                                <input
                                    type="number"
                                    class="form-control"
                                    id="anio"
                                    min="2000"
                                    max="2100"
                                    value="<?php echo date('Y'); ?>"
                                >
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="button" class="btn btn-info" id="btn-consultar">
                                    <i class="fas fa-search mr-1"></i>Consultar
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table
                                id="tabla-festivos"
                                class="table table-bordered table-striped table-hover"
                                width="100%"
                            >
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Día</th>
                                        <th>Descripción</th>
                                        <th>Estado</th>
                                        <th>Creado por</th>
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
<script src="configuracion.js"></script>
</body>
</html>
