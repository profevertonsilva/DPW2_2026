import 'dart:convert';

import 'package:http/http.dart' as http;

/// URL base da API. No emulador Android, o computador é 10.0.2.2.
/// Para outro endereço: flutter run --dart-define=API_URL=http://IP:8000/api
const String apiUrl = String.fromEnvironment(
  'API_URL',
  defaultValue: 'http://10.0.2.2:8000/api',
);

/// Erro devolvido pela API (ou de rede), com a mensagem em português.
class ApiException implements Exception {
  ApiException(this.status, this.mensagem, {this.erros = const {}, this.corpo});

  final int status;
  final String mensagem;
  final Map<String, List<String>> erros;
  final Map<String, dynamic>? corpo;

  /// Primeira mensagem de validação de um campo, se houver.
  String? erro(String campo) => erros[campo]?.first;

  @override
  String toString() => mensagem;
}

/// Cliente HTTP simples da API do AgendAki (token Bearer do Sanctum).
class Api {
  Api._();

  static final Api instancia = Api._();

  http.Client cliente = http.Client();
  String? token;

  /// Chamado quando a API responde 401 (token inválido ou expirado).
  void Function()? aoPerderSessao;

  Future<dynamic> get(String caminho, [Map<String, String>? query]) =>
      _enviar('GET', caminho, query: query);

  Future<dynamic> post(String caminho, [Map<String, dynamic>? corpo]) =>
      _enviar('POST', caminho, corpo: corpo);

  Future<dynamic> put(String caminho, Map<String, dynamic> corpo) =>
      _enviar('PUT', caminho, corpo: corpo);

  Future<dynamic> _enviar(
    String metodo,
    String caminho, {
    Map<String, String>? query,
    Map<String, dynamic>? corpo,
  }) async {
    final uri = Uri.parse('$apiUrl$caminho').replace(queryParameters: query);
    final cabecalhos = {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    };

    http.Response resposta;
    try {
      final requisicao = http.Request(metodo, uri)..headers.addAll(cabecalhos);
      if (corpo != null) requisicao.body = jsonEncode(corpo);
      resposta = await http.Response.fromStream(
        await cliente.send(requisicao).timeout(const Duration(seconds: 15)),
      );
    } catch (_) {
      throw ApiException(
        0,
        'Não foi possível falar com o servidor. Verifique a conexão e tente de novo.',
      );
    }

    final dynamic json = resposta.body.isEmpty
        ? null
        : jsonDecode(utf8.decode(resposta.bodyBytes));

    if (resposta.statusCode >= 200 && resposta.statusCode < 300) {
      return json;
    }

    if (resposta.statusCode == 401 && token != null) {
      aoPerderSessao?.call();
    }

    final mapa = json is Map<String, dynamic> ? json : <String, dynamic>{};
    final erros = <String, List<String>>{};
    if (mapa['errors'] is Map) {
      (mapa['errors'] as Map).forEach((campo, lista) {
        erros[campo.toString()] = List<String>.from(lista as List);
      });
    }

    throw ApiException(
      resposta.statusCode,
      (mapa['message'] as String?) ??
          'Erro inesperado (${resposta.statusCode}).',
      erros: erros,
      corpo: mapa,
    );
  }
}
