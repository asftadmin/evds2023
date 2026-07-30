let tablaLiquidacion = null;

function lcEscape(valor) {
    return $('<div>').text(valor == null ? '' : String(valor)).html();
}

function lcError(xhr, mensaje) {
    return xhr.responseJSON && xhr.responseJSON.message
        ? xhr.responseJSON.message
        : mensaje;
}

function lcRango() {
    const partes = $('#filtro_fechas').val().split(' - ');
    return {
        fecha_desde: partes[0] || '',
        fecha_hasta: partes[1] || '',
        empleado_id: $('#empleado_id').val() || ''
    };
}

function inicializarLiquidacion() {
    $('#filtro_fechas').daterangepicker({
        startDate: moment().startOf('month'),
        endDate: moment(),
        maxDate: moment(),
        locale: {
            format: 'YYYY-MM-DD',
            separator: ' - ',
            applyLabel: 'Aplicar',
            cancelLabel: 'Cancelar'
        }
    });
    $('.select2').select2({
        theme: 'bootstrap4',
        placeholder: 'Todos los trabajadores',
        allowClear: true
    });
}

function cargarContextoLiquidacion() {
    $.getJSON('../../controller/jornada_contable.php', {
        op: 'contextoLiquidacion'
    }).done(function (respuesta) {
        $('#texto-contexto').text(
            'Acceso exclusivo de Contabilidad — ' + respuesta.data.empleado
        );
    }).fail(function (xhr) {
        $('#alerta-contexto').removeClass('alert-info').addClass('alert-danger');
        $('#texto-contexto').text(lcError(xhr, 'Acceso no autorizado.'));
        $('#btn-consultar').prop('disabled', true);
    });
}

function cargarEmpleadosLiquidacion() {
    const filtros = lcRango();
    $.getJSON('../../controller/jornada_contable.php', {
        op: 'listarEmpleados',
        fecha_desde: filtros.fecha_desde,
        fecha_hasta: filtros.fecha_hasta
    }).done(function (respuesta) {
        const selector = $('#empleado_id');
        const actual = selector.val();
        selector.find('option:not(:first)').remove();
        (respuesta.data || []).forEach(function (fila) {
            selector.append(new Option(
                fila.empleado + ' — ' + fila.documento,
                fila.empleado_id
            ));
        });
        selector.val(actual).trigger('change.select2');
    });
}

function cargarLiquidacion() {
    const filtros = lcRango();
    if ($.fn.DataTable.isDataTable('#tabla-liquidacion')) {
        $('#tabla-liquidacion').DataTable().destroy();
    }
    tablaLiquidacion = $('#tabla-liquidacion').DataTable({
        processing: true,
        responsive: true,
        autoWidth: false,
        stateSave: true,
        stateDuration: -1,
        pageLength: 10,
        order: [[1, 'desc']],
        ajax: {
            url: '../../controller/jornada_contable.php',
            type: 'GET',
            dataType: 'json',
            data: Object.assign({ op: 'listarLiquidacion' }, filtros),
            dataSrc: function (respuesta) {
                return respuesta.data || [];
            },
            error: function (xhr) {
                Swal.fire('Error', lcError(xhr, 'No fue posible consultar.'), 'error');
            }
        },
        columns: [
            { data: 'empleado', render: lcEscape },
            { data: 'fecha' },
            { data: 'entrada' },
            {
                data: null,
                render: function (data, type, fila) {
                    return lcEscape(
                        fila.salida +
                        (fila.fecha_salida !== fila.fecha ? ' (+1 día)' : '')
                    );
                }
            },
            { data: 'horas_ordinarias' },
            { data: 'ubicacion', render: lcEscape },
            {
                data: 'resumen_conceptos',
                className: 'resumen-conceptos',
                render: function (conceptos) {
                    if (!Array.isArray(conceptos) || !conceptos.length) {
                        return '<span class="text-muted">Sin clasificar</span>';
                    }
                    return conceptos.map(function (concepto) {
                        return '<div class="mb-1"><span class="badge badge-dark">' +
                            lcEscape(concepto.concepto) +
                            ': ' +
                            lcEscape(concepto.horas) +
                            '</span></div>';
                    }).join('');
                }
            },
            {
                data: 'clasificacion_completa',
                render: function (completa) {
                    return completa
                        ? '<span class="badge badge-success">Clasificada</span>'
                        : '<span class="badge badge-warning">Pendiente</span>';
                }
            },
            {
                data: null,
                orderable: false,
                render: function (data, type, fila) {
                    const id = Number(fila.jornada_id);
                    return '<div class="d-flex align-items-center">' +
                        '<button class="btn btn-info btn-sm btn-segmentos mr-2" data-id="' +
                        id + '" title="Ver segmentos"><i class="fas fa-eye"></i></button>' +
                        '<button class="btn btn-success btn-sm btn-clasificar" data-id="' +
                        id + '" data-recalcular="' + (fila.segmentos > 0 ? '1' : '0') +
                        '" title="Clasificar"><i class="fas fa-calculator"></i></button>' +
                        '</div>';
                }
            }
        ],
        language: {
            emptyTable: 'No existen jornadas aprobadas en el periodo',
            zeroRecords: 'No se encontraron registros',
            search: 'Buscar:',
            info: 'Mostrando _START_ a _END_ de _TOTAL_',
            lengthMenu: 'Mostrar _MENU_ registros',
            paginate: { previous: 'Anterior', next: 'Siguiente' }
        }
    });
}

