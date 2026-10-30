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

Estado: registro, aprobacion de jefe, liquidacion, reportes contables y cruce informativo BioTime con ajustes auditados implementados; flujo de correcciones de Gestion Humana pendiente.

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
- Tomar como inicio del primer reporte la primera jornada válida hasta el corte. Después de un cierre, buscar la primera jornada válida desde el día siguiente al último jle_hasta cerrado; ese día es un límite de búsqueda, no el inicio obligatorio.
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
- Pendiente fase 3: completar Gestion Humana y correcciones. El cruce BioTime de Contabilidad ya está implementado mediante `controller/curl.php`.
- Pruebas de reportes realizadas: migracion aplicada, lint PHP, validacion JavaScript, creacion y limpieza de lote temporal, cierre con snapshot y verificacion binaria de PDF y Excel.
- Pendiente fase 4: completar la resolución persistente de inconsistencias y el flujo de correcciones; el diagnóstico y los ajustes BioTime se describen a continuación.

### Inicio sugerido del lote según jornadas reales (2026-10-29)

Estado: implementado y validado en PostgreSQL con esquema temporal.

- models/JornadaReporteContable.php / calcular_inicio_sugerido(): el día posterior al MAX(jle_hasta) de lotes CERRADO es solo la fecha mínima de búsqueda. Devuelve MIN(jornada_inicio::date) de jornadas del empleado desde esa base hasta el corte común, ambos inclusive.
- Sin cierres previos, conserva la primera jornada válida registrada hasta el corte. En ambos casos resuelve el estado por je_codigo y excluye ANULADO y RECHAZADO.
- crear_lote() conserva empleados activos, omite candidatos sin jornada elegible y guarda jle_desde real, jle_hasta igual al corte y jle_origen_periodo = SUGERIDO. Se alinea la consulta de candidatos para excluir también RECHAZADO.
- La regla se aplica al crear lotes y al incorporar empleados faltantes mediante Validar de nuevo en borradores normales. Los periodos existentes no se vuelven a sugerir. Las correcciones siguen copiando empleados y periodos del lote original.
- Se conserva recalcular_fila(), diagnóstico contable, horas, clasificaciones, cierre, snapshots, PDF y Excel. No requiere modificar la vista ni el esquema.
- Prueba: php tests/jornadas_reporte_inicio.php con JORNADAS_TEST_DSN. Ejecutada satisfactoriamente con PostgreSQL aislado; el esquema de prueba se elimina al terminar.
- Escenarios verificados: cierre 2026-10-15 e inicio real el 16, 17 o 20; falta de jornadas hasta el corte; estados excluidos; primer reporte; fecha de corte inclusiva; base posterior al corte; lotes no cerrados ignorados; empleados inactivos; creación de filas SUGERIDO; conservación de periodos/snapshots históricos y de las correcciones.

### Validar de nuevo: empleados faltantes (2026-10-29)

Estado: implementado y validado en PostgreSQL temporal.

- agregar_empleados_sugeridos() centraliza la búsqueda e inserción de empleados faltantes y se reutiliza desde crear_lote() y refrescar_lote(), sin duplicar la regla de inicio sugerido.
- En borradores NORMAL, Validar de nuevo incorpora empleados activos con jornadas elegibles hasta el corte, incluidos registros posteriores a la creación del lote. Usa la primera jornada real desde el cierre anterior, origen SUGERIDO y el corte común como Hasta.
- Las filas existentes se conservan: no cambia Desde, Hasta, origen ni motivo manual. Recalcula únicamente el diagnóstico y los conteos mediante el método existente.
- Excluye ANULADO/RECHAZADO y candidatos sin actividad nueva hasta el corte. NOT EXISTS y la unicidad por lote/empleado evitan duplicados al repetir la validación.
- Las correcciones revalidan exclusivamente sus filas originales. Cerrados, reemplazados y anulados continúan bloqueados para esta acción.
- La operación mantiene el bloqueo del lote y la transacción; registra AGREGAR_EMPLEADOS cuando hay incorporaciones y devuelve empleados_agregados. El controlador informa ese número en el mensaje existente de la interfaz.
- tests/jornadas_reporte_inicio.php: incorporación tardía con Desde 23/09 y Hasta 15/10, exclusiones, conservación de ajustes, repetición sin duplicados, auditoría, correcciones y rollback/reintento ante una falla. Las pruebas usan un esquema desechable, sin agregar empleados a lotes reales.

