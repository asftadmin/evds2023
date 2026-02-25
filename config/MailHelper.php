<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

class MailHelper {
    public static function enviar($destinatario, $asunto, $mensaje, $adjuntos = []) {
        $mail = new PHPMailer(true);

        $archivos_temp = [];

        try {
            $mail->isSMTP();
            $mail->Host       = 'outlook.office365.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'admin@asfaltart.com';
            $mail->Password   = '4dm1n.art22*';
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom('admin@asfaltart.com', 'Sistema Permisos');

            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';

            $mail->addAddress($destinatario);

            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body    = $mensaje;

            // Adjuntar archivos
            if (!empty($adjuntos) && is_array($adjuntos)) {
                foreach ($adjuntos as $ruta) {
                    // Siempre son rutas FTP, descargar temporalmente
                    $ruta_local = self::descargar_de_ftp($ruta);
                    if ($ruta_local) {
                        $mail->addAttachment($ruta_local);
                        $archivos_temp[] = $ruta_local;
                    }
                }
            }

            $mail->send();

            if (!empty($archivos_temp)) {
                foreach ($archivos_temp as $temp) {
                    if (file_exists($temp)) {
                        unlink($temp);
                    }
                }
            }
            /* 
            foreach ($archivos_temp as $temp) {
                if (file_exists($temp)) unlink($temp);
            } */


            return true;
        } catch (Exception $e) {
            error_log("Error enviando correo: " . $mail->ErrorInfo);
            return false;
        }
    }

    private static function descargar_de_ftp($ruta_remota) {
        $ftp_server = "172.16.5.3";
        $ftp_user   = "asfaltart_admin";
        $ftp_pass   = "s1st3m4s19..";

        $ftp = ftp_connect($ftp_server);
        if (!$ftp || !ftp_login($ftp, $ftp_user, $ftp_pass)) {
            return false;
        }
        ftp_pasv($ftp, true);

        $temp_dir = $_SERVER['DOCUMENT_ROOT'] . '/evds2023/public/temp_adjuntos/';
        if (!file_exists($temp_dir)) {
            mkdir($temp_dir, 0777, true);
        }

        $nombre_archivo = basename($ruta_remota);
        $ruta_local = $temp_dir . time() . '_' . $nombre_archivo;

        if (ftp_get($ftp, $ruta_local, $ruta_remota, FTP_BINARY)) {
            ftp_close($ftp);
            return $ruta_local;
        }

        ftp_close($ftp);
        return false;
    }
}
