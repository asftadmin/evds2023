let rcLoteActual = null;
let rcFilasActuales = [];
let rcPeriodoHoras = null;

function rcNombreLote(nombre) {
    return String(nombre || '').replace(/^(?:Corrección v\d+ - )+/, '');
}

function rcInicializarHorasExtras() {
    JornadasPeriodo.inicializar('#horas_periodo', {
        showDropdowns: true,
        locale: {
            format: 'DD/MM/YYYY', separator: ' - ', applyLabel: 'Aplicar',
            cancelLabel: 'Cancelar', customRangeLabel: 'Otro periodo',
            daysOfWeek: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'],
            monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']
        }
    });
    const tabla = $('#tabla-horas-extras').DataTable({
        data: [], autoWidth: false, order: [[0, 'asc']], dom: 'Bfrtip',
        buttons: [{
            extend: 'excelHtml5', text: '<i class="fas fa-file-excel mr-1"></i>Exportar a Excel',
            className: 'btn btn-success',
            enabled: false,
            title: function () { return 'Horas extras liquidadas ' + (rcPeriodoHoras || ''); },
            filename: function () { return 'Horas_extras_' + (rcPeriodoHoras || ''); },
            exportOptions: { columns: ':visible', orthogonal: 'export' }
        }],
        columns: [
            { data: 'empleado', render: $.fn.dataTable.render.text() },
            { data: 'documento', render: $.fn.dataTable.render.text() }
        ].concat(['ibc', 'valor_hora', 'rn', 'valor_rn', 'hed', 'valor_hed',
            'hen', 'valor_hen', 'hedf', 'valor_hedf', 'henf', 'valor_henf', 'total'].map(function (codigo) {
            return { data: codigo, className: 'text-right', render: function (valor, tipo) {
                if (valor === null || valor === undefined) {
                    return tipo === 'display' ? (codigo === 'valor_rn' ? 'Sin cálculo' : 'No disponible') : '';
                }
                const horas = Number(valor) || 0;
                return tipo === 'display' ? horas.toLocaleString('es-CO', {
                    minimumFractionDigits: 2, maximumFractionDigits: 2
                }) : horas;
            } };
        })),
        language: {
            emptyTable: 'No hay jornadas liquidadas para mostrar', search: 'Buscar:',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ trabajadores',
            infoEmpty: 'Sin trabajadores', infoFiltered: '(de _MAX_ trabajadores)',
            zeroRecords: 'No se encontraron trabajadores',
            paginate: { previous: 'Anterior', next: 'Siguiente' }
        }
    });
    tabla.buttons().container().appendTo('#horas-exportar');
    tabla.button(0).enable(false);
    $('#horas_periodo').on('apply.daterangepicker', function () {
        tabla.clear().draw();
        tabla.button(0).enable(false);
        rcPeriodoHoras = null;
        $('#horas-resultado').text('Pulse Consultar para cargar el periodo seleccionado.');
    });
    $('#btn-consultar-horas').on('click', function () {
        const rango = $('#horas_periodo').data('daterangepicker');
        const desde = rango.startDate.format('YYYY-MM-DD');
        const hasta = rango.endDate.format('YYYY-MM-DD');
        const boton = $(this).prop('disabled', true);
        tabla.clear().draw();
        tabla.button(0).enable(false);
        $('#horas-resultado').text('Consultando jornadas liquidadas y salarios en Siesa...');
        Swal.fire({
            title: 'Consultando',
            text: 'Consultando jornadas liquidadas y salarios en Siesa...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            showCloseButton: false,
            didOpen: function () { Swal.showLoading(); }
        });
        $.getJSON('../../controller/jornada_contable.php', {
            op: 'listarHorasExtras', fecha_desde: desde, fecha_hasta: hasta
        }).done(function (respuesta) {
            Swal.close();
            // Descarta respuestas si se cambió el periodo durante la consulta.
            if (rango.startDate.format('YYYY-MM-DD') !== desde ||
                rango.endDate.format('YYYY-MM-DD') !== hasta) return;
            rcPeriodoHoras = desde + '_al_' + hasta;
            const filas = respuesta.data || [];
            tabla.rows.add(filas).draw();
            tabla.button(0).enable(filas.length > 0);
            $('#horas-resultado').text('Periodo: (' + moment(desde).format('DD/MM/YYYY')
                + ' - ' + moment(hasta).format('DD/MM/YYYY') + '), trabajadores ' + filas.length + '.');
        }).fail(function (xhr) {
            Swal.close();
            $('#horas-resultado').text('No fue posible consultar el periodo.');
            Swal.fire('Error de consulta', rcError(xhr, 'No fue posible consultar las horas extras.'), 'error');
        }).always(function () { boton.prop('disabled', false); });
    });
    const claveTab = 'jornadas-reporte-contable:tab:' + location.pathname;
    $('#tab-lotes, #tab-horas-extras').on('shown.bs.tab', function () {
        try {
            sessionStorage.setItem(claveTab, this.id);
        } catch (e) {
            // La navegación sigue disponible si el navegador bloquea el almacenamiento.
        }
        $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
    });
    try {
        const tabGuardado = sessionStorage.getItem(claveTab);
        if (['tab-lotes', 'tab-horas-extras'].includes(tabGuardado)) {
            $('#' + tabGuardado).tab('show');
        }
    } catch (e) {
        // Conserva la pestaña predeterminada cuando no hay almacenamiento disponible.
    }
}