### Descarga histórica de lotes anulados (2026-10-29)

- Despliegue verificado el 2026-10-29: la base activa todavía excluía ANULADO del CHECK de jornada_lotes_reporte. Se aplicó database/migrations/20261023_anulacion_lotes_jornadas.sql; habilita BORRADOR, CERRADO, REEMPLAZADO y ANULADO. Verificado que no cambiaron los estados de los lotes ni los snapshots. Otras instalaciones deben aplicar esta migración antes de anular.

Estado: implementado y validado con snapshots sintéticos y PostgreSQL temporal.

- [x] Conservar PDF individual, PDF por lote y Excel consolidado de lotes ANULADO que tengan snapshots de un cierre anterior. No reconstruir reportes desde jornadas actuales ni habilitar exportaciones de borradores anulados sin copia histórica.
- [x] Mantener las descargas en la interfaz sin habilitar edición, recálculo, cierre, corrección ni nueva anulación. Identificar el detalle como ANULADO (consulta histórica).
- [x] Mostrar ANULADO - SOLO CONSULTA HISTÓRICA en todas las páginas PDF y en las cuatro hojas Excel. Prefijar los nombres de descarga con ANULADO_; conservar los snapshots y la auditoría.
- [x] Mantener las exportaciones de CERRADO y REEMPLAZADO, el diseño de borradores/firmas y la autorización exclusiva de Contabilidad con permiso reporte_contable.
- Archivos: models/JornadaReporteContable.php, controller/jornada_exportar.php, view/PDF/jornadas_formato.php y view/MntJornadas/reporte_contable.js. Sin migraciones nuevas ni anulación de lotes reales durante este ajuste.
- Pruebas: tests/jornadas_anulados_exportacion.php genera PDF/Excel reales y valida snapshots, estados y auditoría en PostgreSQL aislado mediante JORNADAS_TEST_DSN; tests/jornadas_anulados_vista.cjs comprueba botones y renderers con API simulada; tests/jornadas_pdf_borrador.php verifica regresiones de páginas y firmas. Sintaxis PHP/JavaScript validada. Pendiente comprobación visual y descarga con sesión real en navegador.

### Firmas en el PDF contable (2026-10-29)

Estado: implementado; PostgreSQL aislado, generación PDF y consulta real de solo lectura verificados.

- Jornada::obtener_firmas_pdf() comparte la recuperación de firmas entre Mis jornadas / Historial y el reporte contable. Valida propietario, vigencia y coincidencia con las jornadas del documento; no usa firmas de otros empleados ni de datos distintos.
- Al cerrar un lote, construir_snapshot() incluye jefe, firma del jefe, firma del trabajador, fecha y cantidad de jornadas firmadas, además de jornada_minutos_ordinarios. Las firmas quedan congeladas junto con los datos contables.
- El PDF utiliza mostrar_firmas independientemente de borrador: presenta trabajador y jefe en la segunda hoja por empleado, sin cambiar conceptos, totales ni cálculos de liquidación.
- Para snapshots antiguos sin firmas, la exportación completa solo la presentación: verifica soportes contra las jornadas congeladas y, si existe fecha de cierre, descarta firmas posteriores. Los snapshots anteriores no guardaban jornada_minutos_ordinarios; en ese caso se contrastan los demás campos documentales disponibles.
- La recuperación histórica se identifica en el pie del PDF; los jefes se identifican mediante las aprobaciones efectivas hasta el cierre, y sus imágenes recuperadas corresponden a las firmas configuradas de esos aprobadores. No se sobrescriben snapshots persistidos ni firmas ya congeladas; si falta soporte se conserva la indicación de firma pendiente/no configurada.
- Pruebas: tests/jornadas_firma_unica.php verifica firmas compartidas, datos alterados, límite de fecha, recuperación histórica sin escritura y conservación de firmas congeladas; tests/jornadas_pdf_borrador.php incluye contable firmado de dos/tres hojas y regresiones de Historial, lote y BioTime. Exportación de anulados y sintaxis PHP verificadas.

