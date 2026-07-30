<?php
require_once "../../config/conexion.php";

if (!isset($_SESSION["user_id"])) {
    header("location:" . Conectar::ruta() . "index.php");
    exit;
}

if (empty($_SESSION["csrf_jornadas"])) {
    $_SESSION["csrf_jornadas"] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="es">
<?php require_once("../MainHead/head.php"); ?>
<link rel="stylesheet" href="jornadas.css">
<title>Jornadas de mi Equipo</title>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <?php require_once("../MainNav/nav.php"); ?>
        <?php require_once("../MainMenu/menu.php"); ?>

        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-7">
                            <h1>
                                <i class="fas fa-users mr-2"></i>
                                Jornadas de mi Equipo
                            </h1>
                        </div>
                        <div class="col-sm-5">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item">
                                    <a href="../home/home2.php">Inicio</a>
                                </li>
                                <li class="breadcrumb-item active">
                                    Jornadas del equipo
                                </li>
                            </ol>
                        </div>
                    </div>
                </div>
            </section>

            <section class="content">
                <div class="container-fluid">
                    <div id="alerta-contexto" class="alert alert-info py-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        <span id="texto-contexto">Validando jefe...</span>
                    </div>

                    <div class="alert alert-warning py-2">
                        <i class="fas fa-check-circle mr-1"></i>
                        Las jornadas registradas en esta pantalla quedan
                        aprobadas automáticamente a nombre del jefe.
                    </div>

                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-user-clock mr-1"></i>
                                Registrar jornada de un subordinado
                            </h3>
                        </div>

                        <form id="form-jornada-equipo" autocomplete="off">
                            <div class="card-body">
                                <input
                                    type="hidden"
                                    id="csrf_token"
                                    value="<?php echo htmlspecialchars(
                                        $_SESSION["csrf_jornadas"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ); ?>"
                                >

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="empleado_id">Empleado</label>
                                            <select
                                                class="form-control select2"
                                                id="empleado_id"
                                                required
                                                style="width: 100%;"
                                            >
                                                <option value="">
                                                    Seleccione un subordinado
                                                </option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="fecha">Fecha</label>
                                            <input
                                                type="date"
                                                class="form-control"
                                                id="fecha"
                                                required
                                            >
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="dia_semana">Día</label>
                                            <input
                                                type="text"
                                                class="form-control"
                                                id="dia_semana"
                                                readonly
                                            >
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="hora_entrada">
                                                Hora de entrada
                                            </label>
                                            <input
                                                type="time"
                                                class="form-control"
                                                id="hora_entrada"
                                                required
                                            >
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="hora_salida">
                                                Hora de salida
                                            </label>
                                            <input
                                                type="time"
                                                class="form-control"
                                                id="hora_salida"
                                                required
                                            >
                                            <div class="custom-control custom-checkbox mt-2">
                                                <input
                                                    type="checkbox"
                                                    class="custom-control-input"
                                                    id="salida_dia_siguiente"
                                                >
                                                <label
                                                    class="custom-control-label"
                                                    for="salida_dia_siguiente"
                                                >
                                                    La salida es al día siguiente
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="horas_ordinarias">
                                                Horas ordinarias netas
                                            </label>
                                            <input
                                                type="text"
                                                class="form-control"
                                                id="horas_ordinarias"
                                                value="00:00"
                                                readonly
                                            >
                                            <small
                                                id="ayuda-horas-ordinarias"
                                                class="form-text text-muted"
                                            >
                                                De lunes a viernes descuenta una
                                                hora de almuerzo.
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="ubicacion">
                                                Frente, obra o ubicación
                                            </label>
                                            <input
                                                type="text"
                                                class="form-control"
                                                id="ubicacion"
                                                maxlength="250"
                                                required
                                            >
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="actividad">
                                                Actividad ejecutada
                                            </label>
                                            <textarea
                                                class="form-control"
                                                id="actividad"
                                                rows="3"
                                                maxlength="4000"
                                                required
                                            ></textarea>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="observaciones">
                                                Observaciones
                                            </label>
                                            <textarea
                                                class="form-control"
                                                id="observaciones"
                                                rows="3"
                                                maxlength="4000"
                                            ></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer">
                                <button
                                    type="submit"
                                    class="btn btn-success"
                                    id="btn-guardar-equipo"
                                >
                                    <i class="fas fa-check mr-1"></i>
                                    Registrar y aprobar
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-default"
                                    id="btn-limpiar"
                                >
                                    <i class="fas fa-eraser mr-1"></i>Limpiar
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-history mr-1"></i>
                                Historial del equipo
                            </h3>
                        </div>

                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-5">
                                    <label for="filtro_fechas">Periodo</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="filtro_fechas"
                                        autocomplete="off"
                                    >
                                </div>
                                <div class="col-md-7 d-flex align-items-end">
                                    <button
                                        type="button"
                                        class="btn btn-info mr-2"
                                        id="btn-filtrar"
                                    >
                                        <i class="fas fa-search mr-1"></i>Consultar
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-secondary"
                                        id="btn-limpiar-filtro"
                                    >
                                        <i class="fas fa-undo mr-1"></i>Restablecer
                                    </button>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table
                                    id="tabla-equipo"
                                    class="table table-bordered table-striped table-hover"
                                    width="100%"
                                >
                                    <thead>
                                        <tr>
                                            <th>Empleado</th>
                                            <th>Fecha</th>
                                            <th>Entrada</th>
                                            <th>Salida</th>
                                            <th>Horas</th>
                                            <th>Ubicación</th>
                                            <th>Actividad</th>
                                            <th>Origen</th>
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

        <?php require_once("../MainFooter/footer.php"); ?>
    </div>

    <?php require_once("../MainJS/JS.php"); ?>
    <script src="equipo.js"></script>
</body>
</html>
