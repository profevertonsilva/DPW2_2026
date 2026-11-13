<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Jornada do barbeiro (RF #13): faixas por dia da semana. Pode haver mais de
 * uma faixa no mesmo dia, sem sobreposição. Lista vazia remove a jornada própria.
 */
class JornadaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'faixas' => ['present', 'array', 'max:50'],
            'faixas.*.dia_semana' => ['required', 'integer', 'between:0,6'],
            'faixas.*.hora_inicio' => ['required', 'date_format:H:i'],
            'faixas.*.hora_fim' => ['required', 'date_format:H:i'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $porDia = [];
                foreach ($this->input('faixas', []) as $i => $f) {
                    if ($f['hora_fim'] <= $f['hora_inicio']) {
                        $validator->errors()->add("faixas.$i.hora_fim", 'O fim da faixa deve ser depois do início.');

                        continue;
                    }
                    $porDia[(int) $f['dia_semana']][] = [$i, $f['hora_inicio'], $f['hora_fim']];
                }

                foreach ($porDia as $faixas) {
                    usort($faixas, fn ($a, $b) => $a[1] <=> $b[1]);
                    for ($k = 1; $k < count($faixas); $k++) {
                        if ($faixas[$k][1] < $faixas[$k - 1][2]) {
                            $validator->errors()->add(
                                "faixas.{$faixas[$k][0]}.hora_inicio",
                                'As faixas do mesmo dia não podem se sobrepor.',
                            );
                        }
                    }
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'faixas.*.dia_semana' => 'dia da semana',
            'faixas.*.hora_inicio' => 'início',
            'faixas.*.hora_fim' => 'fim',
        ];
    }
}