### PDF contable: horas netas y aprobadores reales (2026-10-29)

Estado: implementado; generación PDF, pruebas PostgreSQL aisladas y consulta de solo lectura del lote real verificados.

- La columna Horas ordinarias del PDF y su total muestran las horas netas laboradas, no únicamente el concepto liquidado ORD. Se utiliza jornada_minutos_ordinarios cuando está congelado; para snapshots antiguos se toma el intervalo guardado menos los segmentos NO_LIQ. No introduce descuentos nuevos ni altera clasificaciones, conceptos o Excel.
- Caso verificado: 16/09, 03:53–18:27, sin NO_LIQ en el snapshot, muestra 14:34 netas en lugar de 05:53 del concepto ORD. RN y extras conservan su desglose liquidado.
- Jornada::obtener_aprobadores_pdf() resuelve la aprobación efectiva por jornada mediante jornada_aprobaciones, decisión APROBADO, orden por fecha/ID y fecha límite del cierre cuando existe. La firma se busca por el empleado que aprobó; no selecciona el primer jefe de empleado_jefe ni exige que siga activo actualmente.
- Si hay varios aprobadores, firmas_jefes conserva sus identidades, imágenes y jornadas aprobadas. El PDF identifica cada JEFE APROBADOR. Si no hay aprobación o firma disponible, no la sustituye por otro jefe.
- Los cierres nuevos congelan aprobado_por_id por jornada y firmas_jefes. Los snapshots antiguos sin esta identificación se completan solo en la respuesta, verificando el aprobador contra la identidad guardada, sin cambiar horas ni JSON persistido. Las firmas ya congeladas con aprobadores identificados permanecen intactas.
- Pruebas: columna y total netos, descuento NO_LIQ, respeto del neto guardado, jefe asignado distinto del aprobador, varios aprobadores, ausencia de aprobación y corrección de presentación histórica sin escritura. Regresiones de Historial, firmas, páginas, lotes y exportación histórica.

## Cruce informativo BioTime para Contabilidad

Estado al 2026-10-29: implementado; pruebas aisladas y validación PostgreSQL temporal realizadas. Pendiente validación integral de las últimas reglas con sesión real, navegador y BioTime.

- Consulta independiente de reportes FIRMADO mediante jornada_reporte_detalle.jrd_snapshot. Conserva la copia firmada y no requiere tablas, columnas ni migraciones nuevas.
- Acceso exclusivo de Contabilidad con permiso del menú Inconsistencias. El diagnóstico no condiciona Liquidaciones ni persiste resultados o marca jornada_inconsistente.
- Consulta externa mediante controller/curl.php, con departments=1 y areas=2. Una respuesta incompleta genera error, nunca ausencia.
- Agrupa por documento y días abarcados, deduplica eventos y ordena las jornadas. La tolerancia solo clasifica las diferencias después de asociar; no selecciona ni descarta marcas por ese valor.
- No aplica reglas distintas por Planta, Mantenimiento u Obras ni recalcula conceptos de liquidación. Contabilidad decide sobre las diferencias.
- El cruce incluye solo jornadas de reportes firmados. La detección de marcaciones sin reporte y la resolución persistente integral siguen pendientes.

### Reglas vigentes de asociación y continuidad

