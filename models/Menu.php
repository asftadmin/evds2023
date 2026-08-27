<?php

class Menu extends Conectar {

    /*
     * Lista los menús autorizados para el rol principal y, cuando corresponde,
     * incorpora los permisos del rol Jefe Inmediato. También retorna grupo y orden.
     */
    public function mostrar_menu_x_rol($rol_id, $es_jefe = false) {
        $conectar = parent::Conexion();

        $sql = "WITH menus_permitidos AS (
                    SELECT
                        p.perm_id,
                        p.perm_menu,
                        p.perm_rol,
                        p.perm_usua,
                        p.perm_esta,
                        m.menu_id,
                        m.menu_nomb,
                        m.menu_ruta,
                        m.menu_ident,
                        m.menu_icon,
                        m.menu_grupo,
                        m.menu_orden,
                        g.grme_id,
                        g.grme_nomb,
                        g.grme_icon,
                        g.grme_orden,
                        g.grme_esta,
                        ROW_NUMBER() OVER (
                            PARTITION BY m.menu_id
                            ORDER BY
                                CASE WHEN p.perm_usua = 'Si' THEN 0 ELSE 1 END,
                                CASE WHEN p.perm_rol = ? THEN 0 ELSE 1 END,
                                p.perm_id
                        ) AS fila
                    FROM permisos p
                    INNER JOIN menu m ON p.perm_menu = m.menu_id
                    INNER JOIN rol r ON p.perm_rol = r.rol_id
                    LEFT JOIN grupos_menu g ON m.menu_grupo = g.grme_id
                    WHERE (
                        p.perm_rol = ?
                        OR (
                            ? = 1
                            AND r.rol_nomb = 'Jefe Inmediato'
                        )
                    )
                    AND p.perm_esta = 1
                    AND m.menu_esta = 1
                    AND r.rol_esta = 1
                    AND (
                        m.menu_grupo IS NULL
                        OR g.grme_esta = 1
                    )
                )
                SELECT
                    perm_id,
                    perm_menu,
                    perm_rol,
                    perm_usua,
                    perm_esta,
                    menu_id,
                    menu_nomb,
                    menu_ruta,
                    menu_ident,
                    menu_icon,
                    menu_grupo,
                    menu_orden,
                    grme_id,
                    grme_nomb,
                    grme_icon,
                    grme_orden,
                    grme_esta
                FROM menus_permitidos
                WHERE fila = 1
                ORDER BY
                    COALESCE(grme_orden, 0),
                    menu_orden,
                    menu_id";

