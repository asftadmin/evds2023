<?php


class Firma extends Conectar {

    public function get_by_user_id($user_id) {
        $conectar = parent::Conexion();
        parent::set_names();

        $sql = "SELECT * FROM firma_usuario WHERE user_id = ?";
        $stmt = $conectar->prepare($sql);
        $stmt->execute([$user_id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    public function insert_firma($user_id, $firma_base64) {
        $conectar = parent::Conexion();
        parent::set_names();

        $sql = "INSERT INTO firma_usuario (user_id, firma_base64) VALUES (?, ?)";
        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $firma_base64, PDO::PARAM_INT);
        return $stmt->execute();
    }


    public function update_firma($user_id, $firma_base64) {
        $conectar = parent::Conexion();
        parent::set_names();

        $sql = "UPDATE firma_usuario 
                SET firma_base64 = ?, fecha_actualizacion = NOW()
                WHERE user_id = ?";
        $stmt = $conectar->prepare($sql);
        $stmt->execute([$firma_base64, $user_id]);
    }
}