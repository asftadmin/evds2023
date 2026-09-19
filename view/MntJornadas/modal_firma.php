<!-- Modal de firma del reporte consolidado -->
<div class="modal fade" id="modalFirmaReporte" data-backdrop="static" tabindex="-1" role="dialog"
    aria-labelledby="modalFirmaReporteLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="modalFirmaReporteLabel">
                    <i class="fas fa-signature mr-1"></i>
                    Firmar reporte de horas
                </h5>

                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div class="alert alert-info py-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    Al firmar confirma que revisó las jornadas incluidas
                    en este reporte y que corresponden al tiempo laborado
                    registrado.
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <small class="text-muted d-block">
                            Desde
                        </small>
                        <strong id="firma-reporte-desde">-</strong>
                    </div>

                    <div class="col-md-6">
                        <small class="text-muted d-block">
                            Hasta
                        </small>
                        <strong id="firma-reporte-hasta">-</strong>
                    </div>
                </div>

                <div class="text-center">
                    <canvas id="canvasFirmaReporte" width="400" height="200" style="
                            border: 1px solid #ced4da;
                            border-radius: 6px;
                            max-width: 100%;
                            background: #fff;
                            touch-action: none;
                        "></canvas>
                </div>

                <div class="text-center mt-2">
                    <button type="button" class="btn btn-secondary btn-sm" id="btn-limpiar-firma-reporte">
                        <i class="fas fa-eraser mr-1"></i>
                        Limpiar firma
                    </button>
                </div>

                <div id="mensaje-firma-reporte" class="small text-muted text-center mt-2">
                    Firme dentro del recuadro.
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    Cancelar
                </button>

                <button type="button" class="btn btn-success" id="btn-confirmar-firma-reporte" disabled>
                    <i class="fas fa-check mr-1"></i>
                    Confirmar firma
                </button>
            </div>

        </div>
    </div>
</div>