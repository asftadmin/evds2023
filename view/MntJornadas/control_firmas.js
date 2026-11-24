let tablaControlFirmas = null;
let datosControlFirmas = [];

function controlEscapeHtml(valor) {
    return $('<div>')
        .text(valor === null || valor === undefined ? '' : valor)
        .html();
}

function obtenerPeriodoControl() {
    const rango = $('#filtro_fechas').val().split(' - ');

    return {
        fecha_desde: rango.length === 2 ? rango[0] : '',
        fecha_hasta: rango.length === 2 ? rango[1] : ''
    };
}

function inicializarFiltrosControl() {
    $('#empleado_id, #estado_control').select2({
        theme: 'bootstrap4',
        width: '100%'
    });

    $('#filtro_fechas').daterangepicker({
        locale: {
            format: 'YYYY-MM-DD',
            separator: ' - ',
            applyLabel: 'Aplicar',
            cancelLabel: 'Cancelar',
            fromLabel: 'Desde',
            toLabel: 'Hasta',
            customRangeLabel: 'Rango personalizado',
            daysOfWeek: [
                'Do',
                'Lu',
                'Ma',
                'Mi',
                'Ju',
                'Vi',
                'Sa'
            ],
            monthNames: [
                'Enero',
                'Febrero',
                'Marzo',
                'Abril',
                'Mayo',
                'Junio',
                'Julio',
                'Agosto',
                'Septiembre',
                'Octubre',
                'Noviembre',
                'Diciembre'
            ],
            firstDay: 1
        },

        startDate: moment('2026-10-16', 'YYYY-MM-DD'),
        endDate: moment('2026-11-15', 'YYYY-MM-DD'),

        ranges: {
            'Periodo actual': [
                moment('2026-10-16', 'YYYY-MM-DD'),
                moment('2026-11-15', 'YYYY-MM-DD')
            ],

            'Periodo anterior': [
                moment('2026-09-16', 'YYYY-MM-DD'),
                moment('2026-10-15', 'YYYY-MM-DD')
            ]
        },

        alwaysShowCalendars: true,
        showDropdowns: true
    });
}

function renderConfirmacion(fila) {
    const jefe = parseInt(
        fila.jornadas_registradas_jefe || 0,
        10
    );

    const confirmadas = parseInt(
        fila.jornadas_confirmadas || 0,
        10
    );

    // Los autoregistros no requieren confirmación de obra.
    if (jefe === 0) {
        return '<span class="badge badge-secondary">No aplica</span>';
    }

    if (confirmadas === jefe) {
        return (
            '<span class="badge badge-success">' +
            '<i class="fas fa-check"></i> Firmó' +
            '</span>'
        );
    }

    return (
        '<span class="badge badge-warning">' +
        '<i class="fas fa-clock"></i> Pendiente' +
        '</span>'
    );
}

function renderFirmaReporte(fila) {
    const firmadas = parseInt(
        fila.jornadas_firmadas_reporte || 0,
        10
    );

    const pendientes = parseInt(
        fila.pendientes_firma_reporte || 0,
        10
    );

    if (pendientes > 0) {
        return (
            '<span class="badge badge-warning">' +
            'Pendiente' +
            '</span>'
        );
    }

    if (firmadas > 0) {
        return (
            '<span class="badge badge-success">' +
            '<i class="fas fa-check"></i> Firmó' +
            '</span>'
        );
    }

    return '<span class="badge badge-secondary">No disponible</span>';
}

function renderEstadoControl(fila) {
    const estado = fila.estado_control || '';

    const clases = {
        PENDIENTE_CONFIRMACION: 'badge-warning',
        PENDIENTE_APROBACION: 'badge-warning',
        PENDIENTE_FIRMA: 'badge-warning',
        FIRMADO: 'badge-success',
        EN_PROCESO: 'badge-info',
        SIN_JORNADAS: 'badge-secondary'
    };

    return (
        '<span class="badge ' +
        (clases[estado] || 'badge-secondary') +
        '">' +
        controlEscapeHtml(fila.estado_nombre || estado) +
        '</span>'
    );
}

function actualizarCardsControl(filas) {
    $('#card-total').text(filas.length);

    $('#card-pendiente-confirmacion').text(
        filas.filter(function (fila) {
            return (
                fila.estado_control ===
                'PENDIENTE_CONFIRMACION'
            );
        }).length
    );

    $('#card-pendiente-firma').text(
        filas.filter(function (fila) {
            return fila.estado_control === 'PENDIENTE_FIRMA';
        }).length
    );

    $('#card-firmados').text(
        filas.filter(function (fila) {
            return fila.estado_control === 'FIRMADO';
        }).length
    );
}

