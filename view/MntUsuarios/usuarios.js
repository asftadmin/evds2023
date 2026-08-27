/*
 * ============================================================
 * VARIABLES GLOBALES
 * ============================================================
 */

let tablaUsuarios;


/*
 * ============================================================
 * INICIALIZAR MÓDULO
 * ============================================================
 *
 * Ejecuta la configuración inicial de:
 *
 * - Select2 empleados.
 * - Select2 roles.
 * - DataTable usuarios.
 * - Eventos del formulario.
 */
function init() {

    inicializarSelectEmpleado();

    inicializarSelectRol();

    listarUsuarios();

}


/*
 * ============================================================
 * SELECT2 EMPLEADOS
 * ============================================================
 *
 * Consulta únicamente empleados activos que todavía
 * no tienen usuario asociado.
 *
 * El controlador consulta esta información mediante:
 *
 * controller/usuario.php?op=listarEmpleadosSinUsuario
 */
function inicializarSelectEmpleado() {

    $("#empleado_id").select2({

        theme: "bootstrap4",

        width: "100%",

        placeholder: "Seleccione un empleado",

        allowClear: true,

        ajax: {

            url:
                "../../controller/usuario.php"
                + "?op=listarEmpleadosSinUsuario",

            type: "GET",

            dataType: "json",

            delay: 250,

            /*
             * Select2 puede enviar el texto digitado.
             *
             * Por ahora el modelo devuelve todos los empleados
             * disponibles y Select2 realiza la selección.
             */
            processResults: function (data) {

                return {
                    results: data.results
                };

            },

            cache: false

        }

    });

}


/*
 * ============================================================
 * SELECT2 ROLES
 * ============================================================
 *
 * Consulta los roles activos registrados en PostgreSQL.
 *
 * No se colocan IDs de roles manualmente en JavaScript.
 */
function inicializarSelectRol() {

    $("#user_rol").select2({

        theme: "bootstrap4",

        width: "100%",

        placeholder: "Seleccione un rol",

        allowClear: true

    });


    /*
     * Consulta roles disponibles al controlador.
     */
    $.ajax({

        url:
            "../../controller/usuario.php"
            + "?op=listarRoles",

        type: "GET",

        dataType: "json",

        success: function (data) {

            /*
             * Limpia cualquier opción existente.
             */
            $("#user_rol").empty();


            /*
             * Agrega opción inicial.
             */
            $("#user_rol").append(

                $("<option>", {
                    value: "",
                    text: "Seleccione un rol"
                })

            );


            /*
             * Recorre los roles recibidos desde PostgreSQL.
             */
            if (
                data.results
                && Array.isArray(data.results)
            ) {

                data.results.forEach(
                    function (rol) {

                        $("#user_rol").append(

                            $("<option>", {
                                value: rol.id,
                                text: rol.text
                            })

                        );

                    }
                );

            }


            /*
             * Actualiza Select2.
             */
            $("#user_rol")
                .val("")
                .trigger("change");

        },

        error: function () {

            Swal.fire({

                icon: "error",

                title: "Error",

                text:
                    "No fue posible consultar "
                    + "los roles disponibles."

            });

        }

    });

}


/*
 * ============================================================
 * DATATABLE USUARIOS
 * ============================================================
 *
 * Lista los usuarios registrados actualmente en el sistema.
 *
 * La respuesta del controlador utiliza el formato:
 *
 * aaData
 *
 * siguiendo el mismo patrón actual del proyecto.
 */
function listarUsuarios() {

    tablaUsuarios =
        $("#usuarios_data").DataTable({

            processing: true,

            responsive: true,

            autoWidth: false,

            destroy: true,

            ajax: {

                url:
                    "../../controller/usuario.php"
                    + "?op=listarUsuarios",

                type: "GET",

                dataSrc: "aaData"

            },


            /*
             * Como el controlador construye cada fila mediante
             * sub_array[], DataTables recibe las columnas
             * directamente por posición.
             */
            columnDefs: [

                {
                    targets: "_all",
                    className: "align-middle"
                }

            ],


            /*
             * Ordenar inicialmente por nombre del empleado.
             *
             * Según el controlador:
             *
             * 0 = documento
             * 1 = empleado
             * 2 = usuario
             * 3 = rol
             * 4 = acciones
             */
            order: [
                [1, "asc"]
            ],


            language: {

                processing:
                    "Procesando...",

                search:
                    "Buscar:",

                lengthMenu:
                    "Mostrar _MENU_ registros",

                info:
                    "Mostrando registros "
                    + "del _START_ al _END_ "
                    + "de un total de _TOTAL_ registros",

                infoEmpty:
                    "Mostrando registros del 0 al 0 "
                    + "de un total de 0 registros",

                infoFiltered:
                    "(filtrado de un total "
                    + "de _MAX_ registros)",

                loadingRecords:
                    "Cargando...",

                zeroRecords:
                    "No se encontraron registros",

                emptyTable:
                    "No existen usuarios registrados",

                paginate: {

                    first:
                        "Primero",

                    previous:
                        "Anterior",

                    next:
                        "Siguiente",

                    last:
                        "Último"

                }

            }

        });

}


