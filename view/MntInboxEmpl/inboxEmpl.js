let tablaOpen;

function init(){
    inicializarTabla();
}

$('#permisos-btn').on('click', function () {
    cargarSolicitudesP('Jefe');
    //if (tablaRevision) tablaRevision.clear().destroy();
});

function inicializarTabla() {
    if (tablaOpen) {
        tablaOpen.clear(); // Limpiar la tabla existente
    } else {
        tablaOpen = $('#tablaSolcEmpl').DataTable({
            "aProcessing": true,
            "aServerSide": true,
            "searching": false,
            "lengthChange": false,
            "colReorder": true,
            "responsive": true,
            "bInfo": true,
            "iDisplayLength": 7,
            "autoWidth": false,
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
                url: '../../controller/permiso.php?op=listarSolicitudesJefe',
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


function cargarSolicitudesP(tipo = 'Jefe') {
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

function verPermiso(permisoId) {
    window.location.href = BASE_URL + '/view/MntInboxEmpl/detalle_permiso.php?id=' + permisoId; //http://181.204.219.154:3396/preoperacional
}

$(document).ready(function () {
    $("#btnFirma").on("click", function (e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Verificar que el modal existe en esta vista
        if ($("#modalFirmaEmpleado").length > 0) {
            $("#modalFirmaEmpleado").modal("show");
        } else {
            Swal.fire({
                icon: "error",
                title: "Modal no encontrado",
                text: "El modal de firma no está cargado en esta vista."
            });
        }
    });
});

//FIRMA GESTION HUMANA

/* let firmaBase64 = "";
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

}); */





/* */



init();