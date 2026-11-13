<?php

namespace Database\Seeders;

use App\Dominio\Barbearias;
use App\Dominio\Disponibilidade;
use App\Models\Atendimento;
use App\Models\Cliente;
use App\Models\Estabelecimento;
use App\Models\Login;
use App\Models\Servico;
use App\Models\VinculoEstabelecimento;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Dados de demonstração (seção 9.2 do briefing). Nomes e contatos fictícios.
 * As datas dos atendimentos são relativas ao dia em que o seeder roda
 * (ontem, hoje e amanhã), para que a demonstração sempre tenha agenda no dia.
 *
 * USO EXCLUSIVAMENTE LOCAL: todas as contas usam a senha SENHA abaixo,
 * documentada no README.
 */
class DemonstracaoSeeder extends Seeder
{
    public const SENHA = 'Agendaki@2026';

    private const DIAS_UTEIS = [1, 2, 3, 4, 5];

    public function __construct(
        private readonly Barbearias $barbearias,
        private readonly Disponibilidade $disponibilidade,
    ) {}

    public function run(): void
    {
        DB::transaction(function () {
            $central = $this->barbeariaCentral();
            $porto = $this->barbeariaDoPorto();

            $clienteTeste = $this->conta('Lucas Pereira', 'cliente@agendaki.test', '(19) 99800-0001');
            $outros = [
                $this->conta('Marina Costa', 'marina@agendaki.test', '(19) 99800-0002'),
                $this->conta('Pedro Henrique Rocha', 'pedro@agendaki.test', '(19) 99800-0003'),
                $this->conta('Thiago Martins', 'thiago@agendaki.test', '(19) 99800-0004'),
                $this->conta('Felipe Araújo', 'felipe@agendaki.test', '(19) 99800-0005'),
            ];

            $this->atendimentos($central, [$clienteTeste, ...$outros]);
            $this->atendimentos($porto, $outros);
            $this->avaliacoes();
        });
    }

    /** Avaliações (RF #30) para parte dos atendimentos realizados. */
    private function avaliacoes(): void
    {
        $exemplos = [
            [5, 'Atendimento impecável, saí no horário combinado.'],
            [4, 'Bom corte, mas atrasou uns 10 minutos.'],
            [5, 'Já é meu terceiro agendamento pelo app. Recomendo.'],
            [5, null],
        ];

        Atendimento::where('status', Atendimento::REALIZADO)->orderBy('id')->get()
            ->each(function (Atendimento $a, int $i) use ($exemplos) {
                [$nota, $comentario] = $exemplos[$i % count($exemplos)];
                $a->avaliacao()->create(['nota' => $nota, 'comentario' => $comentario, 'status' => 'a']);
            });
    }

