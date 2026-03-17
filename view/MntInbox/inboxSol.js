let tablaOpen = null;

let fecha_desde = null;
let fecha_hasta = null;


function init() {
    recargar();
    initDateRangePicker();

}
/* 
$('#abiertos-btn').on('click', function () {
    cargarSolicitudesP('Pendientes');
    //if (tablaRevision) tablaRevision.clear().destroy();
});


function inicializarTabla() {
    if (tablaOpen) {
        tablaOpen.clear(); // Limpiar la tabla existente
    } else {
        tablaOpen = $('#tableSolicitudes').DataTable({
            "aProcessing": true,
            "aServerSide": true,
            "searching": false,
            "lengthChange": false,
            "colReorder": true,
            "responsive": true,
            "bInfo": true,
            "iDisplayLength": 7,
            "autoWidth": false,
            "language": {
                "sProcessing": "Procesando...",
                "sLengthMenu": "Mostrar _MENU_ registros",
                "sZeroRecords": "No se encontraron resultados",
                "sEmptyTable": "Ningún dato disponible en esta tabla",
                "sInfo": "Mostrando un total de _TOTAL_ registros",
                "sInfoEmpty": "Mostrando un total de 0 registros",
                "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
                "sSearch": "Buscar:",
                "oPaginate": {
                    "sFirst": "Primero",
                    "sLast": "Último",
                    "sNext": "Siguiente",
                    "sPrevious": "Anterior"
                }
            },
            "ajax": {
                url: '../../controller/permiso.php?op=listarSolicitudesPendientes',
                type: "POST",
                dataType: "json",
                success: function (response) {
                    console.log(response);

                    if (response && response.aaData) {
                        tablaOpen.clear().rows.add(response.aaData).draw(); // Añadir los datos a la tabla
                    }
                },
                error: function (e) {
                    console.log(e.responseText);
                }
            }
        });
    }
}

function cargarSolicitudesP(tipo = 'Pendientes') {
    const nuevaURL = `../../controller/tickets.php?op=listarSolicitudes${tipo}`;
    if (tablaOpen) {
        tablaOpen.ajax.url(nuevaURL).load(); // recarga datos sin reiniciar tabla
    } else {
        inicializarTabla();
    }
}
 */

/*====================================

LISTAR APROBACIONES JEFE

=====================================*/

// ── DateRangePicker ────────────────────────────────────
function initDateRangePicker() {
    $('#filtro_fechas').daterangepicker({
        autoUpdateInput: false,
        locale: {
            cancelLabel: 'Limpiar',
            applyLabel: 'Aplicar',
            format: 'DD/MM/YYYY',
            daysOfWeek: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'],
            monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
            firstDay: 1
        }
    })
        .on('apply.daterangepicker', function (ev, picker) {
            $(this).val(
                picker.startDate.format('DD/MM/YYYY') + ' — ' +
                picker.endDate.format('DD/MM/YYYY')
            );
            fecha_desde = picker.startDate.format('YYYY-MM-DD');
            fecha_hasta = picker.endDate.format('YYYY-MM-DD');
        })
        .on('cancel.daterangepicker', function () {
            $(this).val('');
            fecha_desde = null;
            fecha_hasta = null;
        });
}

// ── Helper estado ──────────────────────────────────────
function getEstadoPermiso(estado) {
    const estados = {
        '1': { texto: 'Pendiente', badge: 'badge-warning', icono: 'fas fa-clock' },
        '2': { texto: 'Aprobado', badge: 'badge-info', icono: 'fas fa-user-check' },
        '3': { texto: 'Vbo. Gestión Humana', badge: 'badge-orange', icono: 'fas fa-user-tie', style: 'background-color:#fd7e14; color:#000;' },
        '4': { texto: 'Aprobado c/pendientes', badge: 'badge-secondary', icono: 'fas fa-check-circle', style: 'background-color:#6f42c1; color:#000;' },
        '5': { texto: 'Aprobado c/pendientes', badge: 'badge-secondary', icono: 'fas fa-check-circle', style: 'background-color:#6f42c1; color:#000;' },
        '6': { texto: 'Rechazado', badge: 'badge-danger', icono: 'fas fa-times-circle' },
        '7': { texto: 'Cancelado Operación', badge: 'badge-dark', icono: 'fas fa-ban' },
    };
    return estados[estado] ?? { texto: 'Desconocido', badge: 'badge-secondary', icono: 'fas fa-question' };
}

