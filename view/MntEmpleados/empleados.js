let tabla;


function init() {
    $("#form_empleado").on("submit", function(e){
        guardaryeditar(e);
    });
};

function guardaryeditar(e){
    e.preventDefault();

    // Validar campos obligatorios
    var camposObligatorios = [
        'txt_tipo_documento_empl',
        'txt_numero_documento', 
        'txt_telefono_empleado',
        'txt_direccion_empleado',
        'select_cargo_empleado',
        'txt_fecha_ingreso',
        'select_esta_empl'
    ];

    for(var i = 0; i < camposObligatorios.length; i++) {
        var campo = $('#' + camposObligatorios[i]);
        if(!campo.val() || campo.val().trim() === '') {
            Swal.fire({
                title: "Error",
                text: "El campo " + campo.attr('name') + " es obligatorio",
                icon: "error"
            });
            return false;
        }
    }

    var formData = new FormData($("#form_empleado")[0]);

    var camposOpcionales = [
        'txt_fecha_nacimiento',
        'select_nivel_educativo',
        'txt_profesion',
        'select_genero', 
        'txt_rh'
    ];

    camposOpcionales.forEach(function(campo) {
        var valor = $('#' + campo).val();
        if(!valor || valor.trim() === '') {
            formData.set(campo, 'NULL');
        }
    });

    $.ajax({

        url: "../../controller/empleado.php?op=guardaryeditar",
        type : "post",
        data: formData,			    		
        contentType: false,
        processData: false,
        success:function(datos){
            console.log(datos);
            $('#modalEmpleado').modal('hide');
            $('#txt_numero_documento').prop('readonly', false);
            $('#form_empleado')[0].reset();
            resetearDataTableEmpleados();
            Swal.fire({
                title: "Grupo Empleado",
                text: "Registro guardado exitosamente",
                icon: "success"
            });
        }
 
    });
}

function resetearDataTableEmpleados() {
    if ($.fn.DataTable.isDataTable('#empleados_data')) {
        $('#empleados_data').DataTable().ajax.reload();
    }
}

$(document).ready(function(){

    tabla=$('#empleados_data').dataTable({
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
        "ajax":{
            url: '../../controller/empleado.php?op=listarEmpleado',
            type : "post",
            dataType : "json",	
            data: tabla,			    		
            error: function(e){
                console.log(e.responseText);	
            }
        },

        
        "bDestroy": true,
        "responsive": true,
        "bInfo":true,
        "iDisplayLength": 5,
        "autoWidth": false,
        "language": {
            "sProcessing":     "Procesando...",
            "sLengthMenu":     "Mostrar _MENU_ registros",
            "sZeroRecords":    "No se encontraron resultados",
            "sEmptyTable":     "Ningún dato disponible en esta tabla",
            "sInfo":           "Mostrando un total de _TOTAL_ registros",
            "sInfoEmpty":      "Mostrando un total de 0 registros",
            "sInfoFiltered":   "(filtrado de un total de _MAX_ registros)",
            "sInfoPostFix":    "",
            "sSearch":         "Buscar:",
            "sUrl":            "",
            "sInfoThousands":  ",",
            "sLoadingRecords": "Cargando...",
            "oPaginate": {
                "sFirst":    "Primero",
                "sLast":     "Último",
                "sNext":     "Siguiente",
                "sPrevious": "Anterior"
            },
            "oAria": {
                "sSortAscending":  ": Activar para ordenar la columna de manera ascendente",
                "sSortDescending": ": Activar para ordenar la columna de manera descendente"
            }
        }     
    }).DataTable();
});



$(document).on("click","#btnBuscEmple",function(){

    $('#lblTituloUpdate').html('Editar Registro');
    /* document.getElementById('selectEstadoGrupo').style.display = 'none'; */
    /* $('#mantenimiento_grupo')[0].reset(); */
    $('#modalUpdateEmpleado').modal('show');

});

