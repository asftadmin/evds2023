/* Verifica los renderers reales sin navegador ni conexión a la base. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
let respuesta;
let columnas;
const elemento = {
    on() { return this; }, ready() { return this; }, removeClass() { return this; },
    text() { return this; }, html() { return this; },
    offset() { return {top: 100}; }, animate() { return this; },
    DataTable(opciones) { columnas = opciones.columns; return this; }
};
const $ = () => elemento;
$.fn = { DataTable: { isDataTable: () => false } };
$.getJSON = () => ({ done(callback) { callback(respuesta); return { fail() {} }; } });
const contexto = vm.createContext({ $, document: {} });
vm.runInContext(fs.readFileSync(path.join(__dirname, '../view/MntJornadas/reporte_contable.js'), 'utf8'), contexto);
function abrir(estado, snapshot) {
    respuesta = {data: {lote: {jlot_id: 7, jlot_nombre: 'Historico', jlot_estado: estado,
        jlot_fecha_corte: '2026-09-30'}, empleados: [{jle_id: 8, jle_snapshot: snapshot}]}};
    vm.runInContext('rcAbrirLote(7)', contexto);
    return vm.runInContext('rcAccionesLote(rcLoteActual)', contexto);
}
let acciones = abrir('ANULADO', '{}');
assert(acciones.includes('tipo=pdf_lote&lote_id=7') && acciones.includes('tipo=excel&lote_id=7'));
assert(!acciones.includes('btn-anular-lote') && !acciones.includes('btn-corregir-lote') && !acciones.includes('btn-cerrar-lote'));
assert(columnas.at(-1).render({jle_id: 8, jle_snapshot: '{}'}).includes('tipo=pdf_empleado&fila_id=8'));
assert.equal(columnas.at(-1).render({jle_id: 9, jle_snapshot: null}), '');
assert.equal(abrir('ANULADO', null), '');
acciones = abrir('CERRADO', '{}');
assert(acciones.includes('tipo=pdf_lote') && acciones.includes('btn-corregir-lote') && acciones.includes('btn-anular-lote'));
acciones = abrir('BORRADOR', null);
assert(acciones.includes('btn-cerrar-lote') && !acciones.includes('tipo=pdf_lote'));
assert(columnas.at(-1).render({jle_id: 8}).includes('btn-editar-periodo'));
console.log('OK: descarga histórica de anulados con snapshot, sin acciones de escritura; cerrados y borradores conservados');
