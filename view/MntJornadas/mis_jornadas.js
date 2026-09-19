/* =========================================================
 * VARIABLES GLOBALES
 * ========================================================= */

let tablaJornadas = null;
let solicitudCalculoHoras = 0;
let solicitudEdicion = 0;
let usuarioAutorizado = false;
let operacionEnCurso = false;
let ultimaBusquedaJornadas = '';

const jornadasSeleccionadas = new Set();

let tablaReporteFirma = null;
let jornadasReporteFirma = [];
let firmaReporteDibujada = false;
let reporteFirmaEnProceso = false;


/* =========================================================
 * UTILIDADES GENERALES DE LA VISTA
 * ========================================================= */

/**
 * Habilita o bloquea los controles según el estado operativo.
 */
function actualizarControlesJornada() {
    const bloqueado = !usuarioAutorizado || operacionEnCurso;

    $('#form-jornada :input').prop('disabled', bloqueado);
    $('#btn-guardar').prop('disabled', bloqueado);

    $('#btn-enviar-seleccionadas').prop(
        'disabled',
        bloqueado ||
        jornadasSeleccionadas.size === 0 ||
        jornadasSeleccionadas.size > 500
    );

    $('#tabla-jornadas button, #tabla-jornadas input[type="checkbox"], #btn-filtrar, #btn-limpiar-filtro, #filtro_fechas, #filtro_estado')
        .prop('disabled', bloqueado);
}


/**
 * Retorna los borradores visibles según el filtro actual de DataTables.
 */
function borradoresFiltrados() {
    if (!tablaJornadas) {
        return [];
    }

    return tablaJornadas.rows({ search: 'applied' }).data().toArray()
        .filter(function (fila) {
            return fila.estado_codigo === 'BORRADOR';
        })
        .map(function (fila) {
            return Number(fila.jornada_id);
        });
}


/**
 * Sincroniza los checkboxes de selección de jornadas.
 */
function actualizarSeleccionJornadas() {
    const ids = borradoresFiltrados();
    const permitidos = new Set(ids);

    jornadasSeleccionadas.forEach(function (id) {
        if (!permitidos.has(id)) {
            jornadasSeleccionadas.delete(id);
        }
    });

    $('#tabla-jornadas .seleccionar-jornada').each(function () {
        $(this).prop(
            'checked',
            jornadasSeleccionadas.has(Number($(this).data('id')))
        );
    });

    $('#seleccionar-jornadas')
        .prop(
            'checked',
            ids.length > 0 &&
            jornadasSeleccionadas.size === ids.length
        )
        .prop(
            'indeterminate',
            jornadasSeleccionadas.size > 0 &&
            jornadasSeleccionadas.size < ids.length
        );

    $('#conteo-seleccionadas').text(
        jornadasSeleccionadas.size + ' seleccionadas'
    );

    actualizarControlesJornada();
}


/**
 * Escapa texto antes de insertarlo en fragmentos HTML.
 */
function jornadaEscapeHtml(valor) {
    return $('<div>')
        .text(valor == null ? '' : String(valor))
        .html();
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


/* =========================================================
 * FORMULARIO DE JORNADA
 * ========================================================= */

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

    $('#dia_semana').val(
        dias[objetoFecha.getDay()]
    );
}


/**
 * Consulta al servidor las horas netas para evitar cálculos manipulables
 * en el navegador. La respuesta más reciente prevalece.
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

        $('#horas_ordinarias').val(
            datos.horas_ordinarias || '00:00'
        );

        let detalle =
            'Duración total: ' +
            (datos.duracion_total || '00:00') +
            '.';

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
            jornadaMensajeError(
                xhr,
                'No fue posible calcular las horas.'
            )
        );
    });
}


/* =========================================================
 * RANGOS DE FECHA
 * ========================================================= */

/**
 * Configura el rango inicial usado para consultar el historial.
 */
function inicializarRangoFechas() {
    const inicio = moment()
        .subtract(1, 'month')
        .startOf('month');

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
            ]
        }
    });
}


/**
 * Configura el rango utilizado para construir el reporte que firmará
 * el empleado.
 */
function inicializarRangoReporteFirma() {
    const inicio = moment().startOf('month');
    const fin = moment();

    $('#reporte_fechas').daterangepicker({
        startDate: inicio,
        endDate: fin,
        showDropdowns: true,
        maxDate: moment(),
        locale: {
            format: 'YYYY-MM-DD',
            separator: ' - ',
            applyLabel: 'Aplicar',
            cancelLabel: 'Cancelar',
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
        }
    });
}


/* =========================================================
 * CONTEXTO DEL USUARIO
 * ========================================================= */

/**
 * Consulta el contexto operativo del usuario.
 */
function cargarContextoUsuario() {
    $.ajax({
        url: '../../controller/jornada.php?op=contextoUsuario',
        type: 'GET',
        dataType: 'json'
    }).done(function (respuesta) {
        const datos = respuesta.data || {};

        usuarioAutorizado = true;

        actualizarControlesJornada();

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
            jornadaMensajeError(
                xhr,
                'No fue posible validar el usuario.'
            )
        );

        $('#form-jornada :input').prop(
            'disabled',
            true
        );
    });
}


