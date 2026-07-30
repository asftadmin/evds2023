let tablaEquipo = null;
let solicitudCalculoEquipo = 0;

function equipoEscapeHtml(valor) {
    return $('<div>').text(valor == null ? '' : String(valor)).html();
}

function equipoMensajeError(xhr, predeterminado) {
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

function actualizarDiaEquipo() {
    const fecha = $('#fecha').val();
    const dias = [
        'Domingo',
        'Lunes',
        'Martes',
        'Miércoles',
        'Jueves',
        'Viernes',
        'Sábado'
    ];

    if (!fecha) {
        $('#dia_semana').val('');
        return;
    }

    $('#dia_semana').val(
        dias[new Date(fecha + 'T00:00:00').getDay()]
    );
}

function calcularHorasEquipo() {
    const fecha = $('#fecha').val();
    const entrada = $('#hora_entrada').val();
    const salida = $('#hora_salida').val();
    const numeroSolicitud = ++solicitudCalculoEquipo;

    $('#horas_ordinarias').val('00:00');
    $('#ayuda-horas-ordinarias').text(
        'De lunes a viernes descuenta una hora de almuerzo.'
    );

    if (!fecha || !entrada || !salida) {
        return;
    }

    $.ajax({
        url: '../../controller/jornada.php?op=calcularHorasEquipo',
        type: 'GET',
        dataType: 'json',
        data: {
            fecha: fecha,
            hora_entrada: entrada,
            hora_salida: salida,
            cruza_medianoche: $('#salida_dia_siguiente').is(':checked') ? 1 : 0
        }
    }).done(function (respuesta) {
        if (numeroSolicitud !== solicitudCalculoEquipo) {
            return;
        }

        const datos = respuesta.data || {};
        $('#horas_ordinarias').val(datos.horas_ordinarias || '00:00');
        let detalle = 'Duración total: ' + (datos.duracion_total || '00:00') + '.';
        detalle += datos.descuento_almuerzo !== '00:00'
            ? ' Se descontó 01:00 de almuerzo.'
            : ' Sin descuento de almuerzo.';
        if (datos.cruza_medianoche) {
            detalle += ' La salida corresponde al día siguiente.';
        }
        $('#ayuda-horas-ordinarias').text(detalle);
    }).fail(function (xhr) {
        if (numeroSolicitud !== solicitudCalculoEquipo) {
            return;
        }
        $('#ayuda-horas-ordinarias').text(
            equipoMensajeError(xhr, 'No fue posible calcular las horas.')
        );
    });
}

function cargarContextoEquipo() {
    $.ajax({
        url: '../../controller/jornada.php?op=contextoEquipo',
        type: 'GET',
        dataType: 'json'
    }).done(function (respuesta) {
        const datos = respuesta.data || {};
        $('#texto-contexto').text(
            'Jefe que registra: ' +
            (datos.empleado || '') +
            (datos.documento ? ' — ' + datos.documento : '')
        );
    }).fail(function (xhr) {
        $('#alerta-contexto')
            .removeClass('alert-info')
            .addClass('alert-danger');
        $('#texto-contexto').text(
            equipoMensajeError(xhr, 'No fue posible validar al jefe.')
        );
        $('#form-jornada-equipo :input').prop('disabled', true);
    });
}

function cargarSubordinados() {
    $.ajax({
        url: '../../controller/jornada.php?op=listarSubordinadosJefe',
        type: 'GET',
        dataType: 'json'
    }).done(function (respuesta) {
        const selector = $('#empleado_id');
        selector.find('option:not(:first)').remove();

        (respuesta.data || []).forEach(function (empleado) {
            selector.append(
                $('<option>', {
                    value: empleado.empleado_id,
                    text:
                        empleado.empleado_nombre +
                        ' — ' +
                        empleado.empleado_documento
                })
            );
        });
        selector.trigger('change.select2');
    }).fail(function (xhr) {
        Swal.fire({
            icon: 'error',
            title: 'No fue posible consultar',
            text: equipoMensajeError(
                xhr,
                'No se pudieron cargar los subordinados.'
            )
        });
    });
}

function inicializarRangoEquipo() {
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
                'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre',
                'Diciembre'
            ]
        }
    });
}

