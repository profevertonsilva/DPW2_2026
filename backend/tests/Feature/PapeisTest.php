<?php

use App\Models\Auditoria;
use App\Models\VinculoEstabelecimento;

/*
| Desejável 8: papéis gerente e atendente (RF #03, #11, #12).
*/

beforeEach(function () {
    $this->dono = conta('Dono');
    $this->barbearia = barbearia($this->dono);
    $this->corte = servico($this->barbearia);
    $this->barbeiro = barbeiro($this->barbearia, [$this->corte], conta('Rafael'));
    $this->base = "/api/painel/estabelecimentos/{$this->barbearia->id}";
});

function vinculoComPapel(string $papel, string $nome): VinculoEstabelecimento
{
    $v = barbeiro(test()->barbearia, login: conta($nome));
    $v->update(['papel' => $papel]);

    return $v;
}

it('deixa o proprietário adicionar gerente e atendente', function () {
    entrarComo($this->dono);
    conta('Gil Gerente', 'gil@teste.test');
    conta('Ana Atendente', 'ana@teste.test');

    $this->postJson("{$this->base}/equipe", ['email' => 'gil@teste.test', 'papel' => 'gerente'])->assertCreated()->assertJsonPath('papel', 'gerente');
    $this->postJson("{$this->base}/equipe", ['email' => 'ana@teste.test', 'papel' => 'atendente'])->assertCreated()->assertJsonPath('papel', 'atendente');
    $this->postJson("{$this->base}/equipe", ['email' => 'ana@teste.test', 'papel' => 'proprietario'])->assertUnprocessable()->assertJsonValidationErrors(['papel']);
});

it('dá ao gerente a gestão de serviços, equipe e barbearia, mas não a auditoria', function () {
    $gerente = vinculoComPapel('gerente', 'Gil');
    entrarComo(loginDo($gerente));

    $this->postJson("{$this->base}/servicos", ['nome' => 'Barba', 'preco' => 30, 'duracao_minutos' => 30])->assertCreated();
    $this->putJson("{$this->base}/equipe/{$this->barbeiro->id}/servicos", ['servico_ids' => [$this->corte->id]])->assertOk();
    $this->putJson($this->base, dadosBarbearia(['nome' => 'Nome pelo gerente']))->assertOk();
    $this->getJson("{$this->base}/agenda?data=2026-11-17")->assertOk();
    $this->getJson("{$this->base}/auditoria")->assertForbidden();
});

it('limita o gerente a mover colaboradores entre atendente e barbeiro (RF #12)', function () {
    $gerente = vinculoComPapel('gerente', 'Gil');
    $outroGerente = vinculoComPapel('gerente', 'Gina');
    entrarComo(loginDo($gerente));
    conta('Novo', 'novo@teste.test');

    $this->putJson("{$this->base}/equipe/{$this->barbeiro->id}/papel", ['papel' => 'atendente'])->assertOk()->assertJsonPath('papel', 'atendente');
    $this->putJson("{$this->base}/equipe/{$this->barbeiro->id}/papel", ['papel' => 'profissional'])->assertOk();

    $this->putJson("{$this->base}/equipe/{$this->barbeiro->id}/papel", ['papel' => 'gerente'])->assertForbidden();
    $this->putJson("{$this->base}/equipe/{$outroGerente->id}/papel", ['papel' => 'atendente'])->assertForbidden();
    $this->postJson("{$this->base}/equipe", ['email' => 'novo@teste.test', 'papel' => 'gerente'])->assertForbidden();
    $this->deleteJson("{$this->base}/equipe/{$outroGerente->id}")->assertForbidden();
    $this->deleteJson("{$this->base}/equipe/".vinculoDoDono($this->barbearia)->id)->assertUnprocessable();
});

it('deixa o proprietário promover a gerente e registra a alteração de cargo na auditoria', function () {
    entrarComo($this->dono);

    $this->putJson("{$this->base}/equipe/{$this->barbeiro->id}/papel", ['papel' => 'gerente'])->assertOk()->assertJsonPath('papel', 'gerente');
    $this->putJson("{$this->base}/equipe/".vinculoDoDono($this->barbearia)->id.'/papel', ['papel' => 'atendente'])
        ->assertUnprocessable()->assertJsonPath('errors.papel.0', 'O papel do proprietário não pode ser alterado.');

    $registro = Auditoria::sole();
    expect($registro->tipo_operacao)->toBe('alteracao_cargo')
        ->and($registro->valor_anterior['papel'])->toBe('profissional')
        ->and($registro->valor_novo['papel'])->toBe('gerente');

    // O novo gerente passa a ter as permissões imediatamente.
    entrarComo(loginDo($this->barbeiro->refresh()));
    $this->getJson("{$this->base}/servicos")->assertOk();
});

it('dá ao atendente a agenda de toda a barbearia, sem gestão', function () {
    $atendente = vinculoComPapel('atendente', 'Ana');
    $a = atendimento($this->barbeiro, $this->corte, '2026-11-16 10:00');
    $b = atendimento($this->barbeiro, $this->corte, '2026-11-17 10:00');
    entrarComo(loginDo($atendente));

    $this->getJson("{$this->base}/agenda?data=2026-11-16")->assertOk()->assertJsonCount(1);
    $this->getJson("{$this->base}/equipe")->assertOk();
    $this->postJson("{$this->base}/agendamentos/{$a->id}/realizado")->assertOk();
    $this->postJson("{$this->base}/agendamentos/{$b->id}/cancelar")->assertOk();
    $this->postJson("{$this->base}/bloqueios", ['vinculo_id' => $this->barbeiro->id, 'inicio' => sp('2026-11-18 12:00'), 'fim' => sp('2026-11-18 13:00')])->assertCreated();

    $this->postJson("{$this->base}/servicos", ['nome' => 'X', 'preco' => 10, 'duracao_minutos' => 30])->assertForbidden();
    $this->postJson("{$this->base}/equipe", ['email' => 'x@teste.test'])->assertForbidden();
    $this->putJson($this->base, dadosBarbearia())->assertForbidden();
});

it('mantém o barbeiro restrito à própria agenda e sem a lista da equipe', function () {
    entrarComo(loginDo($this->barbeiro));

    $this->getJson("{$this->base}/equipe")->assertForbidden();
    $this->putJson("{$this->base}/equipe/{$this->barbeiro->id}/papel", ['papel' => 'gerente'])->assertForbidden();
});
