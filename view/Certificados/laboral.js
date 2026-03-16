let tabla;
var modoEdicion = false;

function init() {

    $('.pane').hide();
    $('#pane-erp').show();
    initSelect2Dynamic();
    initDatePicker('#txt_fecha_exp');
    initDatePicker('#txt_fecha_ingreso');
    initDatePicker('#txt_fecha_nacimiento');
    $('#btn_editar_basicos').prop('disabled', true);
    // Deshabilitar btn_toggle_form nuevamente
    $('#btn_toggle_form').prop('disabled', true);
    // Deshabilitar btn_toggle_form
    $('#btn_toggle_form_aux').prop('disabled', true);


}

$(document).on("click", "#buscarEmpl", function () {

    $('#lblTituloUpdate').html('Buscar Registro');
    /* document.getElementById('selectEstadoGrupo').style.display = 'none'; */
    /* $('#mantenimiento_grupo')[0].reset(); */
    $('#modalActuEmpleado').modal('show');

});

$('#modalActuEmpleado').on('shown.bs.modal', function () {
    let modalContent = document.querySelector('.modal-content');
    let dataTable = $('#data_empleado');

    // Esperar un pequeño tiempo para asegurar que el DataTable esté completamente renderizado
    setTimeout(function () {
        let dataTableWidth = dataTable.outerWidth();
        if (dataTableWidth > 0) {
            modalContent.style.width = dataTableWidth + 100 + 'px'; // Ajusta un poco más ancho que el DataTable
        } else {
            modalContent.style.width = 'auto'; // Si el ancho es 0, deja que el ancho se ajuste automáticamente
        }
    }, 200);
});

$(document).ready(function () {

    tabla = $('#data_empleado').dataTable({
        "aProcessing": true,
        "aServerSide": true,
        "searching": true,
        lengthChange: false,
        colReorder: true,
        buttons: [
            'copyHtml5',
            'excelHtml5',
            'csvHtml5',
            'pdfHtml5'
        ],
        "ajax": {
            url: '../../controller/empleado.php?op=listarEmpleado',
            type: "post",
            dataType: "json",
            data: tabla,
            error: function (e) {
                console.log(e.responseText);
            }
        },


        "bDestroy": true,
        "responsive": true,
        "bInfo": true,
        "iDisplayLength": 5,
        "autoWidth": false,
        "language": {
            "sProcessing": "Procesando...",
            "sLengthMenu": "Mostrar _MENU_ registros",
            "sZeroRecords": "No se encontraron resultados",
            "sEmptyTable": "Ningún dato disponible en esta tabla",
            "sInfo": "Mostrando un total de _TOTAL_ registros",
            "sInfoEmpty": "Mostrando un total de 0 registros",
            "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
            "sInfoPostFix": "",
            "sSearch": "Buscar:",
            "sUrl": "",
            "sInfoThousands": ",",
            "sLoadingRecords": "Cargando...",
            "oPaginate": {
                "sFirst": "Primero",
                "sLast": "Último",
                "sNext": "Siguiente",
                "sPrevious": "Anterior"
            },
            "oAria": {
                "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
                "sSortDescending": ": Activar para ordenar la columna de manera descendente"
            }
        }
    }).DataTable();
});

// ── Navegación tabs ──────────────────────────────
$(document).on('click', '#mainTabs .nav-link', function (e) {
    e.preventDefault();

    const pane = $(this).data('pane');

    // Guardar tab activo en localStorage
    localStorage.setItem('tabActivoLaboral', pane);

    // Actualizar tab activo
    $('#mainTabs .nav-link').removeClass('active');
    $(this).addClass('active');

    // Ocultar todos y mostrar el seleccionado
    $('.pane').hide();
    $('#pane-' + pane).show();
});

// ── Restaurar tab activo al cargar ──────────────
$(document).ready(function () {

    const tabGuardado = localStorage.getItem('tabActivoLaboral') || 'erp';

    // Activar el tab correspondiente
    $('#mainTabs .nav-link').removeClass('active');
    $(`#mainTabs .nav-link[data-pane="${tabGuardado}"]`).addClass('active');

    // Mostrar el pane correspondiente
    $('.pane').hide();
    $('#pane-' + tabGuardado).show();
});

function mostrarDescripcionEstado(valor) {
    const estados = {
        '1': { texto: 'Activo', badge: 'badge-success', icono: 'fa-check' },
        '0': { texto: 'Inactivo', badge: 'badge-danger', icono: 'fa-times' }
    };

    const estado = estados[valor] || { texto: 'Desconocido', badge: 'badge-secondary', icono: 'fa-question' };

    // Actualiza el texto
    $('#estado_descripcion').text(estado.texto);

    // Actualiza el ícono
    $('#icon_estado_empleado')
        .removeClass('fa-check fa-times fa-question')
        .addClass(estado.icono);

    // Actualiza el color del badge
    $('#span_estado_empleado')
        .removeClass('badge-success badge-danger badge-secondary')
        .addClass(estado.badge);
}

