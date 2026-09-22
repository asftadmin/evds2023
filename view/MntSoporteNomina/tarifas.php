<?php

require_once ('../../config/conexion.php');

if (isset($_SESSION['user_id'])) {
    // Año actual para construir el selector.
    $anioActual = (int) date('Y');
    ?>

<!DOCTYPE html>
<html lang="es">

<head>

    <?php require_once ('../MainHead/head.php'); ?>

    <title>Configuración Tarifas</title>


</head>

<body class="hold-transition sidebar-mini">

    <div class="wrapper">

        <?php require_once ('../MainNav/nav.php'); ?>
        <?php require_once ('../MainMenu/menu.php'); ?>

        <div class="content-wrapper">

            <?php // Encabezado de la vista ?>
            <section class="content-header">

                <div class="container-fluid">

                    <div class="row mb-2">

                        <div class="col-sm-6">
                            <h1>Configuración de Tarifas</h1>
                        </div>

                        <div class="col-sm-6">

                            <ol class="breadcrumb float-sm-right">

                                <li class="breadcrumb-item">
                                    <a href="../home/">Inicio</a>
                                </li>

                                <li class="breadcrumb-item">
                                    Soporte Nómina
                                </li>

                                <li class="breadcrumb-item active">
                                    Configuración Tarifas
                                </li>

                            </ol>

                        </div>

                    </div>

                </div>

            </section>

            <section class="content">

                <div class="container-fluid">

                    <?php // Formulario para crear o modificar tarifas ?>
                    <div class="card card-outline card-primary">

                        <div class="card-header">

                            <h3 class="card-title">
                                <i class="fas fa-dollar-sign mr-1"></i>
                                Configuración por periodo
                            </h3>

                        </div>

                        <div class="card-body">

                            <form id="form_tarifas">

                                <input type="hidden" id="tarifa_id" name="tarifa_id">

                                <div class="row">

                                    <div class="col-md-3">

                                        <div class="form-group">

                                            <label for="tarifa_mes">
                                                Mes
                                            </label>

                                            <select id="tarifa_mes" name="tarifa_mes" class="form-control select2"
                                                style="width: 100%;">

                                                <option value="">
                                                    Seleccione...
                                                </option>

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

                                    <div class="col-md-3">

                                        <div class="form-group">

                                            <label for="tarifa_anio">
                                                Año
                                            </label>

                                            <select id="tarifa_anio" name="tarifa_anio" class="form-control select2"
                                                style="width: 100%;">

                                                <option value="">
                                                    Seleccione...
                                                </option>

                                                <?php
                                                for (
                                                    $anio = $anioActual - 1;
                                                    $anio <= $anioActual + 3;
                                                    $anio++
                                                ) {
                                                    ?>

                                                <option value="<?php echo $anio; ?>">
                                                    <?php echo $anio; ?>
                                                </option>

                                                <?php
                                                }
                                                ?>

                                            </select>

                                        </div>

                                    </div>

                                    <div class="col-md-3">

                                        <div class="form-group">

                                            <label for="tarifa_alimentacion">
                                                Tarifa Alimentación
                                            </label>

                                            <div class="input-group">

                                                <div class="input-group-prepend">

                                                    <span class="input-group-text">
                                                        $
                                                    </span>

                                                </div>

                                                <input type="text" id="tarifa_alimentacion" name="tarifa_alimentacion"
                                                    class="form-control" placeholder="0">

                                            </div>

                                        </div>

                                    </div>

                                    <div class="col-md-3">

                                        <div class="form-group">

                                            <label for="tarifa_hospedaje">
                                                Tarifa Hospedaje
                                            </label>

                                            <div class="input-group">

                                                <div class="input-group-prepend">

                                                    <span class="input-group-text">
                                                        $
                                                    </span>

                                                </div>

                                                <input type="text" id="tarifa_hospedaje" name="tarifa_hospedaje"
                                                    class="form-control" placeholder="0">

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </form>

                        </div>

                        <div class="card-footer">

                            <div class="row">

                                <div class="col-md-12 text-right">

                                    <button type="button" id="btn_limpiar" class="btn btn-secondary mr-2">

                                        <i class="fas fa-broom mr-1"></i>
                                        Limpiar

                                    </button>

                                    <button type="button" id="btn_guardar" class="btn btn-primary">

                                        <i class="fas fa-save mr-1"></i>
                                        Guardar configuración

                                    </button>

                                </div>

                            </div>

                        </div>

                    </div>


                    <?php // Histórico de tarifas configuradas ?>
                    <div class="card card-outline card-secondary">

                        <div class="card-header">

                            <h3 class="card-title">

                                <i class="fas fa-history mr-1"></i>
                                Histórico de tarifas

                            </h3>

                        </div>

                        <div class="card-body">

                            <div class="table-responsive">

                                <table id="tabla_tarifas" class="table table-bordered table-striped table-hover">

                                    <thead>

                                        <tr>

                                            <th>Periodo</th>
                                            <th>Tarifa Alimentación</th>
                                            <th>Tarifa Hospedaje</th>
                                            <th>Estado</th>
                                            <th class="text-center">
                                                Acción
                                            </th>

                                        </tr>

                                    </thead>

                                    <tbody>
                                    </tbody>

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


    <script src="tarifas.js"></script>

</body>

</html>

<?php
} else {
    header('Location:' . Conectar::ruta() . 'index.php');
}

?>