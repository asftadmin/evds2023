# Guía de prueba inmediata - Etapas 1 y 2

## Instalación controlada

1. Crear copia de seguridad de `models/JornadaCruceBiotime.php` y `controller/jornada_contable.php`.
2. Primero aplicar `etapa1_medicion_biotime.zip` (solo instrumentacion) para obtener la linea base del comportamiento actual.
3. Despues aplicar `parche_cruce_biotime_etapas_1_2.zip` (consulta optimizada) y repetir filtros.
4. Validar sintaxis de los dos archivos con `php -l` en el servidor de desarrollo.
5. No reemplazar el JavaScript ni la vista para esta etapa.

## Qué cambió

- `empleadosCruceBiotime`: llamada directa a `empleados_reportes()` (SELECT DISTINCT) conservando filtros y formato `{success:true,data:[{id,text}]}`.
- `cruzarReporteBiotime`: `reportes()` usa semillas y limita detalles por empleado objetivo; sigue incluyendo todos los vecinos consecutivos reales.
- Instrumentación: tiempos de SQL, BioTime, comparación y autorización, bytes JSON; sin datos personales en `error_log`.
- `marcaciones()` conserva misma firma original (con un tercer parámetro de medición opcional) y mismas validaciones de respuesta.

## Ejemplo de métrica (valores ilustrativos; NO medidos)

```text
[JORNADA_CRUCE_PERF] {"op":"cruzarReporteBiotime","etapa":"completado","periodo_dias":7,"empleado_filtrado":true,"reportes_sql_ms":25.1,"jornadas_firmadas":6,"biotime_dias":9,"biotime_ms":1600.5,"biotime_paginas":2,"biotime_http_ms":1599.9,"biotime_pagina_max_ms":901.3,"biotime_esperadas":1300,"biotime_recibidas":1300,"comparacion_ms":8.2,"resultados":6,"evidencias_ms":0.8,"total_ms":1660.4,"http_status":200,"ok":true,"json_bytes":18500}
```

**No comparar resultados del endpoint con el ejemplo**: solo sirve para identificar los nombres de las métricas.

## Pruebas recomendadas

- Selector empleados: mismo conjunto de IDs y nombres para fechas y empleado equivalentes; carga sin solicitar BioTime.
- Cruce: igualdad del conjunto de `jornada_id`/`reporte` con versión previa para mismo periodo.
- Continuidad: jornada anterior o posterior fuera del filtro incluida si su extremo coincide exactamente.
- Turno nocturno y corte del ultimo dia: conservar diferencias y etiquetas.
- Control de errores: BioTime inaccesible -> JSON de error, nunca 'sin marcaciones'.
- Ajuste: NO es necesario ejecutarlo para medir lectura; si se prueba en ambiente de desarrollo, confirmar versionado, auditoria y snapshot historico.

## Interpretación del log

- `reportes_sql_ms` elevado: revisar plan de PostgreSQL y datos de continuidad.
- `biotime_ms` y `biotime_paginas` elevados: futura etapa 3.
- `comparacion_ms` elevado: futura etapa 4.
- `total_ms` alto con todas las etapas bajas: revisar sesion, autenticacion, red, serializacion o bloqueo de recursos.
- `etapa` diferente a `completado` + `http_status` 4xx/5xx: diagnóstico de fallo, no medirlo como consulta exitosa.

## Reversión

Si cambia el listado o aparece un error SQL, restaurar **ambos** archivos respaldados. No hay migraciones ni tablas nuevas por revertir.

Actualizar `STRUCTURE.md` y `PLAN.md` junto con el commit para que Codex use el codigo vigente.
