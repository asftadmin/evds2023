<?php
// Pruebas CLI; --db utiliza exclusivamente un esquema desechable con tablas vacías.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
class Conectar {
    public static $db;
    /** Inyecta la conexión aislada sin tocar la configuración de producción. */
    protected function Conexion() { return self::$db; }
}
require_once __DIR__ . '/../models/Usuarios.php';
$checks = 0;

/** Detiene la suite ante una regla incumplida, sin mostrar datos de producción. */
function verificar365($condicion, $mensaje)
{
    global $checks;
    if (!$condicion) { throw new RuntimeException($mensaje); }
    $checks++;
}
/** Exige que el servidor rechace una solicitud inválida. */
function rechazar365($accion)
{
    try { $accion(); } catch (InvalidArgumentException | DomainException $e) { verificar365(true, 'Rechazo'); return; }
    throw new RuntimeException('Se aceptó una operación inválida.');
}
/** Genera identidades sintéticas UUID, sin usar cuentas de usuarios reales. */
function id365($n) { return sprintf('00000000-0000-4000-8000-%012d', $n); }
/** Cuenta Graph sintética con mail opcional y UPN independiente. */
function cuenta365($n, $mail, $upn = null)
{
    return (object) ['id' => id365($n), 'displayName' => 'Cuenta de prueba', 'mail' => $mail, 'userPrincipalName' => $upn ?? $mail];
}
/** Empleado sintético: id_empl y user_id son deliberadamente diferentes. */
function empleado365($n, $correo, $actual = null, $estado = 1)
{
    return ['id_empl' => 100 + $n, 'user_id' => $n, 'nomb_empl' => 'Prueba ' . $n,
        'email_empl' => $correo, 'user_graph_id' => $actual, 'esta_empl' => $estado];
}
/** Construye la precondición que la interfaz confirmó; el servidor la verifica otra vez. */
function entrada365($n, $id, $actual = '')
{
    return ['id_empl' => 100 + $n, 'user_id' => $n, 'graph_id' => id365($id), 'actual' => $actual, 'correo' => 'prueba' . $n . '@asfaltart.com'];
}

// Todos los estados, dominio exacto, normalización y fallback con mail nulo.
$empleados = [empleado365(1, ' PRUEBA1@ASFALTART.COM '), empleado365(2, 'prueba2@asfaltart.com', id365(2)),
    empleado365(3, 'prueba3@asfaltart.com', id365(99)), empleado365(4, 'noexiste@asfaltart.com'),
    empleado365(5, 'otro@asfaltart.com.evil'), empleado365(6, 'inactivo@asfaltart.com', null, 0),
    empleado365(7, ''), empleado365(8, 'a@@asfaltart.com')];
$cuentas = [cuenta365(1, null, 'prueba1@asfaltart.com'), cuenta365(2, 'prueba2@asfaltart.com'), cuenta365(3, 'prueba3@asfaltart.com')];
$r = Usuario::comparar_microsoft365($empleados, $cuentas, []);
verificar365(array_column($r['registros'], 'estado') === ['Nuevo', 'Vinculado', 'Cambio detectado', 'Sin coincidencia'], 'Estados básicos');
verificar365($r['indicadores'] === ['total' => 4, 'vinculados' => 1, 'pendientes' => 1, 'revision' => 2], 'Tarjetas');
verificar365($r['registros'][0]['correo_microsoft'] === 'prueba1@asfaltart.com', 'Fallback UPN');

