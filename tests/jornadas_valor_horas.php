<?php
require_once __DIR__ . '/../models/JornadaValorHoras.php';

class ValorHorasPrueba extends JornadaValorHoras
{
    public $consultas = 0;
    public function consultar_salario($cedula)
    {
        $this->consultas++;
        return $cedula === '123' ? 2200000 : null;
    }
}
function verificarValor($condicion, $mensaje)
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}
$modelo = new ValorHorasPrueba();
$base = ['documento' => '123', 'rn' => 5, 'hed' => 1 / 60, 'hen' => 2, 'hedf' => 3, 'henf' => 4];
$sinSalario = array_merge($base, ['documento' => '456']);
$filas = $modelo->valorar([$base, $base, $sinSalario]);
verificarValor($modelo->consultas === 2, 'Una consulta por cédula.');
verificarValor($filas[0]['valor_hora'] == 10000.05, 'Valor hora IBC / 30 / 7.3333.');
verificarValor($filas[0]['valor_hed'] == 208.33, 'No redondear horas antes de valorar.');
verificarValor($filas[0]['total'] == 195209.21, 'Total de los cuatro conceptos sin redondear previamente el valor hora.');
verificarValor($filas[0]['valor_rn'] === null && $filas[0]['rn'] === 5, 'RN sin cálculo conserva horas.');
verificarValor($filas[2]['ibc'] === null && $filas[2]['total'] === null, 'Salario ausente no se convierte en cero.');
echo "OK: factores, precisión, recargo pendiente, salario ausente y caché.\n";
