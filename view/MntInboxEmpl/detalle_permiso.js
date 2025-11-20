
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
