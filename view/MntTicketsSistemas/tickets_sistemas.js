let tablaTicketsSistemas;
let soportesTicket;
let preparandoSoportesTicket = false;
let enviandoTicket = false;
let eliminandoSoportesTicket = 0;
let cargaSoportesTicket = null;
Dropzone.autoDiscover = false;


/*
 * =====================================================
 * INICIALIZAR MODULO
 * =====================================================
 */
function init() {

    initSelect2TicketsSistemas();

    initTablaTicketsSistemas();

    initEventosTicketsSistemas();
    initSoportesTicketsSistemas();

    cargarEmpleadoTicketsSistemas();
    cargarCategoriasTicketsSistemas();

}


/*
 * =====================================================
 * SELECT2
 * =====================================================
 */
function initSelect2TicketsSistemas() {

    /*
     * Filtro de estado.
     */
    $('#filtro-estado').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: 'Todos',
        allowClear: true
    });

    // Espera a que Bootstrap termine de mostrar y posicionar el formulario.
    $('#modal-nuevo-ticket').on('shown.bs.modal.select2Tickets', initSelect2ModalTickets);
}

// Inicializa una sola vez y mantiene los desplegables dentro del contenido del modal.
function initSelect2ModalTickets() {
    const contenidoModal = $('#modal-nuevo-ticket .modal-content');
    if ($('#ticket-tipo').hasClass('select2-hidden-accessible')) { return; }

    /*
     * Selects ubicados dentro del modal.
     *
     * dropdownParent evita problemas visuales
     * de Select2 dentro de modales Bootstrap.
     */
    $('#ticket-tipo').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: 'Seleccione',
        allowClear: true,
        dropdownParent: contenidoModal
    });


    $('#ticket-categoria').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: 'Seleccione una categoría',
        allowClear: true,
        dropdownParent: contenidoModal
    });


    $('#ticket-prioridad').select2({
        theme: 'bootstrap4',
        width: '100%',
        minimumResultsForSearch: Infinity,
        dropdownParent: contenidoModal
    });

    $('#ticket-ubicacion').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: 'Seleccione una ubicación',
        allowClear: true,
        dropdownParent: contenidoModal
    });

}

function escaparHtml(valor) {
    return $('<div>')
        .text(valor || '')
        .html();
}


function formatearFechaTicket(fecha) {

    if (!fecha) {
        return '';
    }

    return fecha
        .substring(0, 16)
        .replace('T', ' ');
}


function badgePrioridadTicket(prioridad) {

    const estilos = {
        BAJA: 'badge-secondary',
        MEDIA: 'badge-info',
        ALTA: 'badge-warning',
        CRITICA: 'badge-danger'
    };

    const clase = estilos[prioridad]
        || 'badge-secondary';

    return '<span class="badge ' + clase + '">' +
        escaparHtml(prioridad) +
        '</span>';
}


function badgeEstadoTicket(estado) {

    const estilos = {
        ABIERTO: 'badge-primary',
        EN_PROCESO: 'badge-info',
        EN_ESPERA: 'badge-warning',
        RESUELTO: 'badge-success',
        CERRADO: 'badge-secondary',
        CANCELADO: 'badge-danger'
    };

    const nombres = {
        ABIERTO: 'Abierto',
        EN_PROCESO: 'En proceso',
        EN_ESPERA: 'En espera',
        RESUELTO: 'Resuelto',
        CERRADO: 'Cerrado',
        CANCELADO: 'Cancelado'
    };

    return '<span class="badge ' +
        (estilos[estado] || 'badge-secondary') +
        '">' +
        (nombres[estado] || escaparHtml(estado)) +
        '</span>';
}


/*
 * =====================================================
 * DATATABLE
 * =====================================================
 */
