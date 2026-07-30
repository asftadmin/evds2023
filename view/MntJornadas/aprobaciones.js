let tablaAprobaciones = null;

function aprobacionEscapeHtml(valor) {
    return $('<div>').text(valor == null ? '' : String(valor)).html();
}

function aprobacionMensajeError(xhr, predeterminado) {
    if (xhr.responseJSON && xhr.responseJSON.message) {
        return xhr.responseJSON.message;
    }

    try {
        const respuesta = JSON.parse(xhr.responseText);
        return respuesta.message || predeterminado;
    } catch (e) {
        return predeterminado;
    }
}

function inicializarRangoAprobaciones() {
    const inicio = moment().startOf('month');
    const fin = moment();

    $('#filtro_fechas').daterangepicker({
        startDate: inicio,
        endDate: fin,
        showDropdowns: true,
        maxDate: moment(),
        locale: {
            format: 'YYYY-MM-DD',
            separator: ' - ',
            applyLabel: 'Aplicar',
            cancelLabel: 'Cancelar',
            daysOfWeek: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'],
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
            ]
        }
    });
}

function cargarContextoAprobador() {
    $.ajax({
        url: '../../controller/jornada.php?op=contextoAprobador',
        type: 'GET',
        dataType: 'json'
    }).done(function (respuesta) {
        const datos = respuesta.data || {};
        $('#texto-contexto').text(
            'Aprobador: ' +
            (datos.empleado || '') +
            (datos.documento ? ' — ' + datos.documento : '')
        );
    }).fail(function (xhr) {
        $('#alerta-contexto')
            .removeClass('alert-info')
            .addClass('alert-danger');
        $('#texto-contexto').text(
            aprobacionMensajeError(
                xhr,
                'No fue posible validar los permisos del jefe.'
            )
        );
        $('#btn-filtrar').prop('disabled', true);
    });
}

function renderAccionesAprobacion(fila) {
    const id = Number(fila.jornada_id);
    return (
        '<div class="btn-group btn-group-sm">' +
        '<button type="button" class="btn btn-info btn-detalle" ' +
        'data-id="' + id + '" title="Ver detalle">' +
        '<i class="fas fa-eye"></i></button>' +
        '<button type="button" class="btn btn-success btn-aprobar" ' +
        'data-id="' + id + '" title="Aprobar">' +
        '<i class="fas fa-check"></i></button>' +
        '<button type="button" class="btn btn-danger btn-rechazar" ' +
        'data-id="' + id + '" title="Rechazar">' +
        '<i class="fas fa-times"></i></button>' +
        '</div>'
    );
}

function cargarPendientesJefe() {
    const rango = $('#filtro_fechas').val().split(' - ');
    const fechaDesde = rango.length === 2 ? rango[0] : '';
    const fechaHasta = rango.length === 2 ? rango[1] : '';

    if ($.fn.DataTable.isDataTable('#tabla-aprobaciones')) {
        $('#tabla-aprobaciones').DataTable().destroy();
    }

    tablaAprobaciones = $('#tabla-aprobaciones').DataTable({
        processing: true,
        responsive: true,
        autoWidth: false,
        pageLength: 10,
        order: [[2, 'asc']],
        ajax: {
            url: '../../controller/jornada.php?op=listarPendientesJefe',
            type: 'GET',
            dataType: 'json',
            data: {
                fecha_desde: fechaDesde,
                fecha_hasta: fechaHasta
            },
            dataSrc: function (respuesta) {
                return respuesta.data || [];
            },
            error: function (xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'No fue posible consultar',
                    text: aprobacionMensajeError(
                        xhr,
                        'No se pudo cargar la bandeja de aprobación.'
                    )
                });
            }
        },
        columns: [
            {
                data: 'empleado',
                render: function (data) {
                    return aprobacionEscapeHtml(data);
                }
            },
            { data: 'documento' },
            {
                data: null,
                render: function (data, type, fila) {
                    return aprobacionEscapeHtml(fila.dia + ' ' + fila.fecha);
                }
            },
            { data: 'hora_entrada' },
            {
                data: null,
                render: function (data, type, fila) {
                    const cambiaFecha = fila.fecha_salida !== fila.fecha;
                    return aprobacionEscapeHtml(
                        fila.hora_salida + (cambiaFecha ? ' (+1 día)' : '')
                    );
                }
            },
            { data: 'horas_ordinarias' },
            {
                data: 'ubicacion',
                render: function (data) {
                    return aprobacionEscapeHtml(data);
                }
            },
            {
                data: 'actividad',
                className: 'jornada-actividad',
                render: function (data) {
                    return aprobacionEscapeHtml(data);
                }
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                render: function (data, type, fila) {
                    return renderAccionesAprobacion(fila);
                }
            }
        ],
        language: {
            processing: 'Procesando...',
            search: 'Buscar:',
            lengthMenu: 'Mostrar _MENU_ registros',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
            infoEmpty: 'No hay jornadas pendientes',
            zeroRecords: 'No se encontraron jornadas',
            emptyTable: 'No existen jornadas pendientes en el periodo',
            paginate: {
                first: 'Primero',
                previous: 'Anterior',
                next: 'Siguiente',
                last: 'Último'
            }
        }
    });
}

