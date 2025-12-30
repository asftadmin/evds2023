// Variable global (para poder hacer reload desde botones)
let tablaAusentismo = null;

$('#filtroFecha').daterangepicker({
    startDate: moment().subtract(8, 'days'),       // fecha inicial hoy
    endDate: moment().subtract(1, 'days'),         // fecha final hoy
    showDropdowns: true,
    autoUpdateInput: true,
    maxDate: moment(),         // evita seleccionar fechas futuras
    locale: {
        format: 'YYYY-MM-DD',
        applyLabel: 'Aplicar',
        cancelLabel: 'Cancelar',
        customRangeLabel: 'Rango personalizado',
        daysOfWeek: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'],
        monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']
    }
}, function (start, end) {
    $('#filtroFecha').val(start.format('YYYY-MM-DD') + ' - ' + end.format('YYYY-MM-DD'));
});

function initAusentismoTable() {

    // Evita doble inicialización
    if (tablaAusentismo) return;

    tablaAusentismo = $("#tablaAusentismo").DataTable({
        aProcessing: true,
        aServerSide: false,      // tú estás devolviendo aaData completo
        searching: false,        // filtros los manejas con select + botón
        lengthChange: true,
        responsive: true,
        autoWidth: false,
        pageLength: 10,
        order: [[0, "asc"]],
        language: {
            sProcessing: "Procesando...",
            sLengthMenu: "Mostrar _MENU_ registros",
            sZeroRecords: "No se encontraron resultados",
            sEmptyTable: "Ningún dato disponible en esta tabla",
            sInfo: "Mostrando un total de _TOTAL_ registros",
            sInfoEmpty: "Mostrando un total de 0 registros",
            sInfoFiltered: "(filtrado de un total de _MAX_ registros)",
            oPaginate: {
                sFirst: "Primero",
                sLast: "Último",
                sNext: "Siguiente",
                sPrevious: "Anterior"
            }
        },
        ajax: {
            url: "../../controller/permiso.php?op=listarAusentismo",
            type: "POST",
            dataType: "json",
            data: function (d) {
                d.empleado_id = "";
                d.fecha_desde = "";
                d.fecha_hasta = "";
            },
            error: function (xhr) {
                console.log(xhr.responseText);
                Swal.fire("Error", "No se pudo cargar el ausentismo.", "error");
            }
        },
        // Si usas columnas fijas, puedes declarar columns, pero no es obligatorio
    });
}

$(document).ready(function () {

    // Inicializa selects (si usas select2)
    if ($.fn.select2) {
        $("#filtroEmpleadoA").select2({ width: "100%" });
    }

    // Inicializa DataTable una sola vez (carga inicial SIN filtros)
    initAusentismoTable();

    $("#btnFiltrar").off("click").on("click", function () {

        let drp = $("#filtroFecha").data("daterangepicker");
        let fecha_desde = drp.startDate.format("YYYY-MM-DD");
        let fecha_hasta = drp.endDate.format("YYYY-MM-DD");
        let empleado_id = $("#filtroEmpleadoA").val() || "";

        // Cambiar parámetros del ajax SOLO para esta recarga
        tablaAusentismo.ajax.url("../../controller/permiso.php?op=listarAusentismo").load(function () { }, false);

        // Truco: DataTables no permite cambiar "data" directo por click,
        // así que usamos preXhr para inyectar esos valores solo en esta llamada.
        tablaAusentismo.one("preXhr.dt", function (e, settings, data) {
            data.empleado_id = empleado_id;
            data.fecha_desde = fecha_desde;
            data.fecha_hasta = fecha_hasta;
        });

        tablaAusentismo.ajax.reload();
    });

    // ✅ Limpiar filtros
    $("#btnLimpiarAusentismo").off("click").on("click", function () {
        $("#filtroEmpleadoAs").val("").trigger("change");

        // opcional: reiniciar rango a default
        $("#filtroFecha").data("daterangepicker").setStartDate(moment().subtract(8, "days"));
        $("#filtroFecha").data("daterangepicker").setEndDate(moment().subtract(1, "days"));

        // recargar sin filtros
        tablaAusentismo.one("preXhr.dt", function (e, settings, data) {
            data.empleado_id = "";
            data.fecha_desde = "";
            data.fecha_hasta = "";
        });

        tablaAusentismo.ajax.reload();
    });

});
