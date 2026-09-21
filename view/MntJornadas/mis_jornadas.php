<?php
require_once '../../config/conexion.php';

if (!isset($_SESSION['user_id'])) {
    header('location:' . Conectar::ruta() . 'index.php');
    exit;
}

// Token independiente del resto de módulos para proteger las mutaciones AJAX.
if (empty($_SESSION['csrf_jornadas'])) {
    $_SESSION['csrf_jornadas'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="es">
<?php require_once ('../MainHead/head.php'); ?>
<link rel="stylesheet" href="jornadas.css">
<title>Mis Jornadas</title>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <?php require_once ('../MainNav/nav.php'); ?>
        <?php require_once ('../MainMenu/menu.php'); ?>

        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>
                                <i class="fas fa-user-clock mr-2"></i>Mis Jornadas
                            </h1>
                        </div>

                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item">
                                    <a href="../home/home2.php">Inicio</a>
                                </li>
                                <li class="breadcrumb-item active">Mis Jornadas</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </section>

            <section class="content">
                <div class="container-fluid p-2">

                    <!-- Registro de jornada -->
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-clock mr-1"></i>
                                Registro de jornada
                            </h3>
                        </div>

                        <form id="form-jornada" autocomplete="off">
                            <div class="card-body">
                                <input type="hidden" id="jornada_id" name="jornada_id">

                                <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo htmlspecialchars(
    $_SESSION['csrf_jornadas'],
    ENT_QUOTES,
    'UTF-8'
); ?>">

                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="fecha">Fecha</label>

                                            <input type="date" class="form-control" id="fecha" name="fecha"
                                                aria-describedby="ayuda-fecha" required>

                                            <small id="ayuda-fecha" class="form-text text-muted"
                                                aria-live="polite"></small>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="dia_semana">Día</label>

                                            <input type="text" class="form-control" id="dia_semana" readonly>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="hora_entrada">
                                                Hora de entrada
                                            </label>

                                            <input type="time" class="form-control" id="hora_entrada"
                                                name="hora_entrada" required>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="hora_salida">
                                                Hora de salida
                                            </label>

                                            <input type="time" class="form-control" id="hora_salida" name="hora_salida"
                                                required>

                                            <div class="custom-control custom-checkbox mt-2">
                                                <input type="checkbox" class="custom-control-input"
                                                    id="salida_dia_siguiente">

                                                <label class="custom-control-label" for="salida_dia_siguiente">
                                                    La salida es al día siguiente
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="horas_ordinarias">
                                                Horas ordinarias netas
                                            </label>

                                            <input type="text" class="form-control" id="horas_ordinarias"
                                                name="horas_ordinarias" value="00:00" readonly>

                                            <small id="ayuda-horas-ordinarias" class="form-text text-muted">
                                                Se calcula entre entrada y salida.
                                                De lunes a viernes descuenta una
                                                hora de almuerzo.
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-md-9">
                                        <div class="form-group">
                                            <label for="ubicacion">
                                                Ubicación
                                            </label>

                                            <select class="form-control" id="ubicacion" name="ubicacion" required>
                                                <option value="">
                                                    Seleccione una ubicación
                                                </option>
                                                <option value="Sede principal">
                                                    Sede principal
                                                </option>
                                                <option value="Obras varias">
                                                    Obras varias
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="actividad">
                                                Actividad ejecutada
                                            </label>

                                            <textarea class="form-control" id="actividad" name="actividad" rows="3"
                                                maxlength="4000" required></textarea>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="observaciones">
                                                Observaciones o justificación
                                            </label>

                                            <textarea class="form-control" id="observaciones" name="observaciones"
                                                rows="3" maxlength="4000"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer jornada-acciones">
                                <button type="submit" class="btn btn-primary" id="btn-guardar">
                                    <i class="fas fa-save mr-1"></i>
                                    Guardar borrador
                                </button>

                                <button type="button" class="btn btn-default" id="btn-limpiar">
                                    <i class="fas fa-eraser mr-1"></i>
                                    Limpiar
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Historial y reporte para firma -->
                    <div class="card card-outline card-primary">
                        <div class="card-header p-2">
                            <ul class="nav nav-pills" id="tabs-jornadas" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="tab-historial" data-toggle="pill"
                                        href="#contenido-historial" role="tab" aria-controls="contenido-historial"
                                        aria-selected="true">
                                        <i class="fas fa-history mr-1"></i>
                                        Historial
                                    </a>
                                </li>

                                <li class="nav-item">
                                    <a class="nav-link" id="tab-reporte-firma" data-toggle="pill"
                                        href="#contenido-reporte-firma" role="tab"
                                        aria-controls="contenido-reporte-firma" aria-selected="false">
                                        <i class="fas fa-file-signature mr-1"></i>
                                        Reporte para firma
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <div class="card-body">
                            <div class="tab-content" id="contenido-tabs-jornadas">

                                <!-- Historial de jornadas -->
                                <div class="tab-pane fade show active" id="contenido-historial" role="tabpanel"
                                    aria-labelledby="tab-historial">
                                    <div class="row mb-3">
                                        <div class="col-md-5">
                                            <label for="filtro_fechas">
                                                Periodo
                                            </label>

                                            <input type="text" class="form-control" id="filtro_fechas"
                                                autocomplete="off">
                                        </div>

                                        <div class="col-md-3">
                                            <label for="filtro_estado">
                                                Estado
                                            </label>

                                            <select class="form-control" id="filtro_estado">
                                                <option value="">
                                                    Todos los estados
                                                </option>

                                                <option value="BORRADOR">
                                                    Borrador
                                                </option>

                                                <option value="PENDIENTE_APROBACION">
                                                    Pendiente aprobación
                                                </option>

                                                <option value="APROBADO">
                                                    Aprobado
                                                </option>

                                                <option value="PENDIENTE_LIQUIDACION">
                                                    Pendiente liquidación
                                                </option>

                                                <option value="LIQUIDADO">Liquidada</option>

                                                <option value="RECHAZADO">
                                                    Rechazado
                                                </option>

                                                <option value="ANULADO">
                                                    Anulado
                                                </option>

                                                <option value="PENDIENTE_CORRECCION">
                                                    Pendiente de corrección
                                                </option>
                                            </select>
                                        </div>

                                        <div class="col-md-4 d-flex align-items-end flex-wrap jornada-acciones">
                                            <button type="button" class="btn btn-info mr-2" id="btn-filtrar">
                                                <i class="fas fa-search mr-1"></i>
                                                Consultar
                                            </button>

                                            <button type="button" class="btn btn-secondary" id="btn-limpiar-filtro">
                                                <i class="fas fa-undo mr-1"></i>
                                                Restablecer
                                            </button>
                                        </div>
                                    </div>

                                    <div class="jornada-acciones mb-2">
                                        <button type="button" class="btn btn-success" id="btn-enviar-seleccionadas"
                                            disabled>
                                            <i class="fas fa-paper-plane mr-1"></i>
                                            Enviar seleccionadas
                                        </button>

                                        <span id="conteo-seleccionadas" class="text-muted" aria-live="polite">
                                            0 seleccionadas
                                        </span>
                                    </div>

                                    <p class="small text-muted">
                                        La casilla del encabezado selecciona los
                                        borradores de todas las páginas del
                                        resultado filtrado. Al cambiar los filtros
                                        se restablece la selección. Máximo 500 por
                                        envío.
                                    </p>

                                    <div id="resultado-envio" class="alert d-none" role="status"></div>

                                    <div class="table-responsive">
                                        <table id="tabla-jornadas"
                                            class="table table-bordered table-striped table-hover" width="100%">
                                            <thead>
                                                <tr>
                                                    <th>
                                                        <input type="checkbox" id="seleccionar-jornadas"
                                                            aria-label="Seleccionar todos los borradores filtrados">
                                                    </th>
                                                    <th>Día</th>
                                                    <th>Fecha</th>
                                                    <th>Entrada</th>
                                                    <th>Salida</th>
                                                    <th>Horas ordinarias</th>
                                                    <th>Ubicación</th>
                                                    <th>Actividad</th>
                                                    <th>Estado</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>

                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Reporte consolidado para firma -->
                                <div class="tab-pane fade" id="contenido-reporte-firma" role="tabpanel"
                                    aria-labelledby="tab-reporte-firma">
                                    <div class="alert alert-info py-2">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Seleccione el rango de fechas que desea
                                        revisar. El reporte incluirá únicamente
                                        jornadas aprobadas y disponibles para firma.
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-5">
                                            <label for="reporte_fechas">
                                                Periodo del reporte
                                            </label>

                                            <input type="text" class="form-control" id="reporte_fechas"
                                                autocomplete="off">
                                        </div>

                                        <div class="col-md-7 d-flex align-items-end jornada-acciones">
                                            <button type="button" class="btn btn-info" id="btn-consultar-reporte">
                                                <i class="fas fa-search mr-1"></i>
                                                Consultar reporte
                                            </button>
                                        </div>
                                    </div>

                                    <div id="resumen-reporte-firma" class="alert alert-light border d-none">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <strong>Desde:</strong>
                                                <span id="reporte-fecha-desde">
                                                    -
                                                </span>
                                            </div>

                                            <div class="col-md-4">
                                                <strong>Hasta:</strong>
                                                <span id="reporte-fecha-hasta">
                                                    -
                                                </span>
                                            </div>

                                            <div class="col-md-4">
                                                <strong>Jornadas:</strong>
                                                <span id="reporte-cantidad">
                                                    0
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="mensaje-reporte-vacio" class="alert alert-warning d-none" role="alert">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>
                                        No existen jornadas aprobadas disponibles
                                        para firma en el rango seleccionado.
                                    </div>

                                    <div id="contenedor-reporte-firma" class="d-none">
                                        <div class="table-responsive">
                                            <table id="tabla-reporte-firma"
                                                class="table table-bordered table-striped table-hover" width="100%">
                                                <thead>
                                                    <tr>
                                                        <th>Fecha</th>
                                                        <th>Día</th>
                                                        <th>Entrada</th>
                                                        <th>Salida</th>
                                                        <th>Horas ordinarias</th>
                                                        <th>Ubicación</th>
                                                        <th>Actividad</th>
                                                    </tr>
                                                </thead>

                                                <tbody></tbody>
                                            </table>
                                        </div>

                                        <div class="text-right mt-3">
                                            <button type="button" class="btn btn-success" id="btn-firmar-reporte"
                                                disabled>
                                                <i class="fas fa-signature mr-1"></i>
                                                Firmar reporte
                                            </button>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>
            </section>
        </div>

        <?php require_once ('../MainFooter/footer.php'); ?>
    </div>

    <?php require_once ('modal_firma.php'); ?>

    <?php require_once ('../MainJS/JS.php'); ?>
    <script src="mis_jornadas.js"></script>
</body>

</html>
