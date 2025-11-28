<nav class="main-header navbar navbar-expand navbar-white navbar-light">

    <!-- Right navbar links -->
    <ul class="navbar-nav">


        <input type="hidden" id="user_idx" value="<?php echo $_SESSION["user_id"] ?>">
        <input type="hidden" id="empl_idx" value="<?php echo $_SESSION["id_empl"] ?>">
        <input type="hidden" id="rol_idx" value="<?php echo $_SESSION["user_rol"] ?>">

        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>

        <li class="nav-item">
            <div class="dropdown dropdown-typical">
                <span class="fas fa-user-alt mr-2" style="color:#17a2b8"></span>
                <span class=""> <?php echo $_SESSION["nomb_empl"] ?></span>
            </div>
        </li>

    </ul>
    <ul class="navbar-nav ml-auto">
        <!-- User Dropdown Menu -->
        <li class="nav-item dropdown">

            <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
                <span class="fas fa-user-circle" style="font-size: 30px;">
                </span>
            </a>

            <div></div>
            <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">


                <!-- Menu Body -->
                <li class="media">
                    <div class="media-body">
                        <div class="row text-center">
                            <div class="col-12">
                                <button id="btnFirma" class="btn btn-default btn-block">
                                    <i class="fas fa-signature"></i> Subir Firma
                                </button>
                            </div>
                        </div>
                    </div>
                </li>

                <!-- Menu Footer-->
                <li class="media">
                    <div class="media-body">
                        <a href="../logout/logout.php" class="btn btn-danger btn-block">
                            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                        </a>
                    </div>
                </li>

            </ul>
        </li>

    </ul>

</nav>