<!-- Modal -->
<div class="modal fade" id="modalRol" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="modalRolLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="lblTitulo"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form class="form-horizontal" method="post" id="mantenimiento_rol">
                <div class="modal-body">

                    <input type="hidden" name="rol_id" id="rol_id">

                    <div class="form-group row">
                        <label for="nomb_rol" class="col-sm-2 col-form-label">Rol</label>
                        <div class="col-sm-10" >
                            <input type="text" class="form-control" name="nomb_rol" id="nomb_rol"
                                placeholder="Nombre del Rol">
                        </div>
                    </div>


                    <span class="form-group row" id="selectEstadoRol">
                        <label for="esta_rol" class="col-sm-2 col-form-label">Estado:</label>
                        <div class="col-sm-10" style="display: inline-block; ">
                            <select class="form-control select2bs4" name="esta_rol" id="esta_rol" required>
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </span>

                </div>

                <div class="modal-footer">
                    <button type="reset" class="btn btn-secondary" data-dismiss="modal">Salir</button>
                    <button type="submit" name="action" value="add" class="btn btn-info">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>