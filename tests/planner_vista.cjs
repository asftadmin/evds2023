/* Chrome, API sintética: no escribe asociaciones ni cierres operativos. */
const {chromium}=require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const fs=require('node:fs');
const path=require('node:path');
const assert=require('node:assert/strict');
const root=path.resolve(__dirname,'..');
(async()=>{
    const browser=await chromium.launch({channel:'chrome',headless:true});
    try {
        const page=await browser.newPage({viewport:{width:1400,height:950},timezoneId:'Asia/Tokyo'});
        const errors=[]; page.on('pageerror',e=>errors.push(e.message));
        let consultas=0,fallo=false,asociaciones=0,periodoIncorrecto=false,ambiguo=false,decision=false;
        let falloGrupos=false;
        const decisiones=[];
        const scripts=['jquery/jquery.min.js','bootstrap/js/bootstrap.bundle.min.js','datatables/jquery.dataTables.min.js',
            'datatables-bs4/js/dataTables.bootstrap4.min.js','select2/js/select2.full.min.js','sweetalert2/sweetalert2.min.js']
            .map(s=>`<script src="/public/plugins/${s}"></script>`).join('');
        const styles=['css/adminlte.min.css','datatables-bs4/css/dataTables.bootstrap4.min.css','select2/css/select2.min.css',
            'select2-bootstrap4-theme/select2-bootstrap4.min.css','sweetalert2/sweetalert2.min.css']
            .map(s=>`<link rel="stylesheet" href="/public/plugins/${s}">`).join('');
        let html=fs.readFileSync(path.join(root,'view/MntMicrosoft365/cumplimiento.php'),'utf8');
        html=html.replace(/<\?php\s+require_once\s+__DIR__\s*\.\s*'\/\.\.\/MainHead\/head.php';\s*\?>/,styles)
            .replace(/<\?php\s+require_once\s+__DIR__\s*\.\s*'\/\.\.\/MainJS\/JS.php';\s*\?>/,scripts);
        const cards=['evaluables','puntuales','tardias','incumplidas','excluidas','cumplimiento'].map(c=>`<div class="col-2"><h3 id="planner_${c}">—</h3></div>`).join('');
        html=html.replace(/<\?php foreach[\s\S]*?<\?php endforeach; \?>/,cards).replace(/<\?(?:php|=)[\s\S]*?\?>/g,'').replace('<head>','<head><meta charset="utf-8">');
        const empleado={id_empl:1,nomb_empl:'<img src=x onerror=alert(1)>',asignadas:2,evaluables:1,puntuales:1,
            tardias:0,incumplidas:0,pendientes:0,excluidas:1,cumplimiento:100,
            tareas:[{nombre:'<script>alert(1)</script>',vencimiento:'2026-06-20T13:00:00-05:00',finalizacion:null,object_id:'object-id',estado:'Requiere revisión',nota:null,motivo:'Sin fecha verificable'},
                {nombre:'Entrega mismo día',vencimiento:'2026-10-30T05:00:00Z',finalizacion:'2026-10-31T04:42:00Z',object_id:'object-id',estado:'Puntual',nota:100,motivo:'Mismo día Colombia'},
                {nombre:'Sin fecha',vencimiento:null,finalizacion:'incorrecta',object_id:'object-id',estado:'Requiere revisión',nota:null,motivo:'Sin fecha verificable'}]};
        const informe={estado:'Abierto',plan:{plan_nombre:'JUNIO',grupo_nombre:'JUNIO'},corte:'2026-07-01T00:00:00-05:00',
            empleados:[empleado,{...empleado,id_empl:2,nomb_empl:'Sin tareas',asignadas:0,evaluables:0,puntuales:0,excluidas:0,cumplimiento:null,tareas:[]},
                {...empleado,id_empl:3,nomb_empl:'Otro empleado',asignadas:3,evaluables:2,puntuales:0,tardias:1,incumplidas:1,excluidas:1,cumplimiento:25,tareas:[]}],
            excepciones:[{nombre:'Revisión',object_id:'object-id',incidencia:'Sin vencimiento',id_empl:1},
                {nombre:'Otra revisión',object_id:'object-id-3',incidencia:'Fecha inconsistente',id_empl:3}]};
        await page.route('http://planner.test/**',async route=>{
            const u=new URL(route.request().url());
            if (u.pathname==='/controller/planner.php') {
                assert.equal(route.request().method(),'POST');
                const op=u.searchParams.get('op'); let data;
                const entrada=new URLSearchParams(route.request().postData());
                if (['consultar','asociar'].includes(op) && periodoIncorrecto) {
                    return route.fulfill({status:409,contentType:'application/json',body:JSON.stringify({status:'error',
                        data:{message:'El plan OCTUBRE 2026 no corresponde al período 11/2026.'}})});
                }
                if (['consultar','asociar'].includes(op) && ambiguo && !decision) {
                    if (op==='asociar' && entrada.get('confirmar_periodo')==='1') {
                        decisiones.push(Object.fromEntries(entrada)); decision=true;
                    } else {
                        return route.fulfill({status:409,contentType:'application/json',body:JSON.stringify({status:'error',data:{
                            message:'El plan requiere validación administrativa.',code:'confirmacion_periodo_requerida',
                            plan:{grupo_id:'grupo',grupo_nombre:'JUNIO',plan_id:'plan',plan_nombre:'JUNIO'}}})});
                    }
                }
                if(op==='inicial') data={empleados:informe.empleados,persistencia:true};
                if(op==='grupos') {
                    await new Promise(resolve=>setTimeout(resolve,150));
                    if(falloGrupos) return route.fulfill({status:502,contentType:'application/json',body:JSON.stringify({status:'error',data:{message:'No fue posible consultar grupos.'}})});
                    data=[{id:'grupo',displayName:'JUNIO'}];
                }
                if(op==='planes') data=[{id:'plan',title:'JUNIO'}];
                if(op==='asociar') {asociaciones++;data={message:'Asociación conservada'};}
                if(op==='consultar') { consultas++; if(fallo) return route.fulfill({status:502,contentType:'application/json',body:JSON.stringify({status:'error',data:{message:'Graph falló'}})}); data=informe; }
                return route.fulfill({contentType:'application/json',body:JSON.stringify({status:'success',data})});
            }
            if(u.pathname.endsWith('cumplimiento.php')) return route.fulfill({contentType:'text/html',body:html});
            const f=path.join(root,u.pathname); if(fs.existsSync(f)) return route.fulfill({path:f});
            return route.fulfill({status:404,body:''});
        });
        await page.goto('http://planner.test/view/MntMicrosoft365/cumplimiento.php');
        await page.waitForFunction(()=>window.jQuery && $('#planner_empleado option').length===4);
        assert.equal(consultas,0);
        assert(await page.locator('#planner_consultar').isDisabled());
        assert(await page.locator('#planner_empleado').evaluate(el=>el.multiple));
        assert.deepEqual(await page.locator('#planner_consultar').evaluate(el=>Array.from(el.parentElement.querySelectorAll('button')).map(b=>b.id)),['planner_grupos_cargar','planner_consultar']);
        await page.evaluate(()=>$('#planner_consultar').trigger('click'));
        assert.equal(consultas,0);
        falloGrupos=true; await page.click('#planner_grupos_cargar');
        await page.waitForSelector('.swal2-popup');
        assert(await page.locator('#planner_consultar').isDisabled());
        await page.click('.swal2-confirm'); falloGrupos=false;
        await page.click('#planner_grupos_cargar');
        assert(await page.locator('#planner_consultar').isDisabled());
        await page.waitForFunction(()=>!document.querySelector('#planner_consultar').disabled);
        await page.click('#planner_consultar');
        await page.waitForFunction(()=>document.querySelector('#planner_evaluables').textContent==='3');
        assert.equal(await page.locator('#planner_cumplimiento').textContent(),'50 %');
        await page.evaluate(()=>$('#planner_empleado').val(['1']).trigger('change'));
        await page.waitForFunction(()=>document.querySelector('#planner_evaluables').textContent==='1');
        assert.equal(await page.locator('#planner_cumplimiento').textContent(),'100 %');
        assert.equal(await page.locator('#planner_tabla img').count(),0);
        await page.locator('#planner_tabla tbody tr').filter({hasText:'<img src=x'}).locator('.planner-detalle').click();
        await page.waitForSelector('#planner_modal.show');
        assert.equal(await page.locator('#planner_detalle script').count(),0);
        assert((await page.locator('#planner_detalle').textContent()).includes('Sin calificación'),await page.locator('#planner_detalle').textContent());
        const filaFecha=page.locator('#planner_detalle tbody tr').filter({hasText:'Entrega mismo día'});
        assert.equal(await filaFecha.locator('td').nth(1).textContent(),'30-10-2026');
        assert.equal(await filaFecha.locator('td').nth(2).textContent(),'30-10-2026');
        assert.equal(await filaFecha.locator('td').nth(3).textContent(),'11:42 pm');
        assert.equal((await page.locator('#planner_detalle thead').textContent()).includes('Object ID'),false);
        assert.equal((await page.locator('#planner_detalle tbody').textContent()).includes('object-id'),false);
        const filaSinFecha=page.locator('#planner_detalle tbody tr').filter({hasText:'Sin fecha'}).last();
        assert.equal(await filaSinFecha.locator('td').nth(1).textContent(),'—');
        assert.equal(await filaSinFecha.locator('td').nth(2).textContent(),'—');
        assert.equal(await filaSinFecha.locator('td').nth(3).textContent(),'—');
        assert.equal((await page.locator('#planner_detalle').textContent()).includes('2026-10-31T04:42:00Z'),false);
        await page.evaluate(()=>$('#planner_detalle').DataTable().order([1,'asc']).draw());
        assert.equal(await page.locator('#planner_detalle tbody tr').last().locator('td').first().textContent(),'Entrega mismo día');
        await page.locator('#planner_modal [data-dismiss]').click();
        await page.evaluate(()=>$('#planner_empleado').val(['1','3']).trigger('change'));
        assert.equal(await page.locator('#planner_evaluables').textContent(),'3');
        assert.equal(await page.locator('#planner_cumplimiento').textContent(),'50 %');
        assert.equal(await page.locator('.planner-detalle').count(),2);
        assert.equal(await page.locator('#planner_excluidas').textContent(),'2');
        await page.evaluate(()=>$('#planner_empleado').select2('open'));
        await page.getByRole('option',{name:'Todos',exact:true}).click();
        await page.evaluate(()=>$('#planner_empleado').select2('close'));
        assert.deepEqual(await page.evaluate(()=>$('#planner_empleado').val()),['todos']);
        assert.equal(await page.locator('.planner-detalle').count(),3);
        await page.evaluate(()=>$('#planner_empleado').select2('open'));
        await page.getByRole('option',{name:'Sin tareas',exact:true}).click();
        await page.evaluate(()=>$('#planner_empleado').select2('close'));
        assert.deepEqual(await page.evaluate(()=>$('#planner_empleado').val()),['2']);
        assert.equal(await page.locator('#planner_cumplimiento').textContent(),'Sin calificación');
        await page.evaluate(()=>$('#planner_mes').val('7').trigger('change'));
        assert.equal(await page.locator('.planner-detalle').count(),0);
        fallo=true; await page.click('#planner_consultar');
        await page.waitForSelector('.swal2-popup');
        assert.equal(await page.locator('.planner-detalle').count(),0);
        await page.click('.swal2-confirm'); fallo=false;
        await page.locator('#planner_asociacion summary').click();
        await page.click('#planner_grupos_cargar');
        await page.waitForFunction(()=>$('#planner_grupo option').length===2 && !document.querySelector('#planner_consultar').disabled);
        await page.evaluate(()=>$('#planner_grupo').val('grupo').trigger('change'));
        await page.waitForFunction(()=>$('#planner_plan option').length===2);
        await page.evaluate(()=>$('#planner_plan').val('plan').trigger('change'));
        await page.click('#planner_validar'); await page.click('.swal2-cancel');
        assert.equal(asociaciones,0);
        await page.click('#planner_validar'); await page.click('.swal2-confirm');
        await page.waitForFunction(()=>document.querySelector('.swal2-html-container')?.textContent==='Asociación conservada');
        assert.equal(asociaciones,1); await page.click('.swal2-confirm');
        await page.setViewportSize({width:390,height:850});
        await page.click('#planner_consultar');
        await page.waitForFunction(()=>document.querySelector('#planner_evaluables').textContent==='0');
        assert(await page.evaluate(()=>document.querySelector('#planner_tabla').closest('.table-responsive').getBoundingClientRect().width<=390));
        // Un conflicto inequívoco muestra SweetAlert y nunca conserva resultados del período anterior.
        periodoIncorrecto=true;
        await page.click('#planner_consultar'); await page.waitForSelector('.swal2-popup');
        assert((await page.locator('.swal2-html-container').textContent()).includes('11/2026'));
        assert.equal(await page.locator('.planner-detalle').count(),0);
        await page.click('.swal2-confirm'); periodoIncorrecto=false; ambiguo=true;
        // Cancelar la decisión de un título sin año no crea asociación ni muestra tareas.
        const previas=asociaciones;
        await page.click('#planner_consultar'); await page.waitForSelector('.swal2-textarea');
        await page.click('.swal2-cancel');
        assert.equal(asociaciones,previas); assert.equal(await page.locator('.planner-detalle').count(),0);
        await page.click('#planner_consultar'); await page.waitForSelector('.swal2-textarea');
        await page.locator('.swal2-textarea').fill('corto'); await page.click('.swal2-confirm');
        assert.equal(decisiones.length,0);
        await page.locator('.swal2-textarea').fill('Período confirmado con el responsable del plan mensual.');
        await page.click('.swal2-confirm');
        await page.waitForFunction(()=>document.querySelector('#planner_evaluables').textContent==='0');
        assert.equal(decisiones.length,1);
        assert.equal(decisiones[0].confirmar_periodo,'1'); assert.equal(decisiones[0].plan_id,'plan');
        assert.equal(decisiones[0].mes,'7'); assert(decisiones[0].motivo_periodo.length>=15);
        assert.deepEqual(errors,[]);
        console.log('OK: Chrome Planner, empleados múltiples/Todos, carga de grupos, detalle fecha/hora, XSS, períodos, confirmación y móvil.');
    } finally {await browser.close();}
})().catch(e=>{console.error(e);process.exit(1);});
