<?php
require_once "../../config/conexion.php";
if (isset($_SESSION["user_id"])) {

?>


    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <?php require_once("../MainHead/head.php") ?>
        <link rel="stylesheet" href="../../public/css/preop.css">
        <title>EVALUACIÓN DESEMPEÑO</title>


    </head>

    <body class="hold-transition sidebar-mini layout-fixed layout-footer-fixed">
        <!-- Site wrapper -->
        <div class="wrapper">
            <!-- Navbar -->
            <?php require_once("../MainNav/nav.php") ?>
            <!-- /.navbar -->

            <?php require_once("../MainMenu/menu.php") ?>

            <!-- Content Wrapper. Contains page content -->
            <div class="content-wrapper">
                <!-- Content Header (Page header) -->
                <section class="content-header">
                    <div class="container-fluid">
                        <div class="row mb-2">
                            <div class="col-sm-6">
                                <h1>Historia Laboral</h1>
                            </div>
                            <div class="col-sm-6">
                                <ol class="breadcrumb float-sm-right">
                                    <li class="breadcrumb-item"><a href="#">Inicio</a></li>
                                    <li class="breadcrumb-item active">Historia Laboral</li>
                                </ol>
                            </div>
                        </div>
                    </div><!-- /.container-fluid -->
                </section>

                <!-- Main content -->
                <section class="content">

                    <!-- Default box -->
                    <!--                     <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-10 offset-md-1">

                                    <div class="card card-info">
                                        <div class="card-header">
                                            <h3 class="card-title">
                                                <i class="fas fa-file-alt mr-2"></i>
                                                Generar Certificado Laboral
                                            </h3>
                                        </div>

                                        <div class="card-body">

                                            <form id="formReporte" method="POST">

                                                <div class="row">


                                                    <div class="col-md-5">
                                                        <div class="form-group">
                                                            <label class="mr-2">Empleado</label>
                                                            <select class="form-control select2bs4" name="nomb_empl"
                                                                id="nomb_empl" style="width:100%;" required>
                                                            </select>
                                                            <input type="hidden" id="codi_empl" name="codi_empl">
                                                        </div>
                                                    </div>


                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label>Código interno de radicado</label>
                                                            <input type="text" class="form-control" name="radicado"
                                                                id="radicado" placeholder="Ej: ASF-GH-2.6-0000-26" required>
                                                        </div>
                                                    </div>


                                                    <div class="col-md-3 d-flex align-items-start">
                                                        <div class="form-group w-100">
                                                            <button id="btnDescargRpte" type="button"
                                                                class="btn btn-info btn-block">
                                                                <i class="fas fa-file-download mr-1"></i>
                                                                Descargar
                                                            </button>
                                                        </div>
                                                    </div>

                                                </div>

                                            </form>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> -->
                    <!-- /.card-body -->

                    <!-- /.card-footer-->
                    <!-- </div> -->
                    <!-- /.card -->


                    <!-- ── Buscador de empleado ── -->
                    <div class="card card-outline card-info">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-search mr-2"></i>Buscar Empleado</h3>
                        </div>
                        <div class="card-body">
                            <div class="row align-items-end">
                                <div class="col-md-5">
                                    <label class="text-muted small font-weight-bold text-uppercase">Cédula / Nombre </label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" value="1090123456 – GARCÍA RUIZ, JUAN CAMILO" placeholder="Buscar empleado…">
                                        <div class="input-group-append">
                                            <button class="btn btn-dark"><i class="fas fa-search mr-1"></i>Buscar</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-default btn-block"><i class="fas fa-undo mr-1"></i>Limpiar</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── TABS ── -->
                    <div class="card card-dark card-tabs">
                        <div class="card-header p-0 pt-1">
                            <ul class="nav nav-tabs" id="mainTabs">
                                <li class="nav-item">
                                    <a class="nav-link active" href="#" onclick="showPane('erp');tabActive(this);return false;">
                                        <i class="fas fa-plug text-dark mr-1"></i>Datos Basicos
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#" onclick="showPane('contrato');tabActive(this);return false;">
                                        <i class="fas fa-file-contract text-light mr-1"></i>Contrato
                                    </a>
                                </li>
                                <!--                                 <li class="nav-item">
                                    <a class="nav-link" href="#" onclick="showPane('documento');tabActive(this);return false;">
                                        <i class="fas fa-id-card text-success mr-1"></i>Documento
                                        <span class="badge badge-warning badge-pill ml-1">!</span>
                                    </a>
                                </li> -->
                                <li class="nav-item">
                                    <a class="nav-link" href="#" onclick="showPane('bonificaciones');tabActive(this);return false;">
                                        <i class="fas fa-hand-holding-usd text-light mr-1"></i>Bonificaciones
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#" onclick="showPane('auxilios');tabActive(this);return false;">
                                        <i class="fas fa-dollar-sign ext-light mr-1"></i>Auxilios
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#" onclick="showPane('preview');tabActive(this);return false;">
                                        <i class="fas fa-eye text-light mr-1"></i>Vista Previa
                                    </a>
                                </li>
                                <!--                                 <li class="nav-item">
                                    <a class="nav-link" href="#" onclick="showPane('historial');tabActive(this);return false;">
                                        <i class="fas fa-history mr-1"></i>Historial
                                    </a>
                                </li> -->
                            </ul>
                        </div>

                        <div class="card-body">

                            <!-- ════════════════ PANE DATOS BASICOS════════════════ -->
                            <div class="pane active" id="pane-erp">

                                <!-- Datos básicos -->
                                <div class="card card-erp mb-3 shadow-sm card-outline card-info">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            <span class="badge-erp mr-2"><i class="fas fa-file mr-1"></i></span>
                                            Datos Básicos del Empleado
                                        </h3>
                                        <div class="card-tools">
                                            <span class="text-muted small"><i class="fas fa-lock mr-1"></i>Solo lectura · Fuente: API ERP</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3 d-flex flex-column mb-3">
                                                <label class="section-subheader"><i class="fas fa-id-badge mr-1"></i>Cédula</label>
                                                <input class="form-control input-erp" readonly value="1.090.123.456">
                                            </div>
                                            <div class="col-md-4 d-flex flex-column mb-3">
                                                <label class="section-subheader"><i class="fas fa-user mr-1"></i>Nombre Completo</label>
                                                <input class="form-control input-erp" readonly value="Juan Camilo García Ruiz">
                                            </div>
                                            <div class="col-md-2 d-flex flex-column mb-3">
                                                <label class="section-subheader"><i class="fas fa-calendar-alt mr-1"></i>Fecha Ingreso</label>
                                                <input class="form-control input-erp" readonly value="15/03/2019">
                                            </div>
                                            <div class="col-md-2 d-flex flex-column mb-3">
                                                <label class="section-subheader"><i class="fas fa-dollar-sign mr-1"></i>Salario Base</label>
                                                <input class="form-control input-erp" readonly value="$ 4.200.000">
                                            </div>
                                            <div class="col-md-1 d-flex flex-column mb-3">
                                                <label class="section-subheader"><i class="fas fa-circle mr-1"></i>Estado</label>
                                                <div class="mt-2"><span class="badge badge-success badge-pill"><i class="fas fa-check mr-1"></i>Activo</span></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Contacto -->
                                <div class="card card-erp mb-3 shadow-sm card-outline card-info">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            <i class="fas fa-sitemap mr-1"></i>
                                            Datos de contacto
                                        </h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-2 d-flex flex-column mb-3">
                                                <label class="section-subheader"><i class="fas fa-phone mr-1"></i>Telefono</label>
                                                <input class="form-control input-erp" readonly value="6059170">
                                            </div>
                                            <div class="col-md-2 d-flex flex-column mb-3">
                                                <label class="section-subheader"><i class="fas fa-mobile-alt mr-1"></i>Celular</label>
                                                <input class="form-control input-erp" readonly value="3134560090">
                                            </div>
                                            <div class="col-md-5 d-flex flex-column mb-3">
                                                <label class="section-subheader"><i class="fas fa-map-marker-alt mr-1"></i>Dirección</label>
                                                <input class="form-control input-erp" readonly value="Km 5 Anillo vial">
                                            </div>
                                            <div class="col-md-3 d-flex flex-column mb-3">
                                                <label class="section-subheader"><i class="fas fa-ats mr-1"></i>Email</label>
                                                <input class="form-control input-erp" readonly value="juan.martinez@gmail.com">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Cargo -->
                                <div class="card card-erp mb-3 shadow-sm card-outline card-info">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            <i class="fas fa-sitemap mr-1"></i>
                                            Cargo y Dependencia
                                        </h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3 d-flex flex-column mb-3">
                                                <label class="section-subheader"><i class="fas fa-briefcase mr-1"></i>Cargo</label>
                                                <input class="form-control input-erp" readonly value="Analista de Sistemas">
                                            </div>
                                            <div class="col-md-2 d-flex flex-column mb-3">
                                                <label class="section-subheader"><i class="fas fa-layer-group mr-1"></i>Nivel</label>
                                                <input class="form-control input-erp" readonly value="Profesional III">
                                            </div>
                                            <div class="col-md-3 d-flex flex-column mb-3">
                                                <label class="section-subheader"><i class="fas fa-sitemap mr-1"></i>Dependencia</label>
                                                <input class="form-control input-erp" readonly value="Dirección TIC">
                                            </div>
                                            <div class="col-md-3 d-flex flex-column mb-3">
                                                <label class="section-subheader"><i class="fas fa-user-tie mr-1"></i>Jefe Inmediato</label>
                                                <input class="form-control input-erp" readonly value="Luis Morales Torres">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Estudio -->
                                <div class="card card-erp shadow-sm card-outline card-info">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            <i class="fas fa-university"></i>
                                            Formación Académica
                                        </h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3 d-flex flex-column mb-3">
                                                <label class="section-subheader"><i class="fas fa-graduation-cap mr-1"></i>Nivel Educativo</label>
                                                <input class="form-control input-erp" readonly value="Profesional Universitario">
                                            </div>
                                            <div class="col-md-3 d-flex flex-column mb-3">
                                                <label class="section-subheader"><i class="fas fa-scroll mr-1"></i>Título</label>
                                                <input class="form-control input-erp" readonly value="Ingeniería de Sistemas">
                                            </div>
                                            <div class="col-md-3 d-flex flex-column mb-3">
                                                <label class="section-subheader"><i class="fas fa-university mr-1"></i>Institución</label>
                                                <input class="form-control input-erp" readonly value="UFPS">
                                            </div>
                                            <div class="col-md-2 d-flex flex-column mb-3">
                                                <label class="section-subheader"><i class="fas fa-calendar-check mr-1"></i>Año de Grado</label>
                                                <input class="form-control input-erp" readonly value="2017">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div><!-- /pane-erp -->

                </section>
                <!-- /.content -->
            </div>
            <!-- /.content-wrapper -->

            <?php require_once("../MainFooter/footer.php") ?>

            <!-- Control Sidebar -->
            <aside class="control-sidebar control-sidebar-dark">
                <!-- Control sidebar content goes here -->
            </aside>
            <!-- /.control-sidebar -->
        </div>
        <!-- ./wrapper -->

        <?php require_once("../MainJS/JS.php") ?>
        <script type="text/javascript" src="certificados.js"></script>
    </body>

    </html>
<?php
} else {
    header("location:" . Conectar::ruta() . "index.php");
}
?>