function mostrarSegmentos(jornadaId) {
    $.getJSON('../../controller/jornada_contable.php', {
        op: 'listarSegmentos',
        jornada_id: jornadaId
    }).done(function (respuesta) {
        const filas = respuesta.data || [];
        if (!filas.length) {
            Swal.fire(
                'Pendiente de clasificación',
                'La jornada todavía no tiene segmentos contables.',
                'warning'
            );
            return;
        }
        let html = '<div class="table-responsive"><table class="table table-sm table-bordered">' +
            '<thead><tr><th>Inicio</th><th>Fin</th><th>Concepto</th><th>Horas</th></tr></thead><tbody>';
        filas.forEach(function (fila) {
            html += '<tr><td>' + lcEscape(fila.inicio) + '</td><td>' +
                lcEscape(fila.fin) + '</td><td>' +
                lcEscape(fila.concepto_codigo + ' - ' + fila.concepto) +
                '</td><td>' + lcEscape(fila.horas) + '</td></tr>';
        });
        html += '</tbody></table></div>';
        Swal.fire({
            title: 'Segmentos contables',
            html: html,
            width: 850,
            confirmButtonText: 'Cerrar'
        });
    }).fail(function (xhr) {
        Swal.fire('Error', lcError(xhr, 'No fue posible consultar.'), 'error');
    });
}

function clasificarJornada(jornadaId, recalcular) {
    Swal.fire({
        icon: 'question',
        title: recalcular ? 'Recalcular jornada' : 'Clasificar jornada',
        text: recalcular
            ? 'Los segmentos anteriores serán reemplazados.'
            : 'Se generarán los segmentos contables automáticamente.',
        showCancelButton: true,
        confirmButtonText: recalcular ? 'Recalcular' : 'Clasificar',
        cancelButtonText: 'Cancelar'
    }).then(function (resultado) {
        if (!resultado.isConfirmed) {
            return;
        }

        $.ajax({
            url: '../../controller/jornada_contable.php?op=clasificarJornada',
            type: 'POST',
            dataType: 'json',
            data: {
                csrf_token: $('#csrf_token').val(),
                jornada_id: jornadaId
            }
        }).done(function (respuesta) {
            Swal.fire({
                icon: 'success',
                title: 'Clasificación completa',
                text: respuesta.message,
                timer: 1800,
                showConfirmButton: false
            });
            if (tablaLiquidacion) {
                tablaLiquidacion.ajax.reload(null, false);
            }
        }).fail(function (xhr) {
            Swal.fire(
                'No fue posible clasificar',
                lcError(xhr, 'Revise la jornada.'),
                'error'
            );
            if (tablaLiquidacion) {
                tablaLiquidacion.ajax.reload(null, false);
            }
        });
    });
}

