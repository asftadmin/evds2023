<?php

require_once("config/conexion.php");
if (isset($_POST["enviar"]) and $_POST["enviar"] == "si") {

    require_once("models/Usuarios.php");


    $usuario = new Usuario();
    $usuario->login();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="public/img/icono.png">
    <title>ASFALTART</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="public/plugins/fontawesome-free/css/all.min.css">
    <!-- icheck bootstrap -->
    <link rel="stylesheet" href="public/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="public/dist/css/adminlte.min.css">
</head>

<body class="hold-transition login-page">
    <div class="login-box">
        <!-- /.login-logo -->
        <div class="card card-outline card-info">
            <div class="card-header text-center">
                <img src="public/img/logo_login.svg" alt="ASFALTAR S.A.S">
            </div>
            <div class="card-body">

                <h5 class="text-center mb-4">Evaluacion Desempeño</h5>


                <form action="" method="post" id="loginForm">



                    <?php
                    if (isset($_GET["m"])) {
                        switch ($_GET["m"]) {
                            case "1":
                    ?>

                                <div class="alert alert-danger alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                                    <h6><i class="icon fas fa-ban"></i> Error de autenticación:</h6>
                                    Usuario y/o contraseña incorrectos. Por favor, inténtalo nuevamente
                                </div>


                            <?php
                                break;


                            case "2":

                            ?>
                                <div class="alert alert-danger alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                                    <h6><i class="icon fas fa-ban"></i> Falta Informacion:</h6>
                                    Los campos se encuentran vacios. Por favor, inténtalo nuevamente
                                </div>

                            <?php
                                break;

                            case "3";
                            ?>

                    <?php


                                break;
                        }
                    }
                    ?>

                    <div class="input-group mb-3">
                        <input type="text" id="user_nick" name="user_nick" class="form-control" placeholder="Usuario">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-envelope"></span>
                            </div>
                        </div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="password" id="user_pass" name="user_pass" class="form-control"
                            placeholder="Contraseña">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">

                        <!-- /.col -->
                        <div class="col-12">
                            <input type="hidden" name="enviar" value="si">
                            <button type="submit" class="btn btn-info btn-block">Ingresar</button>
                        </div>
                        <!-- /.col -->
                    </div>
                </form>




            </div>
            <!-- /.card-body -->
        </div>
        <!-- /.card -->
    </div>
    <!-- /.login-box -->

    <!-- jQuery -->
    <script src="public/plugins/jquery/jquery.min.js"></script>
    <!-- Bootstrap 4 -->
    <script src="public/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE App -->
    <script src="public/dist/js/adminlte.min.js"></script>
</body>

</html>