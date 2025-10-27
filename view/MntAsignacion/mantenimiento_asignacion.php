<link rel="stylesheet" href="../../public/css/style.css">

<!-- Modal -->
<div class="modal fade" id="modalAsignacion" data-backdrop="static" tabindex="-1" role="dialog"
    aria-labelledby="modalAsignacionLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="lblTitulo"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form class="form-horizontal" method="post" id="mantenimiento_asignacion">
                <div class="modal-body">

                    <input type="hidden" name="codigo_asignacion" id="codigo_asignacion">

                    <span class="form-group row" id="selectEvaluador">
                        <label for="nomb_evaluador" class="col-sm-4 col-form-label">Nombre Evaluador:</label>
                        <div class="col-sm-8" style="display: inline-block; ">
                            <select class="form-control select2" name="nomb_evaluador" id="nomb_evaluador" required>

                            </select>
                        </div>
                    </span>

                    <span class="form-group row" id="selectEmpleado">
                        <label for="nomb_empleado" class="col-sm-4 col-form-label">Nombre Evaluado:</label>
                        <div class="col-sm-8" style="display: inline-block; ">
                            <select class="form-control select2" name="nomb_empleado[]" multiple="multiple"
                                id="nomb_empleado" required>

                            </select>
                        </div>
                    </span>

                    <span class="form-group row" id="selectMes">
                        <label for="mes_evalua" class="col-sm-4 col-form-label">Mes:</label>
                        <div class="col-sm-8" style="display: inline-block; ">
                            <select class="form-control select2" name="mes_evalua" id="mes_evalua" required>

                            </select>
                        </div>
                    </span>

                    <span class="form-group row" id="selectAño">
                        <label for="año_evalua" class="col-sm-4 col-form-label">Año:</label>
                        <div class="col-sm-8" style="display: inline-block; ">
                            <select class="form-control select2" name="año_evalua" id="año_evalua" required>
                                <option disabled selected required>--Selecciona Año--
                                </option>
                                <option value="2023">2023</option>
                                <option value="2024">2024</option>
								<option value="2025">2025</option>
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