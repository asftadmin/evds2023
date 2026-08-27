<!-- ========================================================= -->
<!-- MODAL ADMINISTRAR PERMISOS                               -->
<!-- ========================================================= -->

<div class="modal fade" id="modalPermisos" data-backdrop="static" tabindex="-1" role="dialog"
    aria-labelledby="modalPermisosLabel" aria-hidden="true">


    <div class="modal-dialog modal-xl" role="document">


        <div class="modal-content">


            <!-- ================================================= -->
            <!-- HEADER                                            -->
            <!-- ================================================= -->

            <div class="modal-header bg-info">


                <div>


                    <h5 class="modal-title" id="modalPermisosLabel">

                        <i class="fas fa-user-shield mr-2"></i>

                        Administrar Permisos

                    </h5>


                    <small>

                        Seleccione los menús que estarán disponibles
                        para este rol.

                    </small>


                </div>


                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">

                    <span aria-hidden="true">
                        &times;
                    </span>

                </button>


            </div>


            <!-- ================================================= -->
            <!-- BODY                                              -->
            <!-- ================================================= -->

            <div class="modal-body">


                <input type="hidden" id="perm_rol_id">


                <!-- ================================================= -->
                <!-- INFORMACIÓN DEL ROL                               -->
                <!-- ================================================= -->

                <div class="card card-outline card-info mb-3">


                    <div class="card-body py-3">


                        <div class="row align-items-center">


                            <div class="col-md-6">


                                <small class="text-muted">

                                    Rol seleccionado

                                </small>


                                <h5 class="mb-0" id="perm_rol_nomb">

                                    -

                                </h5>


                            </div>


                            <div class="col-md-6 text-md-right mt-3 mt-md-0">


                                <span class="mr-3">

                                    <strong id="total_menus">
                                        0
                                    </strong>

                                    <small class="text-muted">
                                        menús disponibles
                                    </small>

                                </span>


                                <span>

                                    <strong id="total_acceso" class="text-success">

                                        0

                                    </strong>

                                    <small class="text-muted">
                                        con acceso
                                    </small>

                                </span>


                            </div>


                        </div>


                    </div>


                </div>


                <!-- ================================================= -->
                <!-- ACCIONES MASIVAS                                  -->
                <!-- ================================================= -->

                <div class="row mb-3">


                    <div class="col-md-12">


                        <button type="button" id="btnConcederTodos" class="btn btn-outline-info btn-sm">

                            <i class="fas fa-check-double mr-1"></i>

                            Conceder Todos

                        </button>


                        <button type="button" id="btnRevocarTodos" class="btn btn-outline-danger btn-sm ml-1">

                            <i class="fas fa-ban mr-1"></i>

                            Revocar Todos

                        </button>


                    </div>


                </div>


                <!-- ================================================= -->
                <!-- TABLA DE PERMISOS                                 -->
                <!-- ================================================= -->

                <div class="table-responsive">


                    <table id="perm_table_data" class="table table-hover table-striped table-bordered"
                        style="width:100%;">


                        <thead class="thead-light">


                            <tr>


                                <th style="width: 70px;">

                                    Código

                                </th>


                                <th>

                                    <i class="fas fa-bars mr-1"></i>

                                    Menú

                                </th>


                                <th>

                                    Identificador

                                </th>


                                <th>

                                    Ruta

                                </th>


                                <th class="text-center" style="width: 100px;">

                                    Estado

                                </th>


                                <th class="text-center" style="width: 180px;">

                                    Acceso del Rol

                                </th>


                            </tr>


                        </thead>


                        <tbody>

                        </tbody>


                    </table>


                </div>


                <!-- ================================================= -->
                <!-- INFORMACIÓN                                       -->
                <!-- ================================================= -->

                <div class="alert alert-light border mt-3 mb-0">


                    <i class="fas fa-info-circle text-info mr-1"></i>


                    Puede realizar varios cambios antes de guardar.

                    Los permisos solamente serán actualizados al presionar

                    <strong>
                        Guardar Permisos
                    </strong>.


                </div>


            </div>


            <!-- ================================================= -->
            <!-- FOOTER                                            -->
            <!-- ================================================= -->

            <div class="modal-footer">


                <button type="button" id="btnDescartarPermisos" class="btn btn-default mr-auto">

                    <i class="fas fa-undo mr-1"></i>

                    Descartar Cambios

                </button>


                <button type="button" class="btn btn-secondary" data-dismiss="modal">

                    <i class="fas fa-times mr-1"></i>

                    Salir

                </button>


                <button type="button" id="btnGuardarPermisos" class="btn btn-info">

                    <i class="fas fa-save mr-1"></i>

                    Guardar Permisos

                </button>


            </div>


        </div>


    </div>


</div>