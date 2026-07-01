<?php
require_once "../../config/conexion.php";
if (isset($_SESSION["user_id"])) {

?>

    <!DOCTYPE html>
    <html lang="es">

    <?php require_once "../MainHead/head.php"; ?>
    <link rel="stylesheet" href="../../public/css/inicio.css">
    <link rel="stylesheet" href="../../public/css/style.css">
    <style>
        #card_colaborador {
            font-size: 1rem;
            min-height: 2.8rem;
            word-break: break-word;
        }

        #card_total_permisos,
        #card_total_horas {
            min-height: 2.8rem;
        }
    </style>

    <title>Consulta permisos por mes</title>
    </head>

    <body class="hold-transition sidebar-mini">

        <div class="wrapper">

            <?php require_once "../MainNav/nav.php"; ?>
            <?php require_once "../MainMenu/menu.php"; ?>

            <div class="content-wrapper">
                <div class="content-header">
                    <div class="container-fluid">
                        <div class="row mb-2">
                            <div class="col-sm-6">
                                <h1 class="m-0">Consulta permisos por mes</h1>
                            </div>
                            <div class="col-sm-6">
                                <ol class="breadcrumb float-sm-right">
                                    <li class="breadcrumb-item"><a href="#">Inicio</a></li>
                                    <li class="breadcrumb-item active">Permisos por mes</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="content">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-2">
                                <?php require_once "carpetas.php"; ?>
                            </div>

                            <div class="col-md-10">
                                <div class="card card-primary card-outline">
                                    <div class="card-header">
                                        <h3 class="card-title">Filtros de consulta</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-5">
                                                <div class="form-group">
                                                    <label for="filtro_colaborador">Colaborador</label>
                                                    <select id="filtro_colaborador" class="form-control select2bs4"
                                                        style="width: 100%;">
                                                        <option value="">Seleccione un colaborador</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="filtro_periodo">Mes y año</label>
                                                    <input type="month" id="filtro_periodo" class="form-control"
                                                        autocomplete="off">
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>&nbsp;</label>
                                                    <div>
                                                        <button type="button" id="btn_consultar_permisos_mes"
                                                            class="btn btn-primary mr-2">
                                                            <i class="fas fa-search"></i>
                                                            Consultar
                                                        </button>

                                                        <button type="button" id="btn_limpiar_permisos_mes"
                                                            class="btn btn-secondary">
                                                            <i class="fas fa-eraser"></i>
                                                            Limpiar
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="small-box bg-info">
                                            <div class="inner">
                                                <h4 id="card_colaborador">Sin seleccionar</h4>
                                                <p>Colaborador</p>
                                            </div>
                                            <div class="icon">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="small-box bg-success">
                                            <div class="inner">
                                                <h4 id="card_total_permisos">0</h4>
                                                <p>Total de permisos solicitados en el mes</p>
                                            </div>
                                            <div class="icon">
                                                <i class="fas fa-clipboard-list"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="small-box bg-warning">
                                            <div class="inner">
                                                <h4 id="card_total_horas">0:00</h4>
                                                <p>Total de horas solicitadas en el mes</p>
                                            </div>
                                            <div class="icon">
                                                <i class="fas fa-clock"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div id="mensaje_sin_datos" class="alert alert-info d-none">
                                    No se encontro informacion para los filtros seleccionados.
                                </div>

                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Detalle de permisos</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table id="tabla_permisos_mes"
                                                class="table table-bordered table-striped table-hover" style="width: 100%;">
                                                <thead>
                                                    <tr>
                                                        <th>Fecha del permiso</th>
                                                        <th>Hora de salida del permiso</th>
                                                        <th>Hora de ingreso del permiso</th>
                                                        <th>Hora de salida BioTime</th>
                                                        <th>Hora de ingreso BioTime</th>
                                                        <th>Total de horas del permiso</th>
                                                        <th>Motivo</th>
                                                        <th>Estado</th>
                                                    </tr>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php require_once "../MainFooter/footer.php"; ?>
        </div>

        <?php require_once "../MainJS/JS.php"; ?>
        <script src="../../config/config.js"></script>
        <script type="text/javascript" src="consulta_permisos_mes.js"></script>

    </body>

    </html>

<?php
} else {
    header("location:" . Conectar::ruta() . "index.php");
}
?>