        $query = $conectar->prepare($sql);
        $query->bindValue(1, $rol_id, PDO::PARAM_INT);
        $query->bindValue(2, $rol_id, PDO::PARAM_INT);
        $query->bindValue(3, $es_jefe ? 1 : 0, PDO::PARAM_INT);
        $query->execute();

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }


    // Valida si el empleado tiene una relación activa como Jefe Inmediato.
    public function es_jefe_activo($empleado_id) {
        $conectar = parent::Conexion();

        $sql = "SELECT EXISTS (
                    SELECT 1
                    FROM empleado_jefe
                    WHERE jefe_id = ?
                    AND ej_estado = 1
                )";

        $query = $conectar->prepare($sql);
        $query->bindValue(1, $empleado_id, PDO::PARAM_INT);
        $query->execute();

        return (bool)$query->fetchColumn();
    }


    /*
     * Se conservan temporalmente para compatibilidad con el controlador antiguo.
     * El nuevo módulo de Roles ya administra los permisos de forma masiva.
     */
    public function activar_menu($perm_id) {
        $conectar = parent::Conexion();

        $sql = "UPDATE permisos SET perm_usua = 'Si' WHERE perm_id = ?";
        $query = $conectar->prepare($sql);
        $query->bindValue(1, $perm_id, PDO::PARAM_INT);
        $query->execute();

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }


    public function desactivar_menu($perm_id) {
        $conectar = parent::Conexion();

        $sql = "UPDATE permisos SET perm_usua = 'No' WHERE perm_id = ?";
        $query = $conectar->prepare($sql);
        $query->bindValue(1, $perm_id, PDO::PARAM_INT);
        $query->execute();

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }


    // Lista todos los menús para la vista de administración.
    public function listar_menu() {
        $conectar = parent::Conexion();
        parent::set_names();

        $sql = "SELECT
                    m.menu_id,
                    m.menu_nomb,
                    m.menu_ruta,
                    m.menu_ident,
                    m.menu_esta,
                    m.menu_icon,
                    m.menu_grupo,
                    m.menu_orden,
                    g.grme_id,
                    g.grme_nomb,
                    g.grme_icon,
                    g.grme_orden,
                    g.grme_esta
                FROM menu m
                LEFT JOIN grupos_menu g ON m.menu_grupo = g.grme_id
                ORDER BY
                    COALESCE(g.grme_orden, 0),
                    m.menu_orden,
                    m.menu_id";

        $query = $conectar->prepare($sql);
        $query->execute();

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }


    // Consulta un menú específico para cargarlo en edición.
    public function mostrar_menu_x_id($menu_id) {
        $conectar = parent::Conexion();
        parent::set_names();

        $sql = "SELECT
                    menu_id,
                    menu_nomb,
                    menu_ruta,
                    menu_ident,
                    menu_esta,
                    menu_icon,
                    menu_grupo,
                    menu_orden
                FROM menu
                WHERE menu_id = ?";

        $query = $conectar->prepare($sql);
        $query->bindValue(1, $menu_id, PDO::PARAM_INT);
        $query->execute();

        return $query->fetch(PDO::FETCH_ASSOC);
    }


    // Retorna los grupos activos disponibles para asignar un menú.
    public function listar_grupos_menu() {
        $conectar = parent::Conexion();
        parent::set_names();

        $sql = "SELECT
                    grme_id,
                    grme_nomb,
                    grme_icon,
                    grme_orden,
                    grme_esta
                FROM grupos_menu
                WHERE grme_esta = 1
                ORDER BY grme_orden, grme_nomb";

        $query = $conectar->prepare($sql);
        $query->execute();

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }


    // Valida que no exista otro menú con el mismo nombre.
    private function nombre_menu_existe($conectar, $menu_nomb, $menu_id = null) {
        $sql = "SELECT menu_id
                FROM menu
                WHERE LOWER(TRIM(menu_nomb)) = LOWER(TRIM(?))";

        if ($menu_id !== null) {
            $sql .= " AND menu_id <> ?";
        }

        $sql .= " LIMIT 1";

        $query = $conectar->prepare($sql);
        $query->bindValue(1, $menu_nomb, PDO::PARAM_STR);

        if ($menu_id !== null) {
            $query->bindValue(2, $menu_id, PDO::PARAM_INT);
        }

        $query->execute();

        return (bool)$query->fetch(PDO::FETCH_ASSOC);
    }


    // Valida que el identificador no esté utilizado por otro menú.
    private function identificador_menu_existe($conectar, $menu_ident, $menu_id = null) {
        $sql = "SELECT menu_id
                FROM menu
                WHERE LOWER(TRIM(menu_ident)) = LOWER(TRIM(?))";

        if ($menu_id !== null) {
            $sql .= " AND menu_id <> ?";
        }

        $sql .= " LIMIT 1";

        $query = $conectar->prepare($sql);
        $query->bindValue(1, $menu_ident, PDO::PARAM_STR);

        if ($menu_id !== null) {
            $query->bindValue(2, $menu_id, PDO::PARAM_INT);
        }

        $query->execute();

        return (bool)$query->fetch(PDO::FETCH_ASSOC);
    }


    // Valida que el grupo seleccionado exista.
    private function grupo_menu_existe($conectar, $menu_grupo) {
        $sql = "SELECT grme_id
                FROM grupos_menu
                WHERE grme_id = ?
                LIMIT 1";

        $query = $conectar->prepare($sql);
        $query->bindValue(1, $menu_grupo, PDO::PARAM_INT);
        $query->execute();

        return (bool)$query->fetch(PDO::FETCH_ASSOC);
    }


    /*
     * Crea el menú y genera automáticamente su relación con todos los roles.
     * Los permisos se crean inicialmente sin acceso.
     */
    public function insertar_menu(
        $menu_nomb,
        $menu_ruta,
        $menu_ident,
        $menu_icon,
        $menu_grupo,
        $menu_orden
    ) {
        $conectar = parent::Conexion();
        parent::set_names();

        $menu_nomb = trim($menu_nomb);
        $menu_ruta = trim($menu_ruta);
        $menu_ident = trim($menu_ident);
        $menu_icon = trim($menu_icon);
        $menu_grupo = ($menu_grupo === "" || $menu_grupo === null) ? null : (int)$menu_grupo;

        if ($menu_nomb === "" || $menu_ruta === "" || $menu_ident === "" || $menu_icon === "") {
            return [
                "success" => false,
                "message" => "Debe completar los datos obligatorios del menú."
            ];
        }

        if ($menu_orden === "" || !is_numeric($menu_orden)) {
            return [
                "success" => false,
                "message" => "Debe indicar un orden válido para el menú."
            ];
        }

        $menu_orden = (int)$menu_orden;

        try {
            $conectar->beginTransaction();

            if ($this->nombre_menu_existe($conectar, $menu_nomb)) {
                $conectar->rollBack();
                return ["success" => false, "message" => "Ya existe un menú con este nombre."];
            }

            if ($this->identificador_menu_existe($conectar, $menu_ident)) {
                $conectar->rollBack();
                return ["success" => false, "message" => "Ya existe un menú con este identificador."];
            }

            if ($menu_grupo !== null && !$this->grupo_menu_existe($conectar, $menu_grupo)) {
                $conectar->rollBack();
                return ["success" => false, "message" => "El grupo seleccionado no existe."];
            }

            $sql = "INSERT INTO menu (
                        menu_nomb,
                        menu_ruta,
                        menu_ident,
                        menu_esta,
                        menu_icon,
                        menu_grupo,
                        menu_orden
                    )
                    VALUES (?, ?, ?, 1, ?, ?, ?)
                    RETURNING menu_id";

            $query = $conectar->prepare($sql);
            $query->bindValue(1, $menu_nomb, PDO::PARAM_STR);
            $query->bindValue(2, $menu_ruta, PDO::PARAM_STR);
            $query->bindValue(3, $menu_ident, PDO::PARAM_STR);
            $query->bindValue(4, $menu_icon, PDO::PARAM_STR);

            if ($menu_grupo === null) {
                $query->bindValue(5, null, PDO::PARAM_NULL);
            } else {
                $query->bindValue(5, $menu_grupo, PDO::PARAM_INT);
            }

            $query->bindValue(6, $menu_orden, PDO::PARAM_INT);
            $query->execute();

            $menu_id = $query->fetchColumn();

            if (!$menu_id) {
                $conectar->rollBack();
                return ["success" => false, "message" => "No fue posible crear el menú."];
            }

            // Relaciona el nuevo menú con todos los roles sin otorgar acceso.
            $sql = "INSERT INTO permisos (perm_menu, perm_rol, perm_usua, perm_esta)
                    SELECT ?, rol_id, 'No', 1
                    FROM rol";

            $query = $conectar->prepare($sql);
            $query->bindValue(1, $menu_id, PDO::PARAM_INT);
            $query->execute();

            $conectar->commit();

            return [
                "success" => true,
                "message" => "Menú creado correctamente.",
                "data" => [
                    "menu_id" => (int)$menu_id,
                    "menu_nomb" => $menu_nomb,
                    "menu_ruta" => $menu_ruta,
                    "menu_ident" => $menu_ident,
                    "menu_icon" => $menu_icon,
                    "menu_grupo" => $menu_grupo,
                    "menu_orden" => $menu_orden,
                    "menu_esta" => 1
                ]
            ];

        } catch (PDOException $e) {
            if ($conectar->inTransaction()) {
                $conectar->rollBack();
            }

            error_log("Error insertar_menu: " . $e->getMessage());

            return [
                "success" => false,
                "message" => "Ocurrió un error al crear el menú."
            ];
        }
    }


    // Actualiza los datos y organización visual de un menú existente.
    public function editar_menu(
        $menu_id,
        $menu_nomb,
        $menu_ruta,
        $menu_ident,
        $menu_icon,
        $menu_grupo,
        $menu_orden,
        $menu_esta
    ) {
        $conectar = parent::Conexion();
        parent::set_names();

        $menu_id = (int)$menu_id;
        $menu_nomb = trim($menu_nomb);
        $menu_ruta = trim($menu_ruta);
        $menu_ident = trim($menu_ident);
        $menu_icon = trim($menu_icon);
        $menu_grupo = ($menu_grupo === "" || $menu_grupo === null) ? null : (int)$menu_grupo;
        $menu_esta = (int)$menu_esta;

        if ($menu_nomb === "" || $menu_ruta === "" || $menu_ident === "" || $menu_icon === "") {
            return ["success" => false, "message" => "Debe completar los datos obligatorios del menú."];
        }

        if ($menu_orden === "" || !is_numeric($menu_orden)) {
            return ["success" => false, "message" => "Debe indicar un orden válido para el menú."];
        }

        if ($menu_esta !== 0 && $menu_esta !== 1) {
            return ["success" => false, "message" => "El estado del menú no es válido."];
        }

        $menu_orden = (int)$menu_orden;

        try {
            $sql = "SELECT menu_id FROM menu WHERE menu_id = ?";
            $query = $conectar->prepare($sql);
            $query->bindValue(1, $menu_id, PDO::PARAM_INT);
            $query->execute();

            if (!$query->fetch(PDO::FETCH_ASSOC)) {
                return ["success" => false, "message" => "El menú seleccionado no existe."];
            }

            if ($this->nombre_menu_existe($conectar, $menu_nomb, $menu_id)) {
                return ["success" => false, "message" => "Ya existe otro menú con este nombre."];
            }

            if ($this->identificador_menu_existe($conectar, $menu_ident, $menu_id)) {
                return ["success" => false, "message" => "Ya existe otro menú con este identificador."];
            }

            if ($menu_grupo !== null && !$this->grupo_menu_existe($conectar, $menu_grupo)) {
                return ["success" => false, "message" => "El grupo seleccionado no existe."];
            }

            $sql = "UPDATE menu
                    SET menu_nomb = ?,
                        menu_ruta = ?,
                        menu_ident = ?,
                        menu_icon = ?,
                        menu_grupo = ?,
                        menu_orden = ?,
                        menu_esta = ?
                    WHERE menu_id = ?";

            $query = $conectar->prepare($sql);
            $query->bindValue(1, $menu_nomb, PDO::PARAM_STR);
            $query->bindValue(2, $menu_ruta, PDO::PARAM_STR);
            $query->bindValue(3, $menu_ident, PDO::PARAM_STR);
            $query->bindValue(4, $menu_icon, PDO::PARAM_STR);

            if ($menu_grupo === null) {
                $query->bindValue(5, null, PDO::PARAM_NULL);
            } else {
                $query->bindValue(5, $menu_grupo, PDO::PARAM_INT);
            }

            $query->bindValue(6, $menu_orden, PDO::PARAM_INT);
            $query->bindValue(7, $menu_esta, PDO::PARAM_INT);
            $query->bindValue(8, $menu_id, PDO::PARAM_INT);
            $query->execute();

            return [
                "success" => true,
                "message" => "Menú actualizado correctamente."
            ];

        } catch (PDOException $e) {
            error_log("Error editar_menu: " . $e->getMessage());

            return [
                "success" => false,
                "message" => "Ocurrió un error al actualizar el menú."
            ];
        }
    }


    // Activa o inactiva un menú conservando la configuración de permisos de los roles.
    public function cambiar_estado_menu($menu_id, $menu_esta) {
        $conectar = parent::Conexion();
        parent::set_names();

        $menu_id = (int)$menu_id;
        $menu_esta = (int)$menu_esta;

        if ($menu_esta !== 0 && $menu_esta !== 1) {
            return ["success" => false, "message" => "El estado del menú no es válido."];
        }

        try {
            $sql = "SELECT menu_id FROM menu WHERE menu_id = ?";
            $query = $conectar->prepare($sql);
            $query->bindValue(1, $menu_id, PDO::PARAM_INT);
            $query->execute();

            if (!$query->fetch(PDO::FETCH_ASSOC)) {
                return ["success" => false, "message" => "El menú seleccionado no existe."];
            }

            $sql = "UPDATE menu SET menu_esta = ? WHERE menu_id = ?";
            $query = $conectar->prepare($sql);
            $query->bindValue(1, $menu_esta, PDO::PARAM_INT);
            $query->bindValue(2, $menu_id, PDO::PARAM_INT);
            $query->execute();

            return [
                "success" => true,
                "message" => $menu_esta === 1
                    ? "Menú activado correctamente."
                    : "Menú inactivado correctamente."
            ];

        } catch (PDOException $e) {
            error_log("Error cambiar_estado_menu: " . $e->getMessage());

            return [
                "success" => false,
                "message" => "No fue posible cambiar el estado del menú."
            ];
        }
    }
}