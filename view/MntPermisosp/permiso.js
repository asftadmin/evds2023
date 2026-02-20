function init() {
  $("#btnGuardar").prop("disabled", true);

  $("#form_permiso").on("submit", function (e) {
    guardar(e);
  });
}

$('#reservationdate_fecha').daterangepicker({
  singleDatePicker: true,
  showDropdowns: true,
  autoApply: true,
  autoUpdateInput: true,  // ← Actualiza el input automáticamente
  drops: 'down',
  minYear: '1900',
  maxYear: '2090',
  locale: {
    format: 'YYYY-MM-DD',
    applyLabel: "Aplicar",
    cancelLabel: "Cancelar",
    daysOfWeek: ["Do", "Lu", "Ma", "Mi", "Ju", "Vi", "Sa"],
    monthNames: ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"],
    firstDay: 1
  }
});

// Forzar el calendario a mostrar la fecha final (hoy)
$('#reservationdate_fecha').on('apply.daterangepicker', function (ev, picker) {
  $('#fecha_permiso').val(picker.startDate.format('YYYY-MM-DD'));
});

$.post("../../controller/empleado.php?op=getEmployee", function (data) {
  var empleado = JSON.parse(data);

  if (empleado.error) {
    console.error(empleado.error);
  } else {
    // Llena tus inputs con la información
    $('#empleado_codi').val(empleado.id_empl);
    $('#empleado_id').val(empleado.nomb_empl);
    $('#empleado_cargo').val(empleado.nomb_carg);
  }
});

$.post("../../controller/tipo_permiso.php?op=comboTipoPermiso", function (data, status) {
  var $tipoDocumentoEmpl = $('#permiso_motivo');
  $tipoDocumentoEmpl.html(data);
});


$(document).ready(function () {

  const opcionesHora = {
    singleDatePicker: true,
    timePicker: true,
    timePicker24Hour: false,
    timePickerIncrement: 1,
    timePickerSeconds: false,
    autoUpdateInput: true,
    locale: {
      format: 'HH:mm',
      applyLabel: 'Aceptar',
      cancelLabel: 'Cancelar',
      fromLabel: 'Desde',
      toLabel: 'Hasta',
      weekLabel: 'S',
      customRangeLabel: 'Personalizado',
      daysOfWeek: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'],
      monthNames: [
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
      ],
      firstDay: 1
    }
  };

  // Inicializar campos
  $('#timepicker_salida, #timepicker_entrada').each(function () {
    const $input = $(this);
    $input.daterangepicker(opcionesHora, function (start) {
      $input.val(start.format('HH:mm'));
    });

    // Al mostrar el picker, ocultamos solo la tabla de calendario,
    // pero NO el contenedor donde están los selectores de hora.
    $input.on('show.daterangepicker', function (ev, picker) {
      // Oculta solo el calendario, deja visible el bloque de hora
      picker.container.find('.calendar-table').css('display', 'none');
      picker.container.find('.drp-calendar.left').css('display', 'block');
      picker.container.find('.drp-calendar.right').css('display', 'none');
      picker.container.find('.ranges').css('display', 'none');

      // Ajuste visual del cuadro
      picker.container.css({
        width: '250px',
        textAlign: 'center',
        padding: '10px'
      });
    });

    // Valor inicial con hora actual
    $input.val(moment().format('HH:mm'));
  });

});


$("#btnRegistrarFirma").click(function () {
  $("#modalFirma").modal("show");
  inicializarFirma(); // cargamos la función al abrir
});

function inicializarFirma() {
  const canvas = document.getElementById("canvasFirma");
  const ctx = canvas.getContext("2d");
  let dibujando = false;

  ctx.lineWidth = 2;
  ctx.strokeStyle = "#000";
  ctx.lineJoin = "round";

  function getPos(e) {
    const rect = canvas.getBoundingClientRect();
    if (e.touches) {
      return {
        x: e.touches[0].clientX - rect.left,
        y: e.touches[0].clientY - rect.top
      };
    } else {
      return {
        x: e.clientX - rect.left,
        y: e.clientY - rect.top
      };
    }
  }

  function iniciarDibujo(e) {
    e.preventDefault();
    dibujando = true;
    const pos = getPos(e);
    ctx.beginPath();
    ctx.moveTo(pos.x, pos.y);
  }

  function dibujar(e) {
    if (!dibujando) return;
    e.preventDefault();
    const pos = getPos(e);
    ctx.lineTo(pos.x, pos.y);
    ctx.stroke();
  }

  function detenerDibujo(e) {
    e.preventDefault();
    dibujando = false;
  }

  // Eventos mouse
  canvas.addEventListener("mousedown", iniciarDibujo);
  canvas.addEventListener("mousemove", dibujar);
  canvas.addEventListener("mouseup", detenerDibujo);
  canvas.addEventListener("mouseleave", detenerDibujo);

  // Eventos touch
  canvas.addEventListener("touchstart", iniciarDibujo);
  canvas.addEventListener("touchmove", dibujar);
  canvas.addEventListener("touchend", detenerDibujo);
  canvas.addEventListener("touchcancel", detenerDibujo);

  // Limpiar
  $("#btnLimpiarFirma").off("click").on("click", function () {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    $("#previewFirma").html("");
  });

  // Guardar (solo visual por ahora)
  // Guardar firma en input + mostrar preview
  $("#btnGuardarFirma").off("click").on("click", function () {
    const firmaData = canvas.toDataURL("image/png");

    // Guardamos el valor en el input hidden
    $("#firma").val(firmaData);

    // Mostramos la imagen en el formulario
    $("#previewFirma").attr("src", firmaData);

    // Cambiamos estado de botones:
    $("#btnGuardar").prop("disabled", false);   // habilitamos registrar permiso
    $("#btnRegistrarFirma").prop("disabled", true);      // deshabilitamos registrar firma

    // Cerramos el modal
    $("#modalFirma").modal("hide");
  });
}

