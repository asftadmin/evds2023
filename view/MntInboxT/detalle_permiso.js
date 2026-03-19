function init() {

    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%'
    });

    $("#form_detalle_rrhh").on("submit", function (e) {
        guardar(e);
    });


}

$('#btnVolver').on('click', function () {
    window.location.href = '../MntInboxT/inbox.php';
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

// ── Toggle turno nocturno ──────────────────────────────
$('#chk_turno_nocturno').on('change', function () {
    if ($(this).is(':checked')) {
        //$('#bloque_fecha_cierre').show();

        // Sugerir automáticamente el día siguiente
        const fechaPermiso = $('#permiso_fecha').val();
        if (fechaPermiso) {
            const siguiente = new Date(fechaPermiso);
            siguiente.setDate(siguiente.getDate() + 1);
            const yyyy = siguiente.getFullYear();
            const mm = String(siguiente.getMonth() + 1).padStart(2, '0');
            const dd = String(siguiente.getDate()).padStart(2, '0');
            $('#permiso_fecha_cierre').val(`${yyyy}-${mm}-${dd}`);
        }
    } else {
        //$('#bloque_fecha_cierre').hide();
        $('#permiso_fecha_cierre').val('');
    }
    calcularHorasAusentesJornada();
});


$(document).ready(function () {

    var permisoID = getURLParameter('id');

    $.ajax({
        url: '../../controller/permiso.php?op=detallePermiso',
        type: 'GET',
        data: { id: permisoID },
        success: function (response) {

            //console.log(response);

            const detalle = JSON.parse(response);



            if (detalle.status === 'error') {
                Swal.fire('Error', detalle.message, 'error');
                return;
            }

            trabajaSabado = detalle.trabaja_sabado ?? 0;

            // Insertar formulario
            $("#detallePermiso").html(detalle.html);

            cargarSoportes(permisoID);

            calcularHorasAusentesJornada();


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



/* function cargarSoportes(permiso_id) {

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
} */

function cargarSoportes(permiso_id) {
    $.ajax({
        url: BASE_URL + "/controller/permiso.php?op=listarSoportes",
        type: "POST",
        data: { permiso_id: permiso_id },
        success: function (response) {
            let lista = JSON.parse(response);
            let html = "";

            if (lista.length === 0) {
                html = `<li class="list-group-item text-muted text-center">
                            <i class="fas fa-folder-open mr-1"></i>
                            Sin soportes adjuntos
                        </li>`;
                $("#listaSoportes").html(html);
                return;
            }

            lista.forEach(s => {
                const ext = s.soporte_nombre.split('.').pop().toLowerCase();
                const icono = getIconoArchivo(ext);

                // ── URL preview — inline (sin &download) ──
                const urlPreview = BASE_URL + "/controller/permiso.php?op=descargarSoporte&file=" 
                    + encodeURIComponent(s.soporte_ruta.trim());

                // ── URL descarga — fuerza attachment ──
                const urlDescarga = BASE_URL + "/controller/permiso.php?op=descargarSoporte&file="
                    + encodeURIComponent(s.soporte_ruta) + "&download=1";

                // URL para Word usa Google Docs Viewer
                let urlViewer = urlPreview;
                if (['doc', 'docx'].includes(ext)) {
                    urlViewer = 'https://docs.google.com/viewer?url='
                        + encodeURIComponent(urlPreview) + '&embedded=false';
                }

                html += `
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <i class="${icono} mr-2" style="font-size:18px;"></i>
                                    <div>
                                        <div style="font-size:13px; font-weight:600;">
                                            ${s.soporte_nombre}
                                        </div>
                                        <small class="text-muted">
                                            <i class="fas fa-clock mr-1"></i>
                                            ${s.soporte_fecha}
                                        </small>
                                    </div>
                                </div>
                                <div class="d-flex" style="gap:6px;">
                                    <button type="button" class="btn btn-sm btn-info"
                                            onclick="previsualizarSoporte('${urlViewer}', '${ext}', '${s.soporte_nombre}')"
                                            title="Vista previa">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-secondary"
                                            onclick="imprimirSoporte('${urlPreview}', '${ext}')"
                                            title="Imprimir">
                                        <i class="fas fa-print"></i>
                                    </button>
                                    <a href="${urlDescarga}" target="_blank"
                                    class="btn btn-sm btn-success" title="Descargar">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </div>
                            </div>
                        </li>`;
            });

            $("#listaSoportes").html(html);
        }
    });
}

// ── Ícono según extensión ──────────────────────────────
function getIconoArchivo(ext) {
    const iconos = {
        'pdf': 'fas fa-file-pdf text-danger',
        'jpg': 'fas fa-file-image text-info',
        'jpeg': 'fas fa-file-image text-info',
        'png': 'fas fa-file-image text-info',
        'doc': 'fas fa-file-word text-primary',
        'docx': 'fas fa-file-word text-primary',
    };
    return iconos[ext] ?? 'fas fa-file text-secondary';
}

// ── Vista previa en nueva pestaña ─────────────────────
function previsualizarSoporte(url, ext, nombre) {
    if (!url) {
        Swal.fire('Aviso', 'No se puede previsualizar este tipo de archivo.', 'info');
        return;
    }
    window.open(url, '_blank');
}

// ── Imprimir ───────────────────────────────────────────
function imprimirSoporte(url, ext) {
    if (!url) {
        Swal.fire('Aviso', 'No se puede imprimir este tipo de archivo.', 'info');
        return;
    }

    if (['jpg', 'jpeg', 'png'].includes(ext)) {
        // Imprimir imagen — abrir en ventana e imprimir
        const ventana = window.open('', '_blank');
        ventana.document.write(`
            <html>
                <head>
                    <title>Imprimir soporte</title>
                    <style>
                        body { margin: 0; display: flex; justify-content: center; }
                        img  { max-width: 100%; height: auto; }
                    </style>
                </head>
                <body>
                    <img src="${url}" onload="window.print(); window.close();">
                </body>
            </html>
        `);
        ventana.document.close();

    } else if (ext === 'pdf') {
        // PDF — abrir en nueva pestaña y el usuario imprime desde ahí
        const ventana = window.open(url, '_blank');
        ventana.onload = function () {
            ventana.print();
        };

    } else {
        // Word — abrir Google Docs Viewer
        window.open(url, '_blank');
    }
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
                console.log(response);
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


function parseYMD(ymd) {
    const [y, m, d] = ymd.split("-").map(n => parseInt(n, 10));
    return new Date(y, m - 1, d);
}

function timeToMinutes(t) {
    const parts = (t || "").split(":");
    const h = parseInt(parts[0] || "0", 10);
    const m = parseInt(parts[1] || "0", 10);
    return h * 60 + m;
}

function sameDay(a, b) {
    return a.getFullYear() === b.getFullYear()
        && a.getMonth() === b.getMonth()
        && a.getDate() === b.getDate();
}

function addDays(date, n) {
    const d = new Date(date);
    d.setDate(d.getDate() + n);
    return d;
}

// ── Variable global — se asigna al cargar el empleado ──
let trabajaSabado = 0;

let esNocturno = false;

function getWorkIntervalsMinutes(dateObj) {
    const dow = dateObj.getDay(); // 0=dom, 6=sab

    // Domingo — nunca cuenta
    if (dow === 0) return [];

    // Sábado — solo si el empleado trabaja sábado
    if (dow === 6) {
        if (trabajaSabado === 1) {
            return [[8 * 60, 12 * 60]]; // 08:00 - 12:00
        } else {
            return []; // no trabaja sábado
        }
    }

    // ── Turno nocturno: 18:00 - 06:00 del día siguiente ──
    if (esNocturno) {
        return [
            [18 * 60, 24 * 60], // 18:00 - 24:00 del día actual
            [0, 6 * 60], // 00:00 - 06:00 del día siguiente
        ];
    }

    // Lunes a Jueves → hasta 18:00
    // Viernes → hasta 17:00
    const endAfternoon = (dow === 5) ? (17 * 60) : (18 * 60);

    return [
        [8 * 60, 12 * 60],      // mañana  08:00 - 12:00
        [13 * 60, endAfternoon], // tarde   13:00 - 18:00 (o 17:00 viernes)
    ];
}

function overlapMinutes(aStart, aEnd, bStart, bEnd) {
    const start = Math.max(aStart, bStart);
    const end = Math.min(aEnd, bEnd);
    return Math.max(0, end - start);
}

function calcularHorasAusentesJornada() {

    esNocturno = $('#chk_turno_nocturno').is(':checked');

    const fechaPermiso = $("#permiso_fecha").val();
    const horaSalida = $("#permiso_hora_salida").val();

    // Fecha cierre: si no existe o viene vacía, usa fecha permiso
    const fechaCierre = $("#permiso_fecha_cierre").val() || fechaPermiso;

    // Hora cierre: prioriza biotime si lo usas, si no, usa hora entrada del permiso
    const horaCierre = $("#permiso_hora_entrada").val();


    if (!fechaPermiso || !horaSalida || !fechaCierre || !horaCierre) {
        $("#permiso_total_horas").val("");
        return;
    }

    const startDate = parseYMD(fechaPermiso);
    const endDate = parseYMD(fechaCierre);

    if (endDate < startDate) {
        $("#permiso_total_horas").val("0.00");
        return;
    }

    const startMin = timeToMinutes(horaSalida);
    const endMin = timeToMinutes(horaCierre);

    let totalMinutes = 0;

    for (let d = new Date(startDate); d <= endDate; d = addDays(d, 1)) {
        const intervals = getWorkIntervalsMinutes(d);
        if (intervals.length === 0) continue;

        let dayStart = 0;
        let dayEnd = 24 * 60;

        if (sameDay(d, startDate)) dayStart = startMin;
        if (sameDay(d, endDate)) dayEnd = endMin;

        // Si es el mismo día y cierre <= salida => 0
        if (sameDay(d, startDate) && sameDay(d, endDate) && dayEnd <= dayStart) {
            continue;
        }

        for (const [wStart, wEnd] of intervals) {
            totalMinutes += overlapMinutes(dayStart, dayEnd, wStart, wEnd);
        }
    }

    $("#permiso_total_horas").val((totalMinutes / 60).toFixed(2));
}

// Cuando cambian las horas → actualizar cálculo
$(document).on(
    "input change",
    "#permiso_fecha, #permiso_hora_salida, #permiso_fecha_cierre, #permiso_hora_entrada, #chk_turno_nocturno",
    function () {
        calcularHorasAusentesJornada();
    }
);

//funcion cargar incapacidades

function cargarComboIncapacidades() {
    $.post("../../controller/incapacidad.php?op=comboIncapacidades", function (html) {
        $("#incapacidad_id").html(html);

        // Seleccionar el valor de BD si existe
        let valorBd = $("#incapacidad_id").attr("data-valorbd");
        if (valorBd) {
            $("#incapacidad_id").val(valorBd).trigger("change");
        }
    }).fail(function (xhr) {
        console.log(xhr.responseText);
        Swal.fire("Error", "No se pudo cargar la lista de incapacidades.", "error");
    });
}


$(document).on("change", "#permiso_motivo", function () {
    const motivo = $(this).val();

    if (motivo == '3') {
        $("#bloqueIncapacidad").show();
        cargarComboIncapacidades();
    } else {
        $("#bloqueIncapacidad").hide();
        $("#incapacidad_id").val("").trigger("change");
    }
});







init();

