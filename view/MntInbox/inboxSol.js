let tablaOpen = null;

function init() {
    inicializarTabla();
}

$('#abiertos-btn').on('click', function () {
    cargarSolicitudesP('Pendientes');
    //if (tablaRevision) tablaRevision.clear().destroy();
});


function inicializarTabla() {
    if (tablaOpen) {
        tablaOpen.clear(); // Limpiar la tabla existente
    } else {
        tablaOpen = $('#tableSolicitudes').DataTable({
            "aProcessing": true,
            "aServerSide": true,
            "searching": false,
            "lengthChange": false,
            "colReorder": true,
            "responsive": true,
            "bInfo": true,
            "iDisplayLength": 7,
            "autoWidth": false,
            "language": {
                "sProcessing": "Procesando...",
                "sLengthMenu": "Mostrar _MENU_ registros",
                "sZeroRecords": "No se encontraron resultados",
                "sEmptyTable": "Ningún dato disponible en esta tabla",
                "sInfo": "Mostrando un total de _TOTAL_ registros",
                "sInfoEmpty": "Mostrando un total de 0 registros",
                "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
                "sSearch": "Buscar:",
                "oPaginate": {
                    "sFirst": "Primero",
                    "sLast": "Último",
                    "sNext": "Siguiente",
                    "sPrevious": "Anterior"
                }
            },
            "ajax": {
                url: '../../controller/permiso.php?op=listarSolicitudesPendientes',
                type: "POST",
                dataType: "json",
                success: function (response) {
                    console.log(response);

                    if (response && response.aaData) {
                        tablaOpen.clear().rows.add(response.aaData).draw(); // Añadir los datos a la tabla
                    }
                },
                error: function (e) {
                    console.log(e.responseText);
                }
            }
        });
    }
}

function cargarSolicitudesP(tipo = 'Pendientes') {
    const nuevaURL = `../../controller/tickets.php?op=listarSolicitudes${tipo}`;
    if (tablaOpen) {
        tablaOpen.ajax.url(nuevaURL).load(); // recarga datos sin reiniciar tabla
    } else {
        inicializarTabla();
    }
}


function aprobar(codigo_permiso) {

    Swal.fire({
        title: "¿Estás seguro?",
        text: "¿Deseas aprobar este permiso?",
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#009BA9",
        cancelButtonColor: "#FF0000",
        iconColor: "#FF0000",
        confirmButtonText: "Sí, aprobar",
        cancelButtonText: "Cancelar",
        reverseButtons: true
    }).then((result) => {

        if (result.isConfirmed) {

            $.post(
                "../../controller/permiso.php?op=aprobarPermiso",
                { codigo_permiso: codigo_permiso },
                function (data) {

                    let respuesta = JSON.parse(data);

                    if (respuesta.success) {

                        Swal.fire({
                            title: "Aprobado",
                            text: respuesta.message,
                            icon: "success",
                            timer: 1500,
                            showConfirmButton: false
                        });

                        // RECARGAR DATATABLE
                        $("#tableSolicitudes").DataTable().ajax.reload();

                    } else {

                        Swal.fire({
                            title: "Advertencia",
                            text: respuesta.message,
                            icon: "warning"
                        });

                    }
                }
            ).fail(function (xhr) {
                Swal.fire("Error", "Error en el servidor.", "error");
                console.log(xhr.responseText);
            });

        }
    });
    
}

function rechazar(codigo_permiso) {

    Swal.fire({
        title: "Rechazar Permiso",
        text: "Escribe el motivo del rechazo:",
        icon: "warning",
        iconColor: "#FF0000",
        input: "textarea",
        inputPlaceholder: "Describa el motivo del rechazo...",
        inputAttributes: {
            "aria-label": "Motivo del rechazo"
        },
        showCancelButton: true,
        confirmButtonColor: "#009BA9",
        cancelButtonColor: "#FF0000",
        confirmButtonText: "Rechazar",
        cancelButtonText: "Cancelar",
        reverseButtons: true,
        inputValidator: (value) => {
            if (!value) {
                return "Debe escribir un motivo para rechazar el permiso.";
            }
        }
    }).then((result) => {

        if (result.isConfirmed) {

            let motivo = result.value;

            $.post(
                "../../controller/permiso.php?op=rechazarPermiso",
                {
                    codigo_permiso: codigo_permiso,
                    motivo_rechazo: motivo
                },
                function (data) {

                    let respuesta = JSON.parse(data);

                    if (respuesta.success) {

                        Swal.fire({
                            title: "Rechazado",
                            text: "El permiso fue rechazado correctamente.",
                            icon: "success",
                            timer: 1500,
                            showConfirmButton: false
                        });

                        // recargar datatable
                        $("#tableSolicitudes").DataTable().ajax.reload();

                    } else {

                        Swal.fire({
                            title: "Error",
                            text: respuesta.message,
                            icon: "error"
                        });

                    }
                }
            ).fail(function (xhr) {
                Swal.fire("Error", "Error en el servidor.", "error");
                console.log(xhr.responseText);
            });

        }

    });
}





init();