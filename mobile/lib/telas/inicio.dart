import 'package:flutter/material.dart';

import 'busca.dart';
import 'meus_agendamentos.dart';
import 'perfil.dart';

/// Aba ativa da barra inferior. O comprovante usa para abrir "Agenda".
final abaAtual = ValueNotifier<int>(0);

/// Contador para recarregar "Meus agendamentos" ao voltar para a aba.
final versaoAgenda = ValueNotifier<int>(0);

/// Estrutura com a barra inferior: Buscar, Agenda e Perfil.
class TelaInicio extends StatelessWidget {
  const TelaInicio({super.key});

  @override
  Widget build(BuildContext context) {
    return ValueListenableBuilder<int>(
      valueListenable: abaAtual,
      builder: (context, aba, _) => Scaffold(
        body: IndexedStack(
          index: aba,
          children: [
            const TelaBusca(),
            ValueListenableBuilder<int>(
              valueListenable: versaoAgenda,
              builder: (context, versao, _) =>
                  TelaMeusAgendamentos(key: ValueKey(versao)),
            ),
            const TelaPerfil(),
          ],
        ),
        bottomNavigationBar: NavigationBar(
          selectedIndex: aba,
          onDestinationSelected: (i) {
            if (i == 1) versaoAgenda.value++;
            abaAtual.value = i;
          },
          destinations: const [
            NavigationDestination(icon: Icon(Icons.search), label: 'Buscar'),
            NavigationDestination(
              icon: Icon(Icons.event_note),
              label: 'Agenda',
            ),
            NavigationDestination(
              icon: Icon(Icons.person_outline),
              label: 'Perfil',
            ),
          ],
        ),
      ),
    );
  }
}
