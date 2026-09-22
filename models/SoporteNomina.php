<?php

class SoporteNomina extends Conectar
{
    // Valida cada cédula del archivo contra la tabla empleados.
    public function validarEmpleadosArchivo($filas)
    {
        $conectar = parent::conexion();
        parent::set_names();

        $registros = [];
        $inconsistencias = [];

        /*
         * Se prepara una sola vez la consulta y se reutiliza
         * para todos los registros recibidos desde el Controller.
         */
        $sql = '
            SELECT
                e.id_empl,
                e.cedu_empl,
                e.nomb_empl
            FROM empleados e
            WHERE TRIM(e.cedu_empl) = ?
            LIMIT 1
        ';

        $stmt = $conectar->prepare($sql);

        foreach ($filas as $fila) {
            if ((float) $fila['valor'] <= 0) {
                continue;
            }
            $stmt->execute([
                $fila['cedu_empl']
            ]);

            $empleado = $stmt->fetch(PDO::FETCH_ASSOC);

            // Si la cédula no existe, se envía a inconsistencias.
            if (!$empleado) {
                $inconsistencias[] = [
                    'fila_excel' => $fila['fila_excel'],
                    'cedula' => $fila['cedu_empl'],
                    'valor' => $fila['valor'],
                    'observacion' => 'La cédula no coincide con empleados.cedu_empl.'
                ];

                continue;
            }

            /*
             * Si coincide, el nombre utilizado será siempre
             * nomb_empl proveniente de la base de datos.
             *
             * Por ahora el registro queda como BORRADOR únicamente
             * a nivel de la respuesta. Todavía no se persiste.
             */
            $registros[] = [
                'id' => (int) $empleado['id_empl'],
                'empleado' => $empleado['nomb_empl'],
                'total_auxilio' => (float) $fila['valor'],
                'estado' => 'BORRADOR'
            ];
        }

        return [
            'registros' => $registros,
            'inconsistencias' => $inconsistencias
        ];
    }
}
