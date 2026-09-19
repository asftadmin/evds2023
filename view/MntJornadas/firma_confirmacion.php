<div class="modal fade" id="modalFirmaConfirmacion" tabindex="-1" role="dialog"
    aria-labelledby="tituloFirmaConfirmacion" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="tituloFirmaConfirmacion">
                    <i class="fas fa-file-signature mr-2"></i>
                    Confirmación de jornadas
                </h5>

                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">

                <div class="alert alert-info py-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    He revisado las jornadas indicadas y confirmo que
                    corresponden al tiempo laborado.
                </div>

                <div class="row mb-3">

                    <div class="col-md-6 mb-2 mb-md-0">
                        <strong>Periodo:</strong>
                        <span id="firma-confirmacion-periodo">
                            -
                        </span>
                    </div>

                    <div class="col-md-6">
                        <strong>Jornadas:</strong>
                        <span id="firma-confirmacion-cantidad">
                            0
                        </span>
                    </div>

                </div>

                <div class="form-group mb-2">
                    <label>
                        Firma del empleado
                    </label>

                    <div class="contenedor-firma-confirmacion">
                        <canvas id="canvasFirmaConfirmacion" width="900" height="450"></canvas>
                    </div>

                    <small class="form-text text-muted">
                        Firme dentro del recuadro utilizando el mouse o el dedo.
                    </small>
                </div>

                <div class="text-left mt-2">
                    <button type="button" class="btn btn-outline-secondary" id="btn-limpiar-firma-confirmacion">
                        <i class="fas fa-eraser mr-1"></i>
                        Limpiar firma
                    </button>
                </div>

            </div>

            <div class="modal-footer">

                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i>
                    Cancelar
                </button>

                <button type="button" class="btn btn-success" id="btn-confirmar-firma-jornadas" disabled>
                    <i class="fas fa-check-circle mr-1"></i>
                    Confirmar jornadas
                </button>

            </div>

        </div>
    </div>
</div>

<style>
.contenedor-firma-confirmacion {
    width: 100%;
    border: 2px dashed #adb5bd;
    border-radius: 0.35rem;
    background: #fff;
    overflow: hidden;
}

#canvasFirmaConfirmacion {
    display: block;
    width: 100%;
    height: 360px;
    background: #fff;
    cursor: crosshair;
    touch-action: none;
}

@media (max-width: 767.98px) {
    #modalFirmaConfirmacion .modal-dialog {
        margin: 0.5rem;
        max-width: calc(100% - 1rem);
    }

    #modalFirmaConfirmacion .modal-content {
        min-height: calc(100vh - 1rem);
    }

    #canvasFirmaConfirmacion {
        height: 320px;
    }

    #btn-limpiar-firma-confirmacion,
    #btn-confirmar-firma-jornadas,
    #modalFirmaConfirmacion .btn-secondary {
        width: 100%;
        margin-bottom: 0.5rem;
    }

    #modalFirmaConfirmacion .modal-footer {
        display: block;
    }
}
</style>