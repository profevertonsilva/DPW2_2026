import 'package:flutter/material.dart';

import '../api.dart';
import '../componentes.dart';
import '../sessao.dart';

/// Tela 07: Cadastro (RF #04). A confirmação de senha é só do front-end.
class TelaCadastro extends StatefulWidget {
  const TelaCadastro({super.key});

  @override
  State<TelaCadastro> createState() => _TelaCadastroState();
}

class _TelaCadastroState extends State<TelaCadastro> {
  final _nome = TextEditingController();
  final _email = TextEditingController();
  final _telefone = TextEditingController();
  final _senha = TextEditingController();
  final _confirmacao = TextEditingController();
  Map<String, String> _erros = {};
  String? _erroGeral;
  bool _enviando = false;

  Future<void> _cadastrar() async {
    FocusScope.of(context).unfocus();
    if (_senha.text != _confirmacao.text) {
      setState(() => _erros = {'confirmacao': 'As senhas não conferem.'});
      return;
    }
    setState(() {
      _enviando = true;
      _erros = {};
      _erroGeral = null;
    });
    try {
      await Sessao.instancia.cadastrar({
        'nome': _nome.text.trim(),
        'email': _email.text.trim(),
        'telefone': _telefone.text.trim(),
        'senha': _senha.text,
      });
      // Logado: a raiz do app troca para a busca.
      if (mounted) Navigator.popUntil(context, (rota) => rota.isFirst);
    } on ApiException catch (e) {
      setState(() {
        _erros = {for (final c in e.erros.keys) c: e.erros[c]!.first};
        if (_erros.isEmpty) _erroGeral = e.mensagem;
      });
    } finally {
      if (mounted) setState(() => _enviando = false);
    }
  }

  Widget _campo(
    String rotulo,
    TextEditingController controlador,
    String chave, {
    TextInputType? teclado,
    bool senha = false,
    String? dica,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(rotulo, style: estiloRotulo),
          const SizedBox(height: 6),
          TextField(
            key: Key('cadastro-$chave'),
            controller: controlador,
            keyboardType: teclado,
            obscureText: senha,
            autocorrect: false,
            decoration: InputDecoration(
              hintText: dica,
              errorText: _erros[chave],
              errorMaxLines: 3,
            ),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Criar conta')),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(20, 8, 20, 20),
        children: [
          const Text(
            'Cadastre-se para agendar seus horários.',
            style: TextStyle(color: corTextoSuave),
          ),
          const SizedBox(height: 20),
          _campo('Nome completo', _nome, 'nome', dica: 'Seu nome'),
          _campo(
            'E-mail',
            _email,
            'email',
            teclado: TextInputType.emailAddress,
            dica: 'voce@email.com',
          ),
          _campo(
            'Telefone',
            _telefone,
            'telefone',
            teclado: TextInputType.phone,
            dica: '(19) 99999-9999',
          ),
          _campo(
            'Senha',
            _senha,
            'senha',
            senha: true,
            dica: 'Mínimo de 8 caracteres, com um especial',
          ),
          _campo('Confirmar senha', _confirmacao, 'confirmacao', senha: true),
          if (_erroGeral != null)
            Padding(
              padding: const EdgeInsets.only(bottom: 12),
              child: Text(
                _erroGeral!,
                style: const TextStyle(color: corErro),
              ),
            ),
          const Text(
            'Ao criar a conta, você concorda com os termos de uso do AgendAki.',
            style: TextStyle(fontSize: 12, color: corTextoSuave),
          ),
          const SizedBox(height: 16),
          BotaoPrincipal(
            key: const Key('botao-cadastrar'),
            texto: 'Cadastrar',
            carregando: _enviando,
            aoTocar: _cadastrar,
          ),
        ],
      ),
    );
  }
}