function initTablaTicketsSistemas() {

    tablaTicketsSistemas = $('#tabla-tickets-sistemas').DataTable({
        ajax: {
            url: '../../controller/tickets_sistemas.php?op=misTickets',
            type: 'GET',
            dataSrc: function (respuesta) {

                if (!respuesta.success) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: respuesta.message || 'No fue posible consultar los tickets.'
                    });

                    return [];
                }

                const tickets = Array.isArray(respuesta.data)
                    ? respuesta.data
                    : [];

                actualizarResumenTickets(tickets);

                return tickets;
            },
            error: function (xhr) {
                console.log(xhr.responseText);

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No fue posible cargar los tickets.'
                });
            }
        },

        columns: [
            {
                data: 'ticket_numero',
                render: function (data) {
                    return '<strong>' + escaparHtml(data) + '</strong>';
                }
            },
            {
                data: 'asunto',
                render: function (data, type, row) {

                    if (type !== 'display') {
                        return data;
                    }

                    return '<strong>' + escaparHtml(data) + '</strong>' +
                        '<br>' +
                        '<small class="text-muted">' +
                        escaparHtml(row.tipo) +
                        '</small>';
                }
            },
            {
                data: 'categoria'
            },
            {
                data: 'prioridad',
                render: function (data, type) {

                    if (type !== 'display') {
                        return data;
                    }

                    return badgePrioridadTicket(data);
                }
            },
            {
                data: 'estado',
                render: function (data, type) {

                    if (type !== 'display') {
                        return data;
                    }

                    return badgeEstadoTicket(data);
                }
            },
            {
                data: 'fecha_creacion',
                render: function (data) {
                    return formatearFechaTicket(data);
                }
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: function (data, type, row) {

                    return `
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-primary btn-ver-ticket"
                            data-id="${parseInt(row.ticket_id, 10)}"
                            title="Ver ticket"
                        >
                            <i class="fas fa-eye"></i>
                        </button>
                    `;
                }
            }
        ],

        dom: 'lrtip',
        order: [[5, 'desc']],
        responsive: true,
        autoWidth: false,
        processing: true,
        pageLength: 10,

        language: {
            processing: 'Cargando...',
            lengthMenu: 'Mostrar _MENU_ registros',
            zeroRecords: 'No se encontraron tickets',
            emptyTable: 'No tienes tickets registrados',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ tickets',
            infoEmpty: 'Mostrando 0 tickets',
            infoFiltered: '(filtrado de _MAX_ registros)',
            search: 'Buscar:',
            paginate: {
                first: 'Primero',
                last: 'Último',
                next: 'Siguiente',
                previous: 'Anterior'
            }
        }
    });
}

function actualizarResumenTickets(tickets) {

    let abiertos = 0;
    let proceso = 0;
    let finalizados = 0;

    tickets.forEach(function (ticket) {

        switch (ticket.estado) {

            case 'ABIERTO':
                abiertos++;
                break;

            case 'EN_PROCESO':
            case 'EN_ESPERA':
                proceso++;
                break;

            case 'RESUELTO':
            case 'CERRADO':
            case 'CANCELADO':
                finalizados++;
                break;
        }
    });

    $('#total-tickets-abiertos').text(abiertos);
    $('#total-tickets-proceso').text(proceso);
    $('#total-tickets-finalizados').text(finalizados);
}


/*
 * =====================================================
 * EVENTOS
 * =====================================================
 */
