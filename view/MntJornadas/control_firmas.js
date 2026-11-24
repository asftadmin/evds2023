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
            processing: 'Procesando...',
            search: 'Buscar:',
            lengthMenu: 'Mostrar _MENU_ registros',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
            infoEmpty: 'Mostrando 0 a 0 de 0 registros',
            infoFiltered: '(filtrado de _MAX_ registros totales)',
            loadingRecords: 'Cargando...',
            zeroRecords: 'No se encontraron registros',
            emptyTable: 'No hay información disponible',
            paginate: {
                first: 'Primero',
                previous: 'Anterior',
                next: 'Siguiente',
                last: 'Último'
            },
            aria: {
                sortAscending: ': activar para ordenar ascendente',
                sortDescending: ': activar para ordenar descendente'
            }
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
                orderable: true,
                render: function (data, type, fila) {
                    const jefe = parseInt(
                        fila.jornadas_registradas_jefe || 0,
                        10
                    );

                    const confirmadas = parseInt(
                        fila.jornadas_confirmadas || 0,
                        10
                    );

                    // Valor usado por DataTables para ordenar.
                    if (type === 'sort' || type === 'type') {
                        if (jefe > 0 && confirmadas === jefe) {
                            return 1; // Firmó primero
                        }

                        if (jefe > 0 && confirmadas < jefe) {
                            return 2; // Pendiente después
                        }

                        return 3; // No aplica al final
                    }

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
                data: 'fecha_ultima_confirmacion',
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
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: function (data, type, fila) {
                    return renderReporteIndividual(fila);
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

function renderReporteIndividual(fila) {
    const totalJornadas = parseInt(
        fila.total_jornadas || 0,
        10
    );

    if (totalJornadas <= 0) {
        return (
            '<button type="button" ' +
            'class="btn btn-sm btn-secondary" disabled>' +
            '<i class="fas fa-file-pdf"></i>' +
            '</button>'
        );
    }

    return (
        '<button type="button" ' +
        'class="btn btn-sm btn-danger btn-reporte-individual" ' +
        'data-empleado-id="' +
        parseInt(fila.empleado_id, 10) +
        '" ' +
        'title="Ver reporte individual">' +
        '<i class="fas fa-file-pdf"></i> ' +
        'PDF' +
        '</button>'
    );
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

$('#tabla-control-firmas').on(
    'click',
    '.btn-reporte-individual',
    function () {
        const empleadoId = parseInt(
            $(this).data('empleado-id'),
            10
        );

        const periodo = obtenerPeriodoControl();

        if (
            !empleadoId ||
            !periodo.fecha_desde ||
            !periodo.fecha_hasta
        ) {
            Swal.fire({
                icon: 'warning',
                title: 'Datos incompletos',
                text:
                    'No fue posible determinar el empleado ' +
                    'o el periodo del reporte.'
            });

            return;
        }

        const url =
            '../../controller/jornada_exportar.php' +
            '?tipo=pdf_borrador_equipo' +
            '&empleado_id=' +
            encodeURIComponent(empleadoId) +
            '&fecha_desde=' +
            encodeURIComponent(periodo.fecha_desde) +
            '&fecha_hasta=' +
            encodeURIComponent(periodo.fecha_hasta);

        window.open(
            url,
            '_blank',
            'noopener'
        );
    }
);