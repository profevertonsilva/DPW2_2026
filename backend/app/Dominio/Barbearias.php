<?php

namespace App\Dominio;

use App\Http\Resources\AgendamentoResource;
use App\Models\Atendimento;
use App\Models\Auditoria;
use App\Models\CategoriaEstabelecimento;
use App\Models\Estabelecimento;
use App\Models\HorarioFuncionamento;
use App\Models\Jornada;
use App\Models\Login;
use App\Models\Profissional;
use App\Models\Servico;
use App\Models\Telefone;
use App\Models\VinculoEstabelecimento;
use Carbon\CarbonImmutable;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Cadastro da barbearia e gestão da equipe (seção 6.2, RF #09, #11, #13).
 */
class Barbearias
{
    /**
     * Cria, em uma transação: estabelecimento (categoria "Barbearia"), telefone,
     * sete horários, o profissional do usuário (se ainda não existir) e o
     * vínculo de proprietário.
     */
    public function cadastrar(Login $login, array $dados): Estabelecimento
    {
        return DB::transaction(function () use ($login, $dados) {
            $categoria = CategoriaEstabelecimento::firstOrCreate(['nome' => CategoriaEstabelecimento::BARBEARIA]);

            $estabelecimento = Estabelecimento::create([
                ...$this->camposDoEstabelecimento($dados),
                'categoria_estabelecimento_id' => $categoria->id,
            ]);

            Telefone::create(['estabelecimento_id' => $estabelecimento->id, 'telefone' => $dados['telefone']]);
            $this->gravarHorarios($estabelecimento, $dados['horarios']);

            VinculoEstabelecimento::create([
                'profissional_id' => $this->profissionalDe($login)->id,
                'estabelecimento_id' => $estabelecimento->id,
                'papel' => VinculoEstabelecimento::PROPRIETARIO,
                'status' => 'a',
            ]);

            return $estabelecimento;
        });
    }

    public function atualizar(Estabelecimento $estabelecimento, array $dados): Estabelecimento
    {
        return DB::transaction(function () use ($estabelecimento, $dados) {
            $antes = $this->retrato($estabelecimento);
            $estabelecimento->update($this->camposDoEstabelecimento($dados));

            $telefone = $estabelecimento->telefones()->first();
            $telefone
                ? $telefone->update(['telefone' => $dados['telefone']])
                : Telefone::create(['estabelecimento_id' => $estabelecimento->id, 'telefone' => $dados['telefone']]);

            $this->gravarHorarios($estabelecimento, $dados['horarios']);
            $estabelecimento->refresh();

            // RF #38: só os campos que mudaram.
            $depois = $this->retrato($estabelecimento);
            $mudou = array_keys(array_diff_assoc($depois, $antes));
            if ($mudou !== []) {
                Trilha::registrar($estabelecimento->id, Auditoria::ALTERACAO_ESTABELECIMENTO, 'estabelecimento', $estabelecimento->id,
                    array_intersect_key($antes, array_flip($mudou)), array_intersect_key($depois, array_flip($mudou)));
            }

            return $estabelecimento;
        });
    }

    /** Dados cadastrais em texto, para comparar antes e depois (auditoria). */
    private function retrato(Estabelecimento $e): array
    {
        $dias = ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sab'];
        $horarios = HorarioFuncionamento::where('estabelecimento_id', $e->id)->orderBy('dia_semana')->get();

        return [
            'nome' => $e->nome,
            'descricao' => (string) $e->descricao,
            'telefone' => (string) $e->telefones()->value('telefone'),
            'endereco' => "{$e->logradouro}, {$e->numero} {$e->complemento} - {$e->bairro}, {$e->cidade}/{$e->estado}, {$e->cep}",
            ...collect($horarios)->mapWithKeys(fn ($h) => [
                'horario_'.$dias[$h->dia_semana] => $h->aberto ? substr($h->hora_abertura, 0, 5).'-'.substr($h->hora_fechamento, 0, 5) : 'fechado',
            ])->all(),
        ];
    }

    /**
     * Adiciona à equipe alguém que já tem conta (RF #11), com o papel inicial
     * (padrão: barbeiro). Quem pode atribuir cada papel é verificado no controller.
     */
    public function adicionarBarbeiro(Estabelecimento $estabelecimento, string $email, string $papel = VinculoEstabelecimento::PROFISSIONAL): VinculoEstabelecimento
    {
        $login = Login::where('email', mb_strtolower(trim($email)))->first();

        if (! $login) {
            throw ValidationException::withMessages([
                'email' => 'Nenhuma conta encontrada com este e-mail. Peça para a pessoa se cadastrar no AgendAki e tente de novo.',
            ]);
        }

        return DB::transaction(function () use ($estabelecimento, $login, $papel) {
            $profissional = $this->profissionalDe($login);

            $jaVinculado = VinculoEstabelecimento::ativos()
                ->where('estabelecimento_id', $estabelecimento->id)
                ->where('profissional_id', $profissional->id)
                ->exists();

            if ($jaVinculado) {
                throw ValidationException::withMessages(['email' => 'Esta pessoa já faz parte da equipe.']);
            }

            return VinculoEstabelecimento::create([
                'profissional_id' => $profissional->id,
                'estabelecimento_id' => $estabelecimento->id,
                'papel' => $papel,
                'status' => 'a',
            ]);
        });
    }

    /** Alteração de cargo (RF #12). Quem pode fazer cada mudança é verificado no controller. */
    public function alterarPapel(VinculoEstabelecimento $vinculo, string $papel): VinculoEstabelecimento
    {
        if ($vinculo->proprietario()) {
            throw ValidationException::withMessages(['papel' => 'O papel do proprietário não pode ser alterado.']);
        }

        $anterior = $vinculo->papel;
        if ($anterior === $papel) {
            return $vinculo;
        }

        DB::transaction(function () use ($vinculo, $papel, $anterior) {
            $vinculo->update(['papel' => $papel]);

            Trilha::registrar($vinculo->estabelecimento_id, Auditoria::ALTERACAO_CARGO, 'vinculo_estabelecimento', $vinculo->id,
                ['colaborador' => $vinculo->profissional->nome, 'papel' => $anterior], ['papel' => $papel]);
        });

        return $vinculo;
    }

    /** Encerra o vínculo sem apagar registros. O do proprietário não pode ser removido. */
    public function removerBarbeiro(VinculoEstabelecimento $vinculo): VinculoEstabelecimento
    {
        if ($vinculo->proprietario()) {
            throw ValidationException::withMessages(['vinculo' => 'O vínculo do proprietário não pode ser removido.']);
        }

        if (! $vinculo->ativo()) {
            throw ValidationException::withMessages(['vinculo' => 'Este barbeiro já foi removido da equipe.']);
        }

        // Decisão da equipe (DECISOES 4.13, referência RF #14): a remoção não
        // cancela agendamentos automaticamente; o dono cancela antes.
        $pendentes = $vinculo->atendimentos()
            ->where('status', Atendimento::AGENDADO)
            ->where('inicio', '>', CarbonImmutable::now())
            ->with(AgendamentoResource::RELACOES)
            ->orderBy('inicio')
            ->get();

        if ($pendentes->isNotEmpty()) {
            throw new HttpResponseException(response()->json([
                'message' => 'Este barbeiro tem agendamentos futuros. Cancele-os antes de removê-lo da equipe.',
                'errors' => ['vinculo' => ['Este barbeiro tem agendamentos futuros. Cancele-os antes de removê-lo da equipe.']],
                'agendamentos' => AgendamentoResource::collection($pendentes)->resolve(),
            ], 422));
        }

        DB::transaction(function () use ($vinculo) {
            $vinculo->update(['status' => 'i', 'desvinculado_em' => CarbonImmutable::now()]);

            Trilha::registrar($vinculo->estabelecimento_id, Auditoria::REMOCAO_COLABORADOR, 'vinculo_estabelecimento', $vinculo->id,
                ['colaborador' => $vinculo->profissional->nome, 'papel' => $vinculo->papel, 'status' => 'ativo'],
                ['status' => 'removido']);
        });

        return $vinculo;
    }

    /** @param list<int> $servicoIds */
    public function definirServicos(VinculoEstabelecimento $vinculo, array $servicoIds): void
    {
        $validos = Servico::ativos()
            ->where('estabelecimento_id', $vinculo->estabelecimento_id)
            ->whereIn('id', $servicoIds)
            ->pluck('id')
            ->all();

        $invalidos = array_values(array_diff($servicoIds, $validos));
        if ($invalidos !== []) {
            throw ValidationException::withMessages([
                'servico_ids' => 'Serviço não encontrado nesta barbearia ou inativo: '.implode(', ', $invalidos).'.',
            ]);
        }

        $vinculo->servicos()->sync($validos);
    }

    /**
     * Substitui a jornada do barbeiro. Lista vazia = sem jornada própria
     * (vale o horário de funcionamento da barbearia).
     *
     * @param  list<array{dia_semana: int, hora_inicio: string, hora_fim: string}>  $faixas
     */
    public function definirJornada(VinculoEstabelecimento $vinculo, array $faixas): void
    {
        DB::transaction(function () use ($vinculo, $faixas) {
            Jornada::where('vinculo_estabelecimento_id', $vinculo->id)->delete();

            foreach ($faixas as $faixa) {
                Jornada::create([
                    'vinculo_estabelecimento_id' => $vinculo->id,
                    'dia_semana' => $faixa['dia_semana'],
                    'hora_inicio' => $faixa['hora_inicio'],
                    'hora_fim' => $faixa['hora_fim'],
                ]);
            }
        });
    }

    /** Profissional do login; criado a partir dos dados do cliente se ainda não existir. */
    public function profissionalDe(Login $login): Profissional
    {
        if ($login->profissional) {
            return $login->profissional;
        }

        $cliente = $login->cliente;

        $profissional = $login->profissional()->create([
            'nome' => $cliente->nome,
            'telefone' => $cliente->telefone,
            'email' => $login->email,
            'status' => 'a',
        ]);

        // Mantém o objeto do login coerente para o restante da requisição.
        $login->setRelation('profissional', $profissional);

        return $profissional;
    }

    private function camposDoEstabelecimento(array $dados): array
    {
        return [
            'nome' => $dados['nome'],
            'descricao' => $dados['descricao'] ?? null,
            'cep' => $dados['cep'],
            'estado' => mb_strtoupper($dados['estado']),
            'cidade' => $dados['cidade'],
            'bairro' => $dados['bairro'],
            'logradouro' => $dados['logradouro'],
            'numero' => $dados['numero'],
            'complemento' => $dados['complemento'] ?? null,
        ];
    }

    /** Uma linha por dia da semana (0 a 6), atualizada ou criada. */
    private function gravarHorarios(Estabelecimento $estabelecimento, array $horarios): void
    {
        foreach ($horarios as $horario) {
            $aberto = (bool) $horario['aberto'];

            HorarioFuncionamento::updateOrCreate(
                ['estabelecimento_id' => $estabelecimento->id, 'dia_semana' => (int) $horario['dia_semana']],
                [
                    'aberto' => $aberto,
                    'hora_abertura' => $aberto ? $horario['hora_abertura'] : null,
                    'hora_fechamento' => $aberto ? $horario['hora_fechamento'] : null,
                ],
            );
        }
    }
}
