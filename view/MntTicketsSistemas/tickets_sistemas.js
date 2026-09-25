let tablaTicketsSistemas;


/*
 * =====================================================
 * INICIALIZAR MODULO
 * =====================================================
 */
function init() {

    initSelect2TicketsSistemas();

    initTablaTicketsSistemas();

    initEventosTicketsSistemas();

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
        dropdownParent: $('#modal-nuevo-ticket')
    });


    $('#ticket-categoria').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: 'Seleccione una categoría',
        allowClear: true,
        dropdownParent: $('#modal-nuevo-ticket')
    });


    $('#ticket-prioridad').select2({
        theme: 'bootstrap4',
        width: '100%',
        minimumResultsForSearch: Infinity,
        dropdownParent: $('#modal-nuevo-ticket')
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
        .val('');


    $('#ticket-equipo')
        .val('');

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

/*
 * =====================================================
 * ENVIAR TICKET
 * =====================================================
 */
function crearTicketSistemas() {

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
 * INICIAR
 * =====================================================
 */

init();