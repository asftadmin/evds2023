<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/Usuarios.php';

// Restringe la vista antes de renderizar datos o generar el token del formulario.
if (empty($_SESSION['user_id'])) { header('Location: ../../index.php'); exit; }
if (!(new Usuario())->autorizado_microsoft365((int) $_SESSION['user_id'])) {
    http_response_code(403);
    exit('No tiene permiso para administrar Microsoft 365.');
}
if (empty($_SESSION['microsoft365_csrf'])) { $_SESSION['microsoft365_csrf'] = bin2hex(random_bytes(32)); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php require_once __DIR__ . '/../MainHead/head.php'; ?>
    <title>Sincronización Microsoft 365</title>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <?php require_once __DIR__ . '/../MainNav/nav.php'; ?>
    <?php require_once __DIR__ . '/../MainMenu/menu.php'; ?>
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <h1>Sincronización de usuarios Microsoft 365</h1>
                <a href="cumplimiento.php" class="btn btn-outline-primary mb-2">Cumplimiento mensual Planner</a>
                <p class="text-muted mb-2">Consulta y vinculación de usuarios corporativos con Microsoft Entra.</p>
                <button type="button" id="m365_consultar" class="btn btn-primary">
                    <i class="fas fa-sync-alt mr-1" aria-hidden="true"></i> Consultar Microsoft 365
                </button>
                <input type="hidden" id="m365_csrf" value="<?php echo htmlspecialchars($_SESSION['microsoft365_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
                <p id="m365_aviso" class="text-muted mt-2" role="status">Pulse Consultar Microsoft 365 para obtener las vinculaciones actuales.</p>
            </div>
        </section>
        <section class="content"><div class="container-fluid">
            <!-- Los indicadores se completan únicamente con una comparación exitosa. -->
            <div class="row" aria-live="polite">
                <div class="col-lg-3 col-sm-6"><div class="small-box bg-info"><div class="inner">
                    <h3 id="m365_total">—</h3><p>Empleados corporativos</p>
                </div><div class="icon"><i class="fas fa-users" aria-hidden="true"></i></div></div></div>
                <div class="col-lg-3 col-sm-6"><div class="small-box bg-success"><div class="inner">
                    <h3 id="m365_vinculados">—</h3><p>Vinculados</p>
                </div><div class="icon"><i class="fas fa-link" aria-hidden="true"></i></div></div></div>
                <div class="col-lg-3 col-sm-6"><div class="small-box bg-primary"><div class="inner">
                    <h3 id="m365_pendientes">—</h3><p>Pendientes</p>
                </div><div class="icon"><i class="fas fa-user-plus" aria-hidden="true"></i></div></div></div>
                <div class="col-lg-3 col-sm-6"><div class="small-box bg-warning"><div class="inner">
                    <h3 id="m365_revision">—</h3><p>Requieren revisión</p>
                </div><div class="icon"><i class="fas fa-search" aria-hidden="true"></i></div></div></div>
            </div>
            <div class="card card-outline card-primary">
                <div class="card-header"><h2 class="card-title">Vinculaciones corporativas</h2></div>
                <div class="card-body">
                    <div class="form-group col-md-4 px-0">
                        <label for="m365_estado">Estado</label>
                        <select id="m365_estado" class="form-control" style="width:100%">
                            <option value="">Todos los estados</option><option>Nuevo</option><option>Vinculado</option>
                            <option>Cambio detectado</option><option>Sin coincidencia</option><option>Conflicto</option>
                        </select>
                    </div>
                    <div class="table-responsive">
                    <table id="m365_tabla" class="table table-bordered table-striped dt-responsive" style="width:100%">
                        <thead><tr><th>ID usuario</th><th>Empleado</th><th>Correo registrado</th><th>Correo Microsoft 365</th>
                            <th>Object ID actual</th><th>Object ID encontrado</th><th>Estado</th><th>Acciones</th></tr></thead>
                        <tbody></tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div></section>
    </div>
    <?php require_once __DIR__ . '/../MainFooter/footer.php'; ?>
</div>
<!-- Solo la identidad Microsoft puede sustituirse; los campos son informativos. -->
<div class="modal fade" id="m365_modal" tabindex="-1" role="dialog" aria-labelledby="m365_modal_titulo" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document"><div class="modal-content">
        <div class="modal-header"><h5 id="m365_modal_titulo" class="modal-title">Revisar vinculación Microsoft 365</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button></div>
        <div class="modal-body">
            <dl class="row mb-0">
                <dt class="col-sm-4">Empleado</dt><dd class="col-sm-8" id="m365_ed_empleado"></dd>
                <dt class="col-sm-4">Correo registrado</dt><dd class="col-sm-8 text-break" id="m365_ed_correo"></dd>
                <dt class="col-sm-4">Cuenta Microsoft 365</dt><dd class="col-sm-8 text-break" id="m365_ed_cuenta"></dd>
                <dt class="col-sm-4">Object ID actual</dt><dd class="col-sm-8 text-break" id="m365_ed_actual"></dd>
                <dt class="col-sm-4">Object ID encontrado</dt><dd class="col-sm-8 text-break" id="m365_ed_encontrado"></dd>
            </dl>
            <p id="m365_ed_aviso" class="alert alert-info mt-2 mb-0"></p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            <button type="button" class="btn btn-primary" id="m365_ed_confirmar">Confirmar sustitución</button>
        </div>
    </div></div>
</div>
<?php require_once __DIR__ . '/../MainJS/JS.php'; ?>
<script src="sincronizacion.js"></script>
</body>
</html>