// Caso conocido 10/28: no hay reglas especiales por ID y ambos quedan bloqueados.
$duplicados = [empleado365(10, 'coordmantenimiento@asfaltart.com'), empleado365(28, ' COORDMANTENIMIENTO@asfaltart.com ')];
$r = Usuario::comparar_microsoft365($duplicados, [cuenta365(10, 'coordmantenimiento@asfaltart.com')], []);
verificar365(array_column($r['registros'], 'estado') === ['Conflicto', 'Conflicto'], 'Duplicado 10/28');
verificar365(array_unique(array_column($r['registros'], 'accion')) === ['revisar'], 'No guardar duplicados');
foreach ([
    [cuenta365(1, 'prueba1@asfaltart.com'), cuenta365(2, 'otro@asfaltart.com', 'prueba1@asfaltart.com')],
    [cuenta365(1, 'prueba1@asfaltart.com'), cuenta365(2, 'prueba1@asfaltart.com')]
] as $ambiguas) {
    verificar365(Usuario::comparar_microsoft365([$empleados[0]], $ambiguas, [])['registros'][0]['estado'] === 'Conflicto', 'Conflicto Graph mail/UPN');
}
$r = Usuario::comparar_microsoft365([$empleados[0]], [cuenta365(1, 'prueba1@asfaltart.com')], [['user_id' => 88, 'user_graph_id' => id365(1)]]);
verificar365($r['registros'][0]['estado'] === 'Conflicto', 'Propietario externo al listado');
$r = Usuario::comparar_microsoft365([empleado365(1, 'a@asfaltart.com'), empleado365(2, 'b@asfaltart.com')], [cuenta365(1, 'a@asfaltart.com', 'b@asfaltart.com')], []);
verificar365(array_column($r['registros'], 'estado') === ['Conflicto', 'Conflicto'], 'Una cuenta para dos correos locales');
$r = Usuario::comparar_microsoft365([empleado365(1, 'a@asfaltart.com'), empleado365(2, 'a@asfaltart.com', null, 0)], [cuenta365(1, 'a@asfaltart.com')], []);
verificar365(count($r['registros']) === 1 && $r['registros'][0]['estado'] === 'Conflicto', 'Duplicado inactivo requiere revisión');
foreach (['1 OR 1=1', [], 0, -1, '1.5', 2147483648] as $id) {
    $entrada = entrada365(1, 1); $entrada['user_id'] = $id;
    rechazar365(static function () use ($entrada) { Usuario::validar_vinculacion_microsoft365($entrada); });
}
rechazar365(static function () { Usuario::uuid_microsoft365('arbitrario'); });
rechazar365(static function () { Usuario::comparar_microsoft365([], [(object) ['id' => id365(1)]], []); });
verificar365(Usuario::validar_vinculacion_microsoft365(Usuario::validar_vinculacion_microsoft365(entrada365(1, 1)))['actual'] === null, 'Validación repetida');

