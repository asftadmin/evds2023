<?php 

    class Asistencia extends Conectar{


        public function obtenerRegistrosAsistencia($fechainicio = null, $fechafin = null){

            $conectar=parent::Conexion();
            $sql = "
                    WITH RegistrosPorDia AS (
                    SELECT 
                        R.IdUser,
                        U.Name AS Usuario,
                        CAST(R.RecordTime AS DATE) AS Fecha,
                        MIN(R.RecordTime) AS HoraEntrada,
                        MAX(R.RecordTime) AS HoraSalida
                    FROM dbo.Record R
                    INNER JOIN dbo.[User] U ON R.IdUser = U.IdUser
                    GROUP BY R.IdUser, U.Name, CAST(R.RecordTime AS DATE)
                )
                SELECT 
                    IdUser,
                    Usuario,
                    Fecha,
                    HoraEntrada,
                    HoraSalida,
                    CONVERT(varchar, DATEADD(SECOND, DATEDIFF(SECOND, HoraEntrada, HoraSalida), 0), 108) AS TiempoBruto,
                    CASE 
                        WHEN DATEDIFF(SECOND, HoraEntrada, HoraSalida) > 3600 THEN
                            CONVERT(varchar, DATEADD(SECOND, DATEDIFF(SECOND, HoraEntrada, HoraSalida) - 3600, 0), 108)
                        ELSE
                            '00:00:00'
                    END AS TiempoLaboradoNeto
                FROM RegistrosPorDia
                ";

            $conditions = [];
            $params = [];

            if ($fechainicio && $fechafin) {
                $conditions[] = "Fecha BETWEEN :fechainicio AND :fechafin";
                $params[':fechainicio'] = $fechainicio;
                $params[':fechafin'] = $fechafin;
            }

            if (count($conditions) > 0) {
                $sql .= " WHERE " . implode(' AND ', $conditions);
            }

            $sql .= " ORDER BY Fecha DESC, Usuario";

            $query = $conectar->prepare($sql);
            $query->execute($params);
            return $query->fetchAll(PDO::FETCH_ASSOC);


        }

        



    }


?>