BEGIN;

-- Catálogo de estados del flujo de jornadas.
CREATE TABLE IF NOT EXISTS public.jornada_estados (
    je_id serial PRIMARY KEY,
    je_codigo character varying(40) NOT NULL UNIQUE,
    je_nombre character varying(100) NOT NULL,
    je_estado integer NOT NULL DEFAULT 1 CHECK (je_estado IN (0, 1))
);

INSERT INTO public.jornada_estados (je_codigo, je_nombre, je_estado)
VALUES
    ('BORRADOR', 'Borrador', 1),
    ('PENDIENTE_APROBACION', 'Pendiente de aprobación', 1),
    ('APROBADO', 'Aprobado', 1),
    ('RECHAZADO', 'Rechazado', 1),
    ('PENDIENTE_CORRECCION', 'Pendiente de corrección', 1),
    ('CORREGIDO', 'Corregido', 1)
ON CONFLICT (je_codigo) DO UPDATE
SET je_nombre = EXCLUDED.je_nombre,
    je_estado = EXCLUDED.je_estado;

-- Reglas laborales versionadas para no fijar las franjas en el código.
CREATE TABLE IF NOT EXISTS public.jornada_reglas (
    jreg_id serial PRIMARY KEY,
    jreg_nombre character varying(120) NOT NULL,
    jreg_vigencia_desde date NOT NULL,
    jreg_vigencia_hasta date,
    jreg_hora_diurna_inicio time without time zone NOT NULL DEFAULT '06:00',
    jreg_hora_nocturna_inicio time without time zone NOT NULL DEFAULT '19:00',
    jreg_recargo_nocturno_inicio time without time zone NOT NULL DEFAULT '00:00',
    jreg_recargo_nocturno_fin time without time zone NOT NULL DEFAULT '06:00',
    jreg_ordinaria_continuacion_fin time without time zone NOT NULL DEFAULT '08:00',
    jreg_ordinaria_diurna_fin time without time zone NOT NULL DEFAULT '15:00',
    jreg_max_lunes_viernes_min integer NOT NULL DEFAULT 480 CHECK (jreg_max_lunes_viernes_min >= 0),
    jreg_max_sabado_min integer NOT NULL DEFAULT 120 CHECK (jreg_max_sabado_min >= 0),
    jreg_almuerzo_min integer NOT NULL DEFAULT 60 CHECK (jreg_almuerzo_min >= 0),
    jreg_estado integer NOT NULL DEFAULT 1 CHECK (jreg_estado IN (0, 1)),
    jreg_creado_por integer REFERENCES public.usuarios(user_id),
    jreg_fecha_creacion timestamp without time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK (jreg_vigencia_hasta IS NULL OR jreg_vigencia_hasta >= jreg_vigencia_desde)
);

ALTER TABLE public.jornada_reglas
    ADD COLUMN IF NOT EXISTS jreg_recargo_nocturno_inicio
        time without time zone NOT NULL DEFAULT '00:00',
    ADD COLUMN IF NOT EXISTS jreg_recargo_nocturno_fin
        time without time zone NOT NULL DEFAULT '06:00',
    ADD COLUMN IF NOT EXISTS jreg_ordinaria_continuacion_fin
        time without time zone NOT NULL DEFAULT '08:00',
    ADD COLUMN IF NOT EXISTS jreg_ordinaria_diurna_fin
        time without time zone NOT NULL DEFAULT '15:00';

INSERT INTO public.jornada_reglas (
    jreg_nombre,
    jreg_vigencia_desde,
    jreg_hora_diurna_inicio,
    jreg_hora_nocturna_inicio,
    jreg_max_lunes_viernes_min,
    jreg_max_sabado_min,
    jreg_almuerzo_min,
    jreg_estado
)
SELECT
    'Regla inicial jornadas',
    DATE '2026-01-01',
    TIME '06:00',
    TIME '19:00',
    480,
    120,
    60,
    1
WHERE NOT EXISTS (
    SELECT 1
    FROM public.jornada_reglas
    WHERE jreg_vigencia_desde = DATE '2026-01-01'
);