function initEventosTicketsSistemas() {

    /*
     * Abrir modal.
     */
    $(document).on(
        'click',
        '#btn-nuevo-ticket',
        function () {

            limpiarFormularioTicket();

        }
    );


    /*
     * Buscar dentro de la tabla.
     */
    $(document).on(
        'keyup',
        '#filtro-buscar',
        function () {

            if (!tablaTicketsSistemas) {
                return;
            }

            tablaTicketsSistemas
                .search(
                    $(this).val()
                )
                .draw();

        }
    );


    /*
     * Filtrar por estado.
     *
     * Columna 4 corresponde a Estado.
     */
    $(document).on(
        'change',
        '#filtro-estado',
        function () {

            if (!tablaTicketsSistemas) {
                return;
            }

            const estado =
                $(this).val() || '';

            tablaTicketsSistemas
                .column(4)
                .search(
                    estado,
                    false,
                    false
                )
                .draw();

        }
    );


    /*
     * Limpiar filtros.
     */
    $(document).on(
        'click',
        '#btn-limpiar-filtros',
        function () {

            limpiarFiltrosTickets();

        }
    );


    /*
     * Envío temporal del formulario.
     *
     * Todavía NO consume la API.
     * En el siguiente paso reemplazaremos
     * este bloque por AJAX.
     */
    /*
    * =====================================================
    * CREAR TICKET
    * =====================================================
    */
    $(document).on('submit', '#form-ticket-sistemas', function (event) {
        event.preventDefault();

        if (!validarFormularioTicket()) {
            return;
        }

        crearTicketSistemas();
    });


    /*
     * Cuando cierre el modal,
     * limpiar formulario.
     */
    $('#modal-nuevo-ticket').on(
        'hidden.bs.modal',
        function () {

            limpiarFormularioTicket();

        }
    );

    // Conserva el formulario y sus archivos mientras se envía el ticket.
    $('#modal-nuevo-ticket').on('hide.bs.modal', function (event) {
        if (enviandoTicket || preparandoSoportesTicket || eliminandoSoportesTicket > 0
            || (soportesTicket && (soportesTicket.getUploadingFiles().length > 0 || soportesTicket.getQueuedFiles().length > 0))) {
            event.preventDefault();
        }
    });
    $('#modal-nuevo-ticket').on('show.bs.modal', prepararSoportesTicket);

    $('#filtro-buscar').on('keyup', function () {

        if (tablaTicketsSistemas) {
            tablaTicketsSistemas
                .search($(this).val())
                .draw();
        }
    });


    $('#filtro-estado').on('change', function () {

        if (!tablaTicketsSistemas) {
            return;
        }

        const estado = $(this).val();

        if (estado === '') {

            tablaTicketsSistemas
                .column(4)
                .search('')
                .draw();

            return;
        }

        tablaTicketsSistemas
            .column(4)
            .search(
                '^' +
                $.fn.dataTable.util.escapeRegex(estado) +
                '$',
                true,
                false
            )
            .draw();
    });


    $('#btn-limpiar-filtros').on('click', function () {

        $('#filtro-buscar').val('');

        $('#filtro-estado')
            .val('')
            .trigger('change');

        if (tablaTicketsSistemas) {
            tablaTicketsSistemas
                .search('')
                .columns()
                .search('')
                .draw();
        }
    });

}


/*
 * =====================================================
 * VALIDAR FORMULARIO
 * =====================================================
 */
function validarFormularioTicket() {

    const tipo =
        $('#ticket-tipo').val();

    const categoria =
        $('#ticket-categoria').val();

    const prioridad =
        $('#ticket-prioridad').val();

    const asunto =
        $.trim(
            $('#ticket-asunto').val()
        );

    const descripcion =
        $.trim(
            $('#ticket-descripcion').val()
        );


    if (!tipo) {

        Swal.fire({

            icon: 'warning',

            title: 'Tipo requerido',

            text:
                'Seleccione el tipo de solicitud.'

        });

        return false;

    }


    if (!categoria) {

        Swal.fire({

            icon: 'warning',

            title: 'Categoría requerida',

            text:
                'Seleccione una categoría.'

        });

        return false;

    }


    if (!prioridad) {

        Swal.fire({

            icon: 'warning',

            title: 'Prioridad requerida',

            text:
                'Seleccione la prioridad.'

        });

        return false;

    }


    if (asunto === '') {

        Swal.fire({

            icon: 'warning',

            title: 'Asunto requerido',

            text:
                'Escriba un asunto breve para el ticket.'

        });

        $('#ticket-asunto').focus();

        return false;

    }


    if (descripcion === '') {

        Swal.fire({

            icon: 'warning',

            title: 'Descripción requerida',

            text:
                'Describe la situación que necesitas reportar.'

        });

        $('#ticket-descripcion').focus();

        return false;

    }


    return true;

}


/*
 * =====================================================
 * LIMPIAR FORMULARIO
 * =====================================================
 */
function limpiarFormularioTicket() {
    // Conserva los archivos y formulario al cerrar para permitir retomar o retirarlos.
    if (soportesTicket && soportesTicket.files.length > 0) { return; }
    $('#ticket-soporte-token').val('');

    const formulario =
        document.getElementById(
            'form-ticket-sistemas'
        );

    if (formulario) {

        formulario.reset();

    }


    $('#ticket-tipo')
        .val('')
        .trigger('change');


    $('#ticket-categoria')
        .val('')
        .trigger('change');


    $('#ticket-prioridad')
        .val('MEDIA')
        .trigger('change');


    $('#ticket-asunto')
        .val('');


    $('#ticket-descripcion')
        .val('');


    $('#ticket-ubicacion')
        .val('')
        .trigger('change');

}


