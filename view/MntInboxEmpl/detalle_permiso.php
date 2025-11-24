<?php
require_once "../../config/conexion.php";
if (isset($_SESSION["user_id"])) {

?>

<!DOCTYPE html>
<html lang="es">

<?php require_once "../MainHead/head.php"; ?>
<link rel="stylesheet" href="../../public/css/inicio.css">
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
                            <h1 class="m-0">Trazabilidad Permiso</h1>
                        </div><!-- /.col -->
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="#">Inicio</a></li>
                                <li class="breadcrumb-item"><a href="inboxEmpl.php">Inbox</a></li>
                                <li class="breadcrumb-item active">Detalle Permiso</li>
                            </ol>
                        </div><!-- /.col -->
                    </div><!-- /.row -->
                </div><!-- /.container-fluid -->
            </div><!-- /.content-header -->

            <!-- Main content -->
            <div class="content">
                <div class="container-fluid">
                    <div class="row">

                        <div class="col-md-12">

                            <div class="card">

                                <div class="card-header">
                                    <div class="col-md-6">
                                        <button type="button" class="btn btn-dark" id="btnVolverR"><i
                                                class="fas fa-arrow-circle-left"></i>
                                            Regresar</button>
                                    </div>
                                </div>

                                <div class="card-body">
                                    <!-- Timeline container (AQUI SE INSERTA TODO CON AJAX) -->
                                    <div class="timeline" id="timelineContainer">

                                        <!-- timeline dinámico se insertará aquí -->

                                        <!-- Fin -->


                                    </div>
                                    <ul id="listaSoportes" class="list-group mt-3"></ul>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">

                                <div class="card-body">
                                    <div class="form-group">
                                        <div class="dropzone">
                                            <div class="dz-default dz-message">
                                                <button class="dz-button" type="button">
                                                    <i class="fas fa-cloud-upload-alt icon-super-upload"></i>
                                                    <div style="font-size: 18px; font-weight: bold; margin-top: 10px;">
                                                        Arrastra tus archivos o haz clic aquí
                                                    </div>
                                                </button>

                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
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
    <script type="text/javascript" src="detalle_permiso.js"></script>
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