/* =========================================================
 * REPRESENTACIÓN VISUAL DE ESTADOS Y ACCIONES
 * ========================================================= */

/**
 * Genera la insignia visual correspondiente al estado.
 */
function renderEstadoJornada(codigo, nombre) {
    const clases = {
        BORRADOR: 'badge-secondary',
        PENDIENTE_APROBACION: 'badge-warning',
        APROBADO: 'badge-success',
        RECHAZADO: 'badge-danger',
        PENDIENTE_CORRECCION: 'badge-info',
        CORREGIDO: 'badge-primary',
        ANULADO: 'badge-dark'
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
 * Construye las acciones disponibles para cada jornada.
 */
function renderAccionesJornada(fila) {
    if (fila.estado_codigo !== 'BORRADOR') {
        return '<span class="text-muted">Sin acciones</span>';
    }

    return (
        '<div class="jornada-acciones">' +

        '<button type="button" ' +
        'class="btn btn-sm btn-warning btn-editar" ' +
        'data-id="' + Number(fila.jornada_id) + '" ' +
        'title="Editar" aria-label="Editar borrador">' +
        '<i class="fas fa-edit"></i>' +
        '</button>' +

        '<button type="button" ' +
        'class="btn btn-sm btn-success btn-enviar" ' +
        'data-id="' + Number(fila.jornada_id) + '" ' +
        'title="Enviar a aprobación" aria-label="Enviar a aprobación">' +
        '<i class="fas fa-paper-plane"></i>' +
        '</button>' +

        '<button type="button" ' +
        'class="btn btn-sm btn-danger btn-anular" ' +
        'data-id="' + Number(fila.jornada_id) + '" ' +
        'title="Anular borrador" aria-label="Anular borrador">' +
        '<i class="fas fa-ban"></i>' +
        '</button>' +

        '</div>'
    );
}

/* =========================================================
 * DATATABLE DEL REPORTE PARA FIRMA
 * ========================================================= */

/**
 * Inicializa o actualiza la tabla que muestra las jornadas
 * que formarán parte del reporte firmado.
 */
function cargarTablaReporteFirma(datos) {
    jornadasReporteFirma = Array.isArray(datos) ? datos : [];

    if (tablaReporteFirma) {
        tablaReporteFirma.clear();
        tablaReporteFirma.rows.add(jornadasReporteFirma);
        tablaReporteFirma.draw();
        return;
    }

    tablaReporteFirma = $('#tabla-reporte-firma').DataTable({
        data: jornadasReporteFirma,
        responsive: true,
        autoWidth: false,
        order: [[0, 'asc']],
        pageLength: 10,

        columns: [
            {
                data: 'jornada_inicio',

                render: function (data, type) {
                    if (!data) {
                        return '';
                    }

                    if (type !== 'display') {
                        return data;
                    }

                    return moment(data).format('DD/MM/YYYY');
                }
            },
            {
                data: 'jornada_inicio',

                render: function (data) {
                    if (!data) {
                        return '';
                    }

                    const dias = [
                        'Domingo',
                        'Lunes',
                        'Martes',
                        'Miércoles',
                        'Jueves',
                        'Viernes',
                        'Sábado'
                    ];

                    return dias[moment(data).day()];
                }
            },
            {
                data: 'jornada_inicio',

                render: function (data) {
                    return data
                        ? moment(data).format('hh:mm A')
                        : '';
                }
            },
            {
                data: 'jornada_fin',

                render: function (data, type, fila) {
                    if (!data) {
                        return '';
                    }

                    let salida = moment(data).format('hh:mm A');

                    if (
                        fila.jornada_inicio &&
                        moment(data).format('YYYY-MM-DD') !==
                        moment(fila.jornada_inicio).format('YYYY-MM-DD')
                    ) {
                        salida += ' (+1 día)';
                    }

                    return salida;
                }
            },
            {
                data: 'jornada_minutos_ordinarios',

                render: function (data) {
                    return formatearMinutosJornada(data);
                }
            },
            {
                data: 'jornada_ubicacion',

                render: function (data) {
                    return jornadaEscapeHtml(data);
                }
            },
            {
                data: 'jornada_actividad',

                render: function (data) {
                    return jornadaEscapeHtml(data);
                }
            }
        ],

        language: {
            processing: 'Procesando...',
            search: 'Buscar:',
            lengthMenu: 'Mostrar _MENU_ registros',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ jornadas',
            infoEmpty: 'No hay jornadas',
            zeroRecords: 'No se encontraron jornadas',
            emptyTable: 'No existen jornadas aprobadas para firmar',

            paginate: {
                first: 'Primero',
                previous: 'Anterior',
                next: 'Siguiente',
                last: 'Último'
            }
        }
    });
}


/* =========================================================
 * DATATABLE DEL HISTORIAL
 * ========================================================= */

/**
 * Carga el historial propio mediante DataTables.
 */
function cargarMisJornadas() {
    jornadasSeleccionadas.clear();

    if (tablaJornadas) {
        actualizarSeleccionJornadas();
        tablaJornadas.ajax.reload();
        return;
    }

    tablaJornadas = $('#tabla-jornadas').DataTable({
        processing: true,
        responsive: true,
        autoWidth: false,
        order: [[2, 'desc']],
        pageLength: 10,

        ajax: {
            url: '../../controller/jornada.php?op=listarMisJornadas',
            type: 'GET',
            dataType: 'json',

            data: function (datos) {
                const rango = $('#filtro_fechas')
                    .val()
                    .split(' - ');

                datos.fecha_desde =
                    rango.length === 2
                        ? rango[0]
                        : '';

                datos.fecha_hasta =
                    rango.length === 2
                        ? rango[1]
                        : '';
            },

            dataSrc: function (respuesta) {
                const estado = $('#filtro_estado').val();

                return (respuesta.data || []).filter(function (fila) {
                    return !estado || fila.estado_codigo === estado;
                });
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
            {
                data: null,
                orderable: false,
                searchable: false,
                className: 'all text-center',

                render: function (data, type, fila) {
                    if (
                        type !== 'display' ||
                        fila.estado_codigo !== 'BORRADOR'
                    ) {
                        return '';
                    }

                    return (
                        '<input type="checkbox" ' +
                        'class="seleccionar-jornada" ' +
                        'data-id="' + Number(fila.jornada_id) + '" ' +
                        'aria-label="Seleccionar jornada ' +
                        jornadaEscapeHtml(fila.fecha) +
                        '">'
                    );
                }
            },

            {
                data: 'dia'
            },

            {
                data: 'fecha',
                responsivePriority: 1
            },

            {
                data: 'hora_entrada'
            },

            {
                data: null,

                render: function (data, type, fila) {
                    const cambiaFecha =
                        fila.fecha_salida !== fila.fecha;

                    return jornadaEscapeHtml(
                        fila.hora_salida +
                        (cambiaFecha ? ' (+1 día)' : '')
                    );
                }
            },

            {
                data: 'horas_ordinarias'
            },

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
                    if (type !== 'display') {
                        return fila.estado_nombre;
                    }

                    let estado = renderEstadoJornada(
                        fila.estado_codigo,
                        fila.estado_nombre
                    );

                    if (
                        fila.estado_codigo === 'ANULADO' &&
                        fila.anulacion_motivo
                    ) {
                        estado +=
                            '<small class="jornada-anulacion text-muted">' +
                            jornadaEscapeHtml(
                                fila.anulacion_motivo
                            ) +
                            '<br>' +
                            jornadaEscapeHtml(
                                moment(
                                    fila.anulacion_fecha
                                ).format(
                                    'DD/MM/YYYY HH:mm'
                                )
                            ) +
                            '</small>';
                    }

                    return estado;
                }
            },

            {
                data: null,
                orderable: false,
                searchable: false,
                className: 'all',

                render: function (data, type, fila) {
                    return renderAccionesJornada(fila);
                }
            }
        ],

        drawCallback: actualizarSeleccionJornadas,

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


/* =========================================================
 * LIMPIEZA DEL FORMULARIO
 * ========================================================= */

/**
 * Restablece el formulario y selecciona la fecha actual.
 */
function limpiarFormularioJornada() {
    ++solicitudEdicion;

    $('#ubicacion option[data-anterior]').remove();

    $('#form-jornada')[0].reset();

    $('#jornada_id').val('');

    $('#fecha')
        .val(moment().format('YYYY-MM-DD'))
        .attr(
            'max',
            moment().format('YYYY-MM-DD')
        )
        .removeClass('is-invalid');

    // Ya no se valida exclusividad por fecha.
    $('#ayuda-fecha')
        .removeClass('text-danger')
        .addClass('text-muted')
        .text('');

    $('#salida_dia_siguiente').prop(
        'checked',
        false
    );

    $('#horas_ordinarias').val('00:00');

    actualizarDiaSemana();
    calcularHorasJornada();

    $('#btn-guardar').html(
        '<i class="fas fa-save mr-1"></i>Guardar borrador'
    );
}


/* =========================================================
 * GUARDAR BORRADOR
 * ========================================================= */

/**
 * Guarda el formulario después de confirmar el cruce de medianoche.
 */
function guardarBorrador(cruzaMedianoche) {
    if (
        operacionEnCurso ||
        !usuarioAutorizado
    ) {
        return;
    }

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

    operacionEnCurso = true;

    ++solicitudEdicion;

    actualizarControlesJornada();

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
            text: jornadaMensajeError(
                xhr,
                'Revise la información registrada.'
            )
        });

    }).always(function () {
        operacionEnCurso = false;
        actualizarControlesJornada();
    });
}


