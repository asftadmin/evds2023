// Prueba en Chrome con API simulada: no registra tickets ni archivos reales.
const { chromium } = require('playwright');
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const root = path.resolve(__dirname, '..');
(async () => {
    const browser = await chromium.launch({ channel: 'chrome', headless: true });
    try {
        const page = await browser.newPage({ acceptDownloads: true });
        const errors = [];
        page.on('pageerror', e => errors.push(e.message));
        const scripts = ['jquery/jquery.min.js', 'bootstrap/js/bootstrap.bundle.min.js',
            'datatables/jquery.dataTables.min.js', 'datatables-bs4/js/dataTables.bootstrap4.min.js',
            'datatables-responsive/js/dataTables.responsive.min.js', 'select2/js/select2.full.min.js',
            'sweetalert2/sweetalert2.min.js', 'dropzone/dropzone.js']
            .map(file => '<script src="/public/plugins/' + file + '"></script>').join('');
        const bloque = fs.readFileSync(path.join(root, 'view/MntTicketsSistemas/tickets_sistemas.php'), 'utf8')
            .replace(/<\?php[\s\S]*?\?>/g, '').replace(/<script[\s\S]*?<\/script>/g, '');
        const html = bloque.replace('</head>', '<link rel="stylesheet" href="/public/plugins/css/adminlte.min.css"><link rel="stylesheet" href="/public/css/inicio.css"><link rel="stylesheet" href="/public/plugins/select2/css/select2.min.css"><link rel="stylesheet" href="/public/plugins/select2-bootstrap4-theme/select2-bootstrap4.css"></head>')
            .replace('</body>', scripts + '<script src="/view/MntTicketsSistemas/tickets_sistemas.js"></script></body>');
        let falloEliminar = true;
        let falloGuardar = true;
        let cargas = 0;
        let creaciones = 0;
        let demoraCarga = false;
        const token = 'a'.repeat(64);
        // Sirve archivos locales y comprueba el contrato AJAX sin servicios externos.
        await page.route('http://tickets.test/**', async route => {
            const url = new URL(route.request().url());
            if (url.pathname.endsWith('/controller/tickets_sistemas.php')) {
                const op = url.searchParams.get('op');
                const ok = data => route.fulfill({ contentType: 'application/json', body: JSON.stringify({ success: true, data }) });
                if (op === 'empleado') return ok({ nombre: 'Prueba', documento: '900000001' });
                if (op === 'categorias') return ok([{ categoria_id: 1, nombre: 'Prueba' }]);
                if (op === 'misTickets') return ok([]);
                if (op === 'prepararSoportes') return ok({ soporte_token: token });
                if (op === 'subirSoporte') {
                    assert.match(route.request().postDataBuffer().toString(), /name="soporte_token"/);
                    assert.match(route.request().postDataBuffer().toString(), new RegExp(token));
                    if (demoraCarga) await new Promise(resolve => setTimeout(resolve, 500));
                    return ok({ soporte_id: ++cargas });
                }
                if (op === 'eliminarSoporte') {
                    if (falloEliminar) return route.fulfill({ status: 422, contentType: 'application/json', body: JSON.stringify({ success: false, message: 'Fallo simulado al eliminar' }) });
                    return ok(null);
                }
                if (op === 'crear') {
                    creaciones++;
                    assert.equal(new URLSearchParams(route.request().postData()).get('soporte_token'), token);
                    if (falloGuardar) return route.fulfill({ status: 422, contentType: 'application/json', body: JSON.stringify({ success: false, message: 'Fallo simulado al crear' }) });
                    return ok({ ticket_id: 1, ticket_numero: 'TS-PRUEBA' });
                }
                if (op === 'listarSoportes') return ok([{ soporte_id: 1, soporte_nombre: '<img src=x>.png' }]);
                if (op === 'descargarSoporte') return ok({ soporte_nombre: 'prueba.pdf', soporte_mime: 'application/pdf', contenido: Buffer.from('%PDF-1.4').toString('base64') });
                throw new Error('Operación inesperada: ' + op);
            }
            if (url.pathname.endsWith('/tickets_sistemas.php')) return route.fulfill({ contentType: 'text/html', body: html });
            const file = path.resolve(root, '.' + decodeURIComponent(url.pathname));
            if (!file.startsWith(root + path.sep) || !fs.existsSync(file)) return route.fulfill({ status: 404, body: '' });
            return route.fulfill({ path: file, contentType: file.endsWith('.js') ? 'application/javascript' : 'text/css' });
        });
        await page.goto('http://tickets.test/view/MntTicketsSistemas/tickets_sistemas.php');
        await page.waitForFunction(() => typeof soportesTicket !== 'undefined' && soportesTicket);
        // El filtro exterior también debe permanecer abierto y aceptar una selección.
        await page.locator('#filtro-estado + .select2 .select2-selection').click();
        await page.waitForTimeout(350);
        assert.equal(await page.evaluate(() => $('#filtro-estado').data('select2').isOpen()), true);
        await page.getByRole('option', { name: 'Abierto', exact: true }).click();
        assert.equal(await page.locator('#filtro-estado').inputValue(), 'ABIERTO');
        await page.click('#btn-nuevo-ticket');
        await page.waitForFunction(() => $('#ticket-soporte-token').val().length === 64);
        await page.waitForFunction(() => $('#modal-nuevo-ticket').hasClass('show') && !$('#modal-nuevo-ticket').data('bs.modal')._isTransitioning);
        // Abre repetidamente los Select2 del modal y comprueba que no se cierren solos.
        for (const id of ['ticket-tipo', 'ticket-categoria', 'ticket-prioridad', 'ticket-ubicacion']) {
            for (let intento = 0; intento < 2; intento++) {
                await page.locator('#' + id + ' + .select2 .select2-selection').click({ timeout: 5000 });
                await page.waitForTimeout(350);
                assert.equal(await page.evaluate(id => $('#' + id).data('select2').isOpen(), id), true, id + ' permanece abierto');
                const buscador = page.locator('.select2-container--open .select2-search__field:visible');
                if (await buscador.count()) {
                    await buscador.fill('Pr');
                    assert.equal(await page.evaluate(id => $('#' + id).data('select2').isOpen(), id), true, id + ' permite buscar');
                }
                await page.evaluate(id => $('#' + id).select2('close'), id);
            }
        }
        // Selecciona por búsqueda y reabre el modal sin crear otra instancia de Select2.
        await page.locator('#ticket-ubicacion + .select2 .select2-selection').click();
        await page.locator('.select2-container--open .select2-search__field:visible').fill('TESORERIA');
        await page.getByRole('option', { name: 'TESORERIA', exact: true }).click();
        assert.equal(await page.locator('#ticket-ubicacion').inputValue(), 'TESORERIA');
        await page.evaluate(() => { window.instanciaSelectPrueba = $('#ticket-tipo').data('select2'); $('#modal-nuevo-ticket').modal('hide'); });
        await page.waitForFunction(() => !$('#modal-nuevo-ticket').hasClass('show') && !$('#modal-nuevo-ticket').data('bs.modal')._isTransitioning);
        await page.click('#btn-nuevo-ticket');
        await page.waitForFunction(() => $('#modal-nuevo-ticket').hasClass('show') && !$('#modal-nuevo-ticket').data('bs.modal')._isTransitioning && $('#ticket-soporte-token').val().length === 64);
        assert.equal(await page.evaluate(() => window.instanciaSelectPrueba === $('#ticket-tipo').data('select2')), true);
        await page.locator('#ticket-tipo + .select2 .select2-selection').click();
        await page.getByRole('option', { name: 'Solicitud', exact: true }).click();
        assert.equal(await page.locator('#ticket-tipo').inputValue(), 'SOLICITUD');
        const agregar = nombre => page.evaluate(nombre => soportesTicket.addFile(new File(['%PDF-1.4'], nombre, { type: 'application/pdf' })), nombre);
        await agregar('prueba.pdf');
        await page.waitForFunction(() => soportesTicket.files[0].soporteId);
        assert.equal(await page.locator('.dz-preview').count(), 1);
        await page.locator('.dz-preview button').click();
        await page.waitForFunction(() => eliminandoSoportesTicket === 0);
        assert.equal(await page.evaluate(() => soportesTicket.files.length), 1, 'El fallo de eliminación conserva el soporte');
        await page.evaluate(() => Swal.close());
        falloEliminar = false;
        await page.locator('.dz-preview button').click();
        await page.waitForFunction(() => soportesTicket.files.length === 0);
        demoraCarga = true;
        await agregar('otro.pdf');
        await page.evaluate(() => crearTicketSistemas());
        assert.equal(creaciones, 0, 'No crea mientras haya archivos pendientes');
        await page.evaluate(() => Swal.close());
        await page.waitForFunction(() => soportesTicket.files[0].soporteId);
        await page.evaluate(() => {
            $('#ticket-tipo').val('SOLICITUD').trigger('change');
            $('#ticket-categoria').val('1').trigger('change');
            $('#ticket-asunto').val('Prueba'); $('#ticket-descripcion').val('Descripción');
            crearTicketSistemas();
        });
        await page.waitForFunction(() => !enviandoTicket);
        assert.equal(await page.evaluate(() => soportesTicket.files.length), 1, 'Un error al crear conserva soportes');
        await page.evaluate(() => Swal.close());
        falloGuardar = false;
        await page.evaluate(() => crearTicketSistemas());
        await page.waitForFunction(() => soportesTicket.files.length === 0 && !enviandoTicket);
        assert.equal(creaciones, 2);
        await page.evaluate(() => { Swal.close(); cargarSoportesTicket(1); $('#modal-detalle-ticket').modal('show'); });
        await page.waitForSelector('#detalle-ticket-soportes button');
        assert.equal(await page.locator('#detalle-ticket-soportes img').count(), 0, 'Nombre externo escapado');
        const descarga = page.waitForEvent('download');
        await page.click('#detalle-ticket-soportes button');
        assert.equal((await descarga).suggestedFilename(), 'prueba.pdf');
        assert.deepEqual(errors, []);
        console.log('Vista: Select2 repetidos, búsqueda, selección y reapertura; carga, espera, eliminación, reintento, asociación y descarga correctos.');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
