<?php
require_once '../../config/conexion.php';
if (!isset($_SESSION['user_id'])) {
    header('location:' . Conectar::ruta() . 'index.php');
    exit;
}
if (empty($_SESSION['csrf_jornadas'])) {
    $_SESSION['csrf_jornadas'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="es">
<?php require_once ('../MainHead/head.php'); ?>
<link rel="stylesheet" href="jornadas.css">
<title>Jornadas de mi Equipo · Registro individual</title>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <?php require_once ('../MainNav/nav.php'); ?>
    <?php require_once ('../MainMenu/menu.php'); ?>
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-8"><h1><i class="fas fa-users mr-2"></i>Jornadas de mi Equipo</h1></div>
                    <div class="col-sm-4"><ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="../home/home2.php">Inicio</a></li>
                        <li class="breadcrumb-item active">Registro individual</li>
                    </ol></div>
                </div>
            </div>
        </section>
        <section class="content">
            <div class="container-fluid p-2">
                <div id="alerta-contexto" class="alert alert-info py-2" role="status">
                    <i class="fas fa-info-circle mr-1"></i><span id="texto-contexto">Validando jefe inmediato...</span>
                </div>
                <div class="card card-outline card-primary">
                    <div class="card-body">
                        <label for="empleado_id">Empleados para registrar la jornada</label>
                        <select id="empleado_id" class="form-control select2" multiple style="width:100%" disabled></select>
                        <small id="ayuda-empleado" class="form-text text-muted">La misma jornada se guardará para todos los seleccionados. Si uno falla, se revierte todo el registro.</small>
                    </div>
                </div>
                <div id="contenido-empleado" style="display:none;">
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
                                <div id="contexto-registro" class="alert alert-light border" role="status" aria-live="polite"></div>
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
                                                Obra / ubicación
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

                <div class="card card-outline card-primary">
                    <div class="card-header"><h3 class="card-title"><i class="fas fa-history mr-1"></i>Historial del empleado</h3></div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="historial_empleado_id">Empleado del historial</label>
                            <select id="historial_empleado_id" class="form-control select2" style="width:100%" disabled>
                                <option value="">Seleccione un empleado</option>
                            </select>
                            <small class="form-text text-muted">La edición, anulación y aprobación se realizan solo para este empleado.</small>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-5 form-group">
                                <label for="filtro_fechas">Periodo</label>
                                <input id="filtro_fechas" class="form-control" type="text" readonly>
                            </div>
                            <div class="col-md-3 form-group">
                                <label for="filtro_estado">Estado</label>
                                <select id="filtro_estado" class="form-control">
                                    <option value="">Todos los estados</option>
                                    <option value="BORRADOR">Borrador</option>
                                    <option value="CONFIRMADO_EMPLEADO">Confirmado por el empleado</option>
                                    <option value="PENDIENTE_APROBACION">Pendiente de aprobación</option>
                                    <option value="APROBADO">Aprobado</option>
                                    <option value="PENDIENTE_LIQUIDACION">Pendiente de liquidación</option>
                                    <option value="LIQUIDADO">Liquidada</option>
                                    <option value="RECHAZADO">Rechazado</option>
                                    <option value="ANULADO">Anulado</option>
                                    <option value="PENDIENTE_CORRECCION">Pendiente de corrección</option>
                                    <option value="CORREGIDO">Corregido</option>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end form-group jornada-acciones">
                                <button id="btn-filtrar" class="btn btn-info mr-2" type="button"><i class="fas fa-search mr-1"></i>Consultar</button>
                                <button id="btn-limpiar-filtro" class="btn btn-secondary" type="button">Restablecer</button>
                            </div>
                        </div>
                        <div id="resumen-confirmacion" class="alert alert-light border" role="status" aria-live="polite">Consulte un periodo.</div>
                        <p class="small text-muted">La aprobación incluye las jornadas registradas por el jefe que estén pendientes en el periodo consultado. Todas deben estar confirmadas y firmadas por el empleado. Al aprobar quedan pendientes de liquidación con esa misma firma. Los filtros de estado y búsqueda solo afectan el historial.</p>
                        <div class="jornada-acciones mb-3">
                            <button id="btn-registrar-aprobar" class="btn btn-success mr-2" type="button" disabled><i class="fas fa-check-circle mr-1"></i>Registrar y aprobar periodo</button>
                            <button id="btn-pdf-borrador" class="btn btn-outline-danger" type="button" disabled><i class="fas fa-file-pdf mr-1"></i>PDF borrador</button>
                            <button id="btn-pdf-lote" class="btn btn-outline-danger" type="button" disabled>
                                <i class="fas fa-file-pdf mr-1"></i>PDF lote de empleados seleccionados
                            </button>
                        </div>
                        <p class="small text-muted">PDF lote usa el periodo del historial y los empleados seleccionados en el selector superior, incluida la opción Todos los empleados. Solo incluye quienes tengan jornadas guardadas en ese periodo.</p>
                        <div class="table-responsive">
                            <table id="tabla-jornadas" class="table table-bordered table-hover table-sm" style="width:100%">
                                <thead><tr><th>Día</th><th>Fecha</th><th>Entrada</th><th>Salida</th><th>Horas ordinarias</th><th>Ubicación</th><th>Actividad</th><th>Observaciones</th><th>Estado</th><th>Acciones</th></tr></thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </section>
    </div>
    <?php require_once ('../MainFooter/footer.php'); ?>
</div>
<?php require_once ('../MainJS/JS.php'); ?>
<script src="periodo_jornadas.js"></script>
<script src="equipo_individual.js"></script>
</body>
</html>