function rcEscapar(texto) {
    return $('<div>').text(texto == null ? '' : texto).html();
}

function rcError(xhr, predeterminado) {
    return (xhr.responseJSON && xhr.responseJSON.message)
        ? xhr.responseJSON.message
        : predeterminado;
}

function rcEstado(estado) {
    const clases = {
        BORRADOR: 'badge-warning',
        CERRADO: 'badge-success',
        REEMPLAZADO: 'badge-secondary',
        ANULADO: 'badge-danger',
        LISTO: 'badge-success',
        SIN_NOVEDAD: 'badge-secondary',
        PENDIENTE: 'badge-warning',
        BLOQUEADO: 'badge-danger',
        SIN_BASE: 'badge-danger'
    };
    const textos = {
        SIN_NOVEDAD: 'Sin novedad',
        SIN_BASE: 'Sin base'
    };
    return '<span class="badge ' + (clases[estado] || 'badge-light') + '">'
        + rcEscapar(textos[estado] || estado) + '</span>';
}

function rcFechaHora(valor) {
    return valor ? moment(valor).format('YYYY-MM-DD HH:mm') : '';
}

function rcCargarLotes(seleccionarId) {
    $.getJSON('../../controller/jornada_contable.php', {
        op: 'listarLotesReporte'
    }).done(function (respuesta) {
        const filas = respuesta.data || [];
        if ($.fn.DataTable.isDataTable('#tabla-lotes')) {
            $('#tabla-lotes').DataTable().clear().rows.add(filas).draw(false);
        } else {
            $('#tabla-lotes').DataTable({
                data: filas,
                responsive: true,
                autoWidth: false,
                stateSave: true,
                stateDuration: -1,
                order: [[0, 'desc']],
                columns: [
                    {
                        data: 'jlot_fecha_creacion',
                        render: function (v) { return rcFechaHora(v); }
                    },
                    { data: 'jlot_nombre', render: function (valor, tipo) {
                        const nombre = rcNombreLote(valor);
                        return tipo === 'display' ? rcEscapar(nombre) : nombre;
                    } },
                    {
                        data: null,
                        render: function (fila) {
                            const version = 'v' + (fila.jlot_version || 1);
                            if (fila.jlot_tipo === 'CORRECCION') {
                                return '<span class="badge badge-warning">'
                                    + version + ' Corrección</span>';
                            }
                            return '<span class="badge badge-light">' + version
                                + '</span>';
                        }
                    },
                    { data: 'jlot_fecha_corte' },
                    {
                        data: 'jlot_estado',
                        render: function (v) { return rcEstado(v); }
                    },
                    { data: 'empleados' },
                    {
                        data: null,
                        render: function (fila) {
                            return fila.listos + ' / ' + fila.sin_novedad
                                + ' sin novedad';
                        }
                    },
                    { data: 'bloqueados' },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function (fila) {
                            return '<button class="btn btn-sm btn-info btn-ver-lote" '
                                + 'data-id="' + fila.jlot_id + '" title="Ver lote">'
                                + '<i class="fas fa-eye"></i></button>';
                        }
                    }
                ],
                language: {
                    emptyTable: 'Aún no hay lotes documentales',
                    search: 'Buscar:',
                    paginate: { previous: 'Anterior', next: 'Siguiente' }
                }
            });
        }
        if (seleccionarId) {
            rcAbrirLote(seleccionarId);
        }
    }).fail(function (xhr) {
        Swal.fire('No fue posible consultar', rcError(xhr, 'Error de consulta.'), 'error');
    });
}