/* =========================================================
 * EDITAR BORRADOR
 * ========================================================= */

/**
 * Carga un borrador propio para edición.
 */
function editarJornada(jornadaId) {
    if (
        operacionEnCurso ||
        !usuarioAutorizado
    ) {
        return;
    }

    const solicitud = ++solicitudEdicion;

    $.ajax({
        url: '../../controller/jornada.php?op=obtenerMiJornada',
        type: 'GET',
        dataType: 'json',
        data: {
            jornada_id: jornadaId
        }

    }).done(function (respuesta) {
        if (solicitud !== solicitudEdicion) {
            return;
        }

        const fila = respuesta.data;

        if (fila.estado_codigo !== 'BORRADOR') {
            Swal.fire(
                'Registro no editable',
                'La jornada ya fue enviada a aprobación.',
                'warning'
            );

            return;
        }

        $('#jornada_id').val(
            fila.jornada_id
        );

        $('#fecha')
            .val(fila.fecha)
            .removeClass('is-invalid');

        $('#ayuda-fecha')
            .removeClass('text-danger')
            .addClass('text-muted')
            .text('');

        $('#hora_entrada').val(
            fila.hora_entrada
        );

        $('#hora_salida').val(
            fila.hora_salida
        );

        $('#salida_dia_siguiente').prop(
            'checked',
            Boolean(fila.cruza_medianoche)
        );

        $('#ubicacion option[data-anterior]')
            .remove();

        if (
            ![
                'Sede principal',
                'Obras varias'
            ].includes(fila.ubicacion)
        ) {
            $('<option>')
                .val(fila.ubicacion)
                .text(
                    fila.ubicacion +
                    ' (ubicación anterior)'
                )
                .attr(
                    'data-anterior',
                    '1'
                )
                .appendTo('#ubicacion');
        }

        $('#ubicacion').val(
            fila.ubicacion
        );

        $('#actividad').val(
            fila.actividad
        );

        $('#observaciones').val(
            fila.observaciones || ''
        );

        actualizarDiaSemana();
        calcularHorasJornada();

        $('#btn-guardar').html(
            '<i class="fas fa-save mr-1"></i>Actualizar borrador'
        );

        $('html, body').animate(
            {
                scrollTop: 0
            },
            300
        );

    }).fail(function (xhr) {
        Swal.fire({
            icon: 'error',
            title: 'No fue posible consultar',
            text: jornadaMensajeError(
                xhr,
                'No se pudo consultar la jornada.'
            )
        });
    });
}

