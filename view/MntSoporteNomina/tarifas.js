var tablaTarifas;

$(document).ready(function () {
    // Evita envíos nativos del formulario al presionar Enter.
    $("#form_tarifas").on("submit", function (event) {
        event.preventDefault();
        guardarTarifa();
    });

    // Inicializa los Select2 del formulario.
    $(".select2").select2({
        theme: "bootstrap4",
        width: "100%"
    });

    // Inicializa el histórico de tarifas.
    tablaTarifas = $("#tabla_tarifas").DataTable({
        processing: true,
        responsive: true,
        autoWidth: false,
        order: [[0, "desc"]],

        ajax: {
            url: "../../controller/soporte_nomina_tarifas.php?op=listar",
            type: "GET",
            dataSrc: function (response) {

                if (!response.success) {

                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: response.mensaje
                    });

                    return [];
                }

                return response.data || [];
            },

            error: function () {

                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "No fue posible consultar las tarifas configuradas."
                });
            }
        },

        columns: [
            {
                data: null,
                render: function (data, type, row) {
                    if (type === 'sort' || type === 'type') {
                        return Number(row.tarifa_anio) * 100 + Number(row.tarifa_mes);
                    }
                    return obtenerNombreMes(row.tarifa_mes) + " " + row.tarifa_anio;
                }
            },
            {
                data: "tarifa_alimentacion",
                render: function (data) {
                    return formatoMoneda(data);
                }
            },
            {
                data: "tarifa_hospedaje",
                render: function (data) {
                    return formatoMoneda(data);
                }
            },
            {
                data: "tarifa_estado",
                render: function (data) {

                    if (parseInt(data) === 1) {
                        return '<span class="badge badge-success">Activo</span>';
                    }

                    return '<span class="badge badge-secondary">Inactivo</span>';
                }
            },
            {
                data: "tarifa_id",
                orderable: false,
                searchable: false,
                className: "text-center",
                render: function (data) {

                    return `
                        <button
                            type="button"
                            class="btn btn-warning btn-sm btn-editar"
                            data-id="${data}"
                            title="Editar tarifa">

                            <i class="fas fa-edit"></i>
                        </button>
                    `;
                }
            }
        ],

        language: {
            url: "../../public/plugins/datatables/Spanish.json"
        }
    });


    // Formatea visualmente la tarifa de alimentación.
    $("#tarifa_alimentacion").on("input", function () {
        formatearInputMoneda(this);
    });


    // Formatea visualmente la tarifa de hospedaje.
    $("#tarifa_hospedaje").on("input", function () {
        formatearInputMoneda(this);
    });


    // Guarda o actualiza la configuración.
    $("#btn_guardar").on("click", function () {
        guardarTarifa();
    });


    // Limpia el formulario.
    $("#btn_limpiar").on("click", function () {
        limpiarFormulario();
    });


    // Carga la tarifa seleccionada para edición.
    $("#tabla_tarifas").on("click", ".btn-editar", function () {

        var tarifaId = $(this).data("id");

        editarTarifa(tarifaId);
    });
});


// Guarda una nueva tarifa o actualiza una existente.
function guardarTarifa() {
    if ($("#btn_guardar").prop("disabled")) { return; }

    var tarifaId = $("#tarifa_id").val();
    var mes = $("#tarifa_mes").val();
    var anio = $("#tarifa_anio").val();

    var alimentacion = obtenerValorMoneda(
        $("#tarifa_alimentacion").val()
    );

    var hospedaje = obtenerValorMoneda(
        $("#tarifa_hospedaje").val()
    );


    // Valida que el periodo esté completo.
    if (mes === "" || anio === "") {

        Swal.fire({
            icon: "warning",
            title: "Periodo requerido",
            text: "Seleccione el mes y el año de la tarifa."
        });

        return;
    }


    // Valida la tarifa de alimentación.
    if (alimentacion <= 0) {

        Swal.fire({
            icon: "warning",
            title: "Tarifa inválida",
            text: "Ingrese una tarifa de alimentación mayor a cero."
        });

        return;
    }


    // Valida la tarifa de hospedaje.
    if (hospedaje <= 0) {

        Swal.fire({
            icon: "warning",
            title: "Tarifa inválida",
            text: "Ingrese una tarifa de hospedaje mayor a cero."
        });

        return;
    }


    var accion = tarifaId === ""
        ? "guardar"
        : "actualizar";


    var mensajeConfirmacion = tarifaId === ""
        ? "¿Desea guardar esta configuración de tarifas?"
        : "¿Desea actualizar esta configuración de tarifas?";


    Swal.fire({
        title: "Confirmar configuración",
        text: mensajeConfirmacion,
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Sí, continuar",
        cancelButtonText: "Cancelar"
    }).then(function (result) {

        if (!result.isConfirmed) {
            return;
        }


        $("#btn_guardar")
            .prop("disabled", true)
            .html(
                '<i class="fas fa-spinner fa-spin mr-1"></i> Guardando...'
            );


        $.ajax({
            url: "../../controller/soporte_nomina_tarifas.php?op=" + accion,
            type: "POST",
            dataType: "json",

            data: {
                csrf_token: $("#soporte_csrf").val(),
                tarifa_id: tarifaId,
                tarifa_mes: mes,
                tarifa_anio: anio,
                tarifa_alimentacion: alimentacion,
                tarifa_hospedaje: hospedaje
            },

            success: function (response) {

                if (!response.success) {

                    Swal.fire({
                        icon: "warning",
                        title: "No fue posible guardar",
                        text: response.mensaje
                    });

                    return;
                }


                Swal.fire({
                    icon: "success",
                    title: "Configuración guardada",
                    text: response.mensaje
                });


                limpiarFormulario();

                tablaTarifas.ajax.reload(null, false);
            },

            error: function (xhr) {

                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: xhr.responseJSON ? xhr.responseJSON.mensaje : "No fue posible guardar la configuración."
                });
            },

            complete: function () {

                $("#btn_guardar")
                    .prop("disabled", false)
                    .html(
                        '<i class="fas fa-save mr-1"></i> Guardar configuración'
                    );
            }
        });
    });
}


