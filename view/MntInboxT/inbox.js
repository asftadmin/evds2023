let tablaOpen;

function init(){
    inicializarTabla();
}

$('#solicitudes-btn').on('click', function () {
    cargarSolicitudesR('Jefe');
    //if (tablaRevision) tablaRevision.clear().destroy();
});

function inicializarTabla() {
    if (tablaOpen) {
        tablaOpen.clear(); // Limpiar la tabla existente
    } else {
        tablaOpen = $('#tablaSolcRhumano').DataTable({
            "aProcessing": true,
            "aServerSide": true,
            "searching": false,
            "lengthChange": true,
            "colReorder": true,
            "responsive": true,
            "autoWidth": false,
            "bInfo": true,
            "pageLength": 10, 
            "order": [[1, 'desc']],
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
                url: '../../controller/permiso.php?op=listarSolicitudesRecursos',
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


function cargarSolicitudesR(tipo = 'Recursos') {
    const nuevaURL = `../../controller/tickets.php?op=listarSolicitudes${tipo}`;
    if (tablaOpen) {
        tablaOpen.ajax.url(nuevaURL).load(); // recarga datos sin reiniciar tabla
    } else {
        inicializarTabla();
    }
}


var getURLParameter = function(sParam) {
    var sPageURL = decodeURIComponent(window.location.search.substring(1));
    var sURLVariables = sPageURL.split('&');
    var sParameterName;
    for (var i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');
        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : sParameterName[1];
        }
    }
}


function ver(permisoID) {
    console.log(permisoID);
    window.location.href = BASE_URL + '/view/MntInboxT/detalle_permiso.php?id=' + permisoID; //http://181.204.219.154:3396/preoperacional
    verSolicitud(permisoID);
}


function verTimeline(permisoID){
    window.location.href = BASE_URL + '/view/MntInboxEmpl/detalle_permiso.php?id=' + permisoID; //http://181.204.219.154:3396/preoperacional
    cargarTimeline(permisoID);
}

function verPdf(permisoID) {
    console.log(permisoID);
    var url = BASE_URL + '/view/PDF/permisoPDF.php?id=' + permisoID; //http://181.204.219.154:3396/preoperacional
    window.open(url, '_blank');
}




init();