if (in_array('--db', $argv, true)) {
    // Obtiene la conexión privada existente sin abrir sesión ni cambiar config/conexion.php.
    $conexion = str_replace(['class Conectar', 'session_start();'], ['class ConexionReal365', ''], file_get_contents(__DIR__ . '/../config/conexion.php'));
    eval(substr($conexion, 5));
    class ConexionPrueba365 extends ConexionReal365 {
        /** Solo la conexión; los modelos de la prueba usan otro esquema. */
        public function abrir() { return parent::Conexion(); }
    }
    $db = (new ConexionPrueba365())->abrir();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    Conectar::$db = $db;
    $esquema = 'hu02_test_' . bin2hex(random_bytes(6));
    $db->exec('CREATE SCHEMA ' . $esquema);
    try {
        // Copia columnas/constraints reales sin datos, defaults ni secuencias productivas.
        foreach (['usuarios', 'empleados', 'rol', 'menu', 'permisos', 'grupos_menu'] as $tabla) {
            $db->exec("CREATE TABLE $esquema.$tabla (LIKE public.$tabla INCLUDING CONSTRAINTS)");
        }
        foreach (['menu' => 'menu_id', 'permisos' => 'perm_id'] as $tabla => $columna) {
            $db->exec("CREATE SEQUENCE $esquema.seq_$tabla START 1000");
            $db->exec("ALTER TABLE $esquema.$tabla ALTER COLUMN $columna SET DEFAULT nextval('$esquema.seq_$tabla')");
        }
        foreach (['Usuarios' => 'Usuario', 'Menu' => 'Menu'] as $archivo => $clase) {
            $codigo = file_get_contents(__DIR__ . '/../models/' . $archivo . '.php');
            $codigo = str_replace(['class ' . $clase . ' ', 'public.', "table_schema = 'public'"],
                ['class ' . $clase . 'Prueba365 ', $esquema . '.', "table_schema = '$esquema'"], $codigo);
            eval(substr($codigo, 5));
        }
        $db->exec("INSERT INTO $esquema.rol (rol_id,rol_nomb,rol_esta) VALUES (77,'Administrador',1),(88,'Empleado',1)");
        $db->exec("INSERT INTO $esquema.grupos_menu (grme_id,grme_nomb,grme_icon,grme_orden,grme_esta) VALUES (55,'Administración','fas fa-cogs',1,1)");
        foreach ([1, 2, 3, 10, 28, 90, 91] as $n) {
            $stmt = $db->prepare("INSERT INTO $esquema.usuarios (user_id,user_nick,user_rol,user_graph_id) VALUES (?,?,?,CAST(? AS uuid))");
            $stmt->execute([$n, 'sintetico' . $n, $n === 90 ? 77 : 88, null]);
            $stmt = $db->prepare("INSERT INTO $esquema.empleados (id_empl,nomb_empl,email_empl,user_empl,esta_empl) VALUES (?,?,?,?,1)");
            $stmt->execute([100 + $n, 'Prueba ' . $n, in_array($n, [10,28]) ? 'coordmantenimiento@asfaltart.com' : "prueba$n@asfaltart.com", $n]);
        }
        $modelo = new UsuarioPrueba365();
        $modelo->verificar_esquema_microsoft365();
        $menu = new MenuPrueba365();
        $registro = $menu->registrar_microsoft365();
        verificar365($registro['creado'] && !$menu->registrar_microsoft365()['creado'], 'Registro menú idempotente');
        verificar365($modelo->autorizado_microsoft365(90) && !$modelo->autorizado_microsoft365(91), 'Permiso real sin IDs fijos');
        $cuentas = [cuenta365(1,'prueba1@asfaltart.com'), cuenta365(2,'prueba2@asfaltart.com'), cuenta365(10,'coordmantenimiento@asfaltart.com')];
        $antes = $db->query("SELECT * FROM $esquema.usuarios ORDER BY user_id")->fetchAll(PDO::FETCH_ASSOC);
        $modelo->consultar_microsoft365($cuentas); $modelo->consultar_microsoft365($cuentas);
        verificar365($antes === $db->query("SELECT * FROM $esquema.usuarios ORDER BY user_id")->fetchAll(PDO::FETCH_ASSOC), 'Consultar no escribe');
        $r = $modelo->vincular_microsoft365(90, 'guardar', entrada365(1,1), $cuentas);
        verificar365(!$r['sin_cambios'], 'Guardado válido');
        $despues = $db->query("SELECT * FROM $esquema.usuarios ORDER BY user_id")->fetchAll(PDO::FETCH_ASSOC);
        $esperado = $antes; $esperado[0]['user_graph_id'] = id365(1);
        verificar365($esperado === $despues, 'Solo cambia user_graph_id de un usuario');
        $xmin = $db->query("SELECT xmin::text FROM $esquema.usuarios WHERE user_id=1")->fetchColumn();
        verificar365($modelo->vincular_microsoft365(90, 'editar', entrada365(1,1,id365(1)), $cuentas)['sin_cambios'], 'Edición sin cambios');
        verificar365($xmin === $db->query("SELECT xmin::text FROM $esquema.usuarios WHERE user_id=1")->fetchColumn(), 'No ejecuta UPDATE innecesario');
        $cambio = [cuenta365(7,'prueba1@asfaltart.com')];
        $modelo->vincular_microsoft365(90, 'editar', entrada365(1,7,id365(1)), $cambio);
        verificar365($db->query("SELECT user_graph_id FROM $esquema.usuarios WHERE user_id=1")->fetchColumn() === id365(7), 'Sustitución verificada');
        rechazar365(static function () use ($modelo,$cuentas) { $modelo->vincular_microsoft365(91,'guardar',entrada365(2,2),$cuentas); });
        rechazar365(static function () use ($modelo,$cuentas) { $modelo->vincular_microsoft365(90,'guardar',entrada365(2,99),$cuentas); });
        rechazar365(static function () use ($modelo,$cambio) { $modelo->vincular_microsoft365(90,'editar',entrada365(1,7,id365(1)),$cambio); });
        rechazar365(static function () use ($modelo) { $modelo->vincular_microsoft365(90,'guardar',entrada365(3,3),[]); });
        foreach ([10,28] as $n) {
            $entrada = entrada365($n,10); $entrada['correo'] = 'coordmantenimiento@asfaltart.com';
            rechazar365(static function () use ($modelo,$entrada,$cuentas) { $modelo->vincular_microsoft365(90,'guardar',$entrada,$cuentas); });
        }
        // Cuenta ya asignada, incluso si Graph la devuelve para otro correo local.
        rechazar365(static function () use ($modelo) { $modelo->vincular_microsoft365(90,'guardar',entrada365(2,7),[cuenta365(7,'prueba2@asfaltart.com')]); });
        verificar365(!$db->inTransaction(), 'Rollback de validaciones');
        // Dos conexiones: el bloqueo impide que otro escritor se adelante a la validación.
        $otra = (new ConexionPrueba365())->abrir();
        $otra->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->beginTransaction();
        $db->exec("LOCK TABLE $esquema.usuarios IN SHARE ROW EXCLUSIVE MODE");
        $otra->beginTransaction();
        $otra->exec("SET LOCAL lock_timeout = '100ms'");
        try {
            $otra->exec("UPDATE $esquema.usuarios SET user_graph_id = '" . id365(7) . "' WHERE user_id=2");
            throw new RuntimeException('El escritor concurrente eludió el bloqueo.');
        } catch (PDOException $e) { verificar365($e->getCode() === '55P03', 'Bloqueo concurrente real'); }
        finally { $otra->rollBack(); $db->rollBack(); }
        verificar365($db->query("SELECT count(*) FROM $esquema.usuarios WHERE user_graph_id IS NOT NULL")->fetchColumn() == 1, 'Sin vinculaciones duplicadas o inesperadas');
    } finally {
        if ($db->inTransaction()) { $db->rollBack(); }
        $db->exec('DROP SCHEMA ' . $esquema . ' CASCADE');
    }
}
echo "OK: $checks verificaciones HU-02" . (in_array('--db', $argv, true) ? ' con PostgreSQL aislado' : ' del motor') . ".\n";