$.post("../../controller/Cargo.php?op=comboCargo", function (data, status) {
    var $tipoCargo = $('#select_cargo_empleado');
    $tipoCargo.html(data);
});

$.post("../../controller/tipodoc.php?op=comboTipoDocumento", function (data, status) {
    var $tipoDocumentoEmpl = $('#txt_tipo_documento_empl');
    $tipoDocumentoEmpl.html(data);
});

$.post("../../controller/empleado.php?op=comboGenero", function (data, status) {
    console.log(data);
    var genero = $('#txt_genero');
    genero.html(data);
});

$.post("../../controller/empleado.php?op=comboCivil", function (data, status) {
    var $estado_civil = $('#txt_civil');
    $estado_civil.html(data);
});

$.post("../../controller/empleado.php?op=comboRH", function (data, status) {
    var $grupo_sanguineo = $('#txt_rh');
    $grupo_sanguineo.html(data);
});

$.post("../../controller/empleado.php?op=comboEstrato", function (data, status) {
    var $estrato = $('#txt_estrato');
    $estrato.html(data);
});

$.post("../../controller/empleado.php?op=comboLugarExp", function (data, status) {
    var $lugar_exped = $('#txt_lugar_exp');
    $lugar_exped.html(data);
});

$.post("../../controller/empleado.php?op=comboNivelEduc", function (data, status) {
    var $nivel_educ = $('#txt_nivel');
    $nivel_educ.html(data);
});

$.post("../../controller/empleado.php?op=comboTipoContrato", function (data, status) {
    var tipo_contrato = $('#select_tipo_contrato');
    tipo_contrato.html(data);
});

$.post("../../controller/empleado.php?op=comboDependencia", function (data, status) {
    var dependencia = $('#select_dependencia');
    dependencia.html(data);
});

function editar(codigo_empleado) {
    $('#btn_editar_basicos').prop('disabled', false);
    $.post("../../controller/empleado.php?op=mostrar",
        { codigo_empleado: codigo_empleado },
        function (data) {
            try {
                data = JSON.parse(data);
                console.log(data);

                // ── Inputs texto ──────────────────────────────
                $('#txt_buscar_empl').val(data.txt_numero_documento + ' - ' + data.txt_nombre_empleado);
                $('#txt_codigo_empleado').val(data.txt_codigo_empleado);
                $('#txt_numero_documento').val(data.txt_numero_documento);
                $('#txt_nombre_empleado').val(data.txt_nombre_empleado);
                $('#txt_telefono_empleado').val(data.txt_telefono_empleado);
                $('#txt_direccion_empleado').val(data.txt_direccion_empleado);
                $('#txt_correo').val(data.txt_correo);
                $('#txt_fecha_nacimiento').val(data.txt_fecha_nacimiento);
                $('#txt_fecha_exp').val(data.txt_fecha_exp);
                $('#txt_fecha_retiro').val(data.txt_fecha_retiro);
                $('#txt_fecha_retiro_egreso').val(data.txt_fecha_retiro);
                $('#txt_fecha_ingreso').val(data.txt_fecha_ingreso);
                $('#txt_profesion').val(data.txt_profesion);
                $('#txt_anio_grado').val(data.txt_anio_grado);
                $('#txt_salario').val(data.txt_salario);

                // ── Selects — habilitar → valor → trigger → deshabilitar ──
                $('#txt_tipo_documento_empl').prop('disabled', false)
                    .val(data.txt_tipo_documento_empl).trigger('change')
                    .prop('disabled', true);

                $('#txt_lugar_exp').prop('disabled', false)
                    .val(data.txt_lugar_exp).trigger('change')
                    .prop('disabled', true);

                $('#txt_genero').prop('disabled', false)
                    .val(data.txt_genero).trigger('change')
                    .prop('disabled', true);

                $('#txt_civil').prop('disabled', false)
                    .val(data.txt_civil).trigger('change')
                    .prop('disabled', true);

                $('#txt_rh').prop('disabled', false)
                    .val(data.txt_rh).trigger('change')
                    .prop('disabled', true);

                $('#txt_estrato').prop('disabled', false)
                    .val(data.txt_estrato).trigger('change')
                    .prop('disabled', true);

                $('#txt_nivel').prop('disabled', false)
                    .val(data.select_nivel_educativo).trigger('change')
                    .prop('disabled', true);

                $('#select_cargo_empleado').prop('disabled', false)
                    .val(data.select_cargo_empleado).trigger('change')
                    .prop('disabled', true);

                $('#select_tipo_contrato').prop('disabled', false)
                    .val(data.select_tipo_contrato).trigger('change')
                    .prop('disabled', true);

                $('#select_dependencia').prop('disabled', false)
                    .val(data.select_dependencia).trigger('change')
                    .prop('disabled', true);

                // ── Estado badge ──────────────────────────────
                mostrarDescripcionEstado(data.select_esta_empl);

                $('#modalActuEmpleado').modal('hide');

                // ── Preview certificado ───────────────────────
                llenarPreview(data);
                // ── Mostrar tab egreso si tiene fecha de retiro ──
                if (data.txt_fecha_retiro) {
                    $('#tab_egreso').show();
                } else {
                    $('#tab_egreso').hide();
                    // Si estaba en el pane egreso, volver a datos básicos
                    if ($('#pane-egreso').is(':visible')) {
                        $('#mainTabs .nav-link[data-pane="erp"]').trigger('click');
                    }
                }

            } catch (error) {
                console.error('Error parsing JSON:', error);
                console.log('Raw response:', data);
                alert('Error al cargar los datos del empleado');
            }
        }
    );
}

