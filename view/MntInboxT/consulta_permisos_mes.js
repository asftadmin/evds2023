let tablaPermisosMes = null;

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function periodoActual() {
    const hoy = new Date();
    const mes = String(hoy.getMonth() + 1).padStart(2, '0');
    return hoy.getFullYear() + '-' + mes;
}

function estadoBadgeClass(codigo) {
    codigo = String(codigo ?? '');

    if (codigo === '1') return 'badge-warning';
    if (codigo === '2') return 'badge-info';
    if (codigo === '3') return 'badge-success';
    if (codigo === '4' || codigo === '5') return 'badge-primary';
    if (codigo === '6') return 'badge-danger';
    if (codigo === '7') return 'badge-secondary';

    return 'badge-light';
}

function resetResumen() {
    $('#card_colaborador').text('Sin seleccionar');
    $('#card_total_permisos').text('0');
    $('#card_total_horas').text('0:00');
    $('#mensaje_sin_datos').addClass('d-none').text('No se encontro informacion para los filtros seleccionados.');
}

function actualizarResumen(resumen) {
    const colaborador = resumen && resumen.colaborador ? resumen.colaborador : 'Sin seleccionar';
    const documento = resumen && resumen.documento ? resumen.documento : '';

    $('#card_colaborador').text(documento ? colaborador + ' - ' + documento : colaborador);
    $('#card_total_permisos').text(resumen && resumen.total_permisos !== undefined ? resumen.total_permisos : '0');
    $('#card_total_horas').text(resumen && resumen.total_horas ? resumen.total_horas : '0:00');
}

function initSelectColaborador() {
    $('#filtro_colaborador').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: 'Seleccione un colaborador',
        allowClear: true
    });
}

function cargarColaboradores() {
    $.ajax({
        url: '../../controller/informes.php?op=listarColaboradoresPermisosMes',
        type: 'GET',
        dataType: 'json',
        success: function (resp) {
            const $select = $('#filtro_colaborador');
            $select.empty().append(new Option('', '', false, false));

            if (!resp || !resp.success || !Array.isArray(resp.data)) {
                Swal.fire('Error', 'No se pudo cargar la lista de colaboradores.', 'error');
                return;
            }

            resp.data.forEach(function (item) {
                $select.append(new Option(item.text, item.id, false, false));
            });

            $select.trigger('change');
        },
        error: function (xhr) {
            console.log(xhr.responseText);
            Swal.fire('Error', 'No se pudo cargar la lista de colaboradores.', 'error');
        }
    });
}

function initTablaPermisosMes() {
    tablaPermisosMes = $('#tabla_permisos_mes').DataTable({
        data: [],
        columns: [
            { data: 'fecha_permiso', defaultContent: '' },
            { data: 'hora_salida_permiso', defaultContent: '' },
            { data: 'hora_ingreso_permiso', defaultContent: '' },
            { data: 'hora_salida_biotime', defaultContent: 'Sin registro' },
            { data: 'hora_ingreso_biotime', defaultContent: 'Sin registro' },
            {
                data: 'total_horas_permiso',
                defaultContent: '0:00',
                render: function (data, type, row) {
                    if (type !== 'display') {
                        return data;
                    }

                    const permiso = escapeHtml(data || '0:00');
                    const biotime = escapeHtml(row.total_horas_biotime || 'Sin registro');
                    //const diferencia = escapeHtml(row.diferencia_horas || 'Sin registro');

                    return '<span class="font-weight-bold">' + permiso + '</span>' +
                        '<br><small class="text-muted">BioTime: ' + biotime + ' </small>';
                        // | Dif: +4:54
                }
            },
            {
                data: 'motivo',
                defaultContent: '',
                render: function (data) {
                    return escapeHtml(data);
                }
            },
            {
                data: 'estado',
                defaultContent: '',
                render: function (data, type, row) {
                    if (type !== 'display') {
                        return data;
                    }

                    return '<span class="badge ' + estadoBadgeClass(row.estado_codigo) + '">' + escapeHtml(data) + '</span>';
                }
            }
        ],
        order: [[0, 'desc']],
        searching: true,
        lengthChange: true,
        pageLength: 10,
        responsive: true,
        autoWidth: false,
        language: {
            sProcessing: 'Procesando...',
            sLengthMenu: 'Mostrar _MENU_ registros',
            sZeroRecords: 'No se encontraron resultados',
            sEmptyTable: 'Ningun dato disponible en esta tabla',
            sInfo: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
            sInfoEmpty: 'Mostrando 0 registros',
            sInfoFiltered: '(filtrado de _MAX_ registros)',
            sSearch: 'Buscar:',
            sLoadingRecords: 'Cargando...',
            oPaginate: {
                sFirst: 'Primero',
                sLast: 'Ultimo',
                sNext: 'Siguiente',
                sPrevious: 'Anterior'
            }
        }
    });
}

function consultarPermisosMes() {
    const empleadoId = $('#filtro_colaborador').val();
    const periodo = $('#filtro_periodo').val();

    if (!empleadoId) {
        Swal.fire('Validacion', 'Debe seleccionar un colaborador.', 'warning');
        return;
    }

    if (!periodo) {
        Swal.fire('Validacion', 'Debe seleccionar mes y anio.', 'warning');
        return;
    }

    Swal.fire({
        title: 'Consultando permisos',
        html: 'Por favor espere...',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => Swal.showLoading()
    });

    $.ajax({
        url: '../../controller/informes.php?op=consultaPermisosMes',
        type: 'POST',
        dataType: 'json',
        data: {
            empleado_id: empleadoId,
            periodo: periodo
        },
        success: function (resp) {
            Swal.close();

            if (!resp || !resp.success) {
                Swal.fire('Validacion', resp && resp.error ? resp.error : 'No se pudo realizar la consulta.', 'warning');
                return;
            }

            const detalle = Array.isArray(resp.detalle) ? resp.detalle : [];
            actualizarResumen(resp.resumen || {});

            tablaPermisosMes.clear().rows.add(detalle).draw();

            if (detalle.length === 0) {
                $('#mensaje_sin_datos')
                    .removeClass('d-none')
                    .text(resp.message || 'No se encontro informacion para los filtros seleccionados.');
            } else {
                $('#mensaje_sin_datos').addClass('d-none');
            }
        },
        error: function (xhr) {
            Swal.close();
            console.log(xhr.responseText);

            const mensaje = xhr.responseJSON && xhr.responseJSON.error
                ? xhr.responseJSON.error
                : 'Ocurrio un error consultando los permisos.';

            Swal.fire('Error', mensaje, 'error');
        }
    });
}

function limpiarConsultaPermisosMes() {
    $('#filtro_colaborador').val('').trigger('change');
    $('#filtro_periodo').val(periodoActual());
    resetResumen();

    if (tablaPermisosMes) {
        tablaPermisosMes.clear().draw();
    }
}

$(document).ready(function () {
    initSelectColaborador();
    initTablaPermisosMes();
    cargarColaboradores();

    $('#filtro_periodo').val(periodoActual());

    $('#btn_consultar_permisos_mes').on('click', function () {
        consultarPermisosMes();
    });

    $('#btn_limpiar_permisos_mes').on('click', function () {
        limpiarConsultaPermisosMes();
    });
});
