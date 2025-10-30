let tabla;

function init() {

};

$('#filtro-fechas').daterangepicker({
    startDate: '2017-01-01',
    endDate: moment(), // hoy
    showDropdowns: true,
    autoUpdateInput: true,
    maxDate: moment(), // para evitar futuras
    locale: {
        format: 'YYYY-MM-DD',
        applyLabel: 'Aplicar',
        cancelLabel: 'Cancelar',
        customRangeLabel: 'Rango personalizado',
        daysOfWeek: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'],
        monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']
    }
}, function (start, end) {
    // Callback para actualizar el valor del input si necesitas hacer algo
    $('#filtro-fechas').val(start.format('YYYY-MM-DD') + ' - ' + end.format('YYYY-MM-DD'));
});

// Forzar el calendario a mostrar la fecha final (hoy)
$('#filtro-fechas').on('show.daterangepicker', function (ev, picker) {
    picker.leftCalendar.month = moment().clone().subtract(1, 'month');
    picker.rightCalendar.month = moment().clone();
    picker.updateCalendars();
});


$(document).on('click', '#btn-filtrar-fechas', function () {
    $.ajax({
        url: '../../controller/empleado.php?op=consultarEmpleados',
        type: 'GET',
        dataType: 'json',
        success: function (response) {
            const empleadosBD = response.aaData
                .filter(row => row[0] !== null && row[0] !== undefined)
                .map(row => row[0].toString().trim());
            cargarEmpleadosAPI(empleadosBD);
        }
    });
});


function cargarEmpleadosAPI(empleadosBD) {
    const fechas = $('#filtro-fechas').val().split(' - ');
    const fechainicio = fechas[0];
    const fechafin = fechas[1];

    $('#empleados_data_siesa').DataTable({
        destroy: true,
        ajax: {
            url: '../../controller/empleado.php?op=consultaEmpleadoSiesa',
            type: 'GET',
            data: {
                fechainicio,
                fechafin
            },
            dataSrc: function (json) {
                return json.aaData.map(row => {
                    const documento = row[0].toString().trim();
                    const existe = empleadosBD.includes(documento);
                    const estado = existe
                        ? '<span class="badge bg-success">Registrado</span>'
                        : '<span class="badge bg-danger">Nuevo</span>';
                    const accionesHTML = existe
                        ? '' // No mostrar botones si ya está registrado
                        : `<div class="text-center">
                        <button class="btn btn-info btn-sm btn-guardar-empleado" data-documento="${documento}">
                            <i class="fa fa-save"></i>
                        </button>
                        </div>`;
                    row.splice(7, 0, estado);
                    row.splice(8, 1, accionesHTML);
                    return row;
                });
            }
        },
        order: [[1, 'asc']],
        iDisplayLength: 5,
        language: {
            "sProcessing": "Procesando...",
            "sLengthMenu": "Mostrar _MENU_ registros",
            "sZeroRecords": "No se encontraron resultados",
            "sEmptyTable": "Ningún dato disponible en esta tabla",
            "sInfo": "Mostrando un total de _TOTAL_ registros",
            "sInfoEmpty": "Mostrando un total de 0 registros",
            "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
            "sInfoPostFix": "",
            "sSearch": "Buscar:",
            "sUrl": "",
            "sInfoThousands": ",",
            "sLoadingRecords": "Cargando...",
            "oPaginate": {
                "sFirst": "Primero",
                "sLast": "Último",
                "sNext": "Siguiente",
                "sPrevious": "Anterior"
            },
            "oAria": {
                "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
                "sSortDescending": ": Activar para ordenar la columna de manera descendente"
            }
        }
    });
}


//Boton guardar empleado

$(document).on('click', '.btn-guardar-empleado', function () {
    const documento = $(this).data('documento');

    // Buscar la fila completa en la tabla
    const fila = $(this).closest('tr');
    const datos = $('#empleados_data_siesa').DataTable().row(fila).data();

    // Aquí puedes extraer los campos que necesitas
    const payload = {
        documento: datos[0],       // f200_id
        nombre: datos[1],          // f200_razon_social
        fecha_ingreso: datos[2],
        fecha_nacimiento: datos[3],
        direccion: datos[4],
        celular: datos[6]
    };

    let empleadosBD = [];

    $.ajax({
        url: '../../controller/empleado.php?op=guardarEmpleadoNuevo',
        type: 'POST',
        data: payload,
        success: function (response) {
            Swal.fire('Guardado', 'El empleado ha sido registrado', 'success');
            const table = $('#empleados_data_siesa').DataTable();
            const rowIndex = table.row(fila).index();

            datos[7] = '<span class="badge bg-success">Registrado</span>';
            datos[8] = ''; // Quitar el botón

            table.row(rowIndex).data(datos).invalidate().draw(false);
        },
        error: function () {
            Swal.fire('Error', 'No se pudo guardar el empleado', 'error');
        }
    });
});




init();