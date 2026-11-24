<?php

namespace App\Http\Middleware;

use App\Models\Estabelecimento;
use App\Models\VinculoEstabelecimento;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Isolamento entre barbearias (RF #37, RNF #02; seção 6.4 do briefing).
 *
 * Rotas do painel ficam sob /api/painel/estabelecimentos/{estabelecimento}.
 * Quem não tem vínculo ativo com essa barbearia recebe 404, como se ela não
 * existisse; quem tem vínculo, mas sem o papel exigido, recebe 403.
 * O vínculo encontrado fica disponível em $request->attributes->get('vinculo').
 *
 * Uso: ->middleware('vinculo') ou ->middleware('vinculo:proprietario').
 */
class VinculoNaBarbearia
{
    public function handle(Request $request, Closure $next, string ...$papeis): Response
    {
        $parametro = $request->route('estabelecimento');
        $estabelecimento = $parametro instanceof Estabelecimento ? $parametro : Estabelecimento::find($parametro);
        $profissional = $request->user()?->profissional;

        $vinculo = ($estabelecimento && $estabelecimento->status === 'a' && $profissional)
            ? VinculoEstabelecimento::ativos()
                ->where('estabelecimento_id', $estabelecimento->id)
                ->where('profissional_id', $profissional->id)
                ->first()
            : null;

        abort_if($vinculo === null, 404, 'Barbearia não encontrada.');

        abort_if(
            $papeis !== [] && ! in_array($vinculo->papel, $papeis, true),
            403,
            'Seu papel nesta barbearia não permite esta ação.',
        );

        $request->attributes->set('vinculo', $vinculo);

        return $next($request);
    }
}