/*
 * ============================================================
 * ENVIAR FORMULARIO
 * ============================================================
 *
 * Captura el formulario sin recargar la página.
 */
$("#form_usuario").on(
    "submit",
    function (e) {

        e.preventDefault();


        let user_id =
            $("#user_id").val();

        /*
         * Obtiene los valores ingresados.
         */
        let empleado_id =
            $("#empleado_id").val();

        let user_nick =
            $.trim(
                $("#user_nick").val()
            );

        let user_pass =
            $("#user_pass").val();

        let user_pass_confirmar =
            $("#user_pass_confirmar").val();

        let user_rol =
            $("#user_rol").val();


        /*
         * ====================================================
         * VALIDAR EMPLEADO
         * ====================================================
         */
        if (
            (user_id === null || user_id === "")
            &&
            (empleado_id === null || empleado_id === "")
        ) {

            Swal.fire({

                icon: "warning",

                title: "Empleado requerido",

                text:
                    "Debe seleccionar el empleado "
                    + "al cual se le creará el usuario."

            });

            return;

        }


        /*
         * ====================================================
         * VALIDAR USUARIO
         * ====================================================
         */
        if (user_nick === "") {

            Swal.fire({

                icon: "warning",

                title: "Usuario requerido",

                text:
                    "Debe ingresar un nombre de usuario."

            });

            $("#user_nick").focus();

            return;

        }


        /*
         * Se establece una longitud mínima para evitar
         * nombres de usuario demasiado cortos.
         */
        if (user_nick.length < 4) {

            Swal.fire({

                icon: "warning",

                title: "Usuario no válido",

                text:
                    "El nombre de usuario debe contener "
                    + "mínimo 4 caracteres."

            });

            $("#user_nick").focus();

            return;

        }


        /*
         * ====================================================
         * VALIDAR ROL
         * ====================================================
         */
        if (
            user_rol === null
            || user_rol === ""
        ) {

            Swal.fire({

                icon: "warning",

                title: "Rol requerido",

                text:
                    "Debe seleccionar el rol "
                    + "que tendrá el usuario."

            });

            return;

        }


        /*
         * ====================================================
         * VALIDAR CONTRASEÑA
         * ====================================================
         */
        if (
                (user_id === null || user_id === "")
                &&
                user_pass === ""
        ) {

            Swal.fire({

                icon: "warning",

                title: "Contraseña requerida",

                text:
                    "Debe ingresar una contraseña."

            });

            $("#user_pass").focus();

            return;

        }


        /*
         * Valida una longitud mínima.
         */
        if (    user_pass !== ""
               && user_pass.length < 8) {

            Swal.fire({

                icon: "warning",

                title: "Contraseña no válida",

                text:
                    "La contraseña debe contener "
                    + "mínimo 8 caracteres."

            });

            $("#user_pass").focus();

            return;

        }


        /*
         * Comprueba que ambas contraseñas coincidan.
         */
        if (
            user_pass !== ""
            && user_pass !== user_pass_confirmar
        ) {

            Swal.fire({

                icon: "warning",

                title: "Contraseñas diferentes",

                text:
                    "La contraseña y su confirmación "
                    + "no coinciden."

            });

            $("#user_pass_confirmar").focus();

            return;

        }


        /*
         * Obtiene los textos visibles para mostrarlos
         * en la confirmación.
         */
        let empleadoTexto =
            $("#empleado_id option:selected")
                .text();

        let rolTexto =
            $("#user_rol option:selected")
                .text();


        /*
         * ====================================================
         * CONFIRMACIÓN SWEETALERT2
         * ====================================================
         */
        Swal.fire({

            title:
                "¿Crear usuario?",

            html:
                "<div class='text-left'>"
                + "<strong>Empleado:</strong> "
                + $("<div>")
                    .text(empleadoTexto)
                    .html()
                + "<br>"
                + "<strong>Usuario:</strong> "
                + $("<div>")
                    .text(user_nick)
                    .html()
                + "<br>"
                + "<strong>Rol:</strong> "
                + $("<div>")
                    .text(rolTexto)
                    .html()
                + "</div>",

            icon:
                "question",

            showCancelButton:
                true,

            confirmButtonText:
                "Sí, crear usuario",

            cancelButtonText:
                "Cancelar",

            reverseButtons:
                true

        }).then(
            function (result) {

                if (result.isConfirmed) {

                    guardarUsuario();

                }

            }
        );

    }
);


