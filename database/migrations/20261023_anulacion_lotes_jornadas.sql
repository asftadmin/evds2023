BEGIN;
ALTER TABLE public.jornada_lotes_reporte
    DROP CONSTRAINT IF EXISTS jornada_lotes_reporte_jlot_estado_check;
ALTER TABLE public.jornada_lotes_reporte
    ADD CONSTRAINT jornada_lotes_reporte_jlot_estado_check
    CHECK (jlot_estado IN ('BORRADOR', 'CERRADO', 'REEMPLAZADO', 'ANULADO'));
COMMIT;
