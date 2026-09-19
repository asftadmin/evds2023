let tablaConfirmacionJornadas = null;
let jornadasConfirmacion = [];
let firmaConfirmacionDibujada = false;
let confirmacionEnProceso = false;

/**
 * Extrae el mensaje JSON retornado por el controller.
 */
function mensajeErrorConfirmacion(xhr, mensajeDefault) {
    if (
        xhr.responseJSON &&
        xhr.responseJSON.message
    ) {
        return xhr.responseJSON.message;
    }

    return mensajeDefault;
}

/**
 * Obtiene el rango seleccionado actualmente.
 */
function obtenerRangoConfirmacion() {
    const control = $('#filtro_fechas_confirmacion').data(
        'daterangepicker'
    );

    if (!control) {
        return null;
    }

    return {
        fecha_desde: control.startDate.format('YYYY-MM-DD'),
        fecha_hasta: control.endDate.format('YYYY-MM-DD')
    };
}

/**
 * Inicializa el selector de periodo.
 */
function inicializarRangoConfirmacion() {
    const inicio = moment().startOf('month');
    const fin = moment();

    $('#filtro_fechas_confirmacion').daterangepicker({
        startDate: inicio,
        endDate: fin,
        autoUpdateInput: true,
        locale: {
            format: 'DD/MM/YYYY',
            separator: ' - ',
            applyLabel: 'Aplicar',
            cancelLabel: 'Cancelar',
            fromLabel: 'Desde',
            toLabel: 'Hasta',
            customRangeLabel: 'Personalizado',
            weekLabel: 'S',
            daysOfWeek: [
                'Do',
                'Lu',
                'Ma',
                'Mi',
                'Ju',
                'Vi',
                'Sá'
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
        }
    });
}

/**
 * Limpia la información visible de una consulta anterior.
 */
function limpiarConsultaConfirmacion() {
    jornadasConfirmacion = [];

    $('#bloque-identificacion-empleado').hide();
    $('#resumen-jornadas-confirmacion').hide();
    $('#mensaje-sin-jornadas').hide();
    $('#contenedor-tabla-confirmacion').hide();
    $('#acciones-confirmacion').hide();

    $('#btn-revisar-firmar').prop('disabled', true);

    if (tablaConfirmacionJornadas) {
        tablaConfirmacionJornadas.clear().draw();
    }
}

/**
 * Consulta los datos del empleado autenticado.
 */
function cargarContextoConfirmacion() {
    $.ajax({
        url: '../../controller/jornada.php?op=contextoConfirmacionObra',
        type: 'GET',
        dataType: 'json'
    }).done(function (respuesta) {
        if (!respuesta.success) {
            return;
        }

        $('#confirmacion-empleado').text(
            respuesta.data.empleado || '-'
        );

        $('#confirmacion-documento').text(
            respuesta.data.documento || '-'
        );
    }).fail(function (xhr) {
        Swal.fire({
            icon: 'error',
            title: 'No fue posible cargar la información',
            text: mensajeErrorConfirmacion(
                xhr,
                'No se pudo consultar el empleado autenticado.'
            )
        });
    });
}

/**
 * Inicializa la tabla de jornadas pendientes de confirmación.
 */
function inicializarTablaConfirmacion() {
    tablaConfirmacionJornadas = $(
        '#tabla-jornadas-confirmacion'
    ).DataTable({
        data: [],
        responsive: true,
        autoWidth: false,
        searching: false,
        ordering: true,
        order: [
            [1, 'asc'],
            [2, 'asc']
        ],
        pageLength: 10,
        lengthChange: false,
        language: {
            emptyTable: 'No existen jornadas pendientes de confirmación.',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ jornadas',
            infoEmpty: 'No existen jornadas',
            paginate: {
                previous: 'Anterior',
                next: 'Siguiente'
            }
        },
        columns: [
            {
                data: 'dia'
            },
            {
                data: 'fecha',
                render: function (data) {
                    return moment(
                        data,
                        'YYYY-MM-DD'
                    ).format('DD/MM/YYYY');
                }
            },
            {
                data: 'hora_entrada',
                className: 'text-center'
            },
            {
                data: 'hora_salida',
                className: 'text-center'
            },
            {
                data: 'horas_ordinarias',
                className: 'text-center'
            },
            {
                data: 'ubicacion'
            },
            {
                data: 'actividad',
                className: 'actividad-jornada'
            },
            {
                data: 'observaciones',
                defaultContent: ''
            },
            {
                data: 'estado_nombre',
                className: 'text-center',
                render: function (data) {
                    return (
                        '<span class="badge badge-secondary">' +
                        $('<div>').text(
                            data || 'Borrador'
                        ).html() +
                        '</span>'
                    );
                }
            }
        ]
    });
}

/**
 * Carga las jornadas retornadas por el servidor.
 */
function cargarTablaConfirmacion(datos) {
    if (!tablaConfirmacionJornadas) {
        return;
    }

    tablaConfirmacionJornadas
        .clear()
        .rows.add(datos)
        .draw();

    setTimeout(function () {
        tablaConfirmacionJornadas
            .columns.adjust()
            .responsive.recalc();
    }, 100);
}

/**
 * Consulta las jornadas que el empleado puede confirmar.
 */
function consultarJornadasConfirmacion() {
    if (confirmacionEnProceso) {
        return;
    }

    const rango = obtenerRangoConfirmacion();

    if (!rango) {
        Swal.fire({
            icon: 'warning',
            title: 'Seleccione un periodo',
            text: 'Debe seleccionar un rango de fechas válido.'
        });

        return;
    }

    limpiarConsultaConfirmacion();

    confirmacionEnProceso = true;

    $('#btn-consultar-jornadas')
        .prop('disabled', true)
        .html(
            '<span class="spinner-border spinner-border-sm mr-1"></span>' +
            'Consultando...'
        );

    $.ajax({
        url: '../../controller/jornada.php?op=consultarJornadasConfirmacionObra',
        type: 'GET',
        dataType: 'json',
        data: {
            fecha_desde: rango.fecha_desde,
            fecha_hasta: rango.fecha_hasta
        }
    }).done(function (respuesta) {
        jornadasConfirmacion = respuesta.data || [];

        $('#confirmacion-periodo').text(
            moment(rango.fecha_desde).format('DD/MM/YYYY') +
            ' al ' +
            moment(rango.fecha_hasta).format('DD/MM/YYYY')
        );

        $('#bloque-identificacion-empleado').show();
        $('#resumen-jornadas-confirmacion').show();

        if (jornadasConfirmacion.length === 0) {
            $('#badge-jornadas-confirmacion')
                .removeClass(
                    'badge-warning badge-success badge-primary'
                )
                .addClass('badge-secondary')
                .html(
                    '<i class="fas fa-check-circle mr-1"></i>' +
                    'Sin jornadas pendientes'
                );

            $('#mensaje-sin-jornadas').show();

            return;
        }

        $('#badge-jornadas-confirmacion')
            .removeClass(
                'badge-secondary badge-success badge-primary'
            )
            .addClass('badge-warning')
            .html(
                '<i class="fas fa-clock mr-1"></i>' +
                jornadasConfirmacion.length +
                ' jornada(s) pendiente(s) de confirmación'
            );

        cargarTablaConfirmacion(
            jornadasConfirmacion
        );

        $('#contenedor-tabla-confirmacion').show();
        $('#acciones-confirmacion').show();
        $('#btn-revisar-firmar').prop(
            'disabled',
            false
        );
    }).fail(function (xhr) {
        Swal.fire({
            icon: 'error',
            title: 'No fue posible consultar',
            text: mensajeErrorConfirmacion(
                xhr,
                'No se pudieron consultar las jornadas pendientes.'
            )
        });
    }).always(function () {
        confirmacionEnProceso = false;

        $('#btn-consultar-jornadas')
            .prop('disabled', false)
            .html(
                '<i class="fas fa-search mr-1"></i>' +
                'Consultar'
            );
    });
}

/**
 * Inicializa los eventos para capturar la firma con mouse o pantalla táctil.
 */
function inicializarCanvasFirmaConfirmacion() {
    const canvas = document.getElementById(
        'canvasFirmaConfirmacion'
    );

    if (!canvas) {
        return;
    }

    const ctx = canvas.getContext('2d');
    let dibujando = false;

    ctx.lineWidth = 3;
    ctx.strokeStyle = '#000';
    ctx.lineJoin = 'round';
    ctx.lineCap = 'round';

    // Convierte la posición visual a la resolución interna del canvas.
    function obtenerPosicion(evento) {
        const rect = canvas.getBoundingClientRect();

        let clienteX;
        let clienteY;

        if (
            evento.touches &&
            evento.touches.length > 0
        ) {
            clienteX = evento.touches[0].clientX;
            clienteY = evento.touches[0].clientY;
        } else {
            clienteX = evento.clientX;
            clienteY = evento.clientY;
        }

        return {
            x:
                (clienteX - rect.left) *
                (canvas.width / rect.width),
            y:
                (clienteY - rect.top) *
                (canvas.height / rect.height)
        };
    }

    function iniciarDibujo(evento) {
        evento.preventDefault();

        dibujando = true;

        const posicion = obtenerPosicion(
            evento
        );

        ctx.beginPath();
        ctx.moveTo(
            posicion.x,
            posicion.y
        );
    }

    function dibujar(evento) {
        if (!dibujando) {
            return;
        }

        evento.preventDefault();

        const posicion = obtenerPosicion(
            evento
        );

        ctx.lineTo(
            posicion.x,
            posicion.y
        );

        ctx.stroke();

        firmaConfirmacionDibujada = true;

        $('#btn-confirmar-firma-jornadas')
            .prop('disabled', false);
    }

    function detenerDibujo(evento) {
        if (evento) {
            evento.preventDefault();
        }

        dibujando = false;
    }

    // Mouse.
    canvas.addEventListener(
        'mousedown',
        iniciarDibujo
    );

    canvas.addEventListener(
        'mousemove',
        dibujar
    );

    canvas.addEventListener(
        'mouseup',
        detenerDibujo
    );

    canvas.addEventListener(
        'mouseleave',
        detenerDibujo
    );

    // Pantallas táctiles.
    canvas.addEventListener(
        'touchstart',
        iniciarDibujo,
        {
            passive: false
        }
    );

    canvas.addEventListener(
        'touchmove',
        dibujar,
        {
            passive: false
        }
    );

    canvas.addEventListener(
        'touchend',
        detenerDibujo,
        {
            passive: false
        }
    );

    canvas.addEventListener(
        'touchcancel',
        detenerDibujo,
        {
            passive: false
        }
    );
}

/**
 * Limpia la firma capturada.
 */
function limpiarFirmaConfirmacion() {
    const canvas = document.getElementById(
        'canvasFirmaConfirmacion'
    );

    if (!canvas) {
        return;
    }

    const ctx = canvas.getContext('2d');

    ctx.clearRect(
        0,
        0,
        canvas.width,
        canvas.height
    );

    firmaConfirmacionDibujada = false;

    $('#btn-confirmar-firma-jornadas')
        .prop('disabled', true);
}

/**
 * Abre el modal con el periodo y cantidad que el empleado acaba de revisar.
 */
function abrirModalFirmaConfirmacion() {
    if (jornadasConfirmacion.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No hay jornadas',
            text: 'Consulte primero las jornadas pendientes de confirmación.'
        });

        return;
    }

    const rango = obtenerRangoConfirmacion();

    if (!rango) {
        return;
    }

    limpiarFirmaConfirmacion();

    $('#firma-confirmacion-periodo').text(
        moment(rango.fecha_desde).format(
            'DD/MM/YYYY'
        ) +
        ' al ' +
        moment(rango.fecha_hasta).format(
            'DD/MM/YYYY'
        )
    );

    $('#firma-confirmacion-cantidad').text(
        jornadasConfirmacion.length
    );

    $('#modalFirmaConfirmacion').modal(
        'show'
    );
}

