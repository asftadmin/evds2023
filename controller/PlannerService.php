<?php

/** Permite al cliente pedir una decisión explícita, sin confundirla con un error Graph. */
class PlannerConfirmacionPeriodo extends DomainException
{
    public function __construct(public array $plan)
    {
        parent::__construct('El nombre del plan no identifica un período único. Requiere confirmación administrativa y registro del motivo.');
    }
}

/** Coordinación Graph y motor compartido por AJAX, capturas y cierre CLI. Sin SQL. */
class PlannerService
{
    private $graph;
    public function __construct(private Planner $modelo, ?callable $graph = null)
    {
        $this->graph = $graph ?? static fn($url) => CurlController::requestMicrosoftGraph($url, 'GET');
    }

    /** Valida el período sin conversiones permisivas de parámetros HTTP. */
    public static function periodo($anio, $mes): string
    {
        if (!is_scalar($anio) || !is_scalar($mes) || !preg_match('/\A20[0-9]{2}\z/', (string)$anio)
            || !preg_match('/\A(?:[1-9]|0[1-9]|1[0-2])\z/', (string)$mes)) {
            throw new InvalidArgumentException('Año o mes inválido (2000–2099).');
        }
        return sprintf('%04d-%02d-01',(int)$anio,(int)$mes);
    }

    /** Fechas Graph estrictas con zona explícita y validación de calendario. */
    private static function fecha($valor): ?DateTimeImmutable
    {
        if (!is_string($valor) || !preg_match('/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,7})?(?:Z|[+-]\d{2}:\d{2})\z/', $valor)) { return null; }
        // PHP admite microsegundos; Graph puede entregar siete decimales.
        $valor = preg_replace('/(\.\d{6})\d(?=Z|[+-])/', '$1', $valor);
        try {
            $fecha = new DateTimeImmutable($valor);
            $errores = DateTimeImmutable::getLastErrors();
            if ($errores && ($errores['warning_count'] || $errores['error_count'])) { return null; }
            return $fecha->setTimezone(new DateTimeZone('America/Bogota'));
        } catch (Exception $e) { return null; }
    }

    public static function corte(string $periodo): DateTimeImmutable
    {
        return (new DateTimeImmutable($periodo, new DateTimeZone('America/Bogota')))->modify('+1 month');
    }

    /** Una regla única para todos los responsables y todos los puntos de entrada. */
    public static function evaluar(array $tarea, string $periodo, DateTimeImmutable $ahora): array
    {
        $corte = self::corte($periodo);
        $limite = self::fecha($tarea['dueDateTime'] ?? null);
        $fin = self::fecha($tarea['completedDateTime'] ?? null);
        $base = ['nota'=>null,'estado'=>'Requiere revisión','motivo'=>'Sin fecha de vencimiento',
            'vencimiento'=>$limite?->format(DATE_ATOM),'finalizacion'=>$fin?->format(DATE_ATOM)];
        if (($tarea['dueDateTime'] ?? null) === null) { return $base; }
        $porcentaje = $tarea['percentComplete'] ?? null;
        if (!$limite || !is_int($porcentaje) || $porcentaje < 0 || $porcentaje > 100
            || ($porcentaje === 100 && (!$fin || $fin > $ahora))
            || ($porcentaje !== 100 && ($tarea['completedDateTime'] ?? null) !== null)) {
            return array_replace($base,['motivo'=>'Información de fechas o finalización inconsistente']);
        }
        if ($porcentaje !== 100) {
            return array_replace($base,['nota'=>0,'estado'=>$ahora < $corte ? 'En proceso' : 'Incumplida',
                'motivo'=>$ahora < $corte ? 'Pendiente; aporte provisional cero' : 'Pendiente al cierre mensual']);
        }
        if ($fin >= $corte) {
            return array_replace($base,['nota'=>0,'estado'=>'Incumplida','motivo'=>'Finalizada en el mes siguiente o después']);
        }
        // El vencimiento permite todo el día colombiano; el corte mensual conserva su hora exacta.
        $oportuno = $fin->format('Y-m-d') <= $limite->format('Y-m-d');
        return array_replace($base,['nota'=>$oportuno ? 100 : 50,
            'estado'=>$oportuno ? 'Puntual' : 'Tardía',
            'motivo'=>$oportuno ? 'Entrega en el día de vencimiento o antes, y antes del corte' : 'Entrega posterior al día de vencimiento y antes del corte']);
    }