/* =========================================================
 * ENVÍO A APROBACIÓN
 * ========================================================= */

/**
 * Envía un borrador individual a aprobación.
 */
function enviarAprobacion(jornadaId) {
    enviarJornadas([
        jornadaId
    ]);
}


/**
 * Envía una o varias jornadas a aprobación.
 */
function enviarJornadas(ids) {
    if (
        operacionEnCurso ||
        !usuarioAutorizado ||
        ids.length === 0 ||
        ids.length > 500
    ) {
        return;
    }

    operacionEnCurso = true;

    ++solicitudEdicion;

    actualizarControlesJornada();

    Swal.fire({
        icon: 'question',
        title:
            'Enviar ' +
            ids.length +
            ' jornada(s) a aprobación',
        text:
            'Se enviarán los borradores guardados. ' +
            'Después del envío no podrá editarlos ni anularlos.',
        showCancelButton: true,
        confirmButtonText: 'Enviar',
        cancelButtonText: 'Cancelar'

    }).then(function (resultado) {
        if (!resultado.isConfirmed) {
            operacionEnCurso = false;
            actualizarControlesJornada();
            return;
        }

        $.ajax({
            url: '../../controller/jornada.php?op=enviarAprobacionMasiva',
            type: 'POST',
            dataType: 'json',

            data: {
                csrf_token: $('#csrf_token').val(),
                jornada_ids: ids
            }

        }).done(function (respuesta) {
            const resultado = respuesta.data;
            const fallidos = resultado.fallidos || [];
            const enviados = resultado.enviados || [];

            const resumen =
                enviados.length +
                ' jornada(s) enviada(s). ' +
                fallidos.length +
                ' sin enviar.';

            const panel = $('#resultado-envio')
                .empty()
                .removeClass(
                    'd-none alert-success alert-warning'
                )
                .addClass(
                    fallidos.length
                        ? 'alert-warning'
                        : 'alert-success'
                );

            $('<strong>')
                .text(resumen)
                .appendTo(panel);

            if (fallidos.length) {
                const lista = $(
                    '<ul class="mb-0 mt-2">'
                ).appendTo(panel);

                fallidos.forEach(function (fila) {
                    $('<li>')
                        .text(
                            'Jornada #' +
                            fila.jornada_id +
                            ': ' +
                            fila.message
                        )
                        .appendTo(lista);
                });
            }

            // Si la jornada que se estaba editando fue enviada,
            // se limpia el formulario.
            if (
                enviados.map(Number).includes(
                    Number(
                        $('#jornada_id').val()
                    )
                )
            ) {
                limpiarFormularioJornada();
            }

            Swal.fire({
                icon:
                    fallidos.length
                        ? 'warning'
                        : 'success',
                title: 'Resultado del envío',
                text: resumen
            });

            cargarMisJornadas();

        }).fail(function (xhr) {
            Swal.fire({
                icon: 'error',
                title: 'No fue posible enviar',
                text: jornadaMensajeError(
                    xhr,
                    'Intente nuevamente.'
                )
            });

        }).always(function () {
            operacionEnCurso = false;
            actualizarControlesJornada();
        });
    });
}


/* =========================================================
 * ANULACIÓN DE BORRADORES
 * ========================================================= */

/**
 * Anula un borrador conservándolo en el historial.
 */
