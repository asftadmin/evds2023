<?php // Consulta personal de BioTime; la identidad se obtiene en el servidor. ?>
<div class="card card-outline card-info p-2" id="card-marcaciones-empleado">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-fingerprint mr-1"></i>Mis marcaciones BioTime</h3>
    </div>
    <div class="card-body">
        <p id="marcaciones-empleado" class="text-muted" aria-live="polite">Consultando sus marcaciones...</p>
        <form id="form-marcaciones-empleado" class="row align-items-end" autocomplete="off">
            <div class="col-md-5 form-group">
                <label for="marcaciones-periodo">Periodo</label>
                <input type="text" id="marcaciones-periodo" class="form-control" readonly>
            </div>
            <div class="col-md-4 form-group">
                <label for="marcaciones-vista">Forma de visualizar</label>
                <select id="marcaciones-vista" class="form-control select2" style="width:100%">
                    <option value="resumen">Resumen diario</option>
                    <option value="detalle">Todas las entradas y salidas</option>
                </select>
            </div>
            <div class="col-md-3 form-group">
                <button id="btn-consultar-marcaciones" type="submit" class="btn btn-info btn-block">
                    <i class="fas fa-search mr-1"></i>Consultar
                </button>
            </div>
        </form>
        <p class="small text-muted">Resumen: primera y última marcación del día y tiempo bruto sin descuentos. Todas las
            entradas y salidas: una fila por cada marcación, ordenada de la más reciente a la más antigua.</p>
        <div id="marcaciones-estado" class="alert alert-light border py-2" role="status" aria-live="polite">Cargando...
        </div>
        <div id="marcaciones-resumen">
            <table id="tabla-marcaciones-resumen" class="table table-bordered table-striped table-sm"
                style="width:100%">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Hora de entrada</th>
                        <th>Hora de salida</th>
                        <th>Tiempo bruto</th>
                        <th>Marcaciones</th>
                        <th>Observaciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div id="marcaciones-detalle" style="display:none;">
            <table id="tabla-marcaciones-detalle" class="table table-bordered table-striped table-sm"
                style="width:100%">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Empleado</th>
                        <th>Cargo</th>
                        <th>Grupo</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>