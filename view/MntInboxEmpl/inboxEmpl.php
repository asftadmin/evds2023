<?php
require_once "../../config/conexion.php";
if (isset($_SESSION["user_id"])) {
?>
<!DOCTYPE html>
<html lang="es">
<?php require_once "../MainHead/head.php"; ?>
<link rel="stylesheet" href="../../public/css/inboxEmpleados.css">

<title>Mis Permisos</title>
</head>

<body class="hold-transition sidebar-mini layout-fixed layout-fixed-footer">
    <div class="wrapper">

        <?php require_once "../MainNav/nav.php"; ?>
        <?php require_once "../MainMenu/menu.php"; ?>

        <div class="content-wrapper">

            <!-- Breadcrumb -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">
                                <i class="fas fa-clipboard-list mr-2"></i>Mis Permisos
                            </h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="../home/home2.php">Inicio</a></li>
                                <li class="breadcrumb-item active">Mis Permisos</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenido -->
            <div class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">

                            <div class="card card-outline card-info mb-2" style="height: calc(100vh - 155px);">
                                <div class="card-body p-0" style="height:100%; overflow:hidden;">
                                    <div class="inbox-wrapper" style="height:100%;">

                                        <!-- ══ PANEL LISTA ══ -->
                                        <div class="inbox-lista">

                                            <!-- Filtros -->
                                            <div class="p-2 border-bottom">
                                                <!-- Reemplazar el input de búsqueda por esto -->
                                                <div class="form-group mb-2">
                                                    <input type="text" class="form-control form-control-sm"
                                                        id="filtro_fechas_empl" placeholder="Rango de fechas…">
                                                </div>
                                                <div class="form-group mb-2">
                                                    <select class="form-control form-control-sm"
                                                        id="filtro_estado_empl">
                                                        <option value="">Todos los estados</option>
                                                        <option value="1">Pendiente</option>
                                                        <option value="2,3,4,5">Aprobados</option>
                                                        <option value="6">Rechazados</option>
                                                        <option value="7">Cancelados</option>
                                                    </select>
                                                </div>
                                                <div class="d-flex" style="gap:6px;">
                                                    <button class="btn btn-sm btn-primary flex-fill"
                                                        id="btn_filtrar_empl">
                                                        <i class="fas fa-filter mr-1"></i>Filtrar
                                                    </button>
                                                    <button class="btn btn-sm btn-default flex-fill"
                                                        id="btn_limpiar_empl">
                                                        <i class="fas fa-undo mr-1"></i>Limpiar
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Contador -->
                                            <div class="px-3 py-2 border-bottom">
                                                <small class="text-muted" id="lista-contador-empl">
                                                    0 solicitudes
                                                </small>
                                            </div>

                                            <!-- Lista dinámica -->
                                            <div class="lista-body" id="lista-permisos-empl">
                                                <div class="text-center text-muted py-4">
                                                    <i class="fas fa-inbox fa-2x mb-2 d-block" style="opacity:0.3;"></i>
                                                    Sin solicitudes
                                                </div>
                                            </div>

                                        </div>

                                        <!-- ══ PANEL DETALLE ══ -->
                                        <div class="inbox-detalle" id="panel-detalle-empl">
                                            <div class="text-center text-muted" style="padding:60px 0;">
                                                <i class="fas fa-hand-pointer fa-2x mb-2 d-block"
                                                    style="opacity:0.3;"></i>
                                                Selecciona una solicitud para ver el detalle
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /.content-wrapper -->

        <?php require_once "../MainFooter/footer.php"; ?>

    </div><!-- /.wrapper -->

    <?php require_once "../MainJS/JS.php"; ?>
    <script src="../../config/config.js"></script>
    <script src="inboxEmpl.js"></script>

</body>

</html>
<?php
} else {
    header("location:" . Conectar::ruta() . "index.php");
}
?>