// Consulta una configuración para editarla.
function editarTarifa(tarifaId) {

    $.ajax({
        url: "../../controller/soporte_nomina_tarifas.php?op=mostrar",
        type: "POST",
        dataType: "json",

        data: {
            tarifa_id: tarifaId
        },

        success: function (response) {

            if (!response.success) {

                Swal.fire({
                    icon: "warning",
                    title: "No encontrado",
                    text: response.mensaje
                });

                return;
            }


            var tarifa = response.data;


            $("#tarifa_id").val(
                tarifa.tarifa_id
            );


            $("#tarifa_mes")
                .val(tarifa.tarifa_mes)
                .trigger("change");


            $("#tarifa_anio")
                .val(tarifa.tarifa_anio)
                .trigger("change");


            $("#tarifa_alimentacion").val(
                formatoNumeroInput(
                    tarifa.tarifa_alimentacion
                )
            );


            $("#tarifa_hospedaje").val(
                formatoNumeroInput(
                    tarifa.tarifa_hospedaje
                )
            );


            $("#btn_guardar").html(
                '<i class="fas fa-save mr-1"></i> Actualizar configuración'
            );


            $("html, body").animate({
                scrollTop: $("#form_tarifas").offset().top - 100
            }, 300);
        },

        error: function () {

            Swal.fire({
                icon: "error",
                title: "Error",
                text: "No fue posible consultar la configuración."
            });
        }
    });
}


// Limpia todos los campos del formulario.
function limpiarFormulario() {

    $("#tarifa_id").val("");

    $("#tarifa_mes")
        .val("")
        .trigger("change");

    $("#tarifa_anio")
        .val("")
        .trigger("change");

    $("#tarifa_alimentacion").val("");

    $("#tarifa_hospedaje").val("");


    $("#btn_guardar").html(
        '<i class="fas fa-save mr-1"></i> Guardar configuración'
    );
}


// Convierte el nombre del mes para mostrarlo en el histórico.
function obtenerNombreMes(mes) {

    var meses = {
        1: "Enero",
        2: "Febrero",
        3: "Marzo",
        4: "Abril",
        5: "Mayo",
        6: "Junio",
        7: "Julio",
        8: "Agosto",
        9: "Septiembre",
        10: "Octubre",
        11: "Noviembre",
        12: "Diciembre"
    };

    return meses[parseInt(mes)] || "";
}


// Formatea visualmente los inputs monetarios.
function formatearInputMoneda(input) {
    // Conserva el signo negativo para que la validación lo rechace.
    if (input.value.indexOf('-') !== -1) { return; }

    var partes = input.value.replace(/[^\d,]/g, "").split(",");
    var valor = partes[0];

    if (valor === "") {
        input.value = "";
        return;
    }

    input.value = parseInt(valor, 10).toLocaleString("es-CO") +
        (partes.length > 1 ? "," + partes[1].slice(0, 2) : "");
}


// Convierte un valor visual como 60.000 en 60000.
function obtenerValorMoneda(valor) {

    if (!valor) {
        return 0;
    }

    return parseFloat(
        valor
            .toString()
            .replace(/\./g, "")
            .replace(/,/g, ".")
            .replace(/[^\d.-]/g, "")
    ) || 0;
}


// Formatea un valor proveniente del servidor para un input.
function formatoNumeroInput(valor) {

    valor = parseFloat(valor || 0);

    return valor.toLocaleString("es-CO", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    });
}


// Formatea los valores monetarios del DataTable.
function formatoMoneda(valor) {

    valor = parseFloat(valor || 0);

    return valor.toLocaleString("es-CO", {
        style: "currency",
        currency: "COP",
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    });
}
