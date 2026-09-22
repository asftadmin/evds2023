<?php

// Consulta, calcula y persiste soportes sin modificar jornadas ni liquidaciones.
class SoporteNomina extends Conectar
{
    // Reutiliza una conexión para que cada carga sea una única transacción.
    protected function conectarSoporte()
    {
        if (!$this->dbh) {
            $this->dbh = parent::Conexion();
            $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            parent::set_names();
        }
        return $this->dbh;
    }

    // Valida estrictamente mes y año antes de consultar o modificar un periodo.
    public static function validarPeriodo($anio, $mes)
    {
        if (!is_scalar($anio) || !is_scalar($mes)
            || !preg_match('/^[1-9][0-9]{3}$/D', (string) $anio)
            || !preg_match('/^(?:[1-9]|1[0-2])$/D', (string) $mes)) {
            throw new InvalidArgumentException('Debe seleccionar un periodo válido.');
        }
    }

    // Aplica la regla a grupos reales: 4 OBRAS y 6 OPERADORES DE MAQUINARIA.
    public static function calcularAuxilio($total, $alimentacion, $hospedaje, $grupo)
    {
        foreach ([$total, $alimentacion, $hospedaje] as $valor) {
            if (!is_numeric($valor) || !is_finite((float) $valor) || $valor <= 0) {
                throw new InvalidArgumentException('El auxilio y las tarifas deben ser mayores a cero.');
            }
        }
        // Conserva días sin redondear para calcular alimentación y hospedaje.
        $diasBase = $total / $alimentacion;
        $dividir = in_array((int) $grupo, [4, 6], true) && $diasBase > 15;
        $diasAlimentacion = $dividir ? $diasBase / 2 : $diasBase;
        $diasHospedaje = $dividir ? $diasBase / 2 : 0;
        // Conciliación monetaria: Otros descuenta los mismos centavos que se guardan y muestran.
        $valorAlimentacion = round($diasAlimentacion * $alimentacion, 2);
        $valorHospedaje = round($diasHospedaje * $hospedaje, 2);
        return [
            'total_auxilio' => (float) $total,
            'dias_alimentacion' => $diasAlimentacion,
            'dias_hospedaje' => $diasHospedaje,
            'valor_alimentacion' => $valorAlimentacion,
            'valor_hospedaje' => $valorHospedaje,
            'otros' => round(round($total, 2) - $valorAlimentacion - $valorHospedaje, 2)
        ];
    }

    // Valida exclusivamente cédulas; consulta nombre y grupo por sus relaciones reales.
    public function validarEmpleadosArchivo($filas)
    {
        $stmt = $this->conectarSoporte()->prepare('SELECT e.id_empl, e.nomb_empl, g.codi_grem
            FROM empleados e LEFT JOIN cargo c ON c.codi_carg = e.carg_empl
            LEFT JOIN grupoempleados g ON g.codi_grem = c.grem_carg
            WHERE e.cedu_empl = ?');
        $registros = $inconsistencias = [];
        foreach ($filas as $fila) {
            if ((float) $fila['valor'] <= 0) {
                continue;
            }
            $stmt->execute([$fila['cedu_empl']]);
            $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!$empleados) {
                $inconsistencias[] = [
                    'fila_excel' => $fila['fila_excel'], 'cedula' => $fila['cedu_empl'],
                    'valor' => $fila['valor'],
                    'observacion' => 'La cédula no coincide con empleados.cedu_empl.'
                ];
                continue;
            }
            // Rechaza una cédula ambigua sin asignarla al primer empleado.
            if (count($empleados) !== 1) {
                throw new InvalidArgumentException('La cédula ' . $fila['cedu_empl'] . ' está duplicada en empleados.');
            }
            $empleado = $empleados[0];
            $registros[] = [
                'empleado_id' => (int) $empleado['id_empl'], 'empleado' => $empleado['nomb_empl'],
                'grupo' => $empleado['codi_grem'], 'total_auxilio' => $fila['valor']
            ];
        }
        return ['registros' => $registros, 'inconsistencias' => $inconsistencias];
    }