// ── Aplicar filtros ────────────────────────────────────
function aplicarFiltros() {
    const carpetaActiva  = $('.carpeta-item.active').data('estado') ?? '';
    const busqueda = $('#filtro_busqueda').val().trim();

    // Mapear carpeta a estados reales
    let estados = '';
    if (carpetaActiva == '1')  estados = '1';
    if (carpetaActiva == '2')  estados = '2,3,4,5'; // ← Aprobados agrupa varios
    if (carpetaActiva == '6')  estados = '6';
    if (carpetaActiva === '')  estados = '';          // Todos

    $.ajax({
        url: '../../controller/permiso.php?op=listarConFiltros',
        type: 'POST',
        data: {
            busqueda: busqueda,
            fecha_desde: fecha_desde ?? '',
            fecha_hasta: fecha_hasta ?? '',
            estados: estados
        },
        success: function (datos) {
            try {
                const lista = JSON.parse(datos.trim());
                console.log(lista);
                renderLista(lista);
            } catch (e) {
                console.error('Error listarConFiltros:', e);
                console.log('Raw:', datos);
            }
        }
    });
}

// ── Render lista ───────────────────────────────────────
function renderLista(lista) {
    const $lista = $('#lista-solicitudes');
    $lista.empty();

    $('#lista-contador').text(lista.length + ' solicitud' + (lista.length !== 1 ? 'es' : ''));

    if (lista.length === 0) {
        $lista.html(`
            <div class="text-center text-muted py-4">
                <i class="fas fa-inbox fa-2x mb-2 d-block" style="opacity:0.3;"></i>
                Sin solicitudes
            </div>`);
        return;
    }

    lista.forEach(function (p) {

        const est   = getEstadoPermiso(p.permiso_estado);


        const fecha = p.permiso_fecha
            ? new Date(p.permiso_fecha + 'T00:00:00').toLocaleDateString('es-CO')
            : '—';

        $lista.append(`
            <div class="solicitud-item" data-id="${p.permiso_id}">
                <div class="d-flex justify-content-between align-items-start">
                    <span class="nombre">${p.nombre_empleado}</span>
                    <span class="badge ${est.badge} ml-1" style="${est.style ?? ''}"; white-space:normal; text-align:right; max-width:100px;">
                        ${est.texto}
                    </span>
                </div>
                <div class="meta">${p.cedula_empleado}</div>
                <div class="meta">
                    <i class="fas fa-calendar-alt mr-1"></i>${fecha}
                    &nbsp;·&nbsp;
                    <i class="fas fa-tag mr-1"></i>${p.tipo_permiso ?? '—'}
                </div>
            </div>`);
    });
}

// ── Cargar conteos carpetas ────────────────────────────
function cargarConteos() {
    $.ajax({
        url: '../../controller/permiso.php?op=contarPorEstado',
        type: 'POST',
        success: function (datos) {
            try {
                const r = JSON.parse(datos.trim());
                $('#badge-pendientes').text(r.pendientes);
                $('#badge-aprobados').text(r.aprobados);
                $('#badge-rechazados').text(r.rechazados);
                $('#badge-todos').text(r.total);
            } catch (e) {
                console.error('Error conteos:', e);
            }
        }
    });
}

