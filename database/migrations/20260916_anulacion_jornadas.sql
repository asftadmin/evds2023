BEGIN;

INSERT INTO public.jornada_estados (je_codigo, je_nombre, je_estado)
VALUES ('ANULADO', 'Anulado', 1)
ON CONFLICT (je_codigo) DO UPDATE
SET je_nombre = EXCLUDED.je_nombre, je_estado = EXCLUDED.je_estado;

-- La validación usa todos los periodos. Los duplicados históricos se conservan.
CREATE INDEX IF NOT EXISTS idx_jornadas_empleado_fecha
    ON public.jornadas_trabajo (empleado_id, (jornada_inicio::date));

COMMIT;
