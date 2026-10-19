/* Requiere Playwright en NODE_PATH y Chrome. BioTime y datos personales simulados. */
const { chromium } = require('playwright');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname, '..');
(async () => {
    const browser = await chromium.launch({ channel: 'chrome', headless: true });
    try {
        const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        let modo = 'datos';
        const consultas = [];
        const datos = {
            empleado: { nombre: 'Empleado de prueba', cargo: 'Auxiliar', grupo: 'Produccion' },
            resumen: [
                { fecha: '2026-10-19', entrada: '08:00:00', salida: '17:30:00', tiempo_bruto: '09:30:00', marcaciones: 4, observacion: '' },
                { fecha: '2026-10-20', entrada: '08:00:00', salida: null, tiempo_bruto: null, marcaciones: 1, observacion: 'Cantidad impar de marcaciones; revisar.' }
            ],
            detalle: [
                ...['08:00:00', '12:00:00', '13:00:00', '17:30:00'].map(hora => ({ fecha: '2026-10-19', hora,
                    empleado: 'Empleado de prueba', cargo: 'Auxiliar', grupo: 'Produccion' })),
                { fecha: '2026-10-20', hora: '08:00:00', empleado: 'Empleado de prueba',
                    cargo: '<img src=x onerror=fallo()>', grupo: 'Produccion' }
            ],
            totales: { marcaciones: 5, dias: 2, pares: 2, incompletos: 1 }
        };
        const estilos = ['css/adminlte.min.css', 'datatables-bs4/css/dataTables.bootstrap4.min.css',
            'datatables-responsive/css/responsive.bootstrap4.min.css', 'datatables-buttons/css/buttons.bootstrap4.min.css', 'select2/css/select2.min.css',
            'daterangepicker/daterangepicker.css', 'sweetalert2/sweetalert2.min.css']
            .map(file => '<link rel="stylesheet" href="/public/plugins/' + file + '">').join('');
        const scripts = ['jquery/jquery.min.js', 'bootstrap/js/bootstrap.bundle.min.js',
            'datatables/jquery.dataTables.min.js', 'datatables-bs4/js/dataTables.bootstrap4.min.js',
            'datatables-responsive/js/dataTables.responsive.min.js', 'datatables-responsive/js/responsive.bootstrap4.min.js',
            'datatables-buttons/js/dataTables.buttons.min.js', 'datatables-buttons/js/buttons.bootstrap4.min.js',
            'jszip/jszip.min.js', 'pdfmake/pdfmake.min.js', 'pdfmake/vfs_fonts.js',
            'datatables-buttons/js/buttons.html5.min.js', 'datatables-buttons/js/buttons.print.min.js',
            'datatables-buttons/js/buttons.colVis.min.js',
            'daterangepicker/moment.min.js', 'daterangepicker/daterangepicker.js',
            'select2/js/select2.full.min.js', 'sweetalert2/sweetalert2.min.js']
            .map(file => '<script src="/public/plugins/' + file + '"></script>').join('');
        const bloque = fs.readFileSync(path.join(root, 'view/home/marcaciones_empleado.php'), 'utf8').replace(/<\?php[\s\S]*?\?>/g, '');
        const html = '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">' + estilos + '</head><body>' +
            '<input id="rol_idx" value="4"><div id="card_mensual"></div><div id="card_aprobaciones"></div>' +
            '<div id="card_anual" class="col-lg-3"></div><div id="card_solicitudes" class="col-lg-3"></div>' + bloque + scripts +
            '<script src="/view/home/home2.js"></script></body></html>';
        await page.route('http://marcaciones.test/**', async route => {
            const url = new URL(route.request().url());
            if (url.pathname === '/controller/marcaciones_empleado.php') {
                consultas.push(url);
                if (modo === 'error') return route.fulfill({ status: 503, contentType: 'application/json',
                    body: JSON.stringify({ success: false, message: 'BioTime no respondió. Intente nuevamente.' }) });
                const data = modo === 'vacio' ? { ...datos, resumen: [], detalle: [], totales: {} } : datos;
                return route.fulfill({ contentType: 'application/json', body: JSON.stringify({ success: true, data }) });
            }
            if (url.pathname.endsWith('/home2.php')) return route.fulfill({ contentType: 'text/html', body: html });
            const file = path.resolve(root, '.' + decodeURIComponent(url.pathname));
            if (!file.startsWith(root + path.sep) || !fs.existsSync(file)) return route.fulfill({ status: 404, body: '' });
            return route.fulfill({ path: file, contentType: file.endsWith('.js') ? 'application/javascript' : 'text/css' });
        });
        await page.goto('http://marcaciones.test/view/home/home2.php');
        await page.waitForFunction(() => document.querySelector('#marcaciones-estado').textContent.includes('5 marcaciones'));
        assert.equal(consultas.length, 1);
        assert.deepEqual(await page.evaluate(() => Object.keys($('#marcaciones-periodo').data('daterangepicker').ranges)),
            ['Hoy', 'Ayer', 'Esta semana', 'Últimos 7 días', 'Últimos 30 días', 'Este mes', 'Mes anterior']);
        assert.equal(await page.evaluate(() => {
            const rango = $('#marcaciones-periodo').data('daterangepicker');
            return rango.startDate.format('YYYY-MM-DD') === moment().startOf('isoWeek').format('YYYY-MM-DD') &&
                rango.endDate.format('YYYY-MM-DD') === moment().format('YYYY-MM-DD');
        }), true);
        assert.deepEqual([...consultas[0].searchParams.keys()].sort(), ['desde', 'hasta']);
        const esperado = await page.evaluate(() => [moment().startOf('isoWeek').format('YYYY-MM-DD'), moment().format('YYYY-MM-DD')]);
        assert.equal(consultas[0].searchParams.get('desde'), esperado[0]);
        assert.equal(consultas[0].searchParams.get('hasta'), esperado[1]);
        assert.equal(await page.locator('#marcaciones-empleado').innerText(), 'Empleado de prueba');
        assert.equal(await page.locator('#card_mensual').isVisible(), false);
        assert.equal(await page.locator('#tabla-marcaciones-resumen tbody tr').count(), 2);
        assert.equal(await page.locator('#marcaciones-estado.alert-warning').count(), 1);
        assert.deepEqual(await page.locator('#tabla-marcaciones-resumen_wrapper .dt-buttons .btn').allTextContents(),
            ['Copiar', 'CSV', 'Excel', 'PDF', 'Imprimir', 'Columnas']);
        console.log('OK: consulta automática personal, sin parámetros de identidad y con resumen diario');
        await page.evaluate(() => $('#marcaciones-vista').val('detalle').trigger('change'));
        assert.equal(await page.locator('#marcaciones-detalle').isVisible(), true);
        assert.equal(await page.locator('#tabla-marcaciones-detalle tbody tr').count(), 5);
        assert.deepEqual(await page.locator('#tabla-marcaciones-detalle thead th').allTextContents(), ['Fecha', 'Hora', 'Empleado', 'Cargo', 'Grupo']);
        assert.match(await page.locator('#tabla-marcaciones-detalle tbody tr').first().innerText(), /20-10-2026.*08:00:00 AM/s);
        assert.match(await page.locator('#tabla-marcaciones-detalle tbody tr').nth(1).innerText(), /19-10-2026.*05:30:00 PM/s);
        assert.equal(await page.locator('img[src="x"]').count(), 0);
        assert.deepEqual(await page.locator('#tabla-marcaciones-detalle_wrapper .dt-buttons .btn').allTextContents(),
            ['Copiar', 'CSV', 'Excel', 'PDF', 'Imprimir', 'Columnas']);
        const descarga = page.waitForEvent('download');
        await page.locator('#tabla-marcaciones-detalle_wrapper .buttons-csv').click();
        const csv = fs.readFileSync(await (await descarga).path(), 'utf8');
        assert.match(csv, /Fecha.*Hora.*Empleado.*Cargo.*Grupo/);
        assert.match(csv, /20-10-2026/);
        assert.doesNotMatch(csv, /Documento/);
        console.log('OK: botones en ambas tablas y descarga CSV con formato visible y sin documento');
        assert.equal(consultas.length, 1);
        console.log('OK: una fila por marcación, orden descendente, formato de 12 horas y sin documento');
        modo = 'error';
        await page.locator('#btn-consultar-marcaciones').click();
        await page.locator('.swal2-confirm').waitFor();
        assert.match(await page.locator('#marcaciones-estado.alert-danger').innerText(), /BioTime no respondió/);
        assert.equal(await page.locator('#btn-consultar-marcaciones').isEnabled(), true);
        assert.equal(await page.locator('#tabla-marcaciones-detalle tbody .dataTables_empty').count(), 1);
        await page.locator('.swal2-confirm').click();
        modo = 'vacio';
        await page.locator('#btn-consultar-marcaciones').click();
        await page.waitForFunction(() => document.querySelector('#marcaciones-estado').textContent.includes('0 marcaciones'));
        await page.setViewportSize({ width: 390, height: 844 });
        await page.evaluate(() => $('#marcaciones-vista').val('resumen').trigger('change'));
        assert.deepEqual(errors, []);
        const personalizado = await page.evaluate(() => {
            const rango = $('#marcaciones-periodo').data('daterangepicker');
            const inicio = moment().subtract(10, 'days');
            const fin = moment().subtract(7, 'days');
            rango.show();
            rango.setStartDate(inicio);
            rango.setEndDate(fin);
            rango.clickApply();
            return $('#marcaciones-periodo').val() === inicio.format('DD/MM/YYYY') + ' - ' + fin.format('DD/MM/YYYY');
        });
        assert.equal(personalizado, true);
        console.log('OK: consulta inicial de la semana actual, opciones tradicionales y rango personalizado');
        console.log('OK: error visible con SweetAlert2, consulta vacía y pantalla móvil sin errores JavaScript');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
