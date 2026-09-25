<?php

require_once '../../config/conexion.php';

if (isset($_SESSION['user_id'])) {
?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <?php require_once ('../MainHead/head.php') ?>

    <title>MESA DE SERVICIO</title>

</head>


<body class="hold-transition sidebar-mini">

    <div class="wrapper">

        <!-- Navbar -->

        <?php require_once ('../MainNav/nav.php') ?>

        <!-- /.navbar -->


        <!-- Main Sidebar -->

        <?php require_once ('../MainMenu/menu.php') ?>


        <!-- Content Wrapper -->

        <div class="content-wrapper">


            <!-- ================================================= -->
            <!-- CABECERA                                          -->
            <!-- ================================================= -->

            <section class="content-header">

                <div class="container-fluid">

                    <div class="row mb-2">

                        <div class="col-sm-6">

                            <h1>

                                <i class="fas fa-headset"></i>

                                Mesa de Servicio

                            </h1>

                        </div>


                        <div class="col-sm-6">

                            <ol class="breadcrumb float-sm-right">

                                <li class="breadcrumb-item">

                                    <a href="home">
                                        Inicio
                                    </a>

                                </li>

                                <li class="breadcrumb-item active">

                                    Mesa de Servicio

                                </li>

                            </ol>

                        </div>

                    </div>

                </div>

            </section>


            <!-- ================================================= -->
            <!-- CONTENIDO                                         -->
            <!-- ================================================= -->

            <section class="content">

                <div class="container-fluid">


                    <!-- ========================================= -->
                    <!-- PRESENTACIÓN                              -->
                    <!-- ========================================= -->

                    <div class="card card-primary card-outline">

                        <div class="card-body">

                            <div class="row align-items-center">

                                <div class="col-md-8">

                                    <h4 class="mb-1">

                                        <i class="fas fa-laptop-medical text-primary mr-1"></i>

                                        ¿Necesitas ayuda de Sistemas?

                                    </h4>

                                    <p class="text-muted mb-0">

                                        Reporta una solicitud o incidente y consulta
                                        fácilmente el estado de tus tickets.

                                    </p>

                                </div>


                                <div class="col-md-4 text-md-right mt-3 mt-md-0">

                                    <button type="button" id="btn-nuevo-ticket" class="btn btn-primary"
                                        data-toggle="modal" data-target="#modal-nuevo-ticket">

                                        <i class="fas fa-plus"></i>

                                        Nuevo ticket

                                    </button>

                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- ========================================= -->
                    <!-- RESUMEN                                   -->
                    <!-- ========================================= -->

                    <div class="row">


                        <!-- ABIERTOS -->

                        <div class="col-lg-4 col-md-4 col-sm-12">

                            <div class="info-box">

                                <span class="info-box-icon bg-light">

                                    <i class="far fa-clock text-primary"></i>

                                </span>


                                <div class="info-box-content">

                                    <span class="info-box-text">

                                        Tickets abiertos

                                    </span>

                                    <span class="info-box-number" id="total-tickets-abiertos">

                                        0

                                    </span>

                                </div>

                            </div>

                        </div>


                        <!-- EN PROCESO -->

                        <div class="col-lg-4 col-md-4 col-sm-12">

                            <div class="info-box">

                                <span class="info-box-icon bg-light">

                                    <i class="fas fa-tools text-warning"></i>

                                </span>


                                <div class="info-box-content">

                                    <span class="info-box-text">

                                        En proceso

                                    </span>

                                    <span class="info-box-number" id="total-tickets-proceso">

                                        0

                                    </span>

                                </div>

                            </div>

                        </div>


                        <!-- FINALIZADOS -->

                        <div class="col-lg-4 col-md-4 col-sm-12">

                            <div class="info-box">

                                <span class="info-box-icon bg-light">

                                    <i class="fas fa-check text-success"></i>

                                </span>


                                <div class="info-box-content">

                                    <span class="info-box-text">

                                        Finalizados

                                    </span>

                                    <span class="info-box-number" id="total-tickets-finalizados">

                                        0

                                    </span>

                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- ========================================= -->
                    <!-- MIS TICKETS                               -->
                    <!-- ========================================= -->

                    <div class="card">

                        <div class="card-header">

                            <h3 class="card-title">

                                <i class="fas fa-ticket-alt mr-1"></i>

                                Mis tickets

                            </h3>

                        </div>


                        <div class="card-body">


                            <!-- ================================= -->
                            <!-- FILTROS                           -->
                            <!-- ================================= -->

                            <div class="row">


                                <!-- ESTADO -->

                                <div class="col-md-4">

                                    <div class="form-group">

                                        <label>

                                            Estado

                                        </label>


                                        <select id="filtro-estado" class="form-control select2bs4" style="width:100%;">

                                            <option value="">

                                                Todos

                                            </option>

                                            <option value="ABIERTO">

                                                Abierto

                                            </option>

                                            <option value="EN_PROCESO">

                                                En proceso

                                            </option>

                                            <option value="EN_ESPERA">

                                                En espera

                                            </option>

                                            <option value="RESUELTO">

                                                Resuelto

                                            </option>

                                            <option value="CERRADO">

                                                Cerrado

                                            </option>

                                            <option value="CANCELADO">

                                                Cancelado

                                            </option>

                                        </select>

                                    </div>

                                </div>


                                <!-- BUSCAR -->

                                <div class="col-md-5">

                                    <div class="form-group">

                                        <label>

                                            Buscar

                                        </label>

                                        <input type="text" id="filtro-buscar" class="form-control" maxlength="100"
                                            autocomplete="off" placeholder="Número de ticket o asunto">

                                    </div>

                                </div>


                                <!-- LIMPIAR -->

                                <div class="col-md-3">

                                    <div class="form-group">

                                        <label>&nbsp;</label>

                                        <div>

                                            <button type="button" id="btn-limpiar-filtros" class="btn btn-secondary">

                                                <i class="fas fa-eraser"></i>

                                                Limpiar

                                            </button>

                                        </div>

                                    </div>

                                </div>

                            </div>


                            <!-- ================================= -->
                            <!-- TABLA                             -->
                            <!-- ================================= -->

                            <div class="table-responsive">

                                <table id="tabla-tickets-sistemas"
                                    class="table table-bordered table-striped table-hover" width="100%">

                                    <thead>

                                        <tr>

                                            <th>Ticket</th>

                                            <th>Solicitud</th>

                                            <th>Categoría</th>

                                            <th>Prioridad</th>

                                            <th>Estado</th>

                                            <th>Fecha</th>

                                            <th>Acción</th>

                                        </tr>

                                    </thead>


                                    <tbody>

                                    </tbody>

                                </table>

                            </div>

                        </div>

                    </div>

                </div>

            </section>

        </div>


        <!-- ===================================================== -->
        <!-- MODAL NUEVO TICKET                                    -->
        <!-- ===================================================== -->

        <div class="modal fade" id="modal-nuevo-ticket" tabindex="-1" role="dialog" aria-hidden="true">

            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">

                <div class="modal-content">

                    <form id="form-ticket-sistemas">


                        <!-- HEADER -->

                        <div class="modal-header">

                            <div>

                                <h5 class="modal-title">

                                    <i class="fas fa-ticket-alt text-primary mr-1"></i>

                                    Nuevo ticket de Sistemas

                                </h5>

                                <small class="text-muted">

                                    Describe claramente lo que necesitas.

                                </small>

                            </div>


                            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">

                                <span aria-hidden="true">

                                    &times;

                                </span>

                            </button>

                        </div>


                        <!-- BODY -->

                        <div class="modal-body">


                            <!-- ================================ -->
                            <!-- EMPLEADO                         -->
                            <!-- ================================ -->

                            <div class="callout callout-info">

                                <div class="row">

                                    <div class="col-md-12">

                                        <h6 class="mb-1">

                                            <i class="fas fa-user mr-1"></i>

                                            <span id="empleado-nombre">

                                                Cargando información...

                                            </span>

                                        </h6>


                                        <small class="text-muted">

                                            Documento:

                                            <span id="empleado-documento">

                                                -

                                            </span>

                                            &nbsp; | &nbsp;

                                            Cargo:

                                            <span id="empleado-cargo">

                                                -

                                            </span>


                                        </small>


                                        <br>


                                        <small class="text-muted">

                                            Correo:

                                            <span id="empleado-correo">

                                                -

                                            </span>

                                        </small>

                                    </div>

                                </div>

                            </div>


                            <!-- ================================ -->
                            <!-- INFORMACIÓN DEL CASO             -->
                            <!-- ================================ -->

                            <h6 class="mb-3">

                                <i class="fas fa-clipboard-list text-primary mr-1"></i>

                                Información del caso

                            </h6>


                            <div class="row">


                                <!-- TIPO -->

                                <div class="col-md-4">

                                    <div class="form-group">

                                        <label>

                                            Tipo

                                            <span class="text-danger">*</span>

                                        </label>

                                        <select id="ticket-tipo" name="tipo" class="form-control select2bs4"
                                            style="width:100%;" required>

                                            <option value="">

                                                Seleccione

                                            </option>

                                            <option value="SOLICITUD">

                                                Solicitud

                                            </option>

                                            <option value="INCIDENTE">

                                                Incidente

                                            </option>

                                            <option value="REQUERIMIENTO">

                                                Requerimiento

                                            </option>

                                        </select>

                                    </div>

                                </div>


                                <!-- CATEGORÍA -->

                                <div class="col-md-5">

                                    <div class="form-group">

                                        <label>

                                            Categoría

                                            <span class="text-danger">*</span>

                                        </label>

                                        <select id="ticket-categoria" name="categoria_id"
                                            class="form-control select2bs4" style="width:100%;" required>

                                            <option value="">

                                                Seleccione una categoría

                                            </option>

                                        </select>

                                    </div>

                                </div>


                                <!-- PRIORIDAD -->

                                <div class="col-md-3">

                                    <div class="form-group">

                                        <label>

                                            Prioridad

                                            <span class="text-danger">*</span>

                                        </label>

                                        <select id="ticket-prioridad" name="prioridad" class="form-control select2bs4"
                                            style="width:100%;" required>

                                            <option value="BAJA">

                                                Baja

                                            </option>

                                            <option value="MEDIA" selected>

                                                Media

                                            </option>

                                            <option value="ALTA">

                                                Alta

                                            </option>

                                            <option value="CRITICA">

                                                Crítica

                                            </option>

                                        </select>

                                    </div>

                                </div>

                            </div>


                            <!-- ASUNTO -->

                            <div class="form-group">

                                <label>

                                    Asunto

                                    <span class="text-danger">*</span>

                                </label>

                                <input type="text" id="ticket-asunto" name="asunto" class="form-control" maxlength="150"
                                    autocomplete="off" placeholder="Ej. El computador no enciende" required>

                            </div>


                            <!-- DESCRIPCIÓN -->

                            <div class="form-group">

                                <label>

                                    Descripción

                                    <span class="text-danger">*</span>

                                </label>

                                <textarea id="ticket-descripcion" name="descripcion" class="form-control" rows="4"
                                    maxlength="4000"
                                    placeholder="Describe qué sucede, desde cuándo y qué estabas haciendo cuando ocurrió."
                                    required></textarea>

                                <small class="form-text text-muted">

                                    Entre más clara sea la descripción, más fácil será atender tu solicitud.

                                </small>

                            </div>


                            <!-- ================================ -->
                            <!-- INFORMACIÓN ADICIONAL            -->
                            <!-- ================================ -->

                            <h6 class="mt-4 mb-3">

                                <i class="fas fa-info-circle text-primary mr-1"></i>

                                Información adicional

                                <small class="text-muted">

                                    (opcional)

                                </small>

                            </h6>


                            <div class="row">


                                <!-- UBICACIÓN -->

                                <div class="col-md-6">

                                    <div class="form-group">

                                        <label>

                                            Ubicación

                                        </label>

                                        <input type="text" id="ticket-ubicacion" name="ubicacion" class="form-control"
                                            maxlength="150" autocomplete="off" placeholder="Ej. Oficina administrativa">

                                    </div>

                                </div>


                                <!-- EQUIPO -->

                                <div class="col-md-6">

                                    <div class="form-group">

                                        <label>

                                            Equipo relacionado

                                        </label>

                                        <input type="text" id="ticket-equipo" name="equipo" class="form-control"
                                            maxlength="150" autocomplete="off" placeholder="Ej. PC Contabilidad 01">

                                    </div>

                                </div>

                            </div>

                        </div>


                        <!-- FOOTER -->

                        <div class="modal-footer">

                            <button type="button" class="btn btn-secondary" data-dismiss="modal">

                                Cancelar

                            </button>


                            <button type="submit" id="btn-guardar-ticket" class="btn btn-primary">

                                <i class="fas fa-paper-plane"></i>

                                Enviar ticket

                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>


        <!-- Footer -->

        <?php require_once ('../MainFooter/footer.php') ?>


        <!-- Control Sidebar -->

        <aside class="control-sidebar control-sidebar-dark">

        </aside>

    </div>


    <!-- Main JS -->

    <?php require_once ('../MainJS/JS.php') ?>


    <!-- JS Módulo -->

    <script type="text/javascript" src="tickets_sistemas.js"></script>


</body>

</html>


<?php
} else {
    header(
        'location:'
        . Conectar::ruta()
        . 'index.php'
    );
}

?>