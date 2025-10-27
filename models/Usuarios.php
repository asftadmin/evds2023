<?php

class Usuario extends Conectar
{


    public function login()
    {

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

                    header("Location:" . conectar::ruta() . "view/evaluacion/evaluacion.php");
                    exit();
                } else {
                    header("Location:" . conectar::ruta() . "index.php?m=1");
                    exit();
                }
            }
        }
    }
}
