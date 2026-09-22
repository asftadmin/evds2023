var tablaDetalle;
var tablaInconsistencias;
var soporteOcupado = false;
var consultaVersion = 0;
var seleccionSoporte = new Set();

// Inicializa tablas, filtros y eventos sin lógica incrustada en las vistas.
$(document).ready(function () {
    $('.select2').select2({ theme: 'bootstrap4', width: '100%' });
    tablaDetalle = $('#tabla_detalle').DataTable({
        responsive: true, autoWidth: false, ordering: false,
        language: { url: '../../public/plugins/datatables/Spanish.json' },
        columnDefs: [{ targets: 0, searchable: false, className: 'text-center align-middle', width: '40px' }]
    });
    tablaInconsistencias = $('#tabla_inconsistencias').DataTable({
        responsive: true, autoWidth: false, ordering: false,
        language: { url: '../../public/plugins/datatables/Spanish.json' }
    });
    // Conserva la selección entre páginas, búsquedas y redibujados del DataTable.
    tablaDetalle.on('draw', sincronizarSeleccion);
    $('#tabla_detalle').on('change', '.check-procesar', function () {
        var id = String($(this).data('id'));
        if (this.checked) { seleccionSoporte.add(id); } else { seleccionSoporte.delete(id); }
        sincronizarSeleccion();
    });
    $('#check_todos').on('change', function () {
        var marcar = this.checked;
        tablaDetalle.$('.check-procesar:not(:disabled)').each(function () {
            var id = String($(this).data('id'));
            if (marcar) { seleccionSoporte.add(id); } else { seleccionSoporte.delete(id); }
        });
        sincronizarSeleccion();
    });
    $('#archivo_auxilio').on('change', function () {
        $(this).next('.custom-file-label').text(this.files[0] ? this.files[0].name : 'Seleccionar archivo Excel');
    });
    $('#periodo_mes, #periodo_anio').on('change', function () {
        cargarInconsistencias([]);
        consultarPeriodo();
    });
    $('#btn_consultar').on('click', consultarPeriodo);
    $('#btn_validar_archivo').on('click', validarArchivo);
    $('#btn_procesar').on('click', procesarSeleccionados);
});

// Obtiene el periodo visible para usarlo también en el backend.
function periodoSoporte() {
    return { periodo_mes: $('#periodo_mes').val(), periodo_anio: $('#periodo_anio').val() };
}

// Muestra el mensaje JSON del servidor cuando una operación falla.
function errorSoporte(xhr) {
    Swal.fire({ icon: 'error', title: 'No fue posible completar la operación',
        text: xhr.responseJSON ? xhr.responseJSON.mensaje : 'No fue posible comunicarse con el servidor.' });
}

// Evita cambiar de periodo mientras se escribe o se confirma una selección.
function bloquearSoporte(ocupado) {
    soporteOcupado = ocupado;
    $('#periodo_mes, #periodo_anio, #archivo_auxilio, #btn_consultar, #btn_validar_archivo, #check_todos')
        .prop('disabled', ocupado);
    sincronizarSeleccion();
}

// Consulta resultados persistidos y descarta respuestas de periodos anteriores.
function consultarPeriodo() {
    if (soporteOcupado) { return; }
    var periodo = periodoSoporte();
    var version = ++consultaVersion;
    cargarDetalle([]);
    if (!periodo.periodo_mes || !periodo.periodo_anio) { return; }
    $.ajax({
        url: '../../controller/soporte_nomina.php?op=consultar_periodo', type: 'GET', dataType: 'json', data: periodo,
        success: function (respuesta) {
            if (version !== consultaVersion) { return; }
            if (!respuesta.success) { errorSoporte({ responseJSON: respuesta }); return; }
            cargarDetalle(respuesta.registros || []);
        },
        error: function (xhr) { if (version === consultaVersion) { errorSoporte(xhr); } }
    });
}

// Reutiliza la carga Excel para validar, calcular y guardar borradores en una solicitud.
function validarArchivo() {
    if (soporteOcupado) { return; }
    var periodo = periodoSoporte();
    var archivo = $('#archivo_auxilio')[0].files[0];
    if (!periodo.periodo_mes || !periodo.periodo_anio || !archivo) {
        Swal.fire({ icon: 'warning', title: 'Datos requeridos', text: 'Seleccione mes, año y archivo Excel.' });
        return;
    }
    var datos = new FormData();
    datos.append('periodo_mes', periodo.periodo_mes);
    datos.append('periodo_anio', periodo.periodo_anio);
    datos.append('archivo_auxilio', archivo);
    datos.append('csrf_token', $('#soporte_csrf').val());
    ++consultaVersion;
    cargarInconsistencias([]);
    bloquearSoporte(true);
    $('#btn_validar_archivo').html('<i class="fas fa-spinner fa-spin mr-1"></i> Guardando borradores...');
    $.ajax({
        url: '../../controller/soporte_nomina.php?op=validar_archivo', type: 'POST', dataType: 'json',
        data: datos, contentType: false, processData: false,
        success: function (respuesta) {
            if (!respuesta.success) { errorSoporte({ responseJSON: respuesta }); return; }
            cargarInconsistencias(respuesta.inconsistencias || []);
            cargarDetalle(respuesta.registros || []);
            Swal.fire({ icon: respuesta.inconsistencias.length ? 'warning' : 'success',
                title: 'Archivo revisado', text: respuesta.mensaje });
        },
        error: function (xhr) {
            if (xhr.responseJSON && xhr.responseJSON.inconsistencias) {
                cargarInconsistencias(xhr.responseJSON.inconsistencias);
            }
            errorSoporte(xhr);
        },
        complete: function () {
            bloquearSoporte(false);
            $('#btn_validar_archivo').html('<i class="fas fa-save mr-1"></i> Validar y guardar borradores');
        }
    });
}

