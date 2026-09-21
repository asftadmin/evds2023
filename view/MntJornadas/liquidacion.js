let tablaLiquidacion = null;
let lcFiltrosAplicados = null;
let lcAutorizado = false;
let lcEnCurso = false;
let lcRestableciendo = false;
let lcSolicitudEmpleados = 0;
let lcUltimaBusqueda = '';
const lcSeleccionadas = new Set();

function lcClaveEstado(tipo) {
    return 'jornadas-liquidacion-v3:' + location.pathname + ':'
        + $('#tabla-liquidacion').attr('data-usuario-id') + ':' + tipo;
}

function lcLeerEstado(tipo) {
    try {
        return JSON.parse(localStorage.getItem(lcClaveEstado(tipo)) || 'null');
    } catch (e) {
        return null;
    }
}

function lcGuardarEstado(tipo, datos) {
    if (lcRestableciendo) {
        return;
    }
    try {
        localStorage.setItem(lcClaveEstado(tipo), JSON.stringify(datos));
    } catch (e) {
        // Los filtros siguen funcionando durante la visita si el navegador no permite guardar.
    }
}

function lcPeriodoInicial() {
    return { inicio: moment().subtract(1, 'month').startOf('month'), fin: moment() };
}

function lcActualizarControles() {
    const bloqueado = lcEnCurso || !lcAutorizado;
    $('#filtro_fechas, #empleado_id, #estado_liquidacion, #estado_jornada, #btn-consultar, #btn-restablecer, '
        + '#tabla-liquidacion button, #tabla-liquidacion input[type="checkbox"], '
        + '#tabla-liquidacion_wrapper .dataTables_filter input, #tabla-liquidacion_wrapper .dataTables_length select')
        .prop('disabled', bloqueado);
    $('#btn-liquidar-seleccionadas').prop('disabled', bloqueado || lcSeleccionadas.size === 0);
}

function lcFilasFiltradas() {
    return tablaLiquidacion ? tablaLiquidacion.rows({ search: 'applied' }).data().toArray().filter(lcPuedeClasificar) : [];
}

function lcPuedeClasificar(fila) {
    return ['APROBADO', 'PENDIENTE_LIQUIDACION', 'LIQUIDADO'].includes(fila.estado_codigo);
}

function lcActualizarSeleccion() {
    const ids = new Set(lcFilasFiltradas().map(function (fila) { return Number(fila.jornada_id); }));
    lcSeleccionadas.forEach(function (id) {
        if (!ids.has(id)) {
            lcSeleccionadas.delete(id);
        }
    });
    $('#tabla-liquidacion .lc-seleccionar').each(function () {
        $(this).prop('checked', lcSeleccionadas.has(Number($(this).data('id'))));
    });
    $('#seleccionar-liquidacion')
        .prop('checked', ids.size > 0 && lcSeleccionadas.size === ids.size)
        .prop('indeterminate', lcSeleccionadas.size > 0 && lcSeleccionadas.size < ids.size);
    $('#conteo-liquidacion').text(lcSeleccionadas.size + ' seleccionadas');
    lcActualizarControles();
}

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
        empleado_id: $('#empleado_id').val() || '',
        estado: $('#estado_liquidacion').val() || '',
        estado_jornada: $('#estado_jornada').val() || '',
        empleado_nombre: $('#empleado_id option:selected').text()
    };
}

