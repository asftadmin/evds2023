<link rel="stylesheet" href="../../public/css/style.css">
<!-- Modal -->
<div class="modal fade" id="modalEmpleado" data-backdrop="static" tabindex="-1" role="dialog"
    aria-labelledby="modalEmpleadoLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="lblTitulo"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form class="form-horizontal" method="post" id="mantenimiento_empleado">
                <div class="modal-body">

                    <input type="hidden" name="codigo_empleado" id="codigo_empleado">

                    <span class="form-group row" id="selectTipoDocumento">
                        <label for="tipo_documento_empl" class="col-sm-3 col-form-label">Tipo Documento:</label>
                        <div class="col-sm-9" style="display: inline-block; ">
                            <select class="form-control select2" name="tipo_documento_empl" id="tipo_documento_empl"
                                required>

                            </select>
                        </div>
                    </span>

                    <div class="form-group row">
                        <label for="numero_documento" class="col-sm-3 col-form-label">Número Documento: </label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="numero_documento" id="numero_documento"
                                placeholder="Numero de identificación">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="nombre_empleado" class="col-sm-3 col-form-label">Nombre Completo: </label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="nombre_empleado" id="nombre_empleado"
                                placeholder="Nombre completo del Empleado">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="telefono_empleado" class="col-sm-3 col-form-label">Telefono: </label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="telefono_empleado" id="telefono_empleado"
                                placeholder="Telefono y/o Celular">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="direccion_empleado" class="col-sm-3 col-form-label">Dirección: </label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="direccion_empleado" id="direccion_empleado"
                                placeholder="Dirección de residencia">
                        </div>
                    </div>

                    <span class="form-group row" id="selectCargEmpleado">
                        <label for="cargo_empleado" class="col-sm-3 col-form-label">Cargo Empleado:</label>
                        <div class="col-sm-9" style="display: inline-block; ">
                            <select class="form-control select2" style="width: 100%;" name="cargo_empleado"
                                id="cargo_empleado" required>

                            </select>
                        </div>
                    </span>

                    <div class="form-group row">
                        <label for="fecha_ingreso" class="col-sm-3 col-form-label">Fecha Ingreso: </label>
                        <div class="input-group date col-sm-9" id="reservationdate" data-target-input="nearest">
                            <input type="text" class="form-control datetimepicker-input" data-target="#reservationdate"
                                name="fecha_ingreso" id="fecha_ingreso" />
                            <div class="input-group-append" data-target="#reservationdate" data-toggle="datetimepicker">
                                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                            </div>
                        </div>

                    </div>



                    <span class="form-group row" id="selectEstadoEmpleado">
                        <label for="esta_cargo" class="col-sm-3 col-form-label">Estado:</label>
                        <div class="col-sm-9" style="display: inline-block; ">
                            <select class="form-control select2bs4" name="esta_cargo" id="esta_cargo" required>
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