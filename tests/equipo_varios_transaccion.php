<?php
/** PostgreSQL de pruebas únicamente. Crea y elimina su propio esquema, sin cargar conexion.php. */
if (!getenv('JORNADAS_TEST_DSN')) {
    fwrite(STDERR, "Defina JORNADAS_TEST_DSN hacia una base de pruebas.\n");
    exit(1);
}
$schema = 'test_equipo_' . bin2hex(random_bytes(5));
class Conectar {
    protected function Conexion() {
        global $schema;
        $db = new PDO(getenv('JORNADAS_TEST_DSN'), null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $db->exec('SET search_path TO ' . $schema);
        return $db;
    }
}
require_once __DIR__ . '/../models/Jornada.php';
function comprobar($condicion, $mensaje) {
    if (!$condicion) throw new RuntimeException($mensaje);
    echo "OK: $mensaje\n";
}
function falla($accion) {
    try { $accion(); } catch (RuntimeException | InvalidArgumentException $e) { return $e->getMessage(); }
    throw new RuntimeException('La operación debía fallar.');
}
$db = new PDO(getenv('JORNADAS_TEST_DSN'), null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$db->exec('CREATE SCHEMA ' . $schema);
$db->exec('SET search_path TO ' . $schema);
try {
    $db->exec(<<<'SQL'
CREATE TABLE empleados (id_empl integer PRIMARY KEY, esta_empl integer, cedu_empl text, nomb_empl text);
INSERT INTO empleados VALUES (1,1,'1','Uno'),(2,1,'2','Dos'),(3,1,'3','Jefe'),(4,1,'4','Ajeno');
CREATE TABLE empleado_jefe (empleado_id integer, jefe_id integer, ej_estado integer);
INSERT INTO empleado_jefe VALUES (1,3,1),(2,3,1);
CREATE TABLE jornada_estados (je_id serial PRIMARY KEY, je_codigo text, je_nombre text, je_estado integer DEFAULT 1);
INSERT INTO jornada_estados (je_codigo,je_nombre) VALUES ('BORRADOR','Borrador');
CREATE TABLE jornadas_trabajo (
 jornada_id bigserial PRIMARY KEY, empleado_id integer REFERENCES empleados,
 jornada_inicio timestamp, jornada_fin timestamp, jornada_minutos_ordinarios integer,
 jornada_ubicacion text, jornada_actividad text, jornada_observaciones text,
 jornada_origen text, jornada_estado_id integer REFERENCES jornada_estados, jornada_creado_por integer,
 jornada_version integer DEFAULT 1, jornada_fecha_actualizacion timestamp DEFAULT CURRENT_TIMESTAMP,
 CHECK (NOT (empleado_id = 2 AND jornada_actividad = 'FALLAR'))
);
CREATE TABLE jornada_auditoria (
 jaud_id bigserial PRIMARY KEY, jornada_id bigint REFERENCES jornadas_trabajo,
 jaud_accion text, jaud_estado_anterior text, jaud_estado_nuevo text,
 jaud_datos_anteriores jsonb, jaud_datos_nuevos jsonb, jaud_motivo text, jaud_usuario_id integer
);
SQL
    );
    $modelo = new Jornada();
    $fila = ['jornada_id' => null, 'fecha' => '2026-10-19', 'inicio' => '2026-10-19 08:00:00',
        'fin' => '2026-10-19 17:00:00', 'minutos_ordinarios' => 480, 'ubicacion' => 'La Toscana',
        'actividad' => 'FALLAR', 'observaciones' => ''];
    $mensaje = falla(fn() => $modelo->guardar_jornada_equipo_varios([2, 1], 3, 9, $fila));
    comprobar(strpos($mensaje, 'ID 2') !== false, 'Identifica el segundo empleado que falló');
    comprobar((int)$db->query('SELECT count(*) FROM jornadas_trabajo')->fetchColumn() === 0, 'Rollback elimina también la jornada creada para el primer empleado');
    comprobar((int)$db->query('SELECT count(*) FROM jornada_auditoria')->fetchColumn() === 0, 'Rollback revierte la auditoría del primer empleado');

    $fila['actividad'] = 'Registro común';
    falla(fn() => $modelo->guardar_jornada_equipo_varios([1, 4], 3, 9, $fila));
    comprobar((int)$db->query('SELECT count(*) FROM jornadas_trabajo')->fetchColumn() === 0, 'Empleado ajeno al jefe revierte el registro completo');
    $resultado = $modelo->guardar_jornada_equipo_varios([2, 1, 1], 3, 9, $fila);
    comprobar($resultado['creadas'] === 2, 'Commit único registra a todos y no duplica empleados repetidos');
    comprobar((int)$db->query('SELECT count(*) FROM jornada_auditoria')->fetchColumn() === 2, 'Cada jornada confirmada en la transacción conserva su auditoría');

    $conflicto = $fila;
    $conflicto['inicio'] = '2026-10-20 08:00:00';
    $conflicto['fin'] = '2026-10-20 17:00:00';
    $modelo->guardar_borradores_equipo_masivo(2, 3, 9, [$conflicto]);
    $antes = (int)$db->query('SELECT count(*) FROM jornada_auditoria')->fetchColumn();
    falla(fn() => $modelo->guardar_jornada_equipo_varios([1, 2], 3, 9, $conflicto));
    comprobar((int)$db->query("SELECT count(*) FROM jornadas_trabajo WHERE empleado_id=1 AND jornada_inicio::date='2026-10-20'")->fetchColumn() === 0,
        'Superposición del segundo empleado revierte el registro del primero');
    comprobar((int)$db->query('SELECT count(*) FROM jornada_auditoria')->fetchColumn() === $antes, 'El fallo por superposición tampoco deja auditorías parciales');

    $edicion = $fila;
    $edicion['jornada_id'] = $resultado['pasos'][0]['jornada_id'];
    $edicion['actividad'] = 'Edición solo empleado uno';
    $modelo->guardar_borradores_equipo_masivo(1, 3, 9, [$edicion]);
    comprobar($db->query("SELECT jornada_actividad FROM jornadas_trabajo WHERE empleado_id=2 AND jornada_inicio::date='2026-10-19'")->fetchColumn() === 'Registro común',
        'Editar una jornada mantiene intacta la del otro empleado');
    falla(fn() => $modelo->guardar_jornada_equipo_varios([1, 2], 3, 9, $edicion));
    falla(fn() => $modelo->guardar_jornada_equipo_varios([], 3, 9, $fila));
    falla(fn() => $modelo->guardar_jornada_equipo_varios([1, -1], 3, 9, $fila));
    comprobar(count($modelo->listar_ubicaciones_jornada()) === 6, 'El servidor dispone de las seis obras solicitadas');
} finally {
    $db->exec('DROP SCHEMA ' . $schema . ' CASCADE');
}