// ── Campos y selects del tab Datos Básicos ──
const camposBasicos = [
    '#txt_numero_documento',
    '#txt_fecha_exp',
    '#txt_nombre_empleado',
    '#txt_telefono_empleado',
    '#txt_direccion_empleado',
    '#txt_correo',
    '#txt_fecha_ingreso',
    'txt_salario',
    '#txt_dependencia',
    '#txt_profesion',
    '#txt_anio_grado',
    '#txt_salario',
    '#txt_fecha_nacimiento',
    '#txt_observaciones',
    '#txt_fecha_retiro'
];

const selectsBasicos = [
    '#txt_tipo_documento_empl',
    '#txt_lugar_exp',
    '#txt_genero',
    '#txt_civil',
    '#txt_rh',
    '#txt_estrato',
    '#select_cargo_empleado',
    '#txt_nivel',
    '#select_dependencia',
    '#select_tipo_contrato',
    '#select_esta_empl'
];

// ── Habilitar edición ──
$('#btn_editar_basicos').on('click', function () {

    // Habilitar btn_toggle_form
    $('#btn_toggle_form').prop('disabled', false);

    // Habilitar btn_toggle_form
    $('#btn_toggle_form_aux').prop('disabled', false);

    // Habilitar inputs
    camposBasicos.forEach(function (id) {
        $(id).prop('readonly', false).addClass('input-editando');
    });

    // Habilitar selects (incluido Select2)
    selectsBasicos.forEach(function (id) {
        $(id).prop('disabled', false).trigger('change');
        $(id).next('.select2-container').find('.select2-selection')
            .addClass('select2-editando');
    });

    // Reinicializar pickers después de habilitar
    initDatePicker('#txt_fecha_exp');
    initDatePicker('#txt_fecha_ingreso');
    initDatePicker('#txt_fecha_nacimiento');
    initDatePicker('#txt_fecha_retiro');

    //Inicializar tabla bonificaciones
    modoEdicion = true;
    listarBonificaciones();

    // Intercambiar botones
    $('#btn_editar_basicos').hide();
    $('#btn_cancelar_basicos').show();
    $('#btn_guardar_basicos').show();
});

// ── Cancelar: volver al estado readonly ──
$('#btn_cancelar_basicos').on('click', function () {

    // Deshabilitar btn_toggle_form nuevamente
    $('#btn_toggle_form').prop('disabled', true);

    // Deshabilitar btn_toggle_form
    $('#btn_toggle_form_aux').prop('disabled', true);

    // Recargar los datos originales del empleado
    const codigo = $('#txt_codigo_empleado').val();
    if (codigo) editar(codigo); // reutiliza tu función AJAX existente

    // Bloquear inputs
    camposBasicos.forEach(function (id) {
        $(id).prop('readonly', true).removeClass('input-editando');
    });

    // Bloquear selects
    selectsBasicos.forEach(function (id) {
        $(id).prop('disabled', true).trigger('change');
        $(id).next('.select2-container').find('.select2-selection')
            .removeClass('select2-editando');
    });

    modoEdicion = false;
    listarBonificaciones(); // recargar tabla sin botones

    // Intercambiar botones
    $('#btn_guardar_basicos').hide();
    $('#btn_cancelar_basicos').hide();
    $('#btn_editar_basicos').show();
});

$('#btn_toggle_form').on('click', function () {
    // tu lógica aquí
    $('#formBonif').toggle(); // o lo que hacía toggleForm()
    initDatePicker('#txt_fecha_inicio');
});