// ── Clic en item — cargar detalle ──────────────────────
$(document).on('click', '.solicitud-item', function () {
    $('.solicitud-item').removeClass('active');
    $(this).addClass('active');

    const permiso_id = $(this).data('id');

    $.ajax({
        url: '../../controller/permiso.php?op=getPermiso',
        type: 'POST',
        data: { permiso_id: permiso_id },
        success: function (datos) {
            try {

                const p = JSON.parse(datos.trim());
                console.log(p);
                permiso_activo = p;
                renderDetalle(p);

                // Scroll al detalle en móvil
                if ($(window).width() <= 768) {
                    $('html, body').animate({
                        scrollTop: $('#panel-detalle').offset().top - 60
                    }, 300);
                }
            } catch (e) {
                console.error('Error getPermiso:', e);
            }
        }
    });
});

// ── Render detalle ─────────────────────────────────────
function renderDetalle(p) {
    const est   = getEstadoPermiso(p.permiso_estado);

    const fecha = p.permiso_fecha
        ? new Date(p.permiso_fecha + 'T00:00:00').toLocaleDateString('es-CO') : '—';
    const creado = p.permiso_creado
        ? new Date(p.permiso_creado).toLocaleString('es-CO') : '—';

    // Botones — solo si pendiente
    const btnAcciones = p.permiso_estado == '1' ? `
        <div class="card-footer d-flex align-items-center" style="gap:8px;">
            <button class="btn btn-success btn-sm" onclick="aprobar(${p.permiso_id})">
                <i class="fas fa-check mr-1"></i>Aprobar Permiso
            </button>
            <button class="btn btn-danger btn-sm" onclick="rechazar(${p.permiso_id})">
                <i class="fas fa-times mr-1"></i>Rechazar
            </button>
            <small class="text-muted ml-auto">
                <i class="fas fa-info-circle mr-1"></i>Al aprobar se solicitará su firma digital
            </small>
        </div>` : '';

    // Motivo rechazo
    const motivoRechazo = p.permiso_estado == '6' && p.rechazo_permiso ? `
        <div class="alert alert-warning mt-3 mb-0 py-2">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            <strong>Motivo de rechazo:</strong> ${p.rechazo_permiso}
        </div>` : '';

    $('#panel-detalle').html(`
        <div class="card card-outline card-dark mb-0">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-0">
                        <i class="fas fa-file-alt mr-2 text-dark"></i>Solicitud de Permiso
                    </h5>
                    <small class="text-muted ml-2">Solicitado el ${creado}</small>
                </div>
                <span class="badge ${est.badge}" style="${est.style ?? ''}">
                    <i class="${est.icono} mr-1"></i>${est.texto}
                </span>
            </div>

            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <small class="text-uppercase text-muted font-weight-bold d-block" style="font-size:10px; letter-spacing:0.5px;">
                            <i class="fas fa-user mr-1"></i>Empleado
                        </small>
                        <span class="font-weight-bold">${p.nombre_empleado}</span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-uppercase text-muted font-weight-bold d-block" style="font-size:10px; letter-spacing:0.5px;">
                            <i class="fas fa-id-card mr-1"></i>Cédula
                        </small>
                        <span>${p.cedula_empleado}</span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-uppercase text-muted font-weight-bold d-block" style="font-size:10px; letter-spacing:0.5px;">
                            <i class="fas fa-tag mr-1"></i>Tipo Permiso
                        </small>
                        <span>${p.tipo_permiso ?? '—'}</span>
                    </div>
                    <div class="col-md-2">
                        <small class="text-uppercase text-muted font-weight-bold d-block" style="font-size:10px; letter-spacing:0.5px;">
                            <i class="fas fa-calendar mr-1"></i>Fecha
                        </small>
                        <span>${fecha}</span>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <small class="text-uppercase text-muted font-weight-bold d-block" style="font-size:10px; letter-spacing:0.5px;">
                            <i class="fas fa-sign-out-alt mr-1"></i>Hora Salida
                        </small>
                        <span>${p.permiso_hora_salida ?? '—'}</span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-uppercase text-muted font-weight-bold d-block" style="font-size:10px; letter-spacing:0.5px;">
                            <i class="fas fa-sign-in-alt mr-1"></i>Hora Entrada
                        </small>
                        <span>${p.permiso_hora_entrada ?? '—'}</span>
                    </div>
                </div>

                <small class="text-uppercase text-muted font-weight-bold d-block mb-2" style="font-size:10px; letter-spacing:0.5px;">
                    <i class="fas fa-comment-alt mr-1"></i>Justificación
                </small>
                <div class="justif-box mb-2">
                    ${p.permiso_detalle ?? 'Sin justificación'}
                </div>

                ${motivoRechazo}
            </div>

            ${btnAcciones}
        </div>`);
}

