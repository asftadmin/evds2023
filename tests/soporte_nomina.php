<?php

// Pruebas aisladas del motor: no conectan ni modifican datos reales.
class Conectar {}
require_once __DIR__ . '/../models/SoporteNomina.php';
require_once __DIR__ . '/../models/SoporteNominaTarifas.php';
$verificaciones = 0;

// Detiene la ejecución ante una regla incumplida.
function verificarSoporte($condicion, $mensaje)
{
    global $verificaciones;
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
    $verificaciones++;
}

// Compara valores sin confundir precisión de coma flotante con redondeo prematuro.
function verificarNumeroSoporte($actual, $esperado, $mensaje)
{
    verificarSoporte(abs($actual - $esperado) < 0.000001, $mensaje);
}

// Comprueba el rechazo de entradas inválidas sin acceder a PostgreSQL.
function rechazarSoporte($accion)
{
    try {
        $accion();
    } catch (InvalidArgumentException $e) {
        verificarSoporte(true, 'Entrada rechazada');
        return;
    }
    throw new RuntimeException('Se aceptó una entrada inválida.');
}

// Verifica el umbral estricto en ambos grupos especiales y el signo de Otros.
foreach ([4, 6] as $grupo) {
    $r = SoporteNomina::calcularAuxilio(900000, 60000, 40000, $grupo);
    verificarNumeroSoporte($r['dias_alimentacion'], 15, '15 exactos no se dividen');
    verificarNumeroSoporte($r['dias_hospedaje'], 0, '15 exactos sin hospedaje');
    $r = SoporteNomina::calcularAuxilio(960000, 60000, 40000, $grupo);
    verificarNumeroSoporte($r['dias_alimentacion'], 8, '16 días se dividen');
    verificarNumeroSoporte($r['dias_hospedaje'], 8, 'Mitad de hospedaje');
    verificarNumeroSoporte($r['valor_alimentacion'], 480000, 'Valor alimentación');
    verificarNumeroSoporte($r['valor_hospedaje'], 320000, 'Valor hospedaje');
    verificarNumeroSoporte($r['otros'], 160000, 'Otros descuenta alimentación y hospedaje');
    $r = SoporteNomina::calcularAuxilio(900000.01, 60000, 40000, $grupo);
    verificarSoporte($r['dias_hospedaje'] > 0, 'La menor fracción por encima de 15 sí divide');
    $r = SoporteNomina::calcularAuxilio(1000000, 60000, 40000, $grupo);
    verificarNumeroSoporte($r['valor_alimentacion'], 500000, 'No usa 8,33 para calcular alimentación');
    verificarNumeroSoporte($r['valor_hospedaje'], 333333.33, 'Redondea el importe final sin redondear días');
}

// Regresión del ejemplo confirmado: los centavos de Otros concilian con las columnas visibles.
$r = SoporteNomina::calcularAuxilio(1253494.50, 60000, 50000, 4);
verificarNumeroSoporte($r['valor_alimentacion'], 626747.25, 'Alimentación del ejemplo');
verificarNumeroSoporte($r['valor_hospedaje'], 522289.38, 'Hospedaje del ejemplo');
verificarNumeroSoporte($r['otros'], 104457.87, 'Saldo esperado del cuadro rojo');

// Los demás grupos, incluido Obra Administración, siguen la regla general.
foreach ([1, 2, 3, 5, 7, 8, 9, null] as $grupo) {
    $r = SoporteNomina::calcularAuxilio(1200000, 60000, 40000, $grupo);
    verificarNumeroSoporte($r['dias_alimentacion'], 20, 'Otros grupos conservan todos los días');
    verificarNumeroSoporte($r['dias_hospedaje'], 0, 'Otros grupos sin hospedaje');
    verificarNumeroSoporte($r['otros'], 0, 'Otros sin hospedaje');
}

// Valida importes, periodos e identificadores antes de cualquier escritura.
foreach ([0, -1, NAN, INF, 'incorrecto'] as $valor) {
    rechazarSoporte(function () use ($valor) { SoporteNomina::calcularAuxilio($valor, 60000, 40000, 4); });
    rechazarSoporte(function () use ($valor) { SoporteNominaTarifas::validarDatos(2026, 9, $valor, 40000); });
}
foreach (['', '2026x', [], 0] as $anio) {
    rechazarSoporte(function () use ($anio) { SoporteNomina::validarPeriodo($anio, 9); });
}
foreach ([0, 13, '9x', []] as $mes) {
    rechazarSoporte(function () use ($mes) { SoporteNomina::validarPeriodo(2026, $mes); });
}
foreach ([[], ['1 OR 1=1'], [0], [-1], [2147483648], [[1]]] as $ids) {
    rechazarSoporte(function () use ($ids) { (new SoporteNomina())->procesarSeleccionados($ids, 2026, 9); });
}
rechazarSoporte(function () { SoporteNominaTarifas::validarDatos(2026, 9, '0.001', 40000); });
SoporteNominaTarifas::validarDatos(2026, 9, '60000.25', '40000.50');
echo "OK: $verificaciones verificaciones de Soporte Nómina.\n";