function anularJornada(jornadaId) {
    if (
        operacionEnCurso ||
        !usuarioAutorizado
    ) {
        return;
    }

    operacionEnCurso = true;

    ++solicitudEdicion;

    actualizarControlesJornada();

    Swal.fire({
        icon: 'warning',
        title: 'Anular borrador',
        text:
            'Se conservará en el historial y podrá registrar nuevamente esa fecha.',
        input: 'textarea',
        inputLabel: 'Motivo de anulación',
        inputAttributes: {
            maxlength: 2000,
            'aria-label': 'Motivo de anulación'
        },

        inputValidator: function (valor) {
            if (
                !valor ||
                !valor.trim()
            ) {
                return 'Indique el motivo de anulación.';
            }
        },

        showCancelButton: true,
        confirmButtonText: 'Anular borrador',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc3545'

    }).then(function (resultado) {
        if (!resultado.isConfirmed) {
            operacionEnCurso = false;
            actualizarControlesJornada();
            return;
        }

        $.ajax({
            url: '../../controller/jornada.php?op=anularBorrador',
            type: 'POST',
            dataType: 'json',

            data: {
                csrf_token: $('#csrf_token').val(),
                jornada_id: jornadaId,
                motivo: resultado.value.trim()
            }

        }).done(function (respuesta) {
            // Si se estaba editando el registro anulado,
            // se restablece el formulario.
            if (
                Number(
                    $('#jornada_id').val()
                ) === jornadaId
            ) {
                limpiarFormularioJornada();
            }

            cargarMisJornadas();

            Swal.fire(
                'Borrador anulado',
                respuesta.message,
                'success'
            );

        }).fail(function (xhr) {
            Swal.fire(
                'No fue posible anular',
                jornadaMensajeError(
                    xhr,
                    'Intente nuevamente.'
                ),
                'error'
            );

        }).always(function () {
            operacionEnCurso = false;
            actualizarControlesJornada();
        });
    });
}


/* =========================================================
 * EVENTOS DEL HISTORIAL
 * ========================================================= */

/**
 * Edita un borrador.
 */
$(document).on(
    'click',
    '.btn-editar',
    function () {
        editarJornada(
            Number(
                $(this).data('id')
            )
        );
    }
);


/**
 * Envía una jornada individual a aprobación.
 */
$(document).on(
    'click',
    '.btn-enviar',
    function () {
        enviarAprobacion(
            Number(
                $(this).data('id')
            )
        );
    }
);


/**
 * Anula una jornada.
 */
$(document).on(
    'click',
    '.btn-anular',
    function () {
        anularJornada(
            Number(
                $(this).data('id')
            )
        );
    }
);


/* =========================================================
 * SELECCIÓN DE JORNADAS
 * ========================================================= */

/**
 * Selecciona o deselecciona una jornada.
 */
$(document).on(
    'change',
    '.seleccionar-jornada',
    function () {
        const id = Number(
            $(this).data('id')
        );

        if (this.checked) {
            jornadasSeleccionadas.add(
                id
            );
        } else {
            jornadasSeleccionadas.delete(
                id
            );
        }

        actualizarSeleccionJornadas();
    }
);


/**
 * Evita que el click del checkbox afecte eventos de la fila.
 */
$('#tabla-jornadas').on(
    'click',
    'input[type="checkbox"]',
    function (evento) {
        evento.stopPropagation();
    }
);


/**
 * Selecciona todos los borradores del resultado filtrado.
 */
$('#seleccionar-jornadas').on(
    'change',
    function () {
        const seleccionar =
            this.checked;

        jornadasSeleccionadas.clear();

        if (seleccionar) {
            borradoresFiltrados()
                .forEach(function (id) {
                    jornadasSeleccionadas.add(
                        id
                    );
                });
        }

        actualizarSeleccionJornadas();
    }
);


/**
 * Limpia la selección cuando cambia la búsqueda del DataTable.
 */
$('#tabla-jornadas')
    .on(
        'search.dt',
        function () {
            const busqueda = $(
                '#tabla-jornadas'
            )
                .DataTable()
                .search();

            if (
                busqueda !==
                ultimaBusquedaJornadas
            ) {
                jornadasSeleccionadas.clear();

                ultimaBusquedaJornadas =
                    busqueda;
            }
        }
    )
    .on(
        'responsive-display.dt',
        actualizarSeleccionJornadas
    );


/**
 * Envía las jornadas seleccionadas.
 */
$('#btn-enviar-seleccionadas').on(
    'click',
    function () {
        enviarJornadas(
            Array.from(
                jornadasSeleccionadas
            )
        );
    }
);


/* =========================================================
 * FILTROS DEL HISTORIAL
 * ========================================================= */

/**
 * Consulta nuevamente el historial.
 */
$('#btn-filtrar').on(
    'click',
    cargarMisJornadas
);


/**
 * Filtra inmediatamente cuando cambia el estado.
 */
$('#filtro_estado').on(
    'change',
    cargarMisJornadas
);


/**
 * Restablece el periodo al mes anterior hasta hoy
 * y muestra todos los estados.
 */