// ── Recargar después de aprobar/rechazar ───────────────
function recargar() {
    cargarConteos();
    aplicarFiltros();
    $('#panel-detalle').html(`
        <div class="text-center text-muted" style="padding:60px 0;">
            <i class="fas fa-hand-pointer fa-2x mb-2 d-block" style="opacity:0.3;"></i>
            Selecciona una solicitud para ver el detalle
        </div>`);
}

$(document).on('click', '.carpeta-item', function () {
    $('.carpeta-item').removeClass('active');
    $(this).addClass('active');
    $('#filtro_busqueda').val('');
    $('#filtro_fechas').val('');
    fecha_desde = null;
    fecha_hasta = null;
    aplicarFiltros();
});

$('#btn_filtrar').on('click', function () {
    aplicarFiltros();
});

$('#btn_limpiar_filtros').on('click', function () {
    $('#filtro_busqueda').val('');
    $('#filtro_fechas').val('');
    fecha_desde = null;
    fecha_hasta = null;
    aplicarFiltros();
});

$('#filtro_busqueda').on('keydown', function (e) {
    if (e.key === 'Enter') aplicarFiltros();
});

function aprobar(codigo_permiso) {

    Swal.fire({
        title: "¿Estás seguro?",
        text: "¿Deseas aprobar este permiso?",
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#009BA9",
        cancelButtonColor: "#FF0000",
        iconColor: "#FF0000",
        confirmButtonText: "Sí, aprobar",
        cancelButtonText: "Cancelar",
        reverseButtons: true
    }).then((result) => {

        if (result.isConfirmed) {

            $.post(
                "../../controller/permiso.php?op=aprobarPermiso",
                { codigo_permiso: codigo_permiso },
                function (data) {

                    let respuesta = JSON.parse(data);

                    // ========================================
                    // 1️⃣ SI NECESITA FIRMA → ABRIR MODAL
                    // ========================================
                    if (respuesta.need_firma) {

                        // Guardamos el permiso para aprobar después
                        window.codigo_permiso_global = codigo_permiso;

                        Swal.fire({
                            icon: "warning",
                            title: "Firma requerida",
                            text: respuesta.message,
                            confirmButtonText: "Registrar firma"
                        }).then(() => {
                            $("#modalFirmaJefe").modal("show");
                            inicializarFirma();
                        });

                        return; // detener aquí
                    }

                    // ========================================
                    // 2️⃣ APROBADO NORMALMENTE
                    // ========================================
                    if (respuesta.success) {

                        Swal.fire({
                            title: "Aprobado",
                            text: "Permiso aprobado correctamente",
                            icon: "success",
                            timer: 1500,
                            showConfirmButton: false
                        });

                        recargar();
                        return;
                    }

                    // ========================================
                    // 3️⃣ Cualquier otro error
                    // ========================================
                    Swal.fire({
                        title: "Advertencia",
                        text: respuesta.error ?? "No se pudo aprobar el permiso.",
                        icon: "warning"
                    });

                }
            ).fail(function (xhr) {
                Swal.fire("Error", "Error en el servidor.", "error");
                console.log(xhr.responseText);
            });

        }
    });

}


