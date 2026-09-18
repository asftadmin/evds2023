let tablaAprobaciones = null;
const aprobacionesSeleccionadas = new Set();
let aprobacionMasivaEnCurso = false;

function actualizarSeleccionAprobaciones() {
    const filas = tablaAprobaciones ? tablaAprobaciones.rows({ search: 'applied' }).data().toArray() : [];
    const seleccionadas = filas.filter(fila => aprobacionesSeleccionadas.has(Number(fila.jornada_id))).length;
    $('#total-seleccionadas').text(seleccionadas + ' seleccionadas');
    $('#btn-aprobar-masivo').prop('disabled', aprobacionMasivaEnCurso || seleccionadas === 0);
    $('#seleccionar-aprobaciones').prop('checked', filas.length > 0 && seleccionadas === filas.length)
        .prop('indeterminate', seleccionadas > 0 && seleccionadas < filas.length);
    $('.seleccionar-jornada').each(function () {
        $(this).prop('checked', aprobacionesSeleccionadas.has(Number($(this).data('id'))));
    });
}

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

function periodoPredeterminadoAprobaciones() {
    const mesActual = moment().startOf('month');
    return {
        inicio: mesActual.clone().subtract(1, 'month').date(15),
        fin: mesActual.clone().date(16)
    };
}