// Los anulados con snapshots conservan únicamente las descargas históricas.
function rcAccionesLote(lote) {
    const id = lote.jlot_id;
    if (lote.jlot_estado === 'ANULADO' && !rcFilasActuales.some(function (fila) { return !!fila.jle_snapshot; })) return '';
    const anular = '<button class="btn btn-sm btn-outline-danger ml-2" id="btn-anular-lote">'
        + '<i class="fas fa-ban mr-1"></i>Anular lote</button>';
    if (lote.jlot_estado === 'BORRADOR') {
        return '<button class="btn btn-sm btn-outline-info mr-2" id="btn-refrescar-lote">'
            + '<i class="fas fa-sync-alt mr-1"></i>Validar de nuevo</button>'
            + '<button class="btn btn-sm btn-success" id="btn-cerrar-lote">'
            + '<i class="fas fa-lock mr-1"></i>Cerrar lote</button>' + anular;
    }
    let acciones = '<a class="btn btn-sm btn-danger mr-2" target="_blank" '
        + 'href="../../controller/jornada_exportar.php?tipo=pdf_lote&lote_id=' + id + '">'
        + '<i class="fas fa-file-pdf mr-1"></i>PDF por lote</a>'
        + '<a class="btn btn-sm btn-success" target="_blank" '
        + 'href="../../controller/jornada_exportar.php?tipo=excel&lote_id=' + id + '">'
        + '<i class="fas fa-file-excel mr-1"></i>Excel consolidado</a>';
    if (lote.jlot_estado === 'CERRADO') {
        acciones += '<button class="btn btn-sm btn-warning ml-2" '
            + 'id="btn-corregir-lote">'
            + '<i class="fas fa-edit mr-1"></i>Corregir lote</button>';
    }
    return acciones + (lote.jlot_estado === 'CERRADO' ? anular : '');
}

function rcAbrirLote(loteId) {
    $.getJSON('../../controller/jornada_contable.php', {
        op: 'listarEmpleadosLote',
        lote_id: loteId
    }).done(function (respuesta) {
        rcLoteActual = respuesta.data.lote;
        rcFilasActuales = respuesta.data.empleados || [];
        $('#card-detalle').removeClass('d-none');
        $('#titulo-lote').text(
            rcNombreLote(rcLoteActual.jlot_nombre)
            + ' — versión ' + (rcLoteActual.jlot_version || 1)
            + ' — corte ' + rcLoteActual.jlot_fecha_corte
            + (rcLoteActual.jlot_estado === 'ANULADO' ? ' — ANULADO (consulta histórica)' : '')
        );
        $('#acciones-lote').html(rcAccionesLote(rcLoteActual));

        if ($.fn.DataTable.isDataTable('#tabla-empleados-lote')) {
            $('#tabla-empleados-lote').DataTable()
                .clear().rows.add(rcFilasActuales).draw(false);
        } else {
            $('#tabla-empleados-lote').DataTable({
                data: rcFilasActuales,
                responsive: true,
                autoWidth: false,
                stateSave: true,
                stateDuration: -1,
                columns: [
                    { data: 'empleado' },
                    { data: 'documento' },
                    { data: 'jle_desde' },
                    { data: 'jle_hasta' },
                    {
                        data: 'jle_origen_periodo',
                        render: function (v) {
                            if (v === 'MANUAL') {
                                return 'Ajustado';
                            }
                            if (v === 'CORRECCION') {
                                return 'Corrección';
                            }
                            return 'Sugerido';
                        }
                    },
                    {
                        data: 'jle_estado',
                        render: function (v) { return rcEstado(v); }
                    },
                    { data: 'jle_cantidad_jornadas' },
                    { data: 'jle_cantidad_pendientes' },
                    { data: 'horas_reportables' },
                    { data: 'jle_diagnostico' },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function (fila) {
                            if (rcLoteActual.jlot_estado === 'ANULADO' && !fila.jle_snapshot) return '';
                            if (rcLoteActual.jlot_estado === 'BORRADOR') {
                                return '<button class="btn btn-sm btn-primary btn-editar-periodo" '
                                    + 'data-id="' + fila.jle_id + '" title="Ajustar periodo">'
                                    + '<i class="fas fa-calendar-alt"></i></button>';
                            }
                            return '<a class="btn btn-sm btn-danger" target="_blank" '
                                + 'href="../../controller/jornada_exportar.php?tipo=pdf_empleado'
                                + '&fila_id=' + fila.jle_id + '" title="PDF del empleado">'
                                + '<i class="fas fa-file-pdf"></i></a>';
                        }
                    }
                ],
                language: {
                    emptyTable: 'El lote no contiene empleados',
                    search: 'Buscar:',
                    paginate: { previous: 'Anterior', next: 'Siguiente' }
                }
            });
        }
        $('html, body').animate({
            scrollTop: $('#card-detalle').offset().top - 70
        }, 250);
    }).fail(function (xhr) {
        Swal.fire('No fue posible abrir', rcError(xhr, 'Error de consulta.'), 'error');
    });
}