// ── Cancelar formulario Bonificaciones ──
$('#btn_cancelar_bonif').on('click', function () {
    $('#formBonif').hide();
    // limpiar campos del formulario
    $('#formBonif input').val('');
    $('#formBonif select').val(null).trigger('change');
    $('#formBonif textarea').val('');
});


$('#btn_toggle_form_aux').on('click', function () {
    // tu lógica aquí
    $('#formAuxilio').toggle(); // o lo que hacía toggleForm()
    initDatePicker('#txt_fecha_ini_aux');
});

$('#btn_cancelar_aux').on('click', function () {
    $('#formAuxilio').hide();
    // limpiar campos del formulario
    $('#formAuxilio input').val('');
    $('#formAuxilio select').val(null).trigger('change');
    //$('#formAuxilio textarea').val('');
});

// ── Formato pesos colombianos ──
$(document).on('input', '#txt_valor', function () {
    let valor = $(this).val();

    // Quitar todo lo que no sea número o punto
    valor = valor.replace(/[^0-9.]/g, '');

    // Separar entero y decimal
    let partes = valor.split('.');
    let entero = partes[0];
    let decimal = partes[1] !== undefined ? '.' + partes[1] : '';

    // Aplicar comas a la parte entera
    entero = entero.replace(/\B(?=(\d{3})+(?!\d))/g, ',');

    $(this).val(entero + decimal);
});

$(document).on('input', '#txt_valor_aux', function () {
    let valor = $(this).val();

    // Quitar todo lo que no sea número o punto
    valor = valor.replace(/[^0-9.]/g, '');

    // Separar entero y decimal
    let partes = valor.split('.');
    let entero = partes[0];
    let decimal = partes[1] !== undefined ? '.' + partes[1] : '';

    // Aplicar comas a la parte entera
    entero = entero.replace(/\B(?=(\d{3})+(?!\d))/g, ',');

    $(this).val(entero + decimal);
});

function initDatePicker(selector) {
    // Destruir instancia previa si existe
    if ($(selector).data('daterangepicker')) {
        $(selector).data('daterangepicker').remove();
    }

    $(selector).daterangepicker({
        singleDatePicker: true,
        showDropdowns: true,
        autoApply: true,
        autoUpdateInput: false,
        locale: {
            format: 'DD/MM/YYYY',
            applyLabel: 'Aplicar',
            cancelLabel: 'Cancelar',
            daysOfWeek: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'],
            monthNames: [
                'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
            ],
            firstDay: 1
        }
    })
        .on('apply.daterangepicker', function (ev, picker) {
            $(this).val(picker.startDate.format('DD/MM/YYYY'));
        })
        .on('cancel.daterangepicker', function () {
            $(this).val('');
        });
}



function initSelect2Dynamic() {

    $(".select2bs4").each(function () {

        let $select = $(this);

        // Evitar inicialización doble
        if ($select.hasClass("select2-hidden-accessible")) {
            return;
        }

        // Detectar si estamos dentro de algún modal
        let modalPadre = $select.closest(".modal");

        $select.select2({
            theme: "bootstrap4",
            width: "100%",
            dropdownParent: modalPadre.length ? modalPadre : $(document.body)
        });

    });
}

// ── Llenar preview al cargar empleado ──────────────────
function llenarPreview(data) {

    $('#prev_institucion').text(
        'EL SUSCRITO DIRECTOR DEL DEPARTAMENTO DE GESTIÓN HUMANA Y JURÍDICA DE ' +
        'LA SOCIEDAD ASFALTART S.A.S. EN REORGANIZACIÓN IDENTIFICADA CON NIT No. ' +
        '800.164.580-6.'
    );

    // Guardar valores en data() para regenerar al cambiar switches
    $('#prev_parrafo_principal')
        .data('nombre', data.txt_nombre_empleado.toUpperCase())
        .data('cedula', data.txt_numero_documento)
        .data('lugar_exp', data.txt_lugar_exp_desc ?? '')
        .data('tipo_contrato', data.txt_tipo_contrato ?? 'término indefinido')
        .data('fecha_ingreso', data.txt_fecha_ingreso_letras ?? data.txt_fecha_ingreso)
        .data('cargo', data.txt_cargo_desc ?? '')
        .data('salario_letras', data.txt_salario_letras ?? '')
        .data('auxilio_letras', data.txt_auxilio_letras ?? '')
        .data('bonif_letras', data.txt_bonif_letras ?? '');

    // Párrafo expedición
    $('#prev_parrafo_expedicion').html(
        'Se expide a solicitud del interesado, en ' +
        '<strong>' + (data.txt_ciudad_expedicion ?? 'la ciudad') + '</strong> ' +
        'el día <strong>' + (data.txt_fecha_hoy_letras ?? '') + '</strong>.'
    );

    // Firmante
    $('#prev_firmante').text(data.txt_nombre_firmante ?? '');
    $('#prev_cargo_firmante').html(
        (data.txt_cargo_firmante ?? '') + '<br>' +
        '<strong>ASFALTART S.A.S. EN REORGANIZACIÓN.</strong><br>' +
        'NIT No. 800.164.580-6.'
    );
    $('#prev_contacto').html(
        'Para confirmar esta información comunicarse al ' +
        (data.txt_telefono_empresa ?? '') + '<br>' +
        'Correos: ' + (data.txt_correo_empresa ?? '')
    );

    // Radicado — respetar lo que el usuario ya haya escrito
    const radicadoActual = $('#txt_radicado_cert').val().trim();
    $('#prev_radicado').text(radicadoActual ? radicadoActual + '.' : '');

    // Generar párrafo con opciones activas
    generarParrafoPrincipal();
}