function inicializarRangoAprobaciones() {
    const periodo = periodoPredeterminadoAprobaciones();

    $('#filtro_fechas').daterangepicker({
        startDate: periodo.inicio,
        endDate: periodo.fin,
        showDropdowns: true,
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

function inicializarEmpleadosAprobaciones() {
    $('#filtro_empleado').select2({
        theme: 'bootstrap4',
        placeholder: 'Todos los empleados',
        allowClear: true,
        width: '100%',
        language: {
            noResults: function () { return 'No se encontraron empleados'; },
            searching: function () { return 'Buscando...'; }
        }
    });
}

function cargarEmpleadosAprobaciones() {
    return $.ajax({
        url: '../../controller/jornada.php?op=listarSubordinadosAprobador',
        type: 'GET',
        dataType: 'json'
    }).done(function (respuesta) {
        const selector = $('#filtro_empleado');
        selector.find('option:not(:first)').remove();
        (respuesta.data || []).forEach(function (empleado) {
            selector.append($('<option>', {
                value: empleado.empleado_id,
                text: empleado.empleado_nombre + ' — ' + empleado.empleado_documento
            }));
        });
        selector.prop('disabled', false).trigger('change.select2');
    }).fail(function (xhr) {
        Swal.fire({
            icon: 'error',
            title: 'No fue posible cargar los empleados',
            text: aprobacionMensajeError(xhr, 'Recargue la página para intentar nuevamente.')
        });
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
        cargarEmpleadosAprobaciones();
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
        $('#btn-filtrar, #btn-limpiar-filtro, #filtro_fechas, #filtro_empleado').prop('disabled', true);
    });
}

function renderAccionesAprobacion(fila) {
    const id = Number(fila.jornada_id);
    return (
        '<div class="jornada-acciones">' +
        '<button type="button" class="btn btn-sm btn-info btn-detalle" ' +
        'data-id="' + id + '" title="Ver detalle" aria-label="Ver detalle">' +
        '<i class="fas fa-eye"></i></button>' +
        '<button type="button" class="btn btn-sm btn-success btn-aprobar" ' +
        'data-id="' + id + '" title="Aprobar" aria-label="Aprobar jornada">' +
        '<i class="fas fa-check"></i></button>' +
        '<button type="button" class="btn btn-sm btn-danger btn-rechazar" ' +
        'data-id="' + id + '" title="Rechazar" aria-label="Rechazar jornada">' +
        '<i class="fas fa-times"></i></button>' +
        '</div>'
    );
}

function cargarPendientesJefe() {
    if (aprobacionMasivaEnCurso) return;
    aprobacionesSeleccionadas.clear();
    actualizarSeleccionAprobaciones();
    const rango = $('#filtro_fechas').val().split(' - ');
    const fechaDesde = rango.length === 2 ? rango[0] : '';
    const fechaHasta = rango.length === 2 ? rango[1] : '';
    const empleadoId = $('#filtro_empleado').val() || '';

    if ($.fn.DataTable.isDataTable('#tabla-aprobaciones')) {
        $('#tabla-aprobaciones').DataTable().destroy();
    }

    tablaAprobaciones = $('#tabla-aprobaciones').DataTable({
        processing: true,
        responsive: true,
        autoWidth: false,
        pageLength: 10,
        order: [[4, 'asc']],
        drawCallback: function () {
            tablaAprobaciones = this.api();
            actualizarSeleccionAprobaciones();
        },
        ajax: {
            url: '../../controller/jornada.php?op=listarPendientesJefe',
            type: 'GET',
            dataType: 'json',
            data: {
                fecha_desde: fechaDesde,
                fecha_hasta: fechaHasta,
                empleado_id: empleadoId
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
                data: 'jornada_id',
                orderable: false,
                searchable: false,
                className: 'all text-center',
                render: function (data) {
                    const id = Number(data);
                    return '<input type="checkbox" class="seleccionar-jornada" data-id="' + id +
                        '" aria-label="Seleccionar jornada ' + id + '">';
                }
            },
            {
                data: 'empleado',
                render: function (data) {
                    return aprobacionEscapeHtml(data);
                }
            },
            { data: 'documento' },
            {
                data: 'dia',
                render: function (data) {
                    return aprobacionEscapeHtml(data);
                }
            },
            {
                data: 'fecha',
                render: function (data) {
                    return aprobacionEscapeHtml(data);
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
                className: 'all',
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
    if (aprobacionMasivaEnCurso) return;
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
    if (aprobacionMasivaEnCurso) return;
    aprobacionMasivaEnCurso = true;
    actualizarSeleccionAprobaciones();
    const botones = $('#tabla-aprobaciones .btn-aprobar, #tabla-aprobaciones .btn-rechazar');
    botones.prop('disabled', true);
    const boton = botones.filter(function () {
        return Number($(this).data('id')) === Number(jornadaId) &&
            $(this).hasClass(decision === 'APROBAR' ? 'btn-aprobar' : 'btn-rechazar');
    });
    const contenidoOriginal = boton.html();
    boton.attr('aria-busy', 'true').html(
        '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>'
    );
    Swal.fire({
        title: decision === 'APROBAR' ? 'Aprobando jornada...' : 'Rechazando jornada...',
        html: '<div class="spinner-border text-primary my-3" role="status">' +
            '<span class="sr-only">Procesando...</span></div>' +
            '<p>Espere un momento. No recargue ni cierre la página.</p>',
        showConfirmButton: false,
        allowOutsideClick: false,
        allowEscapeKey: false
    });
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
    }).fail(function (xhr) {
        Swal.fire({
            icon: 'error',
            title: 'No fue posible decidir',
            text: aprobacionMensajeError(
                xhr,
                'La jornada pudo cambiar de estado. Actualice la bandeja.'
            )
        });
    }).always(function () {
        aprobacionMasivaEnCurso = false;
        boton.html(contenidoOriginal).removeAttr('aria-busy');
        botones.prop('disabled', false);
        cargarPendientesJefe();
    });
}

function confirmarAprobacion(jornadaId) {
    if (aprobacionMasivaEnCurso) return;
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
    if (aprobacionMasivaEnCurso) return;
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
    inicializarEmpleadosAprobaciones();
    cargarContextoAprobador();
    cargarPendientesJefe();
});

$(document).on('change', '.seleccionar-jornada', function () {
    const id = Number($(this).data('id'));
    if (this.checked) aprobacionesSeleccionadas.add(id);
    else aprobacionesSeleccionadas.delete(id);
    actualizarSeleccionAprobaciones();
});

$('#seleccionar-aprobaciones').on('change', function () {
    if (!tablaAprobaciones || aprobacionMasivaEnCurso) return;
    const seleccionar = this.checked;
    tablaAprobaciones.rows({ search: 'applied' }).data().toArray().forEach(function (fila) {
        const id = Number(fila.jornada_id);
        if (seleccionar) aprobacionesSeleccionadas.add(id);
        else aprobacionesSeleccionadas.delete(id);
    });
    actualizarSeleccionAprobaciones();
});

$('#btn-aprobar-masivo').on('click', async function () {
    if (aprobacionMasivaEnCurso || !tablaAprobaciones) return;
    const ids = tablaAprobaciones.rows({ search: 'applied' }).data().toArray()
        .map(fila => Number(fila.jornada_id)).filter(id => aprobacionesSeleccionadas.has(id));
    if (!ids.length) return;
    aprobacionMasivaEnCurso = true;
    actualizarSeleccionAprobaciones();
    const confirmacion = await Swal.fire({
        icon: 'question', title: 'Aprobar jornadas seleccionadas',
        text: 'Se aprobarán ' + ids.length + ' jornadas. ¿Desea continuar?',
        showCancelButton: true, confirmButtonText: 'Sí, aprobar todas',
        cancelButtonText: 'Cancelar', confirmButtonColor: '#28a745'
    });
    if (!confirmacion.isConfirmed) {
        aprobacionMasivaEnCurso = false;
        actualizarSeleccionAprobaciones();
        return;
    }
    const boton = $('#btn-aprobar-masivo');
    const original = boton.html();
    boton.attr('aria-busy', 'true').html('<span class="spinner-border spinner-border-sm mr-1"></span>Procesando...');
    Swal.fire({
        title: 'Aprobando jornadas...',
        html: '<div class="spinner-border text-success my-3" role="status"><span class="sr-only">Procesando</span></div>' +
            '<p id="progreso-aprobaciones">0 de ' + ids.length + '</p><p>No recargue ni cierre la página.</p>',
        showConfirmButton: false, allowOutsideClick: false, allowEscapeKey: false
    });
    let aprobadas = 0;
    const fallidas = [];
    try {
        // Cada decisión conserva los permisos, bloqueo y auditoría del flujo individual.
        for (const id of ids) {
            try {
                await $.ajax({
                    url: '../../controller/jornada.php?op=decidirJornadaJefe',
                    type: 'POST', dataType: 'json',
                    data: { csrf_token: $('#csrf_token').val(), jornada_id: id, decision: 'APROBAR', motivo: '' }
                });
                aprobadas++;
            } catch (xhr) {
                fallidas.push('#' + id + ': ' + aprobacionMensajeError(xhr, 'No se pudo confirmar la aprobación. Revise la bandeja.'));
            }
            $('#progreso-aprobaciones').text((aprobadas + fallidas.length) + ' de ' + ids.length);
        }
        Swal.fire({
            icon: fallidas.length ? 'warning' : 'success',
            title: 'Aprobación masiva finalizada',
            html: '<p>Aprobadas: ' + aprobadas + '. Sin confirmar: ' + fallidas.length + '.</p>' +
                (fallidas.length ? '<div class="text-left" style="max-height: 240px; overflow-y: auto;">' +
                    fallidas.map(aprobacionEscapeHtml).join('<br>') + '</div>' : '')
        });
    } finally {
        aprobacionMasivaEnCurso = false;
        boton.html(original).removeAttr('aria-busy');
        cargarPendientesJefe();
    }
});

$('#btn-filtrar').on('click', cargarPendientesJefe);

$('#filtro_empleado').on('change', cargarPendientesJefe);

$('#btn-limpiar-filtro').on('click', function () {
    const periodo = periodoPredeterminadoAprobaciones();
    const selector = $('#filtro_fechas').data('daterangepicker');
    selector.setStartDate(periodo.inicio);
    selector.setEndDate(periodo.fin);
    $('#filtro_fechas').val(
        periodo.inicio.format('YYYY-MM-DD') + ' - ' + periodo.fin.format('YYYY-MM-DD')
    );
    $('#filtro_empleado').val('').trigger('change.select2');
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
