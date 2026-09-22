<?php

// Administra exclusivamente la configuración mensual de soporte_nomina_tarifas.
class SoporteNominaTarifas extends Conectar
{
    // Reutiliza la conexión del modelo y convierte errores SQL en excepciones.
    protected function conectarTarifas()
    {
        if (!$this->dbh) {
            $this->dbh = parent::Conexion();
            $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            parent::set_names();
        }
        return $this->dbh;
    }

    // Lista todo el histórico sin filtrar por estado.
    public function listarTarifas()
    {
        return $this->conectarTarifas()->query('SELECT * FROM soporte_nomina_tarifas
            ORDER BY tarifa_anio DESC, tarifa_mes DESC')->fetchAll(PDO::FETCH_ASSOC);
    }

    // Consulta una configuración mediante su identificador real.
    public function mostrarTarifa($tarifaId)
    {
        $stmt = $this->conectarTarifas()->prepare('SELECT * FROM soporte_nomina_tarifas WHERE tarifa_id = ?');
        $stmt->execute([$tarifaId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Comprueba la unicidad del periodo, excluyendo la tarifa que se edita.
    public function existePeriodo($anio, $mes, $tarifaId = 0)
    {
        $stmt = $this->conectarTarifas()->prepare('SELECT 1 FROM soporte_nomina_tarifas
            WHERE tarifa_anio = ? AND tarifa_mes = ? AND tarifa_id <> ?');
        $stmt->execute([$anio, $mes, $tarifaId]);
        return (bool) $stmt->fetchColumn();
    }

    // Valida periodo e importes según la capacidad NUMERIC(12,2) existente.
    public static function validarDatos($anio, $mes, $alimentacion, $hospedaje)
    {
        if (!is_scalar($anio) || !is_scalar($mes)
            || !preg_match('/^[1-9][0-9]{3}$/D', (string) $anio)
            || !preg_match('/^(?:[1-9]|1[0-2])$/D', (string) $mes)) {
            throw new InvalidArgumentException('Debe seleccionar un periodo válido.');
        }
        foreach ([$alimentacion, $hospedaje] as $valor) {
            if (!is_scalar($valor) || !preg_match('/^[0-9]+(?:\.[0-9]{1,2})?$/D', (string) $valor)
                || (float) $valor <= 0 || (float) $valor > 9999999999.99) {
                throw new InvalidArgumentException('Las tarifas deben ser mayores a cero y tener como máximo dos decimales.');
            }
        }
    }

    // Crea una tarifa; la clave única también protege solicitudes simultáneas.
    public function guardarTarifa($anio, $mes, $alimentacion, $hospedaje)
    {
        self::validarDatos($anio, $mes, $alimentacion, $hospedaje);
        $stmt = $this->conectarTarifas()->prepare('INSERT INTO soporte_nomina_tarifas
            (tarifa_anio, tarifa_mes, tarifa_alimentacion, tarifa_hospedaje, tarifa_estado)
            VALUES (?, ?, ?, ?, 1) RETURNING tarifa_id');
        $stmt->execute([$anio, $mes, $alimentacion, $hospedaje]);
        return (int) $stmt->fetchColumn();
    }

    // Modifica solo la configuración elegida, sin recalcular detalles históricos.
    public function actualizarTarifa($tarifaId, $anio, $mes, $alimentacion, $hospedaje)
    {
        self::validarDatos($anio, $mes, $alimentacion, $hospedaje);
        $stmt = $this->conectarTarifas()->prepare('UPDATE soporte_nomina_tarifas SET
            tarifa_anio = ?, tarifa_mes = ?, tarifa_alimentacion = ?, tarifa_hospedaje = ?
            WHERE tarifa_id = ?');
        $stmt->execute([$anio, $mes, $alimentacion, $hospedaje, $tarifaId]);
        if ($stmt->rowCount() !== 1) {
            throw new InvalidArgumentException('La configuración no existe.');
        }
    }
}