$('#modalUpdateEmpleado').on('shown.bs.modal', function () {
    let modalContent = document.querySelector('.modal-content');
    let dataTable = $('#empleados_data');

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




$(document).ready(function() {
    // Escuchar cuando se muestra el modal
    $('#modalEmpleado').on('shown.bs.modal', function () {
      // Inicializar Select2 y evitar problemas de z-index con dropdownParent
      $('#cargo_empleado').select2({
        dropdownParent: $('#modalEmpleado'),
        width: 'resolve'
      });
      $('#tipo_documento_empl').select2({
        dropdownParent: $('#modalEmpleado'),
        width: 'resolve'
      });

    });
    // Ajustar la altura al contenedor de Select2 después de la inicialización
    $('#cargo_empleado').next('.select2-container').find('.select2-selection--single').css({
        'height': '38px', // Altura que deseas aplicar
        'line-height': '38px', // Alinear verticalmente el texto
        'display': 'flex',
        'align-items': 'center' // Centrar el contenido verticalmente
    });
    // Ajustar la flecha del select2
    $('#cargo_empleado').next('.select2-container').find('.select2-selection__arrow').css({
        'height': '38px', // Asegurar que la flecha también esté alineada
        'top': '0px'
    });
    // Ajustar la altura al contenedor de Select2 después de la inicialización
    $('#tipo_documento_empl').next('.select2-container').find('.select2-selection--single').css({
        'height': '38px', // Altura que deseas aplicar
        'line-height': '38px', // Alinear verticalmente el texto
        'display': 'flex',
        'align-items': 'center' // Centrar el contenido verticalmente
    });
    // Ajustar la flecha del select2
    $('#tipo_documento_empl').next('.select2-container').find('.select2-selection__arrow').css({
        'height': '38px', // Asegurar que la flecha también esté alineada
        'top': '0px'
    });

});



// DatePicker para Fecha de Ingreso
$('#reservationdate_ingreso').daterangepicker({
    singleDatePicker: true,
    showDropdowns: true,
    autoApply: true,
    autoUpdateInput: true,  // ← Actualiza el input automáticamente
    drops: 'down',
    minYear: '1900',
    maxYear: '2090',
    locale: {
        format: 'YYYY-MM-DD',
        applyLabel: "Aplicar",
        cancelLabel: "Cancelar",
        daysOfWeek: ["Do", "Lu", "Ma", "Mi", "Ju", "Vi", "Sa"],
        monthNames: ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"],
        firstDay: 1
    }
});

// Manejar el evento cuando se selecciona una fecha
$('#reservationdate_ingreso').on('apply.daterangepicker', function(ev, picker) {
    $('#txt_fecha_ingreso').val(picker.startDate.format('YYYY-MM-DD'));
});

// DatePicker para Fecha de Nacimiento
$('#reservationdate_nacimiento').daterangepicker({
    singleDatePicker: true,
    showDropdowns: true,
    autoApply: true,
    autoUpdateInput: true,  // ← Actualiza el input automáticamente
    drops: 'down',
    minYear: '1900',
    maxYear: '2090',
    locale: {
        format: 'YYYY-MM-DD',
        applyLabel: "Aplicar",
        cancelLabel: "Cancelar",
        daysOfWeek: ["Do", "Lu", "Ma", "Mi", "Ju", "Vi", "Sa"],
        monthNames: ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"],
        firstDay: 1
    }
});

// Manejar el evento cuando se selecciona una fecha
$('#reservationdate_nacimiento').on('apply.daterangepicker', function(ev, picker) {
    $('#txt_fecha_nacimiento').val(picker.startDate.format('YYYY-MM-DD'));
});


$.post("../../controller/tipodoc.php?op=comboTipoDocumento",function(data, status){
    var $tipoDocumentoEmpl = $('#txt_tipo_documento_empl');
    $tipoDocumentoEmpl.html(data);
});

$.post("../../controller/Cargo.php?op=comboCargo",function(data, status){
    var $tipoDocumentoEmpl = $('#select_cargo_empleado');
    $tipoDocumentoEmpl.html(data);
});

function irErp() {
    window.location.href = BASE_URL +'/view/MntSiesa/emplSiesa.php'; //http://181.204.219.154:3396/preoperacional
}

$(function(){
    $('#btnSiesa').on('click', function(){
        irErp();
    });
});

function editar(codigo_empleado){ 
    $.post("../../controller/empleado.php?op=mostrar",{codigo_empleado:codigo_empleado},function(data){
        data= JSON.parse(data); //formatear la data para que front en lo detecte como texto
        console.log(data);
        $('#txt_codigo_empleado').val(data.txt_codigo_empleado);  
        $('#txt_numero_documento').val(data.txt_numero_documento);
        $('#txt_nombre_empleado').val(data.txt_nombre_empleado);
        $('#txt_telefono_empleado').val(data.txt_telefono_empleado);
        $('#txt_direccion_empleado').val(data.txt_direccion_empleado);
        $('#txt_fecha_ingreso').val(data.txt_fecha_ingreso);
        $('#txt_fecha_nacimiento').val(data.txt_fecha_nacimiento);
        $('#txt_tipo_documento_empl').val(data.txt_tipo_documento_empl);
        $('#select_cargo_empleado').val(data.select_cargo_empleado);


    });

    $('#modalUpdateEmpleado').modal('hide');
    $('#txt_numero_documento').prop('readonly', true);

}

/* $(document).on("click","#btnNuevoEmple",function(){

    $('#lblTitulo').html('Nuevo Registro');
    document.getElementById('selectEstadoEmpleado').style.display = 'none';
    $('#mantenimiento_empleado')[0].reset();
    $.post("../../controller/cargo.php?op=comboCargo",function(data, status){
        var $cargoEmpleado = $('#cargo_empleado');
        $cargoEmpleado.html(data);
    });

    $('#modalEmpleado').modal('show');

}); */

init();