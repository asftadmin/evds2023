<?php
// Prueba real optativa: solo lecturas de usuarios/Graph y sesiones nuevas que se eliminan al salir.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (!in_array('--live', $argv, true)) { fwrite(STDERR, "Uso: php tests/microsoft365_integracion.php --live\n"); exit(1); }
// Retiene la salida hasta destruir las sesiones; PHP no permite cambiarlas tras enviar encabezados.
ob_start();
$salida = 0;

// No usa sesiones preexistentes ni modifica el inicio de sesión de la aplicación.
eval(str_replace('session_start();', '', substr(file_get_contents(__DIR__ . '/../config/conexion.php'), 5)));
class ConexionIntegracion365 extends Conectar {
    /** Obtiene la conexión vigente; solo se realizan SELECT en esta prueba. */
    public function abrir() { return parent::Conexion(); }
}
/** Solicita un endpoint local con la sesión desechable; nunca imprime cookies ni cuerpos. */
function http365($ruta, $sesion = null, $datos = null)
{
    $curl = curl_init('http://127.0.0.1/evds2023/' . $ruta);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 180, CURLOPT_FOLLOWLOCATION => false]);
    if ($sesion !== null) { curl_setopt($curl, CURLOPT_COOKIE, session_name() . '=' . $sesion); }
    if ($datos !== null) { curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($datos)); }
    $texto = curl_exec($curl);
    $codigo = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $tipo = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
    curl_close($curl);
    if ($texto === false) { throw new RuntimeException('No se pudo contactar Apache local.'); }
    return [$codigo, $tipo, $texto];
}
/** Verifica estado y JSON limpio del controlador real. */
function json365($esperado, $respuesta)
{
    if ($respuesta[0] !== $esperado || !str_starts_with($respuesta[1] ?? '', 'application/json')) {
        throw new RuntimeException('Estado HTTP o Content-Type inesperado.');
    }
    $json = json_decode($respuesta[2], true, 512, JSON_THROW_ON_ERROR);
    if ($json['status'] !== ($esperado === 200 ? 'success' : 'error')) { throw new RuntimeException('Estado JSON inesperado.'); }
    return $json;
}
/** Emite una sesión administrativa/empleado de prueba sin alterar registros de usuarios. */
function sesion365($usuario, $csrf)
{
    $id = session_create_id('hu02-');
    session_id($id); session_start();
    $_SESSION = $usuario + ['microsoft365_csrf' => $csrf];
    session_write_close();
    return $id;
}
$sesiones = [];
try {
    $db = (new ConexionIntegracion365())->abrir();
    $usuarios = $db->query("SELECT DISTINCT ON (r.rol_nomb = 'Administrador') u.user_id,u.user_rol,u.user_nick,
            e.id_empl,e.nomb_empl,e.user_empl FROM public.usuarios u
        JOIN public.rol r ON r.rol_id=u.user_rol JOIN public.empleados e ON e.user_empl=u.user_id
        WHERE e.esta_empl=1 AND r.rol_esta=1 ORDER BY (r.rol_nomb = 'Administrador') DESC,u.user_id,e.id_empl")->fetchAll(PDO::FETCH_ASSOC);
    if (count($usuarios) !== 2) { throw new RuntimeException('Se requieren usuarios de ambos roles para la prueba.'); }
    $csrf = bin2hex(random_bytes(32));
    $admin = $sesiones[] = sesion365($usuarios[0], $csrf);
    $empleado = $sesiones[] = sesion365($usuarios[1], $csrf);
    $hashAntes = hash('sha256', json_encode($db->query('SELECT user_id,user_graph_id FROM public.usuarios ORDER BY user_id')->fetchAll(PDO::FETCH_ASSOC)));
    json365(401, http365('controller/microsoft365.php?op=consultar'));
    json365(403, http365('controller/microsoft365.php?op=consultar', $empleado, ['csrf_token' => $csrf]));
    json365(403, http365('controller/microsoft365.php?op=guardar', $admin, ['csrf_token' => 'incorrecto']));
    json365(405, http365('controller/microsoft365.php?op=editar', $admin));
    if (http365('view/MntMicrosoft365/sincronizacion.php', $empleado)[0] !== 403) { throw new RuntimeException('La vista permitió un rol sin acceso.'); }
    $vista = http365('view/MntMicrosoft365/sincronizacion.php', $admin);
    if ($vista[0] !== 200 || !str_contains($vista[2], 'id="m365_tabla"')) { throw new RuntimeException('La vista administrativa no pudo cargar.'); }
    $consulta = json365(200, http365('controller/microsoft365.php?op=consultar', $admin, ['csrf_token' => $csrf]));
    json365(200, http365('controller/microsoft365.php?op=consultar', $admin, ['csrf_token' => $csrf]));
    $hashDespues = hash('sha256', json_encode($db->query('SELECT user_id,user_graph_id FROM public.usuarios ORDER BY user_id')->fetchAll(PDO::FETCH_ASSOC)));
    if ($hashAntes !== $hashDespues) { throw new RuntimeException('Las vinculaciones cambiaron durante la prueba de lectura.'); }
    $caso = array_values(array_filter($consulta['data']['registros'], static function ($fila) { return in_array($fila['user_id'], [10,28], true); }));
    echo 'OK: Apache/PHP, acceso administrativo, rechazo sin permiso/CSRF/POST, vista y consulta real repetida sin cambios en vinculaciones.', PHP_EOL;
    echo 'Indicadores: ', json_encode($consulta['data']['indicadores']), PHP_EOL;
    echo 'Estados caso 10/28: ', json_encode(array_column($caso, 'estado', 'user_id')), PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, 'Validación real fallida: ' . $e->getMessage() . PHP_EOL);
    $salida = 1;
} finally {
    // Elimina únicamente las sesiones creadas por esta prueba, nunca sesiones del usuario.
    foreach ($sesiones as $id) {
        session_id($id); session_start(); $_SESSION = []; session_destroy();
    }
}
ob_end_flush();
exit($salida);
