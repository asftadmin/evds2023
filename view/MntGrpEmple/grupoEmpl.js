let tabla;

function init() {
    $("#mantenimiento_grupo").on("submit", function(e){
        guardaryeditar(e);
    });
}


function guardaryeditar(e){
    e.preventDefault();
    var formData = new FormData($("#mantenimiento_grupo")[0]);
    $.ajax({

        url: "../../controller/grupo.php?op=guardaryeditar",
        type : "post",
        data: formData,			    		
        contentType: false,
        processData: false,
        success:function(datos){
            console.log(datos);
            $('#grupo_data').DataTable().ajax.reload();
            $('#modalGrupo').modal('hide');
            $('#grupo_data').DataTable().ajax.reload();
            Swal.fire({
                title: "Grupo Empleado",
                text: "Registro guardado exitosamente",
                icon: "success"
            });
            
        }

    });
}


$(document).ready(function(){

    tabla=$('#grupo_data').dataTable({
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
            url: '../../controller/Grupo.php?op=listarGrupo',
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

$(document).on("click","#btnNuevoGrupo",function(){

    $('#lblTitulo').html('Nuevo Registro');
    document.getElementById('selectEstadoGrupo').style.display = 'none';
    $('#mantenimiento_grupo')[0].reset();
    $('#modalGrupo').modal('show');


});

function editar(codigo_grupo){
    $.post("../../controller/grupo.php?op=mostrar",{codigo_grupo:codigo_grupo},function(data){
        data= JSON.parse(data); //formatear la data para que front en lo detecte como texto
        $('#codigo_grupo').val(data.codigo_grupo);  
        $('#nomb_grupo').val(data.nombre_grupo);
        $('#esta_grupo').val(data.estado_grupo).trigger('change');

    });

    $('#lblTitulo').html('Editar Registro');
    document.getElementById('selectEstadoGrupo').style.display = 'inline';
    $('#modalGrupo').modal('show');
}

function eliminar(codigo_grupo){
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

                $.post("../../controller/grupo.php?op=eliminar_grupo",{codigo_grupo:codigo_grupo},function(data){
                    console.log (data);
                });

            }

            $('#grupo_data').DataTable().ajax.reload();


          Swal.fire({
            title: "Grupo Empleados",
            text: "Registro eliminado exitosamente",
            icon: "success"
          });
        }
      });
}

init();