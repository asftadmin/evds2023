<?php

class Usuario extends Conectar {


    public function login() {
        $conectar = parent::conexion();
        parent::set_names();

        if (isset($_POST["enviar"])) {

            $usuario = trim($_POST["user_nick"]);
            $pass = $_POST["user_pass"];

            // Validar que usuario y contraseña hayan sido diligenciados.
            if (empty($usuario) || empty($pass)) {
                header("Location:" . conectar::ruta() . "index.php?m=2");
                exit();
            }

            // Consultar el usuario únicamente por nombre de usuario.
            // La contraseña se valida posteriormente en PHP para soportar
            // contraseñas antiguas y nuevas con password_hash().
            $sql = "SELECT *
                FROM empleados e
                INNER JOIN usuarios u
                    ON e.user_empl = u.user_id
                WHERE u.user_nick = ?";

            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $usuario);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            // Usuario no encontrado.
            if (!is_array($result) || count($result) === 0) {
                header("Location:" . conectar::ruta() . "index.php?m=1");
                exit();
            }

            $password_guardado = (string)$result["user_pass"];
            $password_correcto = false;

            // Identificar si la contraseña almacenada ya fue creada
            // mediante password_hash().
            $info_password = password_get_info($password_guardado);

            if ($info_password["algo"] != 0) {

                // Usuario con contraseña ya migrada.
                $password_correcto = password_verify(
                    $pass,
                    $password_guardado
                );

                // Actualizar el hash automáticamente si PHP recomienda
                // un nuevo algoritmo o configuración.
                if (
                    $password_correcto &&
                    password_needs_rehash(
                        $password_guardado,
                        PASSWORD_DEFAULT
                    )
                ) {
                    $nuevo_hash = password_hash(
                        $pass,
                        PASSWORD_DEFAULT
                    );

                    $sql_update = "UPDATE usuarios
                               SET user_pass = ?
                               WHERE user_id = ?";

                    $stmt_update = $conectar->prepare($sql_update);
                    $stmt_update->bindValue(1, $nuevo_hash);
                    $stmt_update->bindValue(
                        2,
                        $result["user_id"],
                        PDO::PARAM_INT
                    );
                    $stmt_update->execute();
                }
            } else {

                // Contraseña antigua almacenada en texto plano.
                if (
                    hash_equals(
                        $password_guardado,
                        (string)$pass
                    )
                ) {
                    $password_correcto = true;

                    // Migrar automáticamente la contraseña antigua
                    // después de un inicio de sesión correcto.
                    $nuevo_hash = password_hash(
                        $pass,
                        PASSWORD_DEFAULT
                    );

                    $sql_update = "UPDATE usuarios
                               SET user_pass = ?
                               WHERE user_id = ?";

                    $stmt_update = $conectar->prepare($sql_update);
                    $stmt_update->bindValue(1, $nuevo_hash);
                    $stmt_update->bindValue(
                        2,
                        $result["user_id"],
                        PDO::PARAM_INT
                    );
                    $stmt_update->execute();
                }
            }

            // Contraseña incorrecta.
            if (!$password_correcto) {
                header("Location:" . conectar::ruta() . "index.php?m=1");
                exit();
            }

            // Crear las variables de sesión utilizadas por la plataforma.
            $_SESSION["user_id"] = $result["user_id"];
            $_SESSION["user_nick"] = $result["user_nick"];
            $_SESSION["id_empl"] = $result["id_empl"];
            $_SESSION["user_empl"] = $result["user_empl"];
            $_SESSION["nomb_empl"] = $result["nomb_empl"];
            $_SESSION["user_rol"] = $result["user_rol"];

            // Validar si el empleado también tiene capacidad de actuar
            // como jefe inmediato, independientemente de su rol principal.
            $sql_jefe = "SELECT COUNT(*)
                     FROM empleado_jefe
                     WHERE jefe_id = ?
                       AND ej_estado = 1";

            $stmt_jefe = $conectar->prepare($sql_jefe);
            $stmt_jefe->bindValue(
                1,
                $result["id_empl"],
                PDO::PARAM_INT
            );
            $stmt_jefe->execute();

            $_SESSION["es_jefe"] =
                ((int)$stmt_jefe->fetchColumn() > 0) ? 1 : 0;

            // Login correcto.
            header(
                "Location:" .
                    conectar::ruta() .
                    "view/home/home2.php"
            );
            exit();
        }
    }

    /**
     * ============================================================
     * LISTAR EMPLEADOS SIN USUARIO
     * ============================================================
     *
     * Retorna empleados activos que todavía no tienen un usuario
     * asociado mediante empleados.user_empl.
     *
     * Esta información alimentará el Select2 de la vista.
     */
    public function listar_empleados_sin_usuario() {

        $conectar = parent::Conexion();
        parent::set_names();

        $sql = "SELECT
                    id_empl,
                    cedu_empl,
                    nomb_empl
                FROM empleados
                WHERE esta_empl = 1
                  AND user_empl IS NULL
                ORDER BY nomb_empl ASC";

        $query = $conectar->prepare($sql);

        $query->execute();

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * ============================================================
     * LISTAR ROLES
     * ============================================================
     *
     * Retorna únicamente los roles activos registrados en la
     * tabla rol.
     *
     * Los IDs de los roles no se queman en PHP ni JavaScript.
     */
    public function listar_roles() {

        $conectar = parent::Conexion();
        parent::set_names();

        $sql = "SELECT
                    rol_id,
                    rol_nomb
                FROM rol
                WHERE rol_esta = 1
                ORDER BY rol_nomb ASC";

        $query = $conectar->prepare($sql);

        $query->execute();

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * ============================================================
     * LISTAR USUARIOS
     * ============================================================
     *
     * Consulta los usuarios registrados junto con la información
     * del empleado y el nombre del rol asignado.
     *
     * Esta información será utilizada por DataTables.
     */
    public function mostrar_usuarios() {

        $conectar = parent::Conexion();
        parent::set_names();

        $sql = "SELECT
                    u.user_id,
                    u.user_nick,
                    u.user_rol,
                    e.id_empl,
                    e.cedu_empl,
                    e.nomb_empl,
                    r.rol_nomb
                FROM usuarios u
                LEFT JOIN empleados e
                    ON e.user_empl = u.user_id
                LEFT JOIN rol r
                    ON r.rol_id = u.user_rol
                ORDER BY e.nomb_empl ASC NULLS LAST";

        $query = $conectar->prepare($sql);

        $query->execute();

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * ============================================================
     * MOSTRAR USUARIO POR ID
     * ============================================================
     *
     * Retorna la información de un usuario específico.
     *
     * Este método permitirá posteriormente editar el rol o
     * administrar la cuenta.
     */
    public function mostrar_usuario($user_id) {

        $conectar = parent::Conexion();
        parent::set_names();

        $sql = "SELECT
                    u.user_id,
                    u.user_nick,
                    u.user_rol,
                    e.id_empl,
                    e.cedu_empl,
                    e.nomb_empl,
                    r.rol_nomb
                FROM usuarios u
                LEFT JOIN empleados e
                    ON e.user_empl = u.user_id
                LEFT JOIN rol r
                    ON r.rol_id = u.user_rol
                WHERE u.user_id = ?";

        $query = $conectar->prepare($sql);

        $query->bindValue(
            1,
            $user_id,
            PDO::PARAM_INT
        );

        $query->execute();

        return $query->fetch(PDO::FETCH_ASSOC);
    }


    /**
     * ============================================================
     * VALIDAR NOMBRE DE USUARIO
     * ============================================================
     *
     * Comprueba si el nombre de usuario ya se encuentra registrado.
     *
     * La validación se realiza ignorando mayúsculas y minúsculas.
     */
    public function validar_usuario_existente($user_nick) {

        $conectar = parent::Conexion();
        parent::set_names();

        $sql = "SELECT
                    user_id
                FROM usuarios
                WHERE LOWER(TRIM(user_nick)) = LOWER(TRIM(?))
                LIMIT 1";

        $query = $conectar->prepare($sql);

        $query->bindValue(
            1,
            $user_nick,
            PDO::PARAM_STR
        );

        $query->execute();

        return $query->fetch(PDO::FETCH_ASSOC);
    }


    /**
     * ============================================================
     * CREAR USUARIO
     * ============================================================
     *
     * Realiza las siguientes operaciones:
     *
     * 1. Valida que el empleado exista y esté activo.
     * 2. Valida que el empleado no tenga usuario.
     * 3. Valida que el nombre de usuario no esté registrado.
     * 4. Valida que el rol exista y esté activo.
     * 5. Crea el registro en usuarios.
     * 6. Obtiene el user_id generado.
     * 7. Actualiza empleados.user_empl.
     *
     * Todo se realiza dentro de una transacción.
     */
    public function insertar_usuario(
        $empleado_id,
        $user_nick,
        $user_pass,
        $user_rol
    ) {

        $conectar = parent::Conexion();
        parent::set_names();


        try {

            /*
             * Inicia la transacción.
             *
             * Si cualquiera de las operaciones falla se revierten
             * todos los cambios realizados.
             */
            $conectar->beginTransaction();


            /*
             * ====================================================
             * VALIDAR EMPLEADO
             * ====================================================
             *
             * Se bloquea temporalmente el registro para evitar que
             * dos procesos creen simultáneamente usuario al mismo
             * empleado.
             */
            $sql = "SELECT
                        id_empl,
                        cedu_empl,
                        nomb_empl,
                        user_empl,
                        esta_empl
                    FROM empleados
                    WHERE id_empl = ?
                    FOR UPDATE";

            $query = $conectar->prepare($sql);

            $query->bindValue(
                1,
                $empleado_id,
                PDO::PARAM_INT
            );

            $query->execute();

            $empleado = $query->fetch(PDO::FETCH_ASSOC);


            /*
             * Comprueba que el empleado realmente exista.
             */
            if (!$empleado) {

                $conectar->rollBack();

                return array(
                    "success" => false,
                    "message" => "El empleado seleccionado no existe."
                );
            }


            /*
             * Comprueba que el empleado se encuentre activo.
             */
            if ((int)$empleado["esta_empl"] !== 1) {

                $conectar->rollBack();

                return array(
                    "success" => false,
                    "message" => "El empleado seleccionado no se encuentra activo."
                );
            }


            /*
             * Comprueba que todavía no tenga usuario relacionado.
             */
            if (!empty($empleado["user_empl"])) {

                $conectar->rollBack();

                return array(
                    "success" => false,
                    "message" => "El empleado ya tiene un usuario asignado."
                );
            }


            /*
             * ====================================================
             * VALIDAR NOMBRE DE USUARIO
             * ====================================================
             */
            $sql = "SELECT
                        user_id
                    FROM usuarios
                    WHERE LOWER(TRIM(user_nick)) = LOWER(TRIM(?))
                    LIMIT 1";

            $query = $conectar->prepare($sql);

            $query->bindValue(
                1,
                $user_nick,
                PDO::PARAM_STR
            );

            $query->execute();

            $usuario_existente =
                $query->fetch(PDO::FETCH_ASSOC);


            if ($usuario_existente) {

                $conectar->rollBack();

                return array(
                    "success" => false,
                    "message" => "El nombre de usuario ya se encuentra registrado."
                );
            }


            /*
             * ====================================================
             * VALIDAR ROL
             * ====================================================
             *
             * Se verifica directamente contra la tabla rol.
             */
            $sql = "SELECT
                        rol_id,
                        rol_nomb
                    FROM rol
                    WHERE rol_id = ?
                      AND rol_esta = 1";

            $query = $conectar->prepare($sql);

            $query->bindValue(
                1,
                $user_rol,
                PDO::PARAM_INT
            );

            $query->execute();

            $rol = $query->fetch(PDO::FETCH_ASSOC);


            if (!$rol) {

                $conectar->rollBack();

                return array(
                    "success" => false,
                    "message" => "El rol seleccionado no existe o se encuentra inactivo."
                );
            }


            /*
             * ====================================================
             * PREPARAR CONTRASEÑA
             * ====================================================
             *
             * Se almacena mediante password_hash para no guardar
             * contraseñas en texto plano.
             */
            $password_seguro = password_hash(
                $user_pass,
                PASSWORD_DEFAULT
            );


            /*
             * ====================================================
             * INSERTAR USUARIO
             * ====================================================
             *
             * user_id no se envía porque PostgreSQL utiliza
             * automáticamente su secuencia configurada.
             *
             * RETURNING permite obtener el user_id recién creado.
             */
            $sql = "INSERT INTO usuarios (
                        user_nick,
                        user_pass,
                        user_rol
                    )
                    VALUES (?, ?, ?)
                    RETURNING user_id";

            $query = $conectar->prepare($sql);

            $query->bindValue(
                1,
                trim($user_nick),
                PDO::PARAM_STR
            );

            $query->bindValue(
                2,
                $password_seguro,
                PDO::PARAM_STR
            );

            $query->bindValue(
                3,
                $user_rol,
                PDO::PARAM_INT
            );

            $query->execute();


            /*
             * Obtiene el ID generado por PostgreSQL.
             */
            $resultado_usuario =
                $query->fetch(PDO::FETCH_ASSOC);


            if (!$resultado_usuario) {

                $conectar->rollBack();

                return array(
                    "success" => false,
                    "message" => "No fue posible crear el usuario."
                );
            }


            $user_id =
                $resultado_usuario["user_id"];


            /*
             * ====================================================
             * RELACIONAR USUARIO CON EMPLEADO
             * ====================================================
             *
             * Se almacena el nuevo user_id en empleados.user_empl.
             */
            $sql = "UPDATE empleados
                    SET user_empl = ?
                    WHERE id_empl = ?
                      AND user_empl IS NULL";

            $query = $conectar->prepare($sql);

            $query->bindValue(
                1,
                $user_id,
                PDO::PARAM_INT
            );

            $query->bindValue(
                2,
                $empleado_id,
                PDO::PARAM_INT
            );

            $query->execute();


            /*
             * Debe haberse actualizado exactamente un empleado.
             */
            if ($query->rowCount() !== 1) {

                $conectar->rollBack();

                return array(
                    "success" => false,
                    "message" => "No fue posible asociar el usuario con el empleado."
                );
            }


            /*
             * ====================================================
             * CONFIRMAR TRANSACCIÓN
             * ====================================================
             */
            $conectar->commit();


            return array(

                "success" => true,

                "message" =>
                "Usuario creado y asociado correctamente.",

                "data" => array(

                    "user_id" =>
                    (int)$user_id,

                    "empleado_id" =>
                    (int)$empleado_id,

                    "empleado" =>
                    $empleado["nomb_empl"],

                    "usuario" =>
                    trim($user_nick),

                    "rol" =>
                    $rol["rol_nomb"]
                )
            );
        } catch (PDOException $e) {

            /*
             * Si ocurre cualquier error de PostgreSQL se revierte
             * la transacción completa.
             */
            if ($conectar->inTransaction()) {

                $conectar->rollBack();
            }


            /*
             * El detalle técnico queda en el log del servidor.
             * No se expone información SQL al navegador.
             */
            error_log(
                "Error insertar_usuario: "
                    . $e->getMessage()
            );


            return array(

                "success" => false,

                "message" =>
                "Ocurrió un error al crear el usuario."
            );
        }
    }

    /**
     * ============================================================
     * EDITAR USUARIO
     * ============================================================
     *
     * Permite modificar:
     * - Nombre de usuario.
     * - Rol.
     * - Contraseña, solamente si se envía una nueva.
     *
     * No modifica el empleado relacionado con la cuenta.
     */
    public function editar_usuario(
        $user_id,
        $user_nick,
        $user_pass,
        $user_rol
    ) {

        $conectar = parent::Conexion();
        parent::set_names();

        try {

            /*
         * Inicia transacción.
         */
            $conectar->beginTransaction();


            /*
         * ====================================================
         * VALIDAR USUARIO
         * ====================================================
         *
         * Comprueba que el usuario que se desea editar exista.
         */
            $sql = "SELECT
                    user_id,
                    user_nick,
                    user_rol
                FROM usuarios
                WHERE user_id = ?
                FOR UPDATE";

            $query = $conectar->prepare($sql);

            $query->bindValue(
                1,
                $user_id,
                PDO::PARAM_INT
            );

            $query->execute();

            $usuario_actual =
                $query->fetch(PDO::FETCH_ASSOC);


            if (!$usuario_actual) {

                $conectar->rollBack();

                return array(
                    "success" => false,
                    "message" => "El usuario seleccionado no existe."
                );
            }


            /*
         * ====================================================
         * VALIDAR NOMBRE DE USUARIO
         * ====================================================
         *
         * Comprueba que el nuevo nombre no esté utilizado
         * por otro usuario.
         *
         * Se excluye el usuario que actualmente se está editando.
         */
            $sql = "SELECT
                    user_id
                FROM usuarios
                WHERE LOWER(TRIM(user_nick)) = LOWER(TRIM(?))
                  AND user_id <> ?
                LIMIT 1";

            $query = $conectar->prepare($sql);

            $query->bindValue(
                1,
                trim($user_nick),
                PDO::PARAM_STR
            );

            $query->bindValue(
                2,
                $user_id,
                PDO::PARAM_INT
            );

            $query->execute();

            $usuario_existente =
                $query->fetch(PDO::FETCH_ASSOC);


            if ($usuario_existente) {

                $conectar->rollBack();

                return array(
                    "success" => false,
                    "message" =>
                    "El nombre de usuario ya se encuentra registrado."
                );
            }


            /*
         * ====================================================
         * VALIDAR ROL
         * ====================================================
         *
         * El rol debe existir y encontrarse activo.
         */
            $sql = "SELECT
                    rol_id,
                    rol_nomb
                FROM rol
                WHERE rol_id = ?
                  AND rol_esta = 1";

            $query = $conectar->prepare($sql);

            $query->bindValue(
                1,
                $user_rol,
                PDO::PARAM_INT
            );

            $query->execute();

            $rol =
                $query->fetch(PDO::FETCH_ASSOC);


            if (!$rol) {

                $conectar->rollBack();

                return array(
                    "success" => false,
                    "message" =>
                    "El rol seleccionado no existe o se encuentra inactivo."
                );
            }


            /*
         * ====================================================
         * ACTUALIZAR USUARIO
         * ====================================================
         *
         * Si no se envía contraseña, únicamente se modifica
         * nombre de usuario y rol.
         */
            if (
                $user_pass === null
                || trim($user_pass) === ""
            ) {

                $sql = "UPDATE usuarios
                    SET
                        user_nick = ?,
                        user_rol = ?
                    WHERE user_id = ?";

                $query = $conectar->prepare($sql);

                $query->bindValue(
                    1,
                    trim($user_nick),
                    PDO::PARAM_STR
                );

                $query->bindValue(
                    2,
                    $user_rol,
                    PDO::PARAM_INT
                );

                $query->bindValue(
                    3,
                    $user_id,
                    PDO::PARAM_INT
                );

                $query->execute();
            } else {

                /*
             * Si Sistemas ingresó una nueva contraseña,
             * se procesa utilizando el mismo mecanismo
             * empleado durante la creación del usuario.
             */
                $password_seguro = password_hash(
                    $user_pass,
                    PASSWORD_DEFAULT
                );


                $sql = "UPDATE usuarios
                    SET
                        user_nick = ?,
                        user_pass = ?,
                        user_rol = ?
                    WHERE user_id = ?";

                $query = $conectar->prepare($sql);

                $query->bindValue(
                    1,
                    trim($user_nick),
                    PDO::PARAM_STR
                );

                $query->bindValue(
                    2,
                    $password_seguro,
                    PDO::PARAM_STR
                );

                $query->bindValue(
                    3,
                    $user_rol,
                    PDO::PARAM_INT
                );

                $query->bindValue(
                    4,
                    $user_id,
                    PDO::PARAM_INT
                );

                $query->execute();
            }


            /*
         * ====================================================
         * CONFIRMAR TRANSACCIÓN
         * ====================================================
         */
            $conectar->commit();


            return array(

                "success" => true,

                "message" =>
                "Usuario actualizado correctamente.",

                "data" => array(

                    "user_id" =>
                    (int)$user_id,

                    "usuario" =>
                    trim($user_nick),

                    "rol" =>
                    $rol["rol_nomb"]

                )
            );
        } catch (PDOException $e) {

            /*
         * Revierte cualquier cambio en caso de error.
         */
            if ($conectar->inTransaction()) {

                $conectar->rollBack();
            }


            /*
         * El error técnico solamente se registra
         * en el log del servidor.
         */
            error_log(
                "Error editar_usuario: "
                    . $e->getMessage()
            );


            return array(

                "success" => false,

                "message" =>
                "Ocurrió un error al actualizar el usuario."

            );
        }
    }
}
