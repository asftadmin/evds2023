let tablaRoles;
let tablaPermisos;

let estadoPermisosInicial = {};
let estadoPermisosActual = {};
let capturarEstadoInicial = false;


// =============================================================
// INICIALIZACIÓN
// =============================================================

function init() {

    // Guardar o editar rol.
    $("#mantenimiento_rol").on("submit", function (e) {
        guardarEditarRol(e);
    });

    // Abrir formulario para crear un nuevo rol.
    $(document).on("click", "#btnNuevoRol", function () {
        nuevoRol();
    });

    // Conceder todos los permisos.
    $(document).on("click", "#btnConcederTodos", function () {
        concederTodos();
    });

    // Revocar todos los permisos.
    $(document).on("click", "#btnRevocarTodos", function () {
        revocarTodos();
    });

    // Descartar cambios realizados en los permisos.
    $(document).on("click", "#btnDescartarPermisos", function () {
        descartarPermisos();
    });

    // Guardar todos los permisos del rol.
    $(document).on("click", "#btnGuardarPermisos", function () {
        guardarPermisos();
    });

    // Detectar cambio individual de un permiso.
    $(document).on("change", ".permiso_menu", function () {
        cambiarPermiso($(this));
    });
}


// =============================================================
// DATATABLE ROLES
// =============================================================

