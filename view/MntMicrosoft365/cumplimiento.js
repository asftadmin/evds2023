/* HU-03: presentación, filtros y AJAX. Las calificaciones proceden del servidor. */
$(function () {
    'use strict';
    let informe = null, ocupado = false, secuencia = 0, grupoSecuencia = 0;
    let gruposCargados = false, cargandoPlanes = false;
    const texto = $.fn.dataTable.render.text();
    const nota = v => v === null ? 'Sin calificación' : `${v} %`;
    const columnas = campos => campos.map(data => ({data, render: texto, defaultContent: '—'}));
    /** Presenta fecha u hora colombiana por separado, sin cambiar las fechas originales. */
    function fechaDetalle(formato) {
        return function (valor, tipo) {
            if (!valor) { return tipo === 'sort' || tipo === 'type' ? -Infinity : '—'; }
            const fecha = new Date(valor);
            if (Number.isNaN(fecha.getTime())) { return tipo === 'sort' || tipo === 'type' ? -Infinity : '—'; }
            const soloHora = formato === 'hora';
            const partes = Object.fromEntries(new Intl.DateTimeFormat('en-US', {
                timeZone:'America/Bogota',
                ...(soloHora ? {hour:'2-digit',minute:'2-digit',hour12:true} : {day:'2-digit',month:'2-digit',year:'numeric'})
            }).formatToParts(fecha).map(p => [p.type,p.value]));
            if (tipo === 'sort' || tipo === 'type') {
                return soloHora ? ((Number(partes.hour)%12)+(partes.dayPeriod==='PM'?12:0))*60+Number(partes.minute) : fecha.getTime();
            }
            return soloHora ? `${partes.hour}:${partes.minute} ${partes.dayPeriod.toLowerCase()}` : `${partes.day}-${partes.month}-${partes.year}`;
        };
    }
    const opciones = {autoWidth: false, pageLength: 10,
        language: {emptyTable: 'No hay resultados', search: 'Buscar:', lengthMenu: 'Mostrar _MENU_',
            info: '_START_ a _END_ de _TOTAL_', infoEmpty: 'Sin resultados', zeroRecords: 'Sin coincidencias',
            paginate: {first:'Primero',last:'Último',next:'Siguiente',previous:'Anterior'}}};
    const tabla = $('#planner_tabla').DataTable({...opciones, columns: [
        ...columnas(['nomb_empl','asignadas','evaluables','puntuales','tardias','incumplidas','excluidas']),
        {data:'cumplimiento',render:nota}, {data:null,render:()=>texto.display(informe?.estado || '')},
        {data:null,orderable:false,searchable:false,defaultContent:'<button type="button" class="btn btn-sm btn-outline-primary planner-detalle">Ver detalle</button>'}
    ]});
    const excepciones = $('#planner_excepciones').DataTable({...opciones,columns:columnas(['nombre','object_id','incidencia'])});
    const detalle = $('#planner_detalle').DataTable({...opciones,columns:[
        ...columnas(['nombre']),{data:'vencimiento',render:fechaDetalle('fecha')},
        {data:'finalizacion',render:fechaDetalle('fecha')},{data:'finalizacion',render:fechaDetalle('hora')},...columnas(['estado']),
        {data:'nota',render:nota},...columnas(['motivo'])
    ]});
    const hoy = new Date(new Date().toLocaleString('en-US',{timeZone:'America/Bogota'}));
    for (let y = 2000; y <= 2099; y++) { $('#planner_anio').append(new Option(y,y,false,y===hoy.getFullYear())); }
    ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'].forEach((m,i) => {
        $('#planner_mes').append(new Option(m,i+1,false,i===hoy.getMonth()));
    });
    $('select[id^="planner_"]').not('#planner_empleado').select2({theme:'bootstrap4',width:'100%'});
    $('#planner_empleado').select2({theme:'bootstrap4',width:'100%',placeholder:'Todos',closeOnSelect:false});

    /** POST protegido; propaga únicamente el mensaje controlado del servidor. */
    async function api(op, datos = {}) {
        try {
            const r = await $.ajax({url:`../../controller/planner.php?op=${op}`,method:'POST',dataType:'json',
                data:{csrf_token:$('#planner_csrf').val(),...datos}});
            if (r.status !== 'success') { throw new Error(r.data?.message || 'La consulta falló.'); }
            return r.data;
        } catch (e) {
            const datos=e.responseJSON?.data;
            const fallo=new Error(datos?.message || e.message || 'No fue posible conectar con el servidor.');
            fallo.codigo=datos?.code;
            fallo.plan=datos?.plan;
            throw fallo;
        }
    }
    const entrada = () => ({anio:$('#planner_anio').val(),mes:$('#planner_mes').val(),grupo_id:$('#planner_grupo').val(),plan_id:$('#planner_plan').val()});
    const error = e => Swal.fire({icon:'error',title:'Planner',text:e.message});
    /** Un plan ambiguo solo se consulta después de guardar una decisión motivada con su asociación. */
    async function confirmarPeriodo(datos, plan) {
        const respuesta=await Swal.fire({icon:'warning',title:'Confirmar período del plan',
            text:`El plan ${plan.plan_nombre} no identifica un período único. Confirme ${datos.mes}/${datos.anio} y documente cómo lo verificó. Se guardarán la asociación y la decisión administrativa.`,
            input:'textarea',inputLabel:'Motivo y evidencia de la validación',inputAttributes:{maxlength:'2000'},
            inputValidator:valor=>!valor || valor.trim().length<15 ? 'Escriba un motivo de al menos 15 caracteres.' : undefined,
            showCancelButton:true,confirmButtonText:'Validar y guardar asociación',cancelButtonText:'Cancelar'});
        if (!respuesta.isConfirmed) { return false; }
        await api('asociar',{...datos,grupo_id:plan.grupo_id,plan_id:plan.plan_id,confirmar_periodo:'1',motivo_periodo:respuesta.value.trim()});
        return true;
    }
    function bloquear(valor) {
        ocupado=valor;
        actualizarAcciones();
    }
    /** Mantiene la consulta deshabilitada hasta cargar grupos, incluso tras errores y peticiones de planes. */
    function actualizarAcciones() {
        $('#planner_grupos_cargar,#planner_anio,#planner_mes,#planner_grupo,#planner_plan').prop('disabled',ocupado);
        $('#planner_validar').prop('disabled',ocupado || cargandoPlanes);
        $('#planner_consultar').prop('disabled',ocupado || cargandoPlanes || !gruposCargados);
    }
    /** Aplica el filtro sin alterar el informe completo recibido del servidor. */
    function pintar() {
        const ids=($('#planner_empleado').val() || []).filter(id=>id && id!=='todos');
        const incluido=id=>!ids.length || ids.includes(String(id));
        const filas=(informe?.empleados || []).filter(e=>incluido(e.id_empl));
        tabla.clear().rows.add(filas).draw();
        excepciones.clear().rows.add((informe?.excepciones || []).filter(e=>incluido(e.id_empl) || !e.id_empl)).draw();
        if (!informe) {
            $('#planner_indicadores h3').text('—'); return;
        }
        const suma = campo => filas.reduce((n,e)=>n+Number(e[campo]),0);
        ['evaluables','puntuales','tardias','incumplidas'].forEach(c=>$('#planner_'+c).text(suma(c)));
        $('#planner_excluidas').text((informe.excepciones || []).filter(e=>incluido(e.id_empl) || !e.id_empl).length);
        $('#planner_cumplimiento').text(nota(suma('evaluables') ? Math.round((suma('puntuales')+.5*suma('tardias'))/suma('evaluables')*10000)/100 : null));
        tabla.columns.adjust();
    }
    /** Invalida filas al cambiar período para impedir detalles de un mes anterior. */
    $('#planner_anio,#planner_mes').on('change',function () { secuencia++; informe=null; pintar(); $('#planner_aviso').text('Pulse Consultar para cargar este período.'); });
    $('#planner_empleado').on('change',pintar);
    // Todos es excluyente; vaciar la selección también muestra todos los empleados.
    $('#planner_empleado').on('select2:select',function (e) {
        $(this).val(e.params.data.id==='todos' ? ['todos'] : ($(this).val() || []).filter(id=>id && id!=='todos')).trigger('change');
    });
    $('#planner_consultar').on('click',async function () {
        if (ocupado || cargandoPlanes || !gruposCargados) { return; } bloquear(true); informe=null; pintar();
        const turno=++secuencia;
        const datos=entrada();
        try {
            let r;
            try { r=await api('consultar',datos); }
            catch (e) {
                if (e.codigo!=='confirmacion_periodo_requerida') { throw e; }
                if (!await confirmarPeriodo(datos,e.plan)) { $('#planner_aviso').text('Consulta cancelada: el período del plan requiere validación administrativa.'); return; }
                r=await api('consultar',datos);
            }
            if (turno!==secuencia) { return; }
            informe=r;
            $('#planner_aviso').text(`${r.estado}. Plan: ${r.plan.plan_nombre}. Grupo: ${r.plan.grupo_nombre}. Corte: ${r.corte}. ${r.asociacion_validada === false ? 'Consulta previa: asociación mensual aún sin validar.' : ''} ${r.origen || ''} ${r.observacion_temporal || ''}`);
            pintar();
        } catch (e) { $('#planner_aviso').text('Consulta sin resultados. Seleccione un plan si el período aún no está asociado.'); error(e); }
        finally { bloquear(false); }
    });
    $('#planner_grupos_cargar').on('click',async function () {
        if (ocupado) { return; } gruposCargados=false; bloquear(true);
        try {
            const grupos=await api('grupos');
            $('#planner_grupo').empty().append(new Option('Seleccione un grupo',''));
            grupos.forEach(g=>$('#planner_grupo').append(new Option(`${g.displayName} · ${g.id}`,g.id)));
            $('#planner_grupo').val('').trigger('change');
            gruposCargados=true;
            $('#planner_aviso').text('Grupos consultados. Puede consultar el período o seleccionar y validar su plan mensual.');
            if (!grupos.length) { await Swal.fire({icon:'info',text:'No hay grupos Microsoft 365 disponibles.'}); }
        } catch (e) { error(e); } finally { bloquear(false); }
    });
    $('#planner_grupo').on('change',async function () {
        const turno=++grupoSecuencia, grupo=$(this).val();
        $('#planner_plan').empty().trigger('change');
        cargandoPlanes=!!grupo; actualizarAcciones();
        if (!grupo) { return; }
        try {
            const planes=await api('planes',{grupo_id:grupo});
            if (turno!==grupoSecuencia) { return; }
            $('#planner_plan').append(new Option('Seleccione un plan',''));
            planes.forEach(p=>$('#planner_plan').append(new Option(`${p.title} · ${p.id}`,p.id)));
            if (!planes.length) { await Swal.fire({icon:'info',text:'El grupo no tiene planes disponibles.'}); }
        } catch (e) { if (turno===grupoSecuencia) { error(e); } }
        finally { if (turno===grupoSecuencia) { cargandoPlanes=false; actualizarAcciones(); } }
    });
    /** Confirma la correspondencia administrativa, incluidos nombres sin año. */
    $('#planner_validar').on('click',async function () {
        if (ocupado) { return; }
        const datos=entrada();
        if (!datos.grupo_id || !datos.plan_id) { error(new Error('Seleccione grupo y plan.')); return; }
        bloquear(true);
        try {
            const confirmacion=await Swal.fire({icon:'warning',title:'Validar plan mensual',
                text:`Confirma que ${$('#planner_plan option:selected').text()} corresponde al mes ${datos.mes} del año ${datos.anio}. La asociación se conservará para el cierre automático.`,
                showCancelButton:true,confirmButtonText:'Validar asociación',cancelButtonText:'Cancelar'});
            if (confirmacion.isConfirmed) {
                try { const r=await api('asociar',datos); await Swal.fire({icon:'success',text:r.message}); }
                catch (e) {
                    if (e.codigo!=='confirmacion_periodo_requerida') { throw e; }
                    if (await confirmarPeriodo(datos,e.plan)) { await Swal.fire({icon:'success',text:'Asociación y decisión administrativa conservadas.'}); }
                }
            }
        } catch (e) { error(e); } finally { bloquear(false); }
    });
    $('#planner_tabla').on('click','.planner-detalle',function () {
        const e=tabla.row($(this).closest('tr')).data();
        $('#planner_detalle_titulo').text(`Detalle: ${e.nomb_empl}`);
        $('#planner_detalle_plan').text(`Plan: ${informe.plan.plan_nombre}. Fechas en America/Bogota. Pendientes: ${e.pendientes}.`);
        detalle.clear().rows.add(e.tareas).draw(); $('#planner_modal').modal('show');
    });
    $('#planner_modal').on('shown.bs.modal',()=>detalle.columns.adjust());
    actualizarAcciones();
    // Inicialización local: no se consulta Graph hasta una acción administrativa.
    api('inicial').then(r=>{
        r.empleados.forEach(e=>$('#planner_empleado').append(new Option(e.nomb_empl,e.id_empl)));
        if (!r.persistencia) { $('#planner_aviso').text('Consulta previa disponible. Persistencia y cierre pendientes de migración autorizada.'); }
    }).catch(error);
});