$(document).ready(function () {
    rcInicializarHorasExtras();
    $('#fecha_corte').val(moment().format('YYYY-MM-DD'));
    $.getJSON('../../controller/jornada_contable.php', {
        op: 'contextoReporte'
    }).done(function (r) {
        $('#texto-contexto').text(
            'Acceso exclusivo de Contabilidad — ' + r.data.empleado
        );
        rcCargarLotes();
        $('#btn-consultar-horas').prop('disabled', false);
    }).fail(function () {
        $('#alerta-contexto').removeClass('alert-info').addClass('alert-danger');
        $('#texto-contexto').text('Acceso no autorizado.');
    });
});

$('#btn-crear-lote').on('click', function () {
    const nombre = $('#nombre_lote').val().trim();
    const corte = $('#fecha_corte').val();
    if (nombre.length < 3 || !corte) {
        Swal.fire('Datos incompletos', 'Indique nombre y fecha de corte.', 'warning');
        return;
    }
    $.post('../../controller/jornada_contable.php', {
        op: 'crearLoteReporte',
        nombre: nombre,
        fecha_corte: corte,
        csrf_token: $('#csrf_token').val()
    }).done(function (r) {
        $('#nombre_lote').val('');
        Swal.fire('Lote preparado', r.message, 'success');
        rcCargarLotes(r.data.lote_id);
    }).fail(function (xhr) {
        Swal.fire('No fue posible preparar', rcError(xhr, 'Error al crear.'), 'error');
    });
});

$(document).on('click', '.btn-ver-lote', function () {
    rcAbrirLote($(this).data('id'));
});

$(document).on('click', '.btn-editar-periodo', function () {
    const id = Number($(this).data('id'));
    const fila = rcFilasActuales.find(function (item) {
        return Number(item.jle_id) === id;
    });
    if (!fila) {
        return;
    }
    $('#periodo_fila_id').val(fila.jle_id);
    $('#periodo_empleado').val(fila.empleado + ' — ' + fila.documento);
    $('#periodo_desde').val(fila.jle_desde);
    $('#periodo_hasta').val(fila.jle_hasta);
    $('#periodo_motivo').val(fila.jle_motivo_ajuste || '');
    $('#modal-periodo').modal('show');
});

$('#btn-guardar-periodo').on('click', function () {
    const motivo = $('#periodo_motivo').val().trim();
    if (motivo.length < 5) {
        Swal.fire('Motivo requerido', 'Explique por qué cambia el periodo.', 'warning');
        return;
    }
    $.post('../../controller/jornada_contable.php', {
        op: 'ajustarPeriodoLote',
        fila_id: $('#periodo_fila_id').val(),
        desde: $('#periodo_desde').val(),
        hasta: $('#periodo_hasta').val(),
        motivo: motivo,
        csrf_token: $('#csrf_token').val()
    }).done(function (r) {
        $('#modal-periodo').modal('hide');
        Swal.fire('Periodo actualizado', r.message, 'success');
        rcAbrirLote(rcLoteActual.jlot_id);
        rcCargarLotes();
    }).fail(function (xhr) {
        Swal.fire('No fue posible actualizar', rcError(xhr, 'Error al guardar.'), 'error');
    });
});