    private function barbeariaCentral(): Estabelecimento
    {
        $dono = $this->conta('Carlos Mendes', 'dono.central@agendaki.test', '(19) 99700-0001');

        $estabelecimento = $this->barbearias->cadastrar($dono->login, [
            'nome' => 'Barbearia Central',
            'descricao' => 'Cortes clássicos e modernos no centro da cidade.',
            'telefone' => '(19) 3600-0001',
            'cep' => '13870-000',
            'estado' => 'SP',
            'cidade' => 'São João da Boa Vista',
            'bairro' => 'Centro',
            'logradouro' => 'Rua das Palmeiras',
            'numero' => 120,
            'complemento' => null,
            // Domingo aberto de manhã, para a demonstração ter agenda em qualquer dia.
            'horarios' => $this->semana(
                uteis: ['09:00', '19:00'],
                sabado: ['08:00', '17:00'],
                domingo: ['09:00', '13:00'],
            ),
        ]);

        $servicos = collect([
            ['Corte de cabelo', 'Corte na tesoura ou máquina.', 40.00, 30],
            ['Barba', 'Barba com toalha quente.', 30.00, 30],
            ['Corte e barba', 'Corte de cabelo e barba completa.', 65.00, 60],
            ['Degradê', 'Corte degradê com acabamento na navalha.', 45.00, 45],
            ['Pezinho', 'Acabamento do contorno.', 15.00, 15],
        ])->mapWithKeys(fn ($s) => [$s[0] => $estabelecimento->servicos()->create([
            'nome' => $s[0], 'descricao' => $s[1], 'preco' => $s[2], 'duracao_minutos' => $s[3], 'status' => 'a',
        ])]);

        // Rafael: sem jornada própria, segue o horário da barbearia.
        $rafael = $this->barbeiro($estabelecimento, 'Rafael Souza', 'rafael@agendaki.test', '(19) 99700-0002');
        $this->barbearias->definirServicos($rafael, $servicos->pluck('id')->all());

        // Bruno: folga às segundas-feiras.
        $bruno = $this->barbeiro($estabelecimento, 'Bruno Lima', 'bruno@agendaki.test', '(19) 99700-0003');
        $this->barbearias->definirServicos($bruno, $servicos->only(['Corte de cabelo', 'Corte e barba', 'Degradê', 'Pezinho'])->pluck('id')->all());
        $this->barbearias->definirJornada($bruno, [
            ...array_map(fn ($d) => ['dia_semana' => $d, 'hora_inicio' => '09:00', 'hora_fim' => '19:00'], [0, 2, 3, 4, 5, 6]),
        ]);

        // Diego: pausa para o almoço (duas faixas por dia); não trabalha aos domingos.
        $diego = $this->barbeiro($estabelecimento, 'Diego Alves', 'diego@agendaki.test', '(19) 99700-0004');
        $this->barbearias->definirServicos($diego, $servicos->only(['Corte de cabelo', 'Barba', 'Corte e barba'])->pluck('id')->all());
        $faixas = [];
        foreach ([1, 2, 3, 4, 5, 6] as $dia) {
            $faixas[] = ['dia_semana' => $dia, 'hora_inicio' => '09:00', 'hora_fim' => '12:00'];
            $faixas[] = ['dia_semana' => $dia, 'hora_inicio' => '13:00', 'hora_fim' => '19:00'];
        }
        $this->barbearias->definirJornada($diego, $faixas);

        // Atendente (RF #03): agenda de toda a barbearia, sem gestão.
        $this->conta('Júlia Ramos', 'atendente@agendaki.test', '(19) 99700-0005');
        $this->barbearias->adicionarBarbeiro($estabelecimento, 'atendente@agendaki.test', VinculoEstabelecimento::ATENDENTE);

        return $estabelecimento;
    }

    private function barbeariaDoPorto(): Estabelecimento
    {
        $dono = $this->conta('Marcos Ribeiro', 'dono.porto@agendaki.test', '(19) 99700-0010');

        $estabelecimento = $this->barbearias->cadastrar($dono->login, [
            'nome' => 'Barbearia do Porto',
            'descricao' => 'Barbearia de bairro, atendimento com hora marcada.',
            'telefone' => '(19) 3600-0010',
            'cep' => '13890-000',
            'estado' => 'SP',
            'cidade' => 'Águas da Prata',
            'bairro' => 'Vila Nova',
            'logradouro' => 'Avenida do Porto',
            'numero' => 45,
            'complemento' => 'Sala 2',
            'horarios' => $this->semana(uteis: ['09:00', '18:00'], sabado: ['09:00', '14:00'], domingo: null),
        ]);

        $corte = $estabelecimento->servicos()->create(['nome' => 'Corte de cabelo', 'preco' => 35.00, 'duracao_minutos' => 30, 'status' => 'a']);
        $barba = $estabelecimento->servicos()->create(['nome' => 'Barba', 'preco' => 25.00, 'duracao_minutos' => 30, 'status' => 'a']);

        // O próprio dono corta.
        $vinculoDono = VinculoEstabelecimento::where('estabelecimento_id', $estabelecimento->id)->first();
        $this->barbearias->definirServicos($vinculoDono, [$corte->id, $barba->id]);

        return $estabelecimento;
    }

