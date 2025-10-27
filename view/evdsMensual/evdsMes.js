function init(){

    $("#formulario_evds_mensual").on("submit", function(e){

        e.preventDefault();

        const mes = $("#txt_mes_eval").val();
        if (!mes) {
            Swal.fire("Campo requerido", "Debe seleccionar un mes", "warning");
            return; // no continuar si no hay valor
        }

        guardar(e);

    });

}

$('.select2').select2()

$.post("../../controller/evaluacion.php?op=comboMes",function(data, status){
    var $mesEvaluacion = $('#txt_mes_eval');
    console.log($mesEvaluacion);
    $mesEvaluacion.html(data);
});

$.post("../../controller/empleado.php?op=comboRol",function(data, status){
    var $userEmpleado = $('#txt_nomb_evaluad');
    $userEmpleado.html(data);
});

function guardar(e){

    e.preventDefault();

    let formData = new FormData($("#formulario_evds_mensual")[0]);

    $.ajax({

        url:"../../controller/evaluacion.php?op=guardarEvdsMes",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function(datos){
            
            
            Swal.fire({
                icon: "success",
                title: 'Correcto',
                text: 'Datos Guardados Correctamente',
                type: "success", // Puedes usar 'info', 'success', 'error', 'warning', etc.
                cancelButtonText: 'Cancelar',
                cancelButtonClass: "btn-danger",
                confirmButtonColor: '#009BA9',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Confirmar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Si el usuario confirma, recargar la página
                    document.getElementById("formulario_evds_mensual").reset();
                    $('.select2').val(null).trigger('change');
                }
            });
            

            
            
            //document.getElementById("formulario_evaluacion").reset();
        }


    });


}

init();