let tablaOpen = null;

let fecha_desde = null;
let fecha_hasta = null;


function init() {
    initDateRangePicker();
    inicializarTabla();
    aplicarFiltros();
}

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

                        $("#tableSolicitudes").DataTable().ajax.reload();
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
                        $("#tableSolicitudes").DataTable().ajax.reload();

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


/*====================================

LISTAR APROBACIONES JEFE

=====================================*/

// ── DateRangePicker ────────────────────────────────────
function initDateRangePicker() {
    $('#filtro_fechas').daterangepicker({
        autoUpdateInput: false,
        locale: {
            cancelLabel : 'Limpiar',
            applyLabel  : 'Aplicar',
            format      : 'DD/MM/YYYY',
            daysOfWeek  : ['Do','Lu','Ma','Mi','Ju','Vi','Sa'],
            monthNames  : ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                           'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'],
            firstDay    : 1
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

// ── Aplicar filtros ────────────────────────────────────
function aplicarFiltros() {
    const estado = $('.carpeta-item.active').data('estado') ?? '';
    const busqueda = $('#filtro_busqueda').val().trim();

    $.ajax({
        url: '../../controller/permiso.php?op=listarConFiltros',
        type: 'POST',
        data: {
            busqueda: busqueda,
            fecha_desde: fecha_desde ?? '',
            fecha_hasta: fecha_hasta ?? '',
            estado: estado
        },
        success: function (datos) {
            try {
                const lista = JSON.parse(datos.trim());
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
        const badgeClass = p.permiso_estado == '1' ? 'badge-pendiente'
            : p.permiso_estado == '2' ? 'badge-aprobado'
                : 'badge-rechazado';
        const badgeText = p.permiso_estado == '1' ? 'Pendiente'
            : p.permiso_estado == '2' ? 'Aprobado'
                : 'Rechazado';

        const fecha = p.permiso_fecha
            ? new Date(p.permiso_fecha + 'T00:00:00').toLocaleDateString('es-CO')
            : '—';

        $lista.append(`
            <div class="solicitud-item" data-id="${p.permiso_id}">
                <div class="d-flex justify-content-between align-items-start">
                    <span class="nombre">${p.nombre_empleado}</span>
                    <span class="${badgeClass}">${badgeText}</span>
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



init();