    /** Consolida por assignments; duplicados locales quedan como excepción, sin atribución. */
    public static function calcular(array $tareas, array $empleados, string $periodo, DateTimeImmutable $ahora): array
    {
        $indice = []; $filas = []; $excepciones = [];
        foreach ($empleados as $e) {
            $id = (string)$e['id_empl'];
            $oid = empty($e['user_graph_id']) ? null : strtolower($e['user_graph_id']);
            $filas[$id] = array_merge($e,['user_graph_id'=>$oid,'asignadas'=>0,'evaluables'=>0,'puntuales'=>0,
                'tardias'=>0,'incumplidas'=>0,'pendientes'=>0,'excluidas'=>0,'cumplimiento'=>null,'tareas'=>[]]);
            if ($oid) { $indice[$oid][] = $id; }
        }
        $vistos = [];
        foreach ($tareas as $t) {
            if (!is_array($t) || !is_string($t['id'] ?? null) || $t['id'] === '' || isset($vistos[$t['id']]) || !isset($t['assignments']) || !is_array($t['assignments'])) {
                throw new RuntimeException('Colección de tareas incompleta o duplicada.');
            }
            $vistos[$t['id']] = true;
            $valor = self::evaluar($t,$periodo,$ahora);
            $detalle = array_merge(['id'=>$t['id'],'nombre'=>$t['title'] ?? '(Sin título)'], $valor);
            if (!$t['assignments']) { $excepciones[] = array_merge($detalle,['object_id'=>null,'incidencia'=>'Sin responsables']); }
            foreach ($t['assignments'] as $oid => $asignacion) {
                $oid = strtolower((string)$oid);
                $d = array_merge($detalle,['object_id'=>$oid]);
                if (count($indice[$oid] ?? []) !== 1) {
                    $excepciones[] = array_merge($d,['incidencia'=>'Asignación no vinculada o vínculo ambiguo']);
                    continue;
                }
                $e = &$filas[$indice[$oid][0]];
                $e['asignadas']++; $e['tareas'][] = $d;
                if ($d['nota'] === null) {
                    $e['excluidas']++;
                    $excepciones[] = array_merge($d,['id_empl'=>$e['id_empl'],'incidencia'=>$d['motivo']]);
                } else {
                    $e['evaluables']++;
                    $campo = ['Puntual'=>'puntuales','Tardía'=>'tardias','Incumplida'=>'incumplidas','En proceso'=>'pendientes'][$d['estado']];
                    $e[$campo]++;
                }
                unset($e);
            }
        }
        foreach ($filas as &$e) {
            $e['cumplimiento'] = $e['evaluables'] ? round(($e['puntuales'] + $e['tardias'] * .5) / $e['evaluables'] * 100,2) : null;
        }
        unset($e);
        return ['periodo'=>$periodo,'estado'=>$ahora < self::corte($periodo) ? 'Abierto' : 'Consulta histórica reconstruida',
            'empleados'=>array_values($filas),'excepciones'=>$excepciones,'tareas_graph'=>$tareas,
            'consultado_en'=>$ahora->format(DATE_ATOM),'corte'=>self::corte($periodo)->format(DATE_ATOM)];
    }

    /** Exige colecciones completas: CurlController ya sigue cada nextLink. */
    private function coleccion(string $url): array
    {
        $r = ($this->graph)($url);
        if (!isset($r->value) || !is_array($r->value) || isset($r->{'@odata.nextLink'})) {
            throw new RuntimeException('Colección Graph incompleta.');
        }
        return json_decode(json_encode($r->value,JSON_THROW_ON_ERROR),true,512,JSON_THROW_ON_ERROR);
    }