function renderEstadoEquipo(codigo, nombre) {
    const clases = {
        BORRADOR: 'badge-secondary',
        PENDIENTE_APROBACION: 'badge-warning',
        APROBADO: 'badge-success',
        RECHAZADO: 'badge-danger',
        PENDIENTE_CORRECCION: 'badge-info',
        CORREGIDO: 'badge-primary'
    };
    return (
        '<span class="badge jornada-estado ' +
        (clases[codigo] || 'badge-dark') +
        '">' + equipoEscapeHtml(nombre || codigo) + '</span>'
    );
}

function cargarHistorialEquipo() {
    const rango = $('#filtro_fechas').val().split(' - ');
    const fechaDesde = rango.length === 2 ? rango[0] : '';
    const fechaHasta = rango.length === 2 ? rango[1] : '';

    if ($.fn.DataTable.isDataTable('#tabla-equipo')) {
        $('#tabla-equipo').DataTable().destroy();
    }

    tablaEquipo = $('#tabla-equipo').DataTable({
        processing: true,
        responsive: true,
        autoWidth: false,
        pageLength: 10,
        order: [[1, 'desc']],
        ajax: {
            url: '../../controller/jornada.php?op=listarJornadasEquipo',
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
                    text: equipoMensajeError(
                        xhr,
                        'No se pudo cargar el historial del equipo.'
                    )
                });
            }
        },
        columns: [
            {
                data: 'empleado',
                render: function (data) {
                    return equipoEscapeHtml(data);
                }
            },
            {
                data: null,
                render: function (data, type, fila) {
                    return equipoEscapeHtml(fila.dia + ' ' + fila.fecha);
                }
            },
            { data: 'hora_entrada' },
            {
                data: null,
                render: function (data, type, fila) {
                    return equipoEscapeHtml(
                        fila.hora_salida +
                        (fila.fecha_salida !== fila.fecha ? ' (+1 día)' : '')
                    );
                }
            },
            { data: 'horas_ordinarias' },
            {
                data: 'ubicacion',
                render: function (data) {
                    return equipoEscapeHtml(data);
                }
            },
            {
                data: 'actividad',
                className: 'jornada-actividad',
                render: function (data) {
                    return equipoEscapeHtml(data);
                }
            },
            {
                data: 'origen',
                render: function (data) {
                    return data === 'REGISTRO_JEFE'
                        ? 'Registrada por jefe'
                        : 'Autoregistro';
                }
            },
            {
                data: null,
                render: function (data, type, fila) {
                    return renderEstadoEquipo(
                        fila.estado_codigo,
                        fila.estado_nombre
                    );
                }
            }
        ],
        language: {
            processing: 'Procesando...',
            search: 'Buscar:',
            lengthMenu: 'Mostrar _MENU_ registros',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
            infoEmpty: 'No hay registros',
            zeroRecords: 'No se encontraron jornadas',
            emptyTable: 'No existen jornadas del equipo en el periodo',
            paginate: {
                first: 'Primero',
                previous: 'Anterior',
                next: 'Siguiente',
                last: 'Último'
            }
        }
    });
}

function limpiarFormularioEquipo() {
    $('#form-jornada-equipo')[0].reset();
    $('#empleado_id').val('').trigger('change');
    $('#fecha')
        .val(moment().format('YYYY-MM-DD'))
        .attr('max', moment().format('YYYY-MM-DD'));
    $('#salida_dia_siguiente').prop('checked', false);
    $('#horas_ordinarias').val('00:00');
    actualizarDiaEquipo();
    calcularHorasEquipo();
}

