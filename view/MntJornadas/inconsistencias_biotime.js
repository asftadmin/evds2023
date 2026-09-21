/* Cruce independiente y ajustes explícitos; conserva la bandeja y cálculos existentes. */
$(function () {
    const url = '../../controller/jornada_contable.php';
    let solicitudEmpleados = 0;
    let ocupado = false;
    const escapar = function (valor) { return $('<div>').text(valor == null ? '' : String(valor)).html(); };
    const texto = $.fn.dataTable.render.text();
    // Conserva el texto original para búsqueda y presenta un guion si falta evidencia.
    const evidencia = function (valor, type) {
        return type === 'display' ? (valor ? escapar(valor) : '—') : (valor || '');
    };
    // Formatea solo la presentación y búsqueda; ordenamiento y cálculos conservan los minutos.
    const diferencia = function (valor, type) {
        if (valor == null || valor === '') return type === 'sort' || type === 'type' ? null : '—';
        const minutos = Number(valor);
        if (type === 'sort' || type === 'type') return minutos;
        // Las fracciones de minuto se redondean únicamente para la visualización HH:MM.
        const total = Math.round(Math.abs(minutos));
        const signo = minutos === 0 ? '' : (minutos < 0 ? '-' : '+');
        return signo + String(Math.floor(total / 60)).padStart(2, '0')
            + ':' + String(total % 60).padStart(2, '0');
    };
    const tabla = $('#icb-tabla').DataTable({
        data: [], responsive: true, autoWidth: false, order: [[2, 'desc']],
        columns: [
            { data: null, render: function (d, type, fila) { return fila.reporte + ' / ' + fila.jornada; } },
            { data: 'empleado', render: texto }, { data: 'inicio', render: texto },
            { data: 'fin', render: texto }, { data: 'ubicacion', render: texto },
            { data: 'entrada_bio', render: evidencia }, { data: 'salida_bio', render: evidencia },
            { data: 'diferencia_entrada', render: diferencia, defaultContent: '—' },
            { data: 'diferencia_salida', render: diferencia, defaultContent: '—' },
            { data: 'resultado', render: function (valor, type) {
                if (type !== 'display') return valor;
                return '<span class="badge ' + (valor === 'Horario correcto' ? 'badge-success' : 'badge-warning')
                    + '">' + escapar(valor) + '</span>';
            } },
            { data: null, orderable: false, searchable: false, render: function () {
                return '<button type="button" class="btn btn-sm btn-info icb-detalle">Ver marcaciones</button>';
            } }
        ],
        language: { emptyTable: 'No hay resultados del cruce', search: 'Buscar:',
            lengthMenu: 'Mostrar _MENU_ registros', info: 'Mostrando _START_ a _END_ de _TOTAL_',
            infoEmpty: 'Sin resultados', zeroRecords: 'No hay coincidencias',
            paginate: { previous: 'Anterior', next: 'Siguiente' } }
    });
    $('#icb-empleado').select2({ width: '100%' });
    $('#icb-periodo').daterangepicker({
        startDate: moment().subtract(1, 'month').startOf('month'), endDate: moment(), maxDate: moment(),
        locale: { format: 'YYYY-MM-DD', separator: ' - ', applyLabel: 'Aplicar', cancelLabel: 'Cancelar',
            daysOfWeek: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'],
            monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'] }
    });
    function filtros() {
        const rango = $('#icb-periodo').data('daterangepicker');
        return { fecha_desde: rango.startDate.format('YYYY-MM-DD'), fecha_hasta: rango.endDate.format('YYYY-MM-DD'),
            empleado_id: $('#icb-empleado').val() || '', tolerancia: $('#icb-tolerancia').val() };
    }
    function error(xhr) {
        Swal.fire('No fue posible consultar', xhr.responseJSON && xhr.responseJSON.message
            ? xhr.responseJSON.message : 'La consulta no se completó. Intente nuevamente.', 'error');
    }
    function cargarEmpleados() {
        const numero = ++solicitudEmpleados;
        $('#icb-consultar, #icb-empleado').prop('disabled', true);
        $('#icb-empleado').empty().append(new Option('Todos los empleados con reporte firmado', '')).trigger('change');
        $.getJSON(url, Object.assign(filtros(), { op: 'empleadosCruceBiotime' })).done(function (r) {
            if (numero !== solicitudEmpleados) return;
            (r.data || []).forEach(function (e) { $('#icb-empleado').append(new Option(e.text, e.id)); });
            $('#icb-consultar, #icb-empleado').prop('disabled', false);
        }).fail(function (xhr) { if (numero === solicitudEmpleados) error(xhr); });
    }
    $('#icb-periodo').on('apply.daterangepicker', function () {
        tabla.clear().draw();
        $('#icb-resumen').text('Periodo actualizado. Ejecute nuevamente el cruce.');
        cargarEmpleados();
    });
    $('#icb-empleado, #icb-tolerancia').on('change', function () {
        tabla.clear().draw();
        $('#icb-resumen').text('Filtros actualizados. Ejecute nuevamente el cruce.');
    });
    // Vuelve a consultar la evidencia y las versiones después de cada ajuste confirmado.
    function consultarCruce(mensajeAjuste) {
        if (ocupado) return;
        ocupado = true;
        const datos = Object.assign(filtros(), { op: 'cruzarReporteBiotime' });
        tabla.clear().draw();
        $('#icb-consultar, #icb-periodo, #icb-empleado, #icb-tolerancia').prop('disabled', true);
        $('#icb-resumen').text('Consultando BioTime…');
        Swal.fire({ title: 'Consultando BioTime…', allowOutsideClick: false, allowEscapeKey: false,
            showConfirmButton: false, didOpen: function () { Swal.showLoading(); } });
        $.getJSON(url, datos).done(function (r) {
            tabla.rows.add(r.data || []).draw();
            $('#icb-resumen').text((r.data || []).length + ' jornadas consultadas. Tolerancia: '
                + r.tolerancia + ' min. Consulta: ' + r.consultado);
            if (mensajeAjuste) {
                Swal.fire('Ajuste guardado', mensajeAjuste, 'success');
            } else {
                Swal.close();
            }
        }).fail(function (xhr) {
            $('#icb-resumen').text('Cruce pendiente de consulta. No se han determinado ausencias ni inconsistencias.');
            if (mensajeAjuste) {
                Swal.fire('Ajuste guardado; cruce pendiente',
                    mensajeAjuste + ' No se pudo actualizar el cruce. Pulse Cruzar con BioTime para reintentar.', 'warning');
            } else {
                error(xhr);
            }
        }).always(function () {
            ocupado = false;
            $('#icb-consultar, #icb-periodo, #icb-empleado, #icb-tolerancia').prop('disabled', false);
        });
    }
    $('#icb-consultar').on('click', function () { consultarCruce(); });

    // Solo envía el extremo y la evidencia autenticada, nunca un horario libre del navegador.
    function ajustarHorario(fila, extremo) {
        const marca = fila[extremo + '_bio'];
        if (ocupado || !marca) return;
        const actual = extremo === 'entrada' ? fila.inicio_actual : fila.fin_actual;
        Swal.fire({ title: 'Usar ' + extremo + ' BioTime', icon: 'question',
            html: '<p><strong>Horario actual:</strong> ' + escapar(actual) + '</p>'
                + '<p><strong>Horario BioTime:</strong> ' + escapar(marca) + '</p>'
                + '<p>Se cambiará únicamente la ' + extremo + ' de la jornada actual. La copia firmada se conservará.</p>',
            showCancelButton: true, confirmButtonText: 'Confirmar ajuste', cancelButtonText: 'Cancelar'
        }).then(function (r) {
            if (!r.isConfirmed || ocupado) return;
            ocupado = true;
            $('#icb-consultar, #icb-periodo, #icb-empleado, #icb-tolerancia').prop('disabled', true);
            Swal.fire({ title: 'Guardando ajuste…', allowOutsideClick: false, allowEscapeKey: false,
                showConfirmButton: false, didOpen: function () { Swal.showLoading(); } });
            $.ajax({ url: url, type: 'POST', dataType: 'json', data: {
                op: 'ajustarJornadaBiotime', csrf_token: $('#icb-csrf').val(),
                evidencia: fila.evidencia_ajuste, extremo: extremo
            } }).done(function (respuesta) {
                ocupado = false;
                consultarCruce(respuesta.message);
            }).fail(function (xhr) {
                ocupado = false;
                $('#icb-consultar, #icb-periodo, #icb-empleado, #icb-tolerancia').prop('disabled', false);
                error(xhr);
            });
        });
    }
    $('#icb-tabla').on('click', '.icb-detalle', function () {
        if (ocupado) return;
        let tr = $(this).closest('tr');
        if (tr.hasClass('child')) tr = tr.prev();
        const fila = tabla.row(tr).data();
        if (!fila) return;
        const editable = ['APROBADO', 'PENDIENTE_LIQUIDACION', 'LIQUIDADO'].includes(fila.estado_actual);
        Swal.fire({ title: 'Marcaciones del periodo de la jornada',
            html: '<p>' + escapar(fila.detalle) + '</p><p>Versión firmada: ' + fila.version_firmada
                + '. Versión actual: ' + fila.version_actual + '.</p><p>La firma también incrementa la versión.</p><ul>'
                + fila.marcaciones.map(function (m) { return '<li>' + escapar(m) + '</li>'; }).join('')
                + '</ul>' + (fila.marcaciones.length ? '' : '<p>No hay marcaciones en la consulta.</p>')
                + '<p><strong>Jornada actual:</strong> ' + escapar(fila.inicio_actual) + ' — ' + escapar(fila.fin_actual) + '</p>'
                + '<div class="d-flex justify-content-center flex-wrap">'
                + '<button type="button" id="icb-usar-entrada" class="btn btn-primary m-1"'
                + (editable && fila.entrada_bio ? '' : ' disabled') + '>Usar entrada BioTime</button>'
                + '<button type="button" id="icb-usar-salida" class="btn btn-primary m-1"'
                + (editable && fila.salida_bio ? '' : ' disabled') + '>Usar salida BioTime</button></div>',
            confirmButtonText: 'Cerrar',
            didOpen: function () {
                $('#icb-usar-entrada').on('click', function () { ajustarHorario(fila, 'entrada'); });
                $('#icb-usar-salida').on('click', function () { ajustarHorario(fila, 'salida'); });
            }
        });
    });
    cargarEmpleados();
});