// ── Regenerar párrafo principal según switches ──────────
function generarParrafoPrincipal() {

    const $p = $('#prev_parrafo_principal');

    const nombre = $p.data('nombre') ?? '';
    const cedula = $p.data('cedula') ?? '';
    const lugarExp = $p.data('lugar_exp') ?? '';
    const tipoContrato = $p.data('tipo_contrato') ?? '';
    const fechaIngreso = $p.data('fecha_ingreso') ?? '';
    const cargo = $p.data('cargo') ?? '';
    const salarioLetras = $p.data('salario_letras') ?? '';
    const auxilioLetras = $p.data('auxilio_letras') ?? '';
    const bonifLetras = $p.data('bonif_letras') ?? '';

    const conSalario = $('#opt_salario').is(':checked');
    const conAuxilio = $('#opt_auxilio_transporte').is(':checked');
    const conBonif = $('#opt_bonificaciones').is(':checked');

    // Parte económica
    let economica = '';

    if (conSalario && salarioLetras) {
        economica += ' devengando un salario básico mensual de <strong>'
            + salarioLetras + '</strong>';
    }
    if (conAuxilio && auxilioLetras) {
        economica += (economica ? ', más ' : ' recibiendo ')
            + '<strong>AUXILIO DE TRANSPORTE POR '
            + auxilioLetras + '</strong>';
    }
    if (conBonif && bonifLetras) {
        economica += (economica ? ', más ' : ' recibiendo ')
            + '<strong>BONIFICACIÓN POR ' + bonifLetras + '</strong>';
    }
    if (economica) economica += '.';

    // Párrafo completo
    $p.html(
        'Que el señor <strong>' + nombre + '</strong>, ' +
        'identificado con cedula de ciudadanía número ' +
        '<strong>' + cedula + '</strong> de ' +
        '<strong>' + lugarExp + '</strong>, ' +
        'labora a través de un contrato de trabajo a ' +
        '<strong>' + tipoContrato + '</strong> ' +
        'desde el <strong>' + fechaIngreso + '</strong> ' +
        'y hasta la fecha, desempeñando el cargo de ' +
        '<strong>' + cargo + '</strong>' + economica
    );
}

// ── Actualizar radicado en preview al escribir ──────────
$(document).on('input', '#txt_radicado_cert', function () {
    const val = $(this).val().trim();
    $('#prev_radicado').text(val ? val + '.' : '');
});

// ── Regenerar párrafo al cambiar cualquier switch ───────
$(document).on('change', '.cert-opcion', function () {
    generarParrafoPrincipal();
});

$('#btn_guardar_basicos').on('click', function () {
    guardaryeditar();
});

//=========== Guardar y Editar ===============/

