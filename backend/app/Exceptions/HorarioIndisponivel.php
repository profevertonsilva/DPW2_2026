<?php

namespace App\Exceptions;

use App\Support\Datas;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * Horário ocupado (RF #36): pela verificação prévia ou pela restrição de
 * exclusão do banco. Responde 409 com horários alternativos (seção 7).
 */
class HorarioIndisponivel extends RuntimeException
{
    /** @param list<CarbonImmutable> $alternativas */
    public function __construct(public readonly array $alternativas = [])
    {
        parent::__construct('Este horário acabou de ser preenchido.');
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'alternativas' => array_map(fn (CarbonImmutable $h) => Datas::iso($h), $this->alternativas),
        ], 409);
    }
}
