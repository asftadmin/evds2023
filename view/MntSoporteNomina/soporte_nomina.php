<?php

require_once ('../../config/conexion.php');

if (isset($_SESSION['user_id'])) {
    // Comparte un token de escritura con la configuración de tarifas.
    if (empty($_SESSION['soporte_nomina_csrf'])) {
        $_SESSION['soporte_nomina_csrf'] = bin2hex(random_bytes(32));
    }
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <?php require_once ('../MainHead/head.php'); ?>

    <title>Soporte Nómina</title>

</head>

<body class="hold-transition sidebar-mini">
    <input type="hidden" id="soporte_csrf" value="<?php echo htmlspecialchars($_SESSION['soporte_nomina_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
    <div class="wrapper">

        <?php require_once ('../MainNav/nav.php'); ?>
        <?php require_once ('../MainMenu/menu.php'); ?>

        <div class="content-wrapper">

            <!-- //Encabezado de la vista -->
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>Soporte Nómina</h1>
                        </div>

                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item">
                                    <a href="../home/">Inicio</a>
                                </li>
                                <li class="breadcrumb-item active">
                                    Soporte Nómina
                                </li>
                            </ol>
                        </div>
                    </div>
                </div>
            </section>

            <section class="content">
                <div class="container-fluid p-2">

                    <!-- // Selección del periodo que se procesará -->
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-calendar-alt mr-1"></i>
                                Periodo
                            </h3>
                        </div>

                        <div class="card-body">
                            <div class="row">

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="periodo_mes">Mes</label>

                                        <select id="periodo_mes" class="form-control select2" style="width: 100%;">

                                            <option value="">Seleccione...</option>
                                            <option value="1">Enero</option>
                                            <option value="2">Febrero</option>
                                            <option value="3">Marzo</option>
                                            <option value="4">Abril</option>
                                            <option value="5">Mayo</option>
                                            <option value="6">Junio</option>
                                            <option value="7">Julio</option>
                                            <option value="8">Agosto</option>
                                            <option value="9">Septiembre</option>
                                            <option value="10">Octubre</option>
                                            <option value="11">Noviembre</option>
                                            <option value="12">Diciembre</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="periodo_anio">Año</label>

                                        <select id="periodo_anio" class="form-control select2" style="width: 100%;">

                                            <option value="">Seleccione...</option>
                                            <?php // Permite consultar periodos históricos y configurar próximos años. ?>
                                            <?php for ($anio = 2000; $anio <= (int) date('Y') + 3; $anio++) { ?>
                                            <option value="<?php echo $anio; ?>"><?php echo $anio; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-4 d-flex align-items-end">
                                    <div class="form-group">
                                        <button type="button" id="btn_consultar" class="btn btn-primary">Consultar periodo</button>
                                        <a href="tarifas.php" class="btn btn-outline-secondary">Configurar tarifas</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- // Origen de la información -->
                    <div class="card card-outline card-secondary">

                        <div class="card-header p-0 pt-1">

                            <ul class="nav nav-tabs" id="tabs_origen" role="tablist">

                                <li class="nav-item">
                                    <a class="nav-link active" id="tab_archivo" data-toggle="pill" href="#panel_archivo"
                                        role="tab">

                                        <i class="fas fa-file-excel mr-1"></i>
                                        Cargar archivo
                                    </a>
                                </li>

                                <li class="nav-item">
                                    <a class="nav-link" id="tab_liquidacion" data-toggle="pill"
                                        href="#panel_liquidacion" role="tab">

                                        <i class="fas fa-calculator mr-1"></i>
                                        Obtener desde liquidación
                                    </a>
                                </li>

                            </ul>
                        </div>

                        <div class="card-body">

                            <div class="tab-content">

                                <!-- // Origen 1: archivo Excel -->
                                <div class="tab-pane fade show active" id="panel_archivo" role="tabpanel">

                                    <div class="row">

                                        <div class="col-md-8">
                                            <div class="form-group">

                                                <label for="archivo_auxilio">
                                                    Archivo de auxilios
                                                </label>

                                                <div class="custom-file">

                                                    <input type="file" class="custom-file-input" id="archivo_auxilio"
                                                        accept=".xlsx,.xls">

                                                    <label class="custom-file-label" for="archivo_auxilio">

                                                        Seleccionar archivo Excel
                                                    </label>

                                                </div>

                                                <small class="form-text text-muted">
                                                    El archivo debe contener las columnas cedu_empl y valor.
                                                </small>

                                            </div>
                                        </div>

                                        <div class="col-md-4 d-flex align-items-end">

                                            <div class="form-group w-100">

                                                <button type="button" id="btn_validar_archivo"
                                                    class="btn btn-primary btn-block">

                                                    <i class="fas fa-search mr-1"></i>
                                                    Validar y guardar borradores
                                                </button>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                                <!-- // Origen 2: liquidación, pendiente de desarrollo -->
                                <div class="tab-pane fade" id="panel_liquidacion" role="tabpanel">

                                    <div class="callout callout-info">

                                        <h5>
                                            <i class="fas fa-info-circle mr-1"></i>
                                            Paso previo a la liquidación
                                        </h5>

                                        <p class="mb-3">
                                            Antes de obtener los auxilios desde una
                                            liquidación por periodo se deberá realizar
                                            un paso previo. Esta funcionalidad se
                                            desarrollará en una siguiente etapa.
                                        </p>

                                        <button type="button" class="btn btn-secondary" disabled>

                                            <i class="fas fa-cogs mr-1"></i>
                                            Paso previo
                                        </button>

                                    </div>

                                    <hr>

                                    <div class="row">

                                        <div class="col-md-8">

                                            <p class="text-muted mb-0">
                                                Una vez se implemente el paso anterior,
                                                se habilitará la consulta de la
                                                liquidación correspondiente al periodo
                                                seleccionado.
                                            </p>

                                        </div>

                                        <div class="col-md-4">

                                            <button type="button" id="btn_obtener_liquidacion"
                                                class="btn btn-primary btn-block" disabled>

                                                <i class="fas fa-search mr-1"></i>
                                                Obtener liquidación
                                            </button>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>
                    </div>

                    <!-- // Inconsistencias encontradas al validar el archivo -->
                    <div class="card card-outline card-warning" id="card_inconsistencias" style="display: none;">

                        <div class="card-header">

                            <h3 class="card-title">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                Inconsistencias
                            </h3>

                        </div>

                        <div class="card-body">

                            <div class="alert alert-warning">
                                Los siguientes registros no pudieron relacionarse
                                porque la cédula del archivo no coincide con
                                <strong>cedu_empl</strong>.
                            </div>

                            <div class="table-responsive">

                                <table id="tabla_inconsistencias"
                                    class="table table-bordered table-striped table-hover">

                                    <thead>
                                        <tr>
                                            <th>Cédula archivo</th>
                                            <th>Valor Auxilio</th>
                                            <th>Observación</th>
                                        </tr>
                                    </thead>

                                    <tbody></tbody>

                                </table>

                            </div>

                        </div>
                    </div>

                    <!-- // Resultado que revisará Contabilidad -->
                    <div class="card card-outline card-success" id="card_detalle" style="display: none;">

                        <div class="card-header">

                            <h3 class="card-title">
                                <i class="fas fa-list mr-1"></i>
                                Detalle de auxilios
                            </h3>

                            <div class="card-tools">

                                <button type="button" id="btn_procesar" class="btn btn-success btn-sm" disabled>

                                    <i class="fas fa-check mr-1"></i>
                                    Procesar seleccionados
                                </button>

                            </div>

                        </div>

                        <div class="card-body">

                            <div class="table-responsive">

                                <table id="tabla_detalle" class="table table-bordered table-striped table-hover">

                                    <thead>
                                        <tr>

                                            <th class="text-center align-middle" style="width: 40px;">
                                                <input type="checkbox" id="check_todos" class="mt-0">
                                            </th>

                                            <th>Empleado</th>
                                            <th>Total Auxilio</th>
                                            <th>Días Alimentación</th>
                                            <th>Días Hospedaje</th>
                                            <th>Valor Alimentación</th>
                                            <th>Valor Hospedaje</th>
                                            <th>Otros</th>
                                            <th>Estado</th>

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

        <?php require_once ('../MainFooter/footer.php'); ?>

    </div>

    <?php require_once ('../MainJS/JS.php'); ?>


    <script src="soporte_nomina.js"></script>

</body>

</html>

<?php
} else {
    header('Location:' . Conectar::ruta() . 'index.php');
}
?>
