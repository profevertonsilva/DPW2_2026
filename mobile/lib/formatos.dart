/// Formatação para exibição, sem dependências extras.
library;

import 'modelos.dart';

const diasSemanaCurtos = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
const diasSemanaLongos = [
  'domingo',
  'segunda-feira',
  'terça-feira',
  'quarta-feira',
  'quinta-feira',
  'sexta-feira',
  'sábado',
];
const meses = [
  'janeiro',
  'fevereiro',
  'março',
  'abril',
  'maio',
  'junho',
  'julho',
  'agosto',
  'setembro',
  'outubro',
  'novembro',
  'dezembro',
];

/// R$ 1.234,50
String dinheiro(double valor) {
  final partes = valor.toStringAsFixed(2).split('.');
  final inteiro = partes[0].replaceAllMapped(
    RegExp(r'\B(?=(\d{3})+(?!\d))'),
    (_) => '.',
  );
  return 'R\$ $inteiro,${partes[1]}';
}

/// Dia de hoje em São Paulo (UTC-3, sem horário de verão desde 2019),
/// independente do fuso configurado no aparelho (RNF #09).
DateTime hojeEmSaoPaulo() {
  final agora = DateTime.now().toUtc().subtract(const Duration(hours: 3));
  return DateTime(agora.year, agora.month, agora.day);
}

/// 0 = domingo a 6 = sábado (convenção da API).
int diaSemana(DateTime dia) => dia.weekday % 7;

String doisDigitos(int n) => n.toString().padLeft(2, '0');

/// AAAA-MM-DD
String dataApi(DateTime dia) =>
    '${dia.year}-${doisDigitos(dia.month)}-${doisDigitos(dia.day)}';

/// terça-feira, 17 de novembro
String dataPorExtenso(DateTime dia) =>
    '${diasSemanaLongos[diaSemana(dia)]}, ${dia.day} de ${meses[dia.month - 1]}';

/// Terça, 17/11
String dataCurta(DateTime dia) {
  final nome = diasSemanaLongos[diaSemana(dia)].split('-').first;
  return '${nome[0].toUpperCase()}${nome.substring(1)}, '
      '${doisDigitos(dia.day)}/${doisDigitos(dia.month)}';
}

/// Converte 'dd/MM/yyyy' (formato da API) em DateTime local, só a data.
DateTime dataDaApi(String ddMMyyyy) {
  final p = ddMMyyyy.split('/');
  return DateTime(int.parse(p[2]), int.parse(p[1]), int.parse(p[0]));
}

/// "Seg a Sex, 09:00 às 19:00 · Sáb, 08:00 às 17:00" a partir dos sete dias.
String resumoHorarios(List<HorarioFuncionamento> horarios) {
  final grupos = <String, List<int>>{};
  for (final h in horarios) {
    if (!h.aberto) continue;
    grupos
        .putIfAbsent('${h.abertura} às ${h.fechamento}', () => [])
        .add(h.diaSemana);
  }
  if (grupos.isEmpty) return 'Sem horário de atendimento cadastrado';

  // Segunda primeiro, domingo por último.
  int ordem(int d) => d == 0 ? 7 : d;
  final partes = grupos.entries.toList()
    ..sort((a, b) => ordem(a.value.first).compareTo(ordem(b.value.first)));

  return partes
      .map((g) {
        final dias = g.value..sort((a, b) => ordem(a).compareTo(ordem(b)));
        final consecutivos =
            dias.length > 2 &&
            ordem(dias.last) - ordem(dias.first) == dias.length - 1;
        final nomes = consecutivos
            ? '${diasSemanaCurtos[dias.first]} a ${diasSemanaCurtos[dias.last]}'
            : dias.map((d) => diasSemanaCurtos[d]).join(', ');
        return '$nomes, ${g.key}';
      })
      .join(' · ');
}