/*
 * =====================================================
 * LIMPIAR FILTROS
 * =====================================================
 */
function limpiarFiltrosTickets() {

    $('#filtro-estado')
        .val('')
        .trigger('change');


    $('#filtro-buscar')
        .val('');


    if (tablaTicketsSistemas) {

        tablaTicketsSistemas
            .search('')
            .columns()
            .search('')
            .draw();

    }

}

/*
 * =====================================================
 * CARGAR EMPLEADO AUTENTICADO
 * =====================================================
 */
function cargarEmpleadoTicketsSistemas() {
    $.ajax({
        url: '../../controller/tickets_sistemas.php?op=empleado',
        type: 'GET',
        dataType: 'json',

        success: function (respuesta) {
            if (!respuesta.success || !respuesta.data) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: respuesta.message || 'No fue posible consultar la información del empleado.'
                });

                return;
            }

            const empleado = respuesta.data;

            $('#empleado-nombre').text(
                empleado.nombre || 'No registrado'
            );

            $('#empleado-documento').text(
                empleado.documento || 'No registrado'
            );

            $('#empleado-correo').text(
                empleado.correo || 'No registrado'
            );

            $('#empleado-cargo').text(
                empleado.cargo || 'No registrado'
            );

        },

        error: function (xhr) {
            console.log(xhr.responseText);

            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No fue posible cargar la información del empleado.'
            });
        }
    });
}


/*
 * =====================================================
 * CARGAR CATEGORIAS
 * =====================================================
 */
function cargarCategoriasTicketsSistemas() {
    $.ajax({
        url: '../../controller/tickets_sistemas.php?op=categorias',
        type: 'GET',
        dataType: 'json',

        success: function (respuesta) {
            if (!respuesta.success || !Array.isArray(respuesta.data)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: respuesta.message || 'No fue posible consultar las categorías.'
                });

                return;
            }

            let opciones =
                '<option value="">Seleccione una categoría</option>';

            respuesta.data.forEach(function (categoria) {
                opciones +=
                    '<option value="' +
                    categoria.categoria_id +
                    '">' +
                    categoria.nombre +
                    '</option>';
            });

            $('#ticket-categoria')
                .html(opciones)
                .val('')
                .trigger('change');
        },

        error: function (xhr) {
            console.log(xhr.responseText);

            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No fue posible cargar las categorías de la Mesa de Servicio.'
            });
        }
    });
}

$(document).on(
    'click',
    '.btn-ver-ticket',
    function () {

        const ticketId = parseInt(
            $(this).data('id'),
            10
        );

        if (!ticketId) {
            return;
        }

        consultarDetalleTicket(
            ticketId
        );
    }
);

/*
 * =====================================================
 * ENVIAR TICKET
 * =====================================================
 */
function crearTicketSistemas() {

    // Espera confirmación de todas las cargas y eliminaciones antes de crear.
    if (enviandoTicket) { return; }
    if (preparandoSoportesTicket || !$('#ticket-soporte-token').val()
        || eliminandoSoportesTicket > 0 || soportesTicket.files.some(function (file) {
            return file.status !== Dropzone.SUCCESS;
        })) {
        Swal.fire('Soportes pendientes', 'Espere la carga de los archivos o retire los que tienen error.', 'warning');
        return;
    }
    enviandoTicket = true;
    soportesTicket.disable();

    const boton = $('#btn-guardar-ticket');

    boton
        .prop('disabled', true)
        .html(
            '<i class="fas fa-spinner fa-spin"></i> Enviando...'
        );

    $.ajax({
        url: '../../controller/tickets_sistemas.php?op=crear',
        type: 'POST',
        dataType: 'json',
        data: $('#form-ticket-sistemas').serialize(),

        success: function (respuesta) {

            if (!respuesta.success) {

                Swal.fire({
                    icon: 'warning',
                    title: 'No fue posible crear el ticket',
                    text: respuesta.message || 'Verifica la información e intenta nuevamente.'
                });

                return;
            }

            const ticket = respuesta.data || {};

            // Evita solicitar la eliminación de archivos ya asociados al ticket.
            soportesTicket.files.forEach(function (file) { file.soporteAsociado = true; });
            soportesTicket.removeAllFiles(true);
            enviandoTicket = false;

            $('#modal-nuevo-ticket').modal('hide');

            $('#form-ticket-sistemas')[0].reset();

            $('#ticket-tipo')
                .val('')
                .trigger('change');

            $('#ticket-categoria')
                .val('')
                .trigger('change');

            $('#ticket-prioridad')
                .val('')
                .trigger('change');

            if (tablaTicketsSistemas) {
                tablaTicketsSistemas.ajax.reload(
                    null,
                    false
                );
            }

            Swal.fire({
                icon: 'success',
                title: 'Ticket registrado',
                html:
                    'Tu solicitud fue registrada correctamente.' +
                    (
                        ticket.ticket_numero
                            ? '<br><br><strong>' + ticket.ticket_numero + '</strong>'
                            : ''
                    ),
                confirmButtonText: 'Aceptar'
            });

        },

        error: function (xhr) {

            console.log(xhr.responseText);

            let mensaje =
                'Ocurrió un error registrando el ticket.';

            if (
                xhr.responseJSON &&
                xhr.responseJSON.message
            ) {
                mensaje =
                    xhr.responseJSON.message;
            }

            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: mensaje
            });

        },

        complete: function () {
            enviandoTicket = false;
            soportesTicket.enable();

            boton
                .prop('disabled', false)
                .html(
                    '<i class="fas fa-paper-plane"></i> Enviar ticket'
                );

        }
    });
}

