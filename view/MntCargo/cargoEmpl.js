let tabla;

function init() {
    $("#mantenimiento_cargo").on("submit", function(e){
        guardaryeditar(e);
    });
}

function guardaryeditar(e){
    e.preventDefault();
    var formData = new FormData($("#mantenimiento_cargo")[0]);
    $.ajax({

        url: "../../controller/cargo.php?op=guardaryeditar",
        type : "post",
        data: formData,			    		
        contentType: false,
        processData: false,
        success:function(datos){
            console.log(datos);
            $('#cargo_data').DataTable().ajax.reload();
            $('#modalCargo').modal('hide');
            Swal.fire({
                title: "Registro Cargo",
                text: "Registro guardado exitosamente",
                icon: "success"
            });
            
        }

    });
}


$(document).ready(function(){

    tabla=$('#cargo_data').dataTable({
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
            url: '../../controller/Cargo.php?op=listarCargo',
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

$.post("../../controller/grupo.php?op=comboGrupo",function(data, status){
    var $grupoEmpleado = $('#grupo_empl');
    $grupoEmpleado.html(data);
});

$(document).on("click","#btnNuevoCargo",function(){

    $('#lblTitulo').html('Nuevo Registro');
    document.getElementById('selectGrupoEmpleados').style.display = 'inline';
    document.getElementById('selectEstadoCargo').style.display = 'none';
    $('#mantenimiento_cargo')[0].reset();
    $('#modalCargo').modal('show');

});

function editar(codigo_cargo){
    $.post("../../controller/cargo.php?op=mostrar",{codigo_cargo:codigo_cargo},function(data){
        data= JSON.parse(data); //formatear la data para que front en lo detecte como texto
        $('#codigo_cargo').val(data.codigo_cargo);  
        $('#nomb_cargo').val(data.nombre_cargo);
        $('#esta_cargo').val(data.esta_grupo).trigger('change');

    });

    $('#lblTitulo').html('Editar Registro');
    document.getElementById('selectGrupoEmpleados').style.display = 'none';
    document.getElementById('selectEstadoCargo').style.display = 'inline';
    $('#modalCargo').modal('show');
}

function eliminar(codigo_cargo){
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

                $.post("../../controller/cargo.php?op=eliminar_cargo",{codigo_cargo:codigo_cargo},function(data){
                    console.log (data);
                });

            }

            $('#cargo_data').DataTable().ajax.reload();


          Swal.fire({
            title: "Grupo Empleados",
            text: "Registro eliminado exitosamente",
            icon: "success"
          });
        }
      });
}

init();