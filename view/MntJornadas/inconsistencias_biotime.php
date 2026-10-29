<?php
// Fragmento interno: no puede consultarse directamente.
if (!isset($icEmpleadoAcceso) || $icEmpleadoAcceso['rol_nomb'] !== 'Contabilidad') {
    http_response_code(403);
    exit;
}
?>
<div class="card-header"><h3 class="card-title">Cruce del reporte firmado con BioTime</h3></div>
<div class="card-body border-bottom">
    <input type="hidden" id="icb-csrf" value="<?php echo htmlspecialchars($_SESSION['csrf_jornadas'], ENT_QUOTES, 'UTF-8'); ?>">
    <p class="text-muted">El cruce es informativo. Contabilidad puede confirmar un ajuste de entrada o salida en el detalle.
        Cada ajuste conserva la copia firmada y queda registrado en auditoría.
        La revisión es operativa de Contabilidad y no condiciona el acceso ni las funciones de Liquidaciones.</p>
    <div class="row">
        <div class="col-md-4 form-group">
            <label for="icb-periodo">Periodo de las jornadas firmadas</label>
            <input id="icb-periodo" class="form-control" autocomplete="off" readonly>
        </div>
        <div class="col-md-4 form-group">
            <label for="icb-empleado">Empleado</label>
            <select id="icb-empleado" class="form-control" style="width:100%">
                <option value="">Todos los empleados con reporte firmado</option>
            </select>
        </div>
        <div class="col-md-2 form-group">
            <label for="icb-tolerancia">Tolerancia (min)</label>
            <input id="icb-tolerancia" type="number" class="form-control" min="0" max="60" value="0">
        </div>
        <div class="col-md-2 form-group d-flex align-items-end">
            <button id="icb-consultar" type="button" class="btn btn-info">Cruzar con BioTime</button>
        </div>
    </div>
    <p id="icb-resumen" role="status" aria-live="polite">Seleccione el periodo y consulte. La tolerancia solo afecta esta comparación.</p>
    <div class="table-responsive">
        <table id="icb-tabla" class="table table-bordered table-striped" style="width:100%">
            <thead><tr>
                <th>Empleado</th><th>Fecha entrada firmada</th><th>Hora entrada firmada</th>
                <th>Fecha salida firmada</th><th>Hora salida firmada</th><th>Ubicación</th>
                <th>Fecha entrada BioTime</th><th>Hora entrada BioTime</th>
                <th>Fecha salida BioTime</th><th>Hora salida BioTime</th>
                <th>Diferencia entrada</th><th>Diferencia salida</th><th>Resultado</th><th>Detalle</th>
            </tr></thead><tbody></tbody>
        </table>
    </div>
    <small class="text-muted">Diferencias = marcación menos horario firmado. Esta consulta incluye únicamente jornadas de reportes firmados.
        Se revisan todas las marcaciones de los días abarcados por las jornadas, incluidos los turnos nocturnos.
        Si hay pares reales para cada jornada, se respetan sus marcaciones. Las jornadas donde la salida coincide
        exactamente con la entrada siguiente, a cualquier hora, pueden cruzarse como un bloque continuo,
        incluyendo los extremos fuera del periodo seleccionado.
        Cuando el par valida la continuidad, la diferencia del corte muestra 00:00; ese corte no representa
        una marcación y no permite ajustes BioTime.
        Si una jornada cierra a las 00:00 del final del periodo y la salida real ocurre después,
        se muestra un corte de periodo con diferencia 00:00. La salida real se conserva en el detalle
        como soporte de la continuación del periodo siguiente.
        Una jornada con entrada y salida inequívocas puede mostrar sus diferencias y conservar
        marcaciones intermedias identificadas como adicionales pendientes de revisión.
        Los bloques continuos también pueden compararse si sus extremos son únicos y no compiten
        con otras jornadas; las marcas sin asignación permanecen visibles para revisión.
        Una marca real en un corte respalda el límite compartido sin contarse como dos eventos.
        Una marca aislada dentro de una jornada sin resolver se muestra como pendiente;
        su posición por cercanía es provisional y requiere confirmación antes de cualquier ajuste.
        “Sin marcaciones” requiere revisar ubicación, permisos y soportes; no implica ausencia.</small>
</div>
