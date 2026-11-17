/* HU-02: consulta manual, confirmaciones individuales y actualización sin recargar. */
$(function () {
    'use strict';
    var ocupado = false;
    var seleccion = null;
    var registros = {};
    var etiquetas = { 'Nuevo': 'primary', 'Vinculado': 'success', 'Cambio detectado': 'warning',
        'Sin coincidencia': 'secondary', 'Conflicto': 'danger' };
    var textoTabla = $.fn.dataTable.render.text();

    /** Escapa valores externos antes de insertarlos en los renderers HTML. */
    function escapar(valor) { return $('<span>').text(valor == null ? '' : String(valor)).html(); }

    /** Mantiene la búsqueda y ordenación sobre texto, sin interpretar datos como HTML. */
    function texto(valor, tipo) { return tipo === 'display' ? textoTabla.display(valor == null ? '—' : valor) : (valor || ''); }

    var tabla = $('#m365_tabla').DataTable({
        data: [], responsive: true, autoWidth: true, pageLength: 10, order: [[0, 'asc']],
        language: { emptyTable: 'No hay resultados. Consulte Microsoft 365.', zeroRecords: 'No hay coincidencias con el filtro.',
            search: 'Buscar:', lengthMenu: 'Mostrar _MENU_ registros', info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
            infoEmpty: 'Sin registros', infoFiltered: '(filtrado de _MAX_ registros)',
            paginate: { first: 'Primero', last: 'Último', next: 'Siguiente', previous: 'Anterior' } },
        columns: [
            { data: 'user_id', render: texto, type: 'num', responsivePriority: 1 },
            { data: 'empleado', render: texto, responsivePriority: 3 }, { data: 'correo', render: texto },
            { data: 'correo_microsoft', render: texto }, { data: 'actual', render: texto }, { data: 'encontrado', render: texto },
            { data: 'estado', responsivePriority: 4, render: function (valor, tipo) {
                return tipo === 'display' ? '<span class="badge badge-' + (etiquetas[valor] || 'secondary') + '">' + escapar(valor) + '</span>' : valor;
            } },
            { data: null, orderable: false, searchable: false, responsivePriority: 2, render: function (valor, tipo, fila) {
                if (tipo !== 'display') { return ''; }
                var accion = ['guardar', 'editar'].indexOf(fila.accion) >= 0 ? fila.accion : 'revisar';
                var nombre = { guardar: 'Guardar', editar: 'Editar', revisar: 'Revisar' }[accion];
                return '<button type="button" class="btn btn-sm btn-' + (accion === 'guardar' ? 'primary' : 'outline-secondary') +
                    ' m365-accion" data-empleado="' + Number(fila.id_empl) + '" data-accion="' + accion + '"' +
                    (ocupado ? ' disabled' : '') + '>' + nombre + '</button>';
            } }
        ]
    });
    // DataTables conserva HTML de otras páginas; actualiza disabled al volver a mostrarlas.
    tabla.on('draw.dt responsive-display.dt', function () {
        $('#m365_tabla .m365-accion').prop('disabled', ocupado);
    });
    $('#m365_estado').select2({ theme: 'bootstrap4', minimumResultsForSearch: Infinity, width: '100%' });
    $('#m365_estado').on('change', function () {
        var estado = $(this).val();
        tabla.column(6).search(estado ? '^' + $.fn.dataTable.util.escapeRegex(estado) + '$' : '', true, false).draw();
    });

    /** Deshabilita acciones incluso en filas responsive y mantiene un indicador de actividad. */
    function bloquear(valor, mensaje) {
        ocupado = valor;
        $('#m365_consultar, .m365-accion, #m365_ed_confirmar').prop('disabled', valor);
        $('#m365_consultar').html(valor ? '<i class="fas fa-spinner fa-spin mr-1" aria-hidden="true"></i> Procesando…' :
            '<i class="fas fa-sync-alt mr-1" aria-hidden="true"></i> Consultar Microsoft 365');
        if (mensaje) { $('#m365_aviso').text(mensaje); }
    }

    /** Reemplaza la comparación completa y recalcula tarjetas con valores devueltos por el modelo. */
    function presentar(datos) {
        registros = {};
        datos.registros.forEach(function (fila) { registros[fila.id_empl] = fila; });
        tabla.clear().rows.add(datos.registros).draw(false);
        // La tabla nace vacía: medir los datos recibidos antes de decidir qué columnas plegar.
        tabla.columns.adjust().responsive.recalc();
        ['total', 'vinculados', 'pendientes', 'revision'].forEach(function (clave) {
            $('#m365_' + clave).text(datos.indicadores[clave]);
        });
        $('#m365_aviso').text('Comparación actualizada. Las vinculaciones se guardan únicamente con su confirmación.');
    }

    /** Descarta resultados obsoletos ante fallos, evitando presentar listados parciales como completos. */
    function invalidar() {
        registros = {};
        seleccion = null;
        tabla.clear().draw();
        $('#m365_total, #m365_vinculados, #m365_pendientes, #m365_revision').text('—');
        $('#m365_modal').modal('hide');
        $('#m365_aviso').text('La comparación no está disponible. Consulte Microsoft 365 nuevamente.');
    }

    /** Envía CSRF y trata respuestas HTTP o JSON fallidas sin mostrar detalles técnicos. */
    function solicitar(op, datos) {
        return $.ajax({ url: '../../controller/microsoft365.php?op=' + op, type: 'POST', dataType: 'json',
            data: $.extend({}, datos, { csrf_token: $('#m365_csrf').val() }) });
    }

    /** Muestra únicamente el mensaje JSON controlado del servidor o una explicación genérica. */
    function errorSolicitud(xhr) {
        invalidar();
        var mensaje = xhr.responseJSON && xhr.responseJSON.message;
        Swal.fire({ icon: 'error', title: 'No se completó la operación', text: mensaje || 'No fue posible conectar. Intente consultar nuevamente.' });
    }

    $('#m365_consultar').on('click', function () {
        if (ocupado) { return; }
        bloquear(true, 'Consultando todas las cuentas de Microsoft 365…');
        solicitar('consultar', {}).done(function (respuesta) {
            if (respuesta.status !== 'success') { errorSolicitud({ responseJSON: respuesta }); return; }
            presentar(respuesta.data);
        }).fail(errorSolicitud).always(function () { bloquear(false); });
    });

    /** Confirma identidad y sustitución; el servidor vuelve a verificar Graph y PostgreSQL. */
    function confirmar(fila, op) {
        bloquear(true);
        Swal.fire({ icon: 'question', title: op === 'guardar' ? '¿Guardar vinculación?' : '¿Sustituir vinculación?',
            text: fila.empleado + '\n' + fila.correo + '\nObject ID: ' + fila.encontrado,
            showCancelButton: true, confirmButtonText: op === 'guardar' ? 'Guardar' : 'Confirmar sustitución', cancelButtonText: 'Cancelar' })
            .then(function (decision) {
                if (!decision.isConfirmed) { bloquear(false); return; }
                $('#m365_modal').modal('hide');
                bloquear(true, 'Verificando y guardando la vinculación…');
                solicitar(op, { user_id: fila.user_id, id_empl: fila.id_empl, graph_id: fila.encontrado,
                    actual: fila.actual || '', correo: fila.correo }).done(function (respuesta) {
                    if (respuesta.status !== 'success') { errorSolicitud({ responseJSON: respuesta }); return; }
                    presentar(respuesta.data);
                    Swal.fire({ icon: 'success', title: 'Vinculación verificada', text: respuesta.message });
                }).fail(errorSolicitud).always(function () { bloquear(false); });
            });
    }

    // Delegación por ID de empleado: funciona también en las filas hijas de DataTables responsive.
    $('#m365_tabla').on('click', '.m365-accion', function () {
        if (ocupado) { return; }
        var fila = registros[$(this).attr('data-empleado')];
        if (!fila) { return; }
        if (fila.accion === 'revisar') {
            Swal.fire({ icon: 'info', title: fila.estado, text: fila.detalle || 'Requiere revisión administrativa.' });
        } else if (fila.accion === 'guardar') {
            confirmar(fila, 'guardar');
        } else {
            seleccion = fila;
            $('#m365_ed_empleado').text(fila.empleado);
            $('#m365_ed_correo').text(fila.correo);
            $('#m365_ed_cuenta').text((fila.cuenta_microsoft || '') + ' · ' + (fila.correo_microsoft || ''));
            $('#m365_ed_actual').text(fila.actual || '—');
            $('#m365_ed_encontrado').text(fila.encontrado || '—');
            var cambio = fila.actual !== fila.encontrado;
            $('#m365_ed_aviso').text(cambio ? 'Se detectó un cambio. Revise la cuenta antes de confirmar la sustitución.' :
                'La vinculación está actualizada. No es necesario guardar cambios.');
            $('#m365_ed_confirmar').toggle(cambio).prop('disabled', !cambio);
            $('#m365_modal').modal('show');
        }
    });
    $('#m365_ed_confirmar').on('click', function () {
        if (!ocupado && seleccion && seleccion.actual !== seleccion.encontrado) { confirmar(seleccion, 'editar'); }
    });
    $('#m365_modal').on('hidden.bs.modal', function () { seleccion = null; });
});
