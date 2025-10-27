let tabla;

function init(){
    $("#mantenimiento_asignacion").on("submit", function(e){
        guardaryeditar(e);
    });
}


function guardaryeditar(e){
    e.preventDefault();
    var formData = new FormData($("#mantenimiento_asignacion")[0]);

    // Configuración del AJAX
    $.ajax({
        url: "../../controller/asignacion.php?op=guardaryeditar", // Ruta del controlador
        type: "POST", // Método de envío
        data: formData, // Datos del formulario
        contentType: false, // No procesar el tipo de contenido
        processData: false, // No procesar los datos automáticamente
        success: function (datos) {
            $('#modalAsignacion').modal('hide');
            $('#asignacion_data').DataTable().ajax.reload();
            Swal.fire({
                title: "Asigancion Evaluación",
                text: "Registro guardado exitosamente",
                icon: "success"
            });

        }
    });

}

/* tabla de datos */
$(document).ready(function(){

    tabla=$('#asignacion_data').dataTable({
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
        order: [[1, 'asc']],
        "ajax":{
            url: '../../controller/asignacion.php?op=listarAsignacion',
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
        "iDisplayLength": 7,
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


$.post("../../controller/evaluacion.php?op=comboMesTotal",function(data, status){
    var $mesEvalua = $('#mes_evalua');
    console.log($mesEvalua);
    $mesEvalua.html(data);
});
/* Boton Nuevo Registro Asignacion */
$(document).on("click","#btnNuevoAsig",function(){

    $('#lblTitulo').html('Nuevo Registro');
    $('#mantenimiento_asignacion')[0].reset();
    $('#modalAsignacion').modal('show');

});

/* Arreglar estilos para que el search del select funcione */
$(document).ready(function() {
    // Escuchar cuando se muestra el modal
    $('#modalAsignacion').on('shown.bs.modal', function () {
      // Inicializar Select2 y evitar problemas de z-index con dropdownParent
      $('#nomb_evaluador').select2({
        dropdownParent: $('#modalAsignacion'),
        width: 'resolve'
      });
      $('#nomb_empleado').select2({
        dropdownParent: $('#modalAsignacion'),
        width: 'resolve'
      });
      $('#mes_evalua').select2({
        dropdownParent: $('#modalAsignacion'),
        width: 'resolve'
      });
      $('#año_evalua').select2({
        dropdownParent: $('#modalAsignacion'),
        width: 'resolve'
      });

    });
    // Ajustar la altura al contenedor de Select2 después de la inicialización
    $('#nomb_evaluador').next('.select2-container').find('.select2-selection--single').css({
        'height': '38px', // Altura que deseas aplicar
        'line-height': '38px', // Alinear verticalmente el texto
        'display': 'flex',
        'align-items': 'center' // Centrar el contenido verticalmente
    });
    // Ajustar la flecha del select2
    $('#nomb_evaluador').next('.select2-container').find('.select2-selection__arrow').css({
        'height': '38px', // Asegurar que la flecha también esté alineada
        'top': '0px'
    });
    // Ajustar la altura al contenedor de Select2 después de la inicialización
    $('#nomb_empleado').next('.select2-container').find('.select2-selection--single').css({
        'height': '38px', // Altura que deseas aplicar
        'line-height': '38px', // Alinear verticalmente el texto
        'display': 'flex',
        'align-items': 'center' // Centrar el contenido verticalmente
    });
    // Ajustar la flecha del select2
    $('#nomb_empleado').next('.select2-container').find('.select2-selection__arrow').css({
        'height': '38px', // Asegurar que la flecha también esté alineada
        'top': '0px'
    });
    // Ajustar la altura al contenedor de Select2 después de la inicialización
    $('#mes_evalua').next('.select2-container').find('.select2-selection--single').css({
        'height': '38px', // Altura que deseas aplicar
        'line-height': '38px', // Alinear verticalmente el texto
        'display': 'flex',
        'align-items': 'center' // Centrar el contenido verticalmente
    });
    // Ajustar la flecha del select2
    $('#mes_evalua').next('.select2-container').find('.select2-selection__arrow').css({
        'height': '38px', // Asegurar que la flecha también esté alineada
        'top': '0px'
    });
    // Ajustar la altura al contenedor de Select2 después de la inicialización
    $('#año_evalua').next('.select2-container').find('.select2-selection--single').css({
        'height': '38px', // Altura que deseas aplicar
        'line-height': '38px', // Alinear verticalmente el texto
        'display': 'flex',
        'align-items': 'center' // Centrar el contenido verticalmente
    });
    // Ajustar la flecha del select2
    $('#año_evalua').next('.select2-container').find('.select2-selection__arrow').css({
        'height': '38px', // Asegurar que la flecha también esté alineada
        'top': '0px'
    });


});

/* Funcion Eliminar asignacion */
function eliminar(codi_asignacion){

    Swal.fire({
        title: "Eliminar",
        text: "Desea eliminar el registro",
        icon: "error",
        showCancelButton: true,
        confirmButtonColor: "#009BA9",
        cancelButtonColor: "#d33",
        confirmButtonText: "Confirmar"
      }).then((result) => {
        if (result.value) {

            if(result.value){

                $.post("../../controller/asignacion.php?op=eliminar_asignacion",{codi_asignacion:codi_asignacion},function(data){
                    console.log (data);
                });

            }

            $('#asignacion_data').DataTable().ajax.reload();


          Swal.fire({
            title: "Asignacion Evalacion",
            text: "Registro eliminado exitosamente",
            icon: "success"
          });
        }
      });
} 

/* Funcion Editar */

/* function editar(codi_asignacion){
    $('#lblTitulo').html('Editar Registro');


    $.post("../../controller/asignacion.php?op=mostrar_asignacion", {codi_asignacion : codi_asignacion},function(data){
        data= JSON.parse(data); //formatear la data para que front en lo detecte como texto
        console.log(data);
        $('#codigo_asignacion').val(data.codigo_asignacion);
        $('#nomb_evaluador').val(data.nomb_evaluador);
        $('#nomb_empleado').val(data.nomb_empleado);
        $('#mes_evalua').val(data.mes_evalua);
        $('#año_evalua').val(data.año_evalua);

    });

    $('#modalAsignacion').modal('show');
} */

/* Funcion para mostrar datos de empleados en los select evaluador y evaluado */

$.post("../../controller/empleado.php?op=comboAsig", function(data, status) {
    // Convertir respuesta en objeto JSON si no está convertido automáticamente
    const response = JSON.parse(data);

    // Seleccionar los elementos del DOM donde colocar los datos
    var $evaluadorSelect = $('#nomb_evaluador');
    var $empleadoSelect = $('#nomb_empleado');

    // Rellenar evaluadores
    $evaluadorSelect.empty(); // Limpiar opciones existentes
    $evaluadorSelect.append('<option value="">Seleccione un evaluador</option>'); // Opción inicial
    response.evaluadores.forEach(evaluador => {
        $evaluadorSelect.append(`<option value="${evaluador.id_empl}">${evaluador.nomb_empl}</option>`);
    });

    // Rellenar empleados
    $empleadoSelect.empty(); // Limpiar opciones existentes
    $empleadoSelect.append('<option value="">Seleccione empleados</option>'); // Opción inicial
    response.empleados.forEach(empleado => {
        $empleadoSelect.append(`<option value="${empleado.id_empl}">${empleado.nomb_empl}</option>`);
    });
});


init();