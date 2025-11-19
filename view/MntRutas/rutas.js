

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