    /** Descubre sin inferir años de nombres ambiguos ni de la fecha de creación. */
    public function grupos(): array
    {
        return $this->coleccion('groups?$select=id,displayName&$filter=groupTypes/any(c:c%20eq%20%27Unified%27)');
    }

    public static function identificador($id): string
    {
        if (!is_string($id) || !preg_match('/\A[A-Za-z0-9_-]{1,200}\z/', $id)) {
            throw new InvalidArgumentException('Identificador Graph inválido.');
        }
        return $id;
    }

    /** El administrador selecciona grupo y plan explícitamente para el período. */
    public function planes(string $grupo): array
    {
        return $this->coleccion('groups/'.self::identificador($grupo).'/planner/plans');
    }

    /** Valida mes/año del título. Devuelve true si queda ambiguo; no usa grupo ni createdDateTime. */
    public static function periodoDelPlan(string $nombre, string $periodo): bool
    {
        $nombre = mb_strtoupper($nombre,'UTF-8');
        $meses = ['ENE'=>1,'ENERO'=>1,'FEB'=>2,'FEBRERO'=>2,'MAR'=>3,'MARZO'=>3,'ABR'=>4,'ABRIL'=>4,
            'MAY'=>5,'MAYO'=>5,'JUN'=>6,'JUNIO'=>6,'JUL'=>7,'JULIO'=>7,'AGO'=>8,'AGOSTO'=>8,
            'SEP'=>9,'SEPT'=>9,'SEPTIEMBRE'=>9,'SETIEMBRE'=>9,'OCT'=>10,'OCTUBRE'=>10,
            'NOV'=>11,'NOVIEMBRE'=>11,'DIC'=>12,'DICIEMBRE'=>12,
            'JAN'=>1,'JANUARY'=>1,'FEBRUARY'=>2,'MARCH'=>3,'APR'=>4,'APRIL'=>4,'JUNE'=>6,'JULY'=>7,
            'AUG'=>8,'AUGUST'=>8,'SEPTEMBER'=>9,'OCTOBER'=>10,'NOVEMBER'=>11,'DEC'=>12,'DECEMBER'=>12];
        $nombres=implode('|',array_keys($meses));
        preg_match_all('/(?<!\p{L})('.$nombres.')(?!\p{L})/u',$nombre,$m);
        $encontrados = array_map(static fn($mes)=>$meses[$mes],$m[1]);
        preg_match_all('/(?<!\d)((?:19|20|21)\d{2})(?!\d)/',$nombre,$a);
        // Formatos mensuales numéricos inequívocos: 2026-10, 10/2026 y equivalentes.
        preg_match_all('/(?<!\d)((?:19|20|21)\d{2})[-\/. _](0?[1-9]|1[0-2])(?!\d)/',$nombre,$ym,PREG_SET_ORDER);
        preg_match_all('/(?<!\d)(0?[1-9]|1[0-2])[-\/. _]((?:19|20|21)\d{2})(?!\d)/',$nombre,$my,PREG_SET_ORDER);
        $pares=[];
        foreach ($ym as $par) { $encontrados[]=(int)$par[2]; $pares[]=sprintf('%04d-%02d-01',$par[1],$par[2]); }
        foreach ($my as $par) { $encontrados[]=(int)$par[1]; $pares[]=sprintf('%04d-%02d-01',$par[2],$par[1]); }
        // Un año incidental de revisión no permite omitir una pareja mes/año escrita explícitamente.
        preg_match_all('/(?<!\p{L})('.$nombres.')(?!\p{L})[\s._\/-]*(?:(?:DE|DEL|AÑO)\s+)?((?:19|20|21)\d{2})(?!\d)/u',$nombre,$ma,PREG_SET_ORDER);
        $resto=$nombre;
        foreach ($ma as $par) { $resto=str_replace($par[0],' ',$resto); }
        preg_match_all('/(?<!\d)((?:19|20|21)\d{2})(?!\d)[\s._\/-]*('.$nombres.')(?!\p{L})/u',$resto,$am,PREG_SET_ORDER);
        foreach ($ma as $par) { $pares[]=sprintf('%04d-%02d-01',$par[2],$meses[$par[1]]); }
        foreach ($am as $par) { $pares[]=sprintf('%04d-%02d-01',$par[1],$meses[$par[2]]); }
        $pares=array_values(array_unique($pares));
        $encontrados = array_values(array_unique($encontrados));
        $anios = array_values(array_unique(array_map('intval',$a[1])));
        $mes = (int)substr($periodo,5,2); $anio = (int)substr($periodo,0,4);
        if (($encontrados && !in_array($mes,$encontrados,true)) || ($anios && !in_array($anio,$anios,true))
            || ($pares && !in_array($periodo,$pares,true) && (count($encontrados)===1 || count($pares)>=2))) {
            throw new DomainException('El plan «'.$nombre.'» no corresponde al período '.sprintf('%02d/%04d',$mes,$anio).'. Seleccione el plan del mes y año solicitados.');
        }
        return count($encontrados)!==1 || count($anios)!==1;
    }