$('#btn-limpiar-filtro').on(
    'click',
    function () {
        const inicio = moment()
            .subtract(1, 'month')
            .startOf('month');

        const fin = moment();

        const selector = $(
            '#filtro_fechas'
        ).data(
            'daterangepicker'
        );

        selector.setStartDate(
            inicio
        );

        selector.setEndDate(
            fin
        );

        $('#filtro_fechas').val(
            inicio.format('YYYY-MM-DD') +
            ' - ' +
            fin.format('YYYY-MM-DD')
        );

        $('#filtro_estado').val('');

        cargarMisJornadas();
    }
);

/* =========================================================
 * REPORTE PARA FIRMA
 * ========================================================= */

/**
 * Obtiene el rango actualmente seleccionado para el reporte.
 */
function obtenerRangoReporteFirma() {
    const rango = $('#reporte_fechas').val().split(' - ');

    if (rango.length !== 2) {
        return null;
    }

    return {
        fecha_desde: rango[0],
        fecha_hasta: rango[1]
    };
}


/**
 * Convierte una cantidad de minutos al formato HH:mm.
 */
function formatearMinutosJornada(minutos) {
    minutos = Number(minutos || 0);

    const horas = Math.floor(minutos / 60);
    const minutosRestantes = minutos % 60;

    return String(horas).padStart(2, '0') +
        ':' +
        String(minutosRestantes).padStart(2, '0');
}


/**
 * Restablece el resultado visual del reporte antes de realizar
 * una nueva consulta.
 */
function limpiarReporteFirma() {
    jornadasReporteFirma = [];

    $('#resumen-reporte-firma').addClass('d-none');
    $('#mensaje-reporte-vacio').addClass('d-none');
    $('#contenedor-reporte-firma').addClass('d-none');
    $('#btn-firmar-reporte').prop('disabled', true);

    $('#reporte-fecha-desde').text('-');
    $('#reporte-fecha-hasta').text('-');
    $('#reporte-cantidad').text('0');

    if (tablaReporteFirma) {
        tablaReporteFirma.clear().draw();
    }
}


/**
 * Consulta en el servidor las jornadas aprobadas que pueden formar
 * parte del reporte que firmará el empleado.
 */
function consultarReporteFirma() {
    if (!usuarioAutorizado || reporteFirmaEnProceso) {
        return;
    }

    const rango = obtenerRangoReporteFirma();

    if (!rango) {
        Swal.fire({
            icon: 'warning',
            title: 'Periodo requerido',
            text: 'Seleccione el periodo que desea consultar.'
        });

        return;
    }

    limpiarReporteFirma();

    reporteFirmaEnProceso = true;

    $('#btn-consultar-reporte').prop(
        'disabled',
        true
    );

    $.ajax({
        url: '../../controller/jornada.php?op=consultarReporteFirma',
        type: 'GET',
        dataType: 'json',

        data: {
            fecha_desde: rango.fecha_desde,
            fecha_hasta: rango.fecha_hasta
        }

    }).done(function (respuesta) {
        const datos = respuesta.data || [];
        const cantidad = datos.length;

        $('#reporte-fecha-desde').text(
            moment(
                rango.fecha_desde
            ).format(
                'DD/MM/YYYY'
            )
        );

        $('#reporte-fecha-hasta').text(
            moment(
                rango.fecha_hasta
            ).format(
                'DD/MM/YYYY'
            )
        );

        $('#reporte-cantidad').text(
            cantidad
        );

        $('#resumen-reporte-firma')
            .removeClass('d-none');

        if (cantidad === 0) {
            $('#mensaje-reporte-vacio')
                .removeClass('d-none');

            return;
        }

        cargarTablaReporteFirma(
            datos
        );

        $('#contenedor-reporte-firma')
            .removeClass('d-none');

        $('#btn-firmar-reporte')
            .prop(
                'disabled',
                false
            );

        // Ajusta las columnas porque la tabla está dentro de un tab.
        setTimeout(function () {
            if (tablaReporteFirma) {
                tablaReporteFirma
                    .columns
                    .adjust()
                    .responsive
                    .recalc();
            }
        }, 100);

    }).fail(function (xhr) {
        Swal.fire({
            icon: 'error',
            title: 'No fue posible consultar',
            text: jornadaMensajeError(
                xhr,
                'No se pudo construir el reporte de jornadas.'
            )
        });

    }).always(function () {
        reporteFirmaEnProceso = false;

        $('#btn-consultar-reporte').prop(
            'disabled',
            false
        );
    });
}


/* =========================================================
 * CANVAS DE FIRMA
 * ========================================================= */

/**
 * Inicializa los eventos del canvas utilizado para capturar
 * la firma del reporte.
 */