- [x] Respetar los pares reales compatibles por jornada. Con dos marcas en los días correspondientes, conservar ambas aunque estén lejos del horario firmado. Caso de regresión: 04:17–20:31 frente a 08:00–13:00, diferencias -223 y +451 minutos.
- [x] Formar bloques cuando la salida firmada coincide exactamente con la siguiente entrada del mismo empleado, a cualquier hora y con vecinos únicos. Huecos, duplicados y superposiciones no justifican continuidad automática.
- [x] Incluir mediante consulta recursiva los vecinos firmados fuera del filtro para comparar el bloque completo. Ampliar el intervalo BioTime conservando los límites de consulta.
- [x] Comparar los extremos del bloque y mantener las filas diarias. Un corte interno sin marca muestra su hora firmada y diferencia 00:00 solo cuando el par valida la continuidad; un extremo faltante no se sustituye con una marca interna.
- [x] Usar una marca real exacta en un corte como límite compartido de las filas vecinas, contado una sola vez. Mostrar Límite compartido y calcular el resultado con las diferencias propias de cada jornada.
- [x] Si una jornada firmada termina a las 00:00 posteriores al último día del periodo y un par válido demuestra salida posterior, mostrar Corte de periodo y diferencia 00:00. Conservar salida_bio_real en el detalle como soporte del periodo siguiente; no crear jornadas ni trasladar horas automáticamente.
- [x] Comparar extremos únicos de cualquier grupo, incluidos turnos separados sin continuidad, manteniendo las marcas adicionales visibles y señaladas. No elegir entre extremos empatados o que compitan con otros turnos; una cantidad impar no bloquea por sí sola todas las comparaciones.
- [x] Agregar una marca aislada asociable a una jornada individual sin resolver cuando el grupo ya tiene pares resueltos; puede quedar después de la salida firmada si pertenece a sus días y no compite con otro turno. Mostrar posición provisional por cercanía como Marcación incompleta; si hay empate o competencia, Marcación pendiente de identificar. Mantener intactos los pares y cortes anteriores, sin inventar el otro extremo.
- Estados: Horario correcto, Diferencia de horario, Marcación incompleta, Sin marcaciones, Revisión de marcaciones, Jornada modificada, Marcación adicional por revisar, Marcaciones adicionales por revisar y Marcación pendiente de identificar.
- Sin marcaciones no implica ausencia laboral. Las propuestas por cercanía requieren confirmar si la marca es entrada o salida.

### Ajustes explícitos y evidencia

- [x] Confirmar un ajuste de entrada o salida desde el detalle de Contabilidad y modificar únicamente ese extremo de la jornada actual.
- [x] Validar CSRF, evidencia HMAC privada de sesión con vencimiento, usuario, versión, estado, horario actual y hash del snapshot firmado. Operar en transacción con bloqueo por empleado/fila, control de superposición y auditoría; reconsultar después de guardar.
- [x] Conservar firmas y snapshots; no recalcular horas ordinarias, conceptos ni descuentos como parte del cruce o del ajuste.
- [x] Excluir de ajustes los cortes virtuales, cortes de periodo, límites compartidos y propuestas provisionales, con protección en servidor y botones deshabilitados en la interfaz.
- El diagnóstico no persiste una resolución operativa; los ajustes confirmados sí se guardan con su auditoría.

### Presentación

- [x] Eliminar Reporte / jornada de la tabla y separar fecha y hora en las entradas/salidas firmadas y BioTime. Conservar los identificadores internos para detalle y evidencia; ordenar por fecha y hora de entrada firmada descendentes.
- [x] Mostrar diferencias con signo en HH:MM, manteniendo minutos numéricos para cálculos y ordenamiento. Ejemplos: -223 → -03:43; 451 → +07:31; 0 → 00:00. Sin evidencia se muestra —; las fracciones se redondean solo al presentar.
- [x] Distinguir Corte por continuidad, Corte de periodo, Límite compartido y Pendiente de confirmar. Usar textos breves y señalar marcas adicionales/pendientes en la lista sin repetirlas en párrafos.
- Frontend Bootstrap 4 / AdminLTE 3 con jQuery AJAX, DataTables, Select2, DateRangePicker y SweetAlert2; conserva la bandeja existente.

