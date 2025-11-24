<nav class="main-header navbar navbar-expand navbar-white navbar-light">


    <ul class="navbar-nav">

        <input type="hidden" id="user_idx" value="<?php echo $_SESSION["user_id"] ?>">
        <input type="hidden" id="empl_idx" value="<?php echo $_SESSION["id_empl"] ?>">
        <input type="hidden" id="rol_idx" value="<?php echo $_SESSION["user_rol"] ?>">

        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>

        <li class="nav-item">
            <div class="dropdown dropdown-typical"><span class="fas fa-user-alt mr-2" style="color:#17a2b8"></span>
                <span class="lblcontactonomx"> <?php echo $_SESSION["nomb_empl"] ?></span>
            </div>
        </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
        <!-- Navbar Search -->
        <li class="nav-item">
            <a href="../logout/logout.php"><span class="fas fa-sign-out-alt"></span></a>
            </a>
        </li>
    </ul>
</nav>