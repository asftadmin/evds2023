<?php
require_once "../../config/conexion.php";
if (isset($_SESSION["user_id"])) {

    require_once "../../models/Firma.php";
    $firma = new Firma();
    $datos = $firma->get_by_user_id($_SESSION["user_id"]);

    // ── Construir src correcto ──
    $firma_src = null;
    if ($datos && !empty($datos["firma_base64"])) {
        $firma_src = $datos["firma_base64"];
        if (strpos($firma_src, 'data:image') === false) {
            $firma_src = 'data:image/png;base64,' . $firma_src;
        }
    }
?>
    <!DOCTYPE html>
    <html lang="es">

    <head>
        <meta charset="UTF-8">
        <title>Mi Firma</title>
        <link rel="stylesheet" href="../../public/dist/css/adminlte.min.css">
        <link rel="stylesheet" href="../../public/plugins/fontawesome-free/css/all.min.css">
    </head>

    <body>
        <div class="container mt-4">
            <div class="card card-outline card-primary" style="max-width:500px; margin:0 auto;">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-signature mr-2"></i>Mi Firma Registrada
                    </h3>
                </div>
                <div class="card-body text-center">
                    <?php if ($firma_src): ?>

                        <img src="<?= $firma_src ?>" alt="Firma" style="border:1px solid #dee2e6; border-radius:6px;
                            max-width:100%; background:#fff; padding:10px;">

                        <p class="text-muted mt-2" style="font-size:12px;">
                            <i class="fas fa-clock mr-1"></i>
                            Registrada el <?= date('d/m/Y H:i', strtotime($datos["fecha_registro"])) ?>
                        </p>

                        <?php if (!empty($datos["fecha_actualizacion"])): ?>
                            <p class="text-muted" style="font-size:12px;">
                                <i class="fas fa-edit mr-1"></i>
                                Actualizada el <?= date('d/m/Y H:i', strtotime($datos["fecha_actualizacion"])) ?>
                            </p>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="text-muted py-4">
                            <i class="fas fa-exclamation-circle fa-2x mb-2 d-block"></i>
                            No tiene firma registrada
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-center">
                    <small class="text-muted">
                        <i class="fas fa-user mr-1"></i>
                        Usuario ID: <?= $_SESSION["user_id"] ?>
                        &nbsp;·&nbsp;
                        Firma ID: <?= $datos["firma_id"] ?? '—' ?>
                    </small>
                </div>
            </div>
        </div>
    </body>

    </html>
<?php
} else {
    header("location:" . Conectar::ruta() . "index.php");
}
?>