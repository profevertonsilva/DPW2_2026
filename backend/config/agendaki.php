<?php

return [

    // Intervalo, em minutos, entre os inícios possíveis na grade de horários (RF #19).
    'intervalo_minutos' => (int) env('AGENDAKI_INTERVALO_MINUTOS', 30),

    // Quantidade máxima de horários alternativos devolvidos em um conflito (409).
    'alternativas' => 4,

];
