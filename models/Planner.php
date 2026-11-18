<?php

/** SQL de HU-03: lectura del esquema y conservación inmutable de evidencias. */
class Planner extends Conectar
{
    private ?PDO $conexionPlanner = null;

    /** Reutiliza la conexión existente durante toda una unidad de trabajo. */
    public function db(): PDO
    {
        if (!$this->conexionPlanner) {
            $this->conexionPlanner = parent::Conexion();
            $this->conexionPlanner->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return $this->conexionPlanner;
    }

    /** Inspección de solo lectura, previa a cualquier migración. */
    public function esquema(): array
    {
        return $this->db()->query("SELECT table_name,column_name,data_type,is_nullable
            FROM information_schema.columns WHERE table_schema='public'
            AND (table_name IN ('usuarios','empleados') OR table_name LIKE 'planner_%')
            ORDER BY table_name,ordinal_position")->fetchAll(PDO::FETCH_ASSOC);
    }

    /** No crea estructuras implícitamente desde una petición. */
    public function disponible(): bool
    {
        return (bool) $this->db()->query("SELECT to_regclass('public.planner_periodos') IS NOT NULL")->fetchColumn();
    }

    /** Despliegue explícito por CLI; nunca se invoca desde el controlador HTTP. */
    public function aplicarMigracion(): void
    {
        $db=$this->db();
        $columnas=$this->esquema();
        $graph=array_values(array_filter($columnas,static fn($c)=>$c['table_name']==='usuarios' && $c['column_name']==='user_graph_id'));
        if (count($graph)!==1 || $graph[0]['data_type']!=='uuid') { throw new DomainException('El esquema HU-02 requiere revisión.'); }
        if (array_filter($columnas,static fn($c)=>str_starts_with($c['table_name'],'planner_'))) {
            throw new DomainException('Ya existen estructuras Planner. Revise el despliegue; no se sobrescribió nada.');
        }
        try {
            $db->exec(file_get_contents(__DIR__.'/../database/migrations/20261117_planner_cumplimiento.sql'));
        } catch (Throwable $e) {
            if ($db->inTransaction()) { $db->rollBack(); }
            throw $e;
        }
    }

    /** Se incluyen inactivos para detectar asignaciones ambiguas sin atribuciones arbitrarias. */
    public function empleados(): array
    {
        return $this->db()->query('SELECT e.id_empl,e.nomb_empl,u.user_id,u.user_graph_id
            FROM public.empleados e JOIN public.usuarios u ON e.user_empl=u.user_id
            ORDER BY e.nomb_empl,e.id_empl')->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Obtiene asociación validada y, si existe, el informe cerrado. */
    public function periodo(string $periodo): ?array
    {
        if (!$this->disponible()) { return null; }
        $q = $this->db()->prepare('SELECT * FROM public.planner_periodos WHERE periodo=CAST(? AS date)');
        $q->execute([$periodo]);
        return $q->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Auditoría de impacto: lee cierres originales sin recalcular ni escribir su contenido. */
    public function historicos(): array
    {
        if (!$this->disponible()) { return []; }
        return $this->db()->query("SELECT periodo,estado,informe FROM public.planner_periodos
            WHERE estado<>'Abierto' ORDER BY periodo")->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Inspección de asociaciones para detectar errores anteriores sin corregirlos automáticamente. */
    public function asociaciones(): array
    {
        if (!$this->disponible()) { return []; }
        return $this->db()->query('SELECT periodo,grupo_id,grupo_nombre,plan_id,plan_nombre,estado
            FROM public.planner_periodos ORDER BY periodo')->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Comprueba uso único también en consultas previas, antes de solicitar las tareas. */
    public function verificarPlanDisponible(string $plan, string $periodo): void
    {
        if (!$this->disponible()) { return; }
        $q=$this->db()->prepare('SELECT periodo FROM public.planner_periodos WHERE plan_id=? AND periodo<>CAST(? AS date)');
        $q->execute([$plan,$periodo]);
        $usado=$q->fetchColumn();
        if ($usado) { throw new DomainException('Este Plan ID ya está asociado al período '.substr($usado,5,2).'/'.substr($usado,0,4).'. No puede reutilizarse en otro mes.'); }
    }

    /** Decisión auditable para el nombre exacto y el período, no una confirmación genérica reutilizable. */
    public function confirmacionPeriodo(string $periodo, array $plan): bool
    {
        if (!$this->disponible()) { return false; }
        $q=$this->db()->prepare("SELECT EXISTS (SELECT 1 FROM public.planner_revisiones
            WHERE periodo=CAST(? AS date) AND evidencia @> CAST(? AS jsonb))");
        $q->execute([$periodo,json_encode(['tipo'=>'validacion_periodo_plan','plan_id'=>$plan['plan_id'],
            'grupo_id'=>$plan['grupo_id'],'plan_nombre'=>$plan['plan_nombre']],JSON_THROW_ON_ERROR)]);
        return (bool)$q->fetchColumn();
    }

    /** Guarda asociación y decisión juntas; no reemplaza asociaciones ni resultados existentes. */
    public function asociar(string $periodo, array $plan, int $actor, ?string $decision=null): void
    {
        if (!$this->disponible()) { throw new DomainException('La migración HU-03 requiere autorización y despliegue.'); }
        $db=$this->db(); $db->beginTransaction();
        try {
            // Serializa altas y la revisión de uso único, sin mantener bloqueos durante Graph.
            $db->exec("SET LOCAL lock_timeout='5s'");
            $db->exec('LOCK TABLE public.planner_periodos IN SHARE ROW EXCLUSIVE MODE');
            $this->verificarPlanDisponible($plan['plan_id'],$periodo);
            $actual=$this->periodo($periodo);
            if ($actual && ($actual['plan_id']!==$plan['plan_id'] || $actual['grupo_id']!==$plan['grupo_id'])) {
                throw new DomainException('El período ya tiene otra asociación validada. Requiere revisión administrativa.');
            }
            $requiereDecision=($plan['periodo_ambiguo']??true) && !$this->confirmacionPeriodo($periodo,$plan);
            if ($requiereDecision && ($decision===null || mb_strlen(trim($decision),'UTF-8')<15 || mb_strlen($decision,'UTF-8')>2000)) {
                throw new DomainException('El plan ambiguo requiere confirmación administrativa documentada.');
            }
            if (!$actual) {
                $q=$db->prepare('INSERT INTO public.planner_periodos
                    (periodo,grupo_id,grupo_nombre,plan_id,plan_nombre,validado_por) VALUES (CAST(? AS date),?,?,?,?,?)');
                $q->execute([$periodo,$plan['grupo_id'],$plan['grupo_nombre'],$plan['plan_id'],$plan['plan_nombre'],$actor]);
            }
            if ($requiereDecision) {
                $q=$db->prepare('INSERT INTO public.planner_revisiones (periodo,autorizado_por,motivo,evidencia)
                    VALUES (CAST(? AS date),?,?,CAST(? AS jsonb))');
                $q->execute([$periodo,$actor,trim($decision),json_encode(['tipo'=>'validacion_periodo_plan',
                    'periodo'=>$periodo,'grupo_id'=>$plan['grupo_id'],'plan_id'=>$plan['plan_id'],
                    'plan_nombre'=>$plan['plan_nombre'],'grupo_nombre'=>$plan['grupo_nombre']],JSON_THROW_ON_ERROR)]);
            }
            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            if ($e instanceof PDOException && (string)$e->getCode()==='23505') {
                throw new DomainException('El plan o el período ya tiene una asociación validada. Consulte nuevamente.');
            }
            throw $e;
        }
    }

    /** Bloqueo por período en la conexión; evita carreras entre captura y cierre. */
    public function bloquear(string $periodo): bool
    {
        $q = $this->db()->prepare('SELECT pg_try_advisory_lock(303,CAST(? AS integer))');
        $q->execute([(int) str_replace('-', '', substr($periodo,0,7))]);
        return (bool) $q->fetchColumn();
    }

    public function desbloquear(string $periodo): void
    {
        $q = $this->db()->prepare('SELECT pg_advisory_unlock(303,CAST(? AS integer))');
        $q->execute([(int) str_replace('-', '', substr($periodo,0,7))]);
    }

    /** Conserva una captura completa, incluidas asignaciones e identidades locales del momento. */
    public function capturar(string $periodo, array $informe, string $inicio, string $fin): int
    {
        $q = $this->db()->prepare('INSERT INTO public.planner_capturas (periodo,inicio,fin,evidencia)
            VALUES (CAST(? AS date),CAST(? AS timestamptz),CAST(? AS timestamptz),CAST(? AS jsonb)) RETURNING captura_id');
        $q->execute([$periodo,$inicio,$fin,json_encode($informe,JSON_THROW_ON_ERROR)]);
        return (int) $q->fetchColumn();
    }

    /** Recupera la última evidencia anterior al corte; nunca la sustituye por datos actuales. */
    public function ultimaCaptura(string $periodo, string $corte): ?array
    {
        $q = $this->db()->prepare('SELECT * FROM public.planner_capturas
            WHERE periodo=CAST(? AS date) AND fin < CAST(? AS timestamptz) ORDER BY fin DESC,captura_id DESC LIMIT 1');
        $q->execute([$periodo,$corte]);
        return $q->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Publica resultado y detalle en una transacción; la clave del período impide duplicados. */
    public function cerrar(string $periodo, array $informe, int $captura): void
    {
        $db = $this->db();
        $db->beginTransaction();
        try {
            $q = $db->prepare("UPDATE public.planner_periodos SET estado=?,consolidado_en=clock_timestamp(),
                informe=CAST(? AS jsonb),captura_cierre=? WHERE periodo=CAST(? AS date) AND estado='Abierto'");
            $q->execute([$informe['estado'],json_encode($informe,JSON_THROW_ON_ERROR),$captura,$periodo]);
            if ($q->rowCount() !== 1) { throw new DomainException('El período ya está cerrado.'); }
            $q = $db->prepare('INSERT INTO public.planner_resultados (periodo,id_empl,object_id,resultado)
                VALUES (CAST(? AS date),?,CAST(? AS uuid),CAST(? AS jsonb))');
            foreach ($informe['empleados'] as $e) {
                $q->execute([$periodo,$e['id_empl'],$e['user_graph_id'],json_encode($e,JSON_THROW_ON_ERROR)]);
            }
            $db->commit();
        } catch (Throwable $e) { $db->rollBack(); throw $e; }
    }

    /** Bitácora saneada: solo códigos locales, jamás mensajes SQL o cuerpos de Graph. */
    public function registrar(string $periodo, string $operacion, string $resultado, string $inicio): void
    {
        $q = $this->db()->prepare('INSERT INTO public.planner_ejecuciones (periodo,operacion,resultado,inicio)
            VALUES (CAST(? AS date),?,?,CAST(? AS timestamptz))');
        $q->execute([$periodo,$operacion,$resultado,$inicio]);
    }

    /** Recupera todos los abiertos vencidos para reintentar fallos también en meses posteriores. */
    public function pendientes(string $actual): array
    {
        $q = $this->db()->prepare("SELECT periodo FROM public.planner_periodos WHERE estado='Abierto'
            AND periodo < CAST(? AS date) ORDER BY periodo");
        $q->execute([$actual]);
        return $q->fetchAll(PDO::FETCH_COLUMN);
    }
}
