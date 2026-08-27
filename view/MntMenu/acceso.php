<?php

require_once "../../config/conexion.php";

if (isset($_SESSION["user_id"])) {

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <?php require_once("../MainHead/head.php") ?>

    <!-- Select2 -->
    <link rel="stylesheet" href="../../public/plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="../../public/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">

    <title>ADMINISTRACIÓN DE MENÚS</title>
</head>

<body class="hold-transition sidebar-mini layout-fixed layout-footer-fixed">

    <div class="wrapper">

        <?php require_once("../MainNav/nav.php") ?>
        <?php require_once("../MainMenu/menu.php") ?>

        <div class="content-wrapper">

            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">

                        <div class="col-sm-6">
                            <h1>Administración de Menús</h1>
                        </div>

                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item">
                                    <a href="#">Inicio</a>
                                </li>
                                <li class="breadcrumb-item active">Menús</li>
                            </ol>
                        </div>

                    </div>
                </div>
            </section>


            <section class="content">

                <!-- Formulario -->
                <div class="card card-outline card-info">

                    <div class="card-header" id="titulo_form_menu">
                        <h3 class="card-title">
                            <i class="fas fa-plus-circle mr-2"></i>
                            Registrar Menú
                        </h3>
                    </div>

                    <form id="form_menu" autocomplete="off">

                        <div class="card-body">

                            <input type="hidden" name="menu_id" id="menu_id">

                            <div class="row">

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="menu_nomb">
                                            Nombre del Menú
                                            <span class="text-danger">*</span>
                                        </label>

                                        <input type="text" class="form-control" name="menu_nomb" id="menu_nomb"
                                            placeholder="Ej. Administración de Usuarios" required>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="menu_ident">
                                            Identificador
                                            <span class="text-danger">*</span>
                                        </label>

                                        <input type="text" class="form-control" name="menu_ident" id="menu_ident"
                                            placeholder="Ej. usuarios" required>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="menu_icon">
                                            Icono
                                            <span class="text-danger">*</span>
                                        </label>

                                        <input type="text" class="form-control" name="menu_icon" id="menu_icon"
                                            placeholder="fas fa-users-cog" required>
                                    </div>
                                </div>

                            </div>


                            <div class="row">

                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label for="menu_ruta">
                                            Ruta
                                            <span class="text-danger">*</span>
                                        </label>

                                        <input type="text" class="form-control" name="menu_ruta" id="menu_ruta"
                                            placeholder="../MntRol/" required>

                                        <small class="text-muted">
                                            Ruta utilizada actualmente para construir el enlace del menú.
                                        </small>
                                    </div>
                                </div>


                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="menu_grupo">
                                            Grupo
                                        </label>

                                        <select class="form-control select2bs4" name="menu_grupo" id="menu_grupo"
                                            style="width:100%;">
                                            <option value="">Sin grupo</option>
                                        </select>

                                        <small class="text-muted">
                                            Permite organizar visualmente el menú lateral.
                                        </small>
                                    </div>
                                </div>


                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="menu_orden">
                                            Orden
                                            <span class="text-danger">*</span>
                                        </label>

                                        <input type="number" class="form-control" name="menu_orden" id="menu_orden"
                                            min="0" step="1" placeholder="1" required>
                                    </div>
                                </div>


                                <div class="col-md-2" id="contenedor_estado_menu" style="display:none;">
                                    <div class="form-group">
                                        <label for="menu_esta">Estado</label>

                                        <select class="form-control select2bs4" name="menu_esta" id="menu_esta"
                                            style="width:100%;">
                                            <option value="1">Activo</option>
                                            <option value="0">Inactivo</option>
                                        </select>
                                    </div>
                                </div>

                            </div>

                        </div>


                        <div class="card-footer">
                            <div class="d-flex justify-content-end">

                                <button type="button" id="btn_limpiar_menu" class="btn btn-default">
                                    <i class="fas fa-undo mr-1"></i>
                                    Limpiar
                                </button>

                                <button type="submit" id="btn_guardar_menu" class="btn btn-info ml-2">
                                    <i class="fas fa-save mr-1"></i>
                                    Registrar Menú
                                </button>

                                <button type="button" id="btn_cancelar_edicion" class="btn btn-danger ml-2"
                                    style="display:none;">
                                    <i class="fas fa-times mr-1"></i>
                                    Cancelar Edición
                                </button>

                            </div>
                        </div>

                    </form>

                </div>


                <!-- Menús registrados -->
                <div class="card card-outline card-dark">

                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-bars mr-2"></i>
                            Menús Registrados
                        </h3>
                    </div>

                    <div class="card-body">

                        <div class="table-responsive">

                            <table id="menus_data" class="table table-hover table-striped table-bordered"
                                style="width:100%;">

                                <thead class="thead-light">
                                    <tr>
                                        <th>Código</th>
                                        <th>Menú</th>
                                        <th>Grupo</th>
                                        <th>Ruta</th>
                                        <th>Identificador</th>
                                        <th>Orden</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>

                                <tbody></tbody>

                            </table>

                        </div>

                    </div>

                </div>

            </section>

        </div>


        <?php require_once("../MainFooter/footer.php") ?>

        <aside class="control-sidebar control-sidebar-dark"></aside>

    </div>


    <?php require_once("../MainJS/JS.php") ?>

    <!-- Select2 -->
    <script src="../../public/plugins/select2/js/select2.full.min.js"></script>

    <!-- AJAX del módulo -->
    <script type="text/javascript" src="acceso.js"></script>

</body>

</html>

<?php

} else {
    header("location:" . Conectar::ruta() . "index.php");
    exit();
}

?>