function guardaryeditar(e) {

    // ── Validar campos obligatorios ────────────────────
    var camposObligatorios = [
        'txt_tipo_documento_empl',
        'txt_numero_documento',
        'txt_nombre_empleado',
        'txt_telefono_empleado',
        'txt_direccion_empleado',
        'select_cargo_empleado',
        'txt_fecha_ingreso'
    ];

    for (var i = 0; i < camposObligatorios.length; i++) {
        var campo = $('#' + camposObligatorios[i]);
        if (!campo.val() || campo.val().trim() === '') {
            Swal.fire({
                title: "Campo obligatorio",
                text: "El campo " + (campo.attr('placeholder') || camposObligatorios[i]) + " es obligatorio",
                icon: "error"
            });
            return false;
        }
    }


    var formData = new FormData($('#form_empleado')[0]);

    // ── Campos opcionales — enviar NULL si vacíos ──────
    var camposOpcionales = [
        'txt_fecha_nacimiento',
        'txt_fecha_exp',
        'txt_lugar_exp',
        'txt_civil',
        'txt_rh',
        'txt_genero',
        'txt_estrato',
        'select_nivel_educativo',
        'txt_profesion',
        'txt_anio_grado',
        'select_tipo_contrato',
        'select_dependencia',
        'txt_correo'
    ];

    camposOpcionales.forEach(function (campo) {
        var valor = $('#' + campo).val();
        if (!valor || valor.trim() === '') {
            formData.set(campo, 'NULL');
        }
    });

    // ── Salario — limpiar formato colombiano ───────────
    var salarioBruto = $('#txt_salario').val()
        .replace(/\./g, '')
        .replace(',', '.');
    formData.set('txt_salario', salarioBruto);

    $.ajax({
        url: '../../controller/empleado.php?op=guardaryeditar',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function (datos) {
            console.log(datos);

            try {
                var response = JSON.parse(datos.trim());

                if (response.success) {

                    // ── Si es inserción (no tiene código) ──
                    if (!$('#txt_codigo_empleado').val()) {
                        $('#modalEmpleado').modal('hide');
                        $('#form_empleado')[0].reset();
                        //resetearDataTableEmpleados();
                        Swal.fire({
                            title: 'Grupo Empleado',
                            text: 'Empleado registrado exitosamente',
                            icon: 'success'
                        }).then(() => {
                            setTimeout(function () {
                                location.reload();
                            }, 500);
                        });

                        // ── Si es edición ──────────────────────
                    } else {
                        const codigo = $('#txt_codigo_empleado').val();

                        // Volver a modo lectura
                        inputsBasicos.forEach(id => {
                            $(id).prop('readonly', true)
                                .removeClass('input-editando');
                        });
                        selectsBasicos.forEach(id => {
                            $(id).prop('disabled', true).trigger('change');
                            $(id).next('.select2-container')
                                .find('.select2-selection')
                                .removeClass('select2-editando');
                        });

                        $('#btn_guardar_basicos').hide();
                        $('#btn_cancelar_basicos').hide();
                        $('#btn_editar_basicos').show();
                        $('#btn_toggle_form').prop('disabled', true);

                        // Recargar datos frescos desde BD
                        editar(codigo);

                        toastr.success('Empleado actualizado correctamente');


                    }

                } else {
                    Swal.fire({
                        title: 'Error',
                        text: response.error ?? 'No se pudo guardar el empleado',
                        icon: 'error'
                    });
                }

            } catch (err) {
                // ── Compatibilidad: si el insert no retorna JSON ──
                console.warn('Respuesta no JSON:', datos);
                $('#modalEmpleado').modal('hide');
                $('#form_empleado')[0].reset();
                //resetearDataTableEmpleados();
                Swal.fire({
                    title: 'Grupo Empleado',
                    text: 'Registro guardado exitosamente',
                    icon: 'success'
                }).then(() => {
                    setTimeout(function () {
                        location.reload();
                    }, 500);
                });
            }
        },
        error: function (xhr, status, error) {
            console.error('AJAX error:', error);
            Swal.fire({
                title: 'Error',
                text: 'Error de conexión al guardar',
                icon: 'error'
            });

        }
    });
}

// ══════════════════════════════════════════════════════
// BONIFICACIONES
// ══════════════════════════════════════════════════════

let tablaBonificaciones;

