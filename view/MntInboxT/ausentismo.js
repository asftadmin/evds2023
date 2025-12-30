// Variable global (para poder hacer reload desde botones)
let tablaAusentismo = null;

let filtroFechaIni = "";
let filtroFechaFin = "";
let filtroEmpleadoId = "";

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

        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>><"row"<"col-sm-12"B>><"row"<"col-sm-12"tr>><"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel"></i> Excel',
                title: 'Reporte de Ausentismo',
                className: 'btn btn-success btn-sm',
                // Exportar TODAS las columnas (índices 0 a 11)
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11], // Todas las columnas
                    modifier: {
                        page: 'all' // Todas las páginas
                    }
                }
            },
            {
                extend: 'pdfHtml5',
                text: '<i class="fas fa-file-pdf"></i> PDF',
                title: 'Reporte de Ausentismo',
                className: 'btn btn-danger btn-sm',
                orientation: 'landscape',
                pageSize: 'LETTER',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
                    modifier: {
                        page: 'all'
                    }
                },
                customize: function(doc) {
                    // Configuración básica
                    doc.defaultStyle.fontSize = 7;
                    doc.styles.tableHeader.fontSize = 9;
                    doc.styles.title.fontSize = 14;
                    doc.pageMargins = [20, 60, 20, 40];
                    
                    // Ajustar ancho de columnas
                    var colCount = doc.content[1].table.body[0].length;
                    var colWidths = [];
                    
                    for (var i = 0; i < colCount; i++) {
                        colWidths.push('auto');
                    }
                    
                    doc.content[1].table.widths = colWidths;
                    
                    // Estilo para encabezados
                    doc.content[1].table.body[0].forEach(function(cell) {
                        cell.fillColor = '#2c3e50';
                        cell.color = '#ffffff';
                        cell.bold = true;
                        cell.alignment = 'center';
                    });
                    
                    // Pie de página
                    doc['footer'] = function(currentPage, pageCount) {
                        return {
                            text: 'Página ' + currentPage.toString() + ' de ' + pageCount,
                            alignment: 'center',
                            fontSize: 8,
                            margin: [0, 10, 0, 0]
                        };
                    };
                },
                filename: 'Ausentismo_' + new Date().toISOString().slice(0,10) + '.pdf'
            },
            {
                extend: 'csvHtml5',
                text: '<i class="fas fa-file-csv"></i> CSV',
                title: 'Reporte de Ausentismo',
                className: 'btn btn-info btn-sm',
                // Exportar TODAS las columnas
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
                    modifier: {
                        page: 'all'
                    }
                }
            },
            {
                extend: 'print',
                text: '<i class="fas fa-print"></i> Imprimir',
                title: '',
                className: 'btn btn-warning btn-sm',
                // Exportar TODAS las columnas
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
                    modifier: {
                        page: 'all'
                    }
                },
                customize: function(win) {
                    $(win.document.body).css('font-size', '9pt');
                    
                    // Crear título personalizado
                    var title = '<div style="text-align:center; margin-bottom:20px;">' +
                               '<h3>Reporte Completo de Ausentismo</h3>' +
                               '<p>Generado: ' + new Date().toLocaleDateString() + ' ' + new Date().toLocaleTimeString() + '</p>' +
                               '</div>';
                    
                    $(win.document.body).prepend(title);
                    
                    // Asegurar que todas las columnas sean visibles
                    $(win.document.body).find('table thead th').show();
                    $(win.document.body).find('table tbody td').show();
                    
                    // Ajustar ancho de tabla
                    $(win.document.body).find('table').css('width', '100%');
                    
                    // Estilo para mejor visualización
                    $(win.document.body).find('tr:nth-child(odd)').css('background-color', '#f9f9f9');
                    $(win.document.body).find('tr:nth-child(even)').css('background-color', '#ffffff');
                    
                    // Agregar pie de página
                    var pageCount = win.document.querySelectorAll('.dataTables_scrollBody table tr').length > 0 ? '1' : '';
                    var footer = '<div style="text-align:center; margin-top:30px; font-size:8pt; color:#666;">' +
                                'Página ' + pageCount +
                                '</div>';
                    
                    $(win.document.body).append(footer);
                }
            },
            {
                extend: 'copy',
                text: '<i class="fas fa-copy"></i> Copiar',
                title: 'Reporte de Ausentismo',
                className: 'btn btn-secondary btn-sm',
                // Exportar TODAS las columnas
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
                    modifier: {
                        page: 'all'
                    }
                }
            },
            {
                extend: 'colvis',
                text: '<i class="fas fa-columns"></i> Columnas',
                className: 'btn btn-dark btn-sm',
                columns: ':gt(0)', // Todas excepto la primera (ITEM)
                columnText: function(dt, idx, title) {
                    return (idx + 1) + ': ' + title;
                },
                // Botones adicionales para colvis
                postfixButtons: [
                    {
                        text: 'Mostrar todas',
                        action: function(e, dt, button, config) {
                            dt.columns().visible(true);
                            this.active(false);
                        }
                    },
                    {
                        text: 'Ocultar todas',
                        action: function(e, dt, button, config) {
                            dt.columns(':gt(0)').visible(false); // Ocultar todas excepto ITEM
                            this.active(false);
                        }
                    },
                    'colvisRestore'
                ]
            }
        ],
        processing: true,
        serverSide: false,      // tú estás devolviendo aaData completo
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
            },
            buttons: {
                copyTitle: 'Copiar al portapapeles',
                copySuccess: {
                    _: '%d filas copiadas',
                    1: '1 fila copiada'
                }
            }
        },
        ajax: {
            url: "../../controller/permiso.php?op=listarAusentismo",
            type: "POST",
            dataType: "json",
            data: function (d) {
                d.fecha_ini = filtroFechaIni;
                d.fecha_fin = filtroFechaFin;
                d.empleado_id = filtroEmpleadoId;
            },
            error: function (xhr) {
                console.log(xhr.responseText);
                Swal.fire("Error", "No se pudo cargar el ausentismo.", "error");
            }
        }
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
    cargarEmpleadosActivos();

    $("#btnFiltrarAusentismo").off("click").on("click", function () {

        filtroEmpleadoId = $("#filtroEmpleadoA").val() || "";

        let drp = $("#filtroFecha").data("daterangepicker");
        if (drp && drp.startDate && drp.endDate) {
            filtroFechaIni = drp.startDate.format("YYYY-MM-DD");
            filtroFechaFin = drp.endDate.format("YYYY-MM-DD");
        } else {
            filtroFechaIni = "";
            filtroFechaFin = "";
        }

        // ✅ Swal Loading
        Swal.fire({
            title: "Filtrando...",
            text: "Cargando resultados, por favor espere",
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => Swal.showLoading()
        });

        // ✅ Cerrar Swal cuando termine el AJAX del DataTable (solo esta vez)
        tablaAusentismo.one("xhr.dt", function () {
            Swal.close();
        });

        tablaAusentismo.ajax.reload();
    });



    // ✅ Limpiar filtros
    $("#btnLimpiarAusentismo").off("click").on("click", function () {

        // corregido el ID
        $("#filtroEmpleadoA").val("").trigger("change");

        // reset variables
        filtroEmpleadoId = "";
        filtroFechaIni = "";
        filtroFechaFin = "";

        // reiniciar el daterangepicker visualmente (opcional)
        let drp = $("#filtroFecha").data("daterangepicker");
        if (drp) {
            drp.setStartDate(moment().subtract(8, "days"));
            drp.setEndDate(moment().subtract(1, "days"));
            $("#filtroFecha").val(drp.startDate.format("YYYY-MM-DD") + " - " + drp.endDate.format("YYYY-MM-DD"));
        } else {
            $("#filtroFecha").val("");
        }

        tablaAusentismo.ajax.reload();
    });

    function cargarEmpleadosActivos() {
        $.post("../../controller/empleado.php?op=comboRol", function (html) {
            $("#filtroEmpleadoA")
                .html('<option value="">Todos</option>' + html)
                .trigger("change");
        });
    }


});
