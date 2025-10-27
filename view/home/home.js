let tabla;
let ajaxTabla;
let ajaxTablaEvaluador;

function init() {
  // Llama a la función para obtener y dibujar el gráfico

}

var graficoEval = null;

$('.select2').select2();

$(document).ready(function() {

    $.post("../../controller/empleado.php?op=comboEmpleados",function(data, status){
        var $userEmpleado = $('#nomb_evaluad');
        console.log($userEmpleado);
        $userEmpleado.html(data);
    }); 
});

$('#btnBuscarEmpl').click(function() {
   
  var empleado = $("#nomb_evaluad").val();
  var idEmpleado =document.getElementById("idEmpleado");
  idEmpleado.value = empleado;
  obtenerDatosGrafico(idEmpleado.value);
  obtenerDatosGraficoSept(idEmpleado.value);
});

function obtenerDatosGrafico(idEmpleado) {
    $.post("../../controller/grafico.php?op=listar",{idEmpleado:idEmpleado},function(data){
      console.log("Datos recibidos:", data);
        crearGrafico(data);
  
      });


}

function obtenerDatosGraficoSept(idEmpleado) {
    $.post("../../controller/grafico.php?op=listarGrafSept",{idEmpleado:idEmpleado},function(data){
      console.log("Datos recibidos:", data);
        crearGraficoSept(data);
  
      });


}


function crearGrafico(datos) {
  var canvas = document.getElementById("graficoEval");
  if (!canvas) {
      console.error("No se encontró el canvas con el id 'graficoEval'.");
      return;
  }

  var ctx = canvas.getContext("2d");
  if (!ctx) {
      console.error("No se pudo obtener el contexto del canvas.");
      return;
  }

  // Destruir el gráfico anterior si existe
  if (graficoEval && graficoEval instanceof Chart) {
      console.log("Destruyendo el gráfico existente...");
      graficoEval.destroy();
  } else {
      console.log("No hay gráfico para destruir o no es una instancia de Chart.");
  }

  // Limpiar el canvas
  ctx.clearRect(0, 0, canvas.width, canvas.height);

  // Verificar la estructura de los datos
  if (Array.isArray(datos.valores) && Array.isArray(datos.labels)) {
      console.log("Datos válidos:", {
          labels: datos.labels,
          valores: datos.valores
      });

      // Convertir valores de strings a números
      var valoresNumericos = datos.valores.map(Number);

      // Crear un nuevo gráfico
      graficoEval = new Chart(ctx, {
          type: "line",
          data: {
              labels: datos.labels,
              datasets: [
                  {
                      label: "PUNTAJE ACUMULADO EMPLEADO",
                      data: valoresNumericos,
                      backgroundColor: "rgba(75, 192, 192, 0.2)",
                      borderColor: "rgba(75, 192, 192, 1)",
                      borderWidth: 3,
                      pointStyle: 'circle',
                      pointRadius: 10,
                      pointHoverRadius: 15
                  },
              ],
          },
          options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: (ctx) => 'Point Style: ' + ctx.chart.data.datasets[0].pointStyle,
                  }
          },
              scales: {
                  y: {
                      beginAtZero: true,
                  },
                  x: {
                    grid: {
                      offset: true,
                    }
                },
              },
          },
      });

      console.log("Nuevo gráfico creado:", graficoEval);
  } else {
      console.error("Los datos recibidos no tienen la estructura esperada.");
  }
}

function crearGraficoSept(datos) {
    var canvas = document.getElementById("graficoEvalSept");
    if (!canvas) {
        console.error("No se encontró el canvas con el id 'graficoEval'.");
        return;
    }
  
    var ctx = canvas.getContext("2d");
    if (!ctx) {
        console.error("No se pudo obtener el contexto del canvas.");
        return;
    }
  
    // Destruir el gráfico anterior si existe
    if (graficoEvalSept && graficoEvalSept instanceof Chart) {
        console.log("Destruyendo el gráfico existente...");
        graficoEvalSept.destroy();
    } else {
        console.log("No hay gráfico para destruir o no es una instancia de Chart.");
    }
  
    // Limpiar el canvas
    ctx.clearRect(0, 0, canvas.width, canvas.height);
  
    // Verificar la estructura de los datos
    if (Array.isArray(datos.valores) && Array.isArray(datos.labels)) {
        console.log("Datos válidos:", {
            labels: datos.labels,
            valores: datos.valores
        });
  
        // Convertir valores de strings a números
        var valoresNumericos = datos.valores.map(Number);
  
        // Crear un nuevo gráfico
        graficoEvalSept = new Chart(ctx, {
            type: "line",
            data: {
                labels: datos.labels,
                datasets: [
                    {
                        label: "PUNTAJE ACUMULADO EMPLEADO SEP-DIC",
                        data: valoresNumericos,
                        backgroundColor: "rgba(75, 192, 192, 0.2)",
                        borderColor: "rgba(75, 192, 192, 1)",
                        borderWidth: 3,
                        pointStyle: 'circle',
                        pointRadius: 10,
                        pointHoverRadius: 15
                    },
                ],
            },
            options: {
              responsive: true,
              plugins: {
                  title: {
                      display: true,
                      text: (ctx) => 'Point Style: ' + ctx.chart.data.datasets[0].pointStyle,
                    }
            },
                scales: {
                    y: {
                        beginAtZero: true,
                    },
                    x: {
                      grid: {
                        offset: true,
                      }
                  },
                },
            },
        });
  
        console.log("Nuevo gráfico creado 2:", graficoEvalSept);
    } else {
        console.error("Los datos recibidos no tienen la estructura esperada.");
    }
}

