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

## Cruce informativo BioTime para Contabilidad

Estado: implementado; pendiente validación funcional en navegador y servicios reales.

- Consulta independiente de reportes FIRMADO usando jornada_reporte_detalle.jrd_snapshot y las tablas existentes del esquema compartido. No crea tablas, columnas ni migraciones.
- Vista y endpoints autorizados únicamente para Contabilidad con permiso del menú Inconsistencias.
- Inconsistencias BioTime es un diagnóstico operativo: no condiciona el menú Liquidaciones, sus acciones ni el acceso a ellas. No persiste resultados ni marca jornada_inconsistente. Las validaciones preexistentes de liquidación permanecen fuera del alcance de este cambio.
- Mantiene la consulta externa mediante controller/curl.php, incluidos departments=1 y areas=2. Respuestas incompletas generan error, nunca una ausencia.
- Agrupa por documento y días abarcados por las jornadas; ordena jornadas y marcaciones cronológicamente y consume cada pareja una sola vez. Las jornadas superpuestas o pares ambiguos requieren revisión manual.
- No se descartan marcaciones por distancia horaria. Una jornada con dos marcas en los días correspondientes utiliza ambas, aunque estén lejos del horario firmado. Los turnos nocturnos incluyen los días de entrada y salida.
- La tolerancia solo evalúa las diferencias después de asociar las marcaciones. No recalcula horas ordinarias, conceptos, descuentos ni liquidaciones.
- Estados: Horario correcto, Diferencia de horario, Marcación incompleta, Sin marcaciones, Revisión de marcaciones y Jornada modificada. Conserva JSON y detalle de candidatas, versiones y horario firmado.
- No diferencia reglas por Planta, Mantenimiento u Obras. Contabilidad decide cómo proceder con las diferencias.
- Frontend Bootstrap 4 / AdminLTE 3 con jQuery AJAX, JSON limpio, DataTables, Select2, DateRangePicker y SweetAlert2; conserva la bandeja de inconsistencias existente.
- Pruebas: php tests/jornadas_cruce_biotime.php (28 verificaciones aisladas, sin servicios externos), lint PHP. Pendiente prueba de integración con PostgreSQL/BioTime y navegador.
- Esta etapa consulta solo jornadas incluidas en reportes firmados. No detecta todavía marcaciones sin reporte ni implementa un flujo de resolución persistente.

### Corrección de asociación cronológica BioTime

- Caso real obligatorio: firmado 2026-08-27 08:00–13:00 frente a 04:17–20:31 produce diferencias -223 y +451 minutos y Diferencia de horario.
- Una jornada sin marcas produce Sin marcaciones; una sola marca se presenta en el extremo más próximo y produce Marcación incompleta; dos marcas se asignan como entrada/salida cronológicas.
- Varias jornadas con pares completos, cronológicamente compatibles, se asocian en orden sin reutilizar evidencias. Cantidades impares, sobrantes o asociaciones ambiguas permanecen como Revisión de marcaciones, conservando todas las candidatas.
- La tolerancia solo clasifica el resultado después de la asociación. No se modifican liquidaciones, jornadas, firmas, snapshots, tablas ni columnas.
- Archivos ajustados: models/JornadaCruceBiotime.php, view/MntJornadas/inconsistencias_biotime.php, tests/jornadas_cruce_biotime.php, PLAN.md y STRUCTURE.md.
- Validación: 28 pruebas aisladas y sintaxis PHP correctas; pendiente ejecución en navegador con datos reales.

### Presentación de diferencias BioTime en HH:MM

- inconsistencias_biotime.js muestra diferencia_entrada y diferencia_salida con signo y formato HH:MM, conservando los minutos numéricos del JSON y el ordenamiento por valor.
- Ejemplos: -223 -> -03:43; 451 -> +07:31; 30 -> +00:30; -5 -> -00:05; 0 -> 00:00. Sin marcación se muestra —.
- Las fracciones se redondean al minuto exclusivamente para presentación. No se modifica la comparación ni la tolerancia del modelo.
- inconsistencias_biotime.php usa los encabezados Diferencia entrada y Diferencia salida, sin unidad en minutos.

## Soporte Nómina: tarifas, borradores y contabilización

Estado: implementado; pendiente validación visual y de uso en navegador.