function mostrarParametrizacion() {
    $.getJSON('../../controller/jornada_contable.php', {
        op: 'obtenerParametrizacion'
    }).done(function (respuesta) {
        const datos = respuesta.data || {};
        const regla = datos.regla || {};
        let conceptos = '';

        (datos.conceptos || []).forEach(function (fila) {
            conceptos += '<tr><td>' + lcEscape(fila.jcon_codigo) +
                '</td><td>' + lcEscape(fila.jcon_nombre) +
                '</td><td>' + lcEscape(
                    fila.jcon_codigo_contable || 'Sin configurar'
                ) + '</td></tr>';
        });

        const html =
            '<div class="text-left">' +
            '<p><strong>Regla:</strong> ' + lcEscape(regla.jreg_nombre) + '</p>' +
            '<ul>' +
            '<li>Franja diurna: ' +
            lcEscape(String(regla.jreg_hora_diurna_inicio).substring(0, 5)) +
            '–' +
            lcEscape(String(regla.jreg_hora_nocturna_inicio).substring(0, 5)) +
            '</li>' +
            '<li>Franja nocturna: ' +
            lcEscape(String(regla.jreg_hora_nocturna_inicio).substring(0, 5)) +
            '–' +
            lcEscape(String(regla.jreg_hora_diurna_inicio).substring(0, 5)) +
            '</li>' +
            '<li>Recargo nocturno ordinario: ' +
            lcEscape(String(regla.jreg_recargo_nocturno_inicio).substring(0, 5)) +
            '–' +
            lcEscape(String(regla.jreg_recargo_nocturno_fin).substring(0, 5)) +
            '</li>' +
            '<li>Jornada ordinaria hábil: ' +
            lcEscape(regla.jreg_max_lunes_viernes_min) + ' minutos</li>' +
            '<li>Fin ordinario en día continuado: ' +
            lcEscape(
                String(regla.jreg_ordinaria_continuacion_fin).substring(0, 5)
            ) + '</li>' +
            '<li>Fin de jornada diurna hábil: ' +
            lcEscape(
                String(regla.jreg_ordinaria_diurna_fin).substring(0, 5)
            ) + '</li>' +
            '<li>Descuento de almuerzo: ' +
            lcEscape(regla.jreg_almuerzo_min) +
            ' minutos, independiente de la hora real del almuerzo</li>' +
            '</ul>' +
            '<div class="table-responsive"><table class="table table-sm table-bordered">' +
            '<thead><tr><th>Código</th><th>Concepto</th><th>Código contable</th></tr></thead>' +
            '<tbody>' + conceptos + '</tbody></table></div></div>';

        Swal.fire({
            title: 'Parametrización vigente',
            html: html,
            width: 850,
            confirmButtonText: 'Cerrar'
        });
    }).fail(function (xhr) {
        Swal.fire(
            'Error',
            lcError(xhr, 'No fue posible consultar la parametrización.'),
            'error'
        );
    });
}

$(document).ready(function () {
    inicializarLiquidacion();
    cargarContextoLiquidacion();
    cargarEmpleadosLiquidacion();
    cargarLiquidacion();
});

$('#btn-consultar').on('click', function () {
    cargarEmpleadosLiquidacion();
    cargarLiquidacion();
});

$('#btn-parametrizacion').on('click', mostrarParametrizacion);

$(document).on('click', '.btn-segmentos', function () {
    mostrarSegmentos(Number($(this).data('id')));
});

$(document).on('click', '.btn-clasificar', function () {
    clasificarJornada(
        Number($(this).data('id')),
        String($(this).data('recalcular')) === '1'
    );
});
