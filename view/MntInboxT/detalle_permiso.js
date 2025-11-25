function init() {

    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%'
    });

    $("#form_detalle_rrhh").on("submit", function (e) {
        guardar(e);
    });

}
//Boton Regresar a la bandeja de Abiertos
$('#btnVolver').click(function () {
    if (document.referrer && document.referrer !== "") {
        history.back();
        return;
    }
});


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


$(document).ready(function () {

    var permisoID = getURLParameter('id');

    $.ajax({
        url: '../../controller/permiso.php?op=detallePermiso',
        type: 'GET',
        data: { id: permisoID },
        success: function (response) {

            const detalle = JSON.parse(response);

            if (detalle.status === 'error') {
                Swal.fire('Error', detalle.message, 'error');
                return;
            }

            // Insertar formulario
            $("#detallePermiso").html(detalle.html);

            cargarSoportes(permisoID);

            // ID real guardado en el atributo data-valorbd
            var motivoBD = $("#permiso_motivo").data("valorbd");

            // Cargar lista completa
            $.post("../../controller/tipo_permiso.php?op=comboTipoPermiso", function (data) {

                var $select = $("#permiso_motivo");

                $select.html(data); // coloca todas las opciones

                // seleccionar el valor de la BD
                if (motivoBD) {
                    $select.val(motivoBD).trigger("change");
                }

                // activar select2
                $('.select2').select2();
            });

        },
        error: function (xhr, status, error) {
            Swal.fire('Error', 'No se pudo cargar el detalle: ' + error, 'error');
        }
    });

});


//permiso_fecha


$('#reservationdate_fecha').daterangepicker({
    singleDatePicker: true,
    showDropdowns: true,
    autoApply: true,
    autoUpdateInput: true,  // ← Actualiza el input automáticamente
    drops: 'down',
    minYear: '1900',
    maxYear: '2090',
    locale: {
        format: 'YYYY-MM-DD',
        applyLabel: "Aplicar",
        cancelLabel: "Cancelar",
        daysOfWeek: ["Do", "Lu", "Ma", "Mi", "Ju", "Vi", "Sa"],
        monthNames: ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"],
        firstDay: 1
    }
});

// Forzar el calendario a mostrar la fecha final (hoy)
$('#reservationdate_fecha').on('apply.daterangepicker', function (ev, picker) {
    $('#permiso_fecha').val(picker.startDate.format('YYYY-MM-DD'));
});


$(document).ready(function () {

    const opcionesHora = {
        singleDatePicker: true,
        timePicker: true,
        timePicker24Hour: false,
        timePickerIncrement: 1,
        timePickerSeconds: false,
        autoUpdateInput: true,
        locale: {
            format: 'HH:mm',
            applyLabel: 'Aceptar',
            cancelLabel: 'Cancelar',
            fromLabel: 'Desde',
            toLabel: 'Hasta',
            weekLabel: 'S',
            customRangeLabel: 'Personalizado',
            daysOfWeek: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'],
            monthNames: [
                'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
            ],
            firstDay: 1
        }
    };

    // Inicializar campos
    $('#permiso_hora_salida, #permiso_hora_entrada').each(function () {
        const $input = $(this);
        $input.daterangepicker(opcionesHora, function (start) {
            $input.val(start.format('HH:mm'));
        });

        // Al mostrar el picker, ocultamos solo la tabla de calendario,
        // pero NO el contenedor donde están los selectores de hora.
        $input.on('show.daterangepicker', function (ev, picker) {
            // Oculta solo el calendario, deja visible el bloque de hora
            picker.container.find('.calendar-table').css('display', 'none');
            picker.container.find('.drp-calendar.left').css('display', 'block');
            picker.container.find('.drp-calendar.right').css('display', 'none');
            picker.container.find('.ranges').css('display', 'none');

            // Ajuste visual del cuadro
            picker.container.css({
                width: '250px',
                textAlign: 'center',
                padding: '10px'
            });
        });

        // Valor inicial con hora actual
        $input.val(moment().format('HH:mm'));
    });



});



var permiso_id_drop = getURLParameter('id');

Dropzone.autoDiscover = false;

setTimeout(function () {

    if ($("#uploadZona").length) {

        console.log("Inicializando Dropzone en #uploadZona...");

        let myDropzone = new Dropzone("#uploadZona", {

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

                    cargarSoportes(permiso_id_drop);

                    setTimeout(() => dz.removeFile(file), 1000);
                });

                this.on("error", function (file, message) {
                    Swal.fire("Error", message, "error");
                    setTimeout(() => this.removeFile(file), 1000);
                });
            }
        });

    } else {
        console.error("Dropzone no encontró #uploadZona");
    }

}, 300);



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

function guardar(e) {
    e.preventDefault(); // Evitar recarga 

    let permisoID = getURLParameter('id');

    var formData = new FormData($("#form_detalle_rrhh")[0]);
    formData.append("permiso_id", permisoID); // ← AQUÍ LO ENVÍAS

    $.ajax({
        url: "../../controller/permiso.php?op=updateRecursos",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        beforeSend: function () {
            Swal.fire({
                title: "Guardando...",
                text: "Por favor espere un momento",
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        },
        success: function (response) {
            Swal.close(); // cerrar la animación de carga

            console.log("Respuesta del servidor:", response);
            var data = JSON.parse(response);

            if (data.success) {
                Swal.fire({
                    icon: "success",
                    title: "Datos actualizados",
                    text: "Los datos del permiso se guardaron correctamente.",
                    confirmButtonColor: "#28a745"
                }).then(() => {
                    setTimeout(function () {
                        window.location.href = "../MntInboxT/inbox.php"; // <-- RUTA REAL DE TU INBOX RRHH
                    }, 500);
                });

            } else {
                Swal.fire({
                    icon: "error",
                    title: "Error al guardar",
                    text: data.error || "No se pudo guardar los datos",
                    confirmButtonColor: "#dc3545"
                });
            }
        },
        error: function (xhr, status, error) {
            Swal.close();
            Swal.fire({
                icon: "error",
                title: "Error AJAX",
                text: "Hubo un problema al enviar la solicitud.",
                footer: error,
                confirmButtonColor: "#dc3545"
            });
        }
    });

}




init();

