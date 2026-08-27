let tablaMenus;

function init() {
    $("#form_menu").on("submit", function (e) {
        guardarEditarMenu(e);
    });

    $("#btn_limpiar_menu, #btn_cancelar_edicion").on("click", function () {
        limpiarFormularioMenu();
    });

    // Los botones vienen desde DataTable. No se utiliza onclick en el PHP.
    $(document).on("click", ".btn-editar-menu", function () {
        editarMenu($(this).data("id"));
    });

    $(document).on("click", ".btn-estado-menu", function () {
        cambiarEstadoMenu(
            $(this).data("id"),
            $(this).data("estado")
        );
    });
}


$(document).ready(function () {

    // Inicializa los Select2 del formulario.
    $(".select2bs4").select2({
        theme: "bootstrap4"
    });

    cargarGruposMenu();

    // Carga los menús registrados desde el controlador.
    tablaMenus = $("#menus_data").DataTable({
        processing: true,
        serverSide: false,
        searching: true,
        lengthChange: false,
        responsive: true,
        autoWidth: false,
        pageLength: 10,

        ajax: {
            url: "../../controller/menu.php?op=listar_menus",
            type: "POST",
            dataType: "json",
            dataSrc: "aaData",

            error: function (e) {
                console.error(e.responseText);

                Swal.fire({
                    title: "Menús",
                    text: "No fue posible cargar los menús.",
                    icon: "error"
                });
            }
        },

        columnDefs: [
            {
                targets: [0, 5, 6, 7],
                className: "text-center"
            },
            {
                targets: 7,
                orderable: false,
                searchable: false
            }
        ],

        language: {
            processing: "Procesando...",
            search: "Buscar:",
            zeroRecords: "No se encontraron resultados",
            emptyTable: "No existen menús registrados",
            info: "Mostrando _START_ a _END_ de _TOTAL_ menús",
            infoEmpty: "No existen menús registrados",
            infoFiltered: "(filtrado de _MAX_ registros)",
            paginate: {
                first: "Primero",
                last: "Último",
                next: "Siguiente",
                previous: "Anterior"
            }
        }
    });
});


function cargarGruposMenu(grupoSeleccionado = "") {

    $.ajax({
        url: "../../controller/menu.php?op=listar_grupos",
        type: "POST",
        dataType: "json",

        success: function (data) {

            if (!data.success) {
                Swal.fire({
                    title: "Grupos",
                    text: data.message || "No fue posible cargar los grupos.",
                    icon: "warning"
                });
                return;
            }

            let opciones = '<option value="">Sin grupo</option>';

            $.each(data.data, function (index, grupo) {
                opciones += '<option value="' + grupo.id + '">'
                    + $("<div>").text(grupo.text).html()
                    + '</option>';
            });

            $("#menu_grupo").html(opciones);

            if (grupoSeleccionado !== "" && grupoSeleccionado !== null) {
                $("#menu_grupo")
                    .val(String(grupoSeleccionado))
                    .trigger("change");
            } else {
                $("#menu_grupo")
                    .val("")
                    .trigger("change");
            }
        },

        error: function (e) {
            console.error(e.responseText);

            Swal.fire({
                title: "Grupos",
                text: "No fue posible consultar los grupos del menú.",
                icon: "error"
            });
        }
    });
}


function guardarEditarMenu(e) {

    e.preventDefault();

    let nombre = $.trim($("#menu_nomb").val());
    let ruta = $.trim($("#menu_ruta").val());
    let identificador = $.trim($("#menu_ident").val());
    let icono = $.trim($("#menu_icon").val());
    let orden = $.trim($("#menu_orden").val());

    if (
        nombre === ""
        || ruta === ""
        || identificador === ""
        || icono === ""
        || orden === ""
    ) {
        Swal.fire({
            title: "Menú",
            text: "Debe completar los datos obligatorios.",
            icon: "warning"
        });

        return;
    }

    let formData = new FormData($("#form_menu")[0]);

    $("#btn_guardar_menu")
        .prop("disabled", true)
        .html('<i class="fas fa-spinner fa-spin mr-1"></i> Guardando...');

    $.ajax({
        url: "../../controller/menu.php?op=guardaryeditar",
        type: "POST",
        data: formData,
        dataType: "json",
        contentType: false,
        processData: false,

        success: function (data) {

            if (!data.success) {
                Swal.fire({
                    title: "Menú",
                    text: data.message,
                    icon: "warning"
                });

                return;
            }

            tablaMenus.ajax.reload(null, false);
            limpiarFormularioMenu();

            Swal.fire({
                title: "Menú",
                text: data.message,
                icon: "success"
            });
        },

        error: function (e) {
            console.error(e.responseText);

            Swal.fire({
                title: "Menú",
                text: "No fue posible guardar el menú.",
                icon: "error"
            });
        },

        complete: function () {
            actualizarBotonGuardar();
        }
    });
}


