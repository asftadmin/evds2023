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