function guardar(e) {
  e.preventDefault(); // Evitar recarga
  // Validación: firma obligatoria
  if ($("#firma").val() === "") {
    Swal.fire({
      icon: "warning",
      title: "Falta la firma",
      text: "Por favor registre su firma antes de enviar el permiso.",
      confirmButtonColor: "#3085d6"
    });
    return false;
  }

  var formData = new FormData($("#form_permiso")[0]);

  $.ajax({
    url: "../../controller/permiso.php?op=guardarPermiso",
    type: "POST",
    data: formData,
    contentType: false,
    processData: false,
    beforeSend: function () {
      Swal.fire({
        title: "Guardando...",
        text: "Por favor espere un momento",
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });
    },
    success: function (response) {
      Swal.close(); // cerrar la animación de carga

      console.log("Respuesta del servidor:", response);
      var data = JSON.parse(response);

      if (data.success) {
        Swal.fire({
          icon: "success",
          title: "Permiso registrado",
          text: "El permiso se guardó correctamente.",
          confirmButtonColor: "#28a745"
        }).then(() => {
          // Resetear formulario y botones
          $("#form_permiso")[0].reset();
          $("#previewFirma").attr("src", "");
          $("#btnGuardar").prop("disabled", true);
          $("#btnRegistrarFirma").prop("disabled", false);

          // 🕐 Luego de un breve retardo, recargar la página
          setTimeout(function () {
            location.reload();
          }, 500); // medio segundo para que se note el reset
        });

      } else {
        Swal.fire({
          icon: "error",
          title: "Error al guardar",
          text: data.error || "No se pudo guardar el permiso.",
          confirmButtonColor: "#dc3545"
        });
      }
    },
    error: function (xhr, status, error) {
      Swal.close();
      Swal.fire({
        icon: "error",
        title: "Error AJAX",
        text: "Hubo un problema al enviar la solicitud.",
        footer: error,
        confirmButtonColor: "#dc3545"
      });
    }
  });


}

Dropzone.autoDiscover = false;

let permiso_token = $("#permiso_token").val();

let myDropzone = new Dropzone(".dropzone", {

  url: BASE_URL + "/controller/permiso.php?op=subirSoporte",
  maxFilesize: 10,
  acceptedFiles: ".jpg,.jpeg,.png,.pdf",
  addRemoveLinks: true,
  dictRemoveFile: "Eliminar",

  init: function () {

    var dz = this;

    this.on("sending", function (file, xhr, formData) {

      if (!permiso_token) {
        Swal.fire("Error", "Token no generado", "error");
        xhr.abort();
        return;
      }

      formData.append("permiso_token", permiso_token);
    });

    this.on("success", function (file, response) {

      // Swal.fire({
      //   icon: "success",
      //   title: "Archivo cargado",
      //   showConfirmButton: false,
      //   timer: 1000
      // });
      //
      // setTimeout(function () {
      //   dz.removeFile(file);
      // }, 1000);

      // SOLO MOSTRAR NOTIFICACIÓN, NO ELIMINAR
      Swal.fire({
        icon: "success",
        title: "Archivo cargado",
        showConfirmButton: false,
        timer: 1000
      });
      
      // El archivo SE QUEDA visible en Dropzone
    });

    this.on("error", function (file, message) {

      Swal.fire("Error", message, "error");

      // SOLO ELIMINAR SI HAY ERROR
      setTimeout(function () {
        dz.removeFile(file);
      }, 1000);
    });

  }

});



function cargarSoportes(permiso_id) {

  $.ajax({
    url: BASE_URL + "/controller/permiso.php?op=listarSoportes",
    type: "POST",
    data: { permiso_id: permiso_id },
    success: function (response) {

      let lista = JSON.parse(response);
      let html = "";

      lista.forEach(s => {

        html += `
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="../../controller/permiso.php?op=descargarSoporte&file=${encodeURIComponent(s.soporte_ruta)}" target="_blank">
                            ${s.soporte_nombre}
                        </a>
                        <span class="badge badge-secondary">${s.soporte_fecha}</span>
                    </li>
                `;
      });

      $("#listaSoportes").html(html);
    }
  });
}

init();




