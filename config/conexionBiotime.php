<?php
session_start();

class Conectar
{
    public function Conexion()
    {
        $serverName = "172.16.5.2,1433"; // IP y puerto separados por coma
        $database = "BDBioAdminSQL";
        $username = "bioadmin";
        $password = "&ASFT+98%";

        $dsn = "sqlsrv:Server=$serverName;Database=$database";

        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
            ];
            $dbh = new PDO($dsn, $username, $password, $options);
            return $dbh;
        } catch (PDOException $e) {
            echo "¡Error BD!: " . htmlspecialchars($e->getMessage()) . "<br/>";
            exit;
        }
    }

    public static function ruta()
    {
        //return "http://localhost/evds2023/";
        return "http://181.204.219.154:3396/evds2023/";
    }
}
