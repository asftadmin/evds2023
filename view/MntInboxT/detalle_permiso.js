//Boton Regresar a la bandeja de Abiertos
$('#btnVolver').click(function() {
    // Verificar si viene de tickets.php
    if(document.referrer.indexOf('inbox.php') !== -1) {
        history.back(); // Regresa a la página anterior
    } else {
        window.location.href = 'inbox.php'; // Redirige por defecto
    }
});


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


$(document).ready(function() {

    var permisoID = getURLParameter('id');

    $.ajax({
        url: '../../controller/permiso.php?op=detallePermiso',
        type: 'GET',
        data: { id: permisoID },
        success: function (response) {

            const detalle = JSON.parse(response);

            if (detalle.status === 'error') {
                Swal.fire('Error', detalle.message, 'error');
            } else {
                $("#detallePermiso").html(detalle.html);


                // reconstruir select2
                $('.select2').select2();
            }
        },
        error: function (xhr, status, error) {
            Swal.fire('Error', 'No se pudo cargar el detalle: ' + error, 'error');
        }
    });

});