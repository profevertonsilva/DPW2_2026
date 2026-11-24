import 'package:flutter/material.dart';

import '../api.dart';
import '../componentes.dart';
import '../formatos.dart';
import '../modelos.dart';
import 'agendar.dart';
import 'avaliacoes.dart';

/// Tela 23: Detalhe do agendamento, com cancelar (24) e remarcar (25), RF #21.
class TelaDetalheAgendamento extends StatefulWidget {
  const TelaDetalheAgendamento({super.key, required this.agendamento});

  final Agendamento agendamento;

  @override
  State<TelaDetalheAgendamento> createState() => _TelaDetalheAgendamentoState();
}

class _TelaDetalheAgendamentoState extends State<TelaDetalheAgendamento> {
  late Agendamento _a = widget.agendamento;
  bool _ocupado = false;

  Future<void> _recarregar() async {
    try {
      final json = await Api.instancia.get('/agendamentos/${_a.id}');
      if (mounted) setState(() => _a = Agendamento.fromJson(json));
    } on ApiException catch (e) {
      if (mounted) avisar(context, e.mensagem);
    }
  }

  /// Tela 26: avaliar (ou editar a avaliação em até 7 dias).
  Future<void> _avaliar() async {
    final atualizado = await Navigator.push<Agendamento>(
      context,
      MaterialPageRoute(builder: (_) => TelaAvaliar(agendamento: _a)),
    );
    if (atualizado != null && mounted) setState(() => _a = atualizado);
  }

