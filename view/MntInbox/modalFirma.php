<!-- Modal -->
<div class="modal fade" id="modalFirmaJefe" data-backdrop="static" tabindex="-1" role="dialog"
    aria-labelledby="modalFirmaLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalFirmaLabel">Registrar Firma Digital</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <canvas id="canvasFirma" width="400" height="200"
                        style="border:1px solid #000; border-radius:8px; touch-action:none;"></canvas><br>
                    <button id="btnLimpiarFirma" class="btn btn-secondary mt-2">Limpiar</button>
                </div>
                <div id="previewFirma" class="mt-3 text-center"></div>
            </div>

            <div class="modal-footer">
                <button id="btnGuardarFirma" class="btn btn-info">Guardar Permiso</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>