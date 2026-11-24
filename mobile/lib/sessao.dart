import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'api.dart';
import 'modelos.dart';

/// Sessão do usuário: o token fica guardado no aparelho (seção 8.1 do briefing).
class Sessao extends ChangeNotifier {
  Sessao._();

  static final Sessao instancia = Sessao._();

  static const _chaveToken = 'token';

  Usuario? usuario;

  bool get logado => usuario != null;

  /// Ao abrir o app: se houver token válido, carrega o usuário.
  Future<void> restaurar() async {
    final preferencias = await SharedPreferences.getInstance();
    final token = preferencias.getString(_chaveToken);
    if (token == null) return;

    Api.instancia.token = token;
    try {
      usuario = Usuario.fromJson(await Api.instancia.get('/me'));
    } on ApiException catch (e) {
      // Token inválido: descarta. Sem rede: também volta ao login.
      if (e.status == 401 || e.status == 0) {
        await _limpar(preferencias);
      }
    }
    notifyListeners();
  }

  Future<void> entrar(String email, String senha) async {
    final resposta = await Api.instancia.post('/auth/login', {
      'email': email,
      'senha': senha,
      'dispositivo': 'app',
    });
    await _iniciar(resposta);
  }

  Future<void> cadastrar(Map<String, dynamic> dados) async {
    final resposta = await Api.instancia.post('/auth/cadastro', {
      ...dados,
      'dispositivo': 'app',
    });
    await _iniciar(resposta);
  }

  /// Edição de perfil (RF #07): nome e telefone.
  Future<void> atualizarPerfil(String nome, String telefone) async {
    final json = await Api.instancia.put('/me', {
      'nome': nome,
      'telefone': telefone,
    });
    usuario = Usuario.fromJson(json);
    notifyListeners();
  }

  Future<void> sair() async {
    try {
      await Api.instancia.post('/auth/logout');
    } on ApiException {
      // Mesmo sem resposta da API, a sessão local é encerrada.
    }
    await _limpar(await SharedPreferences.getInstance());
    notifyListeners();
  }

  /// Chamado quando a API responde 401 no meio do uso.
  Future<void> expirou() async {
    await _limpar(await SharedPreferences.getInstance());
    notifyListeners();
  }

  Future<void> _iniciar(dynamic resposta) async {
    final token = resposta['token'] as String;
    Api.instancia.token = token;
    usuario = Usuario.fromJson(resposta['usuario']);
    final preferencias = await SharedPreferences.getInstance();
    await preferencias.setString(_chaveToken, token);
    notifyListeners();
  }

  Future<void> _limpar(SharedPreferences preferencias) async {
    await preferencias.remove(_chaveToken);
    Api.instancia.token = null;
    usuario = null;
  }
}