/**
 * Muestra el bloqueo visual mientras el servidor procesa la firma.
 */
function mostrarProcesandoConfirmacion() {
    $('#btn-confirmar-firma-jornadas')
        .prop('disabled', true);

    $('#btn-limpiar-firma-confirmacion')
        .prop('disabled', true);

    $('#btn-revisar-firmar')
        .prop('disabled', true);

    $('#btn-consultar-jornadas')
        .prop('disabled', true);

    Swal.fire({
        title: 'Procesando confirmación',
        html:
            'Estamos registrando la firma y confirmando las jornadas.',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: function () {
            Swal.showLoading();
        }
    });
}

/**
 * Restablece los controles después de una operación.
 */
function finalizarProcesandoConfirmacion() {
    confirmacionEnProceso = false;

    $('#btn-limpiar-firma-confirmacion')
        .prop('disabled', false);

    $('#btn-consultar-jornadas')
        .prop('disabled', false);

    $('#btn-revisar-firmar')
        .prop(
            'disabled',
            jornadasConfirmacion.length === 0
        );

    $('#btn-confirmar-firma-jornadas')
        .prop(
            'disabled',
            !firmaConfirmacionDibujada
        );
}

/**
 * Envía al servidor exclusivamente rango, CSRF y firma.
 */
function confirmarFirmaJornadasObra() {
    if (
        confirmacionEnProceso ||
        !firmaConfirmacionDibujada ||
        jornadasConfirmacion.length === 0
    ) {
        return;
    }

    const rango = obtenerRangoConfirmacion();

    const canvas = document.getElementById(
        'canvasFirmaConfirmacion'
    );

    if (!rango || !canvas) {
        return;
    }

    const firma = canvas.toDataURL(
        'image/png'
    );

    Swal.fire({
        icon: 'question',
        title: 'Confirmar jornadas',
        html:
            'Va a confirmar <strong>' +
            jornadasConfirmacion.length +
            '</strong> jornada(s) correspondientes al periodo ' +
            '<strong>' +
            moment(
                rango.fecha_desde
            ).format('DD/MM/YYYY') +
            '</strong> al <strong>' +
            moment(
                rango.fecha_hasta
            ).format('DD/MM/YYYY') +
            '</strong>.<br><br>' +
            'Al continuar confirma que las jornadas corresponden al tiempo laborado.',
        showCancelButton: true,
        confirmButtonText:
            'Sí, confirmar jornadas',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#28a745'
    }).then(function (resultado) {
        if (!resultado.isConfirmed) {
            return;
        }

        confirmacionEnProceso = true;

        mostrarProcesandoConfirmacion();

        $.ajax({
            url: '../../controller/jornada.php?op=confirmarJornadasObra',
            type: 'POST',
            dataType: 'json',
            data: {
                csrf_token: $('#csrf_token').val(),
                fecha_desde: rango.fecha_desde,
                fecha_hasta: rango.fecha_hasta,
                firma: firma
            }
        }).done(function (respuesta) {
            $('#modalFirmaConfirmacion').modal(
                'hide'
            );

            limpiarFirmaConfirmacion();

            jornadasConfirmacion = [];

            Swal.fire({
                icon: 'success',
                title: 'Jornadas confirmadas',
                text:
                    respuesta.message ||
                    'Las jornadas fueron confirmadas correctamente.'
            }).then(function () {
                consultarJornadasConfirmacion();
            });
        }).fail(function (xhr) {
            Swal.fire({
                icon: 'error',
                title: 'No fue posible confirmar',
                text: mensajeErrorConfirmacion(
                    xhr,
                    'No se pudo completar la confirmación de las jornadas.'
                )
            });
        }).always(function () {
            finalizarProcesandoConfirmacion();
        });
    });
}

