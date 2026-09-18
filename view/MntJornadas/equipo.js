let tablaExpediente = null;
let solicitudCalculoEquipo = 0;

let ubicacionesJornada = [];

// Conserva la información del expediente actualmente consultado.
let expedienteEquipo = {
    empleadoId: null,
    empleadoNombre: '',
    documento: '',
    fechaDesde: '',
    fechaHasta: ''
};

// Escapa valores antes de mostrarlos dentro de HTML.
function equipoEscapeHtml(valor) {
    return $('<div>').text(valor == null ? '' : String(valor)).html();
}

// Obtiene un mensaje legible de las respuestas AJAX con error.
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

// Inicializa el selector de empleados subordinados.
function inicializarSelectEmpleadoEquipo() {
    $('#empleado_id').select2({
        theme: 'bootstrap4',
        placeholder: 'Seleccione un subordinado',
        allowClear: true,
        width: '100%'
    });
}

// Inicializa el rango de fechas utilizado para consultar el expediente.
function inicializarRangoEquipo() {
    const fin = moment();
    const inicio = fin.clone().subtract(1, 'month').startOf('month');

    $('#filtro_fechas').daterangepicker({
        startDate: inicio,
        endDate: fin,
        minDate: inicio,
        maxDate: fin,
        showDropdowns: true,
        autoApply: false,
        locale: {
            format: 'YYYY-MM-DD',
            separator: ' - ',
            applyLabel: 'Aplicar',
            cancelLabel: 'Cancelar',
            fromLabel: 'Desde',
            toLabel: 'Hasta',
            customRangeLabel: 'Personalizado',
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
            ],
            firstDay: 1
        }
    });
}

// Consulta el contexto del jefe autenticado.
function cargarContextoEquipo() {
    $.ajax({
        url: '../../controller/jornada.php?op=contextoEquipo',
        type: 'GET',
        dataType: 'json'
    }).done(function (respuesta) {
        const datos = respuesta.data || {};

        $('#texto-contexto').text(
            'Jefe inmediato: ' +
            (datos.empleado || '') +
            (datos.documento ? ' — ' + datos.documento : '')
        );
    }).fail(function (xhr) {
        $('#alerta-contexto')
            .removeClass('alert-info')
            .addClass('alert-danger');

        $('#texto-contexto').text(
            equipoMensajeError(
                xhr,
                'No fue posible validar al jefe inmediato.'
            )
        );

        $('#empleado_id').prop('disabled', true);
        $('#filtro_fechas').prop('disabled', true);
        $('#btn-consultar-expediente').prop('disabled', true);
    });
}

