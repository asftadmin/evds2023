let tablaOpen = null;

let fecha_desde_rrhh = null;
let fecha_hasta_rrhh = null;

function init() {
    // ── Restaurar filtros si vienen del detalle ──
    const filtroEmpleado   = sessionStorage.getItem('filtroEmpleado');
    const filtroFechaDesde = sessionStorage.getItem('filtroFechaDesde');
    const filtroFechaHasta = sessionStorage.getItem('filtroFechaHasta');
    const filtroFechaTexto = sessionStorage.getItem('filtroFechaTexto');

    if (filtroEmpleado) {
        $('#filtroEmpleado').val(filtroEmpleado).trigger('change');
    }
    if (filtroFechaTexto) {
        $('#filtroFecha').val(filtroFechaTexto);
        fecha_desde_rrhh = filtroFechaDesde || null;
        fecha_hasta_rrhh = filtroFechaHasta || null;
    }

    // Limpiar sessionStorage después de restaurar
    sessionStorage.removeItem('filtroEmpleado');
    sessionStorage.removeItem('filtroFechaDesde');
    sessionStorage.removeItem('filtroFechaHasta');
    sessionStorage.removeItem('filtroFechaTexto');

    inicializarTabla();
    cargarEmpleadosActivos();
    initDateRangePicker();

    $('.select2').select2({ width: '100%' });

    $("#btnFiltrar").off("click").on("click", function () {
        if (tablaOpen) tablaOpen.ajax.reload();
    });

    $("#btnLimpiarFiltros").off("click").on("click", function () {
        $("#filtroEmpleado").val("").trigger("change");
        $("#filtroFecha").val("");
        fecha_desde_rrhh = null;
        fecha_hasta_rrhh = null;
        if (tablaOpen) tablaOpen.ajax.reload();
    });
}

// ── DateRangePicker ────────────────────────────────────
function initDateRangePicker() {
    $('#filtroFecha').daterangepicker({
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
        fecha_desde_rrhh = picker.startDate.format('YYYY-MM-DD');
        fecha_hasta_rrhh = picker.endDate.format('YYYY-MM-DD');
    })
    .on('cancel.daterangepicker', function () {
        $(this).val('');
        fecha_desde_rrhh = null;
        fecha_hasta_rrhh = null;
    });
}

$('#solicitudes-btn').on('click', function () {
    cargarSolicitudesR('Jefe');
    //if (tablaRevision) tablaRevision.clear().destroy();
});

function inicializarTabla() {

    if ($.fn.DataTable.isDataTable("#tablaSolcRhumano")) {
        tablaOpen = $("#tablaSolcRhumano").DataTable();
        return;
    }

    if (tablaOpen) {
        tablaOpen.clear(); // Limpiar la tabla existente
    } else {
        tablaOpen = $('#tablaSolcRhumano').DataTable({
            "aProcessing": true,
            "aServerSide": true,
            "searching": false,
            "lengthChange": true,
            "colReorder": true,
            "responsive": true,
            "autoWidth": false,
            "bInfo": true,
            "pageLength": 10,
            "order": [[7, 'desc']],
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
                url: '../../controller/permiso.php?op=listarSolicitudesRecursos',
                type: "POST",
                dataType: "json",

                data: function (d) {
                    d.empleado_id = $("#filtroEmpleado").val();
                    d.fecha_desde  = fecha_desde_rrhh ?? '';
                    d.fecha_hasta  = fecha_hasta_rrhh ?? '';
                },
                error: function (e) {
                    console.log(e.responseText);
                }
            }
        });
    }
}


function cargarSolicitudesR(tipo = 'Recursos') {
    const nuevaURL = `../../controller/tickets.php?op=listarSolicitudes${tipo}`;
    if (tablaOpen) {
        tablaOpen.ajax.url(nuevaURL).load(); // recarga datos sin reiniciar tabla
    } else {
        inicializarTabla();
    }
}


var getURLParameter = function (sParam) {
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


function ver(permisoID) {
    // ── Guardar filtros en sessionStorage ──
    sessionStorage.setItem('filtroEmpleado', $('#filtroEmpleado').val() ?? '');
    sessionStorage.setItem('filtroFechaDesde', fecha_desde_rrhh ?? '');
    sessionStorage.setItem('filtroFechaHasta', fecha_hasta_rrhh ?? '');
    sessionStorage.setItem('filtroFechaTexto', $('#filtroFecha').val() ?? '');
    window.location.href = BASE_URL + '/view/MntInboxT/detalle_permiso.php?id=' + permisoID; //http://181.204.219.154:3396/preoperacional
    verSolicitud(permisoID);
}


function verTimeline(permisoID) {
    window.location.href = BASE_URL + '/view/MntInboxEmpl/detalle_permiso.php?id=' + permisoID; //http://181.204.219.154:3396/preoperacional
    cargarTimeline(permisoID);
}

function verPdf(permisoID) {
    console.log(permisoID);
    var url = BASE_URL + '/view/PDF/permisoPDF.php?id=' + permisoID; //http://181.204.219.154:3396/preoperacional
    window.open(url, '_blank');
}

function cargarEmpleadosActivos() {
    $.post("../../controller/empleado.php?op=comboRol", function (html) {
        $("#filtroEmpleado")
            .html('<option value="">Todos</option>' + html)
            .trigger("change");
    });
}

function eliminar(permiso_id, empleado, fecha) {

    Swal.fire({
        title : '¿Eliminar permiso?',
        html  : `¿Estás seguro de eliminar el permiso de<br>
                 <strong>${empleado}</strong><br>
                 del día <strong>${fecha}</strong>?<br><br>
                 <strong style="color:#dc3545;">Esta acción no se puede deshacer.</strong>`,
        icon             : 'warning',
        showCancelButton : true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor : '#6c757d',
        confirmButtonText : '<i class="fas fa-trash mr-1"></i>Sí, eliminar',
        cancelButtonText  : 'Cancelar',
        reverseButtons   : true
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url      : '../../controller/permiso.php?op=eliminarPermiso',
                type     : 'POST',
                data     : { permiso_id: permiso_id },
                beforeSend: function () {
                    Swal.fire({
                        title            : 'Eliminando...',
                        allowOutsideClick: false,
                        didOpen          : () => Swal.showLoading()
                    });
                },
                success: function (response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.success) {
                            Swal.fire({
                                icon             : 'success',
                                title            : 'Eliminado',
                                text             : 'El permiso fue eliminado correctamente.',
                                timer            : 1500,
                                timerProgressBar : true,
                                showConfirmButton: false
                            }).then(() => {
                                if (tablaOpen) tablaOpen.ajax.reload();
                            });
                        } else {
                            Swal.fire({
                                icon : 'error',
                                title: 'Error',
                                text : data.error ?? 'No se pudo eliminar el permiso.'
                            });
                        }
                    } catch (err) {
                        console.error('Error:', err);
                        Swal.fire('Error', 'Respuesta inesperada del servidor.', 'error');
                    }
                },
                error: function () {
                    Swal.fire('Error', 'Error de conexión al eliminar.', 'error');
                }
            });
        }
    });
}



init();