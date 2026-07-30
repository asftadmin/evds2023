let tablaJornadas = null;
let solicitudCalculoHoras = 0;

/**
 * Escapa texto antes de insertarlo en fragmentos HTML.
 */
function jornadaEscapeHtml(valor) {
    return $('<div>').text(valor == null ? '' : String(valor)).html();
}

/**
 * Presenta el mensaje entregado por el controlador o uno predeterminado.
 */
function jornadaMensajeError(xhr, predeterminado) {
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

/**
 * Calcula el nombre del día a partir de la fecha seleccionada.
 */
function actualizarDiaSemana() {
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

    const objetoFecha = new Date(fecha + 'T00:00:00');
    $('#dia_semana').val(dias[objetoFecha.getDay()]);
}

/**
 * Consulta al servidor las horas netas para evitar cálculos manipulables en
 * el navegador. La respuesta más reciente prevalece sobre las anteriores.
 */
function calcularHorasJornada() {
    const fecha = $('#fecha').val();
    const entrada = $('#hora_entrada').val();
    const salida = $('#hora_salida').val();
    const numeroSolicitud = ++solicitudCalculoHoras;

    $('#horas_ordinarias').val('00:00');
    $('#ayuda-horas-ordinarias').text(
        'Se calcula entre entrada y salida. De lunes a viernes descuenta una hora de almuerzo.'
    );

    if (!fecha || !entrada || !salida) {
        return;
    }

    $.ajax({
        url: '../../controller/jornada.php?op=calcularHoras',
        type: 'GET',
        dataType: 'json',
        data: {
            fecha: fecha,
            hora_entrada: entrada,
            hora_salida: salida,
            cruza_medianoche: $('#salida_dia_siguiente').is(':checked') ? 1 : 0
        }
    }).done(function (respuesta) {
        if (numeroSolicitud !== solicitudCalculoHoras) {
            return;
        }

        const datos = respuesta.data || {};
        $('#horas_ordinarias').val(datos.horas_ordinarias || '00:00');

        let detalle = 'Duración total: ' + (datos.duracion_total || '00:00') + '.';
        if (datos.descuento_almuerzo !== '00:00') {
            detalle += ' Se descontó 01:00 de almuerzo.';
        } else {
            detalle += ' Sin descuento de almuerzo.';
        }
        if (datos.cruza_medianoche) {
            detalle += ' La salida se interpreta como el día siguiente.';
        }
        $('#ayuda-horas-ordinarias').text(detalle);
    }).fail(function (xhr) {
        if (numeroSolicitud !== solicitudCalculoHoras) {
            return;
        }

        $('#horas_ordinarias').val('00:00');
        $('#ayuda-horas-ordinarias').text(
            jornadaMensajeError(xhr, 'No fue posible calcular las horas.')
        );
    });
}

/**
 * Configura el rango inicial usado para consultar el historial.
 */
function inicializarRangoFechas() {
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

/**
 * Consulta el contexto operativo sin exponer información contable.
 */
function cargarContextoUsuario() {
    $.ajax({
        url: '../../controller/jornada.php?op=contextoUsuario',
        type: 'GET',
        dataType: 'json'
    }).done(function (respuesta) {
        const datos = respuesta.data || {};
        $('#texto-contexto').text(
            'Registro para ' +
            (datos.empleado || '') +
            (datos.documento ? ' — ' + datos.documento : '')
        );
    }).fail(function (xhr) {
        $('#alerta-contexto')
            .removeClass('alert-info')
            .addClass('alert-danger');
        $('#texto-contexto').text(
            jornadaMensajeError(xhr, 'No fue posible validar el usuario.')
        );
        $('#form-jornada :input').prop('disabled', true);
    });
}

/**
 * Genera la insignia del estado sin mostrar clasificaciones contables.
 */
function renderEstadoJornada(codigo, nombre) {
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
        '">' +
        jornadaEscapeHtml(nombre || codigo) +
        '</span>'
    );
}

/**
 * Construye las acciones permitidas por el estado operativo.
 */
function renderAccionesJornada(fila) {
    if (fila.estado_codigo !== 'BORRADOR') {
        return '<span class="text-muted">Sin acciones</span>';
    }

    return (
        '<div class="btn-group btn-group-sm">' +
        '<button type="button" class="btn btn-warning btn-editar" ' +
        'data-id="' + Number(fila.jornada_id) + '" title="Editar">' +
        '<i class="fas fa-edit"></i></button>' +
        '<button type="button" class="btn btn-success btn-enviar" ' +
        'data-id="' + Number(fila.jornada_id) + '" title="Enviar a aprobación">' +
        '<i class="fas fa-paper-plane"></i></button>' +
        '</div>'
    );
}

/**
 * Carga el historial propio mediante DataTables.
 */
function cargarMisJornadas() {
    const rango = $('#filtro_fechas').val().split(' - ');
    const fechaDesde = rango.length === 2 ? rango[0] : '';
    const fechaHasta = rango.length === 2 ? rango[1] : '';

    if ($.fn.DataTable.isDataTable('#tabla-jornadas')) {
        $('#tabla-jornadas').DataTable().destroy();
    }

    tablaJornadas = $('#tabla-jornadas').DataTable({
        processing: true,
        responsive: true,
        autoWidth: false,
        order: [[1, 'desc']],
        pageLength: 10,
        ajax: {
            url: '../../controller/jornada.php?op=listarMisJornadas',
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
                    text: jornadaMensajeError(
                        xhr,
                        'No se pudo cargar el historial de jornadas.'
                    )
                });
            }
        },
        columns: [
            { data: 'dia' },
            { data: 'fecha' },
            { data: 'hora_entrada' },
            {
                data: null,
                render: function (data, type, fila) {
                    const cambiaFecha = fila.fecha_salida !== fila.fecha;
                    return jornadaEscapeHtml(
                        fila.hora_salida + (cambiaFecha ? ' (+1 día)' : '')
                    );
                }
            },
            { data: 'horas_ordinarias' },
            {
                data: 'ubicacion',
                render: function (data) {
                    return jornadaEscapeHtml(data);
                }
            },
            {
                data: 'actividad',
                className: 'jornada-actividad',
                render: function (data) {
                    return jornadaEscapeHtml(data);
                }
            },
            {
                data: null,
                render: function (data, type, fila) {
                    return renderEstadoJornada(
                        fila.estado_codigo,
                        fila.estado_nombre
                    );
                }
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                render: function (data, type, fila) {
                    return renderAccionesJornada(fila);
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
            emptyTable: 'No existen jornadas en el periodo',
            paginate: {
                first: 'Primero',
                previous: 'Anterior',
                next: 'Siguiente',
                last: 'Último'
            }
        }
    });
}

