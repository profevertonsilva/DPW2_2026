import 'package:flutter/material.dart';

import '../api.dart';
import '../componentes.dart';
import '../formatos.dart';
import '../modelos.dart';
import 'detalhe_agendamento.dart';

/// Telas 21 e 22: Meus agendamentos, próximos e histórico (RF #33).
class TelaMeusAgendamentos extends StatelessWidget {
  const TelaMeusAgendamentos({super.key});

  @override
  Widget build(BuildContext context) {
    return DefaultTabController(
      length: 2,
      child: Scaffold(
        appBar: AppBar(
          title: const Text('Meus agendamentos'),
          bottom: const TabBar(
            tabs: [
              Tab(text: 'Próximos'),
              Tab(text: 'Histórico'),
            ],
          ),
        ),
        body: const TabBarView(
          children: [
            ListaAgendamentos(periodo: 'proximos', aoAbrir: abrirDetalhe),
            ListaAgendamentos(periodo: 'historico', aoAbrir: abrirDetalhe),
          ],
        ),
      ),
    );
  }
}

Future<void> abrirDetalhe(BuildContext context, Agendamento agendamento) =>
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => TelaDetalheAgendamento(agendamento: agendamento),
      ),
    );

class ListaAgendamentos extends StatefulWidget {
  const ListaAgendamentos({super.key, required this.periodo, this.aoAbrir});

  final String periodo;

  /// Abre o detalhe; devolve quando o usuário volta (para recarregar).
  final Future<void> Function(BuildContext context, Agendamento agendamento)?
  aoAbrir;

  @override
  State<ListaAgendamentos> createState() => _ListaAgendamentosState();
}

class _ListaAgendamentosState extends State<ListaAgendamentos> {
  List<Agendamento>? _itens;
  String? _erro;

  @override
  void initState() {
    super.initState();
    _carregar();
  }

  Future<void> _carregar() async {
    setState(() => _erro = null);
    try {
      final lista = await Api.instancia.get('/agendamentos', {
        'periodo': widget.periodo,
      }) as List;
      if (mounted) {
        setState(() => _itens = lista.map(Agendamento.fromJson).toList());
      }
    } on ApiException catch (e) {
      if (mounted) setState(() => _erro = e.mensagem);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_erro != null) {
      return MensagemErro(mensagem: _erro!, aoTentar: _carregar);
    }
    final itens = _itens;
    if (itens == null) return const Center(child: CircularProgressIndicator());

    return RefreshIndicator(
      onRefresh: _carregar,
      child: itens.isEmpty
          ? ListView(
              children: [
                const SizedBox(height: 80),
                Vazio(
                  widget.periodo == 'proximos'
                      ? 'Você não tem agendamentos marcados.\nBusque uma barbearia para agendar.'
                      : 'Nenhum agendamento no histórico.',
                ),
              ],
            )
          : ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: itens.length,
              separatorBuilder: (_, _) => const SizedBox(height: 10),
              itemBuilder: (context, i) => _CartaoAgendamento(
                agendamento: itens[i],
                aoTocar: widget.aoAbrir == null
                    ? null
                    : () async {
                        await widget.aoAbrir!(context, itens[i]);
                        _carregar();
                      },
              ),
            ),
    );
  }
}

class _CartaoAgendamento extends StatelessWidget {
  const _CartaoAgendamento({required this.agendamento, this.aoTocar});

  final Agendamento agendamento;
  final VoidCallback? aoTocar;

  @override
  Widget build(BuildContext context) {
    final a = agendamento;
    return Cartao(
      aoTocar: aoTocar,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  a.barbearia,
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
              EtiquetaStatus(a.status),
            ],
          ),
          const SizedBox(height: 6),
          Text(
            '${a.servico} · ${a.barbeiro}',
            style: const TextStyle(color: corTextoSuave),
          ),
          const SizedBox(height: 4),
          Text(
            '${dataCurta(dataDaApi(a.data))} às ${a.horaInicio}',
            style: const TextStyle(fontWeight: FontWeight.w600),
          ),
        ],
      ),
    );
  }
}