// Escapa nombres y textos externos antes de incorporarlos en las tablas.
function textoSoporte(valor) {
    return $('<div>').text(valor == null ? '' : valor).html();
}

// Muestra solo cédulas inexistentes y el valor que no pudo importarse.
function cargarInconsistencias(registros) {
    tablaInconsistencias.clear();
    registros.forEach(function (registro) {
        tablaInconsistencias.row.add([textoSoporte(registro.cedula), formatoMoneda(registro.valor), textoSoporte(registro.observacion)]);
    });
    tablaInconsistencias.draw();
    $('#card_inconsistencias').toggle(registros.length > 0);
}

// Muestra las nueve columnas acordadas y habilita exclusivamente borradores persistidos.
function cargarDetalle(registros) {
    seleccionSoporte.clear();
    tablaDetalle.clear();
    registros.forEach(function (registro) {
        var borrador = registro.estado === 'BORRADOR';
        var estado = borrador ? 'Borrador' : (registro.estado === 'CONTABILIZADO' ? 'Contabilizado' : 'No disponible');
        tablaDetalle.row.add([
            '<input type="checkbox" class="check-procesar" data-id="' + Number(registro.id) + '" ' + (borrador ? '' : 'disabled') + '>',
            textoSoporte(registro.empleado), formatoMoneda(registro.total_auxilio),
            formatoNumero(registro.dias_alimentacion), formatoNumero(registro.dias_hospedaje),
            formatoMoneda(registro.valor_alimentacion), formatoMoneda(registro.valor_hospedaje), formatoMoneda(registro.otros),
            '<span class="badge badge-' + (borrador ? 'warning' : 'success') + '">' + estado + '</span>'
        ]);
    });
    tablaDetalle.draw();
    $('#card_detalle').toggle(registros.length > 0);
    sincronizarSeleccion();
}

// Refleja la selección completa, incluidas filas fuera de la página visible.
function sincronizarSeleccion() {
    if (!tablaDetalle) { return; }
    var disponibles = tablaDetalle.$('.check-procesar:not(:disabled)');
    disponibles.each(function () { this.checked = seleccionSoporte.has(String($(this).data('id'))); });
    $('#check_todos').prop('checked', disponibles.length > 0 && seleccionSoporte.size === disponibles.length)
        .prop('indeterminate', seleccionSoporte.size > 0 && seleccionSoporte.size < disponibles.length);
    $('#btn_procesar').prop('disabled', soporteOcupado || seleccionSoporte.size === 0);
}

// Confirma y contabiliza exclusivamente los identificadores seleccionados.
function procesarSeleccionados() {
    if (soporteOcupado || seleccionSoporte.size === 0) { return; }
    var datos = periodoSoporte();
    datos.registros = Array.from(seleccionSoporte);
    datos.csrf_token = $('#soporte_csrf').val();
    bloquearSoporte(true);
    Swal.fire({ title: '¿Desea procesar los registros seleccionados?', icon: 'question',
        showCancelButton: true, confirmButtonText: 'Sí, procesar', cancelButtonText: 'Cancelar'
    }).then(function (resultado) {
        if (!resultado.isConfirmed) { bloquearSoporte(false); return; }
        // Indica el guardado en curso y conserva el contenido original del botón.
        var botonProcesar = $('#btn_procesar');
        var contenidoOriginal = botonProcesar.html();
        botonProcesar.attr('aria-busy', 'true')
            .html('<i class="fas fa-spinner fa-spin mr-1" aria-hidden="true"></i> Guardando...');
        $.ajax({
            url: '../../controller/soporte_nomina.php?op=procesar', type: 'POST', dataType: 'json', data: datos,
            success: function (respuesta) {
                if (!respuesta.success) { errorSoporte({ responseJSON: respuesta }); return; }
                Swal.fire({ icon: 'success', title: 'Procesamiento finalizado', text: respuesta.mensaje });
            },
            error: errorSoporte,
            // Restaura el botón tanto al completar el guardado como ante un error.
            complete: function () {
                botonProcesar.removeAttr('aria-busy').html(contenidoOriginal);
                bloquearSoporte(false);
                consultarPeriodo();
            }
        });
    });
}

// Formatea únicamente la presentación de importes.
function formatoMoneda(valor) {
    return Number(valor || 0).toLocaleString('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 2 });
}

// Redondea visualmente los días sin intervenir en el cálculo del servidor.
function formatoNumero(valor) {
    return Number(valor || 0).toLocaleString('es-CO', { maximumFractionDigits: 2 });
}
