import 'package:flutter/material.dart';

import '../api.dart';
import '../componentes.dart';
import '../sessao.dart';
import 'inicio.dart';

/// Tela 27: Perfil, com edição de nome e telefone (RF #07) e o botão Sair (RF #08).
class TelaPerfil extends StatelessWidget {
  const TelaPerfil({super.key});

  Future<void> _sair(BuildContext context) async {
    final confirmar = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Sair da conta?'),
        content: const Text('Você precisará entrar de novo para agendar.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Voltar'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Sair'),
          ),
        ],
      ),
    );
    if (confirmar == true) {
      abaAtual.value = 0;
      await Sessao.instancia.sair();
    }
  }

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: Sessao.instancia,
      builder: (context, _) => _conteudo(context),
    );
  }

  Widget _conteudo(BuildContext context) {
    final usuario = Sessao.instancia.usuario;
    return Scaffold(
      appBar: AppBar(title: const Text('Meu perfil')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Cartao(
            child: Row(
              children: [
                CircleAvatar(
                  radius: 28,
                  backgroundColor: corPrimaria,
                  child: Text(
                    (usuario?.nome.isNotEmpty ?? false)
                        ? usuario!.nome[0].toUpperCase()
                        : '?',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 22,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        usuario?.nome ?? '',
                        style: const TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        usuario?.email ?? '',
                        style: const TextStyle(color: corTextoSuave),
                      ),
                      if (usuario?.telefone != null)
                        Text(
                          usuario!.telefone!,
                          style: const TextStyle(color: corTextoSuave),
                        ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          OutlinedButton.icon(
            key: const Key('editar-perfil'),
            onPressed: () => Navigator.push(
              context,
              MaterialPageRoute(builder: (_) => const TelaEditarPerfil()),
            ),
            icon: const Icon(Icons.edit_outlined),
            label: const Text('Editar dados'),
          ),
          const SizedBox(height: 12),
          OutlinedButton.icon(
            key: const Key('botao-sair'),
            onPressed: () => _sair(context),
            icon: const Icon(Icons.logout),
            label: const Text('Sair'),
            style: OutlinedButton.styleFrom(
              foregroundColor: corErro,
            ),
          ),
        ],
      ),
    );
  }
}

/// Edição de perfil (RF #07): nome e telefone. O e-mail é a credencial e não muda aqui.
class TelaEditarPerfil extends StatefulWidget {
  const TelaEditarPerfil({super.key});

  @override
  State<TelaEditarPerfil> createState() => _TelaEditarPerfilState();
}

class _TelaEditarPerfilState extends State<TelaEditarPerfil> {
  final _nome = TextEditingController(text: Sessao.instancia.usuario?.nome);
  final _telefone = TextEditingController(
    text: Sessao.instancia.usuario?.telefone,
  );
  Map<String, String> _erros = {};
  bool _salvando = false;

  Future<void> _salvar() async {
    FocusScope.of(context).unfocus();
    setState(() {
      _salvando = true;
      _erros = {};
    });
    try {
      await Sessao.instancia.atualizarPerfil(
        _nome.text.trim(),
        _telefone.text.trim(),
      );
      if (!mounted) return;
      Navigator.pop(context);
      avisar(context, 'Dados atualizados.');
    } on ApiException catch (e) {
      setState(
        () => _erros = {
          for (final c in e.erros.keys) c: e.erros[c]!.first,
          if (e.erros.isEmpty) 'nome': e.mensagem,
        },
      );
    } finally {
      if (mounted) setState(() => _salvando = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Editar dados')),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          const Text(
            'Nome completo',
            style: estiloRotulo,
          ),
          const SizedBox(height: 6),
          TextField(
            key: const Key('perfil-nome'),
            controller: _nome,
            decoration: InputDecoration(errorText: _erros['nome']),
          ),
          const SizedBox(height: 16),
          const Text('Telefone', style: estiloRotulo),
          const SizedBox(height: 6),
          TextField(
            key: const Key('perfil-telefone'),
            controller: _telefone,
            keyboardType: TextInputType.phone,
            decoration: InputDecoration(errorText: _erros['telefone']),
          ),
          const SizedBox(height: 8),
          Text(
            'E-mail: ${Sessao.instancia.usuario?.email ?? ''}',
            style: const TextStyle(fontSize: 12, color: corTextoSuave),
          ),
          const SizedBox(height: 20),
          BotaoPrincipal(
            key: const Key('salvar-perfil'),
            texto: 'Salvar alterações',
            carregando: _salvando,
            aoTocar: _salvar,
          ),
        ],
      ),
    );
  }
}
