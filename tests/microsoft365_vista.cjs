/* Chrome con API sintética; no consulta ni modifica usuarios de producción. */
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname, '..');

/** Identidad sintética, compatible con el formato exigido por el servidor. */
function uuid(n) { return '00000000-0000-4000-8000-' + String(n).padStart(12, '0'); }
/** Construye un registro sintético para cada estado de la interfaz. */
function fila(n, estado, accion, actual = null) {
    return { user_id: n, id_empl: 100 + n, empleado: n === 1 ? '<img src=x onerror=alert(1)>' : 'Empleado de prueba con nombre y apellidos completos ' + n,
        correo: 'coordinacion.departamento.prueba' + n + '@asfaltart.com', correo_microsoft: 'coordinacion.departamento.prueba' + n + '@asfaltart.com',
        cuenta_microsoft: 'Cuenta ' + n, encontrado: uuid(n), actual, estado, accion, detalle: 'Conflicto de prueba' };
}

(async () => {
    const browser = await chromium.launch({ channel: 'chrome', headless: true });
    try {
        const page = await browser.newPage({ viewport: { width: 1600, height: 1000 } });
        const errores = [];
        page.on('pageerror', e => errores.push(e.message));
        let consultas = 0, falloGraph = false, conflicto = false;
        const escrituras = [];
        const filas = [fila(1, 'Nuevo', 'guardar'), fila(2, 'Vinculado', 'editar', uuid(2)),
            fila(3, 'Cambio detectado', 'editar', uuid(99)), fila(4, 'Sin coincidencia', 'revisar'),
            fila(10, 'Conflicto', 'revisar'), fila(28, 'Conflicto', 'revisar')];
        const scripts = ['jquery/jquery.min.js', 'bootstrap/js/bootstrap.bundle.min.js',
            'datatables/jquery.dataTables.min.js', 'datatables-bs4/js/dataTables.bootstrap4.min.js',
            'datatables-responsive/js/dataTables.responsive.min.js', 'datatables-responsive/js/responsive.bootstrap4.min.js',
            'select2/js/select2.full.min.js', 'sweetalert2/sweetalert2.min.js']
            .map(s => '<script src="/public/plugins/' + s + '"></script>').join('');
        const estilos = ['css/adminlte.min.css', 'datatables-bs4/css/dataTables.bootstrap4.min.css',
            'datatables-responsive/css/responsive.bootstrap4.min.css', 'select2/css/select2.min.css',
            'select2-bootstrap4-theme/select2-bootstrap4.min.css', 'sweetalert2/sweetalert2.min.css']
            .map(s => '<link rel="stylesheet" href="/public/plugins/' + s + '">').join('');
        const html = fs.readFileSync(path.join(root, 'view/MntMicrosoft365/sincronizacion.php'), 'utf8')
            .replace(/<\?php require_once __DIR__ \. '\/\.\.\/MainHead\/head.php'; \?>/, estilos)
            .replace(/<\?php require_once __DIR__ \. '\/\.\.\/MainJS\/JS.php'; \?>/, scripts)
            .replace(/<\?php[\s\S]*?\?>/g, '').replace('<head>', '<head><meta charset="utf-8">');
        await page.route('http://m365.test/**', async route => {
            const url = new URL(route.request().url());
            if (url.pathname === '/controller/microsoft365.php') {
                assert.equal(route.request().method(), 'POST');
                const body = new URLSearchParams(route.request().postData());
                assert.equal(body.get('csrf_token'), 'csrf-prueba');
                const op = url.searchParams.get('op');
                await new Promise(resolve => setTimeout(resolve, 150));
                if (op === 'consultar') {
                    consultas++;
                    if (falloGraph) return route.fulfill({ status: 502, contentType: 'application/json', body: JSON.stringify({ status: 'error', message: 'Graph no está disponible.' }) });
                } else {
                    escrituras.push({ op, body: Object.fromEntries(body) });
                    if (conflicto) return route.fulfill({ status: 409, contentType: 'application/json', body: JSON.stringify({ status: 'error', message: 'La vinculación cambió.' }) });
                    const row = filas.find(r => r.user_id === Number(body.get('user_id')));
                    assert.equal(body.get('graph_id'), row.encontrado);
                    assert.equal(body.get('id_empl'), String(row.id_empl));
                    row.actual = row.encontrado; row.estado = 'Vinculado'; row.accion = 'editar';
                }
                const indicadores = { total: filas.length, vinculados: filas.filter(r => r.estado === 'Vinculado').length,
                    pendientes: filas.filter(r => r.estado === 'Nuevo').length,
                    revision: filas.filter(r => ['Conflicto', 'Cambio detectado', 'Sin coincidencia'].includes(r.estado)).length };
                return route.fulfill({ contentType: 'application/json', body: JSON.stringify({ status: 'success', message: 'Vinculación guardada.', data: { registros: filas, indicadores } }) });
            }
            if (url.pathname.endsWith('sincronizacion.php')) return route.fulfill({ contentType: 'text/html', body: html });
            const archivo = path.join(root, decodeURIComponent(url.pathname));
            if (fs.existsSync(archivo)) return route.fulfill({ path: archivo });
            return route.fulfill({ status: 404, body: '' });
        });
        await page.goto('http://m365.test/view/MntMicrosoft365/sincronizacion.php');
        await page.waitForFunction(() => !!window.jQuery && $.fn.dataTable.isDataTable('#m365_tabla'));
        await page.locator('#m365_csrf').evaluate(el => { el.value = 'csrf-prueba'; });
        assert.equal(consultas, 0, 'La página no consulta Graph automáticamente');
        await page.click('#m365_consultar');
        assert(await page.locator('#m365_consultar').isDisabled());
        await page.waitForFunction(() => document.querySelector('#m365_total').textContent === '6');
        assert.deepEqual(await page.evaluate(() => $('#m365_tabla').DataTable().order()), [[0, 'asc']], 'Orden inicial por ID');
        assert.deepEqual(await page.evaluate(() => $('#m365_tabla').DataTable().column(0, { order: 'applied' }).data().toArray()), [1, 2, 3, 4, 10, 28], 'Orden numérico');
        assert(await page.evaluate(() => {
            const table = document.querySelector('#m365_tabla');
            return table.getBoundingClientRect().right <= table.closest('.card').getBoundingClientRect().right;
        }), 'La tabla con correos y UUID largos queda dentro de la tarjeta');
        assert.equal(await page.locator('#m365_pendientes').textContent(), '1');
        assert.equal(await page.locator('#m365_revision').textContent(), '4');
        assert.equal(await page.locator('#m365_tabla img').count(), 0, 'Datos externos escapados');
        await page.evaluate(() => $('#m365_estado').val('Conflicto').trigger('change'));
        assert.equal(await page.locator('.m365-accion:visible').count(), 2);
        await page.evaluate(() => $('#m365_estado').val('').trigger('change'));
        await page.click('[data-empleado="101"]');
        await page.click('.swal2-cancel');
        assert.equal(escrituras.length, 0, 'Cancelar no guarda');
        await page.click('[data-empleado="101"]');
        await page.click('.swal2-confirm');
        await page.waitForFunction(() => document.querySelector('#m365_pendientes').textContent === '0');
        await page.click('.swal2-confirm');
        assert.equal(escrituras[0].op, 'guardar');
        assert.equal(consultas, 1, 'Guardar no hace una consulta AJAX adicional');
        await page.evaluate(() => $('#m365_tabla').DataTable().page.len(2).page(1).draw(false));
        assert.equal(await page.locator('.m365-accion:visible:disabled').count(), 0, 'Otras páginas recuperan sus acciones');
        await page.evaluate(() => $('#m365_tabla').DataTable().page.len(10).page(0).draw(false));
        await page.click('[data-empleado="102"]');
        await page.waitForSelector('#m365_modal.show');
        assert(await page.locator('#m365_ed_confirmar').isHidden(), 'Vinculado no permite UPDATE innecesario');
        assert((await page.locator('#m365_ed_aviso').textContent()).includes('actualizada'));
        await page.locator('#m365_modal .modal-footer [data-dismiss]').click();
        await page.waitForSelector('#m365_modal', { state: 'hidden' });
        await page.click('[data-empleado="103"]');
        await page.click('#m365_ed_confirmar');
        await page.click('.swal2-confirm');
        await page.waitForFunction(() => document.querySelector('#m365_vinculados').textContent === '3');
        await page.click('.swal2-confirm');
        assert.equal(escrituras[1].op, 'editar');
        assert.equal(escrituras[1].body.actual, uuid(99));
        await page.click('[data-empleado="110"]');
        assert((await page.locator('.swal2-html-container, #swal2-content').first().textContent()).includes('Conflicto'));
        await page.click('.swal2-confirm');
        assert.equal(escrituras.length, 2, 'Revisar no escribe');
        falloGraph = true;
        await page.click('#m365_consultar');
        await page.waitForFunction(() => document.querySelector('#m365_total').textContent === '—');
        assert.equal(await page.locator('.m365-accion').count(), 0, 'Error no conserva resultados parciales');
        await page.click('.swal2-confirm');
        falloGraph = false;
        filas[0].estado = 'Nuevo'; filas[0].actual = null; filas[0].accion = 'guardar';
        await page.click('#m365_consultar');
        await page.waitForFunction(() => document.querySelector('#m365_total').textContent === '6');
        conflicto = true;
        await page.click('[data-empleado="101"]'); await page.click('.swal2-confirm');
        await page.waitForFunction(() => document.querySelector('#m365_total').textContent === '—');
        await page.click('.swal2-confirm');
        await page.setViewportSize({ width: 390, height: 844 });
        await page.click('#m365_consultar');
        await page.waitForFunction(() => document.querySelector('#m365_total').textContent === '6');
        assert(await page.locator('#m365_tabla').isVisible());
        await page.waitForFunction(() => {
            const table = document.querySelector('#m365_tabla');
            return table.getBoundingClientRect().right <= table.closest('.card').getBoundingClientRect().right;
        });
        assert(await page.locator('[data-empleado="101"]').isVisible(), 'Acciones accesibles en móvil');
        assert.deepEqual(errores, []);
        console.log('OK: Chrome, consulta manual, tarjetas, filtro, XSS, confirmación/cancelación, edición, conflictos, fallo Graph y móvil.');
    } finally { await browser.close(); }
})().catch(e => { console.error(e); process.exitCode = 1; });