-- Horarios asignables a los empleados.
CREATE TABLE IF NOT EXISTS public.horarios_laborales (
    hlab_id serial PRIMARY KEY,
    hlab_nombre character varying(120) NOT NULL,
    hlab_descripcion text,
    hlab_estado integer NOT NULL DEFAULT 1 CHECK (hlab_estado IN (0, 1)),
    hlab_creado_por integer REFERENCES public.usuarios(user_id),
    hlab_fecha_creacion timestamp without time zone NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS public.horario_laboral_detalle (
    hld_id serial PRIMARY KEY,
    hlab_id integer NOT NULL REFERENCES public.horarios_laborales(hlab_id) ON DELETE CASCADE,
    hld_dia_semana smallint NOT NULL CHECK (hld_dia_semana BETWEEN 1 AND 7),
    hld_hora_inicio time without time zone NOT NULL,
    hld_hora_fin time without time zone NOT NULL,
    hld_minutos_ordinarios integer NOT NULL DEFAULT 0 CHECK (hld_minutos_ordinarios >= 0),
    hld_minutos_descanso integer NOT NULL DEFAULT 0 CHECK (hld_minutos_descanso >= 0),
    hld_cruza_medianoche integer NOT NULL DEFAULT 0 CHECK (hld_cruza_medianoche IN (0, 1)),
    hld_estado integer NOT NULL DEFAULT 1 CHECK (hld_estado IN (0, 1))
);

CREATE INDEX IF NOT EXISTS idx_horario_detalle_horario_dia
    ON public.horario_laboral_detalle (hlab_id, hld_dia_semana, hld_estado);

CREATE TABLE IF NOT EXISTS public.empleado_horario (
    eh_id serial PRIMARY KEY,
    empleado_id integer NOT NULL REFERENCES public.empleados(id_empl),
    hlab_id integer NOT NULL REFERENCES public.horarios_laborales(hlab_id),
    eh_vigencia_desde date NOT NULL,
    eh_vigencia_hasta date,
    eh_estado integer NOT NULL DEFAULT 1 CHECK (eh_estado IN (0, 1)),
    eh_creado_por integer REFERENCES public.usuarios(user_id),
    eh_fecha_creacion timestamp without time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK (eh_vigencia_hasta IS NULL OR eh_vigencia_hasta >= eh_vigencia_desde)
);

CREATE INDEX IF NOT EXISTS idx_empleado_horario_vigencia
    ON public.empleado_horario (empleado_id, eh_vigencia_desde, eh_vigencia_hasta, eh_estado);

-- Calendario controlado de días festivos.
CREATE TABLE IF NOT EXISTS public.calendario_festivos (
    cf_id serial PRIMARY KEY,
    cf_fecha date NOT NULL UNIQUE,
    cf_descripcion character varying(160),
    cf_estado integer NOT NULL DEFAULT 1 CHECK (cf_estado IN (0, 1)),
    cf_creado_por integer REFERENCES public.usuarios(user_id),
    cf_fecha_creacion timestamp without time zone NOT NULL DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE public.calendario_festivos
    ALTER COLUMN cf_descripcion DROP NOT NULL;

-- Registro principal. El día se deriva de jornada_inicio y no se duplica.
CREATE TABLE IF NOT EXISTS public.jornadas_trabajo (
    jornada_id bigserial PRIMARY KEY,
    empleado_id integer NOT NULL REFERENCES public.empleados(id_empl),
    jornada_inicio timestamp without time zone NOT NULL,
    jornada_fin timestamp without time zone NOT NULL,
    jornada_minutos_ordinarios integer NOT NULL DEFAULT 0 CHECK (jornada_minutos_ordinarios >= 0),
    jornada_ubicacion character varying(250) NOT NULL,
    jornada_actividad text NOT NULL,
    jornada_observaciones text,
    jornada_origen character varying(30) NOT NULL DEFAULT 'AUTOREGISTRO',
    jornada_estado_id integer NOT NULL REFERENCES public.jornada_estados(je_id),
    jornada_creado_por integer NOT NULL REFERENCES public.usuarios(user_id),
    jornada_fecha_creacion timestamp without time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
    jornada_fecha_actualizacion timestamp without time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
    jornada_inconsistente integer NOT NULL DEFAULT 0 CHECK (jornada_inconsistente IN (0, 1)),
    jornada_inconsistencia_detalle text,
    jornada_version integer NOT NULL DEFAULT 1 CHECK (jornada_version > 0),
    CHECK (jornada_fin > jornada_inicio)
);

CREATE INDEX IF NOT EXISTS idx_jornadas_empleado_inicio
    ON public.jornadas_trabajo (empleado_id, jornada_inicio);

CREATE INDEX IF NOT EXISTS idx_jornadas_estado_inicio
    ON public.jornadas_trabajo (jornada_estado_id, jornada_inicio);

-- Historial de decisiones de aprobación y rechazo.
CREATE TABLE IF NOT EXISTS public.jornada_aprobaciones (
    jap_id bigserial PRIMARY KEY,
    jornada_id bigint NOT NULL REFERENCES public.jornadas_trabajo(jornada_id) ON DELETE CASCADE,
    jap_etapa character varying(40) NOT NULL,
    jap_decision character varying(30) NOT NULL,
    jap_usuario_id integer NOT NULL REFERENCES public.usuarios(user_id),
    jap_empleado_id integer REFERENCES public.empleados(id_empl),
    jap_motivo text,
    jap_fecha timestamp without time zone NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_jornada_aprobaciones_jornada
    ON public.jornada_aprobaciones (jornada_id, jap_fecha);

-- Auditoría inmutable de los cambios funcionales.
CREATE TABLE IF NOT EXISTS public.jornada_auditoria (
    jaud_id bigserial PRIMARY KEY,
    jornada_id bigint REFERENCES public.jornadas_trabajo(jornada_id) ON DELETE SET NULL,
    jaud_accion character varying(60) NOT NULL,
    jaud_estado_anterior character varying(40),
    jaud_estado_nuevo character varying(40),
    jaud_datos_anteriores jsonb,
    jaud_datos_nuevos jsonb,
    jaud_motivo text,
    jaud_usuario_id integer NOT NULL REFERENCES public.usuarios(user_id),
    jaud_fecha timestamp without time zone NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_jornada_auditoria_jornada
    ON public.jornada_auditoria (jornada_id, jaud_fecha);

-- Propuestas de corrección; nunca sobrescriben el registro al ser creadas.
CREATE TABLE IF NOT EXISTS public.jornada_correcciones (
    jcor_id bigserial PRIMARY KEY,
    jornada_id bigint NOT NULL REFERENCES public.jornadas_trabajo(jornada_id) ON DELETE CASCADE,
    jcor_datos_anteriores jsonb NOT NULL,
    jcor_datos_propuestos jsonb NOT NULL,
    jcor_biotime jsonb,
    jcor_motivo text NOT NULL,
    jcor_estado character varying(40) NOT NULL DEFAULT 'PENDIENTE_JEFE',
    jcor_propuesto_por integer NOT NULL REFERENCES public.usuarios(user_id),
    jcor_fecha_propuesta timestamp without time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
    jcor_aprobado_jefe_por integer REFERENCES public.usuarios(user_id),
    jcor_fecha_aprobacion_jefe timestamp without time zone,
    jcor_aprobado_gh_por integer REFERENCES public.usuarios(user_id),
    jcor_fecha_aprobacion_gh timestamp without time zone,
    jcor_motivo_rechazo text
);

CREATE INDEX IF NOT EXISTS idx_jornada_correcciones_estado
    ON public.jornada_correcciones (jcor_estado, jcor_fecha_propuesta);

-- Catálogo reservado para respuestas y reportes contables.
CREATE TABLE IF NOT EXISTS public.jornada_conceptos (
    jcon_id serial PRIMARY KEY,
    jcon_codigo character varying(20) NOT NULL UNIQUE,
    jcon_nombre character varying(140) NOT NULL,
    jcon_codigo_contable character varying(30),
    jcon_estado integer NOT NULL DEFAULT 1 CHECK (jcon_estado IN (0, 1))
);

INSERT INTO public.jornada_conceptos (jcon_codigo, jcon_nombre, jcon_estado)
VALUES
    ('ORD', 'Horas ordinarias', 1),
    ('RN', 'Recargo nocturno', 1),
    ('HED', 'Horas extras diurnas', 1),
    ('HEN', 'Horas extras nocturnas', 1),
    ('HEDF', 'Horas extras diurnas festivas', 1),
    ('HENF', 'Horas extras nocturnas festivas', 1),
    ('RF', 'Recargo festivo', 1),
    ('NO_LIQ', 'Tiempo no liquidable', 1)
ON CONFLICT (jcon_codigo) DO UPDATE
SET jcon_nombre = EXCLUDED.jcon_nombre,
    jcon_estado = EXCLUDED.jcon_estado;

-- Resultado segmentado y reproducible de la clasificación.
CREATE TABLE IF NOT EXISTS public.jornada_clasificaciones (
    jcla_id bigserial PRIMARY KEY,
    jornada_id bigint NOT NULL REFERENCES public.jornadas_trabajo(jornada_id) ON DELETE CASCADE,
    jcon_id integer NOT NULL REFERENCES public.jornada_conceptos(jcon_id),
    jreg_id integer REFERENCES public.jornada_reglas(jreg_id),
    jcla_inicio timestamp without time zone NOT NULL,
    jcla_fin timestamp without time zone NOT NULL,
    jcla_minutos integer NOT NULL CHECK (jcla_minutos > 0),
    jcla_fecha_calculo timestamp without time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
    jcla_calculado_por integer NOT NULL REFERENCES public.usuarios(user_id),
    CHECK (jcla_fin > jcla_inicio)
);

CREATE INDEX IF NOT EXISTS idx_jornada_clasificaciones_jornada
    ON public.jornada_clasificaciones (jornada_id, jcla_inicio);

-- Menús del módulo. Cada funcionalidad se autoriza con el esquema actual.
WITH nuevos_menu(menu_nomb, menu_ident, menu_icon) AS (
    VALUES
        ('Mis Jornadas', 'mis_jornadas', 'nav-icon fas fa-user-clock'),
        ('Jornadas de mi Equipo', 'equipo', 'nav-icon fas fa-users'),
        ('Aprobaciones Jornadas', 'aprobaciones', 'nav-icon fas fa-user-check'),
        ('Gestión de Jornadas', 'gestion', 'nav-icon fas fa-clipboard-list'),
        ('Inconsistencias Jornadas', 'inconsistencias', 'nav-icon fas fa-exclamation-triangle'),
        ('Liquidación de Horas', 'liquidacion', 'nav-icon fas fa-calculator'),
        ('Reporte Operativo Jornadas', 'reporte_operativo', 'nav-icon fas fa-file-alt'),
        ('Reporte Contable Jornadas', 'reporte_contable', 'nav-icon fas fa-file-invoice-dollar'),
        ('Configuración Jornadas', 'configuracion', 'nav-icon fas fa-cogs')
)
INSERT INTO public.menu (menu_nomb, menu_ruta, menu_ident, menu_esta, menu_icon)
SELECT nm.menu_nomb, '../MntJornadas/', nm.menu_ident, 1, nm.menu_icon
FROM nuevos_menu nm
WHERE NOT EXISTS (
    SELECT 1
    FROM public.menu m
    WHERE m.menu_ident = nm.menu_ident
      AND m.menu_ruta = '../MntJornadas/'
);

-- Crea una fila de permiso por rol y menú para que el mantenimiento actual
-- pueda habilitar o deshabilitar cada acceso.
INSERT INTO public.permisos (perm_menu, perm_rol, perm_usua, perm_esta)
SELECT
    m.menu_id,
    r.rol_id,
    CASE
        WHEN m.menu_ident = 'mis_jornadas' THEN 'Si'
        ELSE 'No'
    END,
    1
FROM public.menu m
CROSS JOIN public.rol r
WHERE m.menu_ruta = '../MntJornadas/'
  AND m.menu_ident IN (
      'mis_jornadas',
      'equipo',
      'aprobaciones',
      'gestion',
      'inconsistencias',
      'liquidacion',
      'reporte_operativo',
      'reporte_contable',
      'configuracion'
  )
  AND NOT EXISTS (
      SELECT 1
      FROM public.permisos p
      WHERE p.perm_menu = m.menu_id
        AND p.perm_rol = r.rol_id
  );

-- La primera entrega solo publica Mis Jornadas. Los demás menús se habilitan
-- en la migración de cada fase cuando su vista y endpoints estén completos.
UPDATE public.permisos p
SET perm_usua = 'No'
FROM public.menu m
WHERE m.menu_id = p.perm_menu
  AND m.menu_ruta = '../MntJornadas/'
  AND m.menu_ident <> 'mis_jornadas';

-- La bandeja de aprobaciones se publica para el perfil Jefe Inmediato. Los
-- empleados con relación activa como jefe heredan este permiso sin perder su
-- rol principal.
UPDATE public.permisos p
SET perm_usua = 'Si',
    perm_esta = 1
FROM public.menu m, public.rol r
WHERE m.menu_id = p.perm_menu
  AND r.rol_id = p.perm_rol
  AND m.menu_ruta = '../MntJornadas/'
  AND m.menu_ident IN ('equipo', 'aprobaciones')
  AND r.rol_nomb = 'Jefe Inmediato'
  AND r.rol_esta = 1;

-- Publica exclusivamente las vistas contables que ya cuentan con endpoints y
-- validación de rol en el servidor.
UPDATE public.permisos p
SET perm_usua = 'Si',
    perm_esta = 1
FROM public.menu m, public.rol r
WHERE m.menu_id = p.perm_menu
  AND r.rol_id = p.perm_rol
  AND m.menu_ruta = '../MntJornadas/'
  AND m.menu_ident IN (
      'liquidacion',
      'inconsistencias',
      'reporte_contable',
      'configuracion'
  )
  AND r.rol_nomb = 'Contabilidad'
  AND r.rol_esta = 1;

COMMIT;