// Carga únicamente los empleados relacionados activamente con el jefe.
// Carga los empleados relacionados activamente con el jefe.
function cargarSubordinados() {
    return $.ajax({
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
                }).attr({
                    'data-nombre': empleado.empleado_nombre,
                    'data-documento': empleado.empleado_documento
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

// Obtiene el rango actualmente seleccionado.
function obtenerPeriodoEquipo() {
    const rango = $('#filtro_fechas').val().split(' - ');

    return {
        desde: rango.length === 2 ? rango[0] : '',
        hasta: rango.length === 2 ? rango[1] : ''
    };
}

// Devuelve el nombre del día correspondiente a una fecha ISO.
function obtenerDiaSemanaEquipo(fecha) {
    const dias = [
        'Domingo',
        'Lunes',
        'Martes',
        'Miércoles',
        'Jueves',
        'Viernes',
        'Sábado'
    ];

    return dias[new Date(fecha + 'T00:00:00').getDay()];
}

// Genera todas las fechas existentes dentro del periodo consultado.
function generarFechasPeriodoEquipo(fechaDesde, fechaHasta) {
    const fechas = [];

    let actual = moment(fechaDesde, 'YYYY-MM-DD');
    const fin = moment(fechaHasta, 'YYYY-MM-DD');

    while (actual.isSameOrBefore(fin, 'day')) {
        fechas.push(actual.format('YYYY-MM-DD'));
        actual.add(1, 'day');
    }

    return fechas;
}

// Carga las ubicaciones habilitadas para el registro de jornadas.
// Carga las ubicaciones habilitadas para las jornadas.
function cargarUbicacionesJornada() {
    return $.ajax({
        url: '../../controller/jornada.php?op=listarUbicacionesJornada',
        type: 'GET',
        dataType: 'json'
    }).done(function (respuesta) {
        ubicacionesJornada = respuesta.data || [];
    }).fail(function (xhr) {
        ubicacionesJornada = [];

        Swal.fire({
            icon: 'error',
            title: 'No fue posible consultar',
            text: equipoMensajeError(
                xhr,
                'No se pudieron cargar las ubicaciones.'
            )
        });
    });
}

// Construye una fila nueva que todavía no existe en la base de datos.
function crearFilaNuevaEquipo(fecha) {
    return {
        jornada_id: null,
        empleado_id: parseInt(expedienteEquipo.empleadoId, 10),
        empleado: expedienteEquipo.empleadoNombre,
        documento: expedienteEquipo.documento,
        dia: obtenerDiaSemanaEquipo(fecha),
        fecha: fecha,
        hora_entrada: '',
        fecha_salida: fecha,
        hora_salida: '',
        horas_ordinarias: '00:00',
        ubicacion: '',
        actividad: '',
        observaciones: '',
        estado_codigo: 'NUEVA',
        estado_nombre: 'Nueva',
        cruza_medianoche: false
    };
}

// Mezcla las fechas del periodo con las jornadas existentes del empleado.
function construirExpedienteEquipo(jornadasExistentes) {
    const fechas = generarFechasPeriodoEquipo(
        expedienteEquipo.fechaDesde,
        expedienteEquipo.fechaHasta
    );

    const mapa = {};

    jornadasExistentes.forEach(function (fila) {
        if (
            parseInt(fila.empleado_id, 10) ===
            parseInt(expedienteEquipo.empleadoId, 10)
        ) {
            if (!mapa[fila.fecha]) {
                mapa[fila.fecha] = [];
            }
            fila.cruza_medianoche = !!(fila.fecha_salida && fila.fecha_salida !== fila.fecha);
            mapa[fila.fecha].push(fila);
        }
    });

    return fechas.reduce(function (filas, fecha) {
        return filas.concat(mapa[fecha] || [crearFilaNuevaEquipo(fecha)]);
    }, []);
}

// Renderiza el estado de cada jornada.
function renderEstadoExpediente(codigo, nombre) {
    const clases = {
        NUEVA: 'badge-light border',
        BORRADOR: 'badge-secondary',
        PENDIENTE_APROBACION: 'badge-warning',
        APROBADO: 'badge-success',
        RECHAZADO: 'badge-danger',
        ANULADO: 'badge-dark',
        PENDIENTE_CORRECCION: 'badge-info',
        CORREGIDO: 'badge-primary'
    };

    return (
        '<span class="badge ' +
        (clases[codigo] || 'badge-dark') +
        '">' +
        equipoEscapeHtml(nombre || codigo) +
        '</span>'
    );
}

// Determina si una jornada puede ser modificada desde el expediente del jefe.
function filaEditableEquipo(fila) {
    return (
        fila.estado_codigo === 'NUEVA' ||
        fila.estado_codigo === 'BORRADOR'
    );
}

// Genera el input editable de la hora de entrada.
function renderEntradaEquipo(fila) {
    if (!filaEditableEquipo(fila)) {
        return equipoEscapeHtml(fila.hora_entrada || '-');
    }

    return (
        '<input type="time" ' +
        'class="form-control form-control-sm jornada-entrada" ' +
        'value="' + equipoEscapeHtml(fila.hora_entrada || '') + '">'
    );
}

// Genera el input editable de la hora de salida.
function renderSalidaEquipo(fila) {
    if (!filaEditableEquipo(fila)) {
        let texto = fila.hora_salida || '-';

        if (
            fila.fecha_salida &&
            fila.fecha_salida !== fila.fecha
        ) {
            texto += ' (+1 día)';
        }

        return equipoEscapeHtml(texto);
    }

    return (
        '<input type="time" ' +
        'class="form-control form-control-sm jornada-salida" ' +
        'value="' + equipoEscapeHtml(fila.hora_salida || '') + '">'
    );
}

// Genera el selector de ubicación para filas editables.
// Genera el selector de ubicación utilizando la lista entregada por el servidor.
function renderUbicacionEquipo(fila) {
    if (!filaEditableEquipo(fila)) {
        return equipoEscapeHtml(fila.ubicacion || '-');
    }

    const ubicacion = fila.ubicacion || '';

    let html =
        '<select class="form-control form-control-sm jornada-ubicacion">' +
        '<option value=""' +
        (ubicacion === '' ? ' selected' : '') +
        '>Seleccione</option>';

    ubicacionesJornada.forEach(function (opcion) {
        html +=
            '<option value="' +
            equipoEscapeHtml(opcion) +
            '"' +
            (ubicacion === opcion ? ' selected' : '') +
            '>' +
            equipoEscapeHtml(opcion) +
            '</option>';
    });

    /*
     * Conserva visualmente una ubicación histórica que ya exista
     * aunque posteriormente haya sido retirada de la lista activa.
     */
    if (
        ubicacion !== '' &&
        !ubicacionesJornada.includes(ubicacion)
    ) {
        html +=
            '<option value="' +
            equipoEscapeHtml(ubicacion) +
            '" selected>' +
            equipoEscapeHtml(ubicacion) +
            '</option>';
    }

    html += '</select>';

    return html;
}

// Genera el campo actividad.
function renderActividadEquipo(fila) {
    if (!filaEditableEquipo(fila)) {
        return equipoEscapeHtml(fila.actividad || '-');
    }

    return (
        '<input type="text" ' +
        'class="form-control form-control-sm jornada-actividad" ' +
        'maxlength="4000" ' +
        'value="' + equipoEscapeHtml(fila.actividad || '') + '">'
    );
}

// Genera el campo observaciones.
function renderObservacionesEquipo(fila) {
    if (!filaEditableEquipo(fila)) {
        return equipoEscapeHtml(fila.observaciones || '-');
    }

    return (
        '<input type="text" ' +
        'class="form-control form-control-sm jornada-observaciones" ' +
        'maxlength="4000" ' +
        'value="' + equipoEscapeHtml(fila.observaciones || '') + '">'
    );
}

// Inicializa la DataTable que funciona como planilla del expediente.
function cargarTablaExpediente(filas) {
    if ($.fn.DataTable.isDataTable('#tabla-expediente')) {
        $('#tabla-expediente').DataTable().destroy();
    }

    $('#tabla-expediente tbody').empty();

    tablaExpediente = $('#tabla-expediente').DataTable({
        data: filas,
        processing: false,
        responsive: false,
        autoWidth: false,
        paging: false,
        searching: false,
        info: false,
        ordering: false,
        columns: [
            {
                data: 'dia',
                render: function (data) {
                    return equipoEscapeHtml(data);
                }
            },
            {
                data: 'fecha',
                render: function (data, type, fila) {
                    return moment(data, 'YYYY-MM-DD').format('DD/MM/YYYY') +
                        '<br><button type="button" class="btn btn-outline-primary btn-sm jornada-agregar-turno mt-1">' +
                        '<i class="fas fa-plus mr-1"></i>Agregar turno</button>' +
                        ((fila.turno_adicional && fila.estado_codigo === 'NUEVA') || fila.estado_codigo === 'BORRADOR'
                            ? '<br><button type="button" class="btn btn-outline-danger btn-sm jornada-eliminar-turno mt-1">' +
                              '<i class="fas fa-trash-alt mr-1"></i>Eliminar turno</button>'
                            : '');
                }
            },
            {
                data: null,
                render: function (data, type, fila) {
                    return renderEntradaEquipo(fila);
                }
            },
            {
                data: null,
                render: function (data, type, fila) {
                    return renderSalidaEquipo(fila);
                }
            },
            {
                data: 'horas_ordinarias',
                className: 'text-center jornada-horas',
                render: function (data) {
                    return equipoEscapeHtml(data || '00:00');
                }
            },
            {
                data: null,
                render: function (data, type, fila) {
                    return renderUbicacionEquipo(fila);
                }
            },
            {
                data: null,
                render: function (data, type, fila) {
                    return renderActividadEquipo(fila);
                }
            },
            {
                data: null,
                render: function (data, type, fila) {
                    return renderObservacionesEquipo(fila);
                }
            },
            {
                data: null,
                className: 'text-center',
                render: function (data, type, fila) {
                    return renderEstadoExpediente(
                        fila.estado_codigo,
                        fila.estado_nombre
                    );
                }
            }
        ],
        drawCallback: function () {
            // Mantiene los turnos junto a su fecha al agregar o eliminar filas.
            const nodos = this.api().rows().nodes().toArray();
            nodos.sort(function (a, b) {
                return $(a).attr('data-fecha').localeCompare($(b).attr('data-fecha'));
            });
            $('#tabla-expediente tbody').append(nodos);
        },
        createdRow: function (row, data) {
            $(row)
                .attr('data-fecha', data.fecha)
                .attr('data-jornada-id', data.jornada_id || '')
                .attr(
                    'data-cruza-medianoche',
                    data.cruza_medianoche ? '1' : '0'
                )
                .attr('data-modificada', '0');

            if (!filaEditableEquipo(data)) {
                $(row).addClass('bg-light');
            }
        },
        language: {
            emptyTable: 'No existen fechas para el periodo seleccionado.'
        }
    });

    $('#btn-guardar-borrador').prop('disabled', false);
    $('#btn-registrar-aprobar').prop('disabled', false);
}

// Consulta las jornadas y construye el expediente seleccionado.
function consultarExpedienteEquipo() {
    const empleadoId = $('#empleado_id').val();
    const periodo = obtenerPeriodoEquipo();

    if (!empleadoId) {
        Swal.fire({
            icon: 'warning',
            title: 'Empleado requerido',
            text: 'Seleccione un subordinado.'
        });
        return;
    }

    if (!periodo.desde || !periodo.hasta) {
        Swal.fire({
            icon: 'warning',
            title: 'Periodo requerido',
            text: 'Seleccione el periodo que desea consultar.'
        });
        return;
    }

    const opcion = $('#empleado_id option:selected');

    expedienteEquipo = {
        empleadoId: empleadoId,
        empleadoNombre: opcion.attr('data-nombre') || '',
        documento: opcion.attr('data-documento') || '',
        fechaDesde: periodo.desde,
        fechaHasta: periodo.hasta
    };

    $('#btn-consultar-expediente')
        .prop('disabled', true)
        .html(
            '<span class="spinner-border spinner-border-sm mr-1"></span>' +
            'Consultando...'
        );

    $.ajax({
        url: '../../controller/jornada.php?op=listarJornadasEquipo',
        type: 'GET',
        dataType: 'json',
        data: {
            empleado_id: empleadoId,
            fecha_desde: periodo.desde,
            fecha_hasta: periodo.hasta
        }
    }).done(function (respuesta) {
        const filas = construirExpedienteEquipo(
            respuesta.data || []
        );

        $('#expediente-empleado').text(
            expedienteEquipo.empleadoNombre
        );

        $('#expediente-documento').text(
            expedienteEquipo.documento || '-'
        );

        $('#expediente-periodo').text(
            moment(expedienteEquipo.fechaDesde, 'YYYY-MM-DD')
                .format('DD/MM/YYYY') +
            ' al ' +
            moment(expedienteEquipo.fechaHasta, 'YYYY-MM-DD')
                .format('DD/MM/YYYY')
        );

        cargarTablaExpediente(filas);

        $('#contenedor-expediente').slideDown(200);
    }).fail(function (xhr) {
        $('#contenedor-expediente').hide();

        Swal.fire({
            icon: 'error',
            title: 'No fue posible consultar',
            text: equipoMensajeError(
                xhr,
                'No fue posible cargar el expediente del empleado.'
            )
        });
    }).always(function () {
        $('#btn-consultar-expediente')
            .prop('disabled', false)
            .html(
                '<i class="fas fa-search mr-1"></i>' +
                'Consultar'
            );
    });
}

// Obtiene la información editable de una fila de la planilla.
// Obtiene los valores actuales de una fila editable del expediente.
function obtenerDatosFilaEquipo(filaDom) {
    const fila = tablaExpediente.row(filaDom).data();

    return {
        jornada_id:
            fila.jornada_id === null ||
                fila.jornada_id === undefined ||
                fila.jornada_id === ''
                ? null
                : parseInt(fila.jornada_id, 10),

        fecha: fila.fecha,

        hora_entrada:
            $(filaDom).find('.jornada-entrada').val() || '',

        hora_salida:
            $(filaDom).find('.jornada-salida').val() || '',

        ubicacion:
            $(filaDom).find('.jornada-ubicacion').val() || '',

        actividad:
            $.trim(
                $(filaDom).find('.jornada-actividad').val() || ''
            ),

        observaciones:
            $.trim(
                $(filaDom).find('.jornada-observaciones').val() || ''
            ),

        cruza_medianoche:
            $(filaDom).attr('data-cruza-medianoche') === '1'
    };
}

// Determina si una fila se encuentra completamente vacía.
function filaVaciaEquipo(datos) {
    return (
        datos.hora_entrada === '' &&
        datos.hora_salida === '' &&
        datos.ubicacion === '' &&
        datos.actividad === '' &&
        datos.observaciones === ''
    );
}

// Determina si una fila tiene los datos mínimos requeridos.
function filaCompletaEquipo(datos) {
    return (
        datos.hora_entrada !== '' &&
        datos.hora_salida !== '' &&
        datos.ubicacion !== '' &&
        datos.actividad !== ''
    );
}

// Actualiza en el DataTable el cálculo devuelto por el servidor.
function actualizarHorasFilaEquipo(filaDom, respuesta) {
    const datos = respuesta.data || {};
    const fila = tablaExpediente.row(filaDom).data();

    fila.horas_ordinarias = datos.horas_ordinarias || '00:00';
    fila.cruza_medianoche = !!datos.cruza_medianoche;

    $(filaDom).attr(
        'data-cruza-medianoche',
        fila.cruza_medianoche ? '1' : '0'
    );

    $(filaDom)
        .find('td')
        .eq(4)
        .text(fila.horas_ordinarias);
}

// Solicita al servidor el cálculo de horas de una fila.
function calcularHorasFilaEquipo(filaDom) {
    const datos = obtenerDatosFilaEquipo(filaDom);

    if (!datos.hora_entrada || !datos.hora_salida) {
        $(filaDom).find('td').eq(4).text('00:00');
        return;
    }

    const numeroSolicitud = ++solicitudCalculoEquipo;
    $(filaDom).data('solicitud-calculo', numeroSolicitud);

    $.ajax({
        url: '../../controller/jornada.php?op=calcularHorasEquipo',
        type: 'GET',
        dataType: 'json',
        data: {
            fecha: datos.fecha,
            hora_entrada: datos.hora_entrada,
            hora_salida: datos.hora_salida,
            cruza_medianoche:
                datos.cruza_medianoche ? 1 : 0
        }
    }).done(function (respuesta) {
        if (numeroSolicitud !== $(filaDom).data('solicitud-calculo')) {
            return;
        }

        actualizarHorasFilaEquipo(filaDom, respuesta);
    }).fail(function (xhr) {
        if (numeroSolicitud !== $(filaDom).data('solicitud-calculo')) {
            return;
        }

        $(filaDom).find('td').eq(4).text('00:00');

        Swal.fire({
            icon: 'warning',
            title: 'No fue posible calcular',
            text: equipoMensajeError(
                xhr,
                'Revise las horas ingresadas.'
            )
        });
    });
}

// Evalúa si la salida inferior o igual a la entrada corresponde al día siguiente.
function validarCruceMedianocheEquipo(filaDom) {
    const datos = obtenerDatosFilaEquipo(filaDom);

    if (!datos.hora_entrada || !datos.hora_salida) {
        return;
    }

    if (datos.hora_salida > datos.hora_entrada) {
        $(filaDom).attr('data-cruza-medianoche', '0');
        calcularHorasFilaEquipo(filaDom);
        return;
    }

    Swal.fire({
        icon: 'question',
        title: 'La jornada cruza medianoche',
        html:
            'La salida <strong>' +
            equipoEscapeHtml(datos.hora_salida) +
            '</strong> es anterior o igual a la entrada <strong>' +
            equipoEscapeHtml(datos.hora_entrada) +
            '</strong>.<br><br>' +
            '¿La salida corresponde al día siguiente?',
        showCancelButton: true,
        confirmButtonText: 'Sí, día siguiente',
        cancelButtonText: 'Revisar'
    }).then(function (resultado) {
        if (resultado.isConfirmed) {
            $(filaDom).attr('data-cruza-medianoche', '1');
            calcularHorasFilaEquipo(filaDom);
        } else {
            $(filaDom).attr('data-cruza-medianoche', '0');
            $(filaDom).find('.jornada-salida').val('');
            $(filaDom).find('td').eq(4).text('00:00');
        }
    });
}

// Recorre la tabla y obtiene las filas nuevas o borradores diligenciados.
// Obtiene únicamente jornadas nuevas o borradores modificados.
function obtenerJornadasEditablesEquipo() {
    const jornadas = [];
    const errores = [];

    $('#tabla-expediente tbody tr').each(function () {
        const filaDom = this;
        const fila = tablaExpediente.row(filaDom).data();

        if (!fila || !filaEditableEquipo(fila)) {
            return;
        }

        const datos = obtenerDatosFilaEquipo(filaDom);
        const modificada =
            $(filaDom).attr('data-modificada') === '1';

        // Una fila nueva completamente vacía se ignora.
        if (
            fila.estado_codigo === 'NUEVA' &&
            filaVaciaEquipo(datos)
        ) {
            return;
        }

        /*
         * Un borrador existente que no fue modificado tampoco necesita
         * enviarse nuevamente al guardar.
         */
        if (
            fila.estado_codigo === 'BORRADOR' &&
            !modificada
        ) {
            return;
        }

        // Las filas parcialmente diligenciadas deben corregirse.
        if (!filaCompletaEquipo(datos)) {
            errores.push({
                fecha: datos.fecha,
                mensaje:
                    'La jornada tiene información incompleta.'
            });

            return;
        }

        jornadas.push(datos);
    });

    return {
        jornadas: jornadas,
        errores: errores
    };
}

// Muestra inmediatamente el progreso de las operaciones masivas.
function mostrarProcesandoEquipo(botonId, titulo) {
    $('#btn-guardar-borrador, #btn-registrar-aprobar').prop('disabled', true);
    const boton = $(botonId);
    boton.data('contenido-original', boton.html());
    boton.attr('aria-busy', 'true').html(
        '<span class="spinner-border spinner-border-sm mr-1" aria-hidden="true"></span>' +
        'Procesando...'
    );
    Swal.fire({
        title: titulo,
        html: '<div class="spinner-border text-primary my-3" role="status">' +
            '<span class="sr-only">Procesando...</span></div>' +
            '<p>Espere un momento. No recargue ni cierre la página.</p>',
        showConfirmButton: false,
        allowOutsideClick: false,
        allowEscapeKey: false
    });
}

function finalizarProcesandoEquipo() {
    $('#btn-guardar-borrador, #btn-registrar-aprobar').each(function () {
        const boton = $(this);
        const contenido = boton.data('contenido-original');
        if (contenido !== undefined) {
            boton.html(contenido).removeData('contenido-original');
        }
        boton.removeAttr('aria-busy').prop('disabled', false);
    });
}

// En el Paso 3 este botón será conectado al guardado masivo del controller.
// Valida y envía las jornadas editables para guardarlas como borrador.
function guardarBorradoresEquipo() {
    const resultado = obtenerJornadasEditablesEquipo();

    // Si hay filas parcialmente diligenciadas, no permite guardar el lote.
    if (resultado.errores.length > 0) {
        const detalle = resultado.errores
            .map(function (error) {
                return (
                    moment(error.fecha, 'YYYY-MM-DD').format('DD/MM/YYYY') +
                    ': ' +
                    error.mensaje
                );
            })
            .join('<br>');

        Swal.fire({
            icon: 'warning',
            title: 'Revise las jornadas',
            html: detalle
        });

        return;
    }

    // Si no hay filas diligenciadas, informa y no realiza petición.
    if (resultado.jornadas.length === 0) {
        Swal.fire({
            icon: 'info',
            title: 'Sin cambios',
            text: 'No existen jornadas nuevas o borradores para guardar.'
        });

        return;
    }

    Swal.fire({
        icon: 'question',
        title: 'Guardar borradores',
        html:
            'Se guardarán <strong>' +
            resultado.jornadas.length +
            '</strong> jornada(s) como borrador.<br><br>' +
            'Podrá continuar diligenciándolas posteriormente.',
        showCancelButton: true,
        confirmButtonText: 'Guardar borrador',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#6c757d'
    }).then(function (respuesta) {
        if (!respuesta.isConfirmed) {
            return;
        }

        mostrarProcesandoEquipo('#btn-guardar-borrador', 'Guardando borradores...');

        $.ajax({
            url:
                '../../controller/jornada.php?' +
                'op=guardarBorradoresEquipoMasivo',
            type: 'POST',
            dataType: 'json',
            data: {
                csrf_token: $('#csrf_token').val(),
                empleado_id: expedienteEquipo.empleadoId,
                jornadas: JSON.stringify(resultado.jornadas)
            }
        }).done(function (respuestaAjax) {
            const datos = respuestaAjax.data || {};

            let mensaje =
                'Borradores creados: ' +
                (datos.creadas || 0) +
                '.';

            mensaje +=
                ' Actualizados: ' +
                (datos.actualizadas || 0) +
                '.';

            Swal.fire({
                icon: 'success',
                title: 'Borradores guardados',
                text: mensaje,
                timer: 2200,
                showConfirmButton: false
            });

            // Recarga el expediente para obtener IDs y estados reales de BD.
            consultarExpedienteEquipo();
        }).fail(function (xhr) {
            Swal.fire({
                icon: 'error',
                title: 'No fue posible guardar',
                text: equipoMensajeError(
                    xhr,
                    'No fue posible guardar los borradores.'
                )
            });
        }).always(function () {
            finalizarProcesandoEquipo();
        });
    });
}

// En el Paso 3 este botón será conectado a la aprobación masiva.
// Valida y envía las jornadas para registrarlas y aprobarlas masivamente.
function registrarAprobarJornadasEquipo() {
    const resultado = obtenerJornadasAprobacionEquipo();

    // No se aprueba el expediente si existen filas parcialmente diligenciadas.
    if (resultado.errores.length > 0) {
        const detalle = resultado.errores
            .map(function (error) {
                return (
                    moment(error.fecha, 'YYYY-MM-DD').format('DD/MM/YYYY') +
                    ': ' +
                    error.mensaje
                );
            })
            .join('<br>');

        Swal.fire({
            icon: 'warning',
            title: 'Existen jornadas incompletas',
            html: detalle
        });

        return;
    }

    // Las filas totalmente vacías se ignoran.
    if (resultado.jornadas.length === 0) {
        Swal.fire({
            icon: 'info',
            title: 'Sin jornadas',
            text:
                'No existen jornadas nuevas o borradores ' +
                'para registrar y aprobar.'
        });

        return;
    }

    Swal.fire({
        icon: 'question',
        title: 'Registrar y aprobar jornadas',
        html:
            'Se procesarán <strong>' +
            resultado.jornadas.length +
            '</strong> jornada(s).<br><br>' +
            'Las jornadas quedarán aprobadas automáticamente ' +
            'a nombre del jefe inmediato.',
        showCancelButton: true,
        confirmButtonText: 'Registrar y aprobar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#28a745'
    }).then(function (respuesta) {
        if (!respuesta.isConfirmed) {
            return;
        }

        mostrarProcesandoEquipo('#btn-registrar-aprobar', 'Registrando y aprobando jornadas...');

        $.ajax({
            url:
                '../../controller/jornada.php?' +
                'op=registrarAprobarEquipoMasivo',
            type: 'POST',
            dataType: 'json',
            data: {
                csrf_token: $('#csrf_token').val(),
                empleado_id: expedienteEquipo.empleadoId,
                jornadas: JSON.stringify(resultado.jornadas)
            }
        }).done(function (respuestaAjax) {
            const datos = respuestaAjax.data || {};

            let mensaje =
                'Nuevas jornadas aprobadas: ' +
                (datos.creadas_aprobadas || 0) +
                '.';

            mensaje +=
                ' Borradores aprobados: ' +
                (datos.borradores_aprobados || 0) +
                '.';

            Swal.fire({
                icon: 'success',
                title: 'Jornadas aprobadas',
                text: mensaje,
                timer: 2500,
                showConfirmButton: false
            });

            // Al recargar, las jornadas aprobadas quedan bloqueadas.
            consultarExpedienteEquipo();
        }).fail(function (xhr) {
            Swal.fire({
                icon: 'error',
                title: 'No fue posible aprobar',
                text: equipoMensajeError(
                    xhr,
                    'No fue posible registrar y aprobar las jornadas.'
                )
            });
        }).always(function () {
            finalizarProcesandoEquipo();
        });
    });
}

// Obtiene nuevas jornadas y todos los borradores listos para aprobación.
function obtenerJornadasAprobacionEquipo() {
    const jornadas = [];
    const errores = [];

    $('#tabla-expediente tbody tr').each(function () {
        const filaDom = this;
        const fila = tablaExpediente.row(filaDom).data();

        if (!fila || !filaEditableEquipo(fila)) {
            return;
        }

        const datos = obtenerDatosFilaEquipo(filaDom);

        // Las filas nuevas totalmente vacías no se incluyen.
        if (
            fila.estado_codigo === 'NUEVA' &&
            filaVaciaEquipo(datos)
        ) {
            return;
        }

        // Borradores y nuevas diligenciadas deben estar completos.
        if (!filaCompletaEquipo(datos)) {
            errores.push({
                fecha: datos.fecha,
                mensaje:
                    'La jornada tiene información incompleta.'
            });

            return;
        }

        jornadas.push(datos);
    });

    return {
        jornadas: jornadas,
        errores: errores
    };
}

// Inicializa los componentes de la pantalla.
$(document).ready(function () {
    inicializarSelectEmpleadoEquipo();
    inicializarRangoEquipo();

    cargarContextoEquipo();
    cargarSubordinados();
    cargarUbicacionesJornada();

    $('#contenedor-expediente').hide();
});

// Consulta el expediente seleccionado.
$('#btn-consultar-expediente').on('click', function () {
    consultarExpedienteEquipo();
});

// Si cambia el empleado, oculta el expediente anterior.
$('#empleado_id').on('change', function () {
    $('#contenedor-expediente').hide();

    expedienteEquipo = {
        empleadoId: null,
        empleadoNombre: '',
        documento: '',
        fechaDesde: '',
        fechaHasta: ''
    };
});

// Si cambia el periodo, obliga a realizar una nueva consulta.
$('#filtro_fechas').on('apply.daterangepicker', function () {
    $('#contenedor-expediente').hide();
});

// Calcula las horas cuando cambia la entrada.
$('#tabla-expediente tbody').on('click', '.jornada-agregar-turno', function () {
    const filaDom = $(this).closest('tr')[0];
    const fila = tablaExpediente.row(filaDom).data();
    const nueva = crearFilaNuevaEquipo(fila.fecha);
    nueva.turno_adicional = true;
    const nodo = tablaExpediente.row.add(nueva).draw(false).node();
    $(nodo).find('.jornada-entrada').trigger('focus');
});

$('#tabla-expediente tbody').on('click', '.jornada-eliminar-turno', function () {
    const filaDom = $(this).closest('tr')[0];
    const fila = tablaExpediente.row(filaDom).data();
    if (fila && fila.estado_codigo === 'BORRADOR') {
        const boton = $(this);
        const empleadoId = expedienteEquipo.empleadoId;
        Swal.fire({
            icon: 'warning',
            title: 'Eliminar turno en borrador',
            text: 'Se anulará el turno del ' + moment(fila.fecha).format('DD/MM/YYYY') +
                ' de ' + fila.hora_entrada + ' a ' + fila.hora_salida + '. Indique el motivo.',
            input: 'textarea',
            inputValue: 'Turno registrado por equivocación',
            inputAttributes: { maxlength: 2000 },
            inputValidator: function (valor) {
                if (!valor || !valor.trim()) return 'Indique el motivo de anulación.';
            },
            showCancelButton: true,
            confirmButtonText: 'Eliminar turno',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc3545'
        }).then(function (resultado) {
            if (!resultado.isConfirmed) return;
            boton.prop('disabled', true);
            $.ajax({
                url: '../../controller/jornada.php?op=anularBorradorEquipo',
                type: 'POST',
                dataType: 'json',
                data: {
                    csrf_token: $('#csrf_token').val(),
                    empleado_id: empleadoId,
                    jornada_id: fila.jornada_id,
                    motivo: resultado.value.trim()
                }
            }).done(function () {
                // Actualiza solo esta fila para conservar los demás cambios pendientes.
                if (!$.contains(document, filaDom)) return;
                $(filaDom).removeData('solicitud-calculo');
                fila.estado_codigo = 'ANULADO';
                fila.estado_nombre = 'Anulado';
                tablaExpediente.row(filaDom).data(fila).draw(false);
                $(filaDom).attr('data-modificada', '0').removeClass('table-warning').addClass('bg-light');
            }).fail(function (xhr) {
                Swal.fire({ icon: 'error', title: 'No fue posible eliminar',
                    text: equipoMensajeError(xhr, 'No fue posible anular el turno.') });
            }).always(function () {
                boton.prop('disabled', false);
            });
        });
        return;
    }
    if (!fila || !fila.turno_adicional || fila.estado_codigo !== 'NUEVA') {
        return;
    }
    // Ignora cualquier cálculo pendiente de la fila retirada.
    $(filaDom).removeData('solicitud-calculo');
    tablaExpediente.row(filaDom).remove().draw(false);
});

$('#tabla-expediente tbody').on(
    'change',
    '.jornada-entrada',
    function () {
        const filaDom = $(this).closest('tr')[0];

        if ($(filaDom).find('.jornada-salida').val()) {
            validarCruceMedianocheEquipo(filaDom);
        }
    }
);

// Evalúa automáticamente cruce de medianoche cuando cambia la salida.
$('#tabla-expediente tbody').on(
    'change',
    '.jornada-salida',
    function () {
        const filaDom = $(this).closest('tr')[0];
        validarCruceMedianocheEquipo(filaDom);
    }
);

// Marca visualmente una fila cuando el jefe modifica información.
$('#tabla-expediente tbody').on(
    'change input',
    '.jornada-entrada, ' +
    '.jornada-salida, ' +
    '.jornada-ubicacion, ' +
    '.jornada-actividad, ' +
    '.jornada-observaciones',
    function () {
        $(this)
            .closest('tr')
            .attr('data-modificada', '1')
            .addClass('table-warning');
    }
);

// Guarda las filas diligenciadas como borradores.
$('#btn-pdf-borrador').on('click', function () {
    if (!expedienteEquipo.empleadoId) {
        return;
    }
    const cambios = obtenerJornadasEditablesEquipo();
    if (cambios.jornadas.length || cambios.errores.length) {
        Swal.fire({
            icon: 'info',
            title: 'Guarde los cambios',
            text: 'Guarde las jornadas antes de generar el PDF borrador.'
        });
        return;
    }
    const parametros = $.param({
        tipo: 'pdf_borrador_equipo',
        empleado_id: expedienteEquipo.empleadoId,
        fecha_desde: expedienteEquipo.fechaDesde,
        fecha_hasta: expedienteEquipo.fechaHasta
    });
    window.open('../../controller/jornada_exportar.php?' + parametros, '_blank', 'noopener');
});

$('#btn-guardar-borrador').on('click', function () {
    guardarBorradoresEquipo();
});

// Registra las nuevas jornadas y aprueba los borradores del expediente.
$('#btn-registrar-aprobar').on('click', function () {
    registrarAprobarJornadasEquipo();
});
