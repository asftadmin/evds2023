<?php

class Usuario extends Conectar {

    /** Abre una conexión para HU-02 con errores transaccionales controlables. */
    private function conexionMicrosoft365()
    {
        $db = parent::Conexion();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    }

    /** Valida el esquema existente; nunca crea ni modifica columnas. */
    public function verificar_esquema_microsoft365()
    {
        $db = $this->conexionMicrosoft365();
        $stmt = $db->prepare("SELECT table_name, column_name, data_type, is_nullable
            FROM information_schema.columns WHERE table_schema = 'public'
            AND ((table_name = 'usuarios' AND column_name = 'user_graph_id')
              OR (table_name = 'empleados' AND column_name = 'email_empl'))");
        $stmt->execute();
        $columnas = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), null, 'column_name');
        if (!isset($columnas['email_empl'], $columnas['user_graph_id'])
            || $columnas['user_graph_id']['data_type'] !== 'uuid'
            || $columnas['user_graph_id']['is_nullable'] !== 'YES') {
            throw new DomainException('El esquema requiere revisión: email_empl y user_graph_id UUID nullable deben existir. No se aplicaron cambios.');
        }
    }

    /** Revalida el rol real y su permiso de menú, sin confiar en el rol enviado o en caché. */
    public function autorizado_microsoft365($userId)
    {
        return $this->permisoMicrosoft365($this->conexionMicrosoft365(), $userId);
    }

    /** Usa el mecanismo rol/menú/permisos también dentro de la transacción de escritura. */
    private function permisoMicrosoft365(PDO $db, $userId)
    {
        $stmt = $db->prepare("SELECT EXISTS (SELECT 1 FROM public.usuarios u
            JOIN public.rol r ON r.rol_id = u.user_rol
            JOIN public.permisos p ON p.perm_rol = r.rol_id
            JOIN public.menu m ON m.menu_id = p.perm_menu
            LEFT JOIN public.grupos_menu g ON g.grme_id = m.menu_grupo
            WHERE u.user_id = ? AND r.rol_nomb = 'Administrador' AND r.rol_esta = 1
              AND m.menu_ident = 'sincronizacion' AND m.menu_ruta = '../MntMicrosoft365/'
              AND m.menu_esta = 1 AND p.perm_esta = 1 AND p.perm_usua = 'Si'
              AND (m.menu_grupo IS NULL OR g.grme_esta = 1)
              AND EXISTS (SELECT 1 FROM public.empleados e WHERE e.user_empl = u.user_id AND e.esta_empl = 1))");
        $stmt->execute([$userId]);
        return (bool) $stmt->fetchColumn();
    }

    /** Normaliza solo extremos y mayúsculas; nunca realiza coincidencias parciales. */
    public static function correo_microsoft365($correo)
    {
        return is_string($correo) ? mb_strtolower(trim($correo), 'UTF-8') : '';
    }

    /** Valida el UUID completo sin admitir valores arbitrarios ni conversiones parciales. */
    public static function uuid_microsoft365($id)
    {
        if (!is_string($id) || !preg_match('/\A[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12}\z/i', $id)) {
            throw new InvalidArgumentException('El Object ID no tiene un formato UUID válido.');
        }
        return strtolower($id);
    }

    /** Valida todos los parámetros de escritura antes de consultar servicios externos. */
    public static function validar_vinculacion_microsoft365(array $entrada)
    {
        foreach (['user_id', 'id_empl'] as $campo) {
            if ((!is_string($entrada[$campo] ?? null) && !is_int($entrada[$campo] ?? null))
                || !preg_match('/\A[1-9][0-9]*\z/', (string) $entrada[$campo])
                || (float) $entrada[$campo] > 2147483647) {
                throw new InvalidArgumentException('Identificador local inválido.');
            }
            $entrada[$campo] = (int) $entrada[$campo];
        }
        $entrada['graph_id'] = self::uuid_microsoft365($entrada['graph_id'] ?? null);
        if (!array_key_exists('actual', $entrada)) { throw new InvalidArgumentException('Falta el estado de la vinculación consultada.'); }
        $entrada['actual'] = $entrada['actual'] === '' || $entrada['actual'] === null ? null : self::uuid_microsoft365($entrada['actual']);
        if (!is_string($entrada['correo'] ?? null) || self::correo_microsoft365($entrada['correo']) === '') {
            throw new InvalidArgumentException('Debe indicar el correo consultado.');
        }
        $entrada['correo'] = self::correo_microsoft365($entrada['correo']);
        return $entrada;
    }

    /** Lee empleados relacionados y asignaciones globales; la consulta nunca escribe. */
    private function datosMicrosoft365(PDO $db, array $cuentas)
    {
        $stmt = $db->prepare("SELECT e.id_empl, e.nomb_empl, e.email_empl, e.esta_empl,
                u.user_id, u.user_graph_id
            FROM public.empleados e JOIN public.usuarios u ON e.user_empl = u.user_id
            WHERE e.email_empl IS NOT NULL ORDER BY e.nomb_empl, e.id_empl");
        $stmt->execute();
        $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $db->prepare('SELECT user_id, user_graph_id FROM public.usuarios WHERE user_graph_id IS NOT NULL');
        $stmt->execute();
        return self::comparar_microsoft365($empleados, $cuentas, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /** Compara todas las páginas obtenidas por CurlController sin modificar PostgreSQL. */
    public function consultar_microsoft365(array $cuentas)
    {
        return $this->datosMicrosoft365($this->conexionMicrosoft365(), $cuentas);
    }

    /**
     * Motor de comparación: mail tiene prioridad visual, pero mail/UPN deben ser inequívocos.
     * También considera correos duplicados de empleados inactivos para exigir revisión;
     * solo muestra empleados activos del dominio exacto y con usuario relacionado.
     */
    public static function comparar_microsoft365(array $empleados, array $cuentas, array $asignados)
    {
        $indice = ['mail' => [], 'userPrincipalName' => []];
        $porId = [];
        foreach ($cuentas as $cuenta) {
            $cuenta = (array) $cuenta;
            $id = self::uuid_microsoft365($cuenta['id'] ?? null);
            foreach (['mail', 'userPrincipalName'] as $campo) {
                if (!array_key_exists($campo, $cuenta) || ($cuenta[$campo] !== null && !is_string($cuenta[$campo]))) {
                    throw new DomainException('Microsoft 365 devolvió una cuenta incompleta. Repita la consulta.');
                }
                $correo = self::correo_microsoft365($cuenta[$campo]);
                if ($correo !== '') { $indice[$campo][$correo][$id] = true; }
            }
            $porId[$id] = $cuenta;
        }

        // Incluye todas las asignaciones, aunque el propietario no aparezca en el listado.
        $propietarios = [];
        foreach ($asignados as $asignado) {
            if (!empty($asignado['user_graph_id'])) {
                $propietarios[strtolower($asignado['user_graph_id'])][(int) $asignado['user_id']] = true;
            }
        }
        $correos = $usuarios = $candidatos = $filas = [];
        foreach ($empleados as $empleado) {
            $correo = self::correo_microsoft365($empleado['email_empl'] ?? null);
            $usuario = (int) ($empleado['user_id'] ?? 0);
            if ($usuario <= 0 || !preg_match('/\A[^@\s]+@asfaltart\.com\z/u', $correo)) { continue; }
            $correos[$correo] = ($correos[$correo] ?? 0) + 1;
            $usuarios[$usuario] = ($usuarios[$usuario] ?? 0) + 1;
            if ((int) $empleado['esta_empl'] !== 1) { continue; }
            $matchesMail = $indice['mail'][$correo] ?? [];
            $matches = $matchesMail + ($indice['userPrincipalName'][$correo] ?? []);
            $id = count($matches) === 1 ? (string) array_key_first($matches) : null;
            if ($id !== null) { $candidatos[$id] = ($candidatos[$id] ?? 0) + 1; }
            $filas[] = [
                'id_empl' => (int) $empleado['id_empl'], 'user_id' => $usuario,
                'empleado' => $empleado['nomb_empl'], 'correo' => $correo,
                'correo_microsoft' => $id === null ? null : self::correo_microsoft365($porId[$id][isset($matchesMail[$id]) ? 'mail' : 'userPrincipalName']),
                'cuenta_microsoft' => $id === null ? null : ($porId[$id]['displayName'] ?? ''),
                'actual' => empty($empleado['user_graph_id']) ? null : strtolower($empleado['user_graph_id']),
                'encontrado' => $id, 'coincidencias' => count($matches)
            ];
        }

        $indicadores = ['total' => count($filas), 'vinculados' => 0, 'pendientes' => 0, 'revision' => 0];
        foreach ($filas as &$fila) {
            $id = $fila['encontrado'];
            $otros = $id === null ? [] : array_diff(array_keys($propietarios[$id] ?? []), [$fila['user_id']]);
            $otrosActual = $fila['actual'] === null ? [] : array_diff(array_keys($propietarios[$fila['actual']] ?? []), [$fila['user_id']]);
            $motivos = [];
            if ($correos[$fila['correo']] > 1) { $motivos[] = 'El correo está compartido por varios empleados locales.'; }
            if ($usuarios[$fila['user_id']] > 1) { $motivos[] = 'El usuario está relacionado con varios empleados corporativos.'; }
            if ($fila['coincidencias'] > 1) { $motivos[] = 'mail y/o userPrincipalName coinciden con varias cuentas Microsoft.'; }
            if ($otros || $otrosActual) { $motivos[] = 'Un Object ID de esta vinculación está asignado a otro usuario local.'; }
            if ($id !== null && $candidatos[$id] > 1) { $motivos[] = 'La cuenta Microsoft coincide con varios registros locales.'; }
            if ($motivos) {
                $estado = 'Conflicto';
            } elseif ($id === null) {
                $estado = 'Sin coincidencia';
                $motivos[] = 'No existe una cuenta Microsoft con este correo exacto.';
            } elseif ($fila['actual'] === null) {
                $estado = 'Nuevo';
            } elseif ($fila['actual'] === $id) {
                $estado = 'Vinculado';
            } else {
                $estado = 'Cambio detectado';
            }
            $fila['estado'] = $estado;
            $fila['detalle'] = implode(' ', $motivos);
            $fila['accion'] = $estado === 'Nuevo' ? 'guardar' : (in_array($estado, ['Vinculado', 'Cambio detectado'], true) ? 'editar' : 'revisar');
            unset($fila['coincidencias']);
            $indicadores[$estado === 'Vinculado' ? 'vinculados' : ($estado === 'Nuevo' ? 'pendientes' : 'revision')]++;
        }
        unset($fila);
        return ['registros' => $filas, 'indicadores' => $indicadores];
    }

    /**
     * Vincula un solo usuario con Graph recién consultado por el controlador.
     * Sin índice UUID único preexistente, bloquea escrituras de usuarios y empleados
     * brevemente para volver a comparar y evitar carreras; nunca mantiene locks durante HTTP.
     */
    public function vincular_microsoft365($actorId, $operacion, array $entrada, array $cuentas)
    {
        if (!in_array($operacion, ['guardar', 'editar'], true)) { throw new InvalidArgumentException('Operación inválida.'); }
        $entrada = self::validar_vinculacion_microsoft365($entrada);
        $db = $this->conexionMicrosoft365();
        try {
            $db->beginTransaction();
            $db->exec("SET LOCAL lock_timeout = '5s'");
            $db->exec('LOCK TABLE public.usuarios IN SHARE ROW EXCLUSIVE MODE');
            $db->exec('LOCK TABLE public.empleados IN SHARE MODE');
            if (!$this->permisoMicrosoft365($db, $actorId)) { throw new DomainException('No tiene autorización para esta operación.'); }
            $comparacion = $this->datosMicrosoft365($db, $cuentas);
            $fila = null;
            foreach ($comparacion['registros'] as $registro) {
                if ($registro['user_id'] === $entrada['user_id'] && $registro['id_empl'] === $entrada['id_empl']) { $fila = $registro; break; }
            }
            if ($fila === null || $fila['accion'] !== $operacion
                || $fila['encontrado'] !== $entrada['graph_id'] || $fila['actual'] !== $entrada['actual']
                || $fila['correo'] !== $entrada['correo']) {
                throw new DomainException('La vinculación cambió o presenta un conflicto. Consulte Microsoft 365 nuevamente.');
            }
            $sinCambios = $fila['actual'] === $fila['encontrado'];
            if (!$sinCambios) {
                $stmt = $db->prepare('UPDATE public.usuarios SET user_graph_id = CAST(? AS uuid) WHERE user_id = ?');
                $stmt->execute([$fila['encontrado'], $fila['user_id']]);
                if ($stmt->rowCount() !== 1) { throw new DomainException('No fue posible actualizar una única vinculación.'); }
                $comparacion = $this->datosMicrosoft365($db, $cuentas);
            }
            $db->commit();
            return $comparacion + ['sin_cambios' => $sinCambios];
        } catch (Throwable $e) {
            if ($db->inTransaction()) { $db->rollBack(); }
            throw $e;
        }
    }


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
