import 'package:flutter/material.dart';

import 'api.dart';
import 'componentes.dart';
import 'sessao.dart';
import 'telas/inicio.dart';
import 'telas/login.dart';

final chaveNavegacao = GlobalKey<NavigatorState>();

void main() {
  WidgetsFlutterBinding.ensureInitialized();

  // Token inválido no meio do uso: volta para o login.
  Api.instancia.aoPerderSessao = () {
    chaveNavegacao.currentState?.popUntil((rota) => rota.isFirst);
    Sessao.instancia.expirou();
  };

  runApp(const AgendakiApp());
}

class AgendakiApp extends StatefulWidget {
  const AgendakiApp({super.key});

  @override
  State<AgendakiApp> createState() => _AgendakiAppState();
}

class _AgendakiAppState extends State<AgendakiApp> {
  late final Future<void> _restauracao = Sessao.instancia.restaurar();

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'AgendAki',
      debugShowCheckedModeBanner: false,
      navigatorKey: chaveNavegacao,
      theme: temaAgendaki(),
      home: FutureBuilder(
        future: _restauracao,
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done) {
            return const Scaffold(
              body: Center(child: CircularProgressIndicator()),
            );
          }
          // Com token válido, vai direto para a busca (seção 8.1).
          return ListenableBuilder(
            listenable: Sessao.instancia,
            builder: (context, _) => Sessao.instancia.logado
                ? const TelaInicio()
                : const TelaLogin(),
          );
        },
      ),
    );
  }
}
