BEGIN;

INSERT INTO public.jornada_estados (je_codigo, je_nombre, je_estado)
VALUES ('LIQUIDADO', 'Liquidada', 1)
ON CONFLICT (je_codigo) DO NOTHING;

COMMIT;