/**
 * Restablece el formulario y selecciona la fecha actual.
 */
function limpiarFormularioJornada() {
    $('#form-jornada')[0].reset();
    $('#jornada_id').val('');
    $('#fecha').val(moment().format('YYYY-MM-DD'));
    $('#fecha').attr('max', moment().format('YYYY-MM-DD'));
    $('#salida_dia_siguiente').prop('checked', false);
    $('#horas_ordinarias').val('00:00');
    actualizarDiaSemana();
    calcularHorasJornada();
    $('#btn-guardar').html(
        '<i class="fas fa-save mr-1"></i>Guardar borrador'
    );
}

/**
 * Guarda el formulario después de confirmar el cruce de medianoche.
 */
function guardarBorrador(cruzaMedianoche) {
    const datos = {
        csrf_token: $('#csrf_token').val(),
        jornada_id: $('#jornada_id').val(),
        fecha: $('#fecha').val(),
        hora_entrada: $('#hora_entrada').val(),
        hora_salida: $('#hora_salida').val(),
        ubicacion: $('#ubicacion').val(),
        actividad: $('#actividad').val(),
        observaciones: $('#observaciones').val(),
        cruza_medianoche: cruzaMedianoche ? 1 : 0
    };

    $('#btn-guardar').prop('disabled', true);

    $.ajax({
        url: '../../controller/jornada.php?op=guardarBorrador',
        type: 'POST',
        dataType: 'json',
        data: datos
    }).done(function (respuesta) {
        Swal.fire({
            icon: 'success',
            title: 'Borrador guardado',
            text: respuesta.message,
            timer: 1800,
            showConfirmButton: false
        });
        limpiarFormularioJornada();
        cargarMisJornadas();
    }).fail(function (xhr) {
        Swal.fire({
            icon: 'error',
            title: 'No fue posible guardar',
            text: jornadaMensajeError(xhr, 'Revise la información registrada.')
        });
    }).always(function () {
        $('#btn-guardar').prop('disabled', false);
    });
}

/**
 * Carga un borrador propio para edición.
 */
