import 'dart:convert';

import 'package:agendaki/api.dart';
import 'package:agendaki/formatos.dart';
import 'package:agendaki/main.dart';
import 'package:agendaki/modelos.dart';
import 'package:agendaki/sessao.dart';
import 'package:agendaki/telas/perfil.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:shared_preferences/shared_preferences.dart';

http.Response json(Object corpo, [int status = 200]) => http.Response(
  jsonEncode(corpo),
  status,
  headers: {'content-type': 'application/json; charset=utf-8'},
);

void main() {
  setUp(() {
    SharedPreferences.setMockInitialValues({});
    // A sessão é um singleton: cada teste começa deslogado.
    Sessao.instancia.usuario = null;
    Api.instancia.token = null;
  });

  test('formata dinheiro em reais', () {
    expect(dinheiro(40), 'R\$ 40,00');
    expect(dinheiro(1234.5), 'R\$ 1.234,50');
  });

  test('usa a convenção de dia da semana da API (0 = domingo)', () {
    expect(diaSemana(DateTime(2026, 11, 22)), 0); // domingo
    expect(diaSemana(DateTime(2026, 11, 16)), 1); // segunda
    expect(dataCurta(DateTime(2026, 11, 17)), 'Terça, 17/11');
  });

  testWidgets(
    'sem token, abre o login; com credenciais válidas, vai para a busca',
    (tester) async {
      Api.instancia.cliente = MockClient((requisicao) async {
        if (requisicao.url.path.endsWith('/auth/login')) {
          return json({
            'token': '1|abc',
            'usuario': {
              'nome': 'Lucas',
              'email': 'cliente@agendaki.test',
              'barbearias': [],
            },
          });
        }
        if (requisicao.url.path.endsWith('/barbearias')) {
          return json([
            {
              'id': 1,
              'nome': 'Barbearia Central',
              'endereco': {
                'logradouro': 'Rua das Palmeiras',
                'numero': 120,
                'bairro': 'Centro',
                'cidade': 'São João da Boa Vista',
                'estado': 'SP',
                'complemento': null,
              },
            },
          ]);
        }
        return json({'message': 'Não encontrado'}, 404);
      });

      await tester.pumpWidget(const AgendakiApp());
      await tester.pumpAndSettle();
      expect(find.text('Entrar'), findsWidgets);

      await tester.enterText(
        find.byKey(const Key('campo-email')),
        'cliente@agendaki.test',
      );
      await tester.enterText(
        find.byKey(const Key('campo-senha')),
        'Agendaki@2026',
      );
      await tester.tap(find.widgetWithText(FilledButton, 'Entrar'));
      await tester.pumpAndSettle();

      expect(find.text('Encontre sua barbearia'), findsOneWidget);
      expect(find.text('Barbearia Central'), findsOneWidget);
    },
  );

  testWidgets('no cadastro, recusa senhas diferentes antes de chamar a API', (
    tester,
  ) async {
    // Tela de celular (Pixel 8), para o formulário inteiro caber.
    tester.view.physicalSize = const Size(1080, 2400);
    tester.view.devicePixelRatio = 2.625;
    addTearDown(tester.view.reset);
    var chamadas = 0;
    Api.instancia.cliente = MockClient((_) async {
      chamadas++;
      return json({}, 500);
    });

    await tester.pumpWidget(const AgendakiApp());
    await tester.pumpAndSettle();
    await tester.tap(find.byKey(const Key('ir-cadastro')));
    await tester.pumpAndSettle();

    await tester.enterText(
      find.byKey(const Key('cadastro-senha')),
      'Senha@123',
    );
    await tester.enterText(
      find.byKey(const Key('cadastro-confirmacao')),
      'Outra@123',
    );
    await tester.ensureVisible(find.byKey(const Key('botao-cadastrar')));
    await tester.tap(find.byKey(const Key('botao-cadastrar')));
    await tester.pumpAndSettle();

    expect(find.text('As senhas não conferem.'), findsOneWidget);
    expect(chamadas, 0);
  });

  testWidgets('edita nome e telefone no perfil (RF #07)', (tester) async {
    tester.view.physicalSize = const Size(1080, 2400);
    tester.view.devicePixelRatio = 2.625;
    addTearDown(tester.view.reset);
    Map<String, dynamic>? enviado;
    Api.instancia.cliente = MockClient((requisicao) async {
      if (requisicao.method == 'PUT' && requisicao.url.path.endsWith('/me')) {
        enviado = jsonDecode(requisicao.body) as Map<String, dynamic>;
        return json({
          'nome': enviado!['nome'],
          'telefone': enviado!['telefone'],
          'email': 'lucas@agendaki.test',
          'barbearias': [],
        });
      }
      return json([]);
    });
    Sessao.instancia.usuario = Usuario(
      nome: 'Lucas',
      email: 'lucas@agendaki.test',
      telefone: '(19) 99999-0000',
    );

    await tester.pumpWidget(const MaterialApp(home: TelaPerfil()));
    await tester.tap(find.byKey(const Key('editar-perfil')));
    await tester.pumpAndSettle();
    await tester.enterText(
      find.byKey(const Key('perfil-nome')),
      'Lucas Pereira',
    );
    await tester.tap(find.byKey(const Key('salvar-perfil')));
    await tester.pumpAndSettle();

    expect(enviado, {'nome': 'Lucas Pereira', 'telefone': '(19) 99999-0000'});
    expect(find.text('Lucas Pereira'), findsOneWidget);
  });

  testWidgets('mostra a mensagem genérica de login inválido', (tester) async {
    Api.instancia.cliente = MockClient(
      (_) async => json({
        'message': 'E-mail ou senha inválidos.',
        'errors': {
          'email': ['E-mail ou senha inválidos.'],
        },
      }, 422),
    );

    await tester.pumpWidget(const AgendakiApp());
    await tester.pumpAndSettle();
    await tester.enterText(find.byKey(const Key('campo-email')), 'x@y.z');
    await tester.enterText(find.byKey(const Key('campo-senha')), 'errada');
    await tester.tap(find.widgetWithText(FilledButton, 'Entrar'));
    await tester.pumpAndSettle();

    expect(find.text('E-mail ou senha inválidos.'), findsOneWidget);
  });
}