### Archivos y validación

- Archivos: models/JornadaCruceBiotime.php, controller/jornada_contable.php, view/MntJornadas/inconsistencias.php, inconsistencias_biotime.php e inconsistencias_biotime.js.
- Pruebas: php tests/jornadas_cruce_biotime.php; php tests/jornadas_ajuste_biotime.php; sintaxis PHP/JavaScript y revisión de diferencias.
- La integración opcional de ajustes usa JORNADAS_TEST_DSN y un esquema PostgreSQL desechable. Sin la variable se ejecutan las comprobaciones aisladas y se informa la integración pendiente.
- PostgreSQL temporal ya validado para selección de vecinos fuera del periodo a las 00:00, 07:00 y 16:00, aislamiento por empleado, ajustes, versiones, auditoría y rollback. La validación integral de la última regla con sesión real, navegador y BioTime sigue pendiente.
- Regresiones: 241/245/246 (pares reales junto a medianoche); 261/262 (cierre de periodo); 2 de octubre (05:55 / 08:04 / 15:17); 434/435 (bloque con marcas de otros horarios); 427/428/429 (cortes a las 00:00 y 07:00); 00:24 aislada; 31/32 (marca real compartida); Edinson, 5–6 de octubre (dos pares en turnos separados y una salida nocturna pendiente). Incluyen orden determinístico, competencia, empates, evidencias no ajustables y conservación de resultados anteriores.

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

## Marcaciones personales BioTime al ingresar

Estado: implementado; validación del backend en PostgreSQL aislado y de la interfaz con API simulada.

- Mostrar automáticamente las marcaciones del empleado autenticado en view/home/home2.php.
- Resolver la identidad mediante empleados.user_empl = user_id de la sesión; no aceptar empleados/documentos enviados por el navegador.
- Conservar el transporte BioTime existente con departments=1 y areas=2. La API consultada ignora emp_code; filtrar por documento en el servidor antes de construir la respuesta.
- Resumen diario: primera y última marcación y tiempo bruto. Todas las entradas y salidas: una fila por marcación con Fecha, Hora, Empleado, Cargo y Grupo; sin documento, orden descendente y formato DD-MM-YYYY / hh:mm:ss AM-PM. Cargo/grupo desde las relaciones reales empleados.carg_empl, cargo.grem_carg y grupoempleados.codi_grem. No usar punch_state ni descontar almuerzo.
- Selector de marcaciones con opciones tradicionales: Hoy, Ayer, Esta semana, Últimos 7 días, Últimos 30 días, Este mes, Mes anterior y Rango personalizado. Semana actual (lunes hasta hoy) por defecto para reducir la consulta al iniciar sesión, sin normalizar al ciclo 16 al 15. Advertir sobre días con marcaciones impares.
- AJAX jQuery, Select2, DataTables, daterangepicker y SweetAlert2; JavaScript externo y SQL exclusivamente en el modelo.
- Conservar el dashboard existente por rol y los demás flujos de jornadas, firmas, BioTime del jefe y liquidación.
- Verificar las columnas reales en PostgreSQL mientras no se disponga de la ruta de evds2023.sql; no modificar el esquema.
- Actualizar STRUCTURE.md y PLAN.md junto con los cambios.

- Revisión de integración real: consulta BioTime satisfactoria para un empleado autenticable, con resumen y detalle coherentes; sin modificar datos reales.

- Dashboard: ubicar las tarjetas de Evaluación Mensual, Evaluación Anual, Aprobaciones y Solicitudes debajo del título Panel General del Sistema, antes de Mis marcaciones BioTime.