function inicializarCanvasFirmaReporte() {
    const canvas = document.getElementById(
        'canvasFirmaReporte'
    );

    if (!canvas) {
        return;
    }

    const ctx = canvas.getContext('2d');

    let dibujando = false;

    ctx.lineWidth = 2;
    ctx.strokeStyle = '#000';
    ctx.lineJoin = 'round';
    ctx.lineCap = 'round';

    /**
     * Calcula la posición real del mouse o dedo sobre el canvas,
     * teniendo en cuenta su tamaño visual.
     */
    function obtenerPosicion(evento) {
        const rect = canvas.getBoundingClientRect();

        const clienteX = evento.touches
            ? evento.touches[0].clientX
            : evento.clientX;

        const clienteY = evento.touches
            ? evento.touches[0].clientY
            : evento.clientY;

        return {
            x:
                (clienteX - rect.left) *
                (canvas.width / rect.width),

            y:
                (clienteY - rect.top) *
                (canvas.height / rect.height)
        };
    }

    /**
     * Inicia el trazo de la firma.
     */
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

    /**
     * Dibuja el trazo mientras el usuario mantiene
     * presionado el mouse o el dedo.
     */
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

        firmaReporteDibujada = true;

        $('#btn-confirmar-firma-reporte').prop(
            'disabled',
            false
        );

        $('#mensaje-firma-reporte')
            .removeClass('text-danger')
            .addClass('text-muted')
            .text('Firma capturada.');
    }

    /**
     * Finaliza el trazo actual.
     */
    function detenerDibujo(evento) {
        if (evento) {
            evento.preventDefault();
        }

        dibujando = false;
    }

    // Eventos para mouse.
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

    // Eventos para pantallas táctiles.
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
 * Limpia completamente el canvas y obliga al empleado
 * a realizar nuevamente la firma.
 */
function limpiarFirmaReporte() {
    const canvas = document.getElementById(
        'canvasFirmaReporte'
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

    firmaReporteDibujada = false;

    $('#btn-confirmar-firma-reporte').prop(
        'disabled',
        true
    );

    $('#mensaje-firma-reporte')
        .removeClass('text-danger')
        .addClass('text-muted')
        .text('Firme dentro del recuadro.');
}


/**
 * Abre la captura de firma utilizando exactamente el rango
 * que el empleado acaba de revisar.
 */
function abrirModalFirmaReporte() {
    if (jornadasReporteFirma.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Reporte vacío',
            text: 'Consulte primero las jornadas que desea firmar.'
        });

        return;
    }

    const rango = obtenerRangoReporteFirma();

    if (!rango) {
        return;
    }

    limpiarFirmaReporte();

    $('#firma-reporte-desde').text(
        moment(
            rango.fecha_desde
        ).format(
            'DD/MM/YYYY'
        )
    );

    $('#firma-reporte-hasta').text(
        moment(
            rango.fecha_hasta
        ).format(
            'DD/MM/YYYY'
        )
    );

    $('#modalFirmaReporte').modal(
        'show'
    );
}


/* =========================================================
 * CONFIRMACIÓN DE FIRMA
 * ========================================================= */

/**
 * Envía al servidor la firma y el rango revisado.
 *
 * Los IDs de las jornadas no se envían desde el navegador.
 * El modelo vuelve a determinar cuáles jornadas aprobadas
 * pertenecen realmente al reporte.
 */
function confirmarFirmaReporte() {
    if (
        reporteFirmaEnProceso ||
        !firmaReporteDibujada ||
        jornadasReporteFirma.length === 0
    ) {
        return;
    }

    const rango = obtenerRangoReporteFirma();

    const canvas = document.getElementById(
        'canvasFirmaReporte'
    );

    if (!rango || !canvas) {
        return;
    }

    const firma = canvas.toDataURL(
        'image/png'
    );

    Swal.fire({
        icon: 'question',
        title: 'Confirmar firma',

        html:
            'Va a firmar <strong>' +
            jornadasReporteFirma.length +
            '</strong> jornada(s) del periodo ' +
            '<strong>' +
            moment(
                rango.fecha_desde
            ).format(
                'DD/MM/YYYY'
            ) +
            '</strong> al <strong>' +
            moment(
                rango.fecha_hasta
            ).format(
                'DD/MM/YYYY'
            ) +
            '</strong>.<br><br>' +
            'Después de firmar quedarán pendientes de liquidación.',

        showCancelButton: true,
        confirmButtonText: 'Sí, firmar reporte',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#28a745'

    }).then(function (resultado) {
        if (!resultado.isConfirmed) {
            return;
        }

        reporteFirmaEnProceso = true;

        $('#btn-confirmar-firma-reporte').prop(
            'disabled',
            true
        );

        $('#btn-limpiar-firma-reporte').prop(
            'disabled',
            true
        );

        $.ajax({
            url: '../../controller/jornada.php?op=firmarReporteJornadas',
            type: 'POST',
            dataType: 'json',

            data: {
                csrf_token: $('#csrf_token').val(),
                fecha_desde: rango.fecha_desde,
                fecha_hasta: rango.fecha_hasta,
                firma: firma
            }

        }).done(function (respuesta) {
            $('#modalFirmaReporte').modal(
                'hide'
            );

            limpiarFirmaReporte();
            limpiarReporteFirma();

            // Actualiza el historial para mostrar PENDIENTE_LIQUIDACION.
            cargarMisJornadas();

            Swal.fire({
                icon: 'success',
                title: 'Reporte firmado',
                text:
                    respuesta.message ||
                    'El reporte fue firmado correctamente.'
            });

        }).fail(function (xhr) {
            Swal.fire({
                icon: 'error',
                title: 'No fue posible firmar',
                text: jornadaMensajeError(
                    xhr,
                    'No se pudo completar la firma del reporte.'
                )
            });

            $('#btn-confirmar-firma-reporte').prop(
                'disabled',
                !firmaReporteDibujada
            );

        }).always(function () {
            reporteFirmaEnProceso = false;

            $('#btn-limpiar-firma-reporte').prop(
                'disabled',
                false
            );
        });
    });
}


