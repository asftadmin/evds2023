var tabla = null;

document.getElementById('abiertos-btn').addEventListener('click', function () {
    cargarSolicitudes();  // Llama a la función que carga los tickets "Abiertos"
});


function cargarSolicitudes() {
    if (tabla) {
        tabla.clear(); // Limpiar la tabla existente
    } else {
        tabla = $('#tableSolicitudes').DataTable({
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
                url: '../../controller/permiso.php?op=listarSolicitudesOpen',
                type: "POST",
                dataType: "json",
                success: function (response) {
                    console.log(response);
                
                    if (response && response.aaData) {
                        tabla.clear().rows.add(response.aaData).draw(); // Añadir los datos a la tabla
                    }
                },
                error: function (e) {
                    console.log(e.responseText);
                }
            }
        });
    }
}