function cargarSelectorEmpleados(filas) {
    const selector = $('#empleado_id');
    const seleccionado = selector.val();

    selector
        .find('option:not(:first)')
        .remove();

    filas.forEach(function (fila) {
        selector.append(
            $('<option>', {
                value: fila.empleado_id,
                text:
                    fila.empleado +
                    ' — ' +
                    fila.documento
            })
        );
    });

    if (
        seleccionado &&
        selector.find(
            'option[value="' + seleccionado + '"]'
        ).length
    ) {
        selector.val(seleccionado);
    }

    selector.trigger('change.select2');
}

function renderTablaControl(filas) {
    if ($.fn.DataTable.isDataTable('#tabla-control-firmas')) {
        $('#tabla-control-firmas')
            .DataTable()
            .destroy();
    }

    tablaControlFirmas = $('#tabla-control-firmas').DataTable({
        data: filas,
        responsive: true,
        autoWidth: false,
        pageLength: 10,
        order: [[0, 'asc']],
        language: {
            url:
                '../../public/plugins/datatables-' +
                'plugins/i18n/es-ES.json'
        },
        columns: [
            {
                data: 'empleado',
                render: controlEscapeHtml
            },
            {
                data: 'documento',
                render: controlEscapeHtml
            },
            {
                data: 'total_jornadas'
            },
            {
                data: null,
                render: function (data, type, fila) {
                    const jefe =
                        fila.jornadas_registradas_jefe || 0;

                    const confirmadas =
                        fila.jornadas_confirmadas || 0;

                    if (parseInt(jefe, 10) === 0) {
                        return '-';
                    }

                    return confirmadas + ' / ' + jefe;
                }
            },
            {
                data: null,
                orderable: false,
                render: function (data, type, fila) {
                    return renderConfirmacion(fila);
                }
            },
            {
                data: 'jornadas_aprobadas'
            },
            {
                data: null,
                orderable: false,
                render: function (data, type, fila) {
                    return renderFirmaReporte(fila);
                }
            },
            {
                data: 'fecha_ultima_firma',
                render: function (data) {
                    if (!data) {
                        return '-';
                    }

                    return moment(data).format(
                        'DD/MM/YYYY HH:mm'
                    );
                }
            },
            {
                data: null,
                render: function (data, type, fila) {
                    return renderEstadoControl(fila);
                }
            }
        ]
    });
}

function aplicarFiltroEstado() {
    const estado = $('#estado_control').val();

    const filtrados = !estado
        ? datosControlFirmas
        : datosControlFirmas.filter(function (fila) {
            return fila.estado_control === estado;
        });

    actualizarCardsControl(filtrados);
    renderTablaControl(filtrados);
}

function consultarControlFirmas() {
    const periodo = obtenerPeriodoControl();

    if (
        !periodo.fecha_desde ||
        !periodo.fecha_hasta
    ) {
        Swal.fire({
            icon: 'warning',
            title: 'Periodo requerido',
            text: 'Seleccione un periodo válido.'
        });

        return;
    }

    $.ajax({
        url:
            '../../controller/jornada.php?' +
            'op=listarControlFirmas',
        type: 'GET',
        dataType: 'json',
        data: {
            fecha_desde: periodo.fecha_desde,
            fecha_hasta: periodo.fecha_hasta,
            empleado_id: $('#empleado_id').val() || ''
        }
    })
        .done(function (respuesta) {
            datosControlFirmas =
                respuesta.data || [];

            if (!$('#empleado_id').val()) {
                cargarSelectorEmpleados(
                    datosControlFirmas
                );
            }

            aplicarFiltroEstado();
        })
        .fail(function (xhr) {
            let mensaje =
                'No fue posible consultar el control de firmas.';

            if (
                xhr.responseJSON &&
                xhr.responseJSON.message
            ) {
                mensaje =
                    xhr.responseJSON.message;
            }

            Swal.fire({
                icon: 'error',
                title: 'Error de consulta',
                text: mensaje
            });
        });
}

$(document).ready(function () {
    inicializarFiltrosControl();

    consultarControlFirmas();
});

$('#btn-consultar').on(
    'click',
    consultarControlFirmas
);

$('#estado_control').on(
    'change',
    aplicarFiltroEstado
);