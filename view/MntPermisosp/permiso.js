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


$(document).ready(function() {

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
        'Enero','Febrero','Marzo','Abril','Mayo','Junio',
        'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'
      ],
      firstDay: 1
    }
  };

  // Inicializar campos
  $('#timepicker_salida, #timepicker_entrada').each(function() {
    const $input = $(this);
    $input.daterangepicker(opcionesHora, function(start) {
      $input.val(start.format('HH:mm'));
    });

    // Al mostrar el picker, ocultamos solo la tabla de calendario,
    // pero NO el contenedor donde están los selectores de hora.
    $input.on('show.daterangepicker', function(ev, picker) {
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




