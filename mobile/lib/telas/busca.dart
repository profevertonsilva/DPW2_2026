import 'package:flutter/material.dart';

import '../api.dart';
import '../componentes.dart';
import '../modelos.dart';
import 'avaliacoes.dart';
import 'barbearia.dart';

/// Telas 01 e 02: Busca e resultados (RF #17, por nome e cidade).
class TelaBusca extends StatefulWidget {
  const TelaBusca({super.key});

  @override
  State<TelaBusca> createState() => _TelaBuscaState();
}

class _TelaBuscaState extends State<TelaBusca> {
  final _nome = TextEditingController();
  final _cidade = TextEditingController();
  List<Barbearia>? _resultados;
  String? _erro;
  bool _carregando = false;

  @override
  void initState() {
    super.initState();
    _buscar();
  }

  Future<void> _buscar() async {
    setState(() {
      _carregando = true;
      _erro = null;
    });
    try {
      final lista = await Api.instancia.get('/barbearias', {
        if (_nome.text.trim().isNotEmpty) 'busca': _nome.text.trim(),
        if (_cidade.text.trim().isNotEmpty) 'cidade': _cidade.text.trim(),
      }) as List;
      setState(() => _resultados = lista.map(Barbearia.fromJson).toList());
    } on ApiException catch (e) {
      setState(() => _erro = e.mensagem);
    } finally {
      if (mounted) setState(() => _carregando = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Encontre sua barbearia')),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 4, 16, 8),
            child: Column(
              children: [
                TextField(
                  key: const Key('busca-nome'),
                  controller: _nome,
                  textInputAction: TextInputAction.search,
                  onSubmitted: (_) => _buscar(),
                  decoration: const InputDecoration(
                    hintText: 'Nome da barbearia',
                    prefixIcon: Icon(Icons.search),
                  ),
                ),
                const SizedBox(height: 8),
                Row(
                  children: [
                    Expanded(
                      child: TextField(
                        key: const Key('busca-cidade'),
                        controller: _cidade,
                        textInputAction: TextInputAction.search,
                        onSubmitted: (_) => _buscar(),
                        decoration: const InputDecoration(
                          hintText: 'Cidade',
                          prefixIcon: Icon(Icons.location_city_outlined),
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),
                    SizedBox(
                      height: 50,
                      child: FilledButton(
                        style: FilledButton.styleFrom(
                          minimumSize: const Size(90, 50),
                        ),
                        onPressed: _carregando ? null : _buscar,
                        child: const Text('Buscar'),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          Expanded(child: _lista()),
        ],
      ),
    );
  }

  Widget _lista() {
    if (_carregando && _resultados == null) {
      return const Center(child: CircularProgressIndicator());
    }
    if (_erro != null) return MensagemErro(mensagem: _erro!, aoTentar: _buscar);
    final resultados = _resultados ?? [];
    if (resultados.isEmpty) {
      return const Vazio(
        'Nenhuma barbearia encontrada.\nTente outro nome ou cidade.',
        icone: Icons.storefront_outlined,
      );
    }
    return RefreshIndicator(
      onRefresh: _buscar,
      child: ListView.separated(
        padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
        itemCount: resultados.length,
        separatorBuilder: (_, _) => const SizedBox(height: 10),
        itemBuilder: (context, i) {
          final b = resultados[i];
          return Cartao(
            sombra: true,
            padding: const EdgeInsets.all(12),
            aoTocar: () => Navigator.push(
              context,
              MaterialPageRoute(
                builder: (_) => TelaBarbearia(id: b.id, nome: b.nome),
              ),
            ),
            child: Row(
              children: [
                const FotoBarbearia(),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        b.nome,
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        b.endereco,
                        style: const TextStyle(color: corTextoSuave),
                      ),
                      Text(
                        b.cidade,
                        style: const TextStyle(color: corTextoSuave),
                      ),
                      if (b.mediaAvaliacao != null) ...[
                        const SizedBox(height: 4),
                        Row(
                          children: [
                            const Icon(
                              Icons.star,
                              size: 16,
                              color: corDestaque,
                            ),
                            const SizedBox(width: 4),
                            Text(
                              '${notaFormatada(b.mediaAvaliacao!)} (${b.totalAvaliacoes})',
                              style: const TextStyle(
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ],
                        ),
                      ],
                    ],
                  ),
                ),
                const Icon(Icons.chevron_right, color: corTextoSuave),
              ],
            ),
          );
        },
      ),
    );
  }
}
