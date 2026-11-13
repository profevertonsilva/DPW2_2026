<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Cadastro e edição da barbearia (RF #09): nome, telefone, CEP, UF, cidade,
 * bairro, logradouro e número obrigatórios, e o horário dos sete dias.
 */
class BarbeariaRequest extends FormRequest
{
    public const UFS = [
        'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA',
        'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO',
    ];

    protected function prepareForValidation(): void
    {
        if (is_string($this->estado)) {
            $this->merge(['estado' => mb_strtoupper(trim($this->estado))]);
        }
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:100'],
            'descricao' => ['nullable', 'string', 'max:2000'],
            'telefone' => ['required', 'string', 'max:15', 'regex:/^[0-9()+\-\s]{8,15}$/'],
            'cep' => ['required', 'string', 'regex:/^\d{5}-?\d{3}$/'],
            'estado' => ['required', 'string', 'in:'.implode(',', self::UFS)],
            'cidade' => ['required', 'string', 'max:50'],
            'bairro' => ['required', 'string', 'max:50'],
            'logradouro' => ['required', 'string', 'max:100'],
            'numero' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'complemento' => ['nullable', 'string', 'max:255'],
            'horarios' => ['required', 'array', 'size:7'],
            'horarios.*.dia_semana' => ['required', 'integer', 'between:0,6', 'distinct'],
            'horarios.*.aberto' => ['required', 'boolean'],
            'horarios.*.hora_abertura' => ['nullable', 'required_if_accepted:horarios.*.aberto', 'date_format:H:i'],
            'horarios.*.hora_fechamento' => ['nullable', 'required_if_accepted:horarios.*.aberto', 'date_format:H:i'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                foreach ((array) $this->input('horarios', []) as $i => $h) {
                    $abre = $h['hora_abertura'] ?? null;
                    $fecha = $h['hora_fechamento'] ?? null;
                    if (filter_var($h['aberto'] ?? false, FILTER_VALIDATE_BOOL) && is_string($abre) && is_string($fecha) && $fecha <= $abre) {
                        $validator->errors()->add(
                            "horarios.$i.hora_fechamento",
                            'O horário de fechamento deve ser depois da abertura (o expediente não atravessa a meia-noite).',
                        );
                    }
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'horarios.*.dia_semana' => 'dia da semana',
            'horarios.*.aberto' => 'aberto',
            'horarios.*.hora_abertura' => 'horário de abertura',
            'horarios.*.hora_fechamento' => 'horário de fechamento',
        ];
    }
}
