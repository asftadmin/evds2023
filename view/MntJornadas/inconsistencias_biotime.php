<?php
// Fragmento interno: no puede consultarse directamente.
if (!isset($icEmpleadoAcceso) || $icEmpleadoAcceso['rol_nomb'] !== 'Contabilidad') {
    http_response_code(403);
    exit;
}
?>


<div class="card-header">
    <h3 class="card-title">Cruce del reporte firmado con BioTime</h3>
</div>
<div class="card-body border-bottom px-4 py-3">
    <input type="hidden" id="icb-csrf"
        value="<?php echo htmlspecialchars($_SESSION['csrf_jornadas'], ENT_QUOTES, 'UTF-8'); ?>">
    <p class="text-muted">El cruce es informativo. Contabilidad puede confirmar un ajuste de entrada o salida en el
        detalle.
        Cada ajuste conserva la copia firmada y queda registrado en auditoría.
        La revisión es operativa de Contabilidad y no condiciona el acceso ni las funciones de Liquidaciones.</p>

    <div class="row align-items-end icb-filtros">

        <!-- Período de las jornadas firmadas -->
        <div class="col-xl-3 col-lg-6 col-md-6 form-group">
            <label for="icb-periodo">
                Período de las jornadas firmadas
            </label>
            <input id="icb-periodo" type="text" class="form-control bg-white icb-periodo" autocomplete="off" readonly>
        </div>

        <!-- Empleado -->
        <div class="col-xl-3 col-lg-6 col-md-6 form-group">
            <label for="icb-empleado">Empleado</label>
            <select id="icb-empleado" class="form-control">
                <option value="">
                    Todos los empleados con reporte firmado
                </option>
            </select>
        </div>

        <!-- Tolerancia -->
        <div class="col-xl-2 col-lg-4 col-md-4 form-group">
            <label for="icb-tolerancia">Tolerancia (min)</label>
            <input id="icb-tolerancia" type="number" class="form-control" min="0" max="60" value="0">
        </div>

        <!-- Consultar -->
        <div class="col-xl-2 col-lg-4 col-md-4 form-group">
            <button id="icb-consultar" type="button" class="btn btn-info btn-block">
                <i class="fas fa-search mr-1"></i>
                Cruzar con BioTime
            </button>
        </div>

        <!-- Actualizar marcaciones -->
        <div class="col-xl-2 col-lg-4 col-md-4 form-group">
            <button id="icb-actualizar-biotime" type="button" class="btn btn-outline-danger btn-block">
                <i class="fas fa-sync-alt mr-1"></i>
                Actualizar BioTime
            </button>
        </div>

    </div>



    <p id="icb-resumen" role="status" aria-live="polite">Seleccione el periodo y consulte. La tolerancia solo afecta
        esta comparación.</p>
    <div class="table-responsive">
        <table id="icb-tabla" class="table table-bordered table-striped" style="width:100%">
            <thead>
                <tr>
                    <th>Empleado</th>
                    <th>Fecha entrada firmada</th>
                    <th>Hora entrada firmada</th>
                    <th>Fecha salida firmada</th>
                    <th>Hora salida firmada</th>
                    <th>Ubicación</th>
                    <th>Fecha entrada BioTime</th>
                    <th>Hora entrada BioTime</th>
                    <th>Fecha salida BioTime</th>
                    <th>Hora salida BioTime</th>
                    <th>Diferencia entrada</th>
                    <th>Diferencia salida</th>
                    <th>Resultado</th>
                    <th>Detalle</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>