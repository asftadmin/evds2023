let tablaFestivos = null;

function cfEscape(valor) {
    return $('<div>').text(valor == null ? '' : String(valor)).html();
}

function cfError(xhr, mensaje) {
    return xhr.responseJSON && xhr.responseJSON.message
        ? xhr.responseJSON.message
        : mensaje;
}

function nombreDiaFestivo(fecha) {
    const dias = [
        'Domingo', 'Lunes', 'Martes', 'Miércoles',
        'Jueves', 'Viernes', 'Sábado'
    ];
    return dias[new Date(fecha + 'T00:00:00').getDay()];
}

function cargarContextoConfiguracion() {
    $.getJSON('../../controller/jornada_contable.php', {
        op: 'contextoConfiguracion'
    }).done(function (respuesta) {
        $('#texto-contexto').text(
            'Administración exclusiva de Contabilidad — ' +
            respuesta.data.empleado
        );
    }).fail(function (xhr) {
        $('#alerta-contexto').removeClass('alert-info').addClass('alert-danger');
        $('#texto-contexto').text(cfError(xhr, 'Acceso no autorizado.'));
        $('#form-festivo :input').prop('disabled', true);
    });
}

function cargarFestivos() {
    if ($.fn.DataTable.isDataTable('#tabla-festivos')) {
        $('#tabla-festivos').DataTable().destroy();
    }

    tablaFestivos = $('#tabla-festivos').DataTable({
        responsive: true,
        autoWidth: false,
        order: [[0, 'asc']],
        ajax: {
            url: '../../controller/jornada_contable.php',
            type: 'GET',
            dataType: 'json',
            data: {
                op: 'listarFestivos',
                anio: $('#anio').val()
            },
            dataSrc: function (respuesta) {
                return respuesta.data || [];
            },
            error: function (xhr) {
                Swal.fire(
                    'Error',
                    cfError(xhr, 'No fue posible consultar el calendario.'),
                    'error'
                );
            }
        },
        columns: [
            { data: 'cf_fecha' },
            {
                data: 'cf_fecha',
                render: function (fecha) {
                    return nombreDiaFestivo(fecha);
                }
            },
            {
                data: 'cf_descripcion',
                render: function (valor) {
                    return cfEscape(valor);
                }
            },
            {
                data: 'cf_estado',
                render: function (estado) {
                    return Number(estado) === 1
                        ? '<span class="badge badge-success">Activo</span>'
                        : '<span class="badge badge-secondary">Inactivo</span>';
                }
            },
            {
                data: 'creado_por',
                defaultContent: '',
                render: function (valor) {
                    return cfEscape(valor || '');
                }
            },
            {
                data: null,
                orderable: false,
                render: function (data, type, fila) {
                    const activo = Number(fila.cf_estado) === 1;
                    return '<div class="btn-group btn-group-sm">' +
                        '<button class="btn btn-warning btn-editar" ' +
                        'data-id="' + Number(fila.cf_id) + '" title="Editar">' +
                        '<i class="fas fa-edit"></i></button>' +
                        '<button class="btn ' +
                        (activo ? 'btn-danger' : 'btn-success') +
                        ' btn-estado" data-id="' + Number(fila.cf_id) +
                        '" data-estado="' + (activo ? 0 : 1) + '" title="' +
                        (activo ? 'Inactivar' : 'Activar') + '">' +
                        '<i class="fas ' +
                        (activo ? 'fa-ban' : 'fa-check') + '"></i></button>' +
                        '</div>';
                }
            }
        ],
        language: {
            emptyTable: 'No hay fechas especiales configuradas para el año',
            search: 'Buscar:',
            info: 'Mostrando _START_ a _END_ de _TOTAL_',
            paginate: { previous: 'Anterior', next: 'Siguiente' }
        }
    });
}

function limpiarFestivo() {
    $('#form-festivo')[0].reset();
    $('#fecha').prop('readonly', false);
    $('#btn-guardar').html('<i class="fas fa-save mr-1"></i>Guardar');
}

function guardarFestivo() {
    $('#btn-guardar').prop('disabled', true);
    $.ajax({
        url: '../../controller/jornada_contable.php?op=guardarFestivo',
        type: 'POST',
        dataType: 'json',
        data: {
            csrf_token: $('#csrf_token').val(),
            fecha: $('#fecha').val(),
            descripcion: $('#descripcion').val()
        }
    }).done(function (respuesta) {
        const afectadas = Number(
            respuesta.data && respuesta.data.jornadas_invalidadas
        ) || 0;
        Swal.fire({
            icon: 'success',
            title: 'Fecha guardada',
            text: afectadas > 0
                ? afectadas + ' jornada(s) quedaron pendientes de recalcular.'
                : respuesta.message
        });
        $('#anio').val($('#fecha').val().substring(0, 4));
        limpiarFestivo();
        cargarFestivos();
    }).fail(function (xhr) {
        Swal.fire(
            'No fue posible guardar',
            cfError(xhr, 'Revise la información.'),
            'error'
        );
    }).always(function () {
        $('#btn-guardar').prop('disabled', false);
    });
}

function cambiarEstadoFestivo(festivoId, estado) {
    Swal.fire({
        icon: 'question',
        title: estado === 1 ? 'Activar fecha' : 'Inactivar fecha',
        text: 'Las liquidaciones relacionadas deberán recalcularse.',
        showCancelButton: true,
        confirmButtonText: 'Confirmar',
        cancelButtonText: 'Cancelar'
    }).then(function (resultado) {
        if (!resultado.isConfirmed) {
            return;
        }

        $.ajax({
            url: '../../controller/jornada_contable.php?op=cambiarEstadoFestivo',
            type: 'POST',
            dataType: 'json',
            data: {
                csrf_token: $('#csrf_token').val(),
                festivo_id: festivoId,
                estado: estado
            }
        }).done(function (respuesta) {
            const afectadas = Number(
                respuesta.data && respuesta.data.jornadas_invalidadas
            ) || 0;
            Swal.fire({
                icon: 'success',
                title: 'Estado actualizado',
                text: afectadas > 0
                    ? afectadas + ' jornada(s) quedaron pendientes de recalcular.'
                    : respuesta.message
            });
            cargarFestivos();
        }).fail(function (xhr) {
            Swal.fire(
                'No fue posible actualizar',
                cfError(xhr, 'Intente nuevamente.'),
                'error'
            );
        });
    });
}

$(document).ready(function () {
    cargarContextoConfiguracion();
    cargarFestivos();
});

$('#form-festivo').on('submit', function (evento) {
    evento.preventDefault();
    guardarFestivo();
});

$('#btn-limpiar').on('click', limpiarFestivo);
$('#btn-consultar').on('click', cargarFestivos);

$(document).on('click', '.btn-editar', function () {
    const fila = tablaFestivos
        .row($(this).closest('tr'))
        .data();
    if (!fila) {
        return;
    }
    $('#fecha').val(fila.cf_fecha).prop('readonly', true);
    $('#descripcion').val(fila.cf_descripcion);
    $('#btn-guardar').html('<i class="fas fa-save mr-1"></i>Actualizar');
    $('html, body').animate({ scrollTop: 0 }, 300);
});

$(document).on('click', '.btn-estado', function () {
    cambiarEstadoFestivo(
        Number($(this).data('id')),
        Number($(this).data('estado'))
    );
});