/*
 * =====================================================
 * CONSULTAR DETALLE DEL TICKET
 * =====================================================
 */
function consultarDetalleTicket(ticketId) {

    Swal.fire({
        title: 'Consultando ticket...',
        allowOutsideClick: false,
        didOpen: function () {
            Swal.showLoading();
        }
    });

    $.ajax({
        url: '../../controller/tickets_sistemas.php?op=detalle',
        type: 'GET',
        dataType: 'json',

        data: {
            id: ticketId
        },

        success: function (respuesta) {

            Swal.close();

            if (
                !respuesta.success
                || !respuesta.data
                || !respuesta.data.ticket
            ) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No fue posible consultar',
                    text:
                        respuesta.message
                        || 'No fue posible consultar el ticket.'
                });

                return;
            }

            mostrarDetalleTicket(
                respuesta.data
            );
        },

        error: function (xhr) {

            Swal.close();

            let mensaje =
                'No fue posible consultar el ticket.';

            if (
                xhr.responseJSON
                && xhr.responseJSON.message
            ) {
                mensaje =
                    xhr.responseJSON.message;
            }

            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: mensaje
            });
        }
    });
}

function mostrarDetalleTicket(data) {

    const ticket = data.ticket;
    cargarSoportesTicket(ticket.ticket_id);
    const seguimientos = Array.isArray(data.seguimientos)
        ? data.seguimientos
        : [];

    $('#detalle-ticket-numero').text(
        ticket.ticket_numero || 'Ticket'
    );

    $('#detalle-ticket-asunto').text(
        ticket.asunto || '-'
    );

    $('#detalle-ticket-tipo').text(
        ticket.tipo || '-'
    );

    $('#detalle-ticket-categoria').text(
        ticket.categoria || '-'
    );

    $('#detalle-ticket-estado').html(
        badgeEstadoTicket(
            ticket.estado
        )
    );

    $('#detalle-ticket-prioridad').html(
        badgePrioridadTicket(
            ticket.prioridad
        )
    );

    $('#detalle-ticket-descripcion').text(
        ticket.descripcion || 'Sin descripción.'
    );

    $('#detalle-ticket-ubicacion').text(
        ticket.ubicacion || 'No registrada'
    );

    $('#detalle-ticket-solucion').text(
        ticket.solucion
            ? ticket.solucion
            : 'Aún no se ha registrado una solución.'
    );

    renderizarSeguimientosTicket(
        seguimientos
    );

    $('#modal-detalle-ticket').modal(
        'show'
    );
}

