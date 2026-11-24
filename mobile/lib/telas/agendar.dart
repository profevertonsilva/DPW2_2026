import 'package:flutter/material.dart';

import '../api.dart';
import '../componentes.dart';
import '../formatos.dart';
import '../modelos.dart';
import 'inicio.dart';

/// Fluxo de agendamento (RF #18): serviço, barbeiro, data e horário, resumo.
/// Telas 14, 15, 16, 17, 19 e 20 do protótipo.

const _totalEtapas = 4;

/// Tela 14: escolha do serviço.
class TelaEscolherServico extends StatefulWidget {
  const TelaEscolherServico({super.key, required this.barbearia});

  final Barbearia barbearia;

  @override
  State<TelaEscolherServico> createState() => _TelaEscolherServicoState();
}

class _TelaEscolherServicoState extends State<TelaEscolherServico> {
  Servico? _escolhido;

  @override
  Widget build(BuildContext context) {
    // Só serviços que algum barbeiro executa.
    final servicos = widget.barbearia.servicos
        .where((s) => widget.barbearia.barbeirosDo(s).isNotEmpty)
        .toList();

    return Scaffold(
      appBar: AppBar(title: Text(widget.barbearia.nome)),
      body: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const Etapas(atual: 1, total: _totalEtapas),
          const _Pergunta('Qual serviço você quer?'),
          Expanded(
            child: ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: servicos.length,
              separatorBuilder: (_, _) => const SizedBox(height: 10),
              itemBuilder: (context, i) {
                final s = servicos[i];
                return Cartao(
                  selecionado: _escolhido?.id == s.id,
                  aoTocar: () => setState(() => _escolhido = s),
                  child: Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              s.nome,
                              style: const TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                            Text(
                              '${s.duracaoMinutos} min',
                              style: const TextStyle(color: corTextoSuave),
                            ),
                          ],
                        ),
                      ),
                      Text(
                        dinheiro(s.preco),
                        style: const TextStyle(
                          fontWeight: FontWeight.w700,
                          color: corPrimaria,
                        ),
                      ),
                    ],
                  ),
                );
              },
            ),
          ),
        ],
      ),
      bottomNavigationBar: BarraInferior(
        child: BotaoPrincipal(
          texto: 'Continuar',
          aoTocar: _escolhido == null
              ? null
              : () => Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (_) => TelaEscolherBarbeiro(
                      barbearia: widget.barbearia,
                      servico: _escolhido!,
                    ),
                  ),
                ),
        ),
      ),
    );
  }
}

/// Tela 15: escolha do barbeiro (só quem executa o serviço, RF #20).
class TelaEscolherBarbeiro extends StatefulWidget {
  const TelaEscolherBarbeiro({
    super.key,
    required this.barbearia,
    required this.servico,
  });

  final Barbearia barbearia;
  final Servico servico;

  @override
  State<TelaEscolherBarbeiro> createState() => _TelaEscolherBarbeiroState();
}

class _TelaEscolherBarbeiroState extends State<TelaEscolherBarbeiro> {
  late final List<Barbeiro> _barbeiros = widget.barbearia.barbeirosDo(
    widget.servico,
  );
  late Barbeiro? _escolhido = _barbeiros.length == 1 ? _barbeiros.first : null;

