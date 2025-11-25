const BASE_URL = "http://localhost/evds2023";
//const BASE_URL = "http://181.204.219.154:3396/evds2023";  


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