
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


function cargarTimeline(permiso_id) {

    $.ajax({
        url: "../../controller/permiso.php?op=timeline",
        type: "POST",
        data: { permiso_id: permiso_id },
        success: function (html) {
            $("#timelineContainer").html(html);
        },
        error: function (xhr) {
            console.log(xhr.responseText);
            Swal.fire("Error", "No se pudo cargar la trazabilidad.", "error");
        }
    });

}

$(document).ready(function () {

    var permiso_id = getURLParameter('id');

    if (permiso_id !== undefined && permiso_id !== null && permiso_id !== "") {
        cargarTimeline(permiso_id);
    } else {
        console.log("No se encontró parámetro 'id' en la URL");
    }

});


$('#btnVolverR').click(function () {
    // Verificar si viene de tickets.php
    if (document.referrer.indexOf('inboxEmpl.php') !== -1) {
        history.back(); // Regresa a la página anterior
    } else {
        window.location.href = 'inboxEmpl.php'; // Redirige por defecto
    }
});

Dropzone.autoDiscover = false;

var permiso_id_drop = getURLParameter('id');

let myDropzone = new Dropzone(".dropzone", {

    url: BASE_URL + "/controller/permiso.php?op=subirSoporte",

    maxFilesize: 10, // MB
    acceptedFiles: ".jpg,.jpeg,.png,.pdf,.doc,.docx",
    addRemoveLinks: true,
    dictRemoveFile: "Eliminar",

    init: function () {
        this.on("success", function (file, response) {
            Swal.fire("Subido", "Archivo cargado correctamente", "success");
            cargarSoportes(permiso_id_drop);
        });

        this.on("error", function (file, errorMessage) {
            Swal.fire("Error", errorMessage, "error");
        });
    }

});
