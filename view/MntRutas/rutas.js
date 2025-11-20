let tabla;

function init() {
    $("#form_add_rutas").on("submit", function (e) {
        guardaryeditar(e);
    });
}

$('.select2').select2({
    theme: 'bootstrap4',
    width: '100%'
});

function irForm() {
    window.location.href = BASE_URL + '/view/MntRutas/addRutas.php';
}

$(function () {
    $('#btn-add-rutas').on('click', function () {
        irForm();
    });
});

$('#btnVolverR').click(function () {
    // Verificar si viene de tickets.php
    if (document.referrer.indexOf('rutas.php') !== -1) {
        history.back(); // Regresa a la página anterior
    } else {
        window.location.href = 'rutas.php'; // Redirige por defecto
    }
});

$.post("../../controller/empleado.php?op=comboRol", function (data, status) {
    var $empleado = $('#txt_empleado');
    $empleado.html(data);
});

$.post("../../controller/empleado.php?op=comboRol", function (data, status) {
    var $jefe = $('#txt_jefe');
    $jefe.html(data);
});

function guardaryeditar(e) {
    e.preventDefault();
    var formData = new FormData($("#form_add_rutas")[0]);

    $.ajax({
        url: "../../controller/rutas.php?op=guardaryeditar",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        dataType: "json", // MUY IMPORTANTE
        success: function (datos) {

            console.log("RESPUESTA SERVIDOR:", datos);

            if (datos.success) {
                Swal.fire("Éxito", datos.message, "success");
                $("#form_add_rutas")[0].reset();
                $(".select2").val(null).trigger("change");
            } else {
                Swal.fire("Aviso", datos.message, "warning");
            }
        },
        error: function (xhr, status, error) {
            console.log("ERROR AJAX:", error);
            console.log("RESPUESTA:", xhr.responseText);
            Swal.fire("Error", "Ocurrió un problema en el servidor", "error");
        }
    });
}

//VIZSUALIZAR DATA
$(document).ready(function() {

    tabla = $('#ruta_data').DataTable({
        processing: false,
        serverSide: false,
        searching: true,
        lengthChange: true,      // permitir cambiar entre 10, 25, 50...
        pageLength: 10,          // mostrar 10 registros por defecto
        colReorder: true,

        order: [[1, 'asc']],

        ajax: {
            url: '../../controller/rutas.php?op=listarRutas',
            type: "POST",
            dataType: "json",
            error: function(e) {
                console.log("ERROR AJAX:", e.responseText);
            }
        },

        destroy: true,
        responsive: true,
        info: true,
        autoWidth: false,

        language: {
            "sProcessing":     "Procesando...",
            "sLengthMenu":     "Mostrar _MENU_ registros",
            "sZeroRecords":    "No se encontraron resultados",
            "sEmptyTable":     "Ningún dato disponible en esta tabla",
            "sInfo":           "Mostrando _START_ a _END_ de _TOTAL_ registros",
            "sInfoEmpty":      "Mostrando 0 registros",
            "sInfoFiltered":   "(filtrado de _MAX_ registros)",
            "sSearch":         "Buscar:",
            "sLoadingRecords": "Cargando...",
            "oPaginate": {
                "sFirst":    "Primero",
                "sLast":     "Último",
                "sNext":     "Siguiente",
                "sPrevious": "Anterior"
            }
        }
    });

});

function eliminar(codi_empleado_jefe){

    Swal.fire({
        title: "Deshabilitar",
        text: "Desea deshabiltar el registro",
        icon: "error",
        showCancelButton: true,
        confirmButtonColor: "#009BA9",
        cancelButtonColor: "#d33",
        confirmButtonText: "Confirmar"
      }).then((result) => {
        if (result.value) {

            if(result.value){

                $.post("../../controller/rutas.php?op=inactivar_empleado",{codi_empleado_jefe:codi_empleado_jefe},function(data){
                    console.log (data);
                });

            }

            $('#ruta_data').DataTable().ajax.reload();


          Swal.fire({
            title: "Deshabilitar",
            text: "Registro inhabilitado.",
            icon: "success"
          });
        }
      });
} 


init();