/*
 * ============================================================
 * GUARDAR USUARIO
 * ============================================================
 *
 * Envía los datos al controlador mediante AJAX.
 */
function guardarUsuario() {

    /*
     * Serializa los campos del formulario.
     */
    let formData =
        $("#form_usuario").serialize();


    $.ajax({

        url:
            "../../controller/usuario.php"
            + "?op=guardaryeditar",

        type:
            "POST",

        data:
            formData,

        dataType:
            "json",


        /*
         * Deshabilita temporalmente el botón para impedir
         * registros duplicados mientras se procesa la petición.
         */
        beforeSend: function () {

            $("#btn_guardar")
                .prop(
                    "disabled",
                    true
                );


            Swal.fire({

                title:
                    "Creando usuario...",

                text:
                    "Espere un momento.",

                allowOutsideClick:
                    false,

                allowEscapeKey:
                    false,

                didOpen: function () {

                    Swal.showLoading();

                }

            });

        },


        /*
         * Procesa la respuesta JSON enviada por PHP.
         */
        success: function (data) {

            /*
             * Vuelve a habilitar el botón.
             */
            $("#btn_guardar")
                .prop(
                    "disabled",
                    false
                );


            /*
             * Si el modelo confirmó la operación.
             */
            if (data.success === true) {

                Swal.fire({

                    icon:
                        "success",

                    title:
                        "Usuario creado",

                    text:
                        data.message,

                    confirmButtonText:
                        "Aceptar"

                });


                /*
                 * Limpia el formulario.
                 */
                limpiarFormulario();


                /*
                 * Recarga DataTable sin recargar la página.
                 */
                if (tablaUsuarios) {

                    tablaUsuarios
                        .ajax
                        .reload(
                            null,
                            false
                        );

                }


                /*
                 * El empleado recién asociado ya no debe
                 * aparecer disponible en el Select2.
                 *
                 * Al limpiar Select2 se realizará una nueva
                 * consulta al abrirlo nuevamente.
                 */
                $("#empleado_id")
                    .val(null)
                    .trigger("change");

            } else {

                /*
                 * El modelo devolvió una validación controlada.
                 */
                Swal.fire({

                    icon:
                        "warning",

                    title:
                        "No fue posible crear el usuario",

                    text:
                        data.message

                });

            }

        },


        /*
         * Maneja errores HTTP o respuestas inválidas.
         */
        error: function (
            xhr,
            status,
            error
        ) {

            $("#btn_guardar")
                .prop(
                    "disabled",
                    false
                );


            console.error(
                "Error AJAX:",
                status,
                error
            );


            Swal.fire({

                icon:
                    "error",

                title:
                    "Error",

                text:
                    "No fue posible comunicarse "
                    + "con el servidor."

            });

        }

    });

}


/*
 * ============================================================
 * LIMPIAR FORMULARIO
 * ============================================================
 *
 * Restablece todos los campos después de guardar
 * o cuando Sistemas pulse el botón Limpiar.
 */
function limpiarFormulario() {

    /*
     * Limpia inputs normales.
     */
    $("#form_usuario")[0].reset();


    /*
     * Limpia Select2 empleado.
     */
    $("#empleado_id")
        .val(null)
        .trigger("change");


    /*
     * Limpia Select2 rol.
     */
    $("#user_rol")
        .val("")
        .trigger("change");


    /*
     * Coloca el cursor nuevamente en usuario
     * cuando corresponda.
     */
    $("#user_nick").val("");

    $("#user_pass").val("");

    $("#user_pass_confirmar").val("");

}


/*
 * ============================================================
 * BOTÓN LIMPIAR
 * ============================================================
 */
$(document).on(
    "click",
    "#btn_limpiar",
    function () {

        limpiarFormulario();

    }
);


/*
 * ============================================================
 * MOSTRAR / OCULTAR CONTRASEÑA
 * ============================================================
 *
 * Esta función es opcional y permite utilizar un botón
 * con id btn_ver_password en la vista.
 */