$(document).ready(function() {

  $.post("../../controller/evaluacion.php?op=comboMesTotal",function(data, status){
      var $mesEvalua = $('#mes_eval');
      console.log($mesEvalua);
      $mesEvalua.html(data);
  }); 

});

$(document).ready(function() {

    $.post("../../controller/evaluacion.php?op=comboMesTotal",function(data, status){
        var $mesEvalua = $('#txt_mes_eval');
        console.log($mesEvalua);
        $mesEvalua.html(data);
    }); 
    
});

$(document).ready(function() {

    $.post("../../controller/empleado.php?op=comboRol",function(data, status){
        var $userEvaluador = $('#mes_evaluador');
        console.log($userEvaluador);
        $userEvaluador.html(data);
    }); 
});

$('#btnBuscarSeguimiento').click(function() {
   
  var mes = $("#mes_eval").val();
  var anio = $("#mes_ano").val();
  var evaluador = $("#mes_evaluador").val();

  crearTabla(evaluador, mes, anio);
});

$('#btnBuscarEvaluador').click(function() {
   
    var mes = $("#txt_mes_eval").val();
    var anio = $("#txt_mes_ano").val();
  
    crearTablaCumplimiento(mes, anio);
  });

function crearTablaCumplimiento(mes, anio){

    console.log("Mes:", mes, "Año:", anio);

    if(mes <= 8 && anio == 2024){
        ajaxTabla= '../../controller/evaluacion.php?op=listarCumplimientoAgosto';
    }else{
        ajaxTabla= '../../controller/evaluacion.php?op=listarCumplimiento';
    }

    $('#cumplimiento_data').DataTable({
        "aProcessing": true,
        "aServerSide": true,
        "searching": true,
        dom: 'Bfrtip',
        lengthChange: false,
        colReorder: true,
        buttons: [		          
            'copyHtml5',
            'excelHtml5',
            'csvHtml5',
            'pdfHtml5'
        ],
        "ajax": {
            url: ajaxTabla,
            type: "post",
            dataType: "json",	
            data: {
                txt_mes_eval: mes,
                txt_mes_ano: anio
            }			    		
        },
        "bDestroy": true,
        "responsive": true,
        "bInfo": true,
        "iDisplayLength": 5,
        "autoWidth": false,
        "language": {
            "sProcessing": "Procesando...",
            "sLengthMenu": "Mostrar _MENU_ registros",
            "sZeroRecords": "No se encontraron resultados",
            "sEmptyTable": "Ningún dato disponible en esta tabla",
            "sInfo": "Mostrando un total de _TOTAL_ registros",
            "sInfoEmpty": "Mostrando un total de 0 registros",
            "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
            "sInfoPostFix": "",
            "sSearch": "Buscar:",
            "sUrl": "",
            "sInfoThousands": ",",
            "sLoadingRecords": "Cargando...",
            "oPaginate": {
                "sFirst": "Primero",
                "sLast": "Último",
                "sNext": "Siguiente",
                "sPrevious": "Anterior"
            },
            "oAria": {
                "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
                "sSortDescending": ": Activar para ordenar la columna de manera descendente"
            }
        }     
    });

}


function crearTabla(evaluador,mes,anio){

    if(mes <= 8 && anio == 2024){
        ajaxTablaEvaluador= '../../controller/evaluacion.php?op=listarEvaluacionMes';
    }else{
        ajaxTablaEvaluador= '../../controller/evaluacion.php?op=listarEvaluacionSeptiembre';
    }

    $('#avance_data').DataTable({
      "aProcessing": true,
      "aServerSide": true,
      "searching": true,
      dom: 'Bfrtip',
      lengthChange: false,
      colReorder: true,
      buttons: [		          
              'copyHtml5',
              'excelHtml5',
              'csvHtml5',
              'pdfHtml5'
              ],
      
      "ajax":{
          url:  ajaxTablaEvaluador,
          type : "post",
          dataType : "json",	
          data: {
            mes_evaluador:evaluador,
            mes_eval: mes,
            mes_ano: anio
        }			    		
      },


      
      "bDestroy": true,
      "responsive": true,
      "bInfo":true,
      "iDisplayLength": 10,
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

}


init();