  void _continuar() {
    final barbearia = widget.barbearia;
    final servico = widget.servico;
    final barbeiro = _escolhido!;
    final abertos = {
      for (final h in barbearia.horarios)
        if (h.aberto) h.diaSemana,
    };

    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => TelaEscolherHorario(
          titulo: barbeiro.nome,
          etapa: 3,
          diaFechado: (dia) => !abertos.contains(diaSemana(dia)),
          buscar: (dia) async {
            final json = await Api.instancia.get(
              '/barbearias/${barbearia.id}/disponibilidade',
              {
                'servico_id': '${servico.id}',
                'vinculo_id': '${barbeiro.vinculoId}',
                'data': dataApi(dia),
              },
            );
            return [
              for (final h in json['horarios'] as List)
                HorarioLivre.fromJson(h),
            ];
          },
          textoBotao: 'Revisar agendamento',
          aoContinuar: (context, dia, horario) => Navigator.push(
            context,
            MaterialPageRoute(
              builder: (_) => TelaResumo(
                barbearia: barbearia,
                servico: servico,
                barbeiro: barbeiro,
                dia: dia,
                horario: horario,
              ),
            ),
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(widget.servico.nome)),
      body: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const Etapas(atual: 2, total: _totalEtapas),
          const _Pergunta('Com quem você quer ser atendido?'),
          Expanded(
            child: ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: _barbeiros.length,
              separatorBuilder: (_, _) => const SizedBox(height: 10),
              itemBuilder: (context, i) {
                final b = _barbeiros[i];
                final selecionado = _escolhido?.vinculoId == b.vinculoId;
                return Cartao(
                  selecionado: selecionado,
                  aoTocar: () => setState(() => _escolhido = b),
                  child: Row(
                    children: [
                      CircleAvatar(
                        backgroundColor: corPrimariaSuave,
                        child: Text(
                          b.nome[0],
                          style: const TextStyle(
                            color: corPrimaria,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Text(
                          b.nome,
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                      Icon(
                        selecionado
                            ? Icons.radio_button_checked
                            : Icons.radio_button_off,
                        color: selecionado ? corPrimaria : corBorda,
                      ),
                    ],
                  ),
                );
              },
            ),
          ),
        ],
      ),
      bottomNavigationBar: BarraInferior(
        child: BotaoPrincipal(
          texto: 'Continuar',
          aoTocar: _escolhido == null ? null : _continuar,
        ),
      ),
    );
  }
}

/// Tela 16: data e horário (RF #19). Reaproveitada na remarcação (tela 25).
class TelaEscolherHorario extends StatefulWidget {
  const TelaEscolherHorario({
    super.key,
    required this.titulo,
    required this.buscar,
    required this.textoBotao,
    required this.aoContinuar,
    this.etapa,
    this.diaFechado,
    this.cabecalho,
  });

  final String titulo;
  final int? etapa;
  final Widget? cabecalho;
  final Future<List<HorarioLivre>> Function(DateTime dia) buscar;
  final bool Function(DateTime dia)? diaFechado;
  final String textoBotao;

  /// Ao voltar desta chamada, a grade é recarregada.
  final Future<void> Function(
    BuildContext context,
    DateTime dia,
    HorarioLivre horario,
  )
  aoContinuar;

  @override
  State<TelaEscolherHorario> createState() => _TelaEscolherHorarioState();
}

class _TelaEscolherHorarioState extends State<TelaEscolherHorario> {
  late final List<DateTime> _dias = [
    for (var i = 0; i < 21; i++) hojeEmSaoPaulo().add(Duration(days: i)),
  ];
  late DateTime _dia = _dias.firstWhere(
    (d) => !(widget.diaFechado?.call(d) ?? false),
    orElse: () => _dias.first,
  );
  List<HorarioLivre>? _horarios;
  HorarioLivre? _escolhido;
  String? _erro;
  bool _ocupado = false;

  @override
  void initState() {
    super.initState();
    _carregar();
  }

  Future<void> _carregar() async {
    final dia = _dia;
    setState(() {
      _horarios = null;
      _escolhido = null;
      _erro = null;
    });
    try {
      final horarios = await widget.buscar(dia);
      if (mounted && dia == _dia) setState(() => _horarios = horarios);
    } on ApiException catch (e) {
      if (mounted) setState(() => _erro = e.mensagem);
    }
  }