$(document).on(
    "click",
    "#btn_ver_password",
    function () {

        let input =
            $("#user_pass");

        let tipo =
            input.attr("type");


        if (tipo === "password") {

            input.attr(
                "type",
                "text"
            );

            $(this).html(
                '<i class="fas fa-eye-slash"></i>'
            );

        } else {

            input.attr(
                "type",
                "password"
            );

            $(this).html(
                '<i class="fas fa-eye"></i>'
            );

        }

    }
);

/*
 * ============================================================
 * EDITAR USUARIO
 * ============================================================
 *
 * Captura el botón Editar generado dinámicamente
 * dentro del DataTable.
 */
$(document).on(
    "click",
    ".btn_editar_usuario",
    function () {

        let user_id =
            $(this).data("user_id");

        editarUsuario(user_id);

    }
);

/*
 * ============================================================
 * CARGAR USUARIO PARA EDICIÓN
 * ============================================================
 *
 * Consulta la información del usuario seleccionado
 * y carga sus datos en el formulario.
 */
function editarUsuario(user_id) {

    $.ajax({

        url:
            "../../controller/usuario.php"
            + "?op=mostrar",

        type:
            "POST",

        dataType:
            "json",

        data: {
            user_id: user_id
        },

        success: function (data) {

            if (
                data.success !== true
                || !data.data
            ) {

                Swal.fire({
                    icon: "warning",
                    title: "Usuario",
                    text:
                        data.message
                        || "No fue posible consultar el usuario."
                });

                return;
            }

            // Mostrar el empleado relacionado.
            let textoEmpleado =
                data.data.cedu_empl
                + " - "
                + data.data.nomb_empl;

            let opcionEmpleado = new Option(
                textoEmpleado,
                data.data.id_empl,
                true,
                true
            );

            $("#empleado_id")
                .empty()
                .append(opcionEmpleado)
                .trigger("change")
                .prop("disabled", true);


            /*
             * Guarda el ID del usuario que se está editando.
             */
            $("#user_id")
                .val(
                    data.data.user_id
                );


            /*
             * Carga nombre de usuario.
             */
            $("#user_nick")
                .val(
                    data.data.user_nick
                );


            /*
             * Carga rol.
             */
            $("#user_rol")
                .val(
                    data.data.user_rol
                )
                .trigger("change");


            /*
             * Durante la edición el empleado no puede
             * ser reasignado a otra cuenta.
             */
            $("#empleado_id")
                .prop(
                    "disabled",
                    true
                );


            /*
             * La contraseña queda vacía.
             *
             * Solo será modificada si Sistemas
             * escribe una nueva.
             */
            $("#user_pass")
                .val("");


            $("#user_pass_confirmar")
                .val("");


            /*
             * Cambia visualmente el formulario
             * al modo edición.
             */
            $("#titulo_form_usuario")
                .html(
                    '<i class="fas fa-user-edit mr-2"></i>'
                    + 'Editar Usuario'
                );


            $("#btn_guardar")
                .html(
                    '<i class="fas fa-save mr-1"></i>'
                    + 'Guardar Cambios'
                );

            $("#btn_limpiar")
                .hide();

            /*
             * Muestra botón para cancelar la edición.
             */
            $("#btn_cancelar_edicion")
                .show();


            /*
             * Lleva al usuario al formulario.
             */
            $("html, body").animate(
                {
                    scrollTop:
                        $("#form_usuario")
                            .offset()
                            .top - 100
                },
                400
            );

        },

        error: function () {

            Swal.fire({
                icon: "error",
                title: "Error",
                text:
                    "No fue posible consultar "
                    + "la información del usuario."
            });

        }

    });

}

/*
 * ============================================================
 * CANCELAR EDICIÓN
 * ============================================================
 */
$(document).on(
    "click",
    "#btn_cancelar_edicion",
    function () {

        limpiarFormulario();

        $("#user_id")
            .val("");


        $("#empleado_id")
            .prop(
                "disabled",
                false
            );


        $("#titulo_form_usuario")
            .html(
                '<i class="fas fa-user-plus mr-2"></i>'
                + 'Crear Usuario'
            );


        $("#btn_guardar")
            .html(
                '<i class="fas fa-save mr-1"></i>'
                + 'Crear Usuario'
            );
        
        $("#btn_limpiar")
                .show();


        $(this).hide();

    }
);

/*
 * ============================================================
 * DOCUMENT READY
 * ============================================================
 */
$(document).ready(
    function () {

        init();

    }
);