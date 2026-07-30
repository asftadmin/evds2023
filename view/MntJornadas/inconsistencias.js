function icFiltros() {
    const p = $('#filtro_fechas').val().split(' - ');
    return { fecha_desde: p[0] || '', fecha_hasta: p[1] || '' };
}

function cargarInconsistencias() {
    if ($.fn.DataTable.isDataTable('#tabla-inconsistencias')) {
        $('#tabla-inconsistencias').DataTable().destroy();
    }
    $('#tabla-inconsistencias').DataTable({
        responsive: true,
        autoWidth: false,
        ajax: {
            url: '../../controller/jornada_contable.php',
            data: Object.assign({ op: 'listarInconsistencias' }, icFiltros()),
            dataSrc: function (r) { return r.data || []; }
        },
        columns: [
            { data: 'empleado' }, { data: 'fecha' }, { data: 'entrada' },
            { data: 'salida' }, { data: 'horas' }, { data: 'ubicacion' },
            { data: 'detalle', defaultContent: 'Sin detalle' }, { data: 'estado' }
        ],
        language: {
            emptyTable: 'No hay inconsistencias en el periodo',
            search: 'Buscar:',
            paginate: { previous: 'Anterior', next: 'Siguiente' }
        }
    });
}

$(document).ready(function () {
    $('#filtro_fechas').daterangepicker({
        startDate: moment().startOf('month'),
        endDate: moment(),
        maxDate: moment(),
        locale: { format: 'YYYY-MM-DD', separator: ' - ' }
    });
    $.getJSON('../../controller/jornada_contable.php', {
        op: 'contextoInconsistencias'
    }).done(function (r) {
        $('#texto-contexto').text('Acceso exclusivo de Contabilidad — ' + r.data.empleado);
    }).fail(function () {
        $('#alerta-contexto').removeClass('alert-info').addClass('alert-danger');
        $('#texto-contexto').text('Acceso no autorizado.');
    });
    cargarInconsistencias();
});

$('#btn-consultar').on('click', cargarInconsistencias);
