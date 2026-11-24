import 'package:flutter/material.dart';

import '../api.dart';
import '../componentes.dart';
import '../sessao.dart';
import 'cadastro.dart';
import 'recuperar_senha.dart';

/// Tela 09: Login (RF #05).
class TelaLogin extends StatefulWidget {
  const TelaLogin({super.key});

  @override
  State<TelaLogin> createState() => _TelaLoginState();
}

class _TelaLoginState extends State<TelaLogin> {
  final _email = TextEditingController();
  final _senha = TextEditingController();
  bool _carregando = false;
  bool _senhaVisivel = false;
  String? _erro;

  Future<void> _entrar() async {
    FocusScope.of(context).unfocus();
    setState(() {
      _carregando = true;
      _erro = null;
    });
    try {
      await Sessao.instancia.entrar(_email.text.trim(), _senha.text);
    } on ApiException catch (e) {
      setState(() => _erro = e.erro('email') ?? e.erro('senha') ?? e.mensagem);
    } finally {
      if (mounted) setState(() => _carregando = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.fromLTRB(20, 40, 20, 20),
          children: [
            const Text(
              'Entrar',
              style: TextStyle(fontSize: 32, fontWeight: FontWeight.w800),
            ),
            const SizedBox(height: 8),
            const Text(
              'Acesse para agendar e acompanhar seus atendimentos.',
              style: TextStyle(color: corTextoSuave),
            ),
            const SizedBox(height: 28),
            const Text('E-mail', style: estiloRotulo),
            const SizedBox(height: 6),
            TextField(
              key: const Key('campo-email'),
              controller: _email,
              keyboardType: TextInputType.emailAddress,
              autocorrect: false,
              textInputAction: TextInputAction.next,
              decoration: const InputDecoration(hintText: 'voce@email.com'),
            ),
            const SizedBox(height: 16),
            const Text('Senha', style: estiloRotulo),
            const SizedBox(height: 6),
            TextField(
              key: const Key('campo-senha'),
              controller: _senha,
              obscureText: !_senhaVisivel,
              onSubmitted: (_) => _entrar(),
              decoration: InputDecoration(
                hintText: 'Digite sua senha',
                suffixIcon: IconButton(
                  icon: Icon(
                    _senhaVisivel ? Icons.visibility_off : Icons.visibility,
                  ),
                  onPressed: () =>
                      setState(() => _senhaVisivel = !_senhaVisivel),
                ),
              ),
            ),
            Align(
              alignment: Alignment.centerRight,
              child: TextButton(
                key: const Key('esqueci-senha'),
                onPressed: () => Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (_) =>
                        TelaRecuperarSenha(email: _email.text.trim()),
                  ),
                ),
                child: const Text(
                  'Esqueci minha senha',
                  style: TextStyle(fontWeight: FontWeight.w700),
                ),
              ),
            ),
            if (_erro != null) ...[
              const SizedBox(height: 14),
              Text(_erro!, style: const TextStyle(color: corErro)),
            ],
            const SizedBox(height: 24),
            BotaoPrincipal(
              texto: 'Entrar',
              carregando: _carregando,
              aoTocar: _entrar,
            ),
            const SizedBox(height: 20),
            Wrap(
              alignment: WrapAlignment.center,
              crossAxisAlignment: WrapCrossAlignment.center,
              children: [
                const Text('Não tem conta?'),
                TextButton(
                  key: const Key('ir-cadastro'),
                  onPressed: () => Navigator.push(
                    context,
                    MaterialPageRoute(builder: (_) => const TelaCadastro()),
                  ),
                  child: const Text(
                    'Cadastre-se',
                    style: TextStyle(fontWeight: FontWeight.w700),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