function inicializarFirma() {
    const canvas = document.getElementById("canvasFirma");
    const ctx = canvas.getContext("2d");
    let dibujando = false;

    ctx.lineWidth = 4;
    ctx.strokeStyle = "#000";
    ctx.lineJoin = "round";

    function getPos(e) {
        const rect = canvas.getBoundingClientRect();
        if (e.touches) {
            return {
                x: e.touches[0].clientX - rect.left,
                y: e.touches[0].clientY - rect.top
            };
        } else {
            return {
                x: e.clientX - rect.left,
                y: e.clientY - rect.top
            };
        }
    }

    function iniciarDibujo(e) {
        e.preventDefault();
        dibujando = true;
        const pos = getPos(e);
        ctx.beginPath();
        ctx.moveTo(pos.x, pos.y);
    }

    function dibujar(e) {
        if (!dibujando) return;
        e.preventDefault();
        const pos = getPos(e);
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
    }

    function detenerDibujo(e) {
        e.preventDefault();
        dibujando = false;
    }

    // Eventos mouse
    canvas.addEventListener("mousedown", iniciarDibujo);
    canvas.addEventListener("mousemove", dibujar);
    canvas.addEventListener("mouseup", detenerDibujo);
    canvas.addEventListener("mouseleave", detenerDibujo);

    // Eventos touch
    canvas.addEventListener("touchstart", iniciarDibujo);
    canvas.addEventListener("touchmove", dibujar);
    canvas.addEventListener("touchend", detenerDibujo);
    canvas.addEventListener("touchcancel", detenerDibujo);

    // Limpiar
    $("#btnLimpiarFirma").off("click").on("click", function () {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        $("#previewFirma").html("");
    });

    // Guardar (solo visual por ahora)
    // Guardar firma en input + mostrar preview
    $("#btnGuardarFirma").off("click").on("click", function () {

        const firmaData = canvas.toDataURL("image/png");

        if (!firmaData) {
            Swal.fire("Error", "No se pudo capturar la firma.", "error");
            return;
        }

        // Bloqueo mientras guarda
        Swal.fire({
            title: "Guardando firma...",
            text: "Por favor espere",
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        $.post(
            "../../controller/firma.php?op=guardar",
            { firma_base64: firmaData },
            function (resp) {

                let r = JSON.parse(resp);

                if (r.success) {

                    Swal.fire({
                        icon: "success",
                        title: r.message,
                        timer: 1200,
                        showConfirmButton: false
                    });

                    $("#modalFirmaJefe").modal("hide");

                    // Si venimos de aprobar un permiso,
                    // volvemos a intentar aprobarlo automáticamente
                    if (window.codigo_permiso_global) {
                        aprobar(window.codigo_permiso_global);
                        window.codigo_permiso_global = null;
                    }

                } else {
                    Swal.fire("Error", r.message, "error");
                }
            }
        );
    });
}

function rechazar(codigo_permiso) {

    Swal.fire({
        title: "Rechazar Permiso",
        text: "Escribe el motivo del rechazo:",
        icon: "warning",
        iconColor: "#FF0000",
        input: "textarea",
        inputPlaceholder: "Describa el motivo del rechazo...",
        inputAttributes: {
            "aria-label": "Motivo del rechazo"
        },
        showCancelButton: true,
        confirmButtonColor: "#009BA9",
        cancelButtonColor: "#FF0000",
        confirmButtonText: "Rechazar",
        cancelButtonText: "Cancelar",
        reverseButtons: true,
        inputValidator: (value) => {
            if (!value) {
                return "Debe escribir un motivo para rechazar el permiso.";
            }
        }
    }).then((result) => {

        if (result.isConfirmed) {

            let motivo = result.value;

            $.post(
                "../../controller/permiso.php?op=rechazarPermiso",
                {
                    codigo_permiso: codigo_permiso,
                    motivo_rechazo: motivo
                },
                function (data) {

                    let respuesta = JSON.parse(data);

                    if (respuesta.success) {

                        Swal.fire({
                            title: "Rechazado",
                            text: "El permiso fue rechazado correctamente.",
                            icon: "success",
                            timer: 1500,
                            showConfirmButton: false
                        });

                        // recargar datatable
                        recargar();

                    } else {

                        Swal.fire({
                            title: "Error",
                            text: respuesta.message,
                            icon: "error"
                        });

                    }
                }
            ).fail(function (xhr) {
                Swal.fire("Error", "Error en el servidor.", "error");
                console.log(xhr.responseText);
            });

        }

    });
}


init();