$(document).on('click', '#btn-refrescar-lote', function () {
    $.post('../../controller/jornada_contable.php', {
        op: 'refrescarLoteReporte',
        lote_id: rcLoteActual.jlot_id,
        csrf_token: $('#csrf_token').val()
    }).done(function (r) {
        Swal.fire('Validación terminada', r.message, 'success');
        rcAbrirLote(rcLoteActual.jlot_id);
        rcCargarLotes();
    }).fail(function (xhr) {
        Swal.fire('No fue posible validar', rcError(xhr, 'Error al validar.'), 'error');
    });
});

$(document).on('click', '#btn-cerrar-lote', function () {
    Swal.fire({
        title: '¿Cerrar el lote?',
        text: 'Los periodos y cálculos quedarán congelados para control documental.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, cerrar',
        cancelButtonText: 'Cancelar'
    }).then(function (resultado) {
        if (!resultado.value) {
            return;
        }
        $.post('../../controller/jornada_contable.php', {
            op: 'cerrarLoteReporte',
            lote_id: rcLoteActual.jlot_id,
            csrf_token: $('#csrf_token').val()
        }).done(function (r) {
            Swal.fire('Lote cerrado', r.message, 'success');
            rcCargarLotes(rcLoteActual.jlot_id);
        }).fail(function (xhr) {
            Swal.fire('No fue posible cerrar', rcError(xhr, 'Error al cerrar.'), 'error');
        });
    });
});

$(document).on('click', '#btn-anular-lote', function () {
    const lote = rcLoteActual;
    if (!lote) return;
    Swal.fire({
        title: '¿Anular lote?',
        text: rcNombreLote(lote.jlot_nombre) + ' — versión ' + lote.jlot_version
            + ' — corte ' + lote.jlot_fecha_corte
            + '. Dejará de estar vigente y sus periodos quedarán disponibles para otros lotes. '
            + 'Se conservará el historial y las jornadas no se modificarán. No se reactivarán versiones anteriores.',
        icon: 'warning',
        input: 'textarea',
        inputLabel: 'Motivo de anulación',
        inputAttributes: { maxlength: 500 },
        inputValidator: function (valor) {
            if (!valor || !valor.trim()) return 'Indique el motivo de anulación.';
        },
        showCancelButton: true,
        confirmButtonText: 'Sí, anular lote',
        confirmButtonColor: '#dc3545',
        cancelButtonText: 'Cancelar',
        showLoaderOnConfirm: true,
        allowOutsideClick: function () { return !Swal.isLoading(); },
        allowEscapeKey: function () { return !Swal.isLoading(); },
        preConfirm: function (motivo) {
            return $.ajax({
                url: '../../controller/jornada_contable.php', type: 'POST', dataType: 'json',
                data: { op: 'anularLoteReporte', lote_id: lote.jlot_id,
                    motivo: motivo.trim(), csrf_token: $('#csrf_token').val() }
            }).then(function (r) {
                if (!r.success) { Swal.showValidationMessage(r.message); return false; }
                return r;
            }, function (xhr) {
                Swal.showValidationMessage(rcError(xhr, 'No fue posible anular el lote.'));
                return false;
            });
        }
    }).then(function (resultado) {
        if (!resultado.isConfirmed || !resultado.value) return;
        Swal.fire('Lote anulado', resultado.value.message, 'success');
        rcCargarLotes(lote.jlot_id);
    });
});

$(document).on('click', '#btn-corregir-lote', function () {
    Swal.fire({
        title: '¿Crear una corrección?',
        text: 'Se copiarán los mismos empleados y periodos en una nueva versión. '
            + 'El lote actual permanecerá vigente hasta cerrar la corrección.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Crear corrección',
        cancelButtonText: 'Cancelar'
    }).then(function (resultado) {
        if (!resultado.value) {
            return;
        }
        $.post('../../controller/jornada_contable.php', {
            op: 'crearCorreccionLote',
            lote_id: rcLoteActual.jlot_id,
            csrf_token: $('#csrf_token').val()
        }).done(function (r) {
            Swal.fire('Corrección preparada', r.message, 'success');
            rcCargarLotes(r.data.lote_id);
        }).fail(function (xhr) {
            Swal.fire(
                'No fue posible crear la corrección',
                rcError(xhr, 'Error al preparar la nueva versión.'),
                'error'
            );
        });
    });
});