function guardarJornadaEquipo(cruzaMedianoche) {
    $('#btn-guardar-equipo').prop('disabled', true);

    $.ajax({
        url: '../../controller/jornada.php?op=guardarJornadaEquipo',
        type: 'POST',
        dataType: 'json',
        data: {
            csrf_token: $('#csrf_token').val(),
            empleado_id: $('#empleado_id').val(),
            fecha: $('#fecha').val(),
            hora_entrada: $('#hora_entrada').val(),
            hora_salida: $('#hora_salida').val(),
            cruza_medianoche: cruzaMedianoche ? 1 : 0,
            ubicacion: $('#ubicacion').val(),
            actividad: $('#actividad').val(),
            observaciones: $('#observaciones').val()
        }
    }).done(function (respuesta) {
        Swal.fire({
            icon: 'success',
            title: 'Jornada aprobada',
            text: respuesta.message,
            timer: 2000,
            showConfirmButton: false
        });
        limpiarFormularioEquipo();
        cargarHistorialEquipo();
    }).fail(function (xhr) {
        Swal.fire({
            icon: 'error',
            title: 'No fue posible registrar',
            text: equipoMensajeError(
                xhr,
                'Revise la información de la jornada.'
            )
        });
    }).always(function () {
        $('#btn-guardar-equipo').prop('disabled', false);
    });
}

$(document).ready(function () {
    $('.select2').select2({
        theme: 'bootstrap4',
        placeholder: 'Seleccione un subordinado',
        allowClear: true
    });
    inicializarRangoEquipo();
    limpiarFormularioEquipo();
    cargarContextoEquipo();
    cargarSubordinados();
    cargarHistorialEquipo();
});

$('#fecha').on('change', function () {
    actualizarDiaEquipo();
    calcularHorasEquipo();
});

$('#hora_entrada, #hora_salida, #salida_dia_siguiente')
    .on('change', calcularHorasEquipo);

$('#btn-limpiar').on('click', limpiarFormularioEquipo);
$('#btn-filtrar').on('click', cargarHistorialEquipo);

$('#btn-limpiar-filtro').on('click', function () {
    const inicio = moment().startOf('month');
    const fin = moment();
    const selector = $('#filtro_fechas').data('daterangepicker');
    selector.setStartDate(inicio);
    selector.setEndDate(fin);
    $('#filtro_fechas').val(
        inicio.format('YYYY-MM-DD') + ' - ' + fin.format('YYYY-MM-DD')
    );
    cargarHistorialEquipo();
});

$('#form-jornada-equipo').on('submit', function (evento) {
    evento.preventDefault();

    const entrada = $('#hora_entrada').val();
    const salida = $('#hora_salida').val();
    const salidaDiaSiguiente = $('#salida_dia_siguiente').is(':checked');

    if (!$('#empleado_id').val()) {
        Swal.fire(
            'Empleado requerido',
            'Seleccione el subordinado.',
            'warning'
        );
        return;
    }

    if (salida <= entrada && !salidaDiaSiguiente) {
        Swal.fire({
            icon: 'question',
            title: 'La jornada cruza medianoche',
            text: '¿La salida corresponde al día siguiente?',
            showCancelButton: true,
            confirmButtonText: 'Sí, día siguiente',
            cancelButtonText: 'Revisar'
        }).then(function (resultado) {
            if (resultado.isConfirmed) {
                $('#salida_dia_siguiente').prop('checked', true);
                calcularHorasEquipo();
                guardarJornadaEquipo(true);
            }
        });
        return;
    }

    Swal.fire({
        icon: 'question',
        title: 'Registrar y aprobar',
        text: 'La jornada quedará aprobada automáticamente.',
        showCancelButton: true,
        confirmButtonText: 'Registrar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#28a745'
    }).then(function (resultado) {
        if (resultado.isConfirmed) {
            guardarJornadaEquipo(salidaDiaSiguiente);
        }
    });
});
