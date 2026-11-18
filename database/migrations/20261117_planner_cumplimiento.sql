-- HU-03. Ejecutar solo tras autorización, sobre evds_prueba (o base de despliegue).
-- No altera usuarios, empleados ni tablas de Evaluación de Desempeño.
-- Cinco tablas nuevas; evidencia JSONB incluye identidades, tareas, fechas y notas.
BEGIN;
CREATE TABLE public.planner_periodos (
    periodo date PRIMARY KEY CHECK (extract(day FROM periodo)=1),
    grupo_id text NOT NULL CHECK (length(grupo_id) BETWEEN 1 AND 200),
    grupo_nombre text NOT NULL,
    plan_id text NOT NULL CHECK (length(plan_id) BETWEEN 1 AND 200),
    plan_nombre text NOT NULL,
    validado_por integer NOT NULL REFERENCES public.usuarios(user_id),
    validado_en timestamptz NOT NULL DEFAULT clock_timestamp(),
    estado text NOT NULL DEFAULT 'Abierto' CHECK (estado IN ('Abierto','Cerrado','Cerrado con observaciones')),
    consolidado_en timestamptz,
    captura_cierre bigint,
    informe jsonb,
    UNIQUE(plan_id),
    CHECK ((estado='Abierto' AND informe IS NULL AND consolidado_en IS NULL AND captura_cierre IS NULL)
        OR (estado<>'Abierto' AND informe IS NOT NULL AND consolidado_en IS NOT NULL AND captura_cierre IS NOT NULL))
);
CREATE TABLE public.planner_capturas (
    captura_id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    periodo date NOT NULL REFERENCES public.planner_periodos(periodo),
    inicio timestamptz NOT NULL,
    fin timestamptz NOT NULL CHECK (fin>=inicio),
    evidencia jsonb NOT NULL CHECK (jsonb_typeof(evidencia)='object'),
    UNIQUE(periodo,captura_id)
);
CREATE INDEX planner_capturas_corte ON public.planner_capturas(periodo,fin DESC);
ALTER TABLE public.planner_periodos ADD CONSTRAINT planner_captura_periodo_fk
    FOREIGN KEY (periodo,captura_cierre) REFERENCES public.planner_capturas(periodo,captura_id);
CREATE TABLE public.planner_resultados (
    periodo date NOT NULL REFERENCES public.planner_periodos(periodo),
    -- Identidad conservada: sin FK mutable a empleados ni cascadas sobre el histórico.
    id_empl integer NOT NULL,
    object_id uuid,
    resultado jsonb NOT NULL CHECK (jsonb_typeof(resultado)='object'),
    PRIMARY KEY(periodo,id_empl)
);
CREATE INDEX planner_resultados_empleado ON public.planner_resultados(id_empl,periodo);
CREATE TABLE public.planner_ejecuciones (
    ejecucion_id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    periodo date NOT NULL,
    operacion text NOT NULL CHECK (operacion IN ('captura','cierre')),
    resultado text NOT NULL CHECK (resultado IN ('exito','fallo_reintentable','sin_asociacion')),
    inicio timestamptz NOT NULL,
    fin timestamptz NOT NULL DEFAULT clock_timestamp()
);
CREATE INDEX planner_ejecuciones_periodo ON public.planner_ejecuciones(periodo,fin DESC);
-- Las observaciones no se corrigen editando resultados; las revisiones son anexos auditables.
CREATE TABLE public.planner_revisiones (
    revision_id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    periodo date NOT NULL REFERENCES public.planner_periodos(periodo),
    autorizado_por integer NOT NULL REFERENCES public.usuarios(user_id),
    motivo text NOT NULL CHECK (length(trim(motivo))>=15),
    evidencia jsonb NOT NULL CHECK (jsonb_typeof(evidencia)='object'),
    creado_en timestamptz NOT NULL DEFAULT clock_timestamp()
);
-- Protección de evidencias incluso ante UPDATE/DELETE accidental desde la aplicación.
CREATE FUNCTION public.planner_rechazar_cambios() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
    RAISE EXCEPTION 'La evidencia Planner es inmutable; requiere revisión administrativa anexa';
END;
$$;
CREATE TRIGGER planner_capturas_inmutables BEFORE UPDATE OR DELETE ON public.planner_capturas
    FOR EACH ROW EXECUTE FUNCTION public.planner_rechazar_cambios();
CREATE TRIGGER planner_resultados_inmutables BEFORE UPDATE OR DELETE ON public.planner_resultados
    FOR EACH ROW EXECUTE FUNCTION public.planner_rechazar_cambios();
CREATE TRIGGER planner_revisiones_inmutables BEFORE UPDATE OR DELETE ON public.planner_revisiones
    FOR EACH ROW EXECUTE FUNCTION public.planner_rechazar_cambios();
CREATE FUNCTION public.planner_proteger_cierre() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
    IF TG_OP='DELETE' OR OLD.estado<>'Abierto' THEN
        RAISE EXCEPTION 'No se permite modificar o eliminar un período Planner validado/cerrado';
    END IF;
    IF ROW(OLD.periodo,OLD.grupo_id,OLD.plan_id,OLD.validado_por,OLD.validado_en)
        IS DISTINCT FROM ROW(NEW.periodo,NEW.grupo_id,NEW.plan_id,NEW.validado_por,NEW.validado_en) THEN
        RAISE EXCEPTION 'La asociación Planner validada no se puede sustituir';
    END IF;
    RETURN NEW;
END;
$$;
CREATE TRIGGER planner_periodos_protegidos BEFORE UPDATE OR DELETE ON public.planner_periodos
    FOR EACH ROW EXECUTE FUNCTION public.planner_proteger_cierre();
COMMENT ON TABLE public.planner_capturas IS 'Evidencias completas obtenidas por lectura periódica. No equivalen a una instantánea atómica de Microsoft Planner.';
COMMENT ON TABLE public.planner_resultados IS 'Resultado original inmutable por empleado; conteos, nota y detalle en resultado JSONB.';
COMMIT;