    // Guarda solo nuevos borradores; cualquier fallo revierte toda la carga.
    public function guardarArchivo($filas, $anio, $mes)
    {
        self::validarPeriodo($anio, $mes);
        $db = $this->conectarSoporte();
        $db->beginTransaction();
        try {
            $resultado = $this->validarEmpleadosArchivo($filas);
            // Protege la tarifa frente a edición concurrente hasta terminar la carga.
            $stmt = $db->prepare('SELECT tarifa_alimentacion, tarifa_hospedaje FROM soporte_nomina_tarifas
                WHERE tarifa_anio = ? AND tarifa_mes = ? AND tarifa_estado = 1 FOR SHARE');
            $stmt->execute([$anio, $mes]);
            $tarifa = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$tarifa) {
                throw new DomainException('No existe configuración de tarifas para el periodo seleccionado.');
            }
            $insertar = $db->prepare("INSERT INTO soporte_nomina_detalle
                (empleado_id, soporte_anio, soporte_mes, soporte_total_auxilio,
                 soporte_dias_alimentacion, soporte_dias_hospedaje, soporte_valor_alimentacion,
                 soporte_valor_hospedaje, soporte_otros, soporte_estado, soporte_origen)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'ARCHIVO')
                ON CONFLICT (empleado_id, soporte_anio, soporte_mes) DO NOTHING RETURNING soporte_id");
            $guardados = $omitidos = 0;
            foreach ($resultado['registros'] as $registro) {
                $calculo = self::calcularAuxilio($registro['total_auxilio'], $tarifa['tarifa_alimentacion'],
                    $tarifa['tarifa_hospedaje'], $registro['grupo']);
                // PostgreSQL aplica la escala existente después de calcular todos los valores.
                $insertar->execute(array_merge([$registro['empleado_id'], $anio, $mes], array_values($calculo)));
                if ($insertar->fetchColumn() !== false) {
                    $guardados++;
                } else {
                    $omitidos++;
                }
            }
            $registros = $this->consultarPeriodo($anio, $mes);
            $db->commit();
            return ['registros' => $registros, 'inconsistencias' => $resultado['inconsistencias'],
                'guardados' => $guardados, 'omitidos' => $omitidos];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    // Corrige únicamente Otros en borradores ARCHIVO del periodo con la fórmula anterior.
    public function corregirOtrosBorradores($anio, $mes)
    {
        self::validarPeriodo($anio, $mes);
        $stmt = $this->conectarSoporte()->prepare("UPDATE soporte_nomina_detalle
            SET soporte_otros = soporte_total_auxilio - soporte_valor_alimentacion - soporte_valor_hospedaje
            WHERE soporte_anio = ? AND soporte_mes = ? AND soporte_estado = 1
              AND soporte_origen = 'ARCHIVO' AND soporte_valor_hospedaje > 0
              AND ABS(soporte_otros - (soporte_total_auxilio - soporte_valor_alimentacion + soporte_valor_hospedaje)) <= 0.01
              AND soporte_otros <> soporte_total_auxilio - soporte_valor_alimentacion - soporte_valor_hospedaje");
        $stmt->execute([$anio, $mes]);
        return $stmt->rowCount();
    }

    // Recupera resultados guardados sin recalcularlos con tarifas posteriores.
    public function consultarPeriodo($anio, $mes)
    {
        self::validarPeriodo($anio, $mes);
        $stmt = $this->conectarSoporte()->prepare("SELECT d.soporte_id AS id, e.nomb_empl AS empleado,
            d.soporte_total_auxilio AS total_auxilio, d.soporte_dias_alimentacion AS dias_alimentacion,
            d.soporte_dias_hospedaje AS dias_hospedaje, d.soporte_valor_alimentacion AS valor_alimentacion,
            d.soporte_valor_hospedaje AS valor_hospedaje, d.soporte_otros AS otros,
            CASE d.soporte_estado WHEN 1 THEN 'BORRADOR' WHEN 2 THEN 'CONTABILIZADO' ELSE 'NO DISPONIBLE' END AS estado
            FROM soporte_nomina_detalle d INNER JOIN empleados e ON e.id_empl = d.empleado_id
            WHERE d.soporte_anio = ? AND d.soporte_mes = ? ORDER BY e.nomb_empl, d.soporte_id");
        $stmt->execute([$anio, $mes]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Actualiza solo borradores seleccionados del periodo, sin reprocesar contabilizados.
    public function procesarSeleccionados($ids, $anio, $mes)
    {
        self::validarPeriodo($anio, $mes);
        if (!is_array($ids) || !$ids || count($ids) > 10000) {
            throw new InvalidArgumentException('Seleccione entre 1 y 10000 registros.');
        }
        foreach ($ids as $id) {
            if (!is_scalar($id) || !ctype_digit((string) $id) || $id <= 0 || $id > 2147483647) {
                throw new InvalidArgumentException('La selección contiene identificadores inválidos.');
            }
        }
        $ids = array_values(array_unique($ids));
        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->conectarSoporte()->prepare("UPDATE soporte_nomina_detalle SET soporte_estado = 2
            WHERE soporte_estado = 1 AND soporte_anio = ? AND soporte_mes = ? AND soporte_id IN ($marcas)");
        $stmt->execute(array_merge([$anio, $mes], $ids));
        return $stmt->rowCount();
    }
}