    /** Atendimentos de ontem (realizados, um cancelado), hoje e amanhã (agendados). */
    private function atendimentos(Estabelecimento $estabelecimento, array $clientes): void
    {
        $hoje = CarbonImmutable::today();
        $vinculos = VinculoEstabelecimento::ativos()->where('estabelecimento_id', $estabelecimento->id)
            ->with('servicos')->orderBy('id')->get()
            ->filter(fn ($v) => $v->servicos->isNotEmpty());

        $c = 0;
        foreach ([-1 => $hoje->subDay(), 0 => $hoje, 1 => $hoje->addDay()] as $deslocamento => $dia) {
            foreach ($vinculos as $v => $vinculo) {
                $servicos = $vinculo->servicos->sortBy('id')->values();
                $marcados = 0;

                foreach ($this->disponibilidade->janelas($vinculo, $dia) as [$inicioJanela, $fimJanela]) {
                    // Um atendimento a cada 90 minutos, a partir de 30 minutos após a abertura.
                    for ($inicio = $inicioJanela->addMinutes(30); $marcados < 4; $inicio = $inicio->addMinutes(90)) {
                        $servico = $servicos[($c + $v) % $servicos->count()];
                        $fim = $inicio->addMinutes($servico->duracao_minutos);
                        if ($fim > $fimJanela) {
                            break;
                        }

                        $status = $deslocamento < 0 ? Atendimento::REALIZADO : Atendimento::AGENDADO;
                        if ($deslocamento < 0 && $marcados === 2) {
                            $status = Atendimento::CANCELADO;
                        }

                        $this->criarAtendimento($clientes[$c % count($clientes)], $vinculo, $servico, $inicio, $fim, $status);
                        $marcados++;
                        $c++;
                    }
                }
            }
        }
    }

    private function criarAtendimento(Cliente $cliente, VinculoEstabelecimento $vinculo, Servico $servico, CarbonImmutable $inicio, CarbonImmutable $fim, string $status): void
    {
        DB::table('cliente_estabelecimento')->insertOrIgnore([
            'cliente_id' => $cliente->id,
            'estabelecimento_id' => $vinculo->estabelecimento_id,
        ]);

        $atendimento = Atendimento::create([
            'cliente_id' => $cliente->id,
            'estabelecimento_id' => $vinculo->estabelecimento_id,
            'vinculo_estabelecimento_id' => $vinculo->id,
            'inicio' => $inicio,
            'fim' => $fim,
            'valor_final' => $servico->preco,
            'status' => $status,
            'presenca_confirmada_em' => $status === Atendimento::REALIZADO ? $inicio->addMinutes(5) : null,
            'cancelada_em' => $status === Atendimento::CANCELADO ? $inicio->subHours(3) : null,
            'motivo_cancelamento' => $status === Atendimento::CANCELADO ? 'Imprevisto no trabalho.' : null,
        ]);

        $atendimento->servicos()->attach($servico->id, ['valor' => $servico->preco]);
    }

    private function conta(string $nome, string $email, string $telefone): Cliente
    {
        $login = Login::create([
            'email' => $email,
            'senha_hash' => self::SENHA,
            'tipo_usuario' => 'cliente',
            'status' => 'a',
        ]);

        return $login->cliente()->create(['nome' => $nome, 'telefone' => $telefone, 'email' => $email]);
    }

    private function barbeiro(Estabelecimento $estabelecimento, string $nome, string $email, string $telefone): VinculoEstabelecimento
    {
        $this->conta($nome, $email, $telefone);

        return $this->barbearias->adicionarBarbeiro($estabelecimento, $email);
    }

    private function semana(array $uteis, array $sabado, ?array $domingo): array
    {
        $horarios = [];
        foreach (range(0, 6) as $dia) {
            $faixa = match (true) {
                $dia === 0 => $domingo,
                $dia === 6 => $sabado,
                in_array($dia, self::DIAS_UTEIS, true) => $uteis,
            };
            $horarios[] = [
                'dia_semana' => $dia,
                'aberto' => $faixa !== null,
                'hora_abertura' => $faixa[0] ?? null,
                'hora_fechamento' => $faixa[1] ?? null,
            ];
        }

        return $horarios;
    }
}
