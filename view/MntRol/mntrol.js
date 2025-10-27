function init(){
    $("#mantenimiento_rol").on("submit", function(e){
        guardaryeditar(e);
    });
}

function guardaryeditar(e){
    e.preventDefault();
    var formData = new FormData($("#mantenimiento_rol")[0]);
    $.ajax({

        url: "../../controller/rol.php?op=guardaryeditar",
        type : "post",
        data: formData,			    		
        contentType: false,
        processData: false,
        success:function(datos){
            console.log(datos);
            $('#pre_data').DataTable().ajax.reload();
            $('#modalRol').modal('hide');

            Swal.fire({
                title: "Rol",
                text: "Registro guardado exitosamente",
                icon: "success"
            });
            
        }

    });
}



let tabla;



$(document).ready(function(){

    tabla=$('#pre_data').dataTable({
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
            url: '../../controller/rol.php?op=listar',
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


function editar(rol_id){
    $.post("../../controller/rol.php?op=mostrar",{rol_id:rol_id},function(data){
        data= JSON.parse(data); //formatear la data para que front en lo detecte como texto
        $('#rol_id').val(data.rol_id);
        $('#nomb_rol').val(data.rol_nomb);

    });

    $('#lblTitulo').html('Editar Registro');
    document.getElementById('selectEstadoRol').style.display = 'inline';
    $('#modalRol').modal('show');
}

function permisos(rol_id){

    console.log(rol_id);
    $('#perm_table_data').DataTable({
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
            url: '../../controller/menu.php?op=listar',
            type : "post",
            dataType : "json",	
            data: {rol_id:rol_id}			    		
            
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
    });

    $('#modalPermisos').modal('show');



}

function eliminar(rol_id){
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

                $.post("../../controller/rol.php?op=eliminar_rol",{rol_id:rol_id},function(data){
                    console.log (data);
                });

            }

            $('#pre_data').DataTable().ajax.reload();


          Swal.fire({
            title: "Rol",
            text: "Registro eliminado exitosamente",
            icon: "success"
          });
        }
      });
}

function activar(perm_id){
    $.post("../../controller/menu.php?op=activar",{perm_id:perm_id},function(data){
        $('#perm_table_data').DataTable().ajax.reload();
    });
}

function desactivar(perm_id){
    $.post("../../controller/menu.php?op=desactivar",{perm_id:perm_id},function(data){
        $('#perm_table_data').DataTable().ajax.reload();
    });
}

$(document).on("click","#btnNuevoRol",function(){

    $('#lblTitulo').html('Nuevo Registro');
    document.getElementById('selectEstadoRol').style.display = 'none';
    $('#modalRol').modal('show');


});

init();
