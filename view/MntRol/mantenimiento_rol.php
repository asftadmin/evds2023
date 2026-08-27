<!-- ========================================================= -->
<!-- MODAL CREAR / EDITAR ROL                                 -->
<!-- ========================================================= -->

<div class="modal fade" id="modalRol" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="modalRolLabel"
    aria-hidden="true">


    <div class="modal-dialog" role="document">


        <div class="modal-content">


            <!-- ================================================= -->
            <!-- HEADER                                            -->
            <!-- ================================================= -->

            <div class="modal-header bg-info">


                <h5 class="modal-title" id="lblTitulo">

                    <i class="fas fa-user-tag mr-2"></i>

                    Rol

                </h5>


                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">

                    <span aria-hidden="true">
                        &times;
                    </span>

                </button>


            </div>


            <!-- ================================================= -->
            <!-- FORMULARIO                                        -->
            <!-- ================================================= -->

            <form id="mantenimiento_rol" autocomplete="off">


                <div class="modal-body">


                    <input type="hidden" name="rol_id" id="rol_id">


                    <!-- NOMBRE DEL ROL -->
                    <div class="form-group">


                        <label for="nomb_rol">

                            <i class="fas fa-user-tag mr-1"></i>

                            Nombre del Rol

                            <span class="text-danger">
                                *
                            </span>

                        </label>


                        <input type="text" class="form-control" name="nomb_rol" id="nomb_rol" maxlength="100"
                            placeholder="Ingrese el nombre del rol" required>


                        <small class="form-text text-muted">

                            Nombre con el que se identificará el rol
                            dentro del sistema.

                        </small>


                    </div>


                    <!-- ESTADO -->
                    <div class="form-group" id="contenedor_estado_rol" style="display:none;">


                        <label for="esta_rol">

                            <i class="fas fa-toggle-on mr-1"></i>

                            Estado

                        </label>


                        <select class="form-control select2bs4" name="esta_rol" id="esta_rol" style="width:100%;">


                            <option value="1">
                                Activo
                            </option>


                            <option value="0">
                                Inactivo
                            </option>


                        </select>


                        <small class="form-text text-muted">

                            Los roles inactivos se conservan en el sistema
                            pero no estarán disponibles para nuevas asignaciones.

                        </small>


                    </div>


                </div>


                <!-- ================================================= -->
                <!-- FOOTER                                            -->
                <!-- ================================================= -->

                <div class="modal-footer">


                    <button type="button" class="btn btn-default" data-dismiss="modal">

                        <i class="fas fa-times mr-1"></i>

                        Cancelar

                    </button>


                    <button type="submit" id="btnGuardarRol" class="btn btn-info">

                        <i class="fas fa-save mr-1"></i>

                        Guardar

                    </button>


                </div>


            </form>


        </div>


    </div>


</div>