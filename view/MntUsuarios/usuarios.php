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



    <title>ADMINISTRACIÓN DE USUARIOS</title>

</head>


<body class="hold-transition sidebar-mini layout-fixed layout-footer-fixed">

    <!-- Site wrapper -->
    <div class="wrapper">


        <!-- Navbar -->
        <?php require_once("../MainNav/nav.php") ?>
        <!-- /.navbar -->


        <!-- Main Sidebar -->
        <?php require_once("../MainMenu/menu.php") ?>


        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">


            <!-- ================================================= -->
            <!-- CONTENT HEADER                                    -->
            <!-- ================================================= -->

            <section class="content-header">

                <div class="container-fluid">

                    <div class="row mb-2">


                        <div class="col-sm-6">

                            <h1>
                                Administración de Usuarios
                            </h1>

                        </div>


                        <div class="col-sm-6">

                            <ol class="breadcrumb float-sm-right">

                                <li class="breadcrumb-item">

                                    <a href="#">
                                        Inicio
                                    </a>

                                </li>

                                <li class="breadcrumb-item active">
                                    Usuarios
                                </li>

                            </ol>

                        </div>


                    </div>

                </div>

            </section>


            <!-- ================================================= -->
            <!-- MAIN CONTENT                                      -->
            <!-- ================================================= -->

            <section class="content">


                <!-- ================================================= -->
                <!-- CARD CREAR USUARIO                                -->
                <!-- ================================================= -->

                <div class="card card-outline card-info">


                    <div class="card-header" id="titulo_form_usuario">

                        <h3 class="card-title">

                            <i class="fas fa-user-plus mr-2"></i>

                            Crear Usuario

                        </h3>

                    </div>


                    <form id="form_usuario" autocomplete="off">


                        <div class="card-body">


                            <!-- ========================================= -->
                            <!-- EMPLEADO Y ROL                            -->
                            <!-- ========================================= -->

                            <div class="row">
                                <input type="hidden" name="user_id" id="user_id">

                                <!-- EMPLEADO -->
                                <div class="col-md-6 d-flex flex-column mb-3">

                                    <label class="section-subheader">

                                        <i class="fas fa-user mr-1"></i>

                                        Empleado

                                        <span class="text-danger">
                                            *
                                        </span>

                                    </label>


                                    <select class="form-control select2bs4" style="width: 100%;" name="empleado_id"
                                        id="empleado_id">

                                        <option value=""></option>

                                    </select>


                                    <small class="text-muted mt-1">

                                        Solo se muestran empleados activos
                                        que todavía no tienen usuario asignado.

                                    </small>

                                </div>


                                <!-- ROL -->
                                <div class="col-md-6 d-flex flex-column mb-3">

                                    <label class="section-subheader">

                                        <i class="fas fa-user-tag mr-1"></i>

                                        Rol

                                        <span class="text-danger">
                                            *
                                        </span>

                                    </label>


                                    <select class="form-control select2bs4" style="width: 100%;" name="user_rol"
                                        id="user_rol">

                                        <option value="">
                                            Seleccione un rol
                                        </option>

                                    </select>


                                    <small class="text-muted mt-1">

                                        El rol determina los permisos
                                        disponibles para el usuario.

                                    </small>

                                </div>


                            </div>


                            <!-- ========================================= -->
                            <!-- USUARIO Y CONTRASEÑA                      -->
                            <!-- ========================================= -->

                            <div class="row">


                                <!-- USUARIO -->
                                <div class="col-md-4 d-flex flex-column mb-3">

                                    <label class="section-subheader">

                                        <i class="fas fa-user-circle mr-1"></i>

                                        Nombre de Usuario

                                        <span class="text-danger">
                                            *
                                        </span>

                                    </label>


                                    <input type="text" class="form-control" name="user_nick" id="user_nick"
                                        autocomplete="off" placeholder="Ingrese nombre de usuario">


                                    <small class="text-muted mt-1">

                                        Mínimo 4 caracteres.

                                    </small>

                                </div>


                                <!-- CONTRASEÑA -->
                                <div class="col-md-4 d-flex flex-column mb-3">

                                    <label class="section-subheader">

                                        <i class="fas fa-lock mr-1"></i>

                                        Contraseña

                                        <span class="text-danger">
                                            *
                                        </span>

                                    </label>


                                    <div class="input-group">


                                        <input type="password" class="form-control" name="user_pass" id="user_pass"
                                            autocomplete="new-password" placeholder="Ingrese contraseña">


                                        <div class="input-group-append">

                                            <button type="button" class="btn btn-default" id="btn_ver_password">

                                                <i class="fas fa-eye"></i>

                                            </button>

                                        </div>


                                    </div>


                                    <small class="text-muted mt-1">

                                        Mínimo 8 caracteres.

                                    </small>

                                </div>


                                <!-- CONFIRMAR CONTRASEÑA -->
                                <div class="col-md-4 d-flex flex-column mb-3">

                                    <label class="section-subheader">

                                        <i class="fas fa-lock mr-1"></i>

                                        Confirmar Contraseña

                                        <span class="text-danger">
                                            *
                                        </span>

                                    </label>


                                    <input type="password" class="form-control" name="user_pass_confirmar"
                                        id="user_pass_confirmar" autocomplete="new-password"
                                        placeholder="Repita la contraseña">

                                </div>


                            </div>


                        </div>


                        <!-- ============================================= -->
                        <!-- FOOTER FORMULARIO                              -->
                        <!-- ============================================= -->

                        <div class="card-footer">


                            <div class="d-flex justify-content-end">


                                <!-- LIMPIAR -->
                                <button type="button" id="btn_limpiar" class="btn btn-default">

                                    <i class="fas fa-undo mr-1"></i>

                                    Limpiar

                                </button>


                                <!-- GUARDAR -->
                                <button type="submit" id="btn_guardar" class="btn btn-info ml-2">

                                    <i class="fas fa-save mr-1"></i>

                                    Crear Usuario

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


                <!-- ================================================= -->
                <!-- CARD USUARIOS REGISTRADOS                         -->
                <!-- ================================================= -->

                <div class="card card-outline card-dark">


                    <div class="card-header">

                        <h3 class="card-title">

                            <i class="fas fa-users mr-2"></i>

                            Usuarios Registrados

                        </h3>

                    </div>


                    <div class="card-body">


                        <div class="table-responsive">


                            <table id="usuarios_data" class="table table-hover table-striped table-bordered"
                                style="width:100%;">


                                <thead class="thead-light">

                                    <tr>


                                        <th>

                                            <i class="fas fa-id-card mr-1"></i>

                                            Documento

                                        </th>


                                        <th>

                                            <i class="fas fa-user mr-1"></i>

                                            Empleado

                                        </th>


                                        <th>

                                            <i class="fas fa-user-circle mr-1"></i>

                                            Usuario

                                        </th>


                                        <th>

                                            <i class="fas fa-user-tag mr-1"></i>

                                            Rol

                                        </th>


                                        <th class="text-center">

                                            Acciones

                                        </th>


                                    </tr>

                                </thead>


                                <tbody>

                                </tbody>


                            </table>


                        </div>


                    </div>


                </div>


            </section>
            <!-- /.content -->


        </div>
        <!-- /.content-wrapper -->


        <!-- ===================================================== -->
        <!-- FOOTER                                                -->
        <!-- ===================================================== -->

        <?php require_once("../MainFooter/footer.php") ?>


        <!-- Control Sidebar -->
        <aside class="control-sidebar control-sidebar-dark">

            <!-- Control sidebar content goes here -->

        </aside>
        <!-- /.control-sidebar -->


    </div>
    <!-- ./wrapper -->


    <!-- ========================================================= -->
    <!-- JS GENERAL DEL PROYECTO                                  -->
    <!-- ========================================================= -->

    <?php require_once("../MainJS/JS.php") ?>


    <!-- ========================================================= -->
    <!-- JS DEL MÓDULO                                            -->
    <!-- ========================================================= -->

    <script type="text/javascript" src="usuarios.js">
    </script>


</body>

</html>


<?php

} else {

    header(
        "location:"
            . Conectar::ruta()
            . "index.php"
    );
}

?>