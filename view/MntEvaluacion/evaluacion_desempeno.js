$(document).ready(function () {
    $(".select2").select2({
        theme: "bootstrap4",
        width: "100%"
    });

    cargarEmpleados();
    //cargarPeriodos();
    cargarPreguntasDesempeno();
});

$("#tipo_evaluacion").on("change", function () {
    let tipo = $(this).val();

    if (tipo === "AUTOEVALUACION") {
        $("#grupo_empleado").hide();
        $("#empleado_id").val($("#empleado_logueado_id").val()).trigger("change");
        $("#empleado_id").prop("required", false);
    } else {
        $("#grupo_empleado").show();
        $("#empleado_id").val("").trigger("change");
        $("#empleado_id").prop("required", true);
    }
});

function cargarEmpleados() {
    $.post("../../controller/empleado.php?op=comboRol", function (data) {
        $("#empleado_id").html('<option value="">Seleccione</option>' + data);
    });
}

/* function cargarPeriodos() {
    $.post("../../controller/evaluacion.php?op=comboPeriodos", function (data) {
        $("#anio").html('<option value="">Seleccione</option>' + data);
    });
} */

function cargarPreguntasDesempeno() {
    $.ajax({
        url: "../../controller/evaluacion.php?op=listarPreguntasDesempeno",
        type: "POST",
        dataType: "json",
        success: function (response) {
            if (response.status === "success") {
                pintarPreguntas(response.data);
            } else {
                Swal.fire("Atención", "No fue posible cargar las preguntas.", "warning");
            }
        },
        error: function () {
            Swal.fire("Error", "Error al consultar las preguntas.", "error");
        }
    });
}

function pintarPreguntas(preguntas) {
    let html = "";
    let bloqueActual = "";

    preguntas.forEach(function (item, index) {
        if (bloqueActual !== item.evpr_bloque) {
            if (index !== 0) {
                html += `</div></div>`;
            }

            bloqueActual = item.evpr_bloque;

            html += `
                <div class="card card-outline card-info mt-3">
                    <div class="card-header">
                        <h3 class="card-title">${bloqueActual}</h3>
                    </div>
                    <div class="card-body">
            `;
        }

        html += `
            <div class="form-group border-bottom pb-3">
                <label>${item.evpr_orden}. ${item.evpr_pregunta}</label>
                <select
                    class="form-control select2 pregunta-evaluacion"
                    data-pregunta-id="${item.evpr_id}"
                    data-bloque="${item.evpr_bloque}"
                    data-numero-pregunta="${item.evpr_orden}"
                    data-pregunta="${item.evpr_pregunta}"
                    required>
                    <option value="">Seleccione una calificación</option>
                    <option value="5">5 - Excelente</option>
                    <option value="4">4 - Bueno</option>
                    <option value="3">3 - Aceptable</option>
                    <option value="2">2 - Bajo</option>
                    <option value="1">1 - Crítico</option>
                </select>
            </div>
        `;
    });

    if (preguntas.length > 0) {
        html += `</div></div>`;
    }

    $("#contenedor_preguntas").html(html);

    $(".pregunta-evaluacion").select2({
        theme: "bootstrap4",
        width: "100%"
    });
}

$("#form_evaluacion_desempeno").on("submit", function (e) {
    e.preventDefault();

    let tipo = $("#tipo_evaluacion").val();
    let empleadoId = $("#empleado_id").val();
    let anio = $("#anio").val();

    if (!tipo || !anio) {
        Swal.fire("Campos incompletos", "Seleccione tipo de evaluación y periodo.", "warning");
        return;
    }

    if (tipo !== "AUTOEVALUACION" && !empleadoId) {
        Swal.fire("Campos incompletos", "Seleccione el empleado evaluado.", "warning");
        return;
    }

    if (tipo === "AUTOEVALUACION") {
        empleadoId = $("#empleado_logueado_id").val();
    }

    let respuestas = [];
    let incompleto = false;

    $(".pregunta-evaluacion").each(function () {
        let calificacion = $(this).val();

        if (!calificacion) {
            incompleto = true;
            $(this)
                .next(".select2-container")
                .find(".select2-selection")
                .addClass("is-invalid");
        } else {
            $(this)
                .next(".select2-container")
                .find(".select2-selection")
                .removeClass("is-invalid");

            respuestas.push({
                pregunta_id: $(this).data("pregunta-id"),
                bloque: $(this).data("bloque"),
                numero_pregunta: $(this).data("numero-pregunta"),
                pregunta: $(this).data("pregunta"),
                calificacion: calificacion
            });
        }
    });

    if (incompleto) {
        Swal.fire("Preguntas incompletas", "Debe calificar todas las preguntas.", "warning");
        return;
    }

    Swal.fire({
        title: "Finalizar evaluación",
        text: "¿Confirma que desea finalizar la evaluación?",
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#009BA9",
        cancelButtonColor: "#d33",
        confirmButtonText: "Sí, finalizar",
        cancelButtonText: "Cancelar"
    }).then((result) => {
        if (result.isConfirmed) {
            guardarEvaluacionDesempeno(empleadoId, tipo, anio, respuestas);
        }
    });
});

function guardarEvaluacionDesempeno(empleadoId, tipo, anio, respuestas) {
    $.ajax({
        url: "../../controller/evaluacion.php?op=guardarEvaluacionDesempeno",
        type: "POST",
        dataType: "json",
        data: {
            empleado_id: empleadoId,
            tipo_evaluacion: tipo,
            anio: anio,
            respuestas: JSON.stringify(respuestas)
        },
        success: function (response) {
            if (response.status === "success") {
                Swal.fire({
                    icon: "success",
                    title: "Evaluación culminada",
                    text: "La evaluación se ha culminado exitosamente.",
                    confirmButtonColor: "#009BA9"
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire("Atención", response.message, response.status);
            }
        },
        error: function () {
            Swal.fire("Error", "No fue posible guardar la evaluación.", "error");
        }
    });
}