<?php
require_once "../../config/conexion.php";
if (isset($_SESSION["user_id"])) {

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php require_once("../MainHead/head.php")?>
    <title>EVALUACIÓN DESEMPEÑO</title>


</head>

<body class="hold-transition sidebar-mini">
    <!-- Site wrapper -->
    <div class="wrapper">
        <!-- Navbar -->
        <?php require_once("../MainNav/nav.php")?>
        <!-- /.navbar -->

        <?php require_once("../MainMenu/menu.php")?>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>Empleados</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="#">Inicio</a></li>
                                <li class="breadcrumb-item active">Empleados</li>
                            </ol>
                        </div>
                    </div>
                </div><!-- /.container-fluid -->
            </section>

            <!-- Main content -->
            <section class="content">

                <!-- Default box -->
                <div class="card">
                    <div class="card-header">
                        <button type="button" id="btnNuevoEmple" class="btn btn-info">Nuevo Registro</button>
                        <button type="button" id="btnBuscEmple" class="btn btn-info">Buscar Empleado</button>
                        <button type="button" id="btnBuscEmpleSiesa" class="btn btn-info">Empleados Siesa</button>
                    </div>
                    <div class="card-body p-5">



                        <form class="form-horizontal" method="post" id="form_empleado">

                            <div class="row">
                                <div class="col-sm-6">

                                    <input type="hidden" name="txt_codigo_empleado" id="txt_codigo_empleado">

                                    <span class="form-group row" id="selectTipoDocumentoUpdate">
                                        <label for="txt_tipo_documento_empl" class="col-sm-4 col-form-label">Tipo
                                            Documento:</label>
                                        <div class="col-sm-8" style="display: inline-block; ">
                                            <select class="form-control select2" name="txt_tipo_documento_empl"
                                                id="txt_tipo_documento_empl" required>

                                            </select>
                                        </div>
                                    </span>

                                    <div class="form-group row">
                                        <label for="txt_numero_documento" class="col-sm-4 col-form-label">Número
                                            Documento:
                                        </label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" name="txt_numero_documento"
                                                id="txt_numero_documento" placeholder="Numero de identificación">
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label for="txt_nombre_empleado" class="col-sm-4 col-form-label">Nombre
                                            Completo:
                                        </label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" name="txt_nombre_empleado"
                                                id="txt_nombre_empleado" placeholder="Nombre completo del Empleado">
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label for="txt_telefono_empleado" class="col-sm-4 col-form-label">Telefono:
                                        </label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" name="txt_telefono_empleado"
                                                id="txt_telefono_empleado" placeholder="Telefono y/o Celular">
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label for="txt_direccion_empleado" class="col-sm-4 col-form-label">Dirección:
                                        </label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" name="txt_direccion_empleado"
                                                id="txt_direccion_empleado" placeholder="Dirección de residencia">
                                        </div>
                                    </div>

                                    <span class="form-group row" id="selectCargEmpleadoUpdate">
                                        <label for="select_cargo_empleado" class="col-sm-4 col-form-label">Cargo
                                            Empleado:</label>
                                        <div class="col-sm-8" style="display: inline-block; ">
                                            <select class="form-control select2" style="width: 100%;"
                                                name="select_cargo_empleado" id="select_cargo_empleado" required>

                                            </select>
                                        </div>
                                    </span>

                                    <div class="form-group row">
                                        <label for="txt_fecha_ingreso" class="col-sm-4 col-form-label">Fecha Ingreso:
                                        </label>
                                        <div class="input-group date col-sm-8" id="reservationdate"
                                            data-target-input="nearest">
                                            <input type="text" class="form-control datetimepicker-input"
                                                data-target="#reservationdate" name="txt_fecha_ingreso"
                                                id="txt_fecha_ingreso" />
                                            <div class="input-group-append" data-target="#reservationdate"
                                                data-toggle="datetimepicker">
                                                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                            </div>
                                        </div>

                                    </div>

                                </div>
                                <div class="col-md-6">


                                    <div class="form-group row">
                                        <label for="txt_fecha_nacimiento" class="col-sm-4 col-form-label">Fecha
                                            Nacimiento:
                                        </label>
                                        <div class="input-group date col-sm-8" id="reservationdate"
                                            data-target-input="nearest">
                                            <input type="text" class="form-control datetimepicker-input"
                                                data-target="#reservationdate" name="txt_fecha_nacimiento"
                                                id="txt_fecha_nacimiento" />
                                            <div class="input-group-append" data-target="#reservationdate"
                                                data-toggle="datetimepicker">
                                                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                            </div>
                                        </div>

                                    </div>

                                    <span class="form-group row" id="selectGenero">
                                        <label for="select_genero" class="col-sm-4 col-form-label">Genero: </label>
                                        <div class="col-sm-8" style="display: inline-block; ">
                                            <select class="form-control select2" style="width: 100%;"
                                                name="select_genero" id="select_genero" required>

                                            </select>
                                        </div>
                                    </span>

                                    <span class="form-group row" id="selectNivEdu">
                                        <label for="select_nivel_educativo" class="col-sm-4 col-form-label">Nivel
                                            Educativo:
                                        </label>
                                        <div class="col-sm-8" style="display: inline-block; ">
                                            <select class="form-control select2" style="width: 100%;"
                                                name="select_nivel_educativo" id="select_nivel_educativo" required>

                                            </select>
                                        </div>
                                    </span>

                                    <div class="form-group row">
                                        <label for="txt_profesion" class="col-sm-4 col-form-label">Profesion:
                                        </label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" name="txt_profesion"
                                                id="txt_profesion" placeholder="Profesion del Empleado">
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label for="txt_rh" class="col-sm-4 col-form-label">Grupo Sanguineo:
                                        </label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" name="txt_rh" id="txt_rh"
                                                placeholder="Digite el Grupo Sanguineo">
                                        </div>
                                    </div>

                                    <span class="form-group row" id="selectEstadoEmpleadoUpdate">
                                        <label for="select_esta_empl" class="col-sm-4 col-form-label">Estado:</label>
                                        <div class="col-sm-8" style="display: inline-block; ">
                                            <select class="form-control select2bs4" name="select_esta_empl"
                                                id="select_esta_empl" required>
                                                <option value="1">Activo</option>
                                                <option value="0">Inactivo</option>
                                            </select>
                                        </div>
                                    </span>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <button type="reset" class="btn btn-secondary btn-block"
                                                data-dismiss="modal">Salir</button>
                                        </div>

                                        <div class="col-md-6">
                                            <button type="submit" name="action" value="add"
                                                class="btn btn-info btn-block">Guardar</button>

                                        </div>


                                    </div>
                                </div> 
                            </div>


                        </form>

                    </div>
                    <!-- /.card-body -->

                    <!-- /.card-footer-->
                </div>
                <!-- /.card -->

            </section>
            <!-- /.content -->
        </div>
        <!-- /.content-wrapper -->

        <?php require_once("../MainFooter/footer.php")?>

        <!-- Control Sidebar -->
        <aside class="control-sidebar control-sidebar-dark">
            <!-- Control sidebar content goes here -->
        </aside>
        <!-- /.control-sidebar -->
    </div>
    <!-- ./wrapper -->


    <?php require_once("mantenimiento_empleado.php")?>
s
    <?php require_once("update_empleado.php")?>

    <?php require_once("../MainJS/JS.php")?>
    <script type="text/javascript" src="empleados.js"></script>

</body>

</html>
<?php
} else {
    header("location:" . Conectar::ruta() . "index.php");
}
?>