$(document).ready(function () {

    // Inicializar Select2 utilizado en el formulario del rol.
    $(".select2bs4").select2({
        theme: "bootstrap4",
        minimumResultsForSearch: Infinity
    });


    // Cargar los roles registrados.
    tablaRoles = $("#roles_data").DataTable({

        processing: true,
        serverSide: false,
        searching: true,
        lengthChange: false,
        responsive: true,
        autoWidth: false,
        pageLength: 5,

        ajax: {

            url: "../../controller/rol.php?op=listar",

            type: "POST",

            dataType: "json",

            dataSrc: "aaData",

            error: function (e) {

                console.error(e.responseText);

                Swal.fire({
                    title: "Roles",
                    text: "No fue posible cargar los roles.",
                    icon: "error"
                });
            }
        },

        columnDefs: [

            {
                targets: [0, 2, 3],
                className: "text-center"
            },

            {
                targets: 3,
                orderable: false,
                searchable: false
            }

        ],

        language: {

            processing: "Procesando...",

            search: "Buscar:",

            zeroRecords: "No se encontraron resultados",

            emptyTable: "No existen roles registrados",

            info: "Mostrando _START_ a _END_ de _TOTAL_ roles",

            infoEmpty: "No existen roles registrados",

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


// =============================================================
// NUEVO ROL
// =============================================================

function nuevoRol() {

    // Limpiar formulario antes de crear el nuevo rol.
    limpiarFormularioRol();

    // Cambiar el título del modal.
    $("#lblTitulo").html(
        '<i class="fas fa-user-tag mr-2"></i>Nuevo Rol'
    );

    // El nuevo rol siempre se crea activo desde el modelo.
    $("#contenedor_estado_rol").hide();

    // Mostrar modal.
    $("#modalRol").modal("show");
}


// =============================================================
// GUARDAR / EDITAR ROL
// =============================================================

function guardarEditarRol(e) {

    e.preventDefault();


    let rolNomb = $.trim(
        $("#nomb_rol").val()
    );


    // Validar nombre antes de enviar al servidor.
    if (rolNomb === "") {

        Swal.fire({
            title: "Rol",
            text: "Debe ingresar el nombre del rol.",
            icon: "warning"
        });

        return;
    }


    let formData =
        new FormData(
            $("#mantenimiento_rol")[0]
        );


    // Evitar múltiples envíos mientras responde el servidor.
    $("#btnGuardarRol")
        .prop("disabled", true)
        .html(
            '<i class="fas fa-spinner fa-spin mr-1"></i> Guardando...'
        );


    $.ajax({

        url: "../../controller/rol.php?op=guardaryeditar",

        type: "POST",

        data: formData,

        dataType: "json",

        contentType: false,

        processData: false,

        success: function (data) {

            if (!data.success) {

                Swal.fire({
                    title: "Rol",
                    text: data.message,
                    icon: "warning"
                });

                return;
            }


            // Cerrar formulario.
            $("#modalRol").modal("hide");


            // Actualizar DataTable sin regresar a la primera página.
            tablaRoles.ajax.reload(
                null,
                false
            );


            Swal.fire({
                title: "Rol",
                text: data.message,
                icon: "success"
            });
        },

        error: function (e) {

            console.error(e.responseText);

            Swal.fire({
                title: "Rol",
                text: "No fue posible procesar la solicitud.",
                icon: "error"
            });
        },

        complete: function () {

            $("#btnGuardarRol")
                .prop("disabled", false)
                .html(
                    '<i class="fas fa-save mr-1"></i> Guardar'
                );
        }
    });
}


// =============================================================
// EDITAR ROL
// =============================================================

function editar(rol_id) {

    // Limpiar datos anteriores.
    limpiarFormularioRol();


    $.ajax({

        url: "../../controller/rol.php?op=mostrar",

        type: "POST",

        dataType: "json",

        data: {
            rol_id: rol_id
        },

        success: function (data) {

            if (!data.success) {

                Swal.fire({
                    title: "Rol",
                    text: data.message,
                    icon: "warning"
                });

                return;
            }


            // Cargar información retornada por el controlador.
            $("#rol_id").val(
                data.data.rol_id
            );

            $("#nomb_rol").val(
                data.data.rol_nomb
            );

            $("#esta_rol")
                .val(
                    data.data.rol_esta
                )
                .trigger("change");


            // En edición sí se permite modificar el estado.
            $("#contenedor_estado_rol").show();


            $("#lblTitulo").html(
                '<i class="fas fa-user-tag mr-2"></i>Editar Rol'
            );


            $("#modalRol").modal("show");
        },

        error: function (e) {

            console.error(e.responseText);

            Swal.fire({
                title: "Rol",
                text: "No fue posible consultar el rol.",
                icon: "error"
            });
        }
    });
}


// =============================================================
// CAMBIAR ESTADO DEL ROL
// =============================================================

function cambiarEstadoRol(
    rol_id,
    rol_esta
) {

    let accion =
        parseInt(rol_esta, 10) === 1
            ? "activar"
            : "inactivar";


    let mensaje =
        parseInt(rol_esta, 10) === 1
            ? "¿Desea activar este rol?"
            : "¿Desea inactivar este rol?";


    Swal.fire({

        title: "Cambiar estado",

        text: mensaje,

        icon: "question",

        showCancelButton: true,

        confirmButtonColor: "#17a2b8",

        cancelButtonColor: "#6c757d",

        confirmButtonText:
            "Sí, " + accion,

        cancelButtonText: "Cancelar"

    }).then(function (result) {


        if (!result.isConfirmed) {
            return;
        }


        $.ajax({

            url: "../../controller/rol.php?op=cambiar_estado",

            type: "POST",

            dataType: "json",

            data: {

                rol_id: rol_id,

                rol_esta: rol_esta
            },

            success: function (data) {

                if (!data.success) {

                    Swal.fire({
                        title: "Rol",
                        text: data.message,
                        icon: "warning"
                    });

                    return;
                }


                tablaRoles.ajax.reload(
                    null,
                    false
                );


                Swal.fire({
                    title: "Rol",
                    text: data.message,
                    icon: "success"
                });
            },

            error: function (e) {

                console.error(e.responseText);

                Swal.fire({
                    title: "Rol",
                    text: "No fue posible cambiar el estado.",
                    icon: "error"
                });
            }
        });
    });
}


// =============================================================
// ABRIR PERMISOS DEL ROL
// =============================================================

function permisos(rol_id) {

    // Guardar el rol actualmente administrado.
    $("#perm_rol_id").val(
        rol_id
    );


    // Limpiar contadores mientras se cargan los datos.
    $("#perm_rol_nomb").text("-");

    $("#total_menus").text("0");

    $("#total_acceso").text("0");


    estadoPermisosInicial = {};

    estadoPermisosActual = {};

    capturarEstadoInicial = true;


    // Inicializar DataTable únicamente la primera vez.
    if (
        !$.fn.DataTable.isDataTable(
            "#perm_table_data"
        )
    ) {

        inicializarTablaPermisos();
    } else {

        tablaPermisos.ajax.reload();
    }


    $("#modalPermisos").modal("show");
}


// =============================================================
// DATATABLE PERMISOS
// =============================================================

function inicializarTablaPermisos() {

    tablaPermisos =
        $("#perm_table_data").DataTable({

            processing: true,

            serverSide: false,

            searching: true,

            paging: false,

            lengthChange: false,

            responsive: true,

            autoWidth: false,

            ajax: {

                url:
                    "../../controller/rol.php?op=listar_permisos",

                type: "POST",

                dataType: "json",

                // Enviar el rol actualmente seleccionado.
                data: function () {

                    return {

                        rol_id:
                            $("#perm_rol_id").val()

                    };
                },

                // Procesar la información adicional del rol.
                dataSrc: function (data) {

                    if (!data.success) {

                        Swal.fire({
                            title: "Permisos",
                            text: data.message,
                            icon: "warning"
                        });

                        return [];
                    }


                    $("#perm_rol_nomb").text(
                        data.rol.rol_nomb
                    );


                    $("#total_menus").text(
                        data.total_menus
                    );


                    $("#total_acceso").text(
                        data.total_acceso
                    );


                    // El siguiente draw capturará el estado del servidor.
                    capturarEstadoInicial = true;


                    return data.aaData;
                },

                error: function (e) {

                    console.error(
                        e.responseText
                    );


                    Swal.fire({
                        title: "Permisos",
                        text: "No fue posible cargar los permisos del rol.",
                        icon: "error"
                    });
                }
            },


            columnDefs: [

                {
                    targets: [0, 4, 5],
                    className: "text-center"
                },

                {
                    targets: 5,
                    orderable: false,
                    searchable: false
                }

            ],


            // Mantener los switches aunque DataTable vuelva a dibujar.
            drawCallback: function () {

                if (capturarEstadoInicial) {

                    capturarPermisos();

                    capturarEstadoInicial = false;

                } else {

                    aplicarEstadoPermisos();
                }


                actualizarResumenPermisos();
            },


            language: {

                processing: "Procesando...",

                search: "Buscar menú:",

                zeroRecords:
                    "No se encontraron menús",

                emptyTable:
                    "No existen menús disponibles",

                info: "",

                infoEmpty: ""
            }
        });
}


// =============================================================
// CAPTURAR ESTADO INICIAL DE PERMISOS
// =============================================================

function capturarPermisos() {

    estadoPermisosInicial = {};

    estadoPermisosActual = {};


    $("#perm_table_data .permiso_menu")
        .each(function () {


            let menuId =
                String(
                    $(this).val()
                );


            let acceso =
                $(this).is(
                    ":checked"
                );


            estadoPermisosInicial[menuId] =
                acceso;


            estadoPermisosActual[menuId] =
                acceso;


            actualizarVisualPermiso(
                $(this)
            );
        });
}


// =============================================================
// CAMBIAR UN PERMISO
// =============================================================

function cambiarPermiso(
    checkbox
) {

    let menuId =
        String(
            checkbox.val()
        );


    estadoPermisosActual[menuId] =
        checkbox.is(
            ":checked"
        );


    actualizarVisualPermiso(
        checkbox
    );


    actualizarResumenPermisos();
}


// =============================================================
// ACTUALIZAR TEXTO Y FILA DEL PERMISO
// =============================================================

function actualizarVisualPermiso(
    checkbox
) {

    let menuId =
        String(
            checkbox.val()
        );


    let acceso =
        checkbox.is(
            ":checked"
        );


    let texto =
        checkbox
            .closest("td")
            .find(".permission-text");


    // Actualizar texto del permiso.
    if (acceso) {

        texto
            .text("Con acceso")
            .removeClass(
                "text-danger"
            )
            .addClass(
                "text-success"
            );

    } else {

        texto
            .text("Sin acceso")
            .removeClass(
                "text-success"
            )
            .addClass(
                "text-danger"
            );
    }


    // Resaltar los permisos modificados pero todavía no guardados.
    if (
        estadoPermisosInicial[menuId]
        !== undefined
        &&
        estadoPermisosInicial[menuId]
        !== acceso
    ) {

        checkbox
            .closest("tr")
            .addClass(
                "table-warning"
            );

    } else {

        checkbox
            .closest("tr")
            .removeClass(
                "table-warning"
            );
    }
}


// =============================================================
// RESTAURAR ESTADO DESPUÉS DE REDIBUJAR DATATABLE
// =============================================================

function aplicarEstadoPermisos() {

    $("#perm_table_data .permiso_menu")
        .each(function () {


            let menuId =
                String(
                    $(this).val()
                );


            if (
                estadoPermisosActual[menuId]
                === undefined
            ) {
                return;
            }


            $(this).prop(
                "checked",
                estadoPermisosActual[menuId]
            );


            actualizarVisualPermiso(
                $(this)
            );
        });
}


// =============================================================
// CONCEDER TODOS
// =============================================================

function concederTodos() {

    Object.keys(
        estadoPermisosActual
    ).forEach(function (menuId) {

        estadoPermisosActual[menuId] =
            true;
    });


    aplicarEstadoPermisos();

    actualizarResumenPermisos();
}


// =============================================================
// REVOCAR TODOS
// =============================================================

function revocarTodos() {

    Object.keys(
        estadoPermisosActual
    ).forEach(function (menuId) {

        estadoPermisosActual[menuId] =
            false;
    });


    aplicarEstadoPermisos();

    actualizarResumenPermisos();
}


// =============================================================
// DESCARTAR CAMBIOS
// =============================================================

function descartarPermisos() {

    Swal.fire({

        title: "Descartar cambios",

        text:
            "¿Desea restaurar los permisos al estado con el que fueron cargados?",

        icon: "question",

        showCancelButton: true,

        confirmButtonColor:
            "#17a2b8",

        cancelButtonColor:
            "#6c757d",

        confirmButtonText:
            "Sí, descartar",

        cancelButtonText:
            "Cancelar"

    }).then(function (result) {


        if (!result.isConfirmed) {
            return;
        }


        // Copiar nuevamente el estado original.
        estadoPermisosActual = {
            ...estadoPermisosInicial
        };


        aplicarEstadoPermisos();

        actualizarResumenPermisos();
    });
}


// =============================================================
// ACTUALIZAR CONTADORES
// =============================================================

function actualizarResumenPermisos() {

    let menuIds =
        Object.keys(
            estadoPermisosActual
        );


    let totalMenus =
        menuIds.length;


    let totalAcceso =
        menuIds.filter(
            function (menuId) {

                return (
                    estadoPermisosActual[menuId]
                    === true
                );
            }
        ).length;


    $("#total_menus").text(
        totalMenus
    );


    $("#total_acceso").text(
        totalAcceso
    );
}


// =============================================================
// GUARDAR PERMISOS
// =============================================================

function guardarPermisos() {

    let rolId =
        $("#perm_rol_id").val();


    if (!rolId) {

        Swal.fire({
            title: "Permisos",
            text: "No se ha seleccionado un rol.",
            icon: "warning"
        });

        return;
    }


    // Obtener únicamente los menu_id que quedaron habilitados.
    let menusAcceso =
        Object.keys(
            estadoPermisosActual
        ).filter(
            function (menuId) {

                return (
                    estadoPermisosActual[menuId]
                    === true
                );
            }
        );


    Swal.fire({

        title: "Guardar permisos",

        text:
            "¿Desea guardar los accesos configurados para este rol?",

        icon: "question",

        showCancelButton: true,

        confirmButtonColor:
            "#17a2b8",

        cancelButtonColor:
            "#6c757d",

        confirmButtonText:
            "Sí, guardar",

        cancelButtonText:
            "Cancelar"

    }).then(function (result) {


        if (!result.isConfirmed) {
            return;
        }


        $("#btnGuardarPermisos")
            .prop(
                "disabled",
                true
            )
            .html(
                '<i class="fas fa-spinner fa-spin mr-1"></i> Guardando...'
            );


        $.ajax({

            url:
                "../../controller/rol.php?op=guardar_permisos",

            type: "POST",

            dataType: "json",

            data: {

                rol_id:
                    rolId,

                menus_acceso:
                    menusAcceso
            },

            success: function (data) {

                if (!data.success) {

                    Swal.fire({
                        title: "Permisos",
                        text: data.message,
                        icon: "warning"
                    });

                    return;
                }


                /*
                 * Los permisos actuales pasan a ser
                 * el nuevo estado inicial.
                 */
                estadoPermisosInicial = {
                    ...estadoPermisosActual
                };


                aplicarEstadoPermisos();


                Swal.fire({
                    title: "Permisos",
                    text: data.message,
                    icon: "success"
                });
            },

            error: function (e) {

                console.error(
                    e.responseText
                );


                Swal.fire({
                    title: "Permisos",
                    text:
                        "No fue posible guardar los permisos.",
                    icon: "error"
                });
            },

            complete: function () {

                $("#btnGuardarPermisos")
                    .prop(
                        "disabled",
                        false
                    )
                    .html(
                        '<i class="fas fa-save mr-1"></i> Guardar Permisos'
                    );
            }
        });
    });
}


// =============================================================
// LIMPIAR FORMULARIO DEL ROL
// =============================================================

function limpiarFormularioRol() {

    $("#mantenimiento_rol")[0].reset();


    $("#rol_id").val("");


    $("#esta_rol")
        .val("1")
        .trigger("change");


    $("#contenedor_estado_rol")
        .hide();
}


// =============================================================
// INICIAR MÓDULO
// =============================================================

init();