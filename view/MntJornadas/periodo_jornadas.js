/* Ciclos mensuales de jornadas: del día 16 al día 15 del mes siguiente. */
window.JornadasPeriodo = (function () {
    'use strict';

    function obtener(fecha) {
        const inicio = (fecha ? moment(fecha) : moment()).startOf('day');
        if (inicio.date() < 16) inicio.subtract(1, 'month');
        inicio.date(16);
        return { inicio: inicio, fin: inicio.clone().add(1, 'month').date(15) };
    }

    function predeterminado(fecha) {
        return obtener(obtener(fecha).inicio.subtract(1, 'month'));
    }

    function seleccionar(selector, fecha) {
        const periodo = fecha ? obtener(fecha) : predeterminado();
        const rango = $(selector).data('daterangepicker');
        rango.setStartDate(periodo.inicio);
        rango.setEndDate(periodo.fin);
        $(selector).val(rango.startDate.format(rango.locale.format) +
            rango.locale.separator + rango.endDate.format(rango.locale.format));
        return periodo;
    }

    function inicializar(selector, opciones) {
        const actual = obtener();
        const anterior = obtener(actual.inicio.clone().subtract(1, 'month'));
        const anteriorDos = obtener(actual.inicio.clone().subtract(2, 'months'));
        const rangos = {
            'Periodo actual (16 al 15)': [actual.inicio, actual.fin],
            'Periodo anterior': [anterior.inicio, anterior.fin],
            'Hace dos periodos': [anteriorDos.inicio, anteriorDos.fin]
        };
        $(selector).daterangepicker(Object.assign({}, opciones, {
            startDate: anterior.inicio,
            endDate: anterior.fin,
            ranges: rangos
        }), function (inicio) {
            // Normaliza antes de que las vistas consulten el rango con el evento apply.
            seleccionar(selector, inicio);
        });
    }

    return { obtener: obtener, predeterminado: predeterminado, seleccionar: seleccionar, inicializar: inicializar };
}());
