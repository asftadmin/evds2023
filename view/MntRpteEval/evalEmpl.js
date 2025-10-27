function init(){

}

$('.select2').select2();

$(document).ready(function () {
    // Llenar combo de empleados
    $.post("../../controller/empleado.php?op=buscarEmpleado", function (data, status) {
        $('#nomb_empl').html(data);
    });

    // Acción del botón de descarga
    document.getElementById('btnDescargRpte').addEventListener('click', function () {
        const idEmpleado = document.getElementById('nomb_empl').value;
        console.log (idEmpleado);
        if (!idEmpleado) {
            alert('Por favor seleccione un empleado.');
            return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '../../controller/evaluacion.php?op=reportePDF';
        form.target = '_blank';

        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'id_empl';
        input.value = idEmpleado;

        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    });
});


init();