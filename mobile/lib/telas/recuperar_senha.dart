import 'package:flutter/material.dart';

import '../api.dart';
import '../componentes.dart';

/// Tela 10: recuperar senha (RF #06). Envia um código de 6 dígitos ao e-mail.
class TelaRecuperarSenha extends StatefulWidget {
  const TelaRecuperarSenha({super.key, this.email = ''});

  final String email;

  @override
  State<TelaRecuperarSenha> createState() => _TelaRecuperarSenhaState();
}

class _TelaRecuperarSenhaState extends State<TelaRecuperarSenha> {
  late final _email = TextEditingController(text: widget.email);
  bool _enviando = false;
  String? _erro;
  String? _enviado;

  Future<void> _enviar() async {
    FocusScope.of(context).unfocus();
    setState(() {
      _enviando = true;
      _erro = null;
    });
    try {
      final json = await Api.instancia.post('/auth/esqueci-senha', {
        'email': _email.text.trim(),
      });
      setState(() => _enviado = json['message'] as String);
    } on ApiException catch (e) {
      setState(() => _erro = e.erro('email') ?? e.mensagem);
    } finally {
      if (mounted) setState(() => _enviando = false);
    }
  }

  void _jaTenhoOCodigo() {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => TelaRedefinirSenha(email: _email.text.trim()),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Recuperar senha')),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          const Text(
            'Informe o e-mail da sua conta. Enviaremos um código para você criar uma nova senha.',
            style: TextStyle(color: corTextoSuave),
          ),
          const SizedBox(height: 20),
          TextField(
            key: const Key('recuperar-email'),
            controller: _email,
            keyboardType: TextInputType.emailAddress,
            autocorrect: false,
            decoration: InputDecoration(
              hintText: 'voce@email.com',
              errorText: _erro,
            ),
          ),
          const SizedBox(height: 16),
          BotaoPrincipal(
            key: const Key('enviar-codigo'),
            texto: _enviado == null ? 'Enviar código' : 'Enviar outro código',
            carregando: _enviando,
            aoTocar: _enviar,
          ),
          if (_enviado != null) ...[
            const SizedBox(height: 16),
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: corSucessoSuave,
                borderRadius: BorderRadius.circular(10),
              ),
              child: Text(
                _enviado!,
                style: const TextStyle(color: corSucesso),
              ),
            ),
          ],
          const SizedBox(height: 8),
          TextButton(
            key: const Key('ja-tenho-codigo'),
            onPressed: _jaTenhoOCodigo,
            child: const Text('Já tenho o código'),
          ),
        ],
      ),
    );
  }
}

/// Tela 11: nova senha, com o código recebido por e-mail.
class TelaRedefinirSenha extends StatefulWidget {
  const TelaRedefinirSenha({super.key, required this.email});

  final String email;

  @override
  State<TelaRedefinirSenha> createState() => _TelaRedefinirSenhaState();
}

class _TelaRedefinirSenhaState extends State<TelaRedefinirSenha> {
  late final _email = TextEditingController(text: widget.email);
  final _codigo = TextEditingController();
  final _senha = TextEditingController();
  final _confirmacao = TextEditingController();
  Map<String, String> _erros = {};
  bool _enviando = false;

  Future<void> _salvar() async {
    FocusScope.of(context).unfocus();
    if (_senha.text != _confirmacao.text) {
      setState(
        () => _erros = {'confirmacao': 'As senhas devem ser idênticas.'},
      );
      return;
    }
    setState(() {
      _enviando = true;
      _erros = {};
    });
    try {
      final json = await Api.instancia.post('/auth/redefinir-senha', {
        'email': _email.text.trim(),
        'codigo': _codigo.text.trim(),
        'senha': _senha.text,
      });
      if (!mounted) return;
      Navigator.popUntil(context, (rota) => rota.isFirst);
      avisar(context, json['message'] as String);
    } on ApiException catch (e) {
      setState(() {
        _erros = {for (final c in e.erros.keys) c: e.erros[c]!.first};
        if (_erros.isEmpty) _erros = {'codigo': e.mensagem};
      });
    } finally {
      if (mounted) setState(() => _enviando = false);
    }
  }

  Widget _campo(
    String rotulo,
    TextEditingController c,
    String chave, {
    bool senha = false,
    TextInputType? teclado,
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
            key: Key('redefinir-$chave'),
            controller: c,
            obscureText: senha,
            keyboardType: teclado,
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
      appBar: AppBar(title: const Text('Nova senha')),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          const Text(
            'Defina sua nova senha',
            style: TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
          ),
          const SizedBox(height: 16),
          _campo(
            'E-mail',
            _email,
            'email',
            teclado: TextInputType.emailAddress,
          ),
          _campo(
            'Código recebido por e-mail',
            _codigo,
            'codigo',
            teclado: TextInputType.number,
            dica: '6 dígitos',
          ),
          _campo(
            'Nova senha',
            _senha,
            'senha',
            senha: true,
            dica: 'Mínimo 8 caracteres, 1 especial',
          ),
          _campo(
            'Confirmação da nova senha',
            _confirmacao,
            'confirmacao',
            senha: true,
          ),
          BotaoPrincipal(
            key: const Key('salvar-nova-senha'),
            texto: 'Salvar nova senha',
            carregando: _enviando,
            aoTocar: _salvar,
          ),
          const SizedBox(height: 16),
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: corAlertaSuave,
              borderRadius: BorderRadius.circular(10),
            ),
            child: const Text(
              'O código expira em 30 minutos. Depois disso, será necessário solicitar um novo.',
              style: TextStyle(color: corAlerta),
            ),
          ),
        ],
      ),
    );
  }
}
