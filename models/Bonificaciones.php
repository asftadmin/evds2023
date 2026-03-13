<?php

class Bonificaciones extends Conectar {
    // ── Listar bonificaciones por empleado ────────────────
    public function get_bonificaciones($empleado_id) {
        $conectar = parent::conexion();
        parent::set_names();

        $sql = "SELECT * FROM bonificaciones_empleado 
            WHERE empleado_id = ? 
            ORDER BY bonif_creado DESC";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $empleado_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── Insertar bonificacion ─────────────────────────────
    public function insertar_bonificacion($empleado_id, $concepto, $valor, $periocidad, $fecha_ini, $observ) {
        $conectar = parent::conexion();
        parent::set_names();

        $sql = "INSERT INTO bonificaciones_empleado 
                (empleado_id, bonif_concepto, bonif_valor, bonif_periocidad, bonif_fecha_ini, bonif_observ)
            VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $empleado_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $concepto,    PDO::PARAM_STR);
        $stmt->bindValue(3, $valor,       PDO::PARAM_STR);
        $stmt->bindValue(4, $periocidad,  $periocidad  ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(5, $fecha_ini,   $fecha_ini   ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(6, $observ,      $observ      ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // ── Actualizar bonificacion ───────────────────────────
    public function update_bonificacion($bonif_id, $concepto, $valor, $periocidad, $fecha_ini, $observ, $estado) {
        $conectar = parent::conexion();
        parent::set_names();

        $sql = "UPDATE bonificaciones_empleado SET
                bonif_concepto   = ?,
                bonif_valor      = ?,
                bonif_periocidad = ?,
                bonif_fecha_ini  = ?,
                bonif_observ     = ?,
                bonif_estado     = ?
            WHERE bonif_id = ?";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $concepto,   PDO::PARAM_STR);
        $stmt->bindValue(2, $valor,      PDO::PARAM_STR);
        $stmt->bindValue(3, $periocidad, $periocidad ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(4, $fecha_ini,  $fecha_ini  ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(5, $observ,     $observ     ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(6, $estado,     PDO::PARAM_INT);
        $stmt->bindValue(7, $bonif_id,   PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // ── Eliminar bonificacion ─────────────────────────────
    public function eliminar_bonificacion($bonif_id) {
        $conectar = parent::conexion();
        parent::set_names();

        $sql = "DELETE FROM bonificaciones_empleado WHERE bonif_id = ?";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $bonif_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}