/**
 * Inicialización general de la vista.
 */
$(document).ready(function () {
    inicializarRangoConfirmacion();
    inicializarTablaConfirmacion();
    inicializarCanvasFirmaConfirmacion();

    limpiarConsultaConfirmacion();
    cargarContextoConfirmacion();
});

/**
 * Consulta las jornadas del rango seleccionado.
 */
$('#btn-consultar-jornadas').on(
    'click',
    function () {
        consultarJornadasConfirmacion();
    }
);

/**
 * Invalida visualmente una consulta anterior al cambiar el periodo.
 */
$('#filtro_fechas_confirmacion').on(
    'apply.daterangepicker',
    function () {
        limpiarConsultaConfirmacion();
    }
);

/**
 * Abre el modal de revisión y firma.
 */
$('#btn-revisar-firmar').on(
    'click',
    function () {
        abrirModalFirmaConfirmacion();
    }
);

/**
 * Limpia el canvas.
 */
$('#btn-limpiar-firma-confirmacion').on(
    'click',
    function () {
        limpiarFirmaConfirmacion();
    }
);

/**
 * Ejecuta la confirmación definitiva.
 */
$('#btn-confirmar-firma-jornadas').on(
    'click',
    function () {
        confirmarFirmaJornadasObra();
    }
);

/**
 * Elimina cualquier firma no enviada al cerrar el modal.
 */
$('#modalFirmaConfirmacion').on(
    'hidden.bs.modal',
    function () {
        if (!confirmacionEnProceso) {
            limpiarFirmaConfirmacion();
        }
    }
);