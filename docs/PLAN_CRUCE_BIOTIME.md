# Plan de optimizacion: Inconsistencias vs BioTime

Proyecto: Control de Personal (PHP MVC, PostgreSQL, jQuery, Bootstrap 4/AdminLTE 3).
Fecha de preparacion: 2026-11-20.

> Documento de seguimiento portable. Las etapas se conservan aqui y en esta conversacion; no representa una cache de marcaciones ni confirma cambios realizados en el repositorio local. Al integrarlo, sincronizar `PLAN.md` y `STRUCTURE.md` con Codex.

## Reglas obligatorias durante todas las etapas

- Fuente de comparacion: `jornada_reporte_detalle.jrd_snapshot` firmado, no reemplazarlo por el horario actual.
- Incluir vecinos de continuidad incluso fuera del periodo solicitado y turnos nocturnos.
- Mantener limites compartidos, marcaciones adicionales, pendientes y cortes de periodo.
- Nunca convertir una falla de BioTime en una ausencia laboral.
- Ajustes con HMAC, CSRF, comprobacion de version, transaccion, auditoria y snapshot intacto.
- SQL exclusivamente en modelos; controlador coordina; frontend AJAX JSON limpio, DataTables/Select2/Daterangepicker/SweetAlert2.
- No modificar nombres de tablas ni columnas sin verificar esquema actual; el `evds2023.sql` antiguo no incluye tablas nuevas de jornadas.

## Etapa 1. Instrumentar y establecer linea base

**Estado:** codigo preparado; pendiente validacion en ambiente local y medicion de la linea base.

- Medir por operacion: tiempo total, SQL de reportes / empleados, BioTime, comparacion, generacion de evidencias y bytes JSON.
- Contabilizar paginas HTTP, tiempo HTTP agregado, pagina mas lenta, marcaciones esperadas y recibidas.
- Registrar en `error_log` como `[JORNADA_CRUCE_PERF]`, sin nombres, cedulas ni evidencias.
- Medir en escenario de un empleado y todos los empleados; rangos de 1, 7 y 30 dias.
- Capturar plan SQL (`EXPLAIN (ANALYZE, BUFFERS)`) en la base real antes de crear indices.
- Criterio de aceptacion: JSON anterior conservado y metricas disponibles incluso si BioTime falla.

## Etapa 2. Optimizar lecturas de jornadas y selector

**Estado:** codigo candidato preparado; pendiente pruebas equivalentes y medicion.

- Nuevo metodo `empleados_reportes()` usa `SELECT DISTINCT` y no ejecuta la recursividad ni trae snapshots.
- `reportes()` obtiene `semillas` firmadas en el rango, delimita `empleados_objetivo` y extiende continuidad solo dentro de ese subconjunto, sin limite artificial de vecinos.
- Mantener filtro opcional de empleado y orden deterministico del resultado.
- Criterio: antes y despues, mismos `jrd_id`/`jornada_id` por filtros, incluidos bloques que cruzan el periodo.
- Evaluar indices existentes; NO crear indices expresionales de cast de texto a timestamp sin verificar restricciones de inmutabilidad de PostgreSQL.

## Etapa 3. Reducir carga de la API de BioTime

**Estado:** pendiente.

- Verificar documentacion real y pruebas del endpoint `transactionReport` para filtro remoto por empleado.
- Si soporta filtro remoto, enviar documento o identificador aceptado desde el servidor (no confiar en navegador).
- Crear cache de marcaciones completas y verificadas por periodo/filtros, con TTL corto para dias recientes e invalidacion/actualizacion manual.
- Nunca cachear como 'sin marcaciones' respuestas fallidas o truncadas.
- Controlar concurrencia, expiracion, paginas y limites; no incluir secretos en registros.
- Medir llamadas evitadas y duracion total.

## Etapa 4. Optimizar calculo de comparacion

**Estado:** pendiente.

- Indexar marcaciones por documento y fecha una sola vez.
- Evitar `array_filter` completo para cada grupo de jornadas.
- Revisar bucles anidados en `bloques_con_adicionales()` y recursividad de `asociar_pares()`.
- Probar exhaustivamente: nocturnos, continuidad, cortes, marcas multiples, marcas extra y ambiguedades.
- Criterio: mismo resultado antes y despues para fixtures historicos, incluida evidencia autorizable.

## Etapa 5. Refresco parcial tras ajustes

**Estado:** pendiente.

- En AJAX no volver a cruzar todos los empleados despues de confirmar un ajuste.
- Recalcular todas las jornadas afectadas del mismo empleado, no solo la fila modificada; incluir continuidades y limites compartidos.
- Reutilizar la evidencia de BioTime valida, verificar version actual al confirmar y actualizar fila(s) DataTables.
- Mantener flujo POST con CSRF, HMAC, auditoria y notificaciones SweetAlert2.
- Criterio: las filas dependientes son coherentes y no se repite descarga completa.

## Etapa 6. Pruebas de regresion y despliegue

**Estado:** pendiente.

- Comparar resultados y tiempos antes/despues para los mismos filtros (1, 7, 30 y 63 dias; 1 y todos los empleados).
- Casos: sin marcas, una marca, multiples, nocturnos, continuidades fuera del rango, cortes de periodo, version modificada, errores API, respuestas incompletas, usuario no autorizado y fallas CSRF.
- Validar que la tabla y el detalle coinciden y que no se permiten ajustes sin evidencia.
- Documentar tiempos antes/despues, plan de rollback y commit.
- Sincronizar `PLAN.md` y `STRUCTURE.md` al finalizar cada cambio.

## Entrega preparada ahora (Etapas 1 y 2)

- Archivo `etapa1_medicion_biotime.zip`: codigo original con instrumentacion, para medir la linea base **antes** de cambiar SQL.
- Archivo `parche_cruce_biotime_etapas_1_2.zip`: misma instrumentacion + optimizacion de selector y CTE recursiva.
- `models/JornadaCruceBiotime.php` -> archivo final entregado como `JornadaCruceBiotime.php`.
- `controller/jornada_contable.php` -> archivo final entregado como `jornada_contable.php`.
- La vista y el JavaScript permanecen intactos.
- **No se ha probado contra la base real ni BioTime** desde esta sesion; ejecutar pruebas antes de pasar a produccion.

## Secuencia de comprobacion local

1. Respaldar los archivos vigentes de modelo y controlador.
2. Aplicar primero los archivos del ZIP **etapa 1 (solo medicion)**. Registrar logs de 1, 7 y 30 dias.
3. Aplicar despues los archivos del ZIP **etapa 2 (optimizacion)** y repetir los mismos filtros.
4. En CLI: `php -l models/JornadaCruceBiotime.php` y `php -l controller/jornada_contable.php`.
5. Abrir el modulo de Contabilidad; cambiar periodo y verificar que el selector de empleados cargue y no haga peticiones a BioTime.
6. Pulsar 'Cruzar con BioTime' para un empleado con 1 dia y luego un periodo corto.
7. Leer `error.log` de Apache/PHP; filtrar `[JORNADA_CRUCE_PERF]`.
8. Repetir con todos los empleados y un periodo mas largo; registrar duracion y paginas.
9. Comparar IDs y resultados del cruce con la version previa en la misma base y para las mismas marcaciones BioTime (si hay cambios en BioTime entre consultas, no inferir diferencias logicas).
10. Verificar un bloque continuo que comience antes o termine despues del periodo.
11. Si hay fallas, restaurar ambos archivos anteriores y registrar el caso sin efectuar ajustes.

## Nota de migracion

No hay `CREATE TABLE`, alteraciones de base ni cambios del contrato AJAX en esta entrega. Se conserva PHP compatible con los patrones ya usados en el proyecto. No se actualiza el archivo `PLAN.md` original porque no se suministró en esta conversación: incorporar el contenido de este plan en el repositorio.
