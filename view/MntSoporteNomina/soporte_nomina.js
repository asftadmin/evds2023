var tablaDetalle;
var tablaInconsistencias;

$(document).ready(function () {

    // Inicializa los Select2 de periodo.
    $(".select2").select2({
        theme: "bootstrap4",
        width: "100%"
    });

    // Inicializa la tabla principal de Contabilidad.
    tablaDetalle = $("#tabla_detalle").DataTable({
        responsive: true,
        autoWidth: false,
        ordering: false,
        language: {
            url: "../../public/plugins/datatables/Spanish.json"
        },
        columnDefs: [
            {
                targets: 0,
                orderable: false,
                searchable: false,
                className: "text-center align-middle",
                width: "40px"
            }
        ]
    });

    // Inicializa la tabla de inconsistencias.
    tablaInconsistencias = $("#tabla_inconsistencias").DataTable({
        responsive: true,
        autoWidth: false,
        ordering: false,
        language: {
            url: "../../public/plugins/datatables/Spanish.json"
        }
    });

    // Muestra en el input el nombre del archivo seleccionado.
    $("#archivo_auxilio").on("change", function () {

        var nombreArchivo = $(this)
            .val()
            .split("\\")
            .pop();

        $(this)
            .next(".custom-file-label")
            .html(nombreArchivo || "Seleccionar archivo Excel");
    });

    // Selecciona o deselecciona todos los registros disponibles.
    $("#check_todos").on("change", function () {

        var seleccionado = $(this).is(":checked");

        $(".check-procesar:not(:disabled)")
            .prop("checked", seleccionado);

        actualizarBotonProcesar();
    });

    // Controla el botón de procesamiento cuando cambia un registro.
    $("#tabla_detalle").on("change", ".check-procesar", function () {

        actualizarBotonProcesar();

        var total = $(".check-procesar:not(:disabled)").length;
        var seleccionados = $(".check-procesar:not(:disabled):checked").length;

        $("#check_todos").prop(
            "checked",
            total > 0 && total === seleccionados
        );
    });

    // Valida el archivo cargado.
    $("#btn_validar_archivo").on("click", function () {
        validarArchivo();
    });

    // Procesa únicamente los registros seleccionados.
    $("#btn_procesar").on("click", function () {
        procesarSeleccionados();
    });
});


// Valida los datos mínimos antes de enviar el archivo.
function validarArchivo() {

    var mes = $("#periodo_mes").val();
    var anio = $("#periodo_anio").val();
    var archivo = $("#archivo_auxilio")[0].files[0];

    if (mes === "" || anio === "") {

        Swal.fire({
            icon: "warning",
            title: "Periodo requerido",
            text: "Seleccione el mes y el año que desea procesar."
        });

        return;
    }

    if (!archivo) {

        Swal.fire({
            icon: "warning",
            title: "Archivo requerido",
            text: "Seleccione el archivo de auxilios."
        });

        return;
    }

    var formData = new FormData();

    formData.append("periodo_mes", mes);
    formData.append("periodo_anio", anio);
    formData.append("archivo_auxilio", archivo);

    $("#btn_validar_archivo")
        .prop("disabled", true)
        .html(
            '<i class="fas fa-spinner fa-spin mr-1"></i> Validando...'
        );

    $.ajax({
        url: "../../controller/soporte_nomina.php?op=validar_archivo",
        type: "POST",
        data: formData,
        dataType: "json",
        contentType: false,
        processData: false,

        success: function (response) {

            if (!response.success) {

                Swal.fire({
                    icon: "error",
                    title: "No fue posible validar",
                    text: response.mensaje
                });

                return;
            }

            cargarInconsistencias(
                response.inconsistencias || []
            );

            cargarDetalle(
                response.registros || []
            );

            Swal.fire({
                icon: "success",
                title: "Archivo validado",
                text: "La información fue validada correctamente."
            });
        },

        error: function () {

            Swal.fire({
                icon: "error",
                title: "Error",
                text: "No fue posible validar el archivo."
            });
        },

        complete: function () {

            $("#btn_validar_archivo")
                .prop("disabled", false)
                .html(
                    '<i class="fas fa-search mr-1"></i> Validar archivo'
                );
        }
    });
}


