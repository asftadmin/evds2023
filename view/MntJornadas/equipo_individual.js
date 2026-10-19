/* Registro compartido del equipo y edición individual desde el historial. */
$(function () {
    'use strict';

    const endpoint = '../../controller/jornada.php';
    let autorizado = false;
    let ocupado = false;
    let consultando = false;
    let empleadoId = '';
    let empleadosSeleccionados = [];
    let secuenciaConsulta = 0;
    let secuenciaCalculo = 0;
    let periodoConsultado = null;
    let resumen = {};
    let jornadas = [];
    let jornadaEditada = null;
    let formularioInicial = '';
    let ubicaciones = [];

    const escapar = valor => $('<div>').text(valor == null ? '' : String(valor)).html();
    const mensaje = (xhr, defecto) => (xhr.responseJSON && xhr.responseJSON.message) || defecto;
    // Lee también controles deshabilitados durante una operación y omite los valores calculados.
    const fotoFormulario = () => JSON.stringify([
        ...['jornada_id', 'fecha', 'hora_entrada', 'hora_salida', 'ubicacion', 'actividad', 'observaciones']
            .map(campo => $('#' + campo).val()),
        $('#salida_dia_siguiente').is(':checked')
    ]);
    const hayCambios = () => fotoFormulario() !== formularioInicial;
    const editable = fila => ['BORRADOR', 'CONFIRMADO_EMPLEADO'].includes(fila.estado_codigo);
    const dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];

    function pedir(op, datos, escritura) {
        return $.ajax({
            url: endpoint + '?op=' + op,
            type: escritura ? 'POST' : 'GET',
            dataType: 'json',
            data: escritura ? Object.assign({ csrf_token: $('#csrf_token').val() }, datos) : datos
        });
    }

    function error(xhr, defecto) {
        Swal.fire({ icon: 'error', title: 'No fue posible completar la operación', text: mensaje(xhr, defecto) });
    }

    const tabla = $('#tabla-jornadas').DataTable({
        data: [], responsive: true, order: [[1, 'desc'], [2, 'asc']], pageLength: 10,
        language: {
            search: 'Buscar:', lengthMenu: 'Mostrar _MENU_ registros',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ jornadas', infoEmpty: 'Sin jornadas',
            infoFiltered: '(de _MAX_ jornadas)', zeroRecords: 'No hay jornadas para los filtros seleccionados.',
            emptyTable: 'No hay jornadas en el periodo.',
            paginate: { first: 'Primera', last: 'Última', next: 'Siguiente', previous: 'Anterior' }
        },
        columns: [
            { data: 'dia', render: $.fn.dataTable.render.text() },
            { data: 'fecha', render: $.fn.dataTable.render.text() },
            { data: 'hora_entrada', render: $.fn.dataTable.render.text() },
            { data: 'hora_salida', render: function (dato, tipo, fila) {
                return escapar(dato) + (fila.fecha_salida !== fila.fecha ? ' <small>(día siguiente)</small>' : '');
            } },
            { data: 'horas_ordinarias', render: $.fn.dataTable.render.text() },
            { data: 'ubicacion', render: $.fn.dataTable.render.text() },
            { data: 'actividad', render: $.fn.dataTable.render.text() },
            { data: 'observaciones', defaultContent: '', render: $.fn.dataTable.render.text() },
            { data: 'estado_nombre', render: function (dato, tipo, fila) {
                const colores = { BORRADOR: 'secondary', CONFIRMADO_EMPLEADO: 'info', APROBADO: 'success',
                    LIQUIDADO: 'success', PENDIENTE_LIQUIDACION: 'info', RECHAZADO: 'danger', ANULADO: 'dark', PENDIENTE_APROBACION: 'warning' };
                return '<span class="badge badge-' + (colores[fila.estado_codigo] || 'info') + '">' + escapar(dato) + '</span>';
            } },
            { data: null, orderable: false, searchable: false, render: function (dato, tipo, fila) {
                const id = Number(fila.jornada_id);
                let acciones = editable(fila) ? '<button type="button" class="btn btn-sm btn-info editar-jornada" data-id="' + id + '" title="Editar jornada" aria-label="Editar jornada"><i class="fas fa-edit"></i></button>' : '';
                if (fila.estado_codigo === 'BORRADOR') {
                    acciones += ' <button type="button" class="btn btn-sm btn-danger anular-jornada" data-id="' + id + '" title="Anular borrador" aria-label="Anular borrador"><i class="fas fa-trash"></i></button>';
                }
                return acciones || '<span class="text-muted">Sin acciones</span>';
            } }
        ]
    });

    function actualizarControles() {
        const bloqueado = ocupado || !autorizado || !empleadoId;
        $('#empleado_id').prop('disabled', ocupado || !autorizado || !!jornadaEditada);
        $('#historial_empleado_id').prop('disabled', ocupado || !autorizado);
        $('#form-jornada :input').not('[type="hidden"]').prop('disabled',
            ocupado || !autorizado || (!jornadaEditada && !empleadosSeleccionados.length));
        $('#filtro_fechas, #filtro_estado, #btn-filtrar, #btn-limpiar-filtro').prop('disabled', bloqueado);
        $('#btn-registrar-aprobar').prop('disabled', bloqueado || consultando || !periodoConsultado ||
            !resumen.puede_registrar_aprobar || hayCambios() || !!$('#jornada_id').val());
        $('#btn-pdf-borrador').prop('disabled', bloqueado || consultando || !periodoConsultado || hayCambios());
        $('#btn-pdf-lote').prop('disabled', ocupado || !autorizado || !empleadosSeleccionados.length || hayCambios());
        $('.editar-jornada, .anular-jornada').prop('disabled', bloqueado || consultando);
        $('#contexto-registro').text(jornadaEditada
            ? 'Editando únicamente la jornada de ' + $('#historial_empleado_id option:selected').text() + '. Limpiar permite volver al registro para varios empleados.'
            : 'La jornada se registrará para ' + empleadosSeleccionados.length + ' empleado(s) seleccionados.');
        $('#btn-guardar').html('<i class="fas fa-save mr-1"></i>' +
            (jornadaEditada ? 'Guardar cambios de esta jornada' : 'Guardar borradores (' + empleadosSeleccionados.length + ')'));
    }

    function actualizarDia() {
        const fecha = $('#fecha').val();
        $('#dia_semana').val(fecha ? dias[moment(fecha, 'YYYY-MM-DD').day()] : '');
    }

    function limpiarFormulario() {
        ++secuenciaCalculo;
        jornadaEditada = null;
        $('#form-jornada')[0].reset();
        $('#jornada_id').val('');
        $('#fecha').val(moment().format('YYYY-MM-DD'));
        $('#horas_ordinarias').val('00:00');
        $('#ayuda-fecha').text('Puede registrar varios turnos en la misma fecha sin superponer horarios.');
        $('#ubicacion option[data-historica]').remove();
        actualizarDia();
        formularioInicial = fotoFormulario();
        actualizarControles();
    }

    function mostrarHistorial() {
        const estado = $('#filtro_estado').val();
        tabla.clear().rows.add(jornadas.filter(fila => !estado || fila.estado_codigo === estado)).draw();
        actualizarControles();
    }

    function consultarHistorial() {
        if (!autorizado || !empleadoId || ocupado) return;
        const numero = ++secuenciaConsulta;
        const empleado = empleadoId;
        const rango = $('#filtro_fechas').data('daterangepicker');
        const periodo = { fecha_desde: rango.startDate.format('YYYY-MM-DD'), fecha_hasta: rango.endDate.format('YYYY-MM-DD') };
        consultando = true;
        periodoConsultado = null;
        resumen = {};
        jornadas = [];
        mostrarHistorial();
        $('#resumen-confirmacion').text('Consultando jornadas...');
        pedir('listarJornadasEquipo', Object.assign({ empleado_id: empleado }, periodo)).done(function (respuesta) {
            if (numero !== secuenciaConsulta || empleado !== empleadoId) return;
            jornadas = respuesta.data || [];
            resumen = respuesta.resumen || {};
            periodoConsultado = periodo;
            $('#resumen-confirmacion').text('Periodo ' + periodo.fecha_desde + ' al ' + periodo.fecha_hasta + ': ' +
                (Number(resumen.total_jornadas_rango || 0) === 0
                    ? 'No hay jornadas pendientes de aprobación.'
                    : Number(resumen.total_confirmadas || 0) + ' de ' + Number(resumen.total_jornadas_rango) +
                        ' jornadas pendientes confirmadas y firmadas por el empleado.'));
            mostrarHistorial();
        }).fail(function (xhr) {
            if (numero !== secuenciaConsulta || empleado !== empleadoId) return;
            $('#resumen-confirmacion').text('No fue posible consultar el periodo. Vuelva a consultar.');
            error(xhr, 'No se pudieron consultar las jornadas.');
        }).always(function () {
            if (numero !== secuenciaConsulta) return;
            consultando = false;
            actualizarControles();
        });
    }

    function calcularHoras() {
        actualizarDia();
        const numero = ++secuenciaCalculo;
        $('#horas_ordinarias').val('00:00');
        if (!$('#fecha').val() || !$('#hora_entrada').val() || !$('#hora_salida').val()) return;
        pedir('calcularHorasEquipo', {
            fecha: $('#fecha').val(), hora_entrada: $('#hora_entrada').val(), hora_salida: $('#hora_salida').val(),
            cruza_medianoche: $('#salida_dia_siguiente').is(':checked') ? 1 : 0
        }).done(function (respuesta) {
            if (numero !== secuenciaCalculo) return;
            $('#horas_ordinarias').val(respuesta.data.horas_ordinarias);
            $('#ayuda-fecha').text('Las horas se validan nuevamente al guardar.');
        }).fail(function (xhr) {
            if (numero === secuenciaCalculo) $('#ayuda-fecha').text(mensaje(xhr, 'Revise la entrada, salida y salida al día siguiente.'));
        });
    }

    // El registro para varios empleados se envía una sola vez: el servidor controla el rollback.
    async function guardarJornada(evento) {
        evento.preventDefault();
        if (ocupado || !autorizado || (!jornadaEditada && !empleadosSeleccionados.length) || !this.reportValidity()) return;
        ocupado = true;
        actualizarControles();
        try {
            if ($('#hora_salida').val() <= $('#hora_entrada').val() && !$('#salida_dia_siguiente').is(':checked')) {
                const resultado = await Swal.fire({ icon: 'question', title: 'La jornada cruza medianoche',
                    text: '¿La salida corresponde al día siguiente?', showCancelButton: true,
                    confirmButtonText: 'Sí, día siguiente', cancelButtonText: 'Revisar horas' });
                if (!resultado.isConfirmed) return;
                $('#salida_dia_siguiente').prop('checked', true);
                calcularHoras();
            }
            const id = Number($('#jornada_id').val()) || null;
            const anterior = jornadaEditada;
            if (anterior && anterior.estado_codigo === 'CONFIRMADO_EMPLEADO') {
                const resultado = await Swal.fire({ icon: 'warning', title: 'Se requiere nueva confirmación',
                    text: 'Al guardar, la jornada volverá a borrador y se invalidará la confirmación anterior del empleado.',
                    showCancelButton: true, confirmButtonText: 'Guardar cambios', cancelButtonText: 'Cancelar' });
                if (!resultado.isConfirmed) return;
            }
            const fila = { jornada_id: id, fecha: $('#fecha').val(), hora_entrada: $('#hora_entrada').val(),
                hora_salida: $('#hora_salida').val(), cruza_medianoche: $('#salida_dia_siguiente').is(':checked'),
                ubicacion: $('#ubicacion').val(), actividad: $('#actividad').val(), observaciones: $('#observaciones').val() };
            let respuesta;
            if (id) {
                // El empleado proviene de la jornada elegida, nunca de la selección múltiple.
                respuesta = await pedir('guardarBorradoresEquipoMasivo', {
                    empleado_id: jornadaEditada.empleado_id, jornadas: JSON.stringify([fila])
                }, true);
            } else {
                const resultado = await Swal.fire({ icon: 'question', title: 'Registrar jornada para varios empleados',
                    text: 'Se guardará la misma jornada para ' + empleadosSeleccionados.length +
                        ' empleado(s). Si alguno presenta un error, no se guardará ninguna jornada.',
                    showCancelButton: true, confirmButtonText: 'Guardar para todos', cancelButtonText: 'Cancelar' });
                if (!resultado.isConfirmed) return;
                Swal.fire({ title: 'Guardando jornadas...', text: 'Espere mientras se procesa el registro completo.',
                    allowOutsideClick: false, allowEscapeKey: false, showConfirmButton: false,
                    didOpen: () => Swal.showLoading() });
                respuesta = await pedir('guardarJornadaEquipoVarios', {
                    empleados_ids: JSON.stringify(empleadosSeleccionados), jornada: JSON.stringify(fila)
                }, true);
            }
            // Incluye el registro guardado en la consulta, incluso cuando estaba fuera del periodo visible.
            JornadasPeriodo.seleccionar('#filtro_fechas', fila.fecha);
            $('#filtro_estado').val('');
            limpiarFormulario();
            await Swal.fire({ icon: 'success', title: 'Borrador guardado',
                text: respuesta.message || 'Queda pendiente de confirmación por el empleado.' });
        } catch (xhr) {
            error(xhr, 'No se pudo guardar la jornada.');
        } finally {
            ocupado = false;
            consultarHistorial();
            actualizarControles();
        }
    }

    $('#tabla-jornadas').on('click', '.editar-jornada', async function () {
        if (ocupado || consultando) return;
        const fila = jornadas.find(item => Number(item.jornada_id) === Number($(this).data('id')));
        if (!fila || !editable(fila)) return;
        if (hayCambios()) {
            ocupado = true;
            actualizarControles();
            const resultado = await Swal.fire({ icon: 'question', title: 'Hay cambios sin guardar',
                text: '¿Desea descartarlos para editar esta jornada?', showCancelButton: true,
                confirmButtonText: 'Descartar cambios', cancelButtonText: 'Continuar editando' });
            ocupado = false;
            actualizarControles();
            if (!resultado.isConfirmed) return;
        }
        limpiarFormulario();
        jornadaEditada = Object.assign({}, fila);
        $('#jornada_id').val(fila.jornada_id);
        ['fecha', 'hora_entrada', 'hora_salida', 'actividad', 'observaciones', 'horas_ordinarias'].forEach(campo => $('#' + campo).val(fila[campo] || ''));
        if (!ubicaciones.includes(fila.ubicacion)) {
            $('#ubicacion').append($('<option>').val(fila.ubicacion).text(fila.ubicacion).attr('data-historica', '1'));
        }
        $('#ubicacion').val(fila.ubicacion);
        $('#salida_dia_siguiente').prop('checked', fila.fecha_salida !== fila.fecha);
        actualizarDia();
        $('#ayuda-fecha').text(fila.estado_codigo === 'CONFIRMADO_EMPLEADO'
            ? 'Si guarda cambios, el empleado deberá confirmar nuevamente la jornada.' : 'Editando borrador.');
        actualizarControles();
        document.getElementById('form-jornada').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    $('#tabla-jornadas').on('click', '.anular-jornada', async function () {
        if (ocupado || consultando) return;
        const id = Number($(this).data('id'));
        if (!jornadas.some(fila => Number(fila.jornada_id) === id && fila.estado_codigo === 'BORRADOR')) return;
        ocupado = true;
        actualizarControles();
        try {
            const resultado = await Swal.fire({ icon: 'warning', title: 'Anular borrador', input: 'textarea',
                inputLabel: 'Motivo de anulación', inputAttributes: { maxlength: '2000' }, showCancelButton: true,
                confirmButtonText: 'Anular borrador', cancelButtonText: 'Cancelar',
                inputValidator: valor => !valor || !valor.trim() ? 'Indique el motivo.' : undefined });
            if (!resultado.isConfirmed) return;
            await pedir('anularBorradorEquipo', { empleado_id: empleadoId, jornada_id: id, motivo: resultado.value }, true);
            if (Number($('#jornada_id').val()) === id) limpiarFormulario();
            await Swal.fire({ icon: 'success', title: 'Borrador anulado' });
        } catch (xhr) { error(xhr, 'No se pudo anular el borrador.'); }
        finally { ocupado = false; consultarHistorial(); actualizarControles(); }
    });

    $('#btn-registrar-aprobar').on('click', async function () {
        if (ocupado || consultando || !autorizado || !periodoConsultado || !resumen.puede_registrar_aprobar || hayCambios() || $('#jornada_id').val()) return;
        const periodo = Object.assign({}, periodoConsultado);
        ocupado = true;
        actualizarControles();
        try {
            const resultado = await Swal.fire({ icon: 'question', title: 'Aprobar periodo',
                text: 'Se aprobarán ' + Number(resumen.total_confirmadas) + ' jornadas de ' + $('#historial_empleado_id option:selected').text() +
                    ', del ' + periodo.fecha_desde + ' al ' + periodo.fecha_hasta +
                    '. Quedarán pendientes de liquidación con la firma ya registrada por el empleado.',
                showCancelButton: true, confirmButtonText: 'Registrar y aprobar', cancelButtonText: 'Cancelar' });
            if (!resultado.isConfirmed) return;
            const respuesta = await pedir('registrarAprobarEquipoIndividual', Object.assign({ empleado_id: empleadoId }, periodo), true);
            await Swal.fire({ icon: 'success', title: 'Periodo aprobado', text: respuesta.message });
        } catch (xhr) { error(xhr, 'No se pudieron aprobar las jornadas.'); }
        finally { ocupado = false; consultarHistorial(); actualizarControles(); }
    });

    $('#btn-pdf-borrador').on('click', function () {
        if (ocupado || consultando || !periodoConsultado || hayCambios()) return;
        window.open('../../controller/jornada_exportar.php?' + $.param(Object.assign({
            tipo: 'pdf_borrador_equipo', empleado_id: empleadoId
        }, periodoConsultado)), '_blank', 'noopener');
    });

    $('#empleado_id').select2({ theme: 'bootstrap4', placeholder: 'Seleccione uno o varios empleados', allowClear: true, width: '100%' });
    $('#btn-pdf-lote').on('click', function () {
        if (ocupado || !autorizado || !empleadosSeleccionados.length || hayCambios()) return;
        const rango = $('#filtro_fechas').data('daterangepicker');
        const todos = ($('#empleado_id').val() || []).includes('__todos__');
        const parametros = {
            tipo: 'pdf_borrador_equipo_lote', fecha_desde: rango.startDate.format('YYYY-MM-DD'),
            fecha_hasta: rango.endDate.format('YYYY-MM-DD')
        };
        if (todos) parametros.todos = '1';
        else parametros.empleados_ids = JSON.stringify(empleadosSeleccionados);
        window.open('../../controller/jornada_exportar.php?' + $.param(parametros), '_blank', 'noopener');
    });
    $('#empleado_id').on('change', async function () {
        if (ocupado || jornadaEditada) {
            $(this).val(empleadosSeleccionados).trigger('change.select2');
            return;
        }
        const seleccion = ($(this).val() || []).map(String);
        if (seleccion.includes('__todos__')) {
            $(this).val(['__todos__']).trigger('change.select2');
            empleadosSeleccionados = $(this).find('option').map(function () {
                return this.value !== '__todos__' ? this.value : null;
            }).get();
        } else {
            empleadosSeleccionados = seleccion;
        }
        $('#contenido-empleado').show();
        actualizarControles();
        if (!empleadoId && empleadosSeleccionados.length) {
            $('#historial_empleado_id').val(empleadosSeleccionados[0]).trigger('change');
        }
    });

    $('#historial_empleado_id').select2({ theme: 'bootstrap4', placeholder: 'Seleccione un empleado', allowClear: true, width: '100%' });
    $('#historial_empleado_id').on('change', async function () {
        const siguiente = String($(this).val() || '');
        if (siguiente === empleadoId) return;
        if (ocupado) {
            $(this).val(empleadoId).trigger('change.select2');
            return;
        }
        // Retiene el contexto anterior mientras se decide qué hacer con el formulario.
        if (hayCambios()) {
            ocupado = true;
            $(this).val(empleadoId).trigger('change.select2');
            actualizarControles();
            const resultado = await Swal.fire({ icon: 'question', title: 'Hay cambios sin guardar',
                text: '¿Desea descartarlos para cambiar de empleado?', showCancelButton: true,
                confirmButtonText: 'Descartar cambios', cancelButtonText: 'Continuar editando' });
            ocupado = false;
            actualizarControles();
            if (!resultado.isConfirmed) return;
            $(this).val(siguiente).trigger('change.select2');
        }
        empleadoId = siguiente;
        ++secuenciaConsulta;
        consultando = false;
        periodoConsultado = null;
        resumen = {};
        jornadas = [];
        limpiarFormulario();
        mostrarHistorial();
        $('#contenido-empleado').show();
        $('#resumen-confirmacion').text('Seleccione un empleado del historial para consultar sus jornadas.');
        if (empleadoId) {
            tabla.columns.adjust().responsive.recalc();
            consultarHistorial();
        }
    });

    JornadasPeriodo.inicializar('#filtro_fechas', {
        locale: { format: 'YYYY-MM-DD', separator: ' - ', applyLabel: 'Aplicar', cancelLabel: 'Cancelar',
            daysOfWeek: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'], firstDay: 1,
            monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'] }
    });
    $('#filtro_fechas').on('apply.daterangepicker', consultarHistorial);
    $('#btn-filtrar').on('click', consultarHistorial);
    $('#filtro_estado').on('change', mostrarHistorial);
    $('#btn-limpiar-filtro').on('click', function () {
        if (ocupado) return;
        JornadasPeriodo.seleccionar('#filtro_fechas');
        $('#filtro_estado').val('');
        tabla.search('');
        consultarHistorial();
    });
    $('#fecha, #hora_entrada, #hora_salida, #salida_dia_siguiente').on('change', calcularHoras);
    $('#form-jornada').on('input change', actualizarControles).on('submit', guardarJornada);
    $('#btn-limpiar').on('click', limpiarFormulario);
    $(window).on('beforeunload', function (evento) {
        if (hayCambios()) { evento.preventDefault(); evento.originalEvent.returnValue = ''; }
    });
    limpiarFormulario();

    // La selección solo se habilita cuando contexto, empleados y ubicaciones están disponibles.
    $.when(pedir('contextoEquipo'), pedir('listarSubordinadosJefe'), pedir('listarUbicacionesJornada'))
        .done(function (contexto, empleados, lugares) {
            const jefe = contexto[0].data || {};
            ubicaciones = lugares[0].data || [];
            $('#ubicacion').empty().append($('<option>').val('').text('Seleccione una ubicación'));
            ubicaciones.forEach(lugar => $('#ubicacion').append($('<option>').val(lugar).text(lugar.toUpperCase())));
            if ((empleados[0].data || []).length) {
                $('#empleado_id').append($('<option>').val('__todos__').text('Todos los empleados'));
            }
            (empleados[0].data || []).forEach(empleado => {
                const opcion = $('<option>').val(empleado.empleado_id).text(empleado.empleado_nombre + ' — ' + empleado.empleado_documento);
                $('#empleado_id').append(opcion);
                $('#historial_empleado_id').append(opcion.clone());
            });
            $('#texto-contexto').text('Jefe inmediato: ' + jefe.empleado + ' — ' + jefe.documento);
            if (!(empleados[0].data || []).length) $('#ayuda-empleado').text('No tiene subordinados activos disponibles.');
            autorizado = true;
            $('#contenido-empleado').show();
            limpiarFormulario();
        }).fail(function (xhr) {
            $('#alerta-contexto').removeClass('alert-info').addClass('alert-danger');
            $('#texto-contexto').text(mensaje(xhr, 'No fue posible preparar el registro del equipo. Recargue la página.'));
            actualizarControles();
        });
});