- Marcaciones personales: botones Copiar, CSV, Excel, PDF, Imprimir y Columnas en los dos DataTables, exportando resultados filtrados de todas las páginas y columnas visibles. Validación en Chrome con descarga CSV correcta y sin documento.

- Dashboard: padding inferior pb-4 (1.5rem) entre la tarjeta de marcaciones y el footer; validación de sintaxis PHP satisfactoria.

## Exportación PDF de marcaciones personales BioTime

Estado: implementado y validado.

- Habilitar Exportar PDF GH-F-19 solo tras consultar un periodo con datos; deshabilitar durante consultas, errores o periodos vacíos.
- Generar mediante AJAX un JSON limpio con el PDF en base64, sin recibir empleado/documento desde el navegador.
- Reutilizar el formato institucional de horas extras mediante view/PDF/jornadas_formato.php, conservando las exportaciones previas individuales y por lote.
- Mostrar resumen diario con tiempo bruto real de BioTime, sin liquidación, descuentos ni generación de estados/aprobaciones. No inventar salidas faltantes, obra, actividades o firmas.
- Mantener el SQL y preparación de datos en models/MarcacionesEmpleado.php; controlador valida sesión, operación y periodo.
- Pruebas satisfactorias: PostgreSQL aislado, JSON con PDF real, descarga y habilitación en Chrome, formato GH-F-19 de dos hojas y regresiones de firmas/lotes. Revisión visual del PDF generada con datos sintéticos.
- STRUCTURE.md y PLAN.md actualizados con archivos, bloques y alcance.

- [x] Reubicar Exportar PDF GH-F-19 junto a las exportaciones del DataTable de Mis marcaciones BioTime, conservando su habilitación por periodo y su acción al cambiar de vista.

- [x] Trasladar el botón independiente Exportar PDF GH-F-19 a Mis jornadas > Reporte para firma, después de Consultar reporte, utilizando el periodo seleccionado y conservando la consulta y firma existentes. Retirar botón y leyenda de Mis marcaciones BioTime. Verificar descarga, parámetros sin identidad, manejo de errores y exportaciones del DataTable.

  Validación: jornadas_vista.cjs --pdf-biotime y marcaciones_empleado_vista.cjs verifican descarga, rango, errores y exportaciones. La ejecución general de jornadas_vista.cjs se detiene en una expectativa antigua de validación de fecha duplicada (línea 138); no se modifica ese flujo en este ajuste.

- [x] Corrección de ubicación: Exportar PDF GH-F-19 en Historial después de Restablecer, usando el periodo filtro_fechas. Retirado de Reporte para firma. Prueba específica ajustada al periodo del historial.

- [x] Sustituir la conexión equivocada del botón del Historial: exportar exclusivamente las jornadas de horas extras del trabajador autenticado mediante jornada.php / exportarMisJornadasPdf y Jornada::obtener_reporte_propio_pdf, con periodo y estado del historial. SQL en modelo, permiso mis_jornadas, identidad de sesión, JSON limpio y descarga AJAX. No modifica estados ni firmas. Sin llamadas a BioTime en este botón. Verificación: jornadas_vista.cjs --pdf-jornadas y jornadas_firma_unica.php (PostgreSQL aislado, jornadas propias, firmas, filtros y generación real del PDF).

## Carga de soportes de Tickets de Sistemas

Estado: implementado; validado con PostgreSQL temporal y Chrome con API simulada. Pendiente prueba con sesión real del usuario.

