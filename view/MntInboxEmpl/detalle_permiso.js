
var getURLParameter = function (sParam) {
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
        cargarSoportes(permiso_id);
    } else {
        console.log("No se encontró parámetro 'id' en la URL");
    }

});


$('#btnVolverR').click(function () {
    // 1. Si hay historial previo → volver
    if (document.referrer && document.referrer !== "") {
        history.back();
        return;
    }
});

Dropzone.autoDiscover = false;

var permiso_id_drop = getURLParameter('id');

let myDropzone = new Dropzone(".dropzone", {

    url: BASE_URL + "/controller/permiso.php?op=subirSoporte",
    maxFilesize: 10,
    acceptedFiles: ".jpg,.jpeg,.png,.pdf,.doc,.docx",
    addRemoveLinks: true,
    dictRemoveFile: "Eliminar",

    init: function () {
        var dz = this;

        this.on("sending", function (file, xhr, formData) {
            formData.append("permiso_id", permiso_id_drop);
        });

        this.on("success", function (file, response) {

            Swal.fire({
                icon: "success",
                title: "Subido correctamente",
                showConfirmButton: false,
                timer: 1200
            });

            // Recargar la lista de soportes
            cargarSoportes(permiso_id_drop);

            // Esperar y borrar archivo del Dropzone (solo visual)
            setTimeout(function () {
                dz.removeFile(file);
            }, 1000);
        });

        this.on("error", function (file, message) {
            Swal.fire("Error", message, "error");

            // Remover archivo erróneo también
            var dz = this;
            setTimeout(function () {
                dz.removeFile(file);
            }, 1000);
        });
    }

});



function cargarSoportes(permiso_id) {

    $.ajax({
        url: BASE_URL + "/controller/permiso.php?op=listarSoportes",
        type: "POST",
        data: { permiso_id: permiso_id },
        success: function (response) {

            let lista = JSON.parse(response);
            let html = "";

            lista.forEach(s => {

                html += `
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="../../controller/permiso.php?op=descargarSoporte&file=${encodeURIComponent(s.soporte_ruta)}" target="_blank">
                            ${s.soporte_nombre}
                        </a>
                        <span class="badge badge-secondary">${s.soporte_fecha}</span>
                    </li>
                `;
            });

            $("#listaSoportes").html(html);
        }
    });
}

