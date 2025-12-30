<?php
require_once "../../config/conexion.php";
if (isset($_SESSION["user_id"])) {

?>

    <!DOCTYPE html>
    <html lang="es">

    <?php require_once "../MainHead/head.php"; ?>
    <link rel="stylesheet" href="../../public/css/inicio.css">
    <link rel="stylesheet" href="../../public/css/style.css">
    <!-- SweetAlert -->
    <link rel="stylesheet" href="../../public/plugins/sweetalert2/sweetalert2.css">


    <title>Gestion Personal</title>
    </head>


    <body class="hold-transition sidebar-mini">

        <div class="wrapper">

            <!-- Navbar -->
            <?php require_once "../MainNav/nav.php"; ?>
            <!-- /.navbar -->

            <!-- Main Sidebar Container -->
            <?php require_once "../MainMenu/menu.php"; ?>

            <!-- Content Wrapper. Contains page content -->
            <div class="content-wrapper">
                <!-- Content Header (Page header) -->
                <div class="content-header">
                    <div class="container-fluid">
                        <div class="row mb-2">
                            <div class="col-sm-6">
                                <h1 class="m-0">Bandeja de Entrada - Gestion Solicitudes</h1>
                            </div><!-- /.col -->
                            <div class="col-sm-6">
                                <ol class="breadcrumb float-sm-right">
                                    <li class="breadcrumb-item"><a href="#">Inicio</a></li>
                                    <li class="breadcrumb-item active">Ausentismo</li>
                                </ol>
                            </div><!-- /.col -->
                        </div><!-- /.row -->
                    </div><!-- /.container-fluid -->
                </div><!-- /.content-header -->

                <!-- Main content -->
                <div class="content">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-2">

                                <?php require_once "carpetas.php"; ?>

                            </div>
                            <div class="col-md-10">
                                <div class="card card-primary card-outline">
                                    <div class="card-header">
                                        <h3 class="card-title">Gestion Solicitudes - Ausentismo</h3><br>
                                        <hr>
                                        <div class="row w-100 mt-2">
                                            <div class="col-md-4">
                                                <label class="mb-0">Empleado:</label>
                                                <select id="filtroEmpleadoA" name="filtroEmpleadoA"
                                                    class="form-control select2">
                                                    <option value="">Todos</option>
                                                </select>
                                            </div>

                                            <div class="col-md-4">
                                                <label class="mb-0">Fecha:</label>
                                                <input type="text" id="filtroFecha" name="filtroFecha" class="form-control">
                                            </div>

                                            <div class="col-md-4 d-flex align-items-end">
                                                <button type="button" id="btnFiltrarAusentismo" class="btn btn-info mr-2">
                                                    <i class="fas fa-search"></i> Filtrar
                                                </button>

                                                <button type="button" id="btnLimpiarAusentismo" class="btn btn-default">
                                                    Limpiar
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card-body p-2">

                                        <div class="table-responsive mailbox-messages">
                                            <table class="table table-hover table-striped table-bordered display nowrap"
                                                id="tablaAusentismo" style="width:100%">
                                                <thead>
                                                    <tr>
                                                        <th>ITEM</th>
                                                        <th>FECHA DESDE</th>
                                                        <th>FECHA HASTA</th>
                                                        <th>CEDULA</th>
                                                        <th>NOMBRE DEL EMPLEADO</th>
                                                        <th>Motivo</th>
                                                        <th>IBC</th>
                                                        <th>IBC HORA</th>
                                                        <th>HORAS PERMISO</th>
                                                        <th>COSTO</th>
                                                        <th>COD. CIE</th>
                                                        <th>DIAGNOSTICO</th>
                                                    </tr>
                                                </thead>

                                                <tbody>
                                                    <!-- Las filas se llenarán dinámicamente con JS -->
                                                </tbody>
                                            </table>

                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- /.container-fluid -->
                        </div>
                    </div>
                    <!-- /.content -->
                </div>
                <!-- /.content-wrapper -->
            </div>


            <?php require_once("../MainFooter/footer.php") ?>

            <!-- ./wrapper -->
        </div>

        <?php require_once "../MainJS/JS.php" ?>
        <script src="../../config/config.js"></script>
        <script type="text/javascript" src="ausentismo.js"></script>
        <!-- date-range-picker -->
        <!-- SweetAlert -->
        <script src="../../public/plugins/sweetalert2/sweetalert2.js"></script>



    </body>

    </html>

<?php
} else {
    header("location:" . Conectar::ruta() . "index.php");
}
?>