- Esquemas revisados: evds2023.sql y preoperacional_schema.sql, proporcionados en la raíz. No contienen soportes de tickets de Sistemas. Se propuso explícitamente la ampliación antes de programarla.
- Migración: database/migrations/20261026_preoperacional_tickets_soportes.sql, destinada a la base propietaria de tickets. Crea tickets_sistemas_soportes, FK a tickets_sistemas.ticket_id, propietario, token temporal, nombre, ruta privada, MIME, tamaño y fecha; índices y límites de MIME/tamaño.
- Migración aplicada en preoperacional_prueba, base actualmente configurada por la API. No se insertaron tickets reales durante la validación.
- Interfaz: view/MntTicketsSistemas/tickets_sistemas.php y tickets_sistemas.js. Dropzone local, JPG/JPEG/PNG/PDF, 10 MB y 10 archivos máximo; carga previa, eliminación confirmada en servidor, conservación del formulario con soportes al cerrar, espera de cargas/eliminaciones y conservación ante errores de creación. Lista y descarga en el detalle; textos externos escapados.
- Controller local: controller/tickets_sistemas.php. Operaciones prepararSoportes, subirSoporte, eliminarSoporte, listarSoportes y descargarSoporte; token por formulario limitado a la sesión/24 horas e identidad exclusivamente del modelo de empleados. Conserva JSON y transporte API existentes, sin SQL.
- API dependiente modificada en C:/xampp/htdocs/preoperacional: api/tickets.php, api/controller/TicketsSistemas.php y api/model/TicketsSistemas.php; nuevo api/model/TicketsSoportes.php. Asociación de soportes en la transacción de creación y reintento del mismo lote con soportes sin duplicar ticket. Se completó detalle, invocado por la vista pero ausente de la API, con comprobación de propietario.
- Archivos en C:/xampp/storage/tickets_sistemas por defecto, fuera de htdocs, con claves aleatorias. Configuración opcional TICKETS_SOPORTES_DIR. Se conserva la experiencia de carga de permisos, sin reutilizar sus tablas ni sus credenciales FTP. No se modificó el módulo de permisos.
- API recibe JSON con base64 por el transporte existente; PostgreSQL conserva solo metadatos. Descargas JSON/base64 autorizadas por propietario. Eliminación solo de temporales. Cargas posteriores limpian temporales abandonados del mismo propietario con más de 24 horas; para usuarios que no regresan no hay limpieza periódica configurada.
- Pruebas: tests/tickets_soportes.php, 23 verificaciones con tablas temporales y archivos de prueba aislados: MIME/tamaño, nombres repetidos, acceso de otro propietario, eliminación, asociación, descarga, reintento, máximo de archivos, limpieza y rollback. El error SQL registrado durante la prueba es intencional para verificar rollback.
- tests/tickets_soportes_vista.cjs: Chrome con API simulada verifica carga, bloqueo durante pendientes, eliminación fallida/correcta, conservación al fallar creación, reintento, asociación, escape de nombres y descarga. Requiere playwright en NODE_PATH y Chrome.
- Respuestas HTTP de rechazo sin sesión y sin credencial API verificadas como JSON limpio; sintaxis PHP/JavaScript validada.
- STRUCTURE.md y PLAN.md de ambos proyectos actualizados. Conservados cambios previos de Ubicación/Equipo. Pendiente: prueba de integración con sesión real y configuración TICKETS_API_TOKEN del servidor; cualquier otra instalación debe ejecutar la migración en su base de preoperacional y desplegar también los cambios de API.

### Corrección de Select2 en Nuevo ticket

- view/MntTicketsSistemas/tickets_sistemas.js: inicializa los cuatro Select2 del formulario en shown.bs.modal, después de la transición de Bootstrap; mantiene una sola instancia y usa .modal-content como dropdownParent para mantener los desplegables dentro del formulario. El filtro de estado conserva su inicialización exterior.
- tests/tickets_soportes_vista.cjs: incorpora CSS de AdminLTE, Select2 y tema Bootstrap 4; espera el fin real de las transiciones, verifica apertura repetida de los cuatro selectores, búsqueda, selección, filtro exterior y reapertura del modal sin duplicar instancias. Conserva las verificaciones de soportes.
- Validación: Chrome/API simulada satisfactoria y sintaxis JavaScript correcta. Sin cambios en controladores, modelos ni base de datos.
