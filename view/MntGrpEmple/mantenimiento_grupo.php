<!-- Modal -->
<div class="modal fade" id="modalGrupo" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="modalGrupoLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="lblTitulo"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form class="form-horizontal" method="post" id="mantenimiento_grupo">
                <div class="modal-body">

                    <input type="hidden" name="codigo_grupo" id="codigo_grupo">

                    <div class="form-group row">
                        <label for="nomb_grupo" class="col-sm-3 col-form-label">Nombre Grupo</label>
                        <div class="col-sm-9" >
                            <input type="text" class="form-control" name="nomb_grupo" id="nomb_grupo"
                                placeholder="Nombre grupo de empleado">
                        </div>
                    </div>


                    <span class="form-group row" id="selectEstadoGrupo">
                        <label for="esta_grupo" class="col-sm-3 col-form-label">Estado:</label>
                        <div class="col-sm-9" style="display: inline-block; ">
                            <select class="form-control select2bs4" name="esta_grupo" id="esta_grupo" required>
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