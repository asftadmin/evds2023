<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/Usuarios.php';
if (empty($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}
if (!(new Usuario())->autorizado_microsoft365((int) $_SESSION['user_id'])) {
    http_response_code(403);
    exit('Acceso no autorizado.');
}
if (empty($_SESSION['planner_csrf'])) {
    $_SESSION['planner_csrf'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <?php require_once __DIR__ . '/../MainHead/head.php'; ?>
    <title>Cumplimiento mensual Planner</title>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <?php require_once __DIR__ . '/../MainNav/nav.php';
        require_once __DIR__ . '/../MainMenu/menu.php'; ?>
        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <h1>Cumplimiento mensual de tareas Planner</h1>
                    <p>Consulte el avance mensual y las evidencias conservadas del cierre.</p>
                    <input type="hidden" id="planner_csrf"
                        value="<?= htmlspecialchars($_SESSION['planner_csrf'], ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </section>
            <section class="content">
                <div class="container-fluid">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-2 form-group"><label for="planner_anio">Año</label><select
                                        id="planner_anio" class="form-control" style="width:100%"></select></div>
                                <div class="col-md-2 form-group"><label for="planner_mes">Mes</label><select
                                        id="planner_mes" class="form-control" style="width:100%"></select></div>
                                <div class="col-md-4 form-group"><label for="planner_empleado">Empleado</label><select
                                        id="planner_empleado" class="form-control" multiple style="width:100%">
                                        <option value="todos" selected>Todos</option>
                                    </select></div>
                                <div class="col-md-4 form-group d-flex align-items-end flex-wrap">
                                    <button type="button" id="planner_grupos_cargar"
                                        class="btn btn-outline-primary mr-2 mb-1">Consultar grupos Microsoft 365</button>
                                    <button type="button" id="planner_consultar" class="btn btn-primary mb-1" disabled>Consultar</button>
                                </div>
                            </div>
                            <p id="planner_aviso" role="status" class="alert alert-info">Seleccione el período y consulte
                                primero los grupos Microsoft 365.</p>
                            <details id="planner_asociacion">
                                <summary>Seleccionar y validar plan mensual</summary>
                                <p class="mt-2">Confirme el año y mes con el responsable del plan. Un nombre como MARZO
                                    no demuestra a qué año pertenece.</p>
                                <div class="row">
                                    <div class="col-md-6 form-group"><label for="planner_grupo">Grupo Microsoft
                                            365</label><select id="planner_grupo" class="form-control"
                                            style="width:100%"></select></div>
                                    <div class="col-md-6 form-group"><label for="planner_plan">Plan</label><select
                                            id="planner_plan" class="form-control" style="width:100%"></select></div>
                                </div>
                                <button type="button" id="planner_validar" class="btn btn-warning">Validar asociación al
                                    período</button>
                                <p class="text-muted mt-2">La selección permite una consulta previa. Validar conserva la
                                    asociación para las capturas y el cierre automático.</p>
                            </details>
                        </div>
                    </div>
                    <div class="row" id="planner_indicadores" aria-live="polite">
                        <?php foreach (['evaluables' => 'Tareas evaluables', 'puntuales' => 'Puntuales', 'tardias' => 'Tardías', 'incumplidas' => 'Incumplidas', 'excluidas' => 'Por revisar', 'cumplimiento' => 'Cumplimiento'] as $campo => $titulo): ?>
                        <div class="col-lg-2 col-sm-4">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3 id="planner_<?= $campo ?>">—</h3>
                                    <p><?= $titulo ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Resultados por empleado</h2>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="planner_tabla" class="table table-striped table-bordered" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Empleado</th>
                                            <th>Asignadas</th>
                                            <th>Evaluables</th>
                                            <th>Puntuales</th>
                                            <th>Tardías</th>
                                            <th>Incumplidas</th>
                                            <th>Excluidas</th>
                                            <th>Cumplimiento</th>
                                            <th>Estado del período</th>
                                            <th>Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Excepciones y revisión administrativa</h2>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="planner_excepciones" class="table table-striped" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Tarea</th>
                                            <th>Object ID responsable</th>
                                            <th>Motivo</th>
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
        <?php require_once __DIR__ . '/../MainFooter/footer.php'; ?>
    </div>
    <div class="modal fade" id="planner_modal" tabindex="-1" role="dialog" aria-labelledby="planner_detalle_titulo"
        aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="planner_detalle_titulo">Detalle por empleado</h5><button class="close"
                        type="button" data-dismiss="modal" aria-label="Cerrar"><span
                            aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p id="planner_detalle_plan"></p>
                    <div class="table-responsive">
                        <table id="planner_detalle" class="table table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Tarea</th>
                                    <th>Fecha límite (Colombia)</th>
                                    <th>Fecha de finalización (Colombia)</th>
                                    <th>Hora de finalización (Colombia)</th>
                                    <th>Estado</th>
                                    <th>Calificación</th>
                                    <th>Motivo</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require_once __DIR__ . '/../MainJS/JS.php'; ?><script src="cumplimiento.js"></script>
</body>

</html>