function renderizarSeguimientosTicket(seguimientos) {

    const contenedor =
        $('#detalle-ticket-seguimientos');

    contenedor.empty();

    if (seguimientos.length === 0) {

        contenedor.html(
            '<p class="text-muted mb-0">' +
            'No hay seguimientos registrados.' +
            '</p>'
        );

        return;
    }

    seguimientos.forEach(function (seguimiento) {

        let cambioEstado = '';

        if (
            seguimiento.estado_nuevo
        ) {
            cambioEstado =
                '<div class="mt-1">' +
                    '<small class="text-muted">' +
                        'Estado: ' +
                        escaparHtml(
                            seguimiento.estado_nuevo
                        ) +
                    '</small>' +
                '</div>';
        }

        const html =
            '<div class="border rounded p-3 mb-2">' +

                '<div class="d-flex justify-content-between">' +

                    '<strong>' +
                        escaparHtml(
                            seguimiento.tipo || 'Seguimiento'
                        ) +
                    '</strong>' +

                    '<small class="text-muted">' +
                        escaparHtml(
                            formatearFechaTicket(
                                seguimiento.fecha_creacion
                            )
                        ) +
                    '</small>' +

                '</div>' +

                '<div class="mt-2">' +
                    escaparHtml(
                        seguimiento.comentario
                        || 'Sin comentario.'
                    ) +
                '</div>' +

                cambioEstado +

            '</div>';

        contenedor.append(
            html
        );
    });
}


/*
 * =====================================================
 * INICIAR
 * =====================================================
 */

// Prepara un token temporal sin incluir identidad del empleado en el navegador.
function prepararSoportesTicket() {
    if ($('#ticket-soporte-token').val() || preparandoSoportesTicket) { return; }
    preparandoSoportesTicket = true;
    soportesTicket.disable();
    $.ajax({
        url: '../../controller/tickets_sistemas.php?op=prepararSoportes',
        type: 'POST', dataType: 'json'
    }).done(function (respuesta) {
        if (!respuesta.success || !respuesta.data || !respuesta.data.soporte_token) {
            Swal.fire('Error', respuesta.message || 'No fue posible preparar los soportes.', 'error');
            return;
        }
        $('#ticket-soporte-token').val(respuesta.data.soporte_token);
    }).fail(function () {
        Swal.fire('Error', 'No fue posible preparar los soportes. Cierre y abra Nuevo ticket.', 'error');
    }).always(function () {
        preparandoSoportesTicket = false;
        if ($('#ticket-soporte-token').val()) { soportesTicket.enable(); }
    });
}

// Inicializa Dropzone con el mismo tipo y tamaño de archivos que permisos.
function initSoportesTicketsSistemas() {
    soportesTicket = new Dropzone('#ticket-soportes', {
        url: '../../controller/tickets_sistemas.php?op=subirSoporte',
        maxFilesize: 10, maxFiles: 10, parallelUploads: 1,
        acceptedFiles: '.jpg,.jpeg,.png,.pdf', addRemoveLinks: false,
        dictRemoveFile: 'Eliminar', dictCancelUpload: 'Cancelar carga',
        dictMaxFilesExceeded: 'Puede adjuntar hasta 10 archivos.',
        dictInvalidFileType: 'Solo se permiten JPG, PNG y PDF.',
        dictFileTooBig: 'El archivo supera el máximo de 10 MB.',
        // La eliminación se confirma en servidor antes de retirar la vista previa.
        removedfile: function (file) {
            const dz = this;
            if (!file.soporteId || file.soporteAsociado) {
                if (file.previewElement) { file.previewElement.remove(); }
                return;
            }
            dz.files.push(file);
            if (file.eliminando) { return; }
            file.eliminando = true;
            eliminandoSoportesTicket++;
            $.ajax({
                url: '../../controller/tickets_sistemas.php?op=eliminarSoporte',
                type: 'POST', dataType: 'json',
                data: { soporte_id: file.soporteId, soporte_token: file.soporteToken }
            }).done(function (respuesta) {
                if (!respuesta.success) {
                    Swal.fire('Error', respuesta.message || 'No fue posible eliminar el soporte.', 'error');
                    return;
                }
                file.soporteId = null;
                dz.removeFile(file);
            }).fail(function (xhr) {
                Swal.fire('Error', (xhr.responseJSON || {}).message || 'No fue posible eliminar el soporte.', 'error');
            }).always(function () {
                file.eliminando = false;
                eliminandoSoportesTicket--;
            });
        },
        init: function () {
            // Impide cancelar una petición que podría haberse guardado en el servidor.
            this.on('addedfile', function (file) {
                const dz = this;
                file.botonEliminar = $('<button type="button" class="btn btn-sm btn-outline-danger mt-2">')
                    .text('Eliminar').on('click', function () {
                        if (!enviandoTicket && file.status !== Dropzone.UPLOADING) { dz.removeFile(file); }
                    }).appendTo(file.previewElement);
            });
            this.on('sending', function (file, xhr, formData) {
                file.botonEliminar.prop('disabled', true);
                file.soporteToken = $('#ticket-soporte-token').val();
                formData.append('soporte_token', file.soporteToken);
            });
            this.on('success', function (file, respuesta) {
                if (typeof respuesta === 'string') {
                    try { respuesta = JSON.parse(respuesta); } catch (e) { respuesta = {}; }
                }
                if (!respuesta.success || !respuesta.data || !respuesta.data.soporte_id) {
                    file.status = Dropzone.ERROR;
                    this.emit('error', file, respuesta.message || 'No se confirmó la carga del archivo.');
                    return;
                }
                file.soporteId = respuesta.data.soporte_id;
                file.botonEliminar.prop('disabled', false);
            });
            this.on('error', function (file, mensaje) {
                if (file.botonEliminar) { file.botonEliminar.prop('disabled', false); }
                Swal.fire('Error de soporte', typeof mensaje === 'string' ? mensaje
                    : (mensaje.message || 'No fue posible cargar el archivo.'), 'error');
            });
        }
    });
    soportesTicket.disable();
}

