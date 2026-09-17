<?php
/**
 * Integración con PostgreSQL: usa un esquema desechable y nunca carga conexion.php.
 * Ejecución: JORNADAS_TEST_DSN="pgsql:host=127.0.0.1;port=...;dbname=...;user=..." php tests/jornadas_registro.php
 */
if (!getenv('JORNADAS_TEST_DSN')) {
    fwrite(STDERR, "Defina JORNADAS_TEST_DSN hacia una base de pruebas.\n");
    exit(1);
}
$worker = ($argv[1] ?? '') === 'worker';
$schema = $worker ? ($argv[2] ?? '') : 'test_jornadas_' . bin2hex(random_bytes(5));
if (!preg_match('/^test_jornadas_[a-f0-9]+$/', $schema)) {
    throw new RuntimeException('Esquema de pruebas inválido.');
}

class Conectar {
    protected $dbh;
    protected function Conexion() {
        global $schema, $worker;
        $this->dbh = new PDO(getenv('JORNADAS_TEST_DSN'), null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $this->dbh->exec('SET search_path TO ' . $schema);
        if ($worker) {
            $this->dbh->exec("SET application_name = 'jornadas_test_worker'");
        }
        return $this->dbh;
    }
    public function set_names() {}
}
require_once __DIR__ . '/../models/Jornada.php';

function guardar($modelo, $fecha, $id = null, $empleado = 1, $entrada = '08:00', $salida = '17:00', $ubicacion = 'Sede principal') {
    return $modelo->guardar_borrador_propio(
        $id, $empleado, 1, "$fecha $entrada:00", "$fecha $salida:00", 480,
        $ubicacion, 'Actividad de prueba', 'Observación de prueba'
    );
}
function verificar($condicion, $mensaje) {
    if (!$condicion) {
        throw new RuntimeException('FALLO: ' . $mensaje);
    }
    echo "OK: $mensaje\n";
}
function rechazar($accion, $mensaje) {
    try {
        $accion();
    } catch (PDOException $e) {
        throw $e;
    } catch (RuntimeException | InvalidArgumentException $e) {
        verificar(true, $mensaje);
        return;
    }
    verificar(false, $mensaje);
}

$modelo = new Jornada();
if ($worker) {
    try {
        guardar($modelo, '2026-09-15');
        echo 'CREADA';
    } catch (RuntimeException $e) {
        echo 'BLOQUEADA';
    }
    exit;
}

$db = new PDO(getenv('JORNADAS_TEST_DSN'), null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$db->exec('CREATE SCHEMA ' . $schema);
$db->exec('SET search_path TO ' . $schema);
try {
    $db->exec(<<<'SQL'
CREATE TABLE jornada_estados (je_id serial PRIMARY KEY, je_codigo varchar(40) UNIQUE, je_nombre varchar(100), je_estado integer DEFAULT 1);
INSERT INTO jornada_estados (je_codigo, je_nombre) VALUES
('BORRADOR','Borrador'),('PENDIENTE_APROBACION','Pendiente de aprobación'),('APROBADO','Aprobado'),
('RECHAZADO','Rechazado'),('PENDIENTE_CORRECCION','Pendiente de corrección'),('CORREGIDO','Corregido');
CREATE TABLE empleados (id_empl integer PRIMARY KEY, esta_empl integer DEFAULT 1, cedu_empl varchar(30), nomb_empl varchar(100));
INSERT INTO empleados VALUES (1,1,'111','Empleado uno'),(2,1,'222','Empleado dos'),(3,1,'333','Jefe');
CREATE TABLE empleado_jefe (empleado_id integer, jefe_id integer, ej_estado integer DEFAULT 1);
INSERT INTO empleado_jefe VALUES (1,3,1);
CREATE TABLE jornadas_trabajo (
 jornada_id bigserial PRIMARY KEY, empleado_id integer REFERENCES empleados,
 jornada_inicio timestamp NOT NULL, jornada_fin timestamp NOT NULL,
 jornada_minutos_ordinarios integer, jornada_ubicacion varchar(250), jornada_actividad text,
 jornada_observaciones text, jornada_origen varchar(30), jornada_estado_id integer REFERENCES jornada_estados,
 jornada_creado_por integer, jornada_fecha_actualizacion timestamp DEFAULT CURRENT_TIMESTAMP,
 jornada_version integer DEFAULT 1, jornada_inconsistente integer DEFAULT 0, jornada_inconsistencia_detalle text,
 CHECK (jornada_fin > jornada_inicio)
);
CREATE TABLE jornada_auditoria (
 jaud_id bigserial PRIMARY KEY, jornada_id bigint REFERENCES jornadas_trabajo,
 jaud_accion varchar(60), jaud_estado_anterior varchar(40), jaud_estado_nuevo varchar(40),
 jaud_datos_anteriores jsonb, jaud_datos_nuevos jsonb, jaud_motivo text,
 jaud_usuario_id integer, jaud_fecha timestamp DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE jornada_aprobaciones (
 jap_id bigserial PRIMARY KEY, jornada_id bigint REFERENCES jornadas_trabajo,
 jap_etapa varchar(40), jap_decision varchar(30), jap_usuario_id integer, jap_empleado_id integer, jap_motivo text
);
SQL
    );
    $migracion = str_replace('public.', $schema . '.', file_get_contents(__DIR__ . '/../database/migrations/20260916_anulacion_jornadas.sql'));
    $db->exec($migracion);
    $db->exec($migracion);
    verificar((int)$db->query("SELECT COUNT(*) FROM jornada_estados WHERE je_codigo = 'ANULADO'")->fetchColumn() === 1, 'Migración repetible sin duplicar estados');

    $agosto = guardar($modelo, '2026-08-15');
    verificar((int)$modelo->obtener_jornada_fecha(1, '2026-08-15')['jornada_id'] === $agosto, 'Fecha encontrada fuera del periodo de septiembre');
    verificar(count($modelo->listar_mis_jornadas(1, '2026-09-01', '2026-09-30')) === 0, 'El filtro del historial no altera el bloqueo global');
    rechazar(function () use ($modelo) { guardar($modelo, '2026-08-15', null, 1, '18:00', '19:00'); }, 'Mismo día con horario distinto bloqueado');
    verificar(guardar($modelo, '2026-08-15', $agosto) === $agosto, 'Editar el mismo borrador no genera falso duplicado');
    verificar(guardar($modelo, '2026-08-15', null, 2) > 0, 'Otro empleado puede usar la misma fecha');
    rechazar(function () use ($modelo, $agosto) { $modelo->anular_borrador_propio($agosto, 2, 1, 'Ajena'); }, 'No se puede anular una jornada ajena');
    rechazar(function () use ($modelo, $agosto) { $modelo->anular_borrador_propio($agosto, 1, 1, ' '); }, 'Anulación exige motivo');
    $modelo->anular_borrador_propio($agosto, 1, 1, 'Fecha equivocada');
    verificar(!$modelo->obtener_jornada_fecha(1, '2026-08-15'), 'Anulada libera la fecha');
    $historial = $modelo->listar_mis_jornadas(1);
    verificar($historial[0]['estado_codigo'] === 'ANULADO' && $historial[0]['anulacion_motivo'] === 'Fecha equivocada', 'Anulación y motivo permanecen en el historial');
    verificar((int)$db->query("SELECT jaud_usuario_id FROM jornada_auditoria WHERE jaud_accion = 'ANULAR_BORRADOR'")->fetchColumn() === 1, 'Auditoría conserva el responsable');
    $reemplazo = guardar($modelo, '2026-08-15', null, 1, '08:00', '17:00', 'Obras varias');
    $modelo->enviar_aprobacion_propia($reemplazo, 1, 1);
    rechazar(function () use ($modelo, $reemplazo) { $modelo->anular_borrador_propio($reemplazo, 1, 1, 'Pendiente'); }, 'No se puede anular una pendiente');
    $modelo->decidir_jornada_jefe($reemplazo, 3, 1, 'RECHAZAR', 'Corregir fecha');
    verificar(!$modelo->obtener_jornada_fecha(1, '2026-08-15'), 'Rechazada libera la fecha');
    guardar($modelo, '2026-08-15');

    $equipo = $modelo->guardar_jornada_equipo_aprobada(1, 3, 1, '2026-08-16 08:00:00', '2026-08-16 17:00:00', 480, 'Sede principal', 'Equipo', '');
    rechazar(function () use ($modelo) { guardar($modelo, '2026-08-16', null, 1, '18:00', '19:00'); }, 'Registro del jefe bloquea al trabajador por fecha');
    rechazar(function () use ($modelo) { $modelo->guardar_jornada_equipo_aprobada(1, 3, 1, '2026-08-15 18:00:00', '2026-08-15 19:00:00', 60, 'Sede principal', 'Equipo', ''); }, 'Registro del trabajador bloquea al jefe por fecha');
    rechazar(function () use ($modelo, $equipo) { $modelo->anular_borrador_propio($equipo, 1, 1, 'Aprobada'); }, 'No se puede anular una aprobada');
    rechazar(function () use ($modelo) { guardar($modelo, '2026-08-17', null, 1, '08:00', '17:00', 'Inventada'); }, 'Servidor valida las dos ubicaciones');
    $vieja = guardar($modelo, '2026-08-17');
    $db->exec("UPDATE jornadas_trabajo SET jornada_ubicacion = 'Ubicación anterior' WHERE jornada_id = $vieja");
    verificar(guardar($modelo, '2026-08-17', $vieja, 1, '08:00', '17:00', 'Ubicación anterior') === $vieja, 'Se conserva ubicación de borradores antiguos');

    $nocturna = $modelo->guardar_borrador_propio(null, 1, 1, '2026-08-18 22:00:00', '2026-08-19 06:00:00', 480, 'Obras varias', 'Noche', '');
    rechazar(function () use ($modelo) { guardar($modelo, '2026-08-19', null, 1, '05:00', '07:00'); }, 'Se mantiene control de superposición al cruzar medianoche');
    verificar(guardar($modelo, '2026-08-19') > 0, 'Fecha del día siguiente disponible si no hay cruce de horas');

    $uno = guardar($modelo, '2026-09-01');
    $dos = guardar($modelo, '2026-09-02');
    $ajena = guardar($modelo, '2026-09-03', null, 2);
    $resultado = $modelo->enviar_aprobacion_masiva([$uno, $dos, $ajena, $equipo, $agosto], 1, 1);
    verificar($resultado['enviados'] === [$uno, $dos] && count($resultado['fallidos']) === 3, 'Envío masivo parcial valida dueño y estado por registro');
    verificar($modelo->obtener_mi_jornada($ajena, 2)['estado_codigo'] === 'BORRADOR', 'Envío masivo no modifica registros ajenos');
    $reintento = $modelo->enviar_aprobacion_masiva([$uno, $dos], 1, 1);
    verificar(count($reintento['enviados']) === 0 && count($reintento['fallidos']) === 2, 'Reintento no duplica envíos');
    verificar((int)$db->query("SELECT COUNT(*) FROM jornada_auditoria WHERE jornada_id IN ($uno,$dos) AND jaud_accion = 'ENVIAR_APROBACION'")->fetchColumn() === 2, 'Una auditoría por envío efectivo');

    $modelo->enviar_aprobacion_propia($ajena, 2, 1);
    verificar(count($modelo->listar_pendientes_jefe(3, '2026-08-15', '2026-09-16', 2)) === 0, 'Filtro no expone pendientes de empleados ajenos al jefe');
    $db->exec('INSERT INTO empleado_jefe VALUES (2,3,1)');
    verificar(count($modelo->listar_pendientes_jefe(3, '2026-08-15', '2026-09-16')) === 3, 'Todos los empleados incluye los pendientes de ambos subordinados');
    $filtradas = $modelo->listar_pendientes_jefe(3, '2026-08-15', '2026-09-16', 2);
    verificar(count($filtradas) === 1 && (int)$filtradas[0]['jornada_id'] === $ajena, 'Filtro por subordinado devuelve únicamente sus jornadas');
    verificar(count($modelo->listar_pendientes_jefe(3, '2026-09-01', '2026-09-01', 1)) === 1, 'Filtro combina empleado y límites inclusivos del periodo');
    $db->exec('UPDATE empleado_jefe SET ej_estado = 0 WHERE empleado_id = 2');
    verificar(count($modelo->listar_pendientes_jefe(3, '2026-08-15', '2026-09-16', 2)) === 0, 'Relación inactiva no permite consultar al empleado');
    verificar(count($modelo->listar_subordinados_jefe(3)) === 1, 'Opciones del selector incluyen solo relaciones activas');

    // Dos procesos esperan el mismo bloqueo y luego intentan crear la misma fecha.
    $db->query('SELECT pg_advisory_lock(1)');
    $procesos = [];
    for ($i = 0; $i < 2; $i++) {
        $pipes = [];
        $proceso = proc_open([PHP_BINARY, __FILE__, 'worker', $schema], [1 => ['pipe','w'], 2 => ['pipe','w']], $pipes);
        $procesos[] = [$proceso, $pipes];
    }
    $limite = microtime(true) + 10;
    do {
        $esperando = (int)$db->query("SELECT COUNT(*) FROM pg_stat_activity WHERE application_name = 'jornadas_test_worker' AND wait_event = 'advisory'")->fetchColumn();
        if ($esperando === 2) { break; }
        usleep(20000);
    } while (microtime(true) < $limite);
    $db->query('SELECT pg_advisory_unlock(1)');
    $salidas = [];
    foreach ($procesos as [$proceso, $pipes]) {
        $salidas[] = stream_get_contents($pipes[1]);
        $errores = stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        if (proc_close($proceso) !== 0 || $errores !== '') {
            throw new RuntimeException('Error del proceso concurrente: ' . $errores);
        }
    }
    sort($salidas);
    verificar($esperando === 2 && $salidas === ['BLOQUEADA','CREADA'], 'Dos guardados simultáneos producen una sola jornada');
    echo "Todas las pruebas de jornadas pasaron.\n";
} finally {
    $db->query('SELECT pg_advisory_unlock_all()');
    $db->exec('DROP SCHEMA ' . $schema . ' CASCADE');
}
