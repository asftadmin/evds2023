/* Requiere Playwright disponible en NODE_PATH. No usa la base de datos ni Apache. */
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
        const rows = Array.from({ length: 12 }, (_, i) => ({
            jornada_id: i + 1, dia: 'Lunes', fecha: '2026-09-' + String(i + 1).padStart(2, '0'),
            fecha_salida: '2026-09-' + String(i + 1).padStart(2, '0'),
            hora_entrada: '08:00', hora_salida: '17:00', horas_ordinarias: '08:00',
            ubicacion: i === 0 ? 'Oficina anterior' : 'Sede principal', actividad: 'Actividad ' + (i + 1),
            observaciones: '', estado_codigo: 'BORRADOR', estado_nombre: 'Borrador', cruza_medianoche: false
        }));
        rows.push({ ...rows[0], jornada_id: 13, estado_codigo: 'ANULADO', estado_nombre: 'Anulado',
            anulacion_motivo: '<script>fallo()</script>', anulacion_fecha: '2026-09-16 10:00:00' });
        rows.push({ ...rows[0], jornada_id: 14, estado_codigo: 'APROBADO', estado_nombre: 'Aprobado' });
        let lastBatch = [];
        let lastSave;
        const scripts = [
            'jquery/jquery.min.js', 'bootstrap/js/bootstrap.bundle.min.js',
            'datatables/jquery.dataTables.min.js', 'datatables-bs4/js/dataTables.bootstrap4.min.js',
            'datatables-responsive/js/dataTables.responsive.min.js',
            'datatables-responsive/js/responsive.bootstrap4.min.js',
            'daterangepicker/moment.min.js', 'daterangepicker/daterangepicker.js',
            'sweetalert2/sweetalert2.min.js'
        ].map(file => '<script src="/public/plugins/' + file + '"></script>').join('\n');
        const styles = [
            'css/adminlte.min.css', 'datatables-bs4/css/dataTables.bootstrap4.min.css',
            'datatables-responsive/css/responsive.bootstrap4.min.css', 'sweetalert2/sweetalert2.min.css'
        ].map(file => '<link rel="stylesheet" href="/public/plugins/' + file + '">').join('\n');
        let html = fs.readFileSync(path.join(root, 'view/MntJornadas/mis_jornadas.php'), 'utf8');
        html = html.replace(/<\?php require_once\("\.\.\/MainJS\/JS.php"\); \?>/, scripts)
            .replace(/<\?php[\s\S]*?\?>/g, '')
            .replace('<html lang="es">', '<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">' + styles);

        await page.route('http://jornadas.test/**', async route => {
            const url = new URL(route.request().url());
            const json = data => route.fulfill({ contentType: 'application/json', body: JSON.stringify({ success: true, data }) });
            if (url.pathname === '/controller/jornada.php') {
                const op = url.searchParams.get('op');
                const body = new URLSearchParams(route.request().postData() || '');
                if (op === 'contextoUsuario') return json({ empleado: 'Empleado de prueba', documento: '123' });
                if (op === 'listarMisJornadas') return json(rows);
                if (op === 'validarFecha') {
                    // Respuesta lenta para verificar que la solicitud anterior no sobrescriba la actual.
                    const fecha = url.searchParams.get('fecha');
                    if (fecha === '2026-08-15') await new Promise(resolve => setTimeout(resolve, 200));
                    return json({ disponible: fecha !== '2026-08-15', jornada: fecha === '2026-08-15'
                        ? { jornada_id: 99, estado_nombre: 'Aprobado' } : null });
                }
                if (op === 'calcularHoras') return json({ horas_ordinarias: '08:00', duracion_total: '09:00', descuento_almuerzo: '01:00' });
                if (op === 'obtenerMiJornada') return json(rows.find(row => row.jornada_id === Number(url.searchParams.get('jornada_id'))));
                if (op === 'enviarAprobacionMasiva') {
                    lastBatch = body.getAll('jornada_ids[]').map(Number);
                    const enviados = lastBatch.filter(id => id !== 2);
                    enviados.forEach(id => Object.assign(rows.find(row => row.jornada_id === id), {
                        estado_codigo: 'PENDIENTE_APROBACION', estado_nombre: 'Pendiente de aprobación'
                    }));
                    return json({ enviados, fallidos: lastBatch.includes(2) ? [{ jornada_id: 2, message: 'La jornada cambió de estado.' }] : [] });
                }
                if (op === 'anularBorrador') {
                    const row = rows.find(row => row.jornada_id === Number(body.get('jornada_id')));
                    Object.assign(row, { estado_codigo: 'ANULADO', estado_nombre: 'Anulado',
                        anulacion_motivo: body.get('motivo'), anulacion_fecha: '2026-09-16 11:00:00' });
                    return route.fulfill({ contentType: 'application/json', body: JSON.stringify({ success: true, message: 'Borrador anulado.' }) });
                }
                if (op === 'guardarBorrador') {
                    lastSave = Object.fromEntries(body);
                    return route.fulfill({ contentType: 'application/json', body: JSON.stringify({ success: true, message: 'Borrador guardado.' }) });
                }
                throw new Error('Operación inesperada: ' + op);
            }
            if (url.pathname.endsWith('/mis_jornadas.php')) return route.fulfill({ contentType: 'text/html', body: html });
            const file = path.resolve(root, '.' + decodeURIComponent(url.pathname));
            if (!file.startsWith(root + path.sep) || !fs.existsSync(file)) return route.fulfill({ status: 404, body: '' });
            const ext = path.extname(file);
            return route.fulfill({ path: file, contentType: ext === '.js' ? 'application/javascript' : ext === '.css' ? 'text/css' : 'application/octet-stream' });
        });
        await page.goto('http://jornadas.test/view/MntJornadas/mis_jornadas.php');
        await page.waitForFunction(() => document.querySelector('#texto-contexto').textContent.includes('Empleado de prueba')
            && document.querySelectorAll('.seleccionar-jornada').length > 0);
        assert.deepEqual(await page.locator('#ubicacion option').allTextContents(), ['Seleccione una ubicación', 'Sede principal', 'Obras varias']);
        await page.locator('#seleccionar-jornadas').check();
        assert.equal(await page.locator('#conteo-seleccionadas').textContent(), '12 seleccionadas');
        await page.locator('#tabla-jornadas_next').click();
        assert.equal(await page.locator('#conteo-seleccionadas').textContent(), '12 seleccionadas');
        assert.equal(await page.locator('.seleccionar-jornada:not(:checked)').count(), 0);
        await page.locator('#tabla-jornadas th').nth(2).click();
        assert.equal(await page.locator('#conteo-seleccionadas').textContent(), '12 seleccionadas');
        console.log('OK: Selección de todos los borradores entre páginas y al ordenar');

        await page.locator('#tabla-jornadas_filter input').fill('2026-09-02');
        assert.equal(await page.locator('#conteo-seleccionadas').textContent(), '0 seleccionadas');
        await page.locator('#seleccionar-jornadas').check();
        assert.equal(await page.locator('#conteo-seleccionadas').textContent(), '1 seleccionadas');
        await page.locator('#tabla-jornadas_filter input').fill('');
        assert.equal(await page.locator('#conteo-seleccionadas').textContent(), '0 seleccionadas');
        console.log('OK: Selección restringida al filtro y restablecida al cambiarlo');

        await page.locator('#fecha').fill('2026-08-15');
        await page.locator('#fecha').dispatchEvent('change');
        await page.waitForFunction(() => document.querySelector('#ayuda-fecha').textContent.includes('Ya tiene'));
        assert.equal(await page.locator('#btn-guardar').isDisabled(), true);
        await page.locator('#fecha').fill('2026-08-14');
        await page.locator('#fecha').dispatchEvent('change');
        await page.waitForFunction(() => document.querySelector('#ayuda-fecha').textContent === 'Fecha disponible.');
        assert.equal(await page.locator('#btn-guardar').isEnabled(), true);
        await page.locator('#fecha').fill('2026-08-15');
        await page.locator('#fecha').dispatchEvent('change');
        await page.locator('#fecha').fill('2026-08-14');
        await page.locator('#fecha').dispatchEvent('change');
        await page.waitForTimeout(300);
        assert.equal(await page.locator('#ayuda-fecha').textContent(), 'Fecha disponible.');
        console.log('OK: Fecha duplicada bloqueada y respuestas antiguas descartadas');

        await page.locator('#tabla-jornadas_filter input').fill('2026-09-01');
        await page.locator('.btn-editar[data-id="1"]').click();
        await page.waitForFunction(() => document.querySelector('#jornada_id').value === '1');
        assert.equal(await page.locator('#ubicacion').inputValue(), 'Oficina anterior');
        assert.equal(await page.locator('.jornada-anulacion').textContent().then(text => text.includes('<script>fallo()</script>')), true);
        const gap = await page.locator('.jornada-acciones').last().evaluate(el => getComputedStyle(el).gap);
        assert.notEqual(gap, '0px');
        console.log('OK: Ubicación histórica conservada, motivo escapado y botones separados');

        await page.locator('#tabla-jornadas_filter input').fill('');
        await page.locator('#seleccionar-jornadas').check();
        await page.locator('#btn-enviar-seleccionadas').click();
        await page.locator('.swal2-confirm').click();
        await page.waitForFunction(() => document.querySelector('#resultado-envio').textContent.includes('11 jornada(s)'));
        assert.equal(lastBatch.length, 12);
        assert.equal(await page.locator('#jornada_id').inputValue(), '');
        assert.equal(await page.locator('#resultado-envio').textContent().then(text => text.includes('Jornada #2')), true);
        await page.locator('.swal2-confirm').click();
        console.log('OK: Envío masivo con resultado parcial y formulario actualizado');

        await page.locator('#tabla-jornadas_filter input').fill('2026-09-02');
        await page.locator('.btn-anular[data-id="2"]').click();
        await page.locator('.swal2-confirm').click();
        await page.waitForFunction(() => document.querySelector('.swal2-validation-message').textContent.includes('Indique'));
        await page.locator('.swal2-textarea').fill('Error de digitación');
        await page.locator('.swal2-confirm').click();
        await page.waitForFunction(() => document.querySelector('#swal2-title').textContent === 'Borrador anulado');
        await page.locator('.swal2-confirm').click();
        await page.waitForFunction(() => document.querySelector('.jornada-anulacion')?.textContent.includes('Error de digitación'));
        assert.equal(await page.locator('.btn-anular').count(), 0);
        console.log('OK: Anulación exige motivo y elimina las acciones del registro');

        await page.locator('#fecha').fill('2026-08-14');
        await page.locator('#fecha').dispatchEvent('change');
        await page.locator('#hora_entrada').fill('08:00');
        await page.locator('#hora_salida').fill('17:00');
        await page.locator('#ubicacion').selectOption('Obras varias');
        await page.locator('#actividad').fill('Actividad nueva');
        await page.waitForFunction(() => !document.querySelector('#btn-guardar').disabled);
        await page.locator('#btn-guardar').click();
        await page.waitForFunction(() => document.querySelector('#swal2-title')?.textContent === 'Borrador guardado');
        assert.equal(lastSave.ubicacion, 'Obras varias');
        console.log('OK: Guardado usa la ubicación seleccionada');

        await page.setViewportSize({ width: 390, height: 844 });
        await page.waitForTimeout(300);
        assert.equal(await page.locator('#ubicacion').isVisible(), true);
        assert.deepEqual(errors, []);
        console.log('OK: Vista móvil sin errores JavaScript');
        console.log('Todas las pruebas de vista pasaron.');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