  Future<void> _continuar() async {
    setState(() => _ocupado = true);
    try {
      await widget.aoContinuar(context, _dia, _escolhido!);
    } finally {
      if (mounted) {
        setState(() => _ocupado = false);
        _carregar();
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(widget.titulo)),
      body: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          if (widget.etapa != null)
            Etapas(atual: widget.etapa!, total: _totalEtapas),
          ?widget.cabecalho,
          SizedBox(
            height: 76,
            child: ListView.separated(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 16),
              itemCount: _dias.length,
              separatorBuilder: (_, _) => const SizedBox(width: 8),
              itemBuilder: (context, i) => _BotaoDia(
                dia: _dias[i],
                selecionado: _dias[i] == _dia,
                fechado: widget.diaFechado?.call(_dias[i]) ?? false,
                aoTocar: () {
                  setState(() => _dia = _dias[i]);
                  _carregar();
                },
              ),
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
            child: Text(
              'Horários disponíveis — ${dataPorExtenso(_dia)}',
              style: const TextStyle(fontWeight: FontWeight.w600),
            ),
          ),
          Expanded(child: _grade()),
        ],
      ),
      bottomNavigationBar: BarraInferior(
        child: BotaoPrincipal(
          key: const Key('continuar-horario'),
          texto: widget.textoBotao,
          carregando: _ocupado,
          aoTocar: _escolhido == null ? null : _continuar,
        ),
      ),
    );
  }

  Widget _grade() {
    if (_erro != null) {
      return MensagemErro(mensagem: _erro!, aoTentar: _carregar);
    }
    final horarios = _horarios;
    if (horarios == null) {
      return const Center(child: CircularProgressIndicator());
    }
    if (horarios.isEmpty) {
      return Vazio(
        (widget.diaFechado?.call(_dia) ?? false)
            ? 'A barbearia não abre neste dia.'
            : 'Não há horários livres neste dia.\nEscolha outra data.',
      );
    }
    return SelecaoHorarios(
      horarios: horarios,
      escolhido: _escolhido,
      aoEscolher: (h) => setState(() => _escolhido = h),
    );
  }
}

/// Grade de botões de horário (usada nas telas 16 e 20).
class SelecaoHorarios extends StatelessWidget {
  const SelecaoHorarios({
    super.key,
    required this.horarios,
    required this.aoEscolher,
    this.escolhido,
    this.embutida = false,
  });

  final List<HorarioLivre> horarios;
  final HorarioLivre? escolhido;
  final void Function(HorarioLivre) aoEscolher;

  /// Dentro de outra lista rolável: não rola sozinha.
  final bool embutida;

  @override
  Widget build(BuildContext context) {
    return GridView.count(
      crossAxisCount: 3,
      padding: EdgeInsets.symmetric(
        horizontal: embutida ? 0 : 16,
        vertical: 16,
      ),
      mainAxisSpacing: 10,
      crossAxisSpacing: 10,
      childAspectRatio: 2.1,
      shrinkWrap: embutida,
      physics: embutida ? const NeverScrollableScrollPhysics() : null,
      children: [
        for (final h in horarios)
          Cartao(
            key: Key('horario-${h.hora}'),
            selecionado: escolhido?.inicio == h.inicio,
            padding: EdgeInsets.zero,
            aoTocar: () => aoEscolher(h),
            child: Center(
              child: Text(
                h.hora,
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w600,
                  color: escolhido?.inicio == h.inicio ? corPrimaria : null,
                ),
              ),
            ),
          ),
      ],
    );
  }
}

class _BotaoDia extends StatelessWidget {
  const _BotaoDia({
    required this.dia,
    required this.selecionado,
    required this.fechado,
    required this.aoTocar,
  });

  final DateTime dia;
  final bool selecionado;
  final bool fechado;
  final VoidCallback aoTocar;

