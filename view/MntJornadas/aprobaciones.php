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
<title>Aprobaciones de Jornadas</title>
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
                                <i class="fas fa-user-check mr-2"></i>
                                Aprobaciones de Jornadas
                            </h1>
                        </div>
                        <div class="col-sm-5">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item">
                                    <a href="../home/home2.php">Inicio</a>
                                </li>
                                <li class="breadcrumb-item active">
                                    Aprobaciones
                                </li>
                            </ol>
                        </div>
                    </div>
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
                        <i class="fas fa-info-circle mr-1"></i>
                        <span id="texto-contexto">Validando jefe...</span>
                    </div>

                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-inbox mr-1"></i>
                                Jornadas pendientes de decisión
                            </h3>
                        </div>

                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-5">
                                    <label for="filtro_fechas">Periodo</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="filtro_fechas"
                                        autocomplete="off"
                                    >
                                </div>
                                <div class="col-md-7 d-flex align-items-end">
                                    <button
                                        type="button"
                                        class="btn btn-info mr-2"
                                        id="btn-filtrar"
                                    >
                                        <i class="fas fa-search mr-1"></i>Consultar
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-secondary"
                                        id="btn-limpiar-filtro"
                                    >
                                        <i class="fas fa-undo mr-1"></i>Restablecer
                                    </button>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table
                                    id="tabla-aprobaciones"
                                    class="table table-bordered table-striped table-hover"
                                    width="100%"
                                >
                                    <thead>
                                        <tr>
                                            <th>Empleado</th>
                                            <th>Documento</th>
                                            <th>Día / fecha</th>
                                            <th>Entrada</th>
                                            <th>Salida</th>
                                            <th>Horas</th>
                                            <th>Ubicación</th>
                                            <th>Actividad</th>
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
    <script src="aprobaciones.js"></script>
</body>
</html>