function obtenerFilaAprobacion(jornadaId) {
    if (!tablaAprobaciones) {
        return null;
    }

    let encontrada = null;
    tablaAprobaciones.rows().every(function () {
        const fila = this.data();
        if (Number(fila.jornada_id) === Number(jornadaId)) {
            encontrada = fila;
        }
    });
    return encontrada;
}

function mostrarDetalleAprobacion(jornadaId) {
    const fila = obtenerFilaAprobacion(jornadaId);
    if (!fila) {
        return;
    }

    Swal.fire({
        icon: 'info',
        title: aprobacionEscapeHtml(fila.empleado),
        html:
            '<div class="text-left">' +
            '<p><strong>Fecha:</strong> ' +
            aprobacionEscapeHtml(fila.dia + ' ' + fila.fecha) + '</p>' +
            '<p><strong>Horario:</strong> ' +
            aprobacionEscapeHtml(
                fila.hora_entrada + ' - ' + fila.hora_salida +
                (fila.fecha_salida !== fila.fecha ? ' (día siguiente)' : '')
            ) + '</p>' +
            '<p><strong>Horas:</strong> ' +
            aprobacionEscapeHtml(fila.horas_ordinarias) + '</p>' +
            '<p><strong>Ubicación:</strong> ' +
            aprobacionEscapeHtml(fila.ubicacion) + '</p>' +
            '<p><strong>Actividad:</strong><br>' +
            aprobacionEscapeHtml(fila.actividad) + '</p>' +
            '<p><strong>Observaciones:</strong><br>' +
            aprobacionEscapeHtml(fila.observaciones || 'Sin observaciones') +
            '</p></div>',
        confirmButtonText: 'Cerrar'
    });
}

function enviarDecisionJefe(jornadaId, decision, motivo) {
    $.ajax({
        url: '../../controller/jornada.php?op=decidirJornadaJefe',
        type: 'POST',
        dataType: 'json',
        data: {
            csrf_token: $('#csrf_token').val(),
            jornada_id: jornadaId,
            decision: decision,
            motivo: motivo || ''
        }
    }).done(function (respuesta) {
        Swal.fire({
            icon: 'success',
            title: decision === 'APROBAR'
                ? 'Jornada aprobada'
                : 'Jornada rechazada',
            text: respuesta.message,
            timer: 1800,
            showConfirmButton: false
        });
        cargarPendientesJefe();
    }).fail(function (xhr) {
        Swal.fire({
            icon: 'error',
            title: 'No fue posible decidir',
            text: aprobacionMensajeError(
                xhr,
                'La jornada pudo cambiar de estado. Actualice la bandeja.'
            )
        });
        cargarPendientesJefe();
    });
}

function confirmarAprobacion(jornadaId) {
    const fila = obtenerFilaAprobacion(jornadaId);
    Swal.fire({
        icon: 'question',
        title: 'Aprobar jornada',
        text: '¿Confirma la jornada de ' + (fila ? fila.empleado : 'este empleado') + '?',
        showCancelButton: true,
        confirmButtonText: 'Sí, aprobar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#28a745'
    }).then(function (resultado) {
        if (resultado.isConfirmed) {
            enviarDecisionJefe(jornadaId, 'APROBAR', '');
        }
    });
}

function solicitarRechazo(jornadaId) {
    Swal.fire({
        icon: 'warning',
        title: 'Rechazar jornada',
        input: 'textarea',
        inputLabel: 'Motivo del rechazo',
        inputPlaceholder: 'Explique qué debe revisar el empleado...',
        inputAttributes: {
            maxlength: 2000
        },
        showCancelButton: true,
        confirmButtonText: 'Rechazar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc3545',
        inputValidator: function (valor) {
            if (!valor || !valor.trim()) {
                return 'Debe escribir el motivo del rechazo.';
            }
            return null;
        }
    }).then(function (resultado) {
        if (resultado.isConfirmed) {
            enviarDecisionJefe(
                jornadaId,
                'RECHAZAR',
                resultado.value.trim()
            );
        }
    });
}

$(document).ready(function () {
    inicializarRangoAprobaciones();
    cargarContextoAprobador();
    cargarPendientesJefe();
});

$('#btn-filtrar').on('click', cargarPendientesJefe);

$('#btn-limpiar-filtro').on('click', function () {
    const inicio = moment().startOf('month');
    const fin = moment();
    const selector = $('#filtro_fechas').data('daterangepicker');
    selector.setStartDate(inicio);
    selector.setEndDate(fin);
    $('#filtro_fechas').val(
        inicio.format('YYYY-MM-DD') + ' - ' + fin.format('YYYY-MM-DD')
    );
    cargarPendientesJefe();
});

$(document).on('click', '.btn-detalle', function () {
    mostrarDetalleAprobacion(Number($(this).data('id')));
});

$(document).on('click', '.btn-aprobar', function () {
    confirmarAprobacion(Number($(this).data('id')));
});

$(document).on('click', '.btn-rechazar', function () {
    solicitarRechazo(Number($(this).data('id')));
});