// ── Inicializar DataTable ──────────────────────────────
function initTablaBonificaciones() {
    tablaBonificaciones = $('#tablaBonificaciones').DataTable({
        language: {
            decimal: ',',
            thousands: '.',
            emptyTable: 'No hay bonificaciones registradas',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
            infoEmpty: 'Mostrando 0 a 0 de 0 registros',
            infoFiltered: '(filtrado de _MAX_ registros totales)',
            lengthMenu: 'Mostrar _MENU_ registros',
            loadingRecords: 'Cargando...',
            processing: 'Procesando...',
            search: 'Buscar:',
            zeroRecords: 'No se encontraron registros',
            paginate: {
                first: 'Primero',
                last: 'Último',
                next: 'Siguiente',
                previous: 'Anterior'
            }
        },
        pageLength: 6,
        lengthChange: false,
        responsive: true,
        searching: false,
        info: false,
        ordering: false,
        columns: [
            { data: 'bonif_concepto' },
            {
                data: 'bonif_valor',
                render: function (data) {
                    return parseFloat(data).toLocaleString('es-CO', {
                        style: 'currency', currency: 'COP', minimumFractionDigits: 0
                    });
                }
            },
            { data: 'bonif_periocidad', defaultContent: '—' },
            {
                data: 'bonif_fecha_ini',
                render: function (data) {
                    // ── YYYY-MM-DD → DD/MM/YYYY ──
                    if (!data) return '—';
                    const partes = data.split('-');
                    return partes[2] + '/' + partes[1] + '/' + partes[0];
                }
            },
            {
                data: 'bonif_estado',
                render: function (data) {
                    return data == 1
                        ? '<span class="badge badge-success">Activo</span>'
                        : '<span class="badge badge-secondary">Inactivo</span>';
                }
            },
            { data: 'bonif_observ', defaultContent: '—' },
            {
                data: null,
                orderable: false,
                render: function (data, type, row) {

                    if (!modoEdicion) return '—';

                    // ── Fecha formateada para data-fecha ──
                    let fechaFormateada = '';
                    if (row.bonif_fecha_ini) {
                        const partes = row.bonif_fecha_ini.split('-');
                        fechaFormateada = partes[2] + '/' + partes[1] + '/' + partes[0];
                    }

                    // ── Valor sin decimales para data-valor ──
                    const valorFormateado = parseFloat(row.bonif_valor).toLocaleString('es-CO', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 0
                    });

                    return `
                        <button class="btn btn-xs btn-warning btn-editar-bonif mr-1"
                            data-id="${row.bonif_id}"
                            data-concepto="${row.bonif_concepto}"
                            data-valor="${valorFormateado}"
                            data-periocidad="${row.bonif_periocidad ?? ''}"
                            data-fecha="${fechaFormateada}"
                            data-observ="${row.bonif_observ ?? ''}"
                            data-estado="${row.bonif_estado}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-xs btn-danger btn-eliminar-bonif"
                            data-id="${row.bonif_id}">
                            <i class="fas fa-trash"></i>
                        </button>`;
                }
            }
        ]
    });
}

// ── Listar bonificaciones ──────────────────────────────
function listarBonificaciones() {

    const empleado_id = $('#txt_codigo_empleado').val();
    if (!empleado_id) return;

    $.ajax({
        url: '../../controller/bonificaciones.php?op=listarBonificaciones',
        type: 'POST',
        data: { empleado_id: empleado_id },
        success: function (datos) {
            console.log('Raw listarBonificaciones:', datos); // ← ver qué llega
            try {
                const data = JSON.parse(datos.trim());

                if (tablaBonificaciones) {
                    tablaBonificaciones.clear().rows.add(data).draw();
                } else {
                    initTablaBonificaciones();
                    tablaBonificaciones.rows.add(data).draw();
                }

            } catch (err) {
                console.error('Error listarBonificaciones:', err);
                Swal.fire({ title: 'Error', text: 'Error al cargar bonificaciones', icon: 'error' });
            }
        },
        error: function (xhr, status, error) {
            console.error('AJAX error:', error);
            Swal.fire({ title: 'Error', text: 'Error de conexión', icon: 'error' });
        }
    });
}

// ── Toggle form nuevo ───────────────────────────────────
function toggleFormBonif() {
    limpiarFormBonif();
    $('#formBonif').show();
    $('#btn_guardar_basicos').prop('disabled', true);
    $('#btn_editar_basicos').prop('disabled', true);
}

// ── Cancelar form bonif ─────────────────────────────────
function cancelarFormBonif() {
    $('#formBonif').hide();
    limpiarFormBonif();
    $('#btn_editar_basicos').prop('disabled', false);
}

