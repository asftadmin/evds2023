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

                    <!-- ── Buscador de empleado ── -->
                    <div class="card card-outline card-info">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-search mr-2"></i>Buscar Empleado</h3>
                        </div>
                        <div class="card-body">
                            <div class="row align-items-end">

                                <!-- Buscador -->
                                <div class="col-md-5">
                                    <label class="section-subheader">Cédula / Nombre</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="txt_buscar_empl"
                                            placeholder="Buscar empleado…">
                                        <div class="input-group-append">
                                            <button class="btn btn-dark" id="buscarEmpl">
                                                <i class="fas fa-search mr-1"></i>Buscar
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Limpiar -->
                                <div class="col-md-2">
                                    <button class="btn btn-default btn-block" id="btn_limpiar_buscar">
                                        <i class="fas fa-undo mr-1"></i>Limpiar
                                    </button>
                                </div>

                                <!-- Editar / Cancelar / Guardar — misma línea -->
                                <div class="col-md-5 d-flex justify-content-end">

                                    <button type="button" id="btn_editar_basicos" class="btn btn-secondary">
                                        <i class="fas fa-edit mr-1"></i>Editar
                                    </button>

                                    <button type="button" id="btn_cancelar_basicos" class="btn btn-default ml-2"
                                        style="display:none;">
                                        <i class="fas fa-undo mr-1"></i>Cancelar
                                    </button>

                                    <button type="button" id="btn_guardar_basicos" class="btn btn-info ml-2"
                                        style="display:none;">
                                        <i class="fas fa-save mr-1"></i>Guardar Datos
                                    </button>

                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- ── TABS ── -->
                    <div class="card card-dark card-tabs">
                        <div class="card-header p-0 pt-1">
                            <ul class="nav nav-tabs tabs-laboral" id="mainTabs">
                                <li class="nav-item">
                                    <a class="nav-link active" href="#" data-pane="erp">
                                        <i class="fas fa-plug mr-1"></i>Datos Basicos
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#" data-pane="contrato">
                                        <i class="fas fa-file-contract mr-1"></i>Contrato
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#" data-pane="bonificaciones">
                                        <i class="fas fa-hand-holding-usd mr-1"></i>Bonificaciones
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#" data-pane="auxilios">
                                        <i class="fas fa-dollar-sign mr-1"></i>Auxilios
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#" data-pane="preview">
                                        <i class="fas fa-eye mr-1"></i>Vista Previa
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
                            <!-- formulario editar empleado -->
                            <form id="form_empleado" method="post" id="update_historia">

                                <!-- ════════════════ PANE DATOS BASICOS════════════════ -->
                                <div class="pane active" id="pane-erp">



                                    <!-- Datos básicos -->
                                    <div class="card card-erp mb-3 shadow-sm card-outline card-info">
                                        <div class="card-header">
                                            <h3 class="card-title">
                                                <span class="badge-erp mr-2"><i class="fas fa-id-card"></i></span>
                                                Datos de Identificación
                                            </h3>
                                            <div class="card-tools">
                                                <div class="">
                                                    <span id="span_estado_empleado" class="small badge badge-pill">
                                                        <i id="icon_estado_empleado" class="fas mr-1"></i>
                                                        <span id="estado_descripcion"></span>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">

                                                <input type="hidden" class="form-control input-erp"
                                                    name="txt_codigo_empleado" id="txt_codigo_empleado">
                                                <div class="col-md-3 d-flex flex-column mb-3">
                                                    <label class="section-subheader"><i
                                                            class="fas fa-id-card mr-1"></i></i>Tipo
                                                        Documento</label>
                                                    <select class="form-control select-erp select2bs4" style="width: 100%;"
                                                        name="txt_tipo_documento_empl" id="txt_tipo_documento_empl"
                                                        disabled>

                                                    </select>
                                                </div>
                                                <div class="col-md-3 d-flex flex-column mb-3">
                                                    <label class="section-subheader"><i
                                                            class="fas fa-id-badge mr-1"></i>Cédula</label>
                                                    <input type="text" name="txt_numero_documento"
                                                        class="form-control input-erp" id="txt_numero_documento" readonly>
                                                </div>
                                                <div class="col-md-4 d-flex flex-column mb-3">
                                                    <label class="section-subheader"><i
                                                            class="fas fa-map-marker-alt mr-1"></i>Lugar
                                                        de Expedición</label>
                                                    <select class="form-control select-erp select2bs4" style="width: 100%;"
                                                        name="txt_lugar_exp" id="txt_lugar_exp" disabled>

                                                    </select>
                                                </div>
                                                <div class="col-md-2 d-flex flex-column mb-3">
                                                    <label class="section-subheader"><i
                                                            class="fas fa-calendar-alt mr-1"></i>Fecha
                                                        Expedición</label>
                                                    <input type="text" class="form-control input-erp" name="txt_fecha_exp"
                                                        id="txt_fecha_exp" readonly>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Contacto -->
                                    <div class="card card-erp mb-3 shadow-sm card-outline card-info">
                                        <div class="card-header">
                                            <h3 class="card-title">
                                                <i class="fas fa-sitemap mr-1"></i>
                                                Datos de Personales
                                            </h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-3 d-flex flex-column mb-3">
                                                    <label class="section-subheader"><i class="fas fa-user mr-1"></i>Nombre
                                                        Completo</label>
                                                    <input type="text" name="txt_nombre_empleado" id="txt_nombre_empleado"
                                                        class="form-control input-erp" readonly>
                                                </div>
                                                <div class="col-md-2 d-flex flex-column mb-3">
                                                    <label class="section-subheader"><i
                                                            class="fas fa-phone mr-1"></i>Telefono</label>
                                                    <input type="text" class="form-control input-erp"
                                                        name="txt_telefono_empleado" id="txt_telefono_empleado" readonly>
                                                </div>
                                                <div class="col-md-3 d-flex flex-column mb-3">
                                                    <label class="section-subheader"><i
                                                            class="fas fa-map-marker-alt mr-1"></i>Dirección</label>
                                                    <input type="text" name="txt_direccion_empleado"
                                                        class="form-control input-erp" id="txt_direccion_empleado" readonly>
                                                </div>
                                                <div class="col-md-2 d-flex flex-column mb-3">
                                                    <label class="section-subheader"><i
                                                            class="fas fa-envelope mr-1"></i>Email</label>
                                                    <input type="text" class="form-control input-erp" name="txt_correo"
                                                        id="txt_correo" readonly>
                                                </div>
                                                <div class="col-md-2 d-flex flex-column mb-3">
                                                    <label class="section-subheader"><i
                                                            class="fas fa-calendar-alt mr-1"></i>Fecha
                                                        Nacimiento</label>
                                                    <input type="text" class="form-control input-erp"
                                                        name="txt_fecha_nacimiento" id="txt_fecha_nacimiento" readonly>
                                                </div>
                                            </div>
                                            <!-- Informacion Personal -->
                                            <div class="row">
                                                <div class="col-md-3 d-flex flex-column mb-3">
                                                    <label class="section-subheader"><i
                                                            class="fas fa-venus-mars mr-1"></i></i>Genero</label>
                                                    <select class="form-control select2bs4" style="width: 100%;"
                                                        name="txt_genero" id="txt_genero" disabled>

                                                    </select>
                                                </div>
                                                <div class="col-md-3 d-flex flex-column mb-3">
                                                    <label class="section-subheader"><i
                                                            class="fas fa-ring mr-1"></i></i>Estado
                                                        Civil</label>
                                                    <select class="form-control select2bs4" style="width: 100%;"
                                                        name="txt_civil" id="txt_civil" disabled>

                                                    </select>
                                                </div>
                                                <div class="col-md-3 d-flex flex-column mb-3">
                                                    <label class="section-subheader"><i
                                                            class="fas fa-tint mr-1"></i></i>Grupo
                                                        Sanguineo</label>
                                                    <select class="form-control select2bs4" style="width: 100%;"
                                                        name="txt_rh" id="txt_rh" disabled>

                                                    </select>
                                                </div>
                                                <div class="col-md-3 d-flex flex-column mb-3">
                                                    <label class="section-subheader"><i
                                                            class="fas fa-street-view mr-1"></i></i>Estrato
                                                        Socieconomico</label>
                                                    <select class="form-control select2bs4" style="width: 100%;"
                                                        name="txt_estrato" id="txt_estrato" disabled>

                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Perfil Profesional -->
                                    <div class="card card-erp mb-3 shadow-sm card-outline card-info">
                                        <div class="card-header">
                                            <h3 class="card-title">
                                                <i class="fas fa-sitemap mr-1"></i>
                                                Perfil Profesional
                                            </h3>
                                        </div>
                                        <div class="card-body">


                                            <h3 class="card-title">
                                                <i class="fas fa-university"></i>
                                                Formación Académica
                                            </h3><br>
                                            <hr>

                                            <div class="row">
                                                <div class="col-md-3 d-flex flex-column mb-3">
                                                    <label class="section-subheader"><i
                                                            class="fas fa-graduation-cap mr-1"></i>Nivel
                                                        Educativo</label>
                                                    <select class="form-control select-erp select2bs4" style="width: 100%;"
                                                        name="txt_nivel" id="txt_nivel" disabled>

                                                    </select>
                                                </div>
                                                <div class="col-md-3 d-flex flex-column mb-3">
                                                    <label class="section-subheader"><i
                                                            class="fas fa-scroll mr-1"></i>Título</label>
                                                    <input type="text" class="form-control input-erp" name="txt_profesion"
                                                        id="txt_profesion" readonly>
                                                </div>
                                                <div class="col-md-3 d-flex flex-column mb-3">
                                                    <label class="section-subheader"><i
                                                            class="fas fa-calendar-check mr-1"></i>Año
                                                        de Grado</label>
                                                    <input type="text" class="form-control input-erp" name="txt_anio_grado"
                                                        id="txt_anio_grado" readonly>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div><!-- /pane-erp -->
                                <!-- ══ PANE CONTRATO ══ -->
                                <div class="pane" id="pane-contrato" style="display:none;">
                                    <div class="card card-outline card-info mb-3 shadow-sm">
                                        <div class="card-header">
                                            <h3 class="card-title">
                                                <i class="fas fa-file-contract mr-2"></i>Contrato
                                            </h3>
                                        </div>
                                        <div class="card-body">
                                            <h3 class="card-title">
                                                <i class="fas fa-briefcase"></i>
                                                Informacion Laboral
                                            </h3><br>
                                            <hr>
                                            <div class="row">
                                                <div class="col-md-2 d-flex flex-column mb-3">
                                                    <label class="section-subheader"><i
                                                            class="fas fa-briefcase mr-1"></i>Tipo
                                                        de Contrato</label>
                                                    <select class="form-control select2bs4" style="width: 100%;"
                                                        name="select_tipo_contrato" id="select_tipo_contrato" disabled>

                                                    </select>
                                                </div>
                                                <div class="col-md-2 d-flex flex-column mb-3">
                                                    <label class="section-subheader"><i
                                                            class="fas fa-calendar-alt mr-1"></i>Fecha Ingreso</label>
                                                    <input type="text" class="form-control input-erp"
                                                        name="txt_fecha_ingreso" id="txt_fecha_ingreso" readonly>
                                                </div>
                                                <div class="col-md-2 d-flex flex-column mb-3">
                                                    <label class="section-subheader"><i
                                                            class="fas fa-dollar-sign mr-1"></i>Salario Base</label>
                                                    <input type="text" class="form-control input-erp" name="txt_salario"
                                                        id="txt_salario" readonly>
                                                </div>
                                                <div class="col-md-3 d-flex flex-column mb-3">
                                                    <label class="section-subheader"><i
                                                            class="fas fa-briefcase mr-1"></i>Cargo</label>
                                                    <select class="form-control select2bs4" style="width: 100%;"
                                                        name="select_cargo_empleado" id="select_cargo_empleado" disabled>

                                                    </select>
                                                </div>
                                                <div class="col-md-3 d-flex flex-column mb-3">
                                                    <label class="section-subheader"><i
                                                            class="fas fa-sitemap mr-1"></i>Dependencia / Area</label>
                                                    <select class="form-control select2bs4" style="width: 100%;"
                                                        name="select_dependencia" id="select_dependencia" disabled>

                                                    </select>
                                                </div>
                                                <div class="col-md-12 d-flex flex-column mb-3">
                                                    <label class="section-subheader"><i
                                                            class="fas fa-comment-alt mr-1 text-dark"></i>Observaciones</label>
                                                    <textarea class="form-control input-epr" rows="7" id="txt_observaciones"
                                                        name="txt_observaciones"
                                                        placeholder="Notas adicionales sobre el contrato…"
                                                        readonly></textarea>
                                                </div>
                                            </div>
                                            <br>
                                        </div>
                                    </div>
                                </div><!-- /pane-contrato -->
                            </form><!-- /formulario -->

                            <!-- ════════════════ PANE BONIFICACIONES ════════════════ -->
                            <div class="pane" id="pane-bonificaciones" style="display:none;">
                                <div class="card card-outline card-info mb-3 shadow-sm">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            <i class="fas fa-hand-holding-usd mr-2 text-dark"></i>Bonificaciones
                                        </h3>
                                        <div class="card-tools">
                                            <button type="button" class="btn btn-info btn-sm" id="btn_toggle_form">
                                                <i class="fas fa-plus mr-1"></i>Agregar
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body p-0">

                                        <!-- Tabla registros -->
                                        <table id="tablaBonificaciones" class="table table-hover table-striped mb-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th><i class="fas fa-tag mr-1"></i>Concepto</th>
                                                    <th><i class="fas fa-dollar-sign mr-1"></i>Valor</th>
                                                    <th><i class="fas fa-redo mr-1"></i>Periodicidad</th>
                                                    <th><i class="fas fa-calendar-plus mr-1"></i>Fecha Inicio</th>
                                                    <th><i class="fas fa-circle mr-1"></i>Estado</th>
                                                    <th><i class="fas fa-comment mr-1"></i>Observaciones</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>

                                            </tbody>
                                        </table>

                                        <!-- Formulario nuevo registro -->
                                        <form id="formBonif"
                                            style="display:none;padding:16px;border-top:1px solid #dee2e6;background:#f8f9fa;">
                                            <p class="text-muted text-uppercase font-weight-bold small mb-2 d-block"><i
                                                    class="fas fa-plus-circle mr-1 text-primary"></i>Nuevo Registro de
                                                Bonificación</p>
                                            <div class="row">
                                                <div class="col-md-3 d-flex flex-column mb-3">

                                                    <label class="section-subheader">Concepto</label>
                                                    <input type="text" class="form-control" name="txt_concepto"
                                                        id="txt_concepto" placeholder="Ej: Bonif. productividad">
                                                </div>
                                                <div class="col-md-2 d-flex flex-column mb-3">

                                                    <label class="section-subheader">Valor ($)</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend"><span
                                                                class="input-group-text">$</span></div>
                                                        <input type="text" class="form-control" placeholder="0"
                                                            name="txt_valor" id="txt_valor">
                                                    </div>
                                                </div>
                                                <div class="col-md-2 d-flex flex-column mb-3">
                                                    <label class="section-subheader">Periodicidad</label>
                                                    <select class="form-control" name="select_periocidad"
                                                        id="select_periocidad">
                                                        <option>Mensual</option>
                                                        <option>Bimestral</option>
                                                        <option>Semestral</option>
                                                        <option>Anual</option>
                                                        <option>Única vez</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-2 d-flex flex-column mb-3">
                                                    <label class="section-subheader">Fecha Inicio</label>
                                                    <input type="text" class="form-control" name="txt_fecha_inicio"
                                                        id="txt_fecha_inicio">
                                                </div>
                                                <div class="col-md-3 d-flex flex-column mb-3">
                                                    <label class="section-subheader">Observaciones</label>
                                                    <input type="text" class="form-control"
                                                        placeholder="Acto administrativo…" name="txt_observaciones"
                                                        id="txt_observaciones">
                                                </div>
                                            </div>
                                            <button type="button" id="btn_guardar_bonif" class="btn btn-dark btn-sm"><i
                                                    class="fas fa-save mr-1"></i>Guardar</button>
                                            <button type="button" id="btn_cancelar_bonif"
                                                class="btn btn-default btn-sm ml-2"
                                                id="btn_cancelar_bonif">Cancelar</button>
                                        </form>
                                    </div>
                                </div>
                            </div><!-- /pane-bonificaciones -->
                            <!-- ════════════════ PANE AUXILIOS ════════════════ -->
                            <div class="pane" id="pane-auxilios">
                                <div class="card card-outline card-info mb-3 shadow-sm">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            <i class="fas fa-medkit mr-2 text-info"></i>Auxilios
                                        </h3>
                                        <div class="card-tools">
                                            <button class="btn btn-info btn-sm" id="btn_toggle_form_aux">
                                                <i class="fas fa-plus mr-1"></i>Agregar
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body p-0">
                                        <table class="table table-hover table-striped mb-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th><i class="fas fa-tag mr-1"></i>Tipo de Auxilio</th>
                                                    <th><i class="fas fa-dollar-sign mr-1"></i>Valor</th>
                                                    <th><i class="fas fa-calendar-plus mr-1"></i>Fecha Inicio</th>
                                                    <th><i class="fas fa-toggle-on mr-1"></i>Vigente</th>
                                                    <th><i class="fas fa-comment mr-1"></i>Observaciones</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>

                                            </tbody>
                                        </table>

                                        <!-- Formulario auxilios -->
                                        <div id="formAuxilio"
                                            style="display:none;padding:16px;border-top:1px solid #dee2e6;background:#f8f9fa;">
                                            <p class="text-muted text-uppercase font-weight-bold small mb-2 d-block"><i
                                                    class="fas fa-plus-circle mr-1 text-primary"></i>Nuevo Registro de
                                                Auxilio</p>
                                            <div class="row">
                                                <div class="col-md-3 d-flex flex-column mb-3">

                                                    <label class="section-subheader">Tipo de Auxilio</label>
                                                    <select class="form-control select2bs4" name="select_tipo_aux"
                                                        id="select_tipo_aux">
                                                        <option>— Seleccionar —</option>
                                                        <option>Auxilio de Transporte</option>
                                                        <option>Auxilio de Conectividad</option>
                                                        <option>Auxilio No Salarial</option>
                                                        <option>Auxilio Rodamiento</option>
                                                        <option>Auxilio Alimentacion / Vivienda</option>
                                                        <option>Auxilio Rodamiento</option>
                                                    </select>

                                                </div>

                                                <div class="col-md-2 d-flex flex-column mb-3">

                                                    <label class="section-subheader">Valor ($)</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend"><span
                                                                class="input-group-text">$</span></div>
                                                        <input type="text" class="form-control" placeholder="0"
                                                            name="txt_valor_aux" id="txt_valor_aux">
                                                    </div>

                                                </div>
                                                <div class="col-md-2 d-flex flex-column mb-3">

                                                    <label class="section-subheader">Fecha Inicio</label>
                                                    <input type="text" class="form-control" name="txt_fecha_ini_aux"
                                                        id="txt_fecha_ini_aux">

                                                </div>
                                                <div class="col-md-2 d-flex flex-column mb-3">

                                                    <label class="section-subheader">Vigente</label>
                                                    <select class="form-control select2bs4" name="txt_vigente"
                                                        id="txt_vigente">
                                                        <option>Sí</option>
                                                        <option>No</option>
                                                    </select>

                                                </div>
                                                <div class="col-md-3 d-flex flex-column mb-3">

                                                    <label class="section-subheader">Observaciones</label>
                                                    <input type="text" class="form-control"
                                                        placeholder="Decreto o resolución…" name="txt_observ_aux"
                                                        id="txt_observ_aux">
                                                </div>

                                            </div>
                                            <button class="btn btn-dark btn-sm"><i
                                                    class="fas fa-save mr-1"></i>Guardar</button>
                                            <button type="button" class="btn btn-default btn-sm ml-2"
                                                id="btn_cancelar_aux">Cancelar</button>
                                        </div>
                                    </div>
                                </div>
                            </div><!-- /pane-auxilios -->
                            <!-- ══ PANE VISTA PREVIA ══ -->
                            <div class="pane" id="pane-preview" style="display:none;">

                                <!-- Opciones del certificado -->
                                <div class="card card-outline card-secondary mb-3">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            <i class="fas fa-sliders-h mr-2"></i>Opciones del Certificado
                                        </h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">

                                            <!-- Radicado -->
                                            <div class="col-md-3">
                                                <div class="form-group mb-0">
                                                    <input type="text" class="form-control form-control-sm"
                                                        id="txt_radicado_cert"
                                                        placeholder="Numero radicado Ej: ASF-GH-2.6-0012-25">
                                                </div>
                                            </div>

                                            <!-- Switches -->
                                            <div class="col-md-9">
                                                <div class="row mt-1">
                                                    <div class="col-md-3">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input cert-opcion"
                                                                id="opt_salario" checked>
                                                            <label class="custom-control-label" for="opt_salario">
                                                                <i class="fas fa-dollar-sign mr-1"></i>Incluir Salario
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input cert-opcion"
                                                                id="opt_auxilio_transporte">
                                                            <label class="custom-control-label"
                                                                for="opt_auxilio_transporte">
                                                                <i class="fas fa-bus mr-1"></i>Auxilio de Transporte
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input cert-opcion"
                                                                id="opt_bonificaciones">
                                                            <label class="custom-control-label" for="opt_bonificaciones">
                                                                <i class="fas fa-hand-holding-usd mr-1"></i>Bonificaciones
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input cert-opcion"
                                                                id="opt_otros_auxilios">
                                                            <label class="custom-control-label" for="opt_otros_auxilios">
                                                                <i class="fas fa-coins mr-1"></i>Otros Auxilios
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>

                                <!-- Botón exportar -->
                                <div class="d-flex justify-content-end mb-3">
                                    <button class="btn btn-danger" id="btn_exportar_pdf">
                                        <i class="fas fa-file-pdf mr-1"></i>Exportar PDF
                                    </button>
                                </div>

                                <!-- Hoja certificado -->
                                <div class="card">
                                    <div class="card-body" id="preview_certificado" style="max-width:800px; margin:0 auto;
                    font-family:'Times New Roman', serif;
                    font-size:13px; line-height:1.8;
                    color:#111; text-align:justify;
                    padding: 40px 60px;">

                                        <!-- Línea decorativa superior -->
                                        <div style="height:4px;
                        background:linear-gradient(to right,#1a3a5c,#2980b9,#1a3a5c);
                        margin-bottom:30px;">
                                        </div>

                                        <!-- Radicado -->
                                        <p style="text-align:right; font-family:Arial,sans-serif;
                      font-size:11px; font-weight:bold;" id="prev_radicado"></p>

                                        <!-- Institución -->
                                        <p style="text-align:center; font-family:Arial,sans-serif;
                      font-size:12.5px; font-weight:bold;
                      text-transform:uppercase; line-height:1.6;" id="prev_institucion"></p>

                                        <!-- CERTIFICA -->
                                        <p style="text-align:center; font-family:Arial,sans-serif;
                      font-size:14px; font-weight:bold;
                      text-decoration:underline; letter-spacing:2px;
                      margin:20px 0;">
                                            CERTIFICA:
                                        </p>

                                        <!-- Párrafo principal — se regenera con JS -->
                                        <p id="prev_parrafo_principal" style="margin-bottom:16px;">
                                            <!-- Se llena dinámicamente -->
                                        </p>

                                        <!-- Párrafo conducta -->
                                        <p style="margin-bottom:16px;">
                                            Durante este período ha demostrado su compromiso con la empresa,
                                            responsabilidad, puntualidad en las labores encomendadas, así como
                                            también el respeto con sus compañeros.
                                        </p>

                                        <!-- Párrafo expedición -->
                                        <p id="prev_parrafo_expedicion" style="margin-bottom:16px;">
                                            <!-- Se llena dinámicamente -->
                                        </p>

                                        <p style="margin-bottom:40px;">Atentamente,</p>

                                        <!-- Espacio firma -->
                                        <div style="height:60px;"></div>

                                        <!-- Firmante -->
                                        <p style="font-family:Arial,sans-serif; font-size:12px;
                      font-weight:bold; margin-bottom:2px;" id="prev_firmante"></p>

                                        <p style="font-family:Arial,sans-serif; font-size:11.5px;
                      line-height:1.6; margin-bottom:6px;" id="prev_cargo_firmante"></p>

                                        <p style="font-family:Arial,sans-serif; font-size:9.5px;
                      color:#444; line-height:1.6;" id="prev_contacto"></p>

                                    </div>
                                </div>

                            </div>
                        </div><!-- /card-body-erp -->

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

        <?php require_once("actualizar_empleado.php") ?>
        <?php require_once("../MainJS/JS.php") ?>
        <script type="text/javascript" src="laboral.js"></script>
    </body>

    </html>
<?php
} else {
    header("location:" . Conectar::ruta() . "index.php");
}
?>