/* =========================================================
 * EVENTOS DEL FORMULARIO DE JORNADA
 * ========================================================= */

/**
 * Actualiza el día y recalcula las horas al cambiar la fecha.
 * La fecha puede repetirse en diferentes jornadas.
 */
$('#fecha').on(
    'change',
    function () {
        $('#fecha').removeClass(
            'is-invalid'
        );

        $('#ayuda-fecha')
            .removeClass('text-danger')
            .addClass('text-muted')
            .text('');

        actualizarDiaSemana();
        calcularHorasJornada();
    }
);


/**
 * Recalcula las horas cuando cambia la entrada o salida.
 */
$('#hora_entrada, #hora_salida').on(
    'change',
    calcularHorasJornada
);


/**
 * Recalcula cuando se indica que la salida corresponde
 * al día siguiente.
 */
$('#salida_dia_siguiente').on(
    'change',
    calcularHorasJornada
);


/**
 * Limpia el formulario.
 */
$('#btn-limpiar').on(
    'click',
    limpiarFormularioJornada
);


/**
 * Guarda o actualiza una jornada.
 */
$('#form-jornada').on(
    'submit',
    function (evento) {
        evento.preventDefault();

        if (
            operacionEnCurso ||
            !usuarioAutorizado
        ) {
            return;
        }

        const entrada = $(
            '#hora_entrada'
        ).val();

        const salida = $(
            '#hora_salida'
        ).val();

        const salidaDiaSiguiente = $(
            '#salida_dia_siguiente'
        ).is(
            ':checked'
        );

        if (
            !entrada ||
            !salida
        ) {
            Swal.fire(
                'Información requerida',
                'Ingrese entrada y salida.',
                'warning'
            );

            return;
        }

        // Si la salida es menor o igual a la entrada,
        // pregunta si corresponde al día siguiente.
        if (
            salida <= entrada &&
            !salidaDiaSiguiente
        ) {
            Swal.fire({
                icon: 'question',
                title: 'La jornada cruza medianoche',
                text:
                    '¿La hora de salida corresponde al día siguiente?',
                showCancelButton: true,
                confirmButtonText:
                    'Sí, continúa al día siguiente',
                cancelButtonText:
                    'Revisar horas'

            }).then(function (resultado) {
                if (
                    resultado.isConfirmed
                ) {
                    $('#salida_dia_siguiente')
                        .prop(
                            'checked',
                            true
                        );

                    calcularHorasJornada();

                    guardarBorrador(
                        true
                    );
                }
            });

            return;
        }

        guardarBorrador(
            salidaDiaSiguiente
        );
    }
);


/* =========================================================
 * EVENTOS DEL REPORTE PARA FIRMA
 * ========================================================= */

/**
 * Consulta las jornadas disponibles para firma.
 */
$('#btn-consultar-reporte').on(
    'click',
    consultarReporteFirma
);


/**
 * Abre el modal para registrar la firma.
 */
$('#btn-firmar-reporte').on(
    'click',
    abrirModalFirmaReporte
);


/**
 * Limpia el canvas de firma.
 */
$('#btn-limpiar-firma-reporte').on(
    'click',
    function () {
        limpiarFirmaReporte();
    }
);


/**
 * Confirma la firma del reporte.
 */
$('#btn-confirmar-firma-reporte').on(
    'click',
    function () {
        confirmarFirmaReporte();
    }
);


/**
 * Limpia el resultado anterior cuando cambia
 * el periodo seleccionado.
 */
$('#reporte_fechas').on(
    'apply.daterangepicker',
    function () {
        limpiarReporteFirma();
    }
);


/**
 * Elimina la firma dibujada cuando se cierra el modal.
 */
$('#modalFirmaReporte').on(
    'hidden.bs.modal',
    function () {
        if (!reporteFirmaEnProceso) {
            limpiarFirmaReporte();
        }
    }
);


/* =========================================================
 * EVENTOS DE LOS TABS
 * ========================================================= */

/**
 * Recalcula las tablas cuando Bootstrap muestra una pestaña.
 */
$('a[data-toggle="pill"]').on(
    'shown.bs.tab',
    function () {
        if (tablaJornadas) {
            tablaJornadas
                .columns
                .adjust()
                .responsive
                .recalc();
        }

        if (tablaReporteFirma) {
            tablaReporteFirma
                .columns
                .adjust()
                .responsive
                .recalc();
        }
    }
);


/* =========================================================
 * INICIALIZACIÓN DE LA VISTA
 * ========================================================= */

/**
 * Inicializa todos los componentes de Mis Jornadas.
 */
$(document).ready(function () {
    inicializarRangoFechas();
    inicializarRangoReporteFirma();
    inicializarCanvasFirmaReporte();

    limpiarFormularioJornada();
    limpiarReporteFirma();

    cargarContextoUsuario();
    cargarMisJornadas();
});