<?php
session_start();

class Conectar {
    protected $dbh;

    protected function Conexion() {
        try {
            // Cambiar los valores según tu configuración de PostgreSQL   192.168.0.200  masterd_asft
            $host = "172.16.5.2";
            $dbname = "evds2023";
            $usuario = "postgres";
            $contrasena = "masterd_asft";

            $conectar = $this->dbh = new PDO("pgsql:host=$host;port=5432;dbname=$dbname", $usuario, $contrasena);
            return $conectar;
        } catch (PDOException $e) {
            print "¡Error BD!: " . $e->getMessage() . "<br/>";
            die();
        }
    }

    public function set_names() {
        return $this->dbh->query("SET NAMES 'utf8'");
    }

    public static function ruta() {
        return "http://localhost/evds2023/";
        //return "http://181.204.219.154:3396/evds2023/";
    }
}