// ── Guardar / Actualizar bonificación ───────────────────
function guardarBonificacion() {
    const empleado_id = $('#txt_codigo_empleado').val();

    if (!empleado_id) {
        Swal.fire({ title: 'Atención', text: 'Debe seleccionar un empleado primero', icon: 'warning' });
        return;
    }
    if (!$('#txt_concepto').val()) {
        Swal.fire({ title: 'Campo obligatorio', text: 'El concepto es obligatorio', icon: 'error' });
        return;
    }
    if (!$('#txt_valor').val()) {
        Swal.fire({ title: 'Campo obligatorio', text: 'El valor es obligatorio', icon: 'error' });
        return;
    }

    const bonif_id = $('#formBonif').data('bonif_id') ?? '';
    const op = bonif_id ? 'actualizarBonificacion' : 'guardarBonificacion';


    $.ajax({
        url: '../../controller/bonificaciones.php?op=' + op,
        type: 'POST',
        data: {
            empleado_id: empleado_id,
            bonif_id: bonif_id,
            txt_concepto: $('#txt_concepto').val(),
            txt_valor: $('#txt_valor').val().trim().replace(/[.,]/g, ''),
            select_periocidad: $('#select_periocidad').val(),
            txt_fecha_inicio: $('#txt_fecha_inicio_bonif').val(),
            txt_observaciones: $('#txt_observ_bonif').val().trim()
        },
        success: function (datos) {
            console.log('Raw guardarBonificacion:', datos);
            try {
                const response = datos.trim();
                if (!response) {
                    Swal.fire({ title: 'Error', text: 'Respuesta vacía del servidor', icon: 'error' });
                    return;
                }
                const data = JSON.parse(response);
                if (data.success) {
                    cancelarFormBonif();
                    listarBonificaciones();
                    Swal.fire({
                        title: 'Guardado',
                        text: bonif_id ? 'Bonificación actualizada correctamente' : 'Bonificación guardada correctamente',
                        icon: 'success',
                        timer: 1500,
                        timerProgressBar: true,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({ title: 'Error', text: data.error, icon: 'error' });
                }
            } catch (err) {
                console.error('Error guardarBonificacion:', err);
                console.log('Respuesta raw:', datos);
                Swal.fire({ title: 'Error', text: 'Error inesperado al guardar', icon: 'error' });
            }
        },
        error: function (xhr, status, error) {
            console.error('AJAX error:', error);
            Swal.fire({ title: 'Error', text: 'Error de conexión al guardar', icon: 'error' });
        }
    });
}

// ── Limpiar form bonif ──────────────────────────────────
function limpiarFormBonif() {
    $('#formBonif').removeData('bonif_id');
    $('#txt_concepto').val('');
    $('#txt_valor').val('');
    $('#select_periocidad').val('Mensual');
    $('#txt_fecha_inicio').val('');
    $('#txt_observaciones').val('');
}

// ══════════════════════════════════════════════════════
// EVENTOS — asociados por ID de botón
// ══════════════════════════════════════════════════════

// ── Botón agregar ───────────────────────────────────────
$('#btn_toggle_form').on('click', function () {
    toggleFormBonif();
});

// ── Botón guardar ───────────────────────────────────────
$('#btn_guardar_bonif').on('click', function () {
    guardarBonificacion();
});

// ── Botón cancelar ──────────────────────────────────────
$('#btn_cancelar_bonif').on('click', function () {
    cancelarFormBonif();
});

// ── Botón editar fila ───────────────────────────────────
$(document).on('click', '.btn-editar-bonif', function () {
    const $btn = $(this);
    initDatePicker('#txt_fecha_inicio_bonif');
    $('#formBonif').data('bonif_id', $btn.data('id'));
    $('#txt_concepto').val($btn.data('concepto'));
    $('#txt_valor').val($btn.data('valor'));
    $('#select_periocidad').val($btn.data('periocidad'));
    $('#txt_fecha_inicio_bonif').val($btn.data('fecha'));
    $('#txt_observaciones').val($btn.data('observ'));
    $('#formBonif').show();
    $('#btn_guardar_basicos').prop('disabled', true);
    $('#btn_editar_basicos').prop('disabled', true);
});

// ── Botón eliminar fila ─────────────────────────────────
$(document).on('click', '.btn-eliminar-bonif', function () {
    const bonif_id = $(this).data('id');

    Swal.fire({
        title: '¿Eliminar bonificación?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonText: 'Cancelar',
        confirmButtonText: 'Sí, eliminar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../../controller/bonificaciones.php?op=eliminarBonificacion',
                type: 'POST',
                data: { bonif_id: bonif_id },
                success: function (datos) {
                    try {
                        const data = JSON.parse(datos.trim());
                        if (data.success) {
                            listarBonificaciones();
                            toastr.success('Bonificación eliminada');
                        } else {
                            Swal.fire({ title: 'Error', text: data.error, icon: 'error' });
                        }
                    } catch (err) {
                        console.error('Error eliminarBonificacion:', err);
                        toastr.error('Error inesperado');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX error:', error);
                    Swal.fire({ title: 'Error', text: 'Error de conexión', icon: 'error' });
                }
            });
        }
    });
});

// ── Cargar al entrar al tab ─────────────────────────────
$(document).on('click', '#mainTabs .nav-link[data-pane="bonificaciones"]', function () {
    listarBonificaciones();
});

$('#btn_exportar_egreso').on('click', function () {
    const codigo   = $('#txt_codigo_empleado').val();
    const radicado = $('#txt_radicado_egreso').val().trim();

    if (!codigo) {
        Swal.fire({ title: 'Atención', text: 'Debe seleccionar un empleado', icon: 'warning' });
        return;
    }
    if (!radicado) {
        Swal.fire({ title: 'Atención', text: 'Debe ingresar el radicado', icon: 'warning' });
        return;
    }

    const params = new URLSearchParams({
        op       : 'exportar_egreso_pdf',
        codigo   : codigo,
        radicado : radicado
    });

    window.open('../../controller/empleado.php?' + params.toString(), '_blank');
});


init();