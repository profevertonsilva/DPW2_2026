import 'package:flutter/material.dart';

import '../api.dart';
import '../componentes.dart';
import '../modelos.dart';

/// Estrelas de 1 a 5 (somente leitura).
class Estrelas extends StatelessWidget {
  const Estrelas(this.nota, {super.key, this.tamanho = 18});

  final double nota;
  final double tamanho;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        for (var i = 1; i <= 5; i++)
          Icon(
            nota >= i
                ? Icons.star
                : (nota >= i - 0.5 ? Icons.star_half : Icons.star_border),
            size: tamanho,
            color: corDestaque,
          ),
      ],
    );
  }
}

/// "4,8"
String notaFormatada(double nota) =>
    nota.toStringAsFixed(1).replaceAll('.', ',');

/// Tela 06: avaliações da barbearia (RF #30).
class TelaAvaliacoes extends StatefulWidget {
  const TelaAvaliacoes({
    super.key,
    required this.barbeariaId,
    required this.nome,
  });

  final int barbeariaId;
  final String nome;

  @override
  State<TelaAvaliacoes> createState() => _TelaAvaliacoesState();
}

class _TelaAvaliacoesState extends State<TelaAvaliacoes> {
  Map<String, dynamic>? _dados;
  String? _erro;

  @override
  void initState() {
    super.initState();
    _carregar();
  }

  Future<void> _carregar() async {
    try {
      final json = await Api.instancia.get(
        '/barbearias/${widget.barbeariaId}/avaliacoes',
      );
      if (mounted) setState(() => _dados = json as Map<String, dynamic>);
    } on ApiException catch (e) {
      if (mounted) setState(() => _erro = e.mensagem);
    }
  }

  @override
  Widget build(BuildContext context) {
    final dados = _dados;
    return Scaffold(
      appBar: AppBar(title: const Text('Avaliações')),
      body: _erro != null
          ? MensagemErro(mensagem: _erro!, aoTentar: _carregar)
          : dados == null
          ? const Center(child: CircularProgressIndicator())
          : ListView(
              padding: const EdgeInsets.all(16),
              children: [
                Cartao(
                  child: Column(
                    children: [
                      Text(
                        widget.nome,
                        style: const TextStyle(color: corTextoSuave),
                      ),
                      const SizedBox(height: 4),
                      if (dados['media'] != null) ...[
                        Text(
                          notaFormatada((dados['media'] as num).toDouble()),
                          style: const TextStyle(
                            fontSize: 36,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                        Estrelas(
                          (dados['media'] as num).toDouble(),
                          tamanho: 22,
                        ),
                      ],
                      const SizedBox(height: 4),
                      Text(
                        dados['total'] == 1
                            ? '1 avaliação'
                            : '${dados['total']} avaliações',
                        style: const TextStyle(color: corTextoSuave),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 12),
                if ((dados['avaliacoes'] as List).isEmpty)
                  const Vazio(
                    'Esta barbearia ainda não recebeu avaliações.',
                    icone: Icons.star_border,
                  ),
                for (final a in dados['avaliacoes'] as List) ...[
                  Cartao(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Expanded(
                              child: Text(
                                a['cliente'] as String,
                                style: const TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                            ),
                            Estrelas((a['nota'] as int).toDouble()),
                          ],
                        ),
                        if (a['comentario'] != null) ...[
                          const SizedBox(height: 6),
                          Text(a['comentario'] as String),
                        ],
                        const SizedBox(height: 6),
                        Text(
                          '${a['servico'] ?? ''} · ${a['data']}',
                          style: const TextStyle(
                            fontSize: 12,
                            color: corTextoSuave,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 10),
                ],
              ],
            ),
    );
  }
}

/// Tela 26: avaliar um atendimento realizado (nota 1 a 5 e comentário opcional).
class TelaAvaliar extends StatefulWidget {
  const TelaAvaliar({super.key, required this.agendamento});

  final Agendamento agendamento;

  @override
  State<TelaAvaliar> createState() => _TelaAvaliarState();
}

class _TelaAvaliarState extends State<TelaAvaliar> {
  late int _nota = widget.agendamento.notaAvaliacao ?? 0;
  late final _comentario = TextEditingController(
    text: widget.agendamento.comentarioAvaliacao ?? '',
  );
  bool _enviando = false;

  static const _rotulos = [
    '',
    'Ruim',
    'Regular',
    'Bom',
    'Muito bom',
    'Excelente',
  ];

  Future<void> _enviar() async {
    setState(() => _enviando = true);
    try {
      final json = await Api.instancia.put(
        '/agendamentos/${widget.agendamento.id}/avaliacao',
        {
          'nota': _nota,
          'comentario': _comentario.text.trim().isEmpty
              ? null
              : _comentario.text.trim(),
        },
      );
      if (!mounted) return;
      Navigator.pop(context, Agendamento.fromJson(json));
      avisar(context, 'Obrigado pela avaliação!');
    } on ApiException catch (e) {
      if (mounted) {
        avisar(context, e.erro('nota') ?? e.erro('comentario') ?? e.mensagem);
      }
    } finally {
      if (mounted) setState(() => _enviando = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final a = widget.agendamento;
    return Scaffold(
      appBar: AppBar(title: const Text('Avaliar')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Cartao(
            child: Column(
              children: [
                LinhaResumo('Barbearia', a.barbearia),
                LinhaResumo('Serviço', a.servico),
                LinhaResumo('Barbeiro', a.barbeiro),
                LinhaResumo(
                  'Data',
                  '${a.data.substring(0, 5)} às ${a.horaInicio}',
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              for (var i = 1; i <= 5; i++)
                IconButton(
                  key: Key('estrela-$i'),
                  iconSize: 44,
                  tooltip: '$i de 5',
                  onPressed: () => setState(() => _nota = i),
                  icon: Icon(
                    _nota >= i ? Icons.star : Icons.star_border,
                    color: corDestaque,
                  ),
                ),
            ],
          ),
          Text(
            _rotulos[_nota],
            textAlign: TextAlign.center,
            style: const TextStyle(color: corTextoSuave),
          ),
          const SizedBox(height: 16),
          TextField(
            key: const Key('comentario-avaliacao'),
            controller: _comentario,
            maxLines: 4,
            maxLength: 1000,
            decoration: const InputDecoration(
              hintText: 'Conte como foi o atendimento (opcional)',
            ),
          ),
          const Text(
            'Você pode editar sua avaliação em até 7 dias.',
            style: TextStyle(fontSize: 12, color: corTextoSuave),
          ),
        ],
      ),
      bottomNavigationBar: BarraInferior(
        child: BotaoPrincipal(
          key: const Key('enviar-avaliacao'),
          texto: 'Enviar avaliação',
          carregando: _enviando,
          aoTocar: _nota == 0 ? null : _enviar,
        ),
      ),
    );
  }
}
