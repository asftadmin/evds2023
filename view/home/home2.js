$(function () {
    'use strict';

    // Conserva la distribución de accesos existente en el dashboard.
    if ($('#rol_idx').val() === '4') {
        $('#card_mensual, #card_aprobaciones').hide();
        $('#card_anual').removeClass('col-lg-3').addClass('col-lg-4 offset-lg-2');
        $('#card_solicitudes').removeClass('col-lg-3').addClass('col-lg-4');
    }

    const idioma = {
        search: 'Buscar:', lengthMenu: 'Mostrar _MENU_ registros',
        info: 'Mostrando _START_ a _END_ de _TOTAL_ registros', infoEmpty: 'Sin registros',
        infoFiltered: '(de _MAX_ registros)', emptyTable: 'No hay marcaciones en el periodo consultado.',
        zeroRecords: 'No hay resultados para la búsqueda.',
        paginate: { first: 'Primera', last: 'Última', next: 'Siguiente', previous: 'Anterior' }
    };
    const texto = $.fn.dataTable.render.text();
    const columna = campo => ({ data: campo, defaultContent: '—', render: texto });
    const distribucion = "<'row mb-2'<'col-12'B>><'row'<'col-sm-6'l><'col-sm-6'f>>rt<'row'<'col-sm-6'i><'col-sm-6'p>>";

    function botonesExportacion(vista) {
        const titulo = 'Mis marcaciones BioTime - ' + vista;
        const opciones = { columns: ':visible', orthogonal: 'display', modifier: { search: 'applied', order: 'applied', page: 'all' } };
        const contexto = () => $('#marcaciones-empleado').text() + ' | Periodo: ' + $('#marcaciones-periodo').val();
        const archivo = () => 'Mis_marcaciones_' + vista.replace(/\s+/g, '_') + '_' + $('#marcaciones-periodo').val().replace(/[^0-9]/g, '');
        return [
            { extend: 'copyHtml5', text: 'Copiar', title: titulo, exportOptions: opciones },
            { extend: 'csvHtml5', text: 'CSV', filename: archivo, bom: true, exportOptions: opciones },
            { extend: 'excelHtml5', text: 'Excel', title: titulo, filename: archivo, exportOptions: opciones },
            { text: 'PDF', className: 'buttons-pdf', enabled: false, action: function () { exportarPdf(); } },
            { extend: 'print', text: 'Imprimir', title: titulo,
                messageTop: () => $('<div>').text(contexto()).html(), exportOptions: opciones },
            { extend: 'colvis', text: 'Columnas' }
        ];
    }

    const resumen = $('#tabla-marcaciones-resumen').DataTable({
        data: [], responsive: true, language: idioma, order: [[0, 'desc']], pageLength: 10,
        dom: distribucion, buttons: botonesExportacion('Resumen diario'),
        columns: [columna('fecha'), columna('entrada'), columna('salida'), columna('tiempo_bruto'),
            columna('marcaciones'), columna('observacion')]
    });
    const detalle = $('#tabla-marcaciones-detalle').DataTable({
        data: [], responsive: true, language: idioma, order: [[0, 'desc'], [1, 'desc']], pageLength: 10,
        dom: distribucion, buttons: botonesExportacion('Todas las marcaciones'),
        columns: [
            { data: 'fecha', render: (valor, tipo) => tipo === 'display' ? moment(valor, 'YYYY-MM-DD').format('DD-MM-YYYY') : valor },
            { data: 'hora', render: (valor, tipo) => tipo === 'display' ? moment(valor, 'HH:mm:ss').format('hh:mm:ss A') : valor },
            columna('empleado'), columna('cargo'), columna('grupo')
        ]
    });
    let solicitud = null;
    let secuencia = 0;
    let periodoPdf = null;
    let exportando = false;

    function actualizarPdf() {
        const rango = $('#marcaciones-periodo').data('daterangepicker');
        const habilitado = !solicitud && !exportando && periodoPdf && rango &&
            periodoPdf.desde === rango.startDate.format('YYYY-MM-DD') && periodoPdf.hasta === rango.endDate.format('YYYY-MM-DD');
        resumen.button('.buttons-pdf').enable(!!habilitado);
        detalle.button('.buttons-pdf').enable(!!habilitado);
        return !!habilitado;
    }

    function exportarPdf() {
        if (!actualizarPdf()) return;
        const periodo = Object.assign({}, periodoPdf);
        exportando = true;
        actualizarPdf();
        $('#marcaciones-periodo, #btn-consultar-marcaciones').prop('disabled', true);
        $.ajax({
            url: '../../controller/marcaciones_empleado.php', type: 'GET', dataType: 'json', timeout: 95000,
            data: Object.assign({ op: 'pdf' }, periodo)
        }).done(function (respuesta) {
            try {
                if (!respuesta.success || !respuesta.data || !respuesta.data.pdf_base64) throw new Error('No se recibió un PDF válido.');
                const contenido = atob(respuesta.data.pdf_base64);
                if (contenido.slice(0, 5) !== '%PDF-') throw new Error('El archivo recibido no es un PDF válido.');
                const bytes = new Uint8Array(contenido.length);
                for (let i = 0; i < contenido.length; i++) bytes[i] = contenido.charCodeAt(i);
                const url = URL.createObjectURL(new Blob([bytes], { type: 'application/pdf' }));
                const enlace = document.createElement('a');
                enlace.href = url;
                enlace.download = respuesta.data.archivo;
                document.body.appendChild(enlace);
                enlace.click();
                enlace.remove();
                setTimeout(() => URL.revokeObjectURL(url), 1000);
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'No fue posible exportar', text: error.message });
            }
        }).fail(function (xhr) {
            Swal.fire({ icon: 'error', title: 'No fue posible exportar',
                text: (xhr.responseJSON && xhr.responseJSON.message) || 'No fue posible generar el PDF. Intente nuevamente.' });
        }).always(function () {
            exportando = false;
            $('#marcaciones-periodo, #btn-consultar-marcaciones').prop('disabled', false);
            actualizarPdf();
        });
    }

    function mostrarVista() {
        const verDetalle = $('#marcaciones-vista').val() === 'detalle';
        $('#marcaciones-resumen').toggle(!verDetalle);
        $('#marcaciones-detalle').toggle(verDetalle);
        (verDetalle ? detalle : resumen).columns.adjust().responsive.recalc();
    }

    function consultar() {
        if (exportando) return;
        const numero = ++secuencia;
        if (solicitud) solicitud.abort();
        const rango = $('#marcaciones-periodo').data('daterangepicker');
        const periodo = { desde: rango.startDate.format('YYYY-MM-DD'), hasta: rango.endDate.format('YYYY-MM-DD') };
        periodoPdf = null;
        actualizarPdf();
        resumen.clear().draw();
        detalle.clear().draw();
        $('#marcaciones-empleado').text('Consultando sus marcaciones...');
        $('#marcaciones-estado').removeClass('alert-warning alert-danger').addClass('alert-light').text('Cargando BioTime...');
        $('#btn-consultar-marcaciones').prop('disabled', true).attr('aria-busy', 'true')
            .html('<i class="fas fa-spinner fa-spin mr-1"></i>Consultando...');
        solicitud = $.ajax({
            url: '../../controller/marcaciones_empleado.php', type: 'GET', dataType: 'json', timeout: 95000,
            data: periodo
        }).done(function (respuesta) {
            if (numero !== secuencia) return;
            if (!respuesta.success || !respuesta.data) {
                mostrarError('La respuesta de marcaciones no es válida.');
                return;
            }
            const datos = respuesta.data;
            $('#marcaciones-empleado').text(datos.empleado.nombre);
            resumen.rows.add(datos.resumen || []).draw();
            detalle.rows.add(datos.detalle || []).draw();
            const totales = datos.totales || {};
            periodoPdf = totales.marcaciones > 0 ? periodo : null;
            $('#marcaciones-estado').text((totales.marcaciones || 0) + ' marcaciones en ' + (totales.dias || 0) +
                ' días. ' + (totales.incompletos || 0) + ' días con cantidad impar de marcaciones.');
            if (totales.incompletos) $('#marcaciones-estado').removeClass('alert-light').addClass('alert-warning');
            mostrarVista();
        }).fail(function (xhr, estado) {
            if (numero !== secuencia || estado === 'abort') return;
            mostrarError((xhr.responseJSON && xhr.responseJSON.message) ||
                'No fue posible consultar BioTime. Intente nuevamente.');
        }).always(function () {
            if (numero !== secuencia) return;
            solicitud = null;
            actualizarPdf();
            $('#btn-consultar-marcaciones').prop('disabled', false).attr('aria-busy', 'false')
                .html('<i class="fas fa-search mr-1"></i>Consultar');
        });
    }

    function mostrarError(mensaje) {
        $('#marcaciones-empleado').text('Mis marcaciones');
        $('#marcaciones-estado').removeClass('alert-light alert-warning').addClass('alert-danger').text(mensaje);
        Swal.fire({ icon: 'error', title: 'No fue posible consultar sus marcaciones', text: mensaje });
    }

    $('#marcaciones-vista').select2({ theme: 'bootstrap4', minimumResultsForSearch: Infinity, width: '100%' })
        .on('change', mostrarVista);
    $('#marcaciones-periodo').daterangepicker({
        startDate: moment().startOf('isoWeek'),
        endDate: moment(),
        showDropdowns: true, maxDate: moment(),
        ranges: {
            'Hoy': [moment(), moment()],
            'Ayer': [moment().subtract(1, 'day'), moment().subtract(1, 'day')],
            'Esta semana': [moment().startOf('isoWeek'), moment()],
            'Últimos 7 días': [moment().subtract(6, 'days'), moment()],
            'Últimos 30 días': [moment().subtract(29, 'days'), moment()],
            'Este mes': [moment().startOf('month'), moment()],
            'Mes anterior': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        },
        locale: { format: 'DD/MM/YYYY', separator: ' - ', applyLabel: 'Aplicar', cancelLabel: 'Cancelar',
            customRangeLabel: 'Rango personalizado', firstDay: 1,
            daysOfWeek: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'],
            monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'] }
    });
    $('#form-marcaciones-empleado').on('submit', function (evento) { evento.preventDefault(); consultar(); });
    $('#marcaciones-periodo').on('apply.daterangepicker', consultar);
    mostrarVista();
    consultar();
});
