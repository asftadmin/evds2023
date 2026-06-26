let tabla;
let swalCargaRegistroActivo = false;

function init() {
    initSelect2BioPro();
    cargarComboEmpleadosBioPro();
}

function initSelect2BioPro() {
    $('.select2bs4').select2({
        theme: 'bootstrap4',
        width: '100%'
    });
}

function cargarComboEmpleadosBioPro() {
    $.ajax({
        url: '../../controller/biopro.php?op=comboEmpleadosActivos',
        type: 'GET',
        success: function (html) {
            $('#filtro-empleado, #dash-empleado')
                .html(html)
                .val('')
                .trigger('change');
        },
        error: function (xhr) {
            console.log(xhr.responseText);
            Swal.fire('Error', 'No se pudo cargar la lista de empleados activos.', 'error');
        }
    });
}

$('#filtro-fechas').daterangepicker({
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
    $('#filtro-fechas').val(start.format('YYYY-MM-DD') + ' - ' + end.format('YYYY-MM-DD'));
});

$(document).on('click', '#btn-filtrar-fechas', function () {
    cargarAsistencia();
});


function cargarAsistencia() {
    const fechas = $('#filtro-fechas').val().split(' - ');
    const fechainicio = fechas[0];
    const fechafin = fechas[1];
    const empleado = $('#filtro-empleado').val() || '';

    swalCargaRegistroActivo = true;
    Swal.fire({
        title: 'Cargando información',
        html: 'Por favor espere...',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    return $('#asistencia_biotime_v2').dataTable({
        "aProcessing": true,
        "aServerSide": true,
        "searching": true,
        lengthChange: true,
        pageLength: 10,
        lengthMenu: [
            [5, 10, 25, 50, 100, -1],
            [5, 10, 25, 50, 100, 'Todos']
        ],
        dom:
            "<'row mb-2'<'col-md-7 d-flex flex-wrap align-items-center'B<'ml-2'l>><'col-md-5'f>>" +
            "<'row'<'col-12'tr>>" +
            "<'row mt-2'<'col-md-5'i><'col-md-7'p>>",
        colReorder: true,
        buttons: [
            {
                extend: 'copyHtml5',
                text: 'Copiar',
                className: 'btn btn-secondary btn-sm',
                exportOptions: { columns: ':visible' }
            },
            {
                extend: 'excelHtml5',
                text: 'Excel',
                className: 'btn btn-success btn-sm',
                exportOptions: { columns: ':visible' }
            },
            {
                extend: 'csvHtml5',
                text: 'CSV',
                className: 'btn btn-info btn-sm',
                exportOptions: { columns: ':visible' }
            },
            {
                extend: 'pdfHtml5',
                text: 'PDF',
                className: 'btn btn-danger btn-sm',
                orientation: 'landscape',
                pageSize: 'A4',
                exportOptions: { columns: ':visible' }
            },
            {
                extend: 'print',
                text: 'Imprimir',
                className: 'btn btn-primary btn-sm',
                exportOptions: { columns: ':visible' }
            }
        ],
        "ajax": {
            url: '../../controller/biopro.php?op=listarAsistenciaBioPro',
            type: "get",
            data: {
                fechainicio,
                fechafin,
                empleado
            },
            dataType: "json",
            complete: function () {
                if (swalCargaRegistroActivo) {
                    Swal.close();
                    swalCargaRegistroActivo = false;
                }
            },
            error: function (e) {
                if (swalCargaRegistroActivo) {
                    Swal.close();
                    swalCargaRegistroActivo = false;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ocurrió un error consultando la asistencia.'
                });

                console.log(e.responseText);
            }
        },
        "bDestroy": true,
        "responsive": true,
        "bInfo": true,
        "autoWidth": false,
        order: [[0, 'desc']],
        "language": {
            "sProcessing": "Procesando...",
            "sLengthMenu": "Mostrar _MENU_ registros",
            "sZeroRecords": "No se encontraron resultados",
            "sEmptyTable": "Ningún dato disponible en esta tabla",
            "sInfo": "Mostrando un total de _TOTAL_ registros",
            "sInfoEmpty": "Mostrando un total de 0 registros",
            "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
            "sInfoPostFix": "",
            "sSearch": "Buscar:",
            "sUrl": "",
            "sInfoThousands": ",",
            "sLoadingRecords": "Cargando...",
            "oPaginate": {
                "sFirst": "Primero",
                "sLast": "Último",
                "sNext": "Siguiente",
                "sPrevious": "Anterior"
            },
            "oAria": {
                "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
                "sSortDescending": ": Activar para ordenar la columna de manera descendente"
            }
        }
    }).DataTable();
}

$(document).on('click', '#btn-limpiar-filtros', function () {

    // Limpiar campo empleado
    $('#filtro-empleado').val('').trigger('change');

    // Reiniciar DateRangePicker a valores por defecto
    const startDefault = moment().subtract(8, 'days');
    const endDefault = moment().subtract(1, 'days');

    const picker = $('#filtro-fechas').data('daterangepicker');

    if (picker) {
        picker.setStartDate(startDefault);
        picker.setEndDate(endDefault);
    }

    // Actualizar visualmente el input
    $('#filtro-fechas').val(startDefault.format('YYYY-MM-DD') + ' - ' + endDefault.format('YYYY-MM-DD'));

    // Destruir DataTable si existe
    if ($.fn.DataTable.isDataTable('#asistencia_biotime_v2')) {
        $('#asistencia_biotime_v2').DataTable().clear().destroy();
    }

    // Vaciar cuerpo de tabla
    $('#asistencia_biotime_v2 tbody').empty();
});

let dashChartInstance = null;
let dashTableLastEmpleado = null;

function initDashboardDateRange() {
    $('#dash-fechas').daterangepicker({
        startDate: moment().subtract(8, 'days'),
        endDate: moment().subtract(1, 'days'),
        showDropdowns: true,
        autoUpdateInput: true,
        maxDate: moment(),
        locale: { format: 'YYYY-MM-DD' }
    }, function (start, end) {
        $('#dash-fechas').val(start.format('YYYY-MM-DD') + ' - ' + end.format('YYYY-MM-DD'));
    });
}

function resetDashUI() {
    $('#nota-ausentismo').hide();
    $('#dash-chart-wrap').hide();
    $('#dash-table-wrap').hide();
    $('#dash-kpi-wrap').hide();
    $('#balance-kpi-wrap').hide();
    $('#arrival-detail-wrap').hide();

    if (dashChartInstance) {
        dashChartInstance.destroy();
        dashChartInstance = null;
    }

    if ($.fn.DataTable.isDataTable('#dashTable')) {
        $('#dashTable').DataTable().clear().draw();
    } else {
        $('#dashTable tbody').empty();
    }
    $('#arrivalDetailTable tbody').empty();
}

function renderArrivalDetailTable(detalle) {

    const tbody = $('#arrivalDetailTable tbody');
    tbody.empty();

    detalle.forEach(function (item) {

        let badge = '';

        if (item.estado === 'A TIEMPO') {
            badge = '<span class="badge badge-success">A tiempo</span>';
        } else if (item.estado === 'TARDE') {
            badge = '<span class="badge badge-danger">Tarde</span>';
        } else {
            badge = '<span class="badge badge-secondary">Sin marcación</span>';
        }

        tbody.append(`
            <tr>
                <td>${item.fecha}</td>
                <td>${item.entrada}</td>
                <td>${badge}</td>
            </tr>
        `);
    });

    $('#arrival-detail-wrap').show();
}


function renderArrivalLineChart(labels, entradas, objetivo, entradasTexto, pointColors) {

    $('#dash-chart-wrap').show();

    const ctx = document.getElementById('dashChart').getContext('2d');

    dashChartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Hora de entrada',
                    data: entradas,
                    tension: 0.3,
                    fill: false,
                    borderColor: '#007bff',
                    backgroundColor: pointColors,
                    pointBackgroundColor: pointColors,
                    pointBorderColor: pointColors,
                    pointRadius: 5,
                    spanGaps: false
                },
                {
                    label: 'Hora objetivo 08:00',
                    data: objetivo,
                    tension: 0,
                    fill: false,
                    borderColor: '#ff6384',
                    borderDash: [6, 6],
                    pointRadius: 3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const index = context.dataIndex;

                            if (context.datasetIndex === 0) {
                                return 'Entrada: ' + (entradasTexto[index] ?? 'Sin marcación');
                            }

                            return 'Meta: 08:00';
                        }
                    }
                }
            },
            scales: {
                y: {
                    min: 360, // 06:00
                    max: 620, // 10:20

                    ticks: {
                        stepSize: 120,

                        callback: function (value) {

                            const h = Math.floor(value / 60);
                            const m = value % 60;

                            return String(h).padStart(2, '0') + ':' +
                                String(m).padStart(2, '0');
                        }
                    },

                    title: {
                        display: true,
                        text: 'Hora'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Fecha'
                    }
                }
            }
        }
    });
}


