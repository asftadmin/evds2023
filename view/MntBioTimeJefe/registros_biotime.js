let tablaRegistrosBiotime;
let swalRegistrosBiotimeActivo = false;

function init() {
    initSelect2RegistrosBiotime();
    initDateRangeRegistrosBiotime();
    cargarComboEmpleadosBiotimeJefe();
}

function initSelect2RegistrosBiotime() {
    $('.select2bs4').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: 'Seleccione un empleado',
        allowClear: true
    });
}

function initDateRangeRegistrosBiotime() {
    $('#filtro-fechas').daterangepicker({
        startDate: moment().subtract(8, 'days'),
        endDate: moment().subtract(1, 'days'),
        showDropdowns: true,
        autoUpdateInput: true,
        maxDate: moment(),
        locale: {
            format: 'YYYY-MM-DD',
            applyLabel: 'Aplicar',
            cancelLabel: 'Cancelar',
            customRangeLabel: 'Rango personalizado',
            daysOfWeek: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'],
            monthNames: [
                'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
            ]
        }
    }, function (start, end) {
        $('#filtro-fechas').val(
            start.format('YYYY-MM-DD') + ' - ' + end.format('YYYY-MM-DD')
        );
    });
}

function cargarComboEmpleadosBiotimeJefe() {
    $.ajax({
        url: '../../controller/biopro.php?op=comboEmpleadosBiotimeJefe',
        type: 'GET',
        success: function (html) {
            $('#filtro-empleado')
                .html(html)
                .val('')
                .trigger('change');
        },
        error: function (xhr) {
            console.log(xhr.responseText);

            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo cargar el listado de empleados asignados.'
            });
        }
    });
}

$(document).on('click', '#btn-filtrar-fechas', function () {
    cargarRegistrosBiotimeJefe();
});

function cargarRegistrosBiotimeJefe() {
    const rangoFechas = $('#filtro-fechas').val();

    if (!rangoFechas || rangoFechas.indexOf(' - ') === -1) {
        Swal.fire({
            icon: 'warning',
            title: 'Fechas requeridas',
            text: 'Seleccione un rango de fechas válido.'
        });
        return;
    }

    const fechas = rangoFechas.split(' - ');
    const fechainicio = fechas[0];
    const fechafin = fechas[1];
    const empleado = $('#filtro-empleado').val() || '';

    swalRegistrosBiotimeActivo = true;

    Swal.fire({
        title: 'Cargando registros Biotime',
        html: 'Por favor espere...',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    tablaRegistrosBiotime = $('#tabla_registros_biotime').dataTable({
        aProcessing: true,
        aServerSide: true,
        searching: true,
        lengthChange: true,
        pageLength: 10,
        lengthMenu: [
            [5, 10, 25, 50, 100, -1],
            [5, 10, 25, 50, 100, 'Todos']
        ],
        dom:
            "<'row mb-2'<'col-md-7 d-flex flex-wrap align-items-center'B<'ml-2'l>><'col-md-5'f>>" +
            "<'row'<'col-12'tr>>" +
            "<'row mt-2'<'col-md-5'i><'col-md-7'p>>",
        colReorder: true,
        buttons: [
            {
                extend: 'copyHtml5',
                text: 'Copiar',
                className: 'btn btn-secondary btn-sm',
                exportOptions: { columns: ':visible' }
            },
            {
                extend: 'excelHtml5',
                text: 'Excel',
                className: 'btn btn-success btn-sm',
                exportOptions: { columns: ':visible' }
            },
            {
                extend: 'csvHtml5',
                text: 'CSV',
                className: 'btn btn-info btn-sm',
                exportOptions: { columns: ':visible' }
            },
            {
                extend: 'pdfHtml5',
                text: 'PDF',
                className: 'btn btn-danger btn-sm',
                orientation: 'landscape',
                pageSize: 'A4',
                exportOptions: { columns: ':visible' }
            },
            {
                extend: 'print',
                text: 'Imprimir',
                className: 'btn btn-primary btn-sm',
                exportOptions: { columns: ':visible' }
            }
        ],
        ajax: {
            url: '../../controller/biopro.php?op=listarRegistrosBiotimeJefe',
            type: 'GET',
            dataType: 'json',
            data: {
                fechainicio: fechainicio,
                fechafin: fechafin,
                empleado: empleado
            },
            complete: function () {
                if (swalRegistrosBiotimeActivo) {
                    Swal.close();
                    swalRegistrosBiotimeActivo = false;
                }
            },
            error: function (xhr) {
                if (swalRegistrosBiotimeActivo) {
                    Swal.close();
                    swalRegistrosBiotimeActivo = false;
                }

                console.log(xhr.responseText);

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ocurrió un error consultando los registros Biotime.'
                });
            }
        },
        bDestroy: true,
        responsive: true,
        bInfo: true,
        autoWidth: false,
        order: [[0, 'desc']],
        language: {
            sProcessing: 'Procesando...',
            sLengthMenu: 'Mostrar _MENU_ registros',
            sZeroRecords: 'No se encontraron resultados',
            sEmptyTable: 'Ningún dato disponible en esta tabla',
            sInfo: 'Mostrando un total de _TOTAL_ registros',
            sInfoEmpty: 'Mostrando un total de 0 registros',
            sInfoFiltered: '(filtrado de un total de _MAX_ registros)',
            sSearch: 'Buscar:',
            sLoadingRecords: 'Cargando...',
            oPaginate: {
                sFirst: 'Primero',
                sLast: 'Último',
                sNext: 'Siguiente',
                sPrevious: 'Anterior'
            },
            oAria: {
                sSortAscending: ': Activar para ordenar la columna de manera ascendente',
                sSortDescending: ': Activar para ordenar la columna de manera descendente'
            }
        }
    }).DataTable();
}

$(document).on('click', '#btn-limpiar-filtros', function () {
    $('#filtro-empleado').val('').trigger('change');

    const startDefault = moment().subtract(8, 'days');
    const endDefault = moment().subtract(1, 'days');

    const picker = $('#filtro-fechas').data('daterangepicker');

    if (picker) {
        picker.setStartDate(startDefault);
        picker.setEndDate(endDefault);
    }

    $('#filtro-fechas').val(
        startDefault.format('YYYY-MM-DD') + ' - ' + endDefault.format('YYYY-MM-DD')
    );

    if ($.fn.DataTable.isDataTable('#tabla_registros_biotime')) {
        $('#tabla_registros_biotime').DataTable().clear().destroy();
    }

    $('#tabla_registros_biotime tbody').empty();
});

init();