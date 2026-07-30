<?php

class Usuario extends Conectar {


    public function login() {

        $conectar = parent::conexion();
        parent::set_names();
        if (isset($_POST["enviar"])) {
            $usuario = $_POST["user_nick"];
            $pass = $_POST["user_pass"];

            if (empty($usuario) and empty($pass)) {
                header("Location:" . conectar::ruta() . "index.php?m=2");
                exit();
            } else {
                $sql = "SELECT * FROM empleados e INNER JOIN usuarios u ON e.user_empl = u.user_id WHERE u.user_nick = ? and u.user_pass = ?";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $usuario);
                $stmt->bindValue(2, $pass);
                $stmt->execute();
                $result = $stmt->fetch();
                if (is_array($result) && count($result) > 0) {

                    $_SESSION["user_id"] = $result["user_id"];
                    $_SESSION["user_nick"] = $result["user_nick"];
                    $_SESSION["id_empl"] = $result["id_empl"];
                    $_SESSION["user_empl"] = $result["user_empl"];
                    $_SESSION["nomb_empl"] = $result["nomb_empl"];
                    $_SESSION["user_rol"] = $result["user_rol"];

                    // Conserva el rol principal y registra por separado la
                    // capacidad de actuar como jefe. Esto permite que un jefe
                    // con rol de Contabilidad, Gerencia o RR. HH. no pierda
                    // los menús propios de su rol al iniciar sesión.
                    $sql_jefe = "SELECT COUNT(*)
                        FROM empleado_jefe
                        WHERE jefe_id = ?
                          AND ej_estado = 1";
                    $stmt_jefe = $conectar->prepare($sql_jefe);
                    $stmt_jefe->bindValue(1, $result["id_empl"], PDO::PARAM_INT);
                    $stmt_jefe->execute();
                    $_SESSION["es_jefe"] = ((int)$stmt_jefe->fetchColumn() > 0) ? 1 : 0;

                    header("Location:" . conectar::ruta() . "view/home/home2.php");
                    exit();
                } else {
                    header("Location:" . conectar::ruta() . "index.php?m=1");
                    exit();
                }
            }
        }
    }
}
