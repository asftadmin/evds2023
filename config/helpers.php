<?php
// ──────────────────────────────────────────────────────────────
// FECHA A LETRAS
// Recibe: 'Y-m-d' o 'Y-m-d H:i:s' o 'dd/mm/yyyy'
// Retorna: "15 de marzo de 2026"
// ──────────────────────────────────────────────────────────────
function fechaALetras($fecha_str) {

    if (empty($fecha_str)) return '';

    // Soportar formato DD/MM/YYYY
    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $fecha_str, $m)) {
        $fecha_str = $m[3] . '-' . $m[2] . '-' . $m[1];
    }

    $meses = [
        1  => 'enero',
        2  => 'febrero',
        3  => 'marzo',
        4  => 'abril',
        5  => 'mayo',
        6  => 'junio',
        7  => 'julio',
        8  => 'agosto',
        9  => 'septiembre',
        10 => 'octubre',
        11 => 'noviembre',
        12 => 'diciembre'
    ];

    $ts   = strtotime($fecha_str);
    $dia  = (int) date('d', $ts);
    $mes  = (int) date('m', $ts);
    $anio = (int) date('Y', $ts);

    return $dia . ' de ' . $meses[$mes] . ' de ' . $anio;
}


// ──────────────────────────────────────────────────────────────
// NÚMERO A LETRAS
// Recibe: float (ej: 1423500.00)
// Retorna: "UN MILLÓN CUATROCIENTOS VEINTITRÉS MIL QUINIENTOS PESOS M/CTE"
// ──────────────────────────────────────────────────────────────
function numeroALetras($numero) {

    if ($numero == 0) return 'CERO PESOS M/CTE';

    $numero   = abs(round($numero, 2));
    $entero   = (int) $numero;
    $decimals = round(($numero - $entero) * 100);

    $letras = _convertirEntero($entero);

    if ($decimals > 0) {
        $letras .= ' CON ' . str_pad($decimals, 2, '0', STR_PAD_LEFT) . '/100';
    }

    return strtoupper($letras) . ' PESOS M/CTE';
}

// ── Convierte entero a letras (interno) ───────────────────────
function _convertirEntero($n) {

    if ($n == 0)  return 'CERO';
    if ($n < 0)   return 'MENOS ' . _convertirEntero(abs($n));

    $unidades = [
        '',
        'UN',
        'DOS',
        'TRES',
        'CUATRO',
        'CINCO',
        'SEIS',
        'SIETE',
        'OCHO',
        'NUEVE',
        'DIEZ',
        'ONCE',
        'DOCE',
        'TRECE',
        'CATORCE',
        'QUINCE',
        'DIECISÉIS',
        'DIECISIETE',
        'DIECIOCHO',
        'DIECINUEVE',
        'VEINTE'
    ];

    $decenas = [
        '',
        '',
        'VEINTI',
        'TREINTA',
        'CUARENTA',
        'CINCUENTA',
        'SESENTA',
        'SETENTA',
        'OCHENTA',
        'NOVENTA'
    ];

    $centenas = [
        '',
        'CIENTO',
        'DOSCIENTOS',
        'TRESCIENTOS',
        'CUATROCIENTOS',
        'QUINIENTOS',
        'SEISCIENTOS',
        'SETECIENTOS',
        'OCHOCIENTOS',
        'NOVECIENTOS'
    ];

    $resultado = '';

    // Millones
    if ($n >= 1000000) {
        $mill = (int)($n / 1000000);
        if ($mill == 1) {
            $resultado .= 'UN MILLÓN ';
        } else {
            $resultado .= _convertirEntero($mill) . ' MILLONES ';
        }
        $n %= 1000000;
    }

    // Miles
    if ($n >= 1000) {
        $miles = (int)($n / 1000);
        if ($miles == 1) {
            $resultado .= 'MIL ';
        } else {
            $resultado .= _convertirEntero($miles) . ' MIL ';
        }
        $n %= 1000;
    }

    // Centenas
    if ($n >= 100) {
        $c = (int)($n / 100);
        if ($n == 100) {
            $resultado .= 'CIEN ';
        } else {
            $resultado .= $centenas[$c] . ' ';
        }
        $n %= 100;
    }

    // Decenas y unidades
    if ($n > 20) {
        $d = (int)($n / 10);
        $u = $n % 10;
        if ($u == 0) {
            $resultado .= $decenas[$d] . ' ';
        } else {
            // VEINTI... va junto
            if ($d == 2) {
                $resultado .= $decenas[$d] . strtoupper($unidades[$u]) . ' ';
            } else {
                $resultado .= $decenas[$d] . ' Y ' . $unidades[$u] . ' ';
            }
        }
    } elseif ($n > 0) {
        $resultado .= $unidades[$n] . ' ';
    }

    return trim($resultado);
}

// ── Convertir fechas DD/MM/YYYY → YYYY-MM-DD para PostgreSQL ──
function convertirFecha($fecha) {
    if (empty($fecha) || $fecha == 'NULL') return null;
    $d = DateTime::createFromFormat('d/m/Y', trim($fecha));
    return $d ? $d->format('Y-m-d') : null;
}
