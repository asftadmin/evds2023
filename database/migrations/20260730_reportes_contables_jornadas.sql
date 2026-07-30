BEGIN;

CREATE TABLE IF NOT EXISTS public.jornada_lotes_reporte (
    jlot_id bigserial PRIMARY KEY,
    jlot_nombre character varying(120) NOT NULL,
    jlot_fecha_corte date NOT NULL,
    jlot_estado character varying(20) NOT NULL DEFAULT 'BORRADOR'
        CHECK (jlot_estado IN ('BORRADOR', 'CERRADO')),
    jlot_version_formato character varying(20) NOT NULL DEFAULT 'GH-F-19 v3',
    jlot_creado_por integer NOT NULL REFERENCES public.usuarios(user_id),
    jlot_fecha_creacion timestamp without time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
    jlot_cerrado_por integer REFERENCES public.usuarios(user_id),
    jlot_fecha_cierre timestamp without time zone,
    UNIQUE (jlot_nombre, jlot_fecha_corte)
);

CREATE INDEX IF NOT EXISTS idx_jornada_lotes_reporte_estado
    ON public.jornada_lotes_reporte (jlot_estado, jlot_fecha_corte DESC);

CREATE TABLE IF NOT EXISTS public.jornada_lote_empleados (
    jle_id bigserial PRIMARY KEY,
    jlot_id bigint NOT NULL
        REFERENCES public.jornada_lotes_reporte(jlot_id) ON DELETE CASCADE,
    empleado_id integer NOT NULL REFERENCES public.empleados(id_empl),
    jle_desde date,
    jle_hasta date NOT NULL,
    jle_origen_periodo character varying(30) NOT NULL DEFAULT 'SUGERIDO',
    jle_motivo_ajuste text,
    jle_estado character varying(30) NOT NULL DEFAULT 'PENDIENTE',
    jle_cantidad_jornadas integer NOT NULL DEFAULT 0,
    jle_cantidad_pendientes integer NOT NULL DEFAULT 0,
    jle_minutos_reportables integer NOT NULL DEFAULT 0,
    jle_diagnostico text,
    jle_snapshot jsonb,
    jle_actualizado_por integer REFERENCES public.usuarios(user_id),
    jle_fecha_actualizacion timestamp without time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (jlot_id, empleado_id),
    CHECK (jle_desde IS NULL OR jle_hasta >= jle_desde)
);

CREATE INDEX IF NOT EXISTS idx_jornada_lote_empleados_lote
    ON public.jornada_lote_empleados (jlot_id, jle_estado, empleado_id);

CREATE INDEX IF NOT EXISTS idx_jornada_lote_empleados_periodo
    ON public.jornada_lote_empleados (empleado_id, jle_desde, jle_hasta);

CREATE TABLE IF NOT EXISTS public.jornada_lote_auditoria (
    jla_id bigserial PRIMARY KEY,
    jlot_id bigint NOT NULL
        REFERENCES public.jornada_lotes_reporte(jlot_id) ON DELETE CASCADE,
    jle_id bigint
        REFERENCES public.jornada_lote_empleados(jle_id) ON DELETE SET NULL,
    jla_accion character varying(50) NOT NULL,
    jla_datos_anteriores jsonb,
    jla_datos_nuevos jsonb,
    jla_motivo text,
    jla_usuario_id integer NOT NULL REFERENCES public.usuarios(user_id),
    jla_fecha timestamp without time zone NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_jornada_lote_auditoria_lote
    ON public.jornada_lote_auditoria (jlot_id, jla_fecha);

COMMIT;