// Muestra los empleados cuya cédula no coincide con cedu_empl.
function cargarInconsistencias(registros) {

    tablaInconsistencias.clear();

    if (registros.length === 0) {

        tablaInconsistencias.draw();

        $("#card_inconsistencias").hide();

        return;
    }

    registros.forEach(function (registro) {

        tablaInconsistencias.row.add([
            registro.cedula,
            registro.empleado,
            registro.observacion
        ]);
    });

    tablaInconsistencias.draw();

    $("#card_inconsistencias").show();
}


// Carga el resultado disponible para revisión de Contabilidad.
function cargarDetalle(registros) {

    tablaDetalle.clear();

    registros.forEach(function (registro) {

        var contabilizado =
            registro.estado === "CONTABILIZADO";

        var checkbox =
            '<input type="checkbox" ' +
            'class="check-procesar" ' +
            'data-id="' + registro.id + '" ' +
            (contabilizado ? "disabled" : "") +
            '>';

        var estado = contabilizado
            ? '<span class="badge badge-success">Contabilizado</span>'
            : '<span class="badge badge-warning">Borrador</span>';

        tablaDetalle.row.add([
            checkbox,
            registro.empleado,
            formatoMoneda(registro.total_auxilio),
            formatoNumero(registro.dias_alimentacion),
            formatoNumero(registro.dias_hospedaje),
            formatoMoneda(registro.valor_alimentacion),
            formatoMoneda(registro.valor_hospedaje),
            formatoMoneda(registro.otros),
            estado
        ]);
    });

    tablaDetalle.draw();

    $("#check_todos").prop("checked", false);

    actualizarBotonProcesar();

    if (registros.length > 0) {
        $("#card_detalle").show();
    } else {
        $("#card_detalle").hide();
    }
}


// Obtiene los IDs marcados por Contabilidad.
function obtenerSeleccionados() {

    var registros = [];

    $(".check-procesar:checked").each(function () {
        registros.push($(this).data("id"));
    });

    return registros;
}


// Envía los registros seleccionados para pasar de borrador a contabilizado.
function procesarSeleccionados() {

    var registros = obtenerSeleccionados();

    if (registros.length === 0) {

        Swal.fire({
            icon: "warning",
            title: "Sin registros",
            text: "Seleccione al menos un registro para procesar."
        });

        return;
    }

    Swal.fire({
        title: "¿Procesar registros?",
        text: "Los registros seleccionados serán contabilizados.",
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Sí, procesar",
        cancelButtonText: "Cancelar"
    }).then(function (result) {

        if (!result.isConfirmed) {
            return;
        }

        $.ajax({
            url: "../../controller/soporte_nomina.php?op=procesar",
            type: "POST",
            dataType: "json",
            data: {
                registros: registros
            },

            success: function (response) {

                if (!response.success) {

                    Swal.fire({
                        icon: "error",
                        title: "No fue posible procesar",
                        text: response.mensaje
                    });

                    return;
                }

                Swal.fire({
                    icon: "success",
                    title: "Procesado",
                    text: response.mensaje
                });

                // Más adelante recargaremos el periodo desde el controller.
            },

            error: function () {

                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "No fue posible procesar los registros."
                });
            }
        });
    });
}


// Habilita el botón solo cuando existen registros marcados.
function actualizarBotonProcesar() {

    var cantidad =
        $(".check-procesar:checked").length;

    $("#btn_procesar").prop(
        "disabled",
        cantidad === 0
    );
}


// Formatea los valores monetarios para la vista.
function formatoMoneda(valor) {

    valor = parseFloat(valor || 0);

    return valor.toLocaleString("es-CO", {
        style: "currency",
        currency: "COP",
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    });
}


// Formatea días sin eliminar decimales necesarios.
function formatoNumero(valor) {

    valor = parseFloat(valor || 0);

    return valor.toLocaleString("es-CO", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    });
}