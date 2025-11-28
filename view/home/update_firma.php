<!-- Modal -->
<div class="modal fade" id="modalFirmaEmpleado" data-backdrop="static" tabindex="-1" role="dialog"
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
                <div class="form-group text-center">
                    <label><b>Sube tu firma escaneada (PNG / JPG)</b></label>
                    <input type="file" id="firmaFile" accept="image/*" class="form-control">
                </div>

                <div class="text-center mt-3">
                    <img id="previewFirmaEmpleado" src=""
                        style="max-width: 350px; display:none; border:1px solid #ccc; padding:5px;">
                </div>
            </div>

            <div class="modal-footer">
                <button id="btnGuardarFirmaEmpleado" class="btn btn-success" disabled>Guardar Firma</button>
                <button class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>