function inicializarLiquidacion() {
    const guardado = lcLeerEstado('filtros');
    const periodo = lcPeriodoInicial();
    if (guardado && moment(guardado.fecha_desde, 'YYYY-MM-DD', true).isValid()
        && moment(guardado.fecha_hasta, 'YYYY-MM-DD', true).isValid()
        && guardado.fecha_desde <= guardado.fecha_hasta) {
        periodo.inicio = moment(guardado.fecha_desde);
        periodo.fin = moment(guardado.fecha_hasta);
        if (guardado.empleado_id && /^\d+$/.test(String(guardado.empleado_id))) {
            $('#empleado_id').append(new Option(
                guardado.empleado_nombre || ('Trabajador #' + guardado.empleado_id),
                guardado.empleado_id, true, true
            ));
        }
        if (['', 'PENDIENTE', 'CLASIFICADA'].includes(guardado.estado)) {
            $('#estado_liquidacion').val(guardado.estado);
        }
        $('#estado_jornada').val(guardado.estado_jornada || '');
    }
    $('#filtro_fechas').daterangepicker({
        startDate: periodo.inicio,
        endDate: periodo.fin,
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
    lcActualizarControles();
}

function cargarContextoLiquidacion() {
    $.getJSON('../../controller/jornada_contable.php', {
        op: 'contextoLiquidacion'
    }).done(function (respuesta) {
        lcAutorizado = true;
        lcActualizarControles();
        $('#texto-contexto').text(
            'Acceso exclusivo de Contabilidad — ' + respuesta.data.empleado
        );
    }).fail(function (xhr) {
        $('#alerta-contexto').removeClass('alert-info').addClass('alert-danger');
        $('#texto-contexto').text(lcError(xhr, 'Acceso no autorizado.'));
        lcAutorizado = false;
        lcActualizarControles();
    });
}

function cargarEmpleadosLiquidacion() {
    const filtros = lcRango();
    const solicitud = ++lcSolicitudEmpleados;
    return $.getJSON('../../controller/jornada_contable.php', {
        op: 'listarEmpleados',
        fecha_desde: filtros.fecha_desde,
        fecha_hasta: filtros.fecha_hasta
    }).done(function (respuesta) {
        if (solicitud !== lcSolicitudEmpleados) {
            return;
        }
        const selector = $('#empleado_id');
        const actual = selector.val() || '';
        const nombreActual = selector.find('option:selected').text();
        selector.find('option:not(:first)').remove();
        (respuesta.data || []).forEach(function (fila) {
            selector.append(new Option(
                fila.empleado + ' — ' + fila.documento,
                fila.empleado_id
            ));
        });
        if (actual && !selector.find('option').toArray().some(function (opcion) { return opcion.value === actual; })) {
            selector.append(new Option(nombreActual, actual));
        }
        selector.val(actual).trigger('change.select2');
    });
}

function cargarLiquidacion() {
    if (lcEnCurso) {
        return;
    }
    lcFiltrosAplicados = lcRango();
    lcGuardarEstado('filtros', lcFiltrosAplicados);
    lcSeleccionadas.clear();
    if (tablaLiquidacion) {
        lcActualizarSeleccion();
        tablaLiquidacion.ajax.reload(null, true);
        return;
    }
    tablaLiquidacion = $('#tabla-liquidacion').DataTable({
        processing: true,
        responsive: true,
        autoWidth: false,
        stateSave: true,
        stateDuration: 0,
        stateSaveCallback: function (settings, datos) {
            lcGuardarEstado('tabla', datos);
        },
        stateLoadCallback: function () {
            return lcLeerEstado('tabla');
        },
        pageLength: 10,
        order: [[2, 'desc']],
        ajax: {
            url: '../../controller/jornada_contable.php',
            type: 'GET',
            dataType: 'json',
            data: function (datos) {
                datos.op = 'listarLiquidacion';
                datos.fecha_desde = lcFiltrosAplicados.fecha_desde;
                datos.fecha_hasta = lcFiltrosAplicados.fecha_hasta;
                datos.empleado_id = lcFiltrosAplicados.empleado_id;
            },
            dataSrc: function (respuesta) {
                return (respuesta.data || []).filter(function (fila) {
                    return (!lcFiltrosAplicados.estado_jornada || lcFiltrosAplicados.estado_jornada === fila.estado_codigo)
                        && (!lcFiltrosAplicados.estado || lcFiltrosAplicados.estado === (fila.clasificacion_completa ? 'CLASIFICADA' : 'PENDIENTE'));
                });
            },
            error: function (xhr) {
                Swal.fire('Error', lcError(xhr, 'No fue posible consultar.'), 'error');
            }
        },
        columns: [
            {
                data: null,
                orderable: false,
                searchable: false,
                className: 'all text-center',
                render: function (data, type, fila) {
                    return type === 'display' && lcPuedeClasificar(fila)
                        ? '<input type="checkbox" class="lc-seleccionar" data-id="' + Number(fila.jornada_id)
                            + '" aria-label="Seleccionar jornada #' + Number(fila.jornada_id) + '">'
                        : '';
                }
            },
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
                data: 'estado_nombre',
                render: function (nombre, type, fila) {
                    const texto = nombre || fila.estado_codigo || '';
                    if (type !== 'display') {
                        return texto;
                    }
                    const clases = {
                        BORRADOR: 'badge-secondary',
                        PENDIENTE_APROBACION: 'badge-warning',
                        APROBADO: 'badge-success',
                        PENDIENTE_LIQUIDACION: 'badge-info',
                        LIQUIDADO: 'badge-success',
                        RECHAZADO: 'badge-danger',
                        PENDIENTE_CORRECCION: 'badge-info',
                        CORREGIDO: 'badge-primary',
                        ANULADO: 'badge-dark'
                    };
                    return '<span class="badge ' + (clases[fila.estado_codigo] || 'badge-secondary')
                        + '">' + lcEscape(texto) + '</span>';
                }
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                className: 'all',
                render: function (data, type, fila) {
                    if (!lcPuedeClasificar(fila)) {
                        return '<span class="text-muted">Sin acciones</span>';
                    }
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
        drawCallback: function () {
            const api = this.api();
            const pagina = api.page.info();
            if (pagina.pages > 0 && pagina.page >= pagina.pages) {
                api.page('last').draw('page');
                return;
            }
            lcActualizarSeleccion();
        },
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
    const fila = tablaLiquidacion && tablaLiquidacion.rows().data().toArray().find(function (item) {
        return Number(item.jornada_id) === jornadaId;
    });
    if (fila) {
        liquidarFilas([fila], true, recalcular);
    }
}

function lcMostrarResultado(procesadas, fallidas, pendientes, total) {
    const panel = $('#resultado-liquidacion').empty()
        .removeClass('d-none alert-info alert-success alert-warning')
        .addClass(fallidas.length || pendientes ? 'alert-warning' : 'alert-success');
    $('<strong>').text(procesadas + ' de ' + total + ' jornada(s) liquidadas. '
        + fallidas.length + ' con error.' + (pendientes ? ' ' + pendientes + ' sin procesar.' : '')).appendTo(panel);
    if (fallidas.length) {
        const lista = $('<ul class="mb-0 mt-2">').appendTo(panel);
        fallidas.forEach(function (fallida) {
            $('<li>').text(fallida.fila.empleado + ' — ' + fallida.fila.fecha
                + ' (#' + fallida.fila.jornada_id + '): ' + fallida.mensaje).appendTo(lista);
        });
    }
}

/** Reutiliza el endpoint individual, con una transacción y resultado por jornada. */
function liquidarFilas(filas, individual, recalcular) {
    if (lcEnCurso || !lcAutorizado || !filas.length) {
        return;
    }
    const conSegmentos = filas.filter(function (fila) { return Number(fila.segmentos) > 0; }).length;
    lcEnCurso = true;
    lcActualizarControles();
    Swal.fire({
        icon: 'question',
        title: individual
            ? (recalcular ? 'Recalcular jornada' : 'Clasificar jornada')
            : 'Liquidar ' + filas.length + ' jornada(s)',
        text: individual
            ? (recalcular ? 'Los segmentos anteriores serán reemplazados.'
                : 'Se generarán los segmentos contables automáticamente.')
            : 'Se procesarán las filas seleccionadas.' + (conSegmentos
                ? ' Se recalcularán ' + conSegmentos + ' que ya tienen segmentos, reemplazando los anteriores.'
                : ' Se generarán sus segmentos contables automáticamente.'),
        showCancelButton: true,
        confirmButtonText: individual ? (recalcular ? 'Recalcular' : 'Clasificar') : 'Liquidar seleccionadas',
        cancelButtonText: 'Cancelar'
    }).then(async function (resultado) {
        if (!resultado.isConfirmed) {
            lcEnCurso = false;
            lcActualizarControles();
            return;
        }
        let procesadas = 0;
        let intentadas = 0;
        const fallidas = [];
        const panel = $('#resultado-liquidacion').empty()
            .removeClass('d-none alert-success alert-warning').addClass('alert-info');
        try {
            for (const fila of filas) {
                panel.text('Liquidando ' + (intentadas + 1) + ' de ' + filas.length + '...');
                intentadas++;
                try {
                    const respuesta = await $.ajax({
                        url: '../../controller/jornada_contable.php?op=clasificarJornada',
                        type: 'POST',
                        dataType: 'json',
                        data: { csrf_token: $('#csrf_token').val(), jornada_id: fila.jornada_id }
                    });
                    if (respuesta.success === false) {
                        fallidas.push({ fila: fila, mensaje: respuesta.message || 'No fue posible liquidar.' });
                    } else {
                        procesadas++;
                        lcSeleccionadas.delete(Number(fila.jornada_id));
                    }
                } catch (xhr) {
                    fallidas.push({ fila: fila, mensaje: lcError(xhr, 'No se pudo confirmar la liquidación. Consulte nuevamente.') });
                    if ([0, 401, 403, 419].includes(xhr.status)) {
                        break;
                    }
                }
            }
        } finally {
            lcEnCurso = false;
            lcActualizarControles();
            lcMostrarResultado(procesadas, fallidas, filas.length - intentadas, filas.length);
            if (tablaLiquidacion) {
                tablaLiquidacion.ajax.reload(null, false);
            }
        }
        if (individual) {
            Swal.fire({
                icon: fallidas.length ? 'error' : 'success',
                title: fallidas.length ? 'No fue posible clasificar' : 'Clasificación completa',
                text: fallidas.length ? fallidas[0].mensaje : 'La jornada fue clasificada correctamente.',
                timer: fallidas.length ? undefined : 1800,
                showConfirmButton: fallidas.length > 0
            });
        }
    });
}

function restablecerLiquidacion() {
    if (lcEnCurso || !lcAutorizado) {
        return;
    }
    lcRestableciendo = true;
    try {
        localStorage.removeItem(lcClaveEstado('filtros'));
        localStorage.removeItem(lcClaveEstado('tabla'));
    } catch (e) {
        // Restablecer también funciona si el almacenamiento está bloqueado.
    }
    if (tablaLiquidacion) {
        tablaLiquidacion.state.clear();
        tablaLiquidacion.search('').columns().search('');
        tablaLiquidacion.order([[2, 'desc']]).page.len(10).page('first');
    }
    const periodo = lcPeriodoInicial();
    const selector = $('#filtro_fechas').data('daterangepicker');
    selector.setStartDate(periodo.inicio);
    selector.setEndDate(periodo.fin);
    $('#empleado_id').val('').trigger('change.select2');
    $('#estado_liquidacion').val('');
    $('#estado_jornada').val('');
    $('#resultado-liquidacion').empty().addClass('d-none');
    lcUltimaBusqueda = '';
    lcRestableciendo = false;
    cargarEmpleadosLiquidacion();
    cargarLiquidacion();
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

$('#estado_liquidacion, #estado_jornada').on('change', cargarLiquidacion);

$('#btn-restablecer').on('click', restablecerLiquidacion);

$('#btn-liquidar-seleccionadas').on('click', function () {
    const filas = lcFilasFiltradas().filter(function (fila) {
        return lcSeleccionadas.has(Number(fila.jornada_id));
    });
    liquidarFilas(filas, false, false);
});

$('#seleccionar-liquidacion').on('change', function () {
    lcSeleccionadas.clear();
    if (this.checked) {
        lcFilasFiltradas().forEach(function (fila) { lcSeleccionadas.add(Number(fila.jornada_id)); });
    }
    lcActualizarSeleccion();
});

$('#tabla-liquidacion').on('change', '.lc-seleccionar', function () {
    const id = Number($(this).data('id'));
    if (this.checked) {
        lcSeleccionadas.add(id);
    } else {
        lcSeleccionadas.delete(id);
    }
    lcActualizarSeleccion();
}).on('click', 'input[type="checkbox"]', function (evento) {
    evento.stopPropagation();
}).on('search.dt', function (evento, settings) {
    const busqueda = new $.fn.dataTable.Api(settings).search();
    if (busqueda !== lcUltimaBusqueda) {
        lcSeleccionadas.clear();
        lcUltimaBusqueda = busqueda;
    }
}).on('responsive-display.dt', lcActualizarSeleccion);

$(document).on('click', '.btn-segmentos', function () {
    mostrarSegmentos(Number($(this).data('id')));
});

$(document).on('click', '.btn-clasificar', function () {
    clasificarJornada(
        Number($(this).data('id')),
        String($(this).data('recalcular')) === '1'
    );
});
