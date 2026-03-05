function init() {

    initSelect2Dynamic();
}



$.post("../../controller/empleado.php?op=comboRol", function (data, status) {
    $('#nomb_empl').html(data);
});


function initSelect2Dynamic() {

    $(".select2bs4").each(function () {

        let $select = $(this);

        // Evitar inicialización doble
        if ($select.hasClass("select2-hidden-accessible")) {
            return;
        }

        // Detectar si estamos dentro de algún modal
        let modalPadre = $select.closest(".modal");

        $select.select2({
            theme: "bootstrap4",
            width: "100%",
            dropdownParent: modalPadre.length ? modalPadre : $(document.body)
        });

    });
}

document.getElementById('btnDescargRpte').addEventListener('click', function () {

    const idEmpleado = document.getElementById('nomb_empl').value;
    const radicado = document.getElementById('radicado').value;

    if (!idEmpleado) {
        Swal.fire('Atención', 'Debe seleccionar un empleado.', 'warning');
        return;
    }

    if (!radicado.trim()) {
        Swal.fire('Atención', 'Debe ingresar el código interno de radicado.', 'warning');
        return;
    }

    // Crear form dinámico
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '../../controller/certificado.php?op=generarCertificado';
    form.target = '_blank';

    // ID EMPLEADO
    const inputEmpleado = document.createElement('input');
    inputEmpleado.type = 'hidden';
    inputEmpleado.name = 'id_empl';
    inputEmpleado.value = idEmpleado;

    // RADICADO
    const inputRadicado = document.createElement('input');
    inputRadicado.type = 'hidden';
    inputRadicado.name = 'radicado';
    inputRadicado.value = radicado;

    form.appendChild(inputEmpleado);
    form.appendChild(inputRadicado);

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);

});


init();