- Se revisaron PLAN.md, STRUCTURE.md y el respaldo encontrado en C:/Users/Cristian/Downloads/Telegram Desktop/evds2023.sql. El respaldo no contiene las tablas nuevas; sus columnas, escalas y claves únicas se verificaron directamente en PostgreSQL.
- Tarifas: listado histórico, creación y edición por mes/año; importes positivos con hasta dos decimales, periodo único protegido también por PostgreSQL, sin eliminación física.
- Se conserva el lector PhpSpreadsheet y la validación por empleados.cedu_empl; el nombre proviene de empleados.nomb_empl. Se ignoran auxilios <= 0 y se muestran las cédulas inexistentes como inconsistencias, sin persistirlas. Cédulas repetidas en el archivo o ambiguas en empleados se rechazan.
- Clasificación verificada: empleados.carg_empl = cargo.codi_carg, cargo.grem_carg = grupoempleados.codi_grem. Solo los grupos 4 (OBRAS) y 6 (OPERADORES DE MAQUINARIA) dividen los días cuando DíasBase > 15. El grupo 7 (OBRA ADMINISTRACION) sigue la regla general.
- El motor permanece en backend y aplica la fórmula corregida por el usuario: Otros = TotalAuxilio - ValorAlimentación - ValorHospedaje. No redondea días antes de calcular importes ni expone fórmulas/clasificación en la vista.
- Límite del esquema existente: los días son NUMERIC(10,2) y los importes NUMERIC(12,2). PostgreSQL aplica esa escala al guardar los resultados ya calculados. No se alteraron tablas ni columnas; conservar más decimales de días persistidos requeriría una decisión posterior sobre el esquema.
- La tarifa activa del periodo es obligatoria. La carga es transaccional, guarda estado 1 y origen ARCHIVO, y conserva los registros existentes (borradores o contabilizados), informando cuántos omitió. No reemplaza importes ni genera duplicados.
- La consulta por periodo recupera el histórico sin Excel y sin recalcularlo. Cambiar una tarifa no modifica resultados ya guardados.
- Procesar seleccionados requiere confirmación SweetAlert2 y actualiza únicamente IDs del periodo que aún están en estado 1 hacia estado 2. La selección se conserva entre páginas del DataTable; contabilizados no se seleccionan ni se reprocesan. Cancelar no escribe. Se recarga el detalle por AJAX.
- AJAX tarifas: listar, mostrar, guardar, actualizar; AJAX soporte: validar_archivo, consultar_periodo, procesar. JSON limpio, autenticación de sesión y CSRF en escrituras; SQL exclusivamente en modelos.
- Validación: 69 verificaciones aisladas en tests/soporte_nomina.php; 17 comprobaciones de integración sobre copias temporales de las tablas en PostgreSQL (tarifas, umbral, fracciones, inconsistencias, duplicados, histórico, estados y rollback), sin modificar datos reales; sintaxis PHP/JavaScript y seis comprobaciones de JSON en controladores (lectura, sesión, periodo, CSRF y tarifa inválida).
- Pendientes de esta entrega: prueba visual del flujo completo con sesión real y Excel del usuario. Liquidación sigue visible y pendiente; Gerencia, consolidado, gráficas y dashboard quedan para otra etapa.

### Corrección de carga XLSX: extensión ZIP

- Causa confirmada en el log de Apache: Class "ZipArchive" not found al validar Excel. La extensión existente estaba comentada en C:/xampp/php/php.ini.
- Se habilitó extension=zip y se verificó en PHP CLI mediante creación y lectura de un XLSX temporal con PhpSpreadsheet.
- controller/soporte_nomina.php ahora detecta la ausencia de ZipArchive para XLSX y responde JSON con la acción necesaria, en lugar del mensaje genérico.
- Apache debe reiniciarse desde el panel XAMPP para cargar la extensión. El reinicio por servicio no está disponible: no existe un servicio instalado llamado Apache2.4.

### Corrección confirmada de Otros

- La confirmación del usuario sustituye la fórmula anterior: Otros = TotalAuxilio - ValorAlimentación - ValorHospedaje.
- Se calculan los importes con días sin redondear y se llevan a centavos antes de descontarlos, para que el saldo concilie con los valores persistidos y visibles.
- Caso validado: 1.253.494,50 - 626.747,25 - 522.289,38 = 104.457,87.
- Se corrigieron cuatro borradores ARCHIVO de mayo de 2026 mediante corregirOtrosBorradores; solo cambió Otros en registros que coincidían con la fórmula anterior. No se modificaron contabilizados, días, tarifas ni otros periodos.
- Validación: 72 verificaciones aisladas, sintaxis PHP y prueba PostgreSQL temporal de corrección idempotente con protección de contabilizados y otros periodos.

### Indicador al procesar seleccionados

- El botón muestra spinner y Guardando... durante la solicitud de contabilización, después de confirmar. Permanece deshabilitado y recupera su contenido al finalizar, incluso si falla la solicitud.
- Validación: sintaxis JavaScript correcta.