  @override
  Widget build(BuildContext context) {
    final cor = selecionado
        ? Colors.white
        : (fechado ? corTextoSuave.withValues(alpha: 0.5) : null);
    return Material(
      color: selecionado ? corPrimaria : Colors.white,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(10),
        side: BorderSide(color: selecionado ? corPrimaria : corBorda),
      ),
      child: InkWell(
        borderRadius: BorderRadius.circular(10),
        onTap: aoTocar,
        child: SizedBox(
          width: 62,
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text(
                diasSemanaCurtos[diaSemana(dia)],
                style: TextStyle(fontSize: 12, color: cor ?? corTextoSuave),
              ),
              const SizedBox(height: 2),
              Text(
                '${dia.day}',
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.w700,
                  color: cor,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

/// Tela 17: resumo e confirmação. Pagamento no local (sem cupom nem recorrência).
class TelaResumo extends StatefulWidget {
  const TelaResumo({
    super.key,
    required this.barbearia,
    required this.servico,
    required this.barbeiro,
    required this.dia,
    required this.horario,
  });

  final Barbearia barbearia;
  final Servico servico;
  final Barbeiro barbeiro;
  final DateTime dia;
  final HorarioLivre horario;

  @override
  State<TelaResumo> createState() => _TelaResumoState();
}

class _TelaResumoState extends State<TelaResumo> {
  late HorarioLivre _horario = widget.horario;
  bool _enviando = false;

  Future<void> _confirmar() async {
    setState(() => _enviando = true);
    try {
      final json = await Api.instancia.post('/agendamentos', {
        'servico_id': widget.servico.id,
        'vinculo_id': widget.barbeiro.vinculoId,
        'inicio': _horario.inicio,
      });
      if (!mounted) return;
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(
          builder: (_) =>
              TelaComprovante(agendamento: Agendamento.fromJson(json)),
        ),
      );
    } on ApiException catch (e) {
      if (!mounted) return;
      if (e.status == 409) {
        await _horarioIndisponivel(e);
      } else {
        avisar(
          context,
          e.erro('inicio') ??
              e.erro('servico_id') ??
              e.erro('vinculo_id') ??
              e.mensagem,
        );
      }
    } finally {
      if (mounted) setState(() => _enviando = false);
    }
  }

  /// Tela 20: o horário foi preenchido por outra pessoa (RF #36).
  Future<void> _horarioIndisponivel(ApiException e) async {
    final alternativas = [
      for (final iso in (e.corpo?['alternativas'] as List? ?? []))
        HorarioLivre.doIso(iso as String),
    ];
    final escolha = await Navigator.push<HorarioLivre>(
      context,
      MaterialPageRoute(
        builder: (_) => TelaHorarioIndisponivel(
          horarioPedido: _horario.hora,
          alternativas: alternativas,
        ),
      ),
    );
    if (!mounted) return;
    if (escolha == null) {
      // "Escolher outro horário": volta para a grade, que é recarregada.
      Navigator.pop(context);
    } else {
      setState(() => _horario = escolha);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Confirmar agendamento')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          const Etapas(atual: 4, total: _totalEtapas),
          Cartao(
            child: Column(
              children: [
                LinhaResumo('Barbearia', widget.barbearia.nome),
                LinhaResumo(
                  'Serviço',
                  '${widget.servico.nome} · ${widget.servico.duracaoMinutos} min',
                ),
                LinhaResumo('Barbeiro', widget.barbeiro.nome),
                LinhaResumo(
                  'Data e horário',
                  '${dataCurta(widget.dia)} às ${_horario.hora}',
                ),
                LinhaResumo('Endereço', widget.barbearia.endereco),
              ],
            ),
          ),
          const SizedBox(height: 12),
          const Cartao(
            child: Row(
              children: [
                Icon(Icons.payments_outlined, color: corTextoSuave),
                SizedBox(width: 10),
                Text('Pagamento no local'),
              ],
            ),
          ),
        ],
      ),
      bottomNavigationBar: BarraInferior(
        child: Row(
          children: [
            Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('Total', style: TextStyle(color: corTextoSuave)),
                Text(
                  dinheiro(widget.servico.preco),
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ],
            ),
            const SizedBox(width: 16),
            Expanded(
              child: BotaoPrincipal(
                key: const Key('confirmar-agendamento'),
                texto: 'Confirmar agendamento',
                destaque: true,
                carregando: _enviando,
                aoTocar: _confirmar,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// Tela 20: horário indisponível, com as alternativas devolvidas pela API.
/// Devolve o horário escolhido, ou null para voltar à grade.
class TelaHorarioIndisponivel extends StatelessWidget {
  const TelaHorarioIndisponivel({
    super.key,
    required this.horarioPedido,
    required this.alternativas,
  });

  final String horarioPedido;
  final List<HorarioLivre> alternativas;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Confirmar agendamento')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          CaixaMensagem(
            titulo: 'Este horário acabou de ser preenchido',
            texto:
                'Outro cliente confirmou $horarioPedido enquanto você revisava. '
                '${alternativas.isEmpty ? 'Escolha outro horário.' : 'Escolha um horário alternativo abaixo.'}',
          ),
          const SizedBox(height: 20),
          if (alternativas.isNotEmpty) ...[
            const Text(
              'Horários próximos ainda disponíveis',
              style: TextStyle(fontWeight: FontWeight.w600),
            ),
            SelecaoHorarios(
              horarios: alternativas,
              embutida: true,
              aoEscolher: (h) => Navigator.pop(context, h),
            ),
          ] else
            const Text(
              'Não há outros horários livres com este barbeiro neste dia.',
              style: TextStyle(color: corTextoSuave),
            ),
        ],
      ),
      bottomNavigationBar: BarraInferior(
        child: BotaoPrincipal(
          texto: 'Escolher outro horário',
          aoTocar: () => Navigator.pop(context),
        ),
      ),
    );
  }
}

/// Tela 19: comprovante.
class TelaComprovante extends StatelessWidget {
  const TelaComprovante({super.key, required this.agendamento});

  final Agendamento agendamento;

  void _irPara(BuildContext context, int aba) {
    if (aba == 1) versaoAgenda.value++;
    abaAtual.value = aba;
    Navigator.popUntil(context, (rota) => rota.isFirst);
  }

  @override
  Widget build(BuildContext context) {
    final a = agendamento;
    return Scaffold(
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.fromLTRB(16, 40, 16, 16),
          children: [
            const CircleAvatar(
              radius: 36,
              backgroundColor: corSucessoSuave,
              child: Icon(Icons.check, size: 40, color: corSucesso),
            ),
            const SizedBox(height: 16),
            const Text(
              'Agendamento confirmado!',
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 24, fontWeight: FontWeight.w800),
            ),
            const SizedBox(height: 6),
            Text(
              'Te esperamos em ${a.data} às ${a.horaInicio}.',
              textAlign: TextAlign.center,
              style: const TextStyle(color: corTextoSuave),
            ),
            const SizedBox(height: 24),
            Cartao(
              child: Column(
                children: [
                  LinhaResumo('Código', '#${a.id}'),
                  LinhaResumo('Barbearia', a.barbearia),
                  LinhaResumo('Serviço', a.servico),
                  LinhaResumo('Barbeiro', a.barbeiro),
                  LinhaResumo('Data', a.data),
                  LinhaResumo('Horário', '${a.horaInicio} às ${a.horaFim}'),
                  LinhaResumo('Valor', '${dinheiro(a.valor)} (no local)'),
                  LinhaResumo('Endereço', a.enderecoBarbearia),
                ],
              ),
            ),
          ],
        ),
      ),
      bottomNavigationBar: BarraInferior(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            BotaoPrincipal(
              key: const Key('ver-agendamentos'),
              texto: 'Ver meus agendamentos',
              aoTocar: () => _irPara(context, 1),
            ),
            const SizedBox(height: 8),
            TextButton(
              onPressed: () => _irPara(context, 0),
              child: const Text('Voltar ao início'),
            ),
          ],
        ),
      ),
    );
  }
}

class _Pergunta extends StatelessWidget {
  const _Pergunta(this.texto);

  final String texto;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 4, 16, 0),
      child: Text(
        texto,
        style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w700),
      ),
    );
  }
}