function renderGroupedBars(labels, series, title) {

    $('#dash-chart-wrap').show();

    const canvas = document.getElementById('dashChart');

    canvas.parentNode.style.height = '420px';

    const ctx = canvas.getContext('2d');

    dashChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: series
        },
        plugins: [ChartDataLabels],
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                title: {
                    display: true,
                    text: title
                },
                datalabels: {
                    anchor: 'end',
                    align: 'top',
                    font: {
                        weight: 'bold'
                    },
                    formatter: function (value) {
                        return value + '%';
                    }
                }
            },
            scales: {
                x: {
                    stacked: false,
                    ticks: {
                        autoSkip: false,
                        maxRotation: 45,
                        minRotation: 0
                    },
                    title: {
                        display: true,
                        text: 'Área'
                    }
                },
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function (value) {
                            return value + '%';
                        }
                    },
                    title: {
                        display: true,
                        text: 'Porcentaje'
                    }
                }
            }
        }
    });
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function setDashTableHeaders(headers) {
    const thead = $('#dashTable thead');
    thead.empty();
    thead.append(`<tr>${headers.map(h => `<th>${h}</th>`).join('')}</tr>`);
}

function getDashTableCurrentPage() {
    if (!$.fn.DataTable.isDataTable('#dashTable')) {
        return 0;
    }

    return $('#dashTable').DataTable().page();
}

