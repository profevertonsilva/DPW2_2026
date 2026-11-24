import 'package:flutter/material.dart';

import '../api.dart';
import '../componentes.dart';
import '../formatos.dart';
import '../modelos.dart';
import 'agendar.dart';
import 'avaliacoes.dart';

/// Tela 05: Página da barbearia, com catálogo e horário (RF #15).
class TelaBarbearia extends StatefulWidget {
  const TelaBarbearia({super.key, required this.id, required this.nome});

  final int id;
  final String nome;

  @override
  State<TelaBarbearia> createState() => _TelaBarbeariaState();
}

class _TelaBarbeariaState extends State<TelaBarbearia> {
  Barbearia? _barbearia;
  String? _erro;

  @override
  void initState() {
    super.initState();
    _carregar();
  }

  Future<void> _carregar() async {
    setState(() => _erro = null);
    try {
      final json = await Api.instancia.get('/barbearias/${widget.id}');
      setState(() => _barbearia = Barbearia.fromJson(json));
    } on ApiException catch (e) {
      setState(() => _erro = e.mensagem);
    }
  }

  void _agendar({Servico? servico}) {
    final b = _barbearia!;
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => servico == null
            ? TelaEscolherServico(barbearia: b)
            : TelaEscolherBarbeiro(barbearia: b, servico: servico),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final b = _barbearia;
    return Scaffold(
      appBar: AppBar(title: Text(widget.nome)),
      body: _erro != null
          ? MensagemErro(mensagem: _erro!, aoTentar: _carregar)
          : b == null
          ? const Center(child: CircularProgressIndicator())
          : ListView(
              padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
              children: [
                const SizedBox(height: 16),
                const CapaBarbearia(),
                const SizedBox(height: 16),
                Text(
                  b.nome,
                  style: const TextStyle(
                    fontSize: 26,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                const SizedBox(height: 8),
                if (b.descricao != null && b.descricao!.isNotEmpty) ...[
                  Text(b.descricao!),
                  const SizedBox(height: 8),
                ],
                if (b.mediaAvaliacao != null)
                  InkWell(
                    key: const Key('ver-avaliacoes'),
                    onTap: () => Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (_) =>
                            TelaAvaliacoes(barbeariaId: b.id, nome: b.nome),
                      ),
                    ),
                    child: Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: Row(
                        children: [
                          Estrelas(b.mediaAvaliacao!),
                          const SizedBox(width: 6),
                          Text(
                            '${notaFormatada(b.mediaAvaliacao!)} · ${b.totalAvaliacoes == 1 ? '1 avaliação' : '${b.totalAvaliacoes} avaliações'}',
                            style: const TextStyle(
                              color: corPrimaria,
                              fontWeight: FontWeight.w600,
                              decoration: TextDecoration.underline,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                _Info(Icons.place_outlined, '${b.endereco}\n${b.cidade}'),
                _Info(Icons.schedule, resumoHorarios(b.horarios)),
                if (b.telefone != null)
                  _Info(Icons.phone_outlined, b.telefone!),
                const SizedBox(height: 16),
                const Text(
                  'Serviços',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
                ),
                const SizedBox(height: 10),
                if (b.servicos.isEmpty)
                  const Text(
                    'Esta barbearia ainda não cadastrou serviços.',
                    style: TextStyle(color: corTextoSuave),
                  ),
                for (final s in b.servicos) ...[
                  Cartao(
                    aoTocar: b.barbeirosDo(s).isEmpty
                        ? null
                        : () => _agendar(servico: s),
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
                              const SizedBox(height: 2),
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
                            fontSize: 16,
                            fontWeight: FontWeight.w700,
                            color: corPrimaria,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 10),
                ],
              ],
            ),
      bottomNavigationBar: b == null || b.barbeiros.isEmpty
          ? null
          : BarraInferior(
              child: BotaoPrincipal(
                key: const Key('agendar-agora'),
                texto: 'Agendar agora',
                destaque: true,
                aoTocar: () => _agendar(),
              ),
            ),
    );
  }
}

class _Info extends StatelessWidget {
  const _Info(this.icone, this.texto);

  final IconData icone;
  final String texto;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icone, size: 18, color: corTextoSuave),
          const SizedBox(width: 8),
          Expanded(
            child: Text(texto, style: const TextStyle(color: corTextoSuave)),
          ),
        ],
      ),
    );
  }
}
