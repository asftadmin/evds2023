let tabla;

function init() {

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
    const empleado = $('#filtro-empleado').val();
    return $('#asistencia_biotime_v2').dataTable({
        "aProcessing": true,
        "aServerSide": true,
        "searching": true,
        lengthChange: false,
        colReorder: true,
        buttons: [
            'copyHtml5',
            'excelHtml5',
            'csvHtml5',
            'pdfHtml5'
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
            error: function (e) {
                console.log(e.responseText);
            }
        },
        "bDestroy": true,
        "responsive": true,
        "bInfo": true,
        "iDisplayLength": 5,
        "autoWidth": false,
        order: [[2, 'desc']],
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
    $('#filtro-empleado').val('');

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
    $('#arrival-detail-wrap').hide();

    if (dashChartInstance) {
        dashChartInstance.destroy();
        dashChartInstance = null;
    }

    $('#dashTable tbody').empty();
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

/* function resetDashUI() {
    $('#dash-chart-wrap').hide();
    $('#dash-table-wrap').hide();
    $('#dash-kpi-wrap').hide();

    if (dashChartInstance) {
        dashChartInstance.destroy();
        dashChartInstance = null;
    }

    $('#dashTable tbody').empty();
} */

/* function renderBarChart(labels, values, title) {
    $('#dash-chart-wrap').show();
    const ctx = document.getElementById('dashChart').getContext('2d');

    dashChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: title,
                data: values
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: true }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
} */

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

/* function renderArrivalLineChart(labels, entradas, objetivo, entradasTexto) {
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
                    fill: false
                },
                {
                    label: 'Hora objetivo 08:00',
                    data: objetivo,
                    tension: 0,
                    fill: false,
                    borderDash: [6, 6]
                }
            ]
        },
        options: {
            responsive: true,
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
                    min: 360,
                    max: 600,
                    ticks: {
                        callback: function (value) {
                            const h = Math.floor(value / 60);
                            const m = value % 60;
                            return String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
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
} */

/* function renderStackedBars(labels, series, title) {
    $('#dash-chart-wrap').show();
    const ctx = document.getElementById('dashChart').getContext('2d');

    dashChartInstance = new Chart(ctx, {
        type: 'bar',
        data: { labels, datasets: series },
        options: {
            responsive: true,
            plugins: { legend: { display: true } },
            scales: {
                x: { stacked: true },
                y: { stacked: true, beginAtZero: true }
            }
        }
    });
} */

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

function renderHoursTable(rows) {
    $('#dash-table-wrap').show();
    const tbody = $('#dashTable tbody');
    tbody.empty();
    rows.forEach(r => {
        tbody.append(`<tr>
      <td>${r.nombre}</td>
      <td>${r.documento}</td>
      <td>${r.total_horas}</td>
    </tr>`);
    });
}

function renderPunctualityKpi(rate, diasATiempo, diasSinMarcacion, late) {

    $('#dash-kpi-wrap').show();

    $('#kpi-puntualidad').text(rate + '%');
    $('#kpi-a-tiempo').text(diasATiempo);
    $('#kpi-sin-marcacion').text(diasSinMarcacion);
    $('#kpi-tarde').text(late);
}

$(document).on('click', '#btn-dash-mostrar', function () {
    resetDashUI();

    const fechas = $('#dash-fechas').val().split(' - ');
    const fechainicio = fechas[0];
    const fechafin = fechas[1];
    const empleado = $('#dash-empleado').val().trim();
    const metrica = $('#dash-metrica').val();

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

            if (!resp || !resp.success) return;

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
                renderHoursTable(resp.rows);
            }

            if (metrica === 'absenteeism_by_area') {
                $('#nota-ausentismo').show();
                renderGroupedBars(
                    resp.labels,
                    resp.series,
                    'Ausentismo por U.N (BIOTIME)'
                );
            }
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