function editarMenu(menuId) {

    $.ajax({
        url: "../../controller/menu.php?op=mostrar",
        type: "POST",
        dataType: "json",
        data: {
            menu_id: menuId
        },

        success: function (data) {

            if (!data.success) {
                Swal.fire({
                    title: "Menú",
                    text: data.message,
                    icon: "warning"
                });

                return;
            }

            let menu = data.data;

            $("#menu_id").val(menu.menu_id);
            $("#menu_nomb").val(menu.menu_nomb);
            $("#menu_ruta").val(menu.menu_ruta);
            $("#menu_ident").val(menu.menu_ident);
            $("#menu_icon").val(menu.menu_icon);
            $("#menu_orden").val(menu.menu_orden);

            $("#menu_esta")
                .val(String(menu.menu_esta))
                .trigger("change");

            cargarGruposMenu(menu.menu_grupo);

            $("#contenedor_estado_menu").show();
            $("#btn_cancelar_edicion").show();

            $("#titulo_form_menu").html(
                '<h3 class="card-title">'
                + '<i class="fas fa-edit mr-2"></i>'
                + 'Editar Menú'
                + '</h3>'
            );

            $("#btn_guardar_menu").html(
                '<i class="fas fa-save mr-1"></i> Guardar Cambios'
            );

            $("html, body").animate({
                scrollTop: $("#form_menu").offset().top - 100
            }, 400);
        },

        error: function (e) {
            console.error(e.responseText);

            Swal.fire({
                title: "Menú",
                text: "No fue posible consultar el menú.",
                icon: "error"
            });
        }
    });
}


function cambiarEstadoMenu(menuId, estado) {

    estado = parseInt(estado, 10);

    let accion = estado === 1
        ? "activar"
        : "inactivar";

    Swal.fire({
        title: "Cambiar estado",
        text: "¿Desea " + accion + " este menú?",
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#009BA9",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Sí, " + accion,
        cancelButtonText: "Cancelar"

    }).then(function (result) {

        if (!result.isConfirmed) {
            return;
        }

        $.ajax({
            url: "../../controller/menu.php?op=cambiar_estado",
            type: "POST",
            dataType: "json",
            data: {
                menu_id: menuId,
                menu_esta: estado
            },

            success: function (data) {

                if (!data.success) {
                    Swal.fire({
                        title: "Menú",
                        text: data.message,
                        icon: "warning"
                    });

                    return;
                }

                tablaMenus.ajax.reload(null, false);

                // Si se estaba editando el mismo menú, limpia el formulario.
                if (parseInt($("#menu_id").val(), 10) === parseInt(menuId, 10)) {
                    limpiarFormularioMenu();
                }

                Swal.fire({
                    title: "Menú",
                    text: data.message,
                    icon: "success"
                });
            },

            error: function (e) {
                console.error(e.responseText);

                Swal.fire({
                    title: "Menú",
                    text: "No fue posible cambiar el estado.",
                    icon: "error"
                });
            }
        });
    });
}


function limpiarFormularioMenu() {

    $("#form_menu")[0].reset();

    $("#menu_id").val("");

    $("#menu_grupo")
        .val("")
        .trigger("change");

    $("#menu_esta")
        .val("1")
        .trigger("change");

    $("#contenedor_estado_menu").hide();
    $("#btn_cancelar_edicion").hide();

    $("#titulo_form_menu").html(
        '<h3 class="card-title">'
        + '<i class="fas fa-plus-circle mr-2"></i>'
        + 'Registrar Menú'
        + '</h3>'
    );

    $("#btn_guardar_menu")
        .prop("disabled", false)
        .html(
            '<i class="fas fa-save mr-1"></i> Registrar Menú'
        );
}


function actualizarBotonGuardar() {

    let editando = $("#menu_id").val() !== "";

    $("#btn_guardar_menu")
        .prop("disabled", false)
        .html(
            editando
                ? '<i class="fas fa-save mr-1"></i> Guardar Cambios'
                : '<i class="fas fa-save mr-1"></i> Registrar Menú'
        );
}


init();