<?php
require_once "../../config/conexion.php";
if (isset($_SESSION["user_id"])) {
?>
    <!DOCTYPE html>
    <html lang="es">

    <?php require_once "../MainHead/head.php"; ?>
    <link rel="stylesheet" href="../../public/css/aprobaciones.css">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inbox Permisos</title>


    </head>

    <body class="hold-transition sidebar-mini layout-fixed layout-footer-fixed" style="background:#f4f6f9;">

        <!-- Navbar -->
        <?php require_once "../MainNav/nav.php"; ?>
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        <?php require_once "../MainMenu/menu.php"; ?>

        <!-- Simular content-wrapper de AdminLTE -->
        <div class="content-wrapper mb-2">

            <!-- Breadcrumb -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">
                                <i class="fas fa-inbox mr-2"></i>Gestión Solicitudes
                            </h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="#">Inicio</a></li>
                                <li class="breadcrumb-item active">Permisos</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card principal -->
            <!-- Contenido -->
            <div class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">

                            <div class="card card-outline card-info p-0 mb-0">
                                <div class="card-body p-0">
                                    <div class="inbox-wrapper">

                                        <!-- ══ SIDEBAR CARPETAS ══ -->
                                        <div class="inbox-sidebar bg-white">
                                            <div class="px-3 pt-3 pb-1">
                                                <small class="text-uppercase text-muted font-weight-bold" style="letter-spacing:1px;">Bandeja</small>
                                            </div>

                                            <div class="carpeta-item active" data-estado="1">
                                                <span><i class="fas fa-clock mr-2 text-warning"></i>Pendientes</span>
                                                <span class="badge badge-warning" id="badge-pendientes">3</span>
                                            </div>
                                            <div class="carpeta-item" data-estado="2">
                                                <span><i class="fas fa-check mr-2 text-info"></i>Aprobados</span>
                                                <span class="badge badge-info" id="badge-aprobados">8</span>
                                            </div>
                                            <div class="carpeta-item" data-estado="6">
                                                <span><i class="fas fa-times mr-2 text-danger"></i>Rechazados</span>
                                                <span class="badge badge-danger" id="badge-rechazados">2</span>
                                            </div>
                                            <div class="carpeta-item" data-estado="">
                                                <span><i class="fas fa-list mr-2 text-secondary"></i>Todos</span>
                                                <span class="badge badge-secondary" id="badge-todos">13</span>
                                            </div>
                                        </div>

                                        <!-- ══ PANEL LISTA ══ -->
                                        <div class="inbox-lista bg-white">

                                            <!-- Filtros -->
                                            <div class="p-2 border-bottom">
                                                <div class="form-group mb-2">
                                                    <div class="input-group input-group-sm">
                                                        <input type="text" class="form-control" id="filtro_busqueda"
                                                            placeholder="Buscar por cédula o nombre…">
                                                        <div class="input-group-append">
                                                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group mb-2">
                                                    <input type="text" class="form-control form-control-sm" id="filtro_fechas"
                                                        placeholder="Rango de fechas…">
                                                </div>
                                                <div class="d-flex" style="gap:6px;">
                                                    <button class="btn btn-sm btn-info flex-fill" id="btn_filtrar">
                                                        <i class="fas fa-filter mr-1"></i>Filtrar
                                                    </button>
                                                    <button class="btn btn-sm btn-default flex-fill" id="btn_limpiar_filtros">
                                                        <i class="fas fa-undo mr-1"></i>Limpiar
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Contador -->
                                            <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
                                                <small class="text-muted" id="lista-contador">3 solicitudes</small>
                                            </div>

                                            <!-- Lista -->
                                            <div class="lista-body">

                                                <!-- Item pendiente activo -->
                                                <div class="solicitud-item active">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <span class="nombre">CRISTIAN ARCINIEGAS</span>
                                                        <span class="badge-pendiente">Pendiente</span>
                                                    </div>
                                                    <div class="meta">1.095.934.409</div>
                                                    <div class="meta">
                                                        <i class="fas fa-calendar-alt mr-1"></i>14/03/2026
                                                        &nbsp;·&nbsp;
                                                        <i class="fas fa-tag mr-1"></i>Cita Médica
                                                    </div>
                                                </div>

                                                <!-- Item aprobado -->
                                                <div class="solicitud-item">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <span class="nombre">MARIA RODRIGUEZ</span>
                                                        <span class="badge-aprobado">Aprobado</span>
                                                    </div>
                                                    <div class="meta">63.456.789</div>
                                                    <div class="meta">
                                                        <i class="fas fa-calendar-alt mr-1"></i>10/03/2026
                                                        &nbsp;·&nbsp;
                                                        <i class="fas fa-tag mr-1"></i>Diligencia Personal
                                                    </div>
                                                </div>

                                                <!-- Item rechazado -->
                                                <div class="solicitud-item">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <span class="nombre">ANA GOMEZ</span>
                                                        <span class="badge-rechazado">Rechazado</span>
                                                    </div>
                                                    <div class="meta">27.654.321</div>
                                                    <div class="meta">
                                                        <i class="fas fa-calendar-alt mr-1"></i>05/03/2026
                                                        &nbsp;·&nbsp;
                                                        <i class="fas fa-tag mr-1"></i>Médico EPS
                                                    </div>
                                                </div>

                                            </div>
                                        </div>

                                        <!-- ══ PANEL DETALLE ══ -->
                                        <div class="inbox-detalle">
                                            <div class="card card-outline card-dark mb-0">

                                                <!-- Header detalle -->
                                                <div class="card-header d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h5 class="card-title mb-0">
                                                            <i class="fas fa-file-alt mr-2 text-dark"></i>Solicitud de Permiso
                                                        </h5>
                                                        <small class="text-muted">Solicitado el 14/03/2026 08:32 a.m.</small>
                                                    </div>
                                                    <span class="badge-pendiente">Pendiente</span>
                                                </div>

                                                <div class="card-body">

                                                    <!-- Info empleado -->
                                                    <div class="row mb-3">
                                                        <div class="col-md-4">
                                                            <small class="text-uppercase text-muted font-weight-bold d-block" style="font-size:10px; letter-spacing:0.5px;">
                                                                <i class="fas fa-user mr-1"></i>Empleado
                                                            </small>
                                                            <span class="font-weight-bold">CRISTIAN ARCINIEGAS PORTILLA</span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <small class="text-uppercase text-muted font-weight-bold d-block" style="font-size:10px; letter-spacing:0.5px;">
                                                                <i class="fas fa-id-card mr-1"></i>Cédula
                                                            </small>
                                                            <span>1.095.934.409</span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <small class="text-uppercase text-muted font-weight-bold d-block" style="font-size:10px; letter-spacing:0.5px;">
                                                                <i class="fas fa-tag mr-1"></i>Tipo Permiso
                                                            </small>
                                                            <span>Cita Médica EPS</span>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <small class="text-uppercase text-muted font-weight-bold d-block" style="font-size:10px; letter-spacing:0.5px;">
                                                                <i class="fas fa-calendar mr-1"></i>Fecha
                                                            </small>
                                                            <span>14/03/2026</span>
                                                        </div>
                                                    </div>

                                                    <!-- Horas -->
                                                    <div class="row mb-3">
                                                        <div class="col-md-3">
                                                            <small class="text-uppercase text-muted font-weight-bold d-block" style="font-size:10px; letter-spacing:0.5px;">
                                                                <i class="fas fa-sign-out-alt mr-1"></i>Hora Salida
                                                            </small>
                                                            <span>08:00</span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <small class="text-uppercase text-muted font-weight-bold d-block" style="font-size:10px; letter-spacing:0.5px;">
                                                                <i class="fas fa-sign-in-alt mr-1"></i>Hora Entrada
                                                            </small>
                                                            <span>10:30</span>
                                                        </div>
                                                    </div>

                                                    <!-- Justificación -->
                                                    <small class="text-uppercase text-muted font-weight-bold d-block mb-2" style="font-size:10px; letter-spacing:0.5px;">
                                                        <i class="fas fa-comment-alt mr-1"></i>Justificación
                                                    </small>
                                                    <div class="justif-box mb-3">
                                                        Cita médica programada con especialista en el Hospital Universitario Erasmo Meoz.
                                                        Se adjunta soporte de la cita.
                                                    </div>

                                                </div>

                                                <!-- Footer acciones -->
                                                <div class="card-footer d-flex align-items-center" style="gap:10px;">
                                                    <button class="btn btn-info btn-sm">
                                                        <i class="fas fa-check mr-1"></i>Aprobar Permiso
                                                    </button>
                                                    <button class="btn btn-danger btn-sm">
                                                        <i class="fas fa-times mr-1"></i>Rechazar
                                                    </button>
                                                    <small class="text-muted ml-auto">
                                                        <i class="fas fa-info-circle mr-1"></i>Al aprobar se solicitará su firma digital
                                                    </small>
                                                </div>

                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>


        </div>

        <?php require_once("../MainFooter/footer.php") ?>
        <?php require_once "../MainJS/JS.php" ?>
        <script src="../../config/config.js"></script>
        <script type="text/javascript" src="inboxSol.js"></script>
    </body>

    </html>
<?php
} else {
    header("location:" . Conectar::ruta() . "index.php");
}
?>