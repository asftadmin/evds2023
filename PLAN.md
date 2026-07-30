## Consulta gerencial permisos por mes

Estado: implementado.

- Crear vista `view/MntInboxT/consulta_permisos_mes.php`.
- Crear JS `view/MntInboxT/consulta_permisos_mes.js`.
- Crear `controller/informes.php` para endpoints JSON limpios.
- Extender `models/Informes.php` con el SQL de colaboradores y permisos mensuales.
- Consultar BioTime unicamente mediante `controller/curl.php`.
- Agregar acceso en `view/MntInboxT/carpetas.php`.
- Mostrar resumen por cards y detalle en DataTable ordenado por fecha descendente.

## Reporte individual de evaluacion de desempeno

Estado: implementado, pendiente de validacion funcional con datos reales.

- Crear la vista `view/MntRpteDesempeno/reporte_desempeno.php` y su logica AJAX en `reporte_desempeno.js`.
- Consultar periodos desde `evaluacion_desempeno.evde_anio` y empleados activos desde `empleados.esta_empl = 1`.
- Extender `controller/evaluacion.php` con tres respuestas JSON limpias para periodos, empleados y validacion del reporte.
- Extender `models/Evaluacion.php` con consultas estrictas por empleado y periodo sobre el nuevo modulo.
- Crear `view/PDF/evaluacion_desempeno_pdf.php` con TCPDF, encabezado y pie institucionales, detalle por bloques, consolidado 5/5/90, interpretacion, cierre y firmas fisicas.
- Pruebas realizadas: lint PHP, validacion de endpoints JSON, busqueda de empleados activos, validacion de parametros y rechazo de PDF sin evaluaciones.
- Pendiente conocido: la tabla `evaluacion_desempeno` esta vacia en el ambiente consultado; faltan pruebas de generacion con tres tipos, sin coevaluacion y textos largos.
- Pendiente conocido: no se encontro el PDF visual indicado en los adjuntos, por lo que se conservaron los metadatos y estilo institucional del reporte anterior del proyecto.

## Registro, aprobacion y liquidacion de jornadas

Estado: registro, aprobacion de jefe, liquidacion y reportes contables implementados; integracion BioTime y correcciones de Gestion Humana pendientes.

- Crear migracion versionada con tablas de jornadas, horarios, reglas, festivos, estados, auditoria, correcciones y clasificacion.
- Mantener el esquema existente de permisos por rol y menu, sin permisos por accion.
- Conservar el rol principal del usuario y heredar menus del rol Jefe Inmediato cuando exista una relacion activa en `empleado_jefe`.
- Implementar el modelo y controlador base con JSON limpio, validacion de sesion, permiso de menu, CSRF, propiedad y superposiciones.
- Implementar `Mis Jornadas` con borradores, historial, edicion y envio a aprobacion, sin mostrar conceptos contables.
- Calcular las horas ordinarias exclusivamente en el servidor como la diferencia entre entrada y salida, sin topes diarios y sin permitir su edicion manual.
- Descontar una hora de almuerzo cuando la fecha inicial sea de lunes a viernes; sabados y domingos conservan la duracion completa.
- Permitir indicar expresamente que la salida corresponde al dia siguiente para soportar intervalos superiores a 24 horas, como sabado 15:00 a domingo 16:00.
- Implementar la bandeja `Aprobaciones de Jornadas` para jefes activos, con consulta exclusiva de sus empleados relacionados.
- Aprobar o rechazar mediante transacciones, motivo obligatorio para rechazo, historial en `jornada_aprobaciones` y auditoria; la primera decision cierra la jornada cuando existen varios jefes.
- Permitir que el jefe registre jornadas para subordinados activos desde `Jornadas de mi Equipo`; estos registros quedan aprobados automaticamente con origen, aprobacion y auditoria diferenciados.
- Publicar para el rol Contabilidad las vistas de liquidacion, inconsistencias y reporte contable, con validacion de rol y menu repetida en el servidor.
- Consultar jornadas aprobadas, avance de clasificacion, segmentos contables, inconsistencias y consolidados sin exponer estos conceptos en endpoints de empleados o jefes.
- Clasificar o recalcular individualmente una jornada aprobada desde Contabilidad, cubriendo cada minuto con un unico concepto, reemplazando atomica y auditablemente el calculo anterior.
- Bloquear la liquidacion de jornadas inconsistentes o superpuestas y enviarlas a la bandeja de inconsistencias.
- En dias habiles, cuando una jornada inicia antes de las 06:00, asignar sus primeras ocho horas como tiempo ordinario: RN durante la franja nocturna y ORD durante la franja diurna; clasificar como extra el tiempo posterior.
- Permitir que Contabilidad administre fechas especiales mediante altas, edicion, activacion e inactivacion auditadas; invalidar clasificaciones que atraviesen una fecha modificada.
- Mantener opcional la descripcion de las fechas especiales; la fecha y el estado activo determinan su aplicacion.
- Parametrizar la jornada diurna habil de 06:00 a 15:00; representar el descuento abstracto de almuerzo con NO_LIQ al final del tramo ordinario y comenzar HED desde las 15:00.
- Reestructurar el reporte contable mediante lotes documentales con una fecha de corte comun y periodos sugeridos individualmente por empleado.
- Tomar como inicio del primer reporte la primera jornada registrada y, despues de un cierre, continuar automaticamente desde el dia siguiente al ultimo periodo cerrado.
- Mostrar por empleado los estados Listo, Sin novedad, Pendiente, Bloqueado o Sin base; impedir el cierre mientras existan pendientes o inconsistencias.
- Permitir ajustes manuales del periodo solo en borrador, con motivo obligatorio, auditoria y validacion contra periodos cerrados superpuestos.
- Congelar al cerrar el lote un snapshot JSON de empleado, jornadas, aprobadores, segmentos y totales para reproducibilidad documental.
- Exportar el formato GH-F-19 v3 como PDF individual por empleado y como PDF multipagina por lote, aplicando la franja nocturna vigente 19:00-06:00.
- Exportar un Excel consolidado compatible con hojas Control, Detalle, Totales y Novedades, sin requerir dependencias adicionales.
- Permitir corregir un lote cerrado mediante una nueva version completa en borrador; admitir la superposicion exclusivamente contra el lote origen.
- Mantener vigente el lote original mientras se prepara la correccion y marcarlo como Reemplazado solamente cuando la nueva version se cierre correctamente.
- Conservar la exportacion de lotes reemplazados para auditoria e identificar tipo, version y lote origen en PDF y Excel.
- Ajustar el PDF GH-F-19 al encabezado institucional con logo, titulo, version 3, fecha documental, codigo, tipo de documento y paginacion por empleado.
- Presentar en el encabezado PDF Nombre, Cedula, Cargo, Periodo, Edad y Sexo; excluir del documento visible el nombre/version del lote y la franja nocturna.
- Mantener deshabilitados los menus de fases posteriores hasta que existan sus vistas y endpoints, evitando enlaces con error 404.
- Pruebas realizadas en fase 1: lint PHP de archivos afectados, `node --check` del JavaScript y revision de whitespace del diff.
- La fase de jefes incluye registro delegado, consulta historica del equipo y bandeja de decisiones.
- Pendiente fase 3: Gestion Humana, comparacion BioTime mediante `controller/curl.php`, inconsistencias y correcciones.
- Pruebas de reportes realizadas: migracion aplicada, lint PHP, validacion JavaScript, creacion y limpieza de lote temporal, cierre con snapshot y verificacion binaria de PDF y Excel.
- Pendiente fase 4: integrar BioTime para inconsistencias y completar el flujo de correcciones.
