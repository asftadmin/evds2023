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


$(document).on("click", "#btnFirma", function (e) {
    e.preventDefault();
    this.blur();

    // Verifica si el modal existe en la página actual
    if ($("#modalFirmaEmpleado").length) {

        $("#modalFirmaEmpleado").modal("show");

    } else {

        Swal.fire({
            title: "Firma no disponible aquí",
            text: "Serás redirigido a la página de inicio. Desde allí ve a tu Perfil para registrar tu firma.",
            icon: "info",
            confirmButtonText: "Entendido",
            confirmButtonColor: "#009BA9",
            allowOutsideClick: false
        }).then(() => {
            window.location.href = "../home/home.php";
        });

    }
});


let firmaBase64 = "";
let userId = $("#empl_idx").val();

$("#firmaFile").on("change", function () {

    let file = this.files[0];
    if (!file) return;

    let reader = new FileReader();

    reader.onload = function (e) {
        firmaBase64 = e.target.result; // base64 de la imagen

        $("#previewFirmaEmpleado")
            .attr("src", firmaBase64)
            .show();

        $("#btnGuardarFirmaEmpleado").prop("disabled", false);
    };

    reader.readAsDataURL(file);
});


$("#btnGuardarFirmaEmpleado").click(function () {

    if (!firmaBase64) {
        Swal.fire("Advertencia", "Debes seleccionar una imagen.", "warning");
        return;
    }

    $.ajax({
        url: "../../controller/firma.php?op=guardar",
        type: "POST",
        data: { firma_base64: firmaBase64 },
        success: function (response) {
            let res = JSON.parse(response);

            if (res.success) {
                Swal.fire("Éxito", res.message, "success");
                $("#modalFirmaEmpleado").modal("hide");
            } else {
                Swal.fire("Error", res.message, "error");
            }
        },
        error: function () {
            Swal.fire("Error", "No se pudo guardar la firma.", "error");
        }
    });

});