function renderDashDataTable(headers, rows, options = {}) {
    const preservePage = options.preservePage === true;
    const requestedPage = preservePage ? Number(options.page || 0) : 0;

    if ($.fn.DataTable.isDataTable('#dashTable')) {
        $('#dashTable').DataTable().clear().destroy();
    }

    setDashTableHeaders(headers);

    const tbody = $('#dashTable tbody');
    tbody.empty();

    rows.forEach(row => {
        tbody.append(`<tr>${row.map(cell => `<td>${cell}</td>`).join('')}</tr>`);
    });

    const table = $('#dashTable').DataTable({
        searching: true,
        lengthChange: true,
        pageLength: 10,
        lengthMenu: [
            [5, 10, 25, 50, 100, -1],
            [5, 10, 25, 50, 100, 'Todos']
        ],
        dom:
            "<'row mb-2'<'col-md-7 d-flex flex-wrap align-items-center'B<'ml-2'l>><'col-md-5'f>>" +
            "<'row'<'col-12'tr>>" +
            "<'row mt-2'<'col-md-5'i><'col-md-7'p>>",
        buttons: [
            {
                extend: 'copyHtml5',
                text: 'Copiar',
                className: 'btn btn-secondary btn-sm',
                exportOptions: { columns: ':visible' }
            },
            {
                extend: 'excelHtml5',
                text: 'Excel',
                className: 'btn btn-success btn-sm',
                exportOptions: { columns: ':visible' }
            },
            {
                extend: 'csvHtml5',
                text: 'CSV',
                className: 'btn btn-info btn-sm',
                exportOptions: { columns: ':visible' }
            },
            {
                extend: 'pdfHtml5',
                text: 'PDF',
                className: 'btn btn-danger btn-sm',
                orientation: 'landscape',
                pageSize: 'A4',
                exportOptions: { columns: ':visible' }
            },
            {
                extend: 'print',
                text: 'Imprimir',
                className: 'btn btn-primary btn-sm',
                exportOptions: { columns: ':visible' }
            }
        ],
        responsive: true,
        autoWidth: false,
        language: {
            sProcessing: 'Procesando...',
            sLengthMenu: 'Mostrar _MENU_ registros',
            sZeroRecords: 'No se encontraron resultados',
            sEmptyTable: 'Ningun dato disponible en esta tabla',
            sInfo: 'Mostrando un total de _TOTAL_ registros',
            sInfoEmpty: 'Mostrando un total de 0 registros',
            sInfoFiltered: '(filtrado de un total de _MAX_ registros)',
            sSearch: 'Buscar:',
            sLoadingRecords: 'Cargando...',
            oPaginate: {
                sFirst: 'Primero',
                sLast: 'Ultimo',
                sNext: 'Siguiente',
                sPrevious: 'Anterior'
            }
        }
    });

    if (preservePage) {
        const info = table.page.info();
        const maxPage = Math.max(info.pages - 1, 0);
        table.page(Math.min(requestedPage, maxPage)).draw('page');
    }

    return table;
}

function getSignedBadgeClass(minutes) {
    if (minutes > 0) return 'badge-success';
    if (minutes < 0) return 'badge-danger';
    return 'badge-secondary';
}

function renderHoursTable(rows, options = {}) {
    $('#dash-table-wrap').show();

    const tableRows = rows.map(r => {
        return [
            escapeHtml(r.nombre),
            escapeHtml(r.documento),
            escapeHtml(r.total_horas)
        ];
    });

    renderDashDataTable(['Empleado', 'Documento', 'Total Horas'], tableRows, options);
}

