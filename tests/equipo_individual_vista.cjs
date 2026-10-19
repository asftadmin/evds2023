/* Requiere Playwright en NODE_PATH y Chrome. Prueba la interfaz sin Apache ni base de datos. */
const { chromium } = require('playwright');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname, '..');

(async () => {
    const browser = await chromium.launch({ channel: 'chrome', headless: true });
    try {
        const page = await browser.newPage({ viewport: { width: 1500, height: 1000 } });
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        const fecha = new Date().toLocaleDateString('sv-SE', { timeZone: 'America/Bogota' });
        const base = { dia: 'Lunes', fecha, fecha_salida: fecha, hora_entrada: '08:00', hora_salida: '17:00',
            horas_ordinarias: '08:00', ubicacion: 'Sede principal', actividad: 'Actividad', observaciones: '', origen: 'REGISTRO_JEFE' };
        const rows = [
            { ...base, jornada_id: 1, empleado_id: 10, estado_codigo: 'BORRADOR', estado_nombre: 'Borrador' },
            { ...base, jornada_id: 2, empleado_id: 20, estado_codigo: 'CONFIRMADO_EMPLEADO', estado_nombre: 'Confirmado por el empleado' },
            { ...base, jornada_id: 3, empleado_id: 30, estado_codigo: 'APROBADO', estado_nombre: 'Aprobado' }
        ];
        const saves = [];
        const approvals = [];
        const cancellations = [];
        let slowEmployee = false;
        const scripts = ['jquery/jquery.min.js', 'bootstrap/js/bootstrap.bundle.min.js',
            'datatables/jquery.dataTables.min.js', 'datatables-bs4/js/dataTables.bootstrap4.min.js',
            'datatables-responsive/js/dataTables.responsive.min.js', 'datatables-responsive/js/responsive.bootstrap4.min.js',
            'daterangepicker/moment.min.js', 'daterangepicker/daterangepicker.js',
            'select2/js/select2.full.min.js', 'sweetalert2/sweetalert2.min.js']
            .map(file => '<script src="/public/plugins/' + file + '"></script>').join('\n');
        const styles = ['css/adminlte.min.css', 'datatables-bs4/css/dataTables.bootstrap4.min.css',
            'datatables-responsive/css/responsive.bootstrap4.min.css', 'select2/css/select2.min.css',
            'sweetalert2/sweetalert2.min.css'].map(file => '<link rel="stylesheet" href="/public/plugins/' + file + '">').join('');
        let html = fs.readFileSync(path.join(root, 'view/MntJornadas/equipo_individual.php'), 'utf8')
            .replace(/<\?php require_once \('\.\.\/MainJS\/JS.php'\); \?>/, scripts)
            .replace(/<\?php[\s\S]*?\?>/g, '')
            .replace('<html lang="es">', '<html lang="es"><head><meta charset="utf-8">' + styles);

        await page.route('http://equipo.test/**', async route => {
            const url = new URL(route.request().url());
            const json = payload => route.fulfill({ contentType: 'application/json', body: JSON.stringify({ success: true, ...payload }) });
            if (url.pathname === '/controller/jornada.php') {
                const op = url.searchParams.get('op');
                const body = new URLSearchParams(route.request().postData() || '');
                if (op === 'contextoEquipo') return json({ data: { empleado: 'Jefe de prueba', documento: '100' } });
                if (op === 'listarSubordinadosJefe') return json({ data: [10, 20, 30].map(id => ({
                    empleado_id: id, empleado_nombre: 'Empleado ' + id, empleado_documento: String(id)
                })) });
                if (op === 'listarUbicacionesJornada') return json({ data: ['Sede principal', 'Obras varias', 'Mario Huertas', 'Isagen', 'La Toscana', 'Findeter Atta'] });
                if (op === 'calcularHorasEquipo') return json({ data: { horas_ordinarias: '08:00' } });
                if (op === 'listarJornadasEquipo') {
                    const id = Number(url.searchParams.get('empleado_id'));
                    const data = rows.filter(row => row.empleado_id === id);
                    const activas = data.filter(row => !['ANULADO', 'RECHAZADO', 'APROBADO', 'PENDIENTE_LIQUIDACION', 'LIQUIDADO'].includes(row.estado_codigo));
                    const confirmadas = activas.filter(row => row.estado_codigo === 'CONFIRMADO_EMPLEADO');
                    const payload = { data, resumen: { total_jornadas_rango: activas.length, total_confirmadas: confirmadas.length,
                        puede_registrar_aprobar: activas.length > 0 && activas.length === confirmadas.length } };
                    if (slowEmployee && id === 10) await new Promise(resolve => setTimeout(resolve, 300));
                    return json(payload);
                }
                if (op === 'guardarBorradoresEquipoMasivo') {
                    const data = JSON.parse(body.get('jornadas'));
                    saves.push({ empleado: Number(body.get('empleado_id')), jornadas: data });
                    for (const fila of data) {
                        const existing = rows.find(row => row.jornada_id === fila.jornada_id);
                        const nuevo = { ...base, ...fila, jornada_id: fila.jornada_id || 99, empleado_id: Number(body.get('empleado_id')),
                            estado_codigo: 'BORRADOR', estado_nombre: 'Borrador' };
                        if (existing) Object.assign(existing, nuevo); else rows.push(nuevo);
                    }
                    return json({ message: 'Guardado' });
                }
                if (op === 'guardarJornadaEquipoVarios') {
                    const ids = JSON.parse(body.get('empleados_ids')).map(Number);
                    const fila = JSON.parse(body.get('jornada'));
                    if (fila.actividad === 'FALLAR') return route.fulfill({ status: 409, contentType: 'application/json',
                        body: JSON.stringify({ success: false, message: 'Empleado ID 30: Se revirtió todo el registro.' }) });
                    saves.push({ empleados: ids, jornadas: [fila] });
                    ids.forEach((id, i) => rows.push({ ...base, ...fila, jornada_id: 99 + i, empleado_id: id,
                        estado_codigo: 'BORRADOR', estado_nombre: 'Borrador' }));
                    return json({ message: ids.length + ' jornadas guardadas.' });
                }
                if (op === 'registrarAprobarEquipoIndividual') {
                    approvals.push(Object.fromEntries(body));
                    rows.filter(row => row.empleado_id === Number(body.get('empleado_id'))).forEach(row => Object.assign(row, {
                        estado_codigo: 'PENDIENTE_LIQUIDACION', estado_nombre: 'Pendiente de liquidación'
                    }));
                    return json({ message: 'Periodo aprobado.' });
                }
                if (op === 'anularBorradorEquipo') {
                    cancellations.push(Object.fromEntries(body));
                    Object.assign(rows.find(row => row.jornada_id === Number(body.get('jornada_id'))), {
                        estado_codigo: 'ANULADO', estado_nombre: 'Anulado'
                    });
                    return json({ message: 'Anulado' });
                }
                throw new Error('Operación inesperada: ' + op);
            }
            if (url.pathname.endsWith('/equipo_individual.php')) return route.fulfill({ contentType: 'text/html', body: html });
            const file = path.resolve(root, '.' + decodeURIComponent(url.pathname));
            if (!file.startsWith(root + path.sep) || !fs.existsSync(file)) return route.fulfill({ status: 404, body: '' });
            return route.fulfill({ path: file, contentType: file.endsWith('.js') ? 'application/javascript' : 'text/css' });
        });

        await page.goto('http://equipo.test/view/MntJornadas/equipo_individual.php');
        await page.waitForFunction(() => !document.querySelector('#empleado_id').disabled);
        assert.deepEqual(await page.evaluate(() => {
            const rango = $('#filtro_fechas').data('daterangepicker');
            const anterior = JornadasPeriodo.predeterminado();
            if (rango.startDate.format('YYYY-MM-DD') !== anterior.inicio.format('YYYY-MM-DD') ||
                rango.endDate.format('YYYY-MM-DD') !== anterior.fin.format('YYYY-MM-DD')) throw new Error('Debe iniciar en el periodo anterior');
            return [rango.startDate.date(), rango.endDate.date(),
                rango.endDate.format('YYYY-MM') === rango.startDate.clone().add(1, 'month').format('YYYY-MM')];
        }), [16, 15, true]);
        await page.evaluate(() => {
            const rango = $('#filtro_fechas').data('daterangepicker');
            rango.show();
            rango.setStartDate(moment('2026-02-04'));
            rango.setEndDate(moment('2026-02-25'));
            rango.clickApply();
        });
        assert.equal(await page.locator('#filtro_fechas').inputValue(), '2026-01-16 - 2026-02-15');
        await page.evaluate(() => JornadasPeriodo.seleccionar('#filtro_fechas'));
        const select = id => page.evaluate(value => {
            $('#empleado_id').val([value]).trigger('change');
            $('#historial_empleado_id').val(value).trigger('change');
        }, String(id));
        const settled = () => page.waitForFunction(() => !document.querySelector('#resumen-confirmacion').textContent.includes('Consultando'));
        const confirm = () => page.locator('.swal2-confirm').click();
        await select(10);
        await settled();
        await page.evaluate(() => {
            window.pdfLoteAbierto = '';
            window.open = url => { window.pdfLoteAbierto = url; };
            $('#empleado_id').val(['__todos__']).trigger('change');
        });
        await page.locator('#btn-pdf-lote').click();
        let enlaceLote = new URL(await page.evaluate(() => window.pdfLoteAbierto), 'http://equipo.test/view/MntJornadas/');
        assert.equal(enlaceLote.searchParams.get('tipo'), 'pdf_borrador_equipo_lote');
        assert.equal(enlaceLote.searchParams.get('todos'), '1');
        await page.evaluate(() => $('#empleado_id').val(['10', '30']).trigger('change'));
        await page.locator('#btn-pdf-lote').click();
        enlaceLote = new URL(await page.evaluate(() => window.pdfLoteAbierto), 'http://equipo.test/view/MntJornadas/');
        assert.deepEqual(JSON.parse(enlaceLote.searchParams.get('empleados_ids')), ['10', '30']);
        assert.equal(enlaceLote.searchParams.get('todos'), null);
        console.log('OK: PDF lote para varios empleados y opción Todos con el periodo seleccionado');
        assert.deepEqual(await page.locator('#ubicacion option').allTextContents(), [
            'Seleccione una ubicación', 'SEDE PRINCIPAL', 'OBRAS VARIAS', 'MARIO HUERTAS', 'ISAGEN', 'LA TOSCANA', 'FINDETER ATTA'
        ]);
        await page.evaluate(() => $('#empleado_id').val(['10', '30']).trigger('change'));
        assert.equal(await page.locator('#btn-registrar-aprobar').isDisabled(), true);
        assert.equal(await page.locator('.editar-jornada').count(), 1);
        await page.locator('#hora_entrada').fill('18:00');
        await page.locator('#hora_salida').fill('22:00');
        await page.locator('#ubicacion').selectOption('Sede principal');
        await page.locator('#actividad').fill('Turno adicional');
        await page.locator('#btn-guardar').click();
        await page.getByText('Registrar jornada para varios empleados', { exact: true }).waitFor();
        await confirm();
        await page.getByText('Borrador guardado', { exact: true }).waitFor();
        await confirm();
        await settled();
        assert.equal(saves.length, 1);
        assert.deepEqual(saves[0].empleados, [10, 30]);
        assert.equal(saves[0].jornadas.length, 1);
        assert.equal(saves[0].jornadas[0].jornada_id, null);
        assert.equal(await page.locator('#actividad').inputValue(), '');
        console.log('OK: selección múltiple, seis obras y una sola petición para todos los empleados');

        await page.locator('#hora_entrada').fill('18:00');
        await page.locator('#hora_salida').fill('22:00');
        await page.locator('#ubicacion').selectOption('La Toscana');
        await page.locator('#actividad').fill('FALLAR');
        await page.locator('#btn-guardar').click();
        await page.getByText('Registrar jornada para varios empleados', { exact: true }).waitFor();
        await confirm();
        await page.getByText('No fue posible completar la operación', { exact: true }).waitFor();
        assert.match(await page.locator('.swal2-html-container, #swal2-content').innerText(), /Se revirtió todo/);
        await confirm();
        await settled();
        assert.equal(saves.length, 1);
        assert.equal(await page.locator('#actividad').inputValue(), 'FALLAR');
        assert.deepEqual(await page.locator('#empleado_id').evaluate(element => Array.from(element.selectedOptions).map(option => option.value)), ['10', '30']);
        await page.locator('#btn-limpiar').click();
        console.log('OK: un fallo conserva el formulario y los empleados para corregir y reintentar');

        await select(20);
        await settled();
        assert.equal(await page.locator('#btn-registrar-aprobar').isEnabled(), true);
        await page.locator('#actividad').fill('Cambio sin guardar');
        assert.equal(await page.locator('#btn-registrar-aprobar').isDisabled(), true);
        await page.evaluate(() => $('#historial_empleado_id').val('10').trigger('change'));
        await page.locator('.swal2-cancel').click();
        assert.equal(await page.locator('#historial_empleado_id').inputValue(), '20');
        assert.equal(await page.locator('#actividad').inputValue(), 'Cambio sin guardar');
        await page.locator('#btn-limpiar').click();
        await page.evaluate(() => $('#empleado_id').val(['10', '30']).trigger('change'));
        await page.locator('.editar-jornada').click();
        assert.equal(await page.locator('#empleado_id').isDisabled(), true);
        await page.locator('#actividad').fill('Actividad corregida');
        await page.locator('#btn-guardar').click();
        await page.getByText('Se requiere nueva confirmación', { exact: true }).waitFor();
        await confirm();
        await page.getByText('Borrador guardado', { exact: true }).waitFor();
        await confirm();
        await settled();
        assert.equal(saves[1].empleado, 20);
        assert.equal(saves[1].jornadas[0].jornada_id, 2);
        assert.equal(await page.locator('#btn-registrar-aprobar').isDisabled(), true);
        console.log('OK: protección de cambios al cambiar de empleado y edición de jornadas confirmadas');

        rows.find(row => row.jornada_id === 2).estado_codigo = 'CONFIRMADO_EMPLEADO';
        await page.locator('#btn-filtrar').click();
        await settled();
        await page.locator('#filtro_estado').selectOption('BORRADOR');
        assert.equal(await page.locator('.editar-jornada').count(), 0);
        await page.locator('#btn-registrar-aprobar').click();
        await page.getByText('Aprobar periodo', { exact: true }).waitFor();
        assert.match(await page.locator('.swal2-html-container, #swal2-content').innerText(), /Empleado 20/);
        await confirm();
        await page.getByText('Periodo aprobado', { exact: true }).waitFor();
        await confirm();
        await settled();
        assert.equal(approvals.length, 1);
        assert.equal(approvals[0].empleado_id, '20');
        assert.ok(approvals[0].fecha_desde && approvals[0].fecha_hasta);
        assert.match(await page.locator('#resumen-confirmacion').innerText(), /No hay jornadas pendientes de aprobación/);
        console.log('OK: aprobación del periodo consultado independiente del filtro visual de estado');

        await page.locator('#filtro_estado').selectOption('');
        await select(30);
        await settled();
        assert.equal(await page.locator('.editar-jornada[data-id="3"], .anular-jornada[data-id="3"]').count(), 0);
        slowEmployee = true;
        await select(10);
        await select(20);
        await settled();
        await page.waitForTimeout(400);
        assert.equal(await page.locator('#historial_empleado_id').inputValue(), '20');
        assert.equal(await page.locator('#tabla-jornadas tbody tr').count(), 1);
        assert.equal(await page.locator('.editar-jornada').count(), 0);
        console.log('OK: estados protegidos y respuestas tardías de otro empleado descartadas');

        slowEmployee = false;
        await select(10);
        await settled();
        await page.locator('.anular-jornada[data-id="1"]').click();
        await page.locator('.swal2-textarea').fill('Registro duplicado');
        await confirm();
        await page.getByText('Borrador anulado', { exact: true }).waitFor();
        await confirm();
        await settled();
        assert.equal(cancellations[0].empleado_id, '10');
        assert.equal(cancellations[0].motivo, 'Registro duplicado');
        assert.deepEqual(errors, []);
        console.log('OK: anulación con motivo y sin errores JavaScript');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