function editarJornada(jornadaId) {
    $.ajax({
        url: '../../controller/jornada.php?op=obtenerMiJornada',
        type: 'GET',
        dataType: 'json',
        data: { jornada_id: jornadaId }
    }).done(function (respuesta) {
        const fila = respuesta.data;

        if (fila.estado_codigo !== 'BORRADOR') {
            Swal.fire(
                'Registro no editable',
                'La jornada ya fue enviada a aprobación.',
                'warning'
            );
            return;
        }

        $('#jornada_id').val(fila.jornada_id);
        $('#fecha').val(fila.fecha);
        $('#hora_entrada').val(fila.hora_entrada);
        $('#hora_salida').val(fila.hora_salida);
        $('#salida_dia_siguiente').prop(
            'checked',
            Boolean(fila.cruza_medianoche)
        );
        $('#ubicacion').val(fila.ubicacion);
        $('#actividad').val(fila.actividad);
        $('#observaciones').val(fila.observaciones || '');
        actualizarDiaSemana();
        calcularHorasJornada();
        $('#btn-guardar').html(
            '<i class="fas fa-save mr-1"></i>Actualizar borrador'
        );
        $('html, body').animate({ scrollTop: 0 }, 300);
    }).fail(function (xhr) {
        Swal.fire({
            icon: 'error',
            title: 'No fue posible consultar',
            text: jornadaMensajeError(xhr, 'No se pudo consultar la jornada.')
        });
    });
}

/**
 * Envía un borrador a la bandeja de los jefes relacionados.
 */
function enviarAprobacion(jornadaId) {
    Swal.fire({
        icon: 'question',
        title: 'Enviar a aprobación',
        text: 'Después de enviarla no podrá editar esta jornada.',
        showCancelButton: true,
        confirmButtonText: 'Enviar',
        cancelButtonText: 'Cancelar'
    }).then(function (resultado) {
        if (!resultado.isConfirmed) {
            return;
        }

        $.ajax({
            url: '../../controller/jornada.php?op=enviarAprobacion',
            type: 'POST',
            dataType: 'json',
            data: {
                csrf_token: $('#csrf_token').val(),
                jornada_id: jornadaId
            }
        }).done(function (respuesta) {
            Swal.fire({
                icon: 'success',
                title: 'Jornada enviada',
                text: respuesta.message
            });
            cargarMisJornadas();
        }).fail(function (xhr) {
            Swal.fire({
                icon: 'error',
                title: 'No fue posible enviar',
                text: jornadaMensajeError(xhr, 'Intente nuevamente.')
            });
        });
    });
}

$(document).ready(function () {
    inicializarRangoFechas();
    limpiarFormularioJornada();
    cargarContextoUsuario();
    cargarMisJornadas();
});

$('#fecha').on('change', function () {
    actualizarDiaSemana();
    calcularHorasJornada();
});

$('#hora_entrada, #hora_salida').on('change', calcularHorasJornada);

$('#salida_dia_siguiente').on('change', calcularHorasJornada);

$('#btn-limpiar').on('click', limpiarFormularioJornada);

$('#btn-filtrar').on('click', cargarMisJornadas);

$('#btn-limpiar-filtro').on('click', function () {
    const inicio = moment().startOf('month');
    const fin = moment();
    const selector = $('#filtro_fechas').data('daterangepicker');
    selector.setStartDate(inicio);
    selector.setEndDate(fin);
    $('#filtro_fechas').val(
        inicio.format('YYYY-MM-DD') + ' - ' + fin.format('YYYY-MM-DD')
    );
    cargarMisJornadas();
});

$('#form-jornada').on('submit', function (evento) {
    evento.preventDefault();

    const entrada = $('#hora_entrada').val();
    const salida = $('#hora_salida').val();
    const salidaDiaSiguiente = $('#salida_dia_siguiente').is(':checked');

    if (!entrada || !salida) {
        Swal.fire('Información requerida', 'Ingrese entrada y salida.', 'warning');
        return;
    }

    if (salida <= entrada && !salidaDiaSiguiente) {
        Swal.fire({
            icon: 'question',
            title: 'La jornada cruza medianoche',
            text: '¿La hora de salida corresponde al día siguiente?',
            showCancelButton: true,
            confirmButtonText: 'Sí, continúa al día siguiente',
            cancelButtonText: 'Revisar horas'
        }).then(function (resultado) {
            if (resultado.isConfirmed) {
                $('#salida_dia_siguiente').prop('checked', true);
                calcularHorasJornada();
                guardarBorrador(true);
            }
        });
        return;
    }

    guardarBorrador(salidaDiaSiguiente);
});

$(document).on('click', '.btn-editar', function () {
    editarJornada(Number($(this).data('id')));
});

$(document).on('click', '.btn-enviar', function () {
    enviarAprobacion(Number($(this).data('id')));
});
