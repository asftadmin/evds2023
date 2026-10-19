/* Verifica los cortes del periodo usando el mismo Moment de las vistas. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.resolve(__dirname, '..');
const contexto = { window: {} };
vm.createContext(contexto);
vm.runInContext(fs.readFileSync(path.join(root, 'public/plugins/daterangepicker/moment.min.js'), 'utf8'), contexto);
vm.runInContext(fs.readFileSync(path.join(root, 'view/MntJornadas/periodo_jornadas.js'), 'utf8'), contexto);
const calcular = contexto.window.JornadasPeriodo.obtener;
for (const [fecha, desde, hasta] of [
    ['2026-10-15', '2026-09-16', '2026-10-15'],
    ['2026-10-16', '2026-10-16', '2026-11-15'],
    ['2026-10-19', '2026-10-16', '2026-11-15'],
    ['2026-01-01', '2025-12-16', '2026-01-15'],
    ['2026-12-31', '2026-12-16', '2027-01-15'],
    ['2024-02-29', '2024-02-16', '2024-03-15'],
    ['2026-03-01', '2026-02-16', '2026-03-15']
]) {
    const periodo = calcular(fecha);
    assert.equal(periodo.inicio.format('YYYY-MM-DD'), desde);
    assert.equal(periodo.fin.format('YYYY-MM-DD'), hasta);
    const anterior = contexto.window.JornadasPeriodo.predeterminado(fecha);
    assert.equal(anterior.inicio.format('YYYY-MM-DD'), periodo.inicio.clone().subtract(1, 'month').format('YYYY-MM-DD'));
    assert.equal(anterior.fin.format('YYYY-MM-DD'), periodo.inicio.clone().subtract(1, 'day').format('YYYY-MM-DD'));
    console.log('OK: ' + fecha + ' corresponde al periodo ' + desde + ' al ' + hasta);
}
