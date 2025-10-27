

function init() {



    $("#formulario_evaluacion").on("submit", function (e) {

        const tipoEvaluacionSeleccionado = document.querySelector('input[name="radioTipoEval"]:checked');

        if (!tipoEvaluacionSeleccionado) {
            e.preventDefault(); // Detiene el envío o acción por defecto
            Swal.fire({
                icon: 'warning',
                title: 'Tipo de Evaluación',
                text: 'Por favor selecciona un tipo de evaluación: Autoevaluacion, Coevaluacion o Subevaluacion',
                confirmButtonText: 'Entendido'
            });
            return;
        }

        guardar(e);

    });




}


$('.select2').select2()


$(document).ready(function () {

    document.getElementById('card2').style.display = 'none';
    document.getElementById('card3').style.display = 'none';
    document.getElementById('card4').style.display = 'none';
    document.getElementById('cardFooter').style.display = 'none';

    $("#eval_observaciones").blur();

    $.post("../../controller/evaluacion.php?op=comboMes", function (data, status) {
        var $mesEvaluacion = $('#mes_eval');
        console.log($mesEvaluacion);
        $mesEvaluacion.html(data);
    });
});

$(document).on("click", "#btnSiguiente1", function () {
    let camposInvalidos = $("#card1").find("select[required]").filter(function () {
        return !$(this).val() || $(this).val() === "";
    });

    $("#card1").find("select").removeClass("is-invalid");
    $(".select2-selection").removeClass("is-invalid");

    if (camposInvalidos.length > 0) {
        camposInvalidos.each(function () {
            $(this).addClass("is-invalid");
            if ($(this).hasClass("select2-hidden-accessible")) {
                $(this).next('.select2-container').find('.select2-selection').addClass('is-invalid');
            }
        });

        Swal.fire({
            icon: "warning",
            title: "Campos incompletos",
            text: "Por favor, completa todas las preguntas",
        });

        return;
    }

    // Forzar la validación de select2 (si es necesario)
    $(".select2").trigger('change');

    // Continuar con la transición entre tarjetas si todo es válido
    document.getElementById('card2').style.display = 'block';
    document.getElementById('card1').style.display = 'none';
});



$(document).on("click", "#btnSiguiente2", function () {
    let camposInvalidos = $("#card2").find("input[required], select[required]").filter(function () {
        return !$(this).val() || $(this).val() === "";
    });
    // Quitar clases anteriores para que no se acumulen
    $("#card2").find("input, select").removeClass("is-invalid");
    $(".select2-selection").removeClass("is-invalid");

    if (camposInvalidos.length > 0) {
        camposInvalidos.each(function () {
            $(this).addClass("is-invalid");

            // Si es select2, aplicar clase al contenedor visual también
            if ($(this).hasClass("select2-hidden-accessible")) {
                $(this).next('.select2-container').find('.select2-selection').addClass('is-invalid');
            }
        });

        Swal.fire({
            icon: "warning",
            title: "Campos incompletos",
            text: "Por favor, completa todas las preguntas",
        });
        return;
    }
    document.getElementById('card3').style.display = 'block';
    document.getElementById('card2').style.display = 'none';
});

$(document).on("click", "#btnAnterior2", function () {
    document.getElementById('card2').style.display = 'none';
    document.getElementById('card1').style.display = 'block';
});

$(document).on("click", "#btnSiguiente3", function () {
    let camposInvalidos = $("#card3").find("input[required], select[required]").filter(function () {
        return !$(this).val() || $(this).val() === "";
    });
    // Quitar clases anteriores para que no se acumulen
    $("#card3").find("input, select").removeClass("is-invalid");
    $(".select2-selection").removeClass("is-invalid");

    if (camposInvalidos.length > 0) {
        camposInvalidos.each(function () {
            $(this).addClass("is-invalid");

            // Si es select2, aplicar clase al contenedor visual también
            if ($(this).hasClass("select2-hidden-accessible")) {
                $(this).next('.select2-container').find('.select2-selection').addClass('is-invalid');
            }
        });

        Swal.fire({
            icon: "warning",
            title: "Campos incompletos",
            text: "Por favor, completa todas las preguntas",
        });
        return;
    }
    document.getElementById('card4').style.display = 'block';
    document.getElementById('cardFooter').style.display = 'block';
    document.getElementById('card3').style.display = 'none';
});

$(document).on("click", "#btnAnterior3", function () {
    document.getElementById('card3').style.display = 'none';
    document.getElementById('card2').style.display = 'block';
});

$(document).on("click", "#btnAnterior4", function () {
    document.getElementById('card4').style.display = 'none';
    document.getElementById('cardFooter').style.display = 'none';
    document.getElementById('card3').style.display = 'block';
});


$.post("../../controller/empleado.php?op=comboRol", function (data, status) {
    var $userEmpleado = $('#nomb_evaluad');
    $userEmpleado.html(data);
});



function guardar(e) {

    e.preventDefault();

    let formData = new FormData($("#formulario_evaluacion")[0]);

    $.ajax({

        url: "../../controller/evaluacion.php?op=insert",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function (datos) {


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
                    $('#nomb_evaluad').val('');
                    $('#nomb_evaluad').html('');
                    document.getElementById("formulario_evaluacion").reset();
                    document.getElementById('card1').style.display = 'block';
                    document.getElementById('card4').style.display = 'none';
                    document.getElementById('cardFooter').style.display = 'none';
                }
            });




            //document.getElementById("formulario_evaluacion").reset();
        }


    });


}


/*function menuSegunRoles(roles){

    const menuInicio = document.getElementById('menuInicio');
    const menuEvaluacion = document.getElementById('menuEvaluacion');
    const menuInformes = document.getElementById('menuInformes');
    const menuEmpleados = document.getElementById('menuEmpleados');

    // Ocultar todos los elementos del menú inicialmente
    menuInicio.style.display = 'none';
    menuEvaluacion.style.display = 'none';
    menuInformes.style.display = 'none';
    menuEmpleados.style.display = 'none';

    // Mostrar u ocultar elementos del menú según los roles
    if (roles.some(r => rolesMostrarOpcion1.includes(r))) {
        menuInicio.style.display = 'block';
        menuEvaluacion.style.display = 'block';
        menuInformes.style.display = 'block';
        menuEmpleados.style.display = 'block';
    }

    if (roles.some(r => rolesMostrarOpcion2.includes(r))) {
        menuInicio.style.display = 'block';
        menuEvaluacion.style.display = 'block';
        menuInformes.style.display = 'block';
        menuEmpleados.style.display = 'none';
    } else {
        menuEvaluacion.style.display = 'block';
    }

    if (roles.some(r => rolesMostrarOpcion3.includes(r))) {
        menuInicio.style.display = 'none';
        menuEvaluacion.style.display = 'block';
        menuInformes.style.display = 'none';
        menuEmpleados.style.display = 'none';
    }

}

const rolesMostrarOpcion1 = ['ADMINISTRADOR', 'RECURSO HUMANO'];
const rolesMostrarOpcion2 = ['GERENTE', 'SIG'];
const rolesMostrarOpcion3 = ['EMPLEADO'];*/

init();