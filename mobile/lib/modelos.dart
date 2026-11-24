/// Modelos simples, lidos do JSON da API.
library;

class Usuario {
  Usuario({required this.nome, required this.email, this.telefone});

  factory Usuario.fromJson(dynamic j) => Usuario(
    nome: j['nome'] as String? ?? '',
    email: j['email'] as String,
    telefone: j['telefone'] as String?,
  );

  final String nome;
  final String email;
  final String? telefone;
}

class Servico {
  Servico({
    required this.id,
    required this.nome,
    required this.preco,
    required this.duracaoMinutos,
    this.descricao,
  });

  factory Servico.fromJson(dynamic j) => Servico(
    id: j['id'] as int,
    nome: j['nome'] as String,
    descricao: j['descricao'] as String?,
    preco: (j['preco'] as num).toDouble(),
    duracaoMinutos: j['duracao_minutos'] as int,
  );

  final int id;
  final String nome;
  final String? descricao;
  final double preco;
  final int duracaoMinutos;
}

class Barbeiro {
  Barbeiro({
    required this.vinculoId,
    required this.nome,
    required this.servicoIds,
  });

  factory Barbeiro.fromJson(dynamic j) => Barbeiro(
    vinculoId: j['vinculo_id'] as int,
    nome: j['nome'] as String,
    servicoIds: List<int>.from(j['servico_ids'] as List),
  );

  final int vinculoId;
  final String nome;
  final List<int> servicoIds;
}

class HorarioFuncionamento {
  HorarioFuncionamento({
    required this.diaSemana,
    required this.aberto,
    this.abertura,
    this.fechamento,
  });

  factory HorarioFuncionamento.fromJson(dynamic j) => HorarioFuncionamento(
    diaSemana: j['dia_semana'] as int,
    aberto: j['aberto'] as bool,
    abertura: j['hora_abertura'] as String?,
    fechamento: j['hora_fechamento'] as String?,
  );

  /// 0 = domingo a 6 = sábado.
  final int diaSemana;
  final bool aberto;
  final String? abertura;
  final String? fechamento;
}

class Barbearia {
  Barbearia({
    required this.id,
    required this.nome,
    required this.endereco,
    required this.cidade,
    this.descricao,
    this.telefone,
    this.horarios = const [],
    this.servicos = const [],
    this.barbeiros = const [],
    this.mediaAvaliacao,
    this.totalAvaliacoes = 0,
  });

  factory Barbearia.fromJson(dynamic j) {
    final e = j['endereco'];
    final complemento = e['complemento'] as String?;
    return Barbearia(
      id: j['id'] as int,
      nome: j['nome'] as String,
      descricao: j['descricao'] as String?,
      telefone: j['telefone'] as String?,
      endereco:
          '${e['logradouro']}, ${e['numero']}'
          '${complemento == null || complemento.isEmpty ? '' : ' - $complemento'}'
          ' — ${e['bairro']}',
      cidade: '${e['cidade']}/${e['estado']}',
      horarios: [
        for (final h in (j['horarios'] as List? ?? []))
          HorarioFuncionamento.fromJson(h),
      ],
      servicos: [
        for (final s in (j['servicos'] as List? ?? [])) Servico.fromJson(s),
      ],
      barbeiros: [
        for (final b in (j['barbeiros'] as List? ?? [])) Barbeiro.fromJson(b),
      ],
      mediaAvaliacao: (j['avaliacao']?['media'] as num?)?.toDouble(),
      totalAvaliacoes: j['avaliacao']?['total'] as int? ?? 0,
    );
  }

  final int id;
  final String nome;
  final String? descricao;
  final String? telefone;
  final String endereco;
  final String cidade;
  final List<HorarioFuncionamento> horarios;
  final List<Servico> servicos;
  final List<Barbeiro> barbeiros;

  /// RF #30: média das avaliações (null sem avaliações).
  final double? mediaAvaliacao;
  final int totalAvaliacoes;

  List<Barbeiro> barbeirosDo(Servico servico) =>
      barbeiros.where((b) => b.servicoIds.contains(servico.id)).toList();
}

/// Horário livre na grade: instante ISO 8601 (-03:00) e hora pronta (HH:mm).
class HorarioLivre {
  HorarioLivre(this.inicio, this.hora);

  factory HorarioLivre.fromJson(dynamic j) =>
      HorarioLivre(j['inicio'] as String, j['hora'] as String);

  /// A hora vem no próprio texto ISO, sem converter para o fuso do aparelho.
  factory HorarioLivre.doIso(String iso) =>
      HorarioLivre(iso, iso.substring(11, 16));

  final String inicio;
  final String hora;
}

class Agendamento {
  Agendamento({
    required this.id,
    required this.status,
    required this.inicio,
    required this.data,
    required this.horaInicio,
    required this.horaFim,
    required this.valor,
    required this.servico,
    required this.duracaoMinutos,
    required this.barbeiro,
    required this.barbearia,
    required this.enderecoBarbearia,
    required this.podeCancelar,
    required this.podeRemarcar,
    this.telefoneBarbearia,
    this.motivoCancelamento,
    this.notaAvaliacao,
    this.comentarioAvaliacao,
    this.podeAvaliar = false,
  });

  factory Agendamento.fromJson(dynamic j) => Agendamento(
    id: j['id'] as int,
    status: j['status'] as String,
    inicio: j['inicio'] as String,
    data: j['data'] as String,
    horaInicio: j['hora_inicio'] as String,
    horaFim: j['hora_fim'] as String,
    valor: (j['valor'] as num).toDouble(),
    servico: j['servico']?['nome'] as String? ?? '',
    duracaoMinutos: j['servico']?['duracao_minutos'] as int? ?? 0,
    barbeiro: j['barbeiro']['nome'] as String,
    barbearia: j['barbearia']['nome'] as String,
    enderecoBarbearia: j['barbearia']['endereco'] as String,
    telefoneBarbearia: j['barbearia']['telefone'] as String?,
    motivoCancelamento: j['motivo_cancelamento'] as String?,
    podeCancelar: j['pode_cancelar'] as bool,
    podeRemarcar: j['pode_remarcar'] as bool,
    notaAvaliacao: j['avaliacao']?['nota'] as int?,
    comentarioAvaliacao: j['avaliacao']?['comentario'] as String?,
    podeAvaliar: j['pode_avaliar'] as bool? ?? false,
  );

  final int id;
  final String status;
  final String inicio;
  final String data;
  final String horaInicio;
  final String horaFim;
  final double valor;
  final String servico;
  final int duracaoMinutos;
  final String barbeiro;
  final String barbearia;
  final String enderecoBarbearia;
  final String? telefoneBarbearia;
  final String? motivoCancelamento;
  final bool podeCancelar;
  final bool podeRemarcar;
  final int? notaAvaliacao;
  final String? comentarioAvaliacao;
  final bool podeAvaliar;
}
