$(document).ready(function () {
    inicializarPeriodo();
    inicializarEmpleados();
});

/**
 * Configura el Select2 de periodos y carga los anos registrados en el nuevo modulo.
 * No recibe parametros y no devuelve valor.
 */
function inicializarPeriodo() {
    $("#periodo").select2({
        theme: "bootstrap4",
        width: "100%",
        placeholder: "Seleccione un periodo",
        allowClear: true
    });

    // La fuente es evaluacion_desempeno.evde_anio; no se agrega el ano actual en el navegador.
    $.ajax({
        url: "../../controller/evaluacion.php?op=listarPeriodosReporteDesempeno",
        type: "GET",
        dataType: "json"
    }).done(function (respuesta) {
        if (respuesta.status !== "success") {
            mostrarErrorPeriodos();
            return;
        }

        respuesta.data.forEach(function (item) {
            $("#periodo").append(new Option(item.periodo, item.periodo, false, false));
        });

        if (respuesta.data.length === 0) {
            Swal.fire("Sin periodos", "Todavía no hay periodos con evaluaciones de desempeño registradas.", "info");
        }
    }).fail(mostrarErrorPeriodos);
}

/**
 * Informa que la consulta AJAX de periodos no pudo completarse.
 * No recibe parametros y no devuelve valor.
 */
function mostrarErrorPeriodos() {
    Swal.fire("Error", "No fue posible cargar los periodos de evaluación.", "error");
}

/**
 * Configura el Select2 AJAX que busca exclusivamente empleados activos.
 * No recibe parametros y no devuelve valor.
 */
function inicializarEmpleados() {
    $("#empleado_id").select2({
        theme: "bootstrap4",
        width: "100%",
        placeholder: "Busque por documento o nombre",
        allowClear: true,
        minimumInputLength: 0,
        ajax: {
            url: "../../controller/evaluacion.php?op=listarEmpleadosActivosReporte",
            dataType: "json",
            delay: 250,
            data: function (params) {
                return { q: params.term || "" };
            },
            processResults: function (respuesta) {
                return respuesta;
            },
            cache: true
        }
    });
}

/**
 * Oculta los datos previamente consultados y obliga a ejecutar una nueva busqueda.
 * No recibe parametros y no devuelve valor.
 */
function limpiarResultado() {
    $("#resultado_reporte").hide();
    $("#btn_pdf").prop("disabled", true).removeData("empleado-id").removeData("periodo");
    $("#resultado_tipos").empty();
}

// Cualquier cambio de filtro invalida el resultado visible y los parametros autorizados para el PDF.
$("#periodo, #empleado_id").on("change", limpiarResultado);

// Valida los filtros y consulta por AJAX la existencia de evaluaciones del empleado y periodo exactos.
$("#btn_buscar").on("click", function () {
    var empleadoId = $("#empleado_id").val();
    var periodo = $("#periodo").val();

    limpiarResultado();

    if (!periodo || !empleadoId) {
        Swal.fire("Campos incompletos", "Seleccione el periodo y el empleado.", "warning");
        return;
    }

    $.ajax({
        url: "../../controller/evaluacion.php?op=consultarReporteDesempeno",
        type: "POST",
        dataType: "json",
        data: {
            empleado_id: empleadoId,
            periodo: periodo
        }
    }).done(function (respuesta) {
        if (respuesta.status === "empty") {
            Swal.fire("Sin evaluaciones", respuesta.message, "info");
            return;
        }

        if (respuesta.status !== "success") {
            Swal.fire("Atención", respuesta.message || "No fue posible realizar la consulta.", "warning");
            return;
        }

        mostrarResultado(respuesta.data, empleadoId, periodo);
    }).fail(function (xhr) {
        var mensaje = xhr.responseJSON && xhr.responseJSON.message
            ? xhr.responseJSON.message
            : "No fue posible consultar las evaluaciones.";
        Swal.fire("Error", mensaje, "error");
    });
});

/**
 * Presenta la ficha validada y conserva solo el id y periodo necesarios para generar el PDF.
 * Recibe los datos del servidor, el id del empleado y el periodo; no devuelve valor.
 */
function mostrarResultado(datos, empleadoId, periodo) {
    $("#resultado_documento").text(datos.cedu_empl || "No registrado");
    $("#resultado_nombre").text(datos.nomb_empl || "No registrado");
    $("#resultado_cargo").text(datos.nomb_carg || "No registrado");
    $("#resultado_periodo").text(datos.periodo);

    datos.tipos_evaluacion.forEach(function (tipo) {
        $("<span>", {
            "class": "badge badge-info tipo-evaluacion",
            text: formatearTipo(tipo)
        }).appendTo("#resultado_tipos");
    });

    $("#btn_pdf")
        .prop("disabled", false)
        .data("empleado-id", empleadoId)
        .data("periodo", periodo);
    $("#resultado_reporte").show();
}

/**
 * Convierte el codigo almacenado del tipo en una etiqueta legible.
 * Recibe el codigo de tipo y devuelve su texto para presentacion.
 */
function formatearTipo(tipo) {
    var etiquetas = {
        AUTOEVALUACION: "Autoevaluación",
        COEVALUACION: "Coevaluación",
        SUBEVALUACION: "Subevaluación"
    };
    return etiquetas[tipo] || tipo;
}

// Abre una nueva pestana enviando unicamente los dos filtros; el PDF vuelve a consultar todos los datos.
$("#btn_pdf").on("click", function () {
    if ($(this).prop("disabled")) {
        return;
    }

    var empleadoId = $(this).data("empleado-id");
    var periodo = $(this).data("periodo");
    var url = "../PDF/evaluacion_desempeno_pdf.php?empleado_id="
        + encodeURIComponent(empleadoId)
        + "&periodo="
        + encodeURIComponent(periodo);

    window.open(url, "_blank", "noopener");
});
