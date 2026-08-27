<?php

class Rol extends Conectar
{
    // Lista todos los roles registrados para la DataTable.
    public function listar_rol()
    {

        $conectar = parent::Conexion();
        parent::set_names();

        $sql = "SELECT
                    rol_id,
                    rol_nomb,
                    rol_esta
                FROM rol
                ORDER BY rol_nomb ASC";

        $query = $conectar->prepare($sql);
        $query->execute();

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }


    // Consulta un rol específico para cargarlo en el formulario de edición.
    public function mostrar_rol_x_id($rol_id)
    {

        $conectar = parent::Conexion();
        parent::set_names();

        $sql = "SELECT
                    rol_id,
                    rol_nomb,
                    rol_esta
                FROM rol
                WHERE rol_id = ?";

        $query = $conectar->prepare($sql);

        $query->bindValue(
            1,
            $rol_id,
            PDO::PARAM_INT
        );

        $query->execute();

        return $query->fetch(PDO::FETCH_ASSOC);
    }


    // Valida que no exista otro rol con el mismo nombre.
    private function nombre_rol_existe(
        $conectar,
        $rol_nomb,
        $rol_id = null
    ) {

        if ($rol_id === null) {

            $sql = "SELECT rol_id
                    FROM rol
                    WHERE LOWER(TRIM(rol_nomb)) = LOWER(TRIM(?))
                    LIMIT 1";

            $query = $conectar->prepare($sql);

            $query->bindValue(
                1,
                $rol_nomb,
                PDO::PARAM_STR
            );
        } else {

            $sql = "SELECT rol_id
                    FROM rol
                    WHERE LOWER(TRIM(rol_nomb)) = LOWER(TRIM(?))
                      AND rol_id <> ?
                    LIMIT 1";

            $query = $conectar->prepare($sql);

            $query->bindValue(
                1,
                $rol_nomb,
                PDO::PARAM_STR
            );

            $query->bindValue(
                2,
                $rol_id,
                PDO::PARAM_INT
            );
        }

        $query->execute();

        return $query->fetch(PDO::FETCH_ASSOC);
    }


    // Crea el rol y genera inicialmente sus permisos sin acceso.
    public function insertar_rol($rol_nomb)
    {

        $conectar = parent::Conexion();
        parent::set_names();

        $rol_nomb = trim($rol_nomb);


        if ($rol_nomb === "") {

            return array(
                "success" => false,
                "message" => "Debe ingresar el nombre del rol."
            );
        }


        try {

            $conectar->beginTransaction();


            // Evita registrar nombres de rol duplicados.
            if (
                $this->nombre_rol_existe(
                    $conectar,
                    $rol_nomb
                )
            ) {

                $conectar->rollBack();

                return array(
                    "success" => false,
                    "message" => "Ya existe un rol con este nombre."
                );
            }


            // El nuevo rol se crea activo.
            $sql = "INSERT INTO rol (
                        rol_nomb,
                        rol_esta
                    )
                    VALUES (?, 1)
                    RETURNING rol_id";

            $query = $conectar->prepare($sql);

            $query->bindValue(
                1,
                $rol_nomb,
                PDO::PARAM_STR
            );

            $query->execute();

            $rol_id = $query->fetchColumn();


            if (!$rol_id) {

                $conectar->rollBack();

                return array(
                    "success" => false,
                    "message" => "No fue posible crear el rol."
                );
            }


            /*
             * Crea la relación del nuevo rol con todos los menús
             * existentes. Inicialmente ninguno tendrá acceso.
             */
            $sql = "INSERT INTO permisos (
                        perm_menu,
                        perm_rol,
                        perm_usua,
                        perm_esta
                    )
                    SELECT
                        menu_id,
                        ?,
                        'No',
                        1
                    FROM menu";

            $query = $conectar->prepare($sql);

            $query->bindValue(
                1,
                $rol_id,
                PDO::PARAM_INT
            );

            $query->execute();


            $conectar->commit();


            return array(
                "success" => true,
                "message" => "Rol creado correctamente.",
                "data" => array(
                    "rol_id" => (int)$rol_id,
                    "rol_nomb" => $rol_nomb,
                    "rol_esta" => 1
                )
            );
        } catch (PDOException $e) {

            if ($conectar->inTransaction()) {
                $conectar->rollBack();
            }

            error_log(
                "Error insertar_rol: "
                    . $e->getMessage()
            );


            return array(
                "success" => false,
                "message" => "Ocurrió un error al crear el rol."
            );
        }
    }


    // Actualiza el nombre y estado de un rol existente.
    public function editar_rol(
        $rol_id,
        $rol_nomb,
        $rol_esta
    ) {

        $conectar = parent::Conexion();
        parent::set_names();

        $rol_nomb = trim($rol_nomb);
        $rol_esta = (int)$rol_esta;


        if ($rol_nomb === "") {

            return array(
                "success" => false,
                "message" => "Debe ingresar el nombre del rol."
            );
        }


        if (
            $rol_esta !== 0
            && $rol_esta !== 1
        ) {

            return array(
                "success" => false,
                "message" => "El estado del rol no es válido."
            );
        }


        try {

            // Comprueba que el rol exista.
            $sql = "SELECT
                        rol_id
                    FROM rol
                    WHERE rol_id = ?";

            $query = $conectar->prepare($sql);

            $query->bindValue(
                1,
                $rol_id,
                PDO::PARAM_INT
            );

            $query->execute();


            if (!$query->fetch(PDO::FETCH_ASSOC)) {

                return array(
                    "success" => false,
                    "message" => "El rol seleccionado no existe."
                );
            }


            // Evita duplicar el nombre con otro rol.
            if (
                $this->nombre_rol_existe(
                    $conectar,
                    $rol_nomb,
                    $rol_id
                )
            ) {

                return array(
                    "success" => false,
                    "message" => "Ya existe otro rol con este nombre."
                );
            }


            $sql = "UPDATE rol
                    SET
                        rol_nomb = ?,
                        rol_esta = ?
                    WHERE rol_id = ?";

            $query = $conectar->prepare($sql);

            $query->bindValue(
                1,
                $rol_nomb,
                PDO::PARAM_STR
            );

            $query->bindValue(
                2,
                $rol_esta,
                PDO::PARAM_INT
            );

            $query->bindValue(
                3,
                $rol_id,
                PDO::PARAM_INT
            );

            $query->execute();


            return array(
                "success" => true,
                "message" => "Rol actualizado correctamente.",
                "data" => array(
                    "rol_id" => (int)$rol_id,
                    "rol_nomb" => $rol_nomb,
                    "rol_esta" => $rol_esta
                )
            );
        } catch (PDOException $e) {

            error_log(
                "Error editar_rol: "
                    . $e->getMessage()
            );


            return array(
                "success" => false,
                "message" => "Ocurrió un error al actualizar el rol."
            );
        }
    }


    // Permite activar o inactivar un rol sin eliminarlo físicamente.
    public function cambiar_estado_rol(
        $rol_id,
        $rol_esta
    ) {

        $conectar = parent::Conexion();
        parent::set_names();

        $rol_esta = (int)$rol_esta;


        if (
            $rol_esta !== 0
            && $rol_esta !== 1
        ) {

            return array(
                "success" => false,
                "message" => "El estado del rol no es válido."
            );
        }


        try {

            $sql = "UPDATE rol
                    SET rol_esta = ?
                    WHERE rol_id = ?";

            $query = $conectar->prepare($sql);

            $query->bindValue(
                1,
                $rol_esta,
                PDO::PARAM_INT
            );

            $query->bindValue(
                2,
                $rol_id,
                PDO::PARAM_INT
            );

            $query->execute();


            if ($query->rowCount() === 0) {

                return array(
                    "success" => false,
                    "message" => "El rol seleccionado no existe."
                );
            }


            return array(
                "success" => true,
                "message" =>
                $rol_esta === 1
                    ? "Rol activado correctamente."
                    : "Rol inactivado correctamente."
            );
        } catch (PDOException $e) {

            error_log(
                "Error cambiar_estado_rol: "
                    . $e->getMessage()
            );


            return array(
                "success" => false,
                "message" => "No fue posible cambiar el estado del rol."
            );
        }
    }


    /*
     * Consulta todos los menús activos y determina si el rol
     * seleccionado tiene acceso a cada uno.
     *
     * LEFT JOIN permite mostrar también un menú que por alguna
     * razón todavía no tenga registro en permisos.
     */
    public function listar_permisos_rol($rol_id)
    {

        $conectar = parent::Conexion();
        parent::set_names();

        $sql = "SELECT
                    m.menu_id,
                    m.menu_nomb,
                    m.menu_ruta,
                    m.menu_ident,
                    m.menu_esta,
                    m.menu_icon,
                    CASE
                        WHEN MAX(
                            CASE
                                WHEN p.perm_usua = 'Si'
                                 AND p.perm_esta = 1
                                THEN 1
                                ELSE 0
                            END
                        ) = 1
                        THEN 1
                        ELSE 0
                    END AS acceso
                FROM menu m
                LEFT JOIN permisos p
                    ON p.perm_menu = m.menu_id
                   AND p.perm_rol = ?
                WHERE m.menu_esta = 1
                GROUP BY
                    m.menu_id,
                    m.menu_nomb,
                    m.menu_ruta,
                    m.menu_ident,
                    m.menu_esta,
                    m.menu_icon
                ORDER BY m.menu_nomb ASC";

        $query = $conectar->prepare($sql);

        $query->bindValue(
            1,
            $rol_id,
            PDO::PARAM_INT
        );

        $query->execute();

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }


    /*
     * Guarda todos los accesos del rol en una sola transacción.
     *
     * $menus_acceso debe contener únicamente los menu_id que
     * quedarán habilitados.
     */
    public function guardar_permisos_rol(
        $rol_id,
        $menus_acceso
    ) {

        $conectar = parent::Conexion();
        parent::set_names();

        $menus = array();


        // Normaliza los IDs recibidos desde el controlador.
        if (is_array($menus_acceso)) {

            foreach ($menus_acceso as $menu_id) {

                $menu_id = (int)$menu_id;

                if ($menu_id > 0) {
                    $menus[$menu_id] = $menu_id;
                }
            }
        }


        $menus = array_values($menus);


        try {

            $conectar->beginTransaction();


            // Valida que el rol exista antes de modificar sus permisos.
            $sql = "SELECT rol_id
                    FROM rol
                    WHERE rol_id = ?
                    FOR UPDATE";

            $query = $conectar->prepare($sql);

            $query->bindValue(
                1,
                $rol_id,
                PDO::PARAM_INT
            );

            $query->execute();


            if (!$query->fetch(PDO::FETCH_ASSOC)) {

                $conectar->rollBack();

                return array(
                    "success" => false,
                    "message" => "El rol seleccionado no existe."
                );
            }


            /*
             * Completa cualquier relación rol-menú que pudiera faltar.
             * Esto permite que roles antiguos también tengan relación
             * con todos los menús registrados.
             */
            $sql = "INSERT INTO permisos (
                        perm_menu,
                        perm_rol,
                        perm_usua,
                        perm_esta
                    )
                    SELECT
                        m.menu_id,
                        ?,
                        'No',
                        1
                    FROM menu m
                    WHERE NOT EXISTS (
                        SELECT 1
                        FROM permisos p
                        WHERE p.perm_menu = m.menu_id
                          AND p.perm_rol = ?
                    )";

            $query = $conectar->prepare($sql);

            $query->bindValue(
                1,
                $rol_id,
                PDO::PARAM_INT
            );

            $query->bindValue(
                2,
                $rol_id,
                PDO::PARAM_INT
            );

            $query->execute();


            // Primero deja todos los accesos del rol deshabilitados.
            $sql = "UPDATE permisos
                    SET perm_usua = 'No'
                    WHERE perm_rol = ?";

            $query = $conectar->prepare($sql);

            $query->bindValue(
                1,
                $rol_id,
                PDO::PARAM_INT
            );

            $query->execute();


            // Habilita únicamente los menús enviados por la vista.
            if (count($menus) > 0) {

                $placeholders =
                    implode(
                        ",",
                        array_fill(
                            0,
                            count($menus),
                            "?"
                        )
                    );


                $sql = "UPDATE permisos p
                        SET perm_usua = 'Si'
                        FROM menu m
                        WHERE p.perm_menu = m.menu_id
                          AND p.perm_rol = ?
                          AND m.menu_esta = 1
                          AND p.perm_menu IN ($placeholders)";

                $query = $conectar->prepare($sql);


                $posicion = 1;

                $query->bindValue(
                    $posicion,
                    $rol_id,
                    PDO::PARAM_INT
                );

                $posicion++;


                foreach ($menus as $menu_id) {

                    $query->bindValue(
                        $posicion,
                        $menu_id,
                        PDO::PARAM_INT
                    );

                    $posicion++;
                }


                $query->execute();
            }


            $conectar->commit();


            return array(
                "success" => true,
                "message" => "Permisos del rol actualizados correctamente."
            );
        } catch (PDOException $e) {

            if ($conectar->inTransaction()) {
                $conectar->rollBack();
            }


            error_log(
                "Error guardar_permisos_rol: "
                    . $e->getMessage()
            );


            return array(
                "success" => false,
                "message" => "Ocurrió un error al actualizar los permisos."
            );
        }
    }
}
