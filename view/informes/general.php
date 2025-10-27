<?php

    require_once('../../config/conexion.php');
    require_once('../../models/Informes.php');

    $informes = new Informes();
    $datos = $informes->getInfomeGeneral();

    require_once('../../public/assets/tcpdf/tcpdf.php');

    // create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, 'LETTER', true, 'UTF-8', false);

    // set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Nicola Asuni');
    $pdf->SetTitle('Evaluacion Desempeño 2023');


    // set default header data
    $pdf->SetHeaderData(false);
    $pdf->setFooterData(false);

    // set header and footer fonts
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

    // set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

    // set margins
    $pdf->SetMargins(10,10,10);

    // set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    // set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    // set some language-dependent strings (optional)
    if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
        require_once(dirname(__FILE__).'/lang/eng.php');
        $pdf->setLanguageArray($l);
    }

    // ---------------------------------------------------------

    // set default font subsetting mode
    $pdf->setFontSubsetting(true);

    // Set font
    // dejavusans is a UTF-8 Unicode font, if you only need to
    // print standard ASCII chars, you can use core fonts like
    // helvetica or times to reduce file size.
    $pdf->SetFont('dejavusans', '', 11, '', true);

    // Add a page
    // This method has several options, check the source code documentation for more information.
    $pdf->AddPage();

    
    // Set some content to print
    $html = '
    <style>
      .center-table {
        margin-left: auto;
        margin-right: auto;
      }
      table {
          width: 100%;
          border-collapse: collapse;
          font-size: 10pt;
      }
      th, td {
          padding: 5px;
      }
      th {
          background-color: #d3d3d3;
          font-weight: bold;
          text-align: center;
      }
      .vertical-text {
          writing-mode: vertical-lr;
          transform: rotate(180deg);
          text-align: center;
      }
      .subtotal {
          text-align: right;
          font-weight: bold;
      }
    </style>
    
    <div>
      <table border="">


          <tr>
            <td style="width: 20%;"><img src="../../public/img/logo asft vertical@3x.png" alt="" style="width: 110px; align-items: center;"></td>

            

            <td class="title" colspan="2" style="width: 50%; text-align: center; margin: auto">
              <p style="font-size: 18px;">EVALUACIÓN DE DESEMPEÑO</p>
            </td> 

            

            <td rowspan="4" style="width: 30%; margin-bottom: 1.5rem;">
              <table class="center-table" style="text-align: center; font-size: 10.3px;">

                <tr style="margin-top: 5rem;">
                  <td>Version</td>
                  <td>7</td>
                </tr>
                <tr>
                  <td>Implementación:</td>
                  <td>Abril 20 de 2023</td>
                </tr>
                <tr>
                  <td>Código: </td>
                  <td>GH-F-4</td>
                </tr>
                <tr>
                  <td>Tipo Documento: </td>
                  <td style="vertical-align: middle;">Formato</td>
                </tr>
              </table>
            </td>
          </tr>
        </table>
      </div>


    ';

    $html.='
    

    <div>
      <table border="1">

        <tr>

          <td style="width: 25%;" >Impresión: 01/08/2023 - 11:38:15</td>
          <td style="width: 50%;"> ANGEL MARIA MUÑOZ GARCIA</td>
          <td style="width: 25%;"> Página 1 de 3</td>

        </tr>

      </table>
      
    </div>

    
    ';

    $html.='

    <div>
      <table border="1" style="padding: 4px;">

        <tr>

          <td style="text-align: center;">INFORMACION GENERAL</td>

        </tr>

      </table>
    <div>

    
    
    ';

    $html.='

    <div>

    <table border="1" style="padding: 2px;">


      <tr>
        <td style="text-align: center;">Evaluado:</td>
      </tr>

    </table>


          <table border="1" style="padding: 3px;">
            <tr>
              <td>Periodo</td>
              <td>Nombre</td>
              <td>Cargo</td>
            </tr>
            <tr>
              <td>2022</td>
              <td>ANGEL MARIA MUÑOZ GARCIA</td>
              <td>OFICIAL DE OBRA</td>
            </tr>
          </table>
    </div>
    
    ';

    $html.='


      <div>
        <table border="1">

          <tr>
          
            <th style= "width: 15%; text-align: center; vertical-align: middle;"><strong>Areas</strong></th>
            <th style= "width: 70%; text-align: center; vertical-align: middle;"><strong>Descripcion</strong></th>
            <th style= "width: 15%; text-align: center; vertical-align: middle;"><strong>Calificacion</strong></th>
          
          </tr>

          <tbody>

            <tr>
              <td rowspan="7" style="vertical-align: middle;" class="vertical-text">
                PRODUCTIVIDAD</td>
                <td><strong>Conocimiento:</strong> conocimiento y habilidades para el desempeño, cumpliendo con las actividades del cargo y
                calidad del trabajo.</td>
              <td style="text-align: center; vertical-align: middle;">3</td>
            </tr>
            <tr>
              <td><strong>Planeación y dirección:</strong> determinación de metas y prioridades institucionales, cumplimiento con las
                metas, objetivos y planes establecidos relacionadas con su cargo. Determinación de planes y objetivos
                claros coherentes con las metas.</td>
              <td style="text-align: center; vertical-align: middle;">3</td>
            </tr>
            <tr>
              <td><strong>Planeación y organización:</strong> determina eficazmente las metas, objetivos y prioridades, estipulando la
                acción, los plazos y los recursos requeridos para su labor. determina mecanismos y seguimiento para su
                labor, los cuales le permiten obtener los resultados esperados.</td>
              <td style="text-align: center; vertical-align: middle;">3</td>
            </tr>
            <tr>
              <td><strong>Capacidad de organización del trabajo:</strong> la disposición y habilidad para crear las condiciones adecuadas
              de utilización de todos los recursos para el desarrollo y cumplimiento de las actividades de su cargo,
              dando atención y ejecución de solicitudes verbales.</td>
              <td style="text-align: center; vertical-align: middle;">3</td>
            </tr>
            <tr>
              <td><strong>Orientación al servicio:</strong> poseen un trato cordial y amable, se interesan por el cliente como persona, se
              preocupan por entender las necesidades de los clientes internos y externos y dar soluciones a sus
              problemas, colabora con los clientes internos en la realización de las labores.</td>
              <td style="text-align: center; vertical-align: middle;">3</td>
            </tr>
            <tr>
              <td><strong>Capacidad de iniciativa y/o innovación:</strong> disposición para tomar decisiones y encaminarlas en propuestas o
              acciones, que permitan mejorar las labores desempeñadas. aplicación y asimilación de nueva información y/o
              tecnología.</td>
              <td style="text-align: center; vertical-align: middle;">3</td>
            </tr>
            <tr>
              <td style="text-align: right;"><strong>Subtotal</strong></td>
              <td style="text-align: center; vertical-align: middle;">3</td>
            </tr>


          </tbody>
        </table>
      </div>
    
    
    ';

    $html.= '
    
    <div>
      <table border="1">

        <tr>
        
          <th style= "width: 15%; text-align: center; vertical-align: middle;"><strong>Areas</strong></th>
          <th style= "width: 70%; text-align: center; vertical-align: middle;"><strong>Descripcion</strong></th>
          <th style= "width: 15%; text-align: center; vertical-align: middle;"><strong>Calificacion</strong></th>
        
        </tr>

        <tbody>

          <tr>
            <td rowspan="7" style="vertical-align: middle;">CONDUCTA LABORAL</td>
            <td><strong>Conocimiento:</strong> conocimiento y habilidades para el desempeño, cumpliendo con las actividades del cargo y
            calidad del trabajo.</td>
            <td style="text-align: center; vertical-align: middle;">3</td>
          </tr>
          


        </tbody>
      </table>
    </div>
          
    
    
    ';


    // Print text using writeHTMLCell()
    // output the HTML content
    $pdf->writeHTML($html, true, false, true, false, '');

    $pdf->AddPage();

    $html2 = '

    <div>
      <table border="1">

        <tr>
        
          <th style= "width: 15%; text-align: center; vertical-align: middle;"><strong>Areas</strong></th>
          <th style= "width: 70%; text-align: center; vertical-align: middle;"><strong>Descripcion</strong></th>
          <th style= "width: 15%; text-align: center; vertical-align: middle;"><strong>Calificacion</strong></th>
        
        </tr>

        <tbody>

        <tr>
          <td rowspan="7" style="vertical-align: middle;">CONDUCTA LABORAL</td>
          <td><strong>Planeación y dirección:</strong> determinación de metas y prioridades institucionales, cumplimiento con las
            metas, objetivos y planes establecidos relacionadas con su cargo. Determinación de planes y objetivos
            claros coherentes con las metas.</td>
          <td style="text-align: center; vertical-align: middle;">3</td>
        </tr>
        <tr>
          <td><strong>Planeación y organización:</strong> determina eficazmente las metas, objetivos y prioridades, estipulando la
            acción, los plazos y los recursos requeridos para su labor. determina mecanismos y seguimiento para su
            labor, los cuales le permiten obtener los resultados esperados.</td>
          <td style="text-align: center; vertical-align: middle;">3</td>
        </tr>
        <tr>
          <td><strong>Capacidad de organización del trabajo:</strong> la disposición y habilidad para crear las condiciones adecuadas
          de utilización de todos los recursos para el desarrollo y cumplimiento de las actividades de su cargo,
          dando atención y ejecución de solicitudes verbales.</td>
          <td style="text-align: center; vertical-align: middle;">3</td>
        </tr>
        <tr>
          <td><strong>Orientación al servicio:</strong> poseen un trato cordial y amable, se interesan por el cliente como persona, se
          preocupan por entender las necesidades de los clientes internos y externos y dar soluciones a sus
          problemas, colabora con los clientes internos en la realización de las labores.</td>
          <td style="text-align: center; vertical-align: middle;">3</td>
        </tr>
        <tr>
          <td><strong>Capacidad de iniciativa y/o innovación:</strong> disposición para tomar decisiones y encaminarlas en propuestas o
          acciones, que permitan mejorar las labores desempeñadas. aplicación y asimilación de nueva información y/o
          tecnología.</td>
          <td style="text-align: center; vertical-align: middle;">3</td>
        </tr>
        <tr>
          <td style="text-align: right;"><strong>Subtotal</strong></td>
          <td style="text-align: center; vertical-align: middle;">3</td>
        </tr>

        </tbody>

      </table>
    </div>

    ';

    $html2.='
    
    <div>
      <table border="1">

        <tr>
        
          <th style= "width: 15%; text-align: center; vertical-align: middle;"><strong>Areas</strong></th>
          <th style= "width: 70%; text-align: center; vertical-align: middle;"><strong>Descripcion</strong></th>
          <th style= "width: 15%; text-align: center; vertical-align: middle;"><strong>Calificacion</strong></th>
        
        </tr>

        <tbody>

        <tr>
          <td rowspan="5" style="vertical-align: middle;">ACTITUD</td>
          <td><strong>Planeación y dirección:</strong> determinación de metas y prioridades institucionales, cumplimiento con las
            metas, objetivos y planes establecidos relacionadas con su cargo. Determinación de planes y objetivos
            claros coherentes con las metas.</td>
          <td style="text-align: center; vertical-align: middle;">3</td>
        </tr>
        <tr>
          <td><strong>Planeación y organización:</strong> determina eficazmente las metas, objetivos y prioridades, estipulando la
            acción, los plazos y los recursos requeridos para su labor. determina mecanismos y seguimiento para su
            labor, los cuales le permiten obtener los resultados esperados.</td>
          <td style="text-align: center; vertical-align: middle;">3</td>
        </tr>
        <tr>
          <td><strong>Capacidad de organización del trabajo:</strong> la disposición y habilidad para crear las condiciones adecuadas
          de utilización de todos los recursos para el desarrollo y cumplimiento de las actividades de su cargo,
          dando atención y ejecución de solicitudes verbales.</td>
          <td style="text-align: center; vertical-align: middle;">3</td>
        </tr>
        <tr>
          <td><strong>Orientación al servicio:</strong> poseen un trato cordial y amable, se interesan por el cliente como persona, se
          preocupan por entender las necesidades de los clientes internos y externos y dar soluciones a sus
          problemas, colabora con los clientes internos en la realización de las labores.</td>
          <td style="text-align: center; vertical-align: middle;">3</td>
        </tr>
        <tr>
          <td style="text-align: right;"><strong>Subtotal</strong></td>
          <td style="text-align: center; vertical-align: middle;">3</td>
        </tr>

        </tbody>

      </table>
    </div>
    
    ';



    $pdf->writeHTML($html2, true, false, true, false, '');

    $pdf->AddPage();

    $html3 = '
    
    <div>
      <table border="1">

        <tr>
        
          <th style= "width: 15%; text-align: center; vertical-align: middle;"><strong>Areas</strong></th>
          <th style= "width: 70%; text-align: center; vertical-align: middle;"><strong>Descripcion</strong></th>
          <th style= "width: 15%; text-align: center; vertical-align: middle;"><strong>Calificacion</strong></th>
        
        </tr>

        <tbody>

          <tr>
            <td rowspan="6" style="vertical-align: middle;">LIDERAZGO</td>
            <td><strong>Planeación y dirección:</strong> determinación de metas y prioridades institucionales, cumplimiento con las
              metas, objetivos y planes establecidos relacionadas con su cargo. Determinación de planes y objetivos
              claros coherentes con las metas.</td>
            <td style="text-align: center; vertical-align: middle;">3</td>
          </tr>
          <tr>
            <td><strong>Planeación y organización:</strong> determina eficazmente las metas, objetivos y prioridades, estipulando la
              acción, los plazos y los recursos requeridos para su labor. determina mecanismos y seguimiento para su
              labor, los cuales le permiten obtener los resultados esperados.</td>
            <td style="text-align: center; vertical-align: middle;">3</td>
          </tr>
          <tr>
            <td><strong>Capacidad de organización del trabajo:</strong> la disposición y habilidad para crear las condiciones adecuadas
            de utilización de todos los recursos para el desarrollo y cumplimiento de las actividades de su cargo,
            dando atención y ejecución de solicitudes verbales.</td>
            <td style="text-align: center; vertical-align: middle;">3</td>
          </tr>
          <tr>
            <td><strong>Orientación al servicio:</strong> poseen un trato cordial y amable, se interesan por el cliente como persona, se
            preocupan por entender las necesidades de los clientes internos y externos y dar soluciones a sus
            problemas, colabora con los clientes internos en la realización de las labores.</td>
            <td style="text-align: center; vertical-align: middle;">3</td>
          </tr>
          <tr>
            <td><strong>Orientación al servicio:</strong> poseen un trato cordial y amable, se interesan por el cliente como persona, se
            preocupan por entender las necesidades de los clientes internos y externos y dar soluciones a sus
            problemas, colabora con los clientes internos en la realización de las labores.</td>
            <td style="text-align: center; vertical-align: middle;">3</td>
          </tr>
          <tr>
            <td style="text-align: right;"><strong>Subtotal</strong></td>
            <td style="text-align: center; vertical-align: middle;">3</td>
          </tr>

        </tbody>

      </table>
    </div>
    
    ';

    $html3.='
    <div>
      <table border="1">

      <tr>
        
        <th style= "text-align: center; vertical-align: middle;"><strong>Observaciones</strong></th>
            
      </tr>

      <tbody>

        <tr>
          <td style= "height: 150px; text-align: center; vertical-align: middle;"></td> 
        </tr>

      </tbody>

      
      </table>
    </div>
    
    ';


    $html3.='
    <div>
      <table border="1">
        <tr>
            <th colspan="3" class="title" style="text-align: center; vertical-align: middle;"><strong>INTERPRETACIÓN DE LA EVALUACIÓN DE DESEMPEÑO</strong></th>
        </tr>

        <tr>
          <td colspan="3">Para efectos de las decisiones que se deriven de la evaluación del desempeño, se tienen en
          cuenta los siguientes grados:</td>
        </tr>

        <tr>
            <th style="text-align: center; vertical-align: middle;"><strong>EXCELENTE</strong></th>
            <th style="text-align: center; vertical-align: middle;"><strong>BUENO</strong></th>
            <th style="text-align: center; vertical-align: middle;"><strong>DEFICIENTE</strong></th>
        </tr>
        <tr>
            <td style="text-align: center; vertical-align: middle;">50 A 63</td>
            <td style="text-align: center; vertical-align: middle;">22 A 49</td>
            <td style="text-align: center; vertical-align: middle;">1 A 21</td>
        </tr>
        <!-- Añade más filas según sea necesario -->
      </table>
    </div>
    
    ';

    $pdf->writeHTML($html3, true, false, true, false, '');


    // ---------------------------------------------------------

    // Close and output PDF document
    // This method has several options, check the source code documentation for more information.
    $pdf->Output('example_001.pdf', 'I');

    //============================================================+
    // END OF FILE
    //============================================================+



?>