// Consulta los soportes sin depender de cambios en la respuesta del detalle.
function cargarSoportesTicket(ticketId) {
    if (cargaSoportesTicket) { cargaSoportesTicket.abort(); }
    const lista = $('#detalle-ticket-soportes').empty();
    $('<li class="list-group-item text-muted">').text('Consultando soportes...').appendTo(lista);
    cargaSoportesTicket = $.ajax({
        url: '../../controller/tickets_sistemas.php?op=listarSoportes',
        type: 'POST', dataType: 'json', data: { ticket_id: ticketId }
    }).done(function (respuesta) {
        lista.empty();
        if (!respuesta.success || !Array.isArray(respuesta.data)) {
            $('<li class="list-group-item text-danger">').text(respuesta.message || 'No fue posible consultar soportes.').appendTo(lista);
            return;
        }
        if (!respuesta.data.length) {
            $('<li class="list-group-item text-muted">').text('Sin soportes adjuntos.').appendTo(lista);
        }
        respuesta.data.forEach(function (soporte) {
            const fila = $('<li class="list-group-item d-flex justify-content-between align-items-center">').appendTo(lista);
            $('<span>').text(soporte.soporte_nombre).appendTo(fila);
            $('<button type="button" class="btn btn-sm btn-outline-primary">').text('Descargar')
                .on('click', function () { descargarSoporteTicket(soporte.soporte_id, $(this)); }).appendTo(fila);
        });
    }).fail(function (xhr, estado) {
        if (estado !== 'abort') { lista.empty().append($('<li class="list-group-item text-danger">').text('No fue posible consultar soportes.')); }
    });
}

// Genera una descarga local después de recibir el JSON autorizado del controlador.
function descargarSoporteTicket(id, boton) {
    boton.prop('disabled', true);
    $.ajax({
        url: '../../controller/tickets_sistemas.php?op=descargarSoporte',
        type: 'POST', dataType: 'json', data: { soporte_id: id }
    }).done(function (respuesta) {
        if (!respuesta.success || !respuesta.data) {
            Swal.fire('Error', respuesta.message || 'No fue posible descargar el soporte.', 'error');
            return;
        }
        const soporte = respuesta.data;
        const bytes = Uint8Array.from(atob(soporte.contenido), function (c) { return c.charCodeAt(0); });
        const url = URL.createObjectURL(new Blob([bytes], { type: soporte.soporte_mime }));
        const enlace = document.createElement('a');
        enlace.href = url;
        enlace.download = soporte.soporte_nombre;
        document.body.appendChild(enlace);
        enlace.click();
        enlace.remove();
        setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
    }).fail(function (xhr) {
        Swal.fire('Error', (xhr.responseJSON || {}).message || 'No fue posible descargar el soporte.', 'error');
    }).always(function () { boton.prop('disabled', false); });
}

init();
