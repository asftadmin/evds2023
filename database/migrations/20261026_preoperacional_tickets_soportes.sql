-- Ejecutar en la base de preoperacional, propietaria de tickets_sistemas.
BEGIN;
CREATE TABLE IF NOT EXISTS public.tickets_sistemas_soportes (
    soporte_id BIGSERIAL PRIMARY KEY,
    ticket_id BIGINT REFERENCES public.tickets_sistemas(ticket_id) ON DELETE CASCADE,
    soporte_token VARCHAR(64) NOT NULL,
    empleado_documento VARCHAR(20) NOT NULL,
    soporte_nombre VARCHAR(255) NOT NULL,
    soporte_ruta VARCHAR(64) NOT NULL UNIQUE,
    soporte_mime VARCHAR(50) NOT NULL,
    soporte_tamano INTEGER NOT NULL CHECK (soporte_tamano > 0 AND soporte_tamano <= 10485760),
    soporte_fecha TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK (soporte_token ~ '^[a-f0-9]{64}$'),
    CHECK (soporte_mime IN ('image/jpeg', 'image/png', 'application/pdf'))
);
CREATE INDEX IF NOT EXISTS idx_tickets_soportes_token
    ON public.tickets_sistemas_soportes(empleado_documento, soporte_token);
CREATE INDEX IF NOT EXISTS idx_tickets_soportes_ticket
    ON public.tickets_sistemas_soportes(ticket_id);
COMMIT;
