let tablaOpen;

let fecha_desde_empl = null;
let fecha_hasta_empl = null;

function init(){
    cargarMisPermisos(false);
    initDateRangePickerEmpl();
    inicializarTabla();
}

$('#permisos-btn').on('click', function () {
    cargarSolicitudesP('Jefe');
    //if (tablaRevision) tablaRevision.clear().destroy();
});

function inicializarTabla() {
    if (tablaOpen) {
        tablaOpen.clear(); // Limpiar la tabla existente
    } else {
        tablaOpen = $('#tablaSolcEmpl').DataTable({
            "aProcessing": true,
            "aServerSide": true,
            "searching": false,
            "lengthChange": false,
            "colReorder": true,
            "responsive": true,
            "bInfo": true,
            "iDisplayLength": 7,
            "autoWidth": false,
            "order": [[0, 'DESC']],
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
                url: '../../controller/permiso.php?op=listarSolicitudesJefe',
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


function cargarSolicitudesP(tipo = 'Jefe') {
    const nuevaURL = `../../controller/permisos.php?op=listarSolicitudes${tipo}`;
    if (tablaOpen) {
        tablaOpen.ajax.url(nuevaURL).load(); // recarga datos sin reiniciar tabla
    } else {
        inicializarTabla();
    }
}


var getURLParameter = function(sParam) {
    var sPageURL = decodeURIComponent(window.location.search.substring(1));
    var sURLVariables = sPageURL.split('&');
    var sParameterName;
    for (var i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');
        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : sParameterName[1];
        }
    }
}

function verPermiso(permisoId) {
    window.location.href = BASE_URL + '/view/MntInboxEmpl/detalle_permiso.php?id=' + permisoId; //http://181.204.219.154:3396/preoperacional
}

$(document).ready(function () {
    $("#btnFirma").on("click", function (e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Verificar que el modal existe en esta vista
        if ($("#modalFirmaEmpleado").length > 0) {
            $("#modalFirmaEmpleado").modal("show");
        } else {
            Swal.fire({
                icon: "error",
                title: "Modal no encontrado",
                text: "El modal de firma no está cargado en esta vista."
            });
        }
    });
});

/**===============================================
 * 
 * BUZON MIS PERMISOS
 * 
 */

//Daterangerpicker

// ── DateRangePicker ────────────────────────────────────
function initDateRangePickerEmpl() {
    $('#filtro_fechas_empl').daterangepicker({
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
        fecha_desde_empl = picker.startDate.format('YYYY-MM-DD');
        fecha_hasta_empl = picker.endDate.format('YYYY-MM-DD');
        cargarMisPermisos();
    })
    .on('cancel.daterangepicker', function () {
        $(this).val('');
        fecha_desde_empl = null;
        fecha_hasta_empl = null;
        cargarMisPermisos();
    });
}

// ── Cargar permisos del empleado ───────────────────────

// ── Cargar permisos del empleado ───────────────────────
function cargarMisPermisos(autoseleccionar = true) {
    const estados_raw = $('#filtro_estado_empl').val();
    const estados     = !empty(estados_raw) ? estados_raw : '';

    $.ajax({
        url    : '../../controller/permiso.php?op=listarMisPermisos',
        type   : 'POST',
        data   : {
            fecha_desde: fecha_desde_empl ?? '',
            fecha_hasta: fecha_hasta_empl ?? '',
            estados    : estados
        },
        success: function (datos) {
            try {
                const lista = JSON.parse(datos.trim());
                renderListaEmpl(lista);
            } catch (e) {
                console.error('Error listarMisPermisos:', e);
                console.log('Raw:', datos);
            }
        }
    });
}

// ── Helper vacío ───────────────────────────────────────
function empty(val) {
    return val === null || val === undefined || val === '';
}

// ── Helper estado (mismo que el jefe) ─────────────────
function getEstadoPermiso(estado) {
    const estados = {
        '1': { texto: 'Pendiente Aprobación', badge: 'badge-warning',  icono: 'fas fa-clock',        style: '' },
        '2': { texto: 'Aprobado Jefe',        badge: 'badge-info',     icono: 'fas fa-user-check',   style: '' },
        '3': { texto: 'Vbo. Gestión Humana',  badge: 'badge-orange',   icono: 'fas fa-user-tie',     style: 'background-color:#fd7e14; color:#fff;' },
        '4': { texto: 'Aprobado c/pendientes',badge: 'badge-purple',   icono: 'fas fa-check-circle', style: 'background-color:#6f42c1; color:#fff;' },
        '5': { texto: 'Aprobado c/pendientes',badge: 'badge-purple',   icono: 'fas fa-check-circle', style: 'background-color:#6f42c1; color:#fff;' },
        '6': { texto: 'Rechazado',            badge: 'badge-danger',   icono: 'fas fa-times-circle', style: '' },
        '7': { texto: 'Cancelado Operación',  badge: 'badge-dark',     icono: 'fas fa-ban',          style: '' },
    };
    return estados[estado] ?? { texto: 'Desconocido', badge: 'badge-secondary', icono: 'fas fa-question', style: '' };
}

// ── Render lista ───────────────────────────────────────
function renderListaEmpl(lista, autoseleccionar = true) {
    const $lista = $('#lista-permisos-empl');
    $lista.empty();

    $('#lista-contador-empl').text(lista.length + ' solicitud' + (lista.length !== 1 ? 'es' : ''));

    // ── Siempre limpiar detalle al renderizar lista ──
    $('#panel-detalle-empl').html(`
        <div class="text-center text-muted" style="padding:60px 0;">
            <i class="fas fa-hand-pointer fa-2x mb-2 d-block" style="opacity:0.3;"></i>
            Selecciona una solicitud para ver el detalle
        </div>`);

    if (lista.length === 0) {
        $lista.html(`
            <div class="text-center text-muted py-4">
                <i class="fas fa-inbox fa-2x mb-2 d-block" style="opacity:0.3;"></i>
                Sin solicitudes
            </div>`);
        return;
    }

    // ── Ordenar por fecha más reciente ──
    lista.sort((a, b) => new Date(b.permiso_creado) - new Date(a.permiso_creado));

    lista.forEach(function (p) {
        const est   = getEstadoPermiso(p.permiso_estado);
        const fecha = p.permiso_fecha
            ? new Date(p.permiso_fecha + 'T00:00:00').toLocaleDateString('es-CO') : '—';

        $lista.append(`
            <div class="solicitud-item" data-id="${p.permiso_id}">
                <div class="d-flex justify-content-between align-items-start">
                    <span class="nombre">${p.tipo_permiso ?? '—'}</span>
                    <span class="badge ${est.badge}"
                          style="${est.style} font-size:10px; white-space:normal; text-align:right; max-width:110px;">
                        ${est.texto}
                    </span>
                </div>
                <div class="meta">
                    <i class="fas fa-calendar-alt mr-1"></i>${fecha}
                </div>
                <div class="meta">
                    <i class="fas fa-clock mr-1"></i>
                    ${p.permiso_hora_salida ?? '—'} — ${p.permiso_hora_entrada ?? '—'}
                </div>
            </div>`);
    });

    // ── Seleccionar automáticamente el primero ──
    if (autoseleccionar) {
        $('#lista-permisos-empl .solicitud-item:first').trigger('click');
    }
}

// ── Clic en item — cargar detalle ──────────────────────
$(document).on('click', '.solicitud-item', function () {
    $('.solicitud-item').removeClass('active');
    $(this).addClass('active');

    const permiso_id = $(this).data('id');

    $.ajax({
        url    : '../../controller/permiso.php?op=getPermisoEmpleado',
        type   : 'POST',
        data   : { permiso_id: permiso_id },
        success: function (datos) {
            try {
                const p = JSON.parse(datos.trim());
                renderDetalleEmpl(p);

                if ($(window).width() <= 768) {
                    $('html, body').animate({
                        scrollTop: $('#panel-detalle-empl').offset().top - 60
                    }, 300);
                }
            } catch (e) {
                console.error('Error getPermiso:', e);
            }
        }
    });
});

// ── Render detalle ─────────────────────────────────────
function renderDetalleEmpl(p) {
    const est    = getEstadoPermiso(p.permiso_estado);
    const fecha  = p.permiso_fecha
        ? new Date(p.permiso_fecha + 'T00:00:00').toLocaleDateString('es-CO') : '—';
    const creado = p.permiso_creado
        ? new Date(p.permiso_creado).toLocaleString('es-CO') : '—';

    const motivoRechazo = p.permiso_estado == '6' && p.rechazo_permiso ? `
        <div class="alert alert-danger mt-3 mb-0 py-2">
            <i class="fas fa-times-circle mr-1"></i>
            <strong>Motivo de rechazo:</strong> ${p.rechazo_permiso}
        </div>` : '';

    const timeline = renderTimeline(
        p.permiso_estado,
        p.aprobado_jefe_id,
        p.aprobado_rrhh_id
    );

    $('#panel-detalle-empl').html(`
        <div class="card card-outline card-dark mb-0">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-0">
                        <i class="fas fa-file-alt mr-2 text-dark"></i>Solicitud de Permiso
                    </h5>
                    <small class="text-muted">Solicitado el ${creado}</small>
                </div>
                <span class="badge ${est.badge}" style="${est.style}">
                    <i class="${est.icono} mr-1"></i>${est.texto}
                </span>
            </div>

            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <small class="text-uppercase text-muted font-weight-bold d-block"
                               style="font-size:10px; letter-spacing:0.5px;">
                            <i class="fas fa-tag mr-1"></i>Tipo Permiso
                        </small>
                        <span class="font-weight-bold">${p.tipo_permiso ?? '—'}</span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-uppercase text-muted font-weight-bold d-block"
                               style="font-size:10px; letter-spacing:0.5px;">
                            <i class="fas fa-calendar mr-1"></i>Fecha
                        </small>
                        <span>${fecha}</span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-uppercase text-muted font-weight-bold d-block"
                               style="font-size:10px; letter-spacing:0.5px;">
                            <i class="fas fa-sign-out-alt mr-1"></i>Hora Salida
                        </small>
                        <span>${p.permiso_hora_salida ?? '—'}</span>
                    </div>
                    <div class="col-md-2">
                        <small class="text-uppercase text-muted font-weight-bold d-block"
                               style="font-size:10px; letter-spacing:0.5px;">
                            <i class="fas fa-sign-in-alt mr-1"></i>Hora Entrada
                        </small>
                        <span>${p.permiso_hora_entrada ?? '—'}</span>
                    </div>
                </div>

                <small class="text-uppercase text-muted font-weight-bold d-block mb-2"
                       style="font-size:10px; letter-spacing:0.5px;">
                    <i class="fas fa-comment-alt mr-1"></i>Justificación
                </small>
                <div class="justif-box mb-4">
                    ${p.permiso_detalle ?? 'Sin justificación'}
                </div>

                <small class="text-uppercase text-muted font-weight-bold d-block mb-3"
                       style="font-size:10px; letter-spacing:0.5px;">
                    <i class="fas fa-tasks mr-1"></i>Estado de Aprobación
                </small>
                <div class="estado-timeline">
                    ${timeline}
                </div>

                ${motivoRechazo}
            </div>

            <div class="card-footer bg-light">
                <small class="text-muted">
                    <i class="fas fa-lock mr-1"></i>
                    Vista de solo lectura. Para gestionar sus permisos vaya a
                    <strong>Solicitud de Permisos</strong>.
                </small>
            </div>
        </div>`);
}

// ── Render timeline ────────────────────────────────────
function renderTimeline(estado, aprobado_jefe_id, aprobado_rrhh_id) {

    const pasos = [
        { icono: 'fas fa-plus',         label: 'Solicitado'        },
        { icono: 'fas fa-user',         label: 'Jefe<br>Inmediato' },
        { icono: 'fas fa-user-tie',     label: 'Gestión<br>Humana' },
        { icono: 'fas fa-check-double', label: 'Aprobado<br>Final' },
    ];

    const esRechazo  = estado == '6' || estado == '7';
    const jefeAprobo = aprobado_jefe_id !== null && aprobado_jefe_id !== '' && aprobado_jefe_id !== 'null';
    const rhAprobo   = aprobado_rrhh_id !== null && aprobado_rrhh_id !== '' && aprobado_rrhh_id !== 'null';

    // ── Cada paso es independiente ──
    // Paso 0: Solicitado — siempre done
    // Paso 1: Jefe       — done si aprobó, danger si rechazó, apagado si no ha actuado
    // Paso 2: GH         — done si aprobó, apagado si no ha actuado (independiente del jefe)
    // Paso 3: Final      — done solo si los dos aprobaron
    const estadoPasos = [
        'done',
        jefeAprobo  ? 'done'
                    : esRechazo ? 'danger' : '',
        rhAprobo    ? 'done' : '',
        (jefeAprobo && rhAprobo) ? 'done' : ''
    ];

    return pasos.map((paso, i) => `
        <div class="estado-step ${estadoPasos[i]}">
            <div class="step-dot"><i class="${paso.icono}"></i></div>
            <div class="step-label">${paso.label}</div>
        </div>`
    ).join('');
}

// ── Botón filtrar ──────────────────────────────────────
$('#btn_filtrar_empl').on('click', function () {
    cargarMisPermisos(false);
});

// ── Botón limpiar ──────────────────────────────────────
$('#btn_limpiar_empl').on('click', function () {
    $('#filtro_fechas_empl').val('');
    $('#filtro_estado_empl').val('');
    fecha_desde_empl = null;
    fecha_hasta_empl = null;
    cargarMisPermisos(false);
});

// ── Cambio de estado en select ─────────────────────────
$('#filtro_estado_empl').on('change', function () {
    cargarMisPermisos(false);
});


//FIRMA GESTION HUMANA

/* let firmaBase64 = "";
let userId = $("#empl_idx").val();

$("#firmaFile").on("change", function () {

    let file = this.files[0];
    if (!file) return;

    let reader = new FileReader();

    reader.onload = function (e) {
        firmaBase64 = e.target.result; // base64 de la imagen

        $("#previewFirmaEmpleado")
            .attr("src", firmaBase64)
            .show();

        $("#btnGuardarFirmaEmpleado").prop("disabled", false);
    };

    reader.readAsDataURL(file);
});


$("#btnGuardarFirmaEmpleado").click(function () {

    if (!firmaBase64) {
        Swal.fire("Advertencia", "Debes seleccionar una imagen.", "warning");
        return;
    }

    $.ajax({
        url: "../../controller/firma.php?op=guardar",
        type: "POST",
        data: { firma_base64: firmaBase64 },
        success: function (response) {
            let res = JSON.parse(response);

            if (res.success) {
                Swal.fire("Éxito", res.message, "success");
                $("#modalFirmaEmpleado").modal("hide");
            } else {
                Swal.fire("Error", res.message, "error");
            }
        },
        error: function () {
            Swal.fire("Error", "No se pudo guardar la firma.", "error");
        }
    });

}); */





/* */



init();