$(document).ready(function () {

    // Inicializa DataTable
    let tabla = $('#data_disabled').DataTable({
        ajax: {
            url: "../../controller/empleado.php?op=listar_activos",
            type: "GET",
            dataType: "json",
            dataSrc: function (json) {
                // DataTables espera un array de objetos; tu backend devuelve aaData o aData
                return json.aaData || json.aData || json.data || [];
            }

        },
        columns: [
            {
                data: 0, title: "Seleccionar",
                render: function (data) {
                    return `<input type="checkbox" class="checkEmpleado" value="${data}">`;
                },
                orderable: false
            },
            {
                data: 1, title: "No. Documento", render: function (data) {
                    // Si el valor existe y es numérico, formatea con separador de miles
                    if (data && !isNaN(data)) {
                        return parseInt(data).toLocaleString('es-CO');
                    }
                    return data; // si viene vacío o texto, lo deja igual
                }
            },
            { data: 2, title: "Nombre Completo" },
            {
                data: 3, title: "Estado", render: function (data) {
                    return data == 1
                        ? '<span class="badge bg-success">Activo</span>'
                        : '<span class="badge bg-danger">Inactivo</span>';
                }
            }
        ],
        order: [[2, "asc"]],
        pageLength: 10,
        responsive: true,
        language: {
            processing: "Procesando...",
            search: "Buscar:",
            lengthMenu: "Mostrar _MENU_ registros",
            info: "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
            infoEmpty: "Mostrando registros del 0 al 0 de un total de 0 registros",
            infoFiltered: "(filtrado de un total de _MAX_ registros)",
            infoPostFix: "",
            loadingRecords: "Cargando...",
            zeroRecords: "No se encontraron resultados",
            emptyTable: "Ningún dato disponible en esta tabla",
            paginate: {
                first: "Primero",
                previous: "Anterior",
                next: "Siguiente",
                last: "Último"
            },
            aria: {
                sortAscending: ": Activar para ordenar la columna de manera ascendente",
                sortDescending: ": Activar para ordenar la columna de manera descendente"
            }
        }
    });

    // Check/Uncheck todos
    $('#checkAll').on('click', function () {
        $('.checkEmpleado').prop('checked', this.checked);
    });

    // Botón Inactivar
    $('#btnInactivar').click(function () {
        let ids = [];
        $('.checkEmpleado:checked').each(function () {
            ids.push($(this).val());
        });

        if (ids.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Atención',
                text: 'Seleccione al menos un empleado para inactivar',
                confirmButtonColor: '#3085d6'
            });
            return;
        }

        Swal.fire({
            title: '¿Desea inactivar los empleados seleccionados?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, inactivar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "../../controller/empleado.php?op=inactivar_masivo",
                    type: "POST",
                    dataType: "json",
                    data: { ids: ids },
                    success: function (response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Listo!',
                                text: 'Empleados inactivados correctamente.',
                                confirmButtonColor: '#3085d6'
                            });
                            tabla.ajax.reload(); // recarga DataTable
                            $('#checkAll').prop('checked', false);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'No se pudo inactivar los empleados.'
                            });
                        }
                    },
                    error: function () {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Ocurrió un problema en la comunicación con el servidor.'
                        });
                    }
                });
            }
        });
    });

});