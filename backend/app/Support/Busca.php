<?php

namespace App\Support;

use Illuminate\Contracts\Database\Query\Builder;

/**
 * Busca "contém", sem diferenciar maiúsculas, com o mesmo resultado em qualquer
 * locale do banco (C, C.UTF-8 ou pt_BR.UTF-8). O ILIKE converte maiúsculas
 * conforme a collation da coluna, e com a collation C ele não converte letras
 * acentuadas ("Á" não vira "á"). Aqui as duas pontas passam por lower() com a
 * collation ICU "und-x-icu", que segue as regras do Unicode. Acentos continuam
 * diferenciados: "aguas" não encontra "Águas" (DECISOES 9.1).
 */
final class Busca
{
    public const COLLATION = 'und-x-icu';

    /** Termo do LIKE: '%texto%', com %, _ e \ escapados. */
    public static function termo(string $texto): string
    {
        return '%'.addcslashes(trim($texto), '\\%_').'%';
    }

    /** Condição "a coluna contém o texto", sem diferenciar maiúsculas. */
    public static function contem(Builder $consulta, string $coluna, string $texto, string $boolean = 'and'): Builder
    {
        if (! preg_match('/^[a-z_][a-z0-9_]*$/', $coluna)) {
            throw new \InvalidArgumentException("Coluna inválida: {$coluna}");
        }

        $collation = self::COLLATION;

        return $consulta->whereRaw(
            "lower(\"{$coluna}\" collate \"{$collation}\") like lower(cast(? as text) collate \"{$collation}\")",
            [self::termo($texto)],
            $boolean,
        );
    }
}