    /** Valida pertenencia, nombre mensual y uso previo; devuelve exclusivamente datos de Graph. */
    public function validarPlan(string $grupo, string $plan, string $periodo): array
    {
        self::identificador($plan); $grupoReal = null; $planReal = null;
        foreach ($this->grupos() as $g) { if ($g['id'] === $grupo) { $grupoReal = $g; } }
        foreach ($this->planes($grupo) as $p) { if ($p['id'] === $plan) { $planReal = $p; } }
        if (!$grupoReal || !$planReal) { throw new DomainException('El plan no pertenece al grupo seleccionado.'); }
        $r = ($this->graph)('planner/plans/'.$plan);
        if (($r->id ?? null) !== $plan || ($r->title ?? null) !== $planReal['title']) {
            throw new DomainException('El plan cambió durante la validación. Consulte nuevamente.');
        }
        $ambiguo = self::periodoDelPlan($r->title,$periodo);
        $this->modelo->verificarPlanDisponible($plan,$periodo);
        return ['grupo_id'=>$grupo,'grupo_nombre'=>$grupoReal['displayName'],'plan_id'=>$plan,'plan_nombre'=>$r->title,
            'periodo_ambiguo'=>$ambiguo];
    }

    /** Coordina la decisión administrativa antes de la escritura transaccional del modelo. */
    public function asociar(string $periodo, string $grupo, string $plan, int $actor, bool $confirmado=false, string $motivo=''): void
    {
        $real = $this->validarPlan($grupo,$plan,$periodo);
        $decision = null;
        if ($real['periodo_ambiguo'] && !$this->modelo->confirmacionPeriodo($periodo,$real)) {
            if (!$confirmado) { throw new PlannerConfirmacionPeriodo($real); }
            $decision = trim($motivo);
            if (mb_strlen($decision,'UTF-8')<15 || mb_strlen($decision,'UTF-8')>2000) {
                throw new InvalidArgumentException('Documente la validación del período con un motivo de 15 a 2000 caracteres.');
            }
        }
        $this->modelo->asociar($periodo,$real,$actor,$decision);
    }

    /** Consulta actual o resultado congelado; un cerrado nunca consulta Graph. */
    public function consultar(string $periodo, ?array $plan = null): array
    {
        $p = $this->modelo->periodo($periodo);
        // Una selección diferente no se ignora silenciosamente a favor de la asociación guardada.
        if ($p && $plan && ($plan['plan_id']!==$p['plan_id'] || $plan['grupo_id']!==$p['grupo_id'])) {
            $this->validarPlan($plan['grupo_id'],$plan['plan_id'],$periodo);
            throw new DomainException('El período ya tiene otra asociación validada. No se sustituyó el plan existente.');
        }
        if ($p && $p['estado'] !== 'Abierto') {
            // Rechaza un histórico mal rotulado sin modificarlo ni depender de Graph.
            $ambiguo = self::periodoDelPlan($p['plan_nombre'],$periodo);
            if ($ambiguo && !$this->modelo->confirmacionPeriodo($periodo,$p)) { throw new PlannerConfirmacionPeriodo($p); }
            return json_decode($p['informe'],true,512,JSON_THROW_ON_ERROR);
        }
        $plan = $p ?: $plan;
        if (!$plan) { throw new DomainException('Seleccione y valide un plan para este período.'); }
        $real = $this->validarPlan($plan['grupo_id'],$plan['plan_id'],$periodo);
        if ($real['periodo_ambiguo'] && !$this->modelo->confirmacionPeriodo($periodo,$real)) {
            throw new PlannerConfirmacionPeriodo($real);
        }
        $tareas = $this->coleccion('planner/plans/'.$real['plan_id'].'/tasks');
        return array_merge(self::calcular($tareas,$this->modelo->empleados(),$periodo,
            new DateTimeImmutable('now',new DateTimeZone('America/Bogota'))),['plan'=>$real,'asociacion_validada'=>(bool)$p]);
    }