  /// Tela 24: confirmação do cancelamento, com motivo opcional.
  Future<void> _cancelar() async {
    final motivo = TextEditingController();
    final confirmar = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Cancelar agendamento?'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              '${_a.servico} com ${_a.barbeiro}\n${dataCurta(dataDaApi(_a.data))} às ${_a.horaInicio}',
            ),
            const SizedBox(height: 16),
            TextField(
              key: const Key('motivo-cancelamento'),
              controller: motivo,
              maxLength: 500,
              decoration: const InputDecoration(hintText: 'Motivo (opcional)'),
            ),
            const Text(
              'O horário fica livre para outros clientes.',
              style: TextStyle(fontSize: 12, color: corTextoSuave),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Voltar'),
          ),
          TextButton(
            key: const Key('confirmar-cancelamento'),
            style: TextButton.styleFrom(
              foregroundColor: corErro,
            ),
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Cancelar agendamento'),
          ),
        ],
      ),
    );
    if (confirmar != true) return;

    setState(() => _ocupado = true);
    try {
      final json = await Api.instancia.post('/agendamentos/${_a.id}/cancelar', {
        if (motivo.text.trim().isNotEmpty) 'motivo': motivo.text.trim(),
      });
      if (!mounted) return;
      setState(() => _a = Agendamento.fromJson(json));
      avisar(context, 'Agendamento cancelado.');
    } on ApiException catch (e) {
      if (mounted) avisar(context, e.erro('status') ?? e.mensagem);
    } finally {
      if (mounted) setState(() => _ocupado = false);
    }
  }

  /// Tela 25: remarcar com o mesmo serviço e barbeiro, reaproveitando a grade da tela 16.
  Future<void> _remarcar() async {
    await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => TelaEscolherHorario(
          titulo: 'Remarcar',
          cabecalho: Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
            child: Cartao(
              child: Text(
                '${_a.servico} com ${_a.barbeiro}\n'
                'Atual: ${dataCurta(dataDaApi(_a.data))} às ${_a.horaInicio}',
              ),
            ),
          ),
          buscar: (dia) async {
            final json = await Api.instancia.get(
              '/agendamentos/${_a.id}/disponibilidade',
              {'data': dataApi(dia)},
            );
            return [
              for (final h in json['horarios'] as List)
                HorarioLivre.fromJson(h),
            ];
          },
          textoBotao: 'Confirmar novo horário',
          aoContinuar: _confirmarRemarcacao,
        ),
      ),
    );
    _recarregar();
  }

  Future<void> _confirmarRemarcacao(
    BuildContext context,
    DateTime dia,
    HorarioLivre horario,
  ) async {
    try {
      final json = await Api.instancia.post('/agendamentos/${_a.id}/remarcar', {
        'inicio': horario.inicio,
      });
      _a = Agendamento.fromJson(json);
      if (!context.mounted) return;
      Navigator.pop(context);
      avisar(
        context,
        'Agendamento remarcado para ${dataCurta(dia)} às ${horario.hora}.',
      );
    } on ApiException catch (e) {
      if (!context.mounted) return;
      if (e.status == 409) {
        // Horário preenchido enquanto o cliente escolhia (RF #36): tela 20.
        final alternativas = [
          for (final iso in (e.corpo?['alternativas'] as List? ?? []))
            HorarioLivre.doIso(iso as String),
        ];
        final escolha = await Navigator.push<HorarioLivre>(
          context,
          MaterialPageRoute(
            builder: (_) => TelaHorarioIndisponivel(
              horarioPedido: horario.hora,
              alternativas: alternativas,
            ),
          ),
        );
        if (escolha != null && context.mounted) {
          await _confirmarRemarcacao(context, dia, escolha);
        }
      } else {
        avisar(context, e.erro('inicio') ?? e.erro('status') ?? e.mensagem);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final a = _a;
    return Scaffold(
      appBar: AppBar(title: const Text('Detalhe do agendamento')),
      body: RefreshIndicator(
        onRefresh: _recarregar,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            Align(
              alignment: Alignment.centerLeft,
              child: EtiquetaStatus(a.status),
            ),
            const SizedBox(height: 12),
            Cartao(
              child: Column(
                children: [
                  LinhaResumo('Barbearia', a.barbearia),
                  LinhaResumo(
                    'Serviço',
                    '${a.servico} · ${a.duracaoMinutos} min',
                  ),
                  LinhaResumo('Barbeiro', a.barbeiro),
                  LinhaResumo(
                    'Data e horário',
                    '${dataCurta(dataDaApi(a.data))} às ${a.horaInicio}',
                  ),
                  LinhaResumo('Valor', dinheiro(a.valor)),
                  const LinhaResumo('Pagamento', 'No local'),
                  if (a.status == 'cancelado' && a.motivoCancelamento != null)
                    LinhaResumo(
                      'Motivo do cancelamento',
                      a.motivoCancelamento!,
                    ),
                ],
              ),
            ),
            if (a.notaAvaliacao != null || a.podeAvaliar) ...[
              const SizedBox(height: 12),
              Cartao(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Sua avaliação',
                      style: TextStyle(fontWeight: FontWeight.w700),
                    ),
                    const SizedBox(height: 6),
                    if (a.notaAvaliacao != null) ...[
                      Estrelas(a.notaAvaliacao!.toDouble(), tamanho: 22),
                      if (a.comentarioAvaliacao != null) ...[
                        const SizedBox(height: 4),
                        Text(a.comentarioAvaliacao!),
                      ],
                    ] else
                      const Text(
                        'Como foi o atendimento? Sua nota ajuda outros clientes.',
                        style: TextStyle(color: corTextoSuave),
                      ),
                    if (a.podeAvaliar)
                      Align(
                        alignment: Alignment.centerRight,
                        child: TextButton(
                          key: const Key('botao-avaliar'),
                          onPressed: _avaliar,
                          child: Text(
                            a.notaAvaliacao == null
                                ? 'Avaliar atendimento'
                                : 'Editar avaliação',
                          ),
                        ),
                      ),
                  ],
                ),
              ),
            ],
            const SizedBox(height: 12),
            Text(
              a.enderecoBarbearia,
              style: const TextStyle(color: corTextoSuave),
            ),
            if (a.telefoneBarbearia != null)
              Text(
                a.telefoneBarbearia!,
                style: const TextStyle(color: corTextoSuave),
              ),
          ],
        ),
      ),
      bottomNavigationBar: !(a.podeCancelar || a.podeRemarcar)
          ? null
          : BarraInferior(
              child: Row(
                children: [
                  if (a.podeRemarcar)
                    Expanded(
                      child: OutlinedButton(
                        key: const Key('botao-remarcar'),
                        onPressed: _ocupado ? null : _remarcar,
                        child: const Text('Remarcar'),
                      ),
                    ),
                  const SizedBox(width: 12),
                  if (a.podeCancelar)
                    Expanded(
                      child: TextButton(
                        key: const Key('botao-cancelar'),
                        style: TextButton.styleFrom(
                          foregroundColor: corErro,
                          minimumSize: const Size.fromHeight(50),
                        ),
                        onPressed: _ocupado ? null : _cancelar,
                        child: const Text('Cancelar agendamento'),
                      ),
                    ),
                ],
              ),
            ),
    );
  }
}
