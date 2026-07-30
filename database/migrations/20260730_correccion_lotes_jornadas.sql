BEGIN;

ALTER TABLE public.jornada_lotes_reporte
    ADD COLUMN IF NOT EXISTS jlot_tipo character varying(20)
        NOT NULL DEFAULT 'NORMAL',
    ADD COLUMN IF NOT EXISTS jlot_lote_origen_id bigint,
    ADD COLUMN IF NOT EXISTS jlot_version integer NOT NULL DEFAULT 1;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conname = 'fk_jornada_lote_reporte_origen'
          AND conrelid = 'public.jornada_lotes_reporte'::regclass
    ) THEN
        ALTER TABLE public.jornada_lotes_reporte
            ADD CONSTRAINT fk_jornada_lote_reporte_origen
            FOREIGN KEY (jlot_lote_origen_id)
            REFERENCES public.jornada_lotes_reporte(jlot_id);
    END IF;
END $$;

ALTER TABLE public.jornada_lotes_reporte
    DROP CONSTRAINT IF EXISTS jornada_lotes_reporte_jlot_estado_check;

ALTER TABLE public.jornada_lotes_reporte
    DROP CONSTRAINT IF EXISTS jornada_lotes_reporte_jlot_tipo_check;

ALTER TABLE public.jornada_lotes_reporte
    DROP CONSTRAINT IF EXISTS jornada_lotes_reporte_jlot_version_check;

ALTER TABLE public.jornada_lotes_reporte
    ADD CONSTRAINT jornada_lotes_reporte_jlot_estado_check
        CHECK (jlot_estado IN ('BORRADOR', 'CERRADO', 'REEMPLAZADO')),
    ADD CONSTRAINT jornada_lotes_reporte_jlot_tipo_check
        CHECK (jlot_tipo IN ('NORMAL', 'CORRECCION')),
    ADD CONSTRAINT jornada_lotes_reporte_jlot_version_check
        CHECK (jlot_version > 0);

CREATE INDEX IF NOT EXISTS idx_jornada_lotes_reporte_origen
    ON public.jornada_lotes_reporte (jlot_lote_origen_id);

COMMIT;