    /** Captura y cierre exclusivos de CLI; fallo remoto impide publicar un cierre exitoso. */
    public function ejecutar(string $periodo, bool $cerrar): string
    {
        $inicio = (new DateTimeImmutable())->format(DATE_ATOM);
        if (!$this->modelo->bloquear($periodo)) { return 'ocupado'; }
        try {
            $p = $this->modelo->periodo($periodo);
            if (!$p) { throw new DomainException('Falta asociación validada.'); }
            if ($p['estado'] !== 'Abierto') { return 'ya_cerrado'; }
            $ahora = new DateTimeImmutable();
            if ($cerrar && $ahora < self::corte($periodo)) { throw new DomainException('El período sigue abierto.'); }
            $informe = $this->consultar($periodo);
            $fin = (new DateTimeImmutable())->format(DATE_ATOM);
            $captura = $this->modelo->capturar($periodo,$informe,$inicio,$fin);
            if ($cerrar) {
                $anterior = $this->modelo->ultimaCaptura($periodo,self::corte($periodo)->format(DATE_ATOM));
                // Graph no ofrece una lectura atómica al corte. Nunca afirmar exactitud inexistente.
                $informe['origen'] = 'Histórico reconstruido';
                $informe['evidencia_previa'] = $anterior ? ['captura_id'=>$anterior['captura_id'],'fin'=>$anterior['fin']] : null;
                $informe['cambios_desde_captura'] = [];
                if ($anterior) {
                    $previo = json_decode($anterior['evidencia'],true,512,JSON_THROW_ON_ERROR);
                    $actuales = array_column($informe['tareas_graph'],null,'id');
                    foreach ($previo['tareas_graph'] ?? [] as $t) {
                        if (!isset($actuales[$t['id']]) || $actuales[$t['id']] != $t) {
                            $incidencia = isset($actuales[$t['id']]) ? 'Modificada desde la captura anterior al corte' : 'Ausente respecto a la captura anterior al corte';
                            $informe['cambios_desde_captura'][] = ['id'=>$t['id'],'incidencia'=>$incidencia];
                            $informe['excepciones'][] = ['id'=>$t['id'],'nombre'=>$t['title']??'(Sin título)',
                                'object_id'=>null,'incidencia'=>$incidencia.'; requiere revisión de la evidencia conservada'];
                        }
                    }
                }
                $informe['observacion_temporal'] = 'Estado observado después del corte; consulte las capturas previas para revisar eliminaciones o reasignaciones. No certifica el estado exacto al corte.';
                $informe['estado'] = 'Cerrado con observaciones';
                $informe['consolidado_en'] = $fin;
                $this->modelo->cerrar($periodo,$informe,$captura);
            }
            $this->modelo->registrar($periodo,$cerrar ? 'cierre' : 'captura','exito',$inicio);
            return $cerrar ? 'cerrado' : 'capturado';
        } catch (Throwable $e) {
            $this->modelo->registrar($periodo,$cerrar ? 'cierre' : 'captura','fallo_reintentable',$inicio);
            throw $e;
        } finally { $this->modelo->desbloquear($periodo); }
    }
}