function renderBalanceCards(cards) {
    $('#balance-kpi-wrap').show();

    const diferenciaMin = Number(cards.diferencia_min || 0);
    const $box = $('#balance-diferencia-box');

    $('#balance-total-biotime').text(cards.total_biotime || '0:00');
    $('#balance-total-permisos').text(cards.total_permisos || '0:00');
    $('#balance-diferencia').text(cards.diferencia || '0:00');
    $('#balance-inconsistencias').text(cards.inconsistencias || 0);

    $box.removeClass('bg-success bg-danger bg-secondary');

    if (diferenciaMin > 0) {
        $box.addClass('bg-success');
        $('#balance-diferencia-label').text('BioTime supera permisos');
    } else if (diferenciaMin < 0) {
        $box.addClass('bg-danger');
        $('#balance-diferencia-label').text('Permisos superan BioTime');
    } else {
        $box.addClass('bg-secondary');
        $('#balance-diferencia-label').text('Sin diferencia');
    }
}

function renderBalanceTable(rows, options = {}) {
    $('#dash-table-wrap').show();

    const tableRows = rows.map(r => {
        const badgeClass = getSignedBadgeClass(Number(r.diferencia_min || 0));

        return [
            escapeHtml(r.fecha),
            escapeHtml(r.empleado),
            escapeHtml(r.entrada),
            escapeHtml(r.salida),
            escapeHtml(r.tiempo_biotime),
            escapeHtml(r.tiempo_permisos),
            `<span class="badge ${badgeClass}">${escapeHtml(r.diferencia)}</span>`,
            escapeHtml(r.observacion)
        ];
    });

    renderDashDataTable([
        'Fecha',
        'Empleado',
        'Entrada BioTime',
        'Salida BioTime',
        'Tiempo BioTime',
        'Permisos salida',
        'Diferencia',
        'Observacion'
    ], tableRows, options);
}

function renderPunctualityKpi(rate, diasATiempo, diasSinMarcacion, late) {

    $('#dash-kpi-wrap').show();

    $('#kpi-puntualidad').text(rate + '%');
    $('#kpi-a-tiempo').text(diasATiempo);
    $('#kpi-sin-marcacion').text(diasSinMarcacion);
    $('#kpi-tarde').text(late);
}

$(document).on('click', '#btn-dash-mostrar', function () {
    const fechas = $('#dash-fechas').val().split(' - ');
    const fechainicio = fechas[0];
    const fechafin = fechas[1];
    const empleado = ($('#dash-empleado').val() || '').trim();
    const metrica = $('#dash-metrica').val();
    const dashTablePage = getDashTableCurrentPage();
    const preserveDashTablePage = dashTableLastEmpleado !== null && dashTableLastEmpleado === empleado;

    resetDashUI();

    if (metrica === 'arrival_hist' && empleado === '') {
        Swal.fire({
            icon: 'warning',
            title: 'Empleado requerido',
            text: 'Para esta métrica debe ingresar la cédula del empleado.'
        });
        return;
    }

    Swal.fire({
        title: 'Cargando información',
        html: 'Por favor espere...',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    $.ajax({
        url: '../../controller/biopro.php?op=dashboardAsistencia',
        type: 'GET',
        dataType: 'json',
        data: { fechainicio, fechafin, empleado, metrica },
        success: function (resp) {
            Swal.close();

            if (!resp || !resp.success) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Validación',
                    text: resp && resp.error ? resp.error : 'No se pudo consultar la métrica seleccionada.'
                });
                return;
            }

            if (metrica === 'arrival_hist') {
                renderArrivalLineChart(
                    resp.labels,
                    resp.entradas_min,
                    resp.objetivo_min,
                    resp.entradas_texto,
                    resp.point_colors
                );

                renderArrivalDetailTable(resp.detalle);
            }

            if (metrica === 'punctuality_rate') {
                renderPunctualityKpi(
                    resp.rate,
                    resp.dias_a_tiempo,
                    resp.dias_sin_marcacion,
                    resp.late
                );
            }

            if (metrica === 'hours_by_employee') {
                renderHoursTable(resp.rows, {
                    preservePage: preserveDashTablePage,
                    page: dashTablePage
                });
            }

            if (metrica === 'time_balance') {
                renderBalanceCards(resp.cards || {});
                renderBalanceTable(resp.rows || [], {
                    preservePage: preserveDashTablePage,
                    page: dashTablePage
                });
            }

            if (metrica === 'absenteeism_by_area') {
                $('#nota-ausentismo').show();
                renderGroupedBars(
                    resp.labels,
                    resp.series,
                    'Ausentismo por U.N (BIOTIME)'
                );
            }

            dashTableLastEmpleado = empleado;
        },
        error: function (e) {
            Swal.close();

            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Ocurrió un error consultando la información'
            });

            console.log(e.responseText);
        }
    });
});

$(function () {
    initDashboardDateRange();
});

init();
