// Tipos das respostas da API (ver docs/api.http).

export type Papel = 'proprietario' | 'profissional' | 'gerente' | 'atendente'

export type BarbeariaDoUsuario = {
  id: number
  nome: string
  papel: Papel
  vinculo_id: number
}

export type Usuario = {
  id: number
  email: string
  nome: string
  telefone: string | null
  barbearias: BarbeariaDoUsuario[]
}

export type HorarioFuncionamento = {
  dia_semana: number
  aberto: boolean
  hora_abertura: string | null
  hora_fechamento: string | null
}

export type Servico = {
  id: number
  nome: string
  descricao: string | null
  preco: number
  duracao_minutos: number
  status: 'a' | 'i'
}

export type Barbearia = {
  id: number
  nome: string
  descricao: string | null
  telefone: string | null
  endereco: {
    cep: string | null
    estado: string | null
    cidade: string | null
    bairro: string | null
    logradouro: string | null
    numero: number | null
    complemento: string | null
  }
  horarios: HorarioFuncionamento[]
  servicos: Servico[]
  barbeiros: { vinculo_id: number; nome: string; servico_ids: number[] }[]
  status: 'a' | 'i'
  papel: Papel
}

export type FaixaJornada = { dia_semana: number; hora_inicio: string; hora_fim: string }

export type MembroEquipe = {
  vinculo_id: number
  papel: Papel
  status: 'a' | 'i'
  nome: string
  email: string
  telefone: string | null
  servico_ids: number[]
  jornada: FaixaJornada[]
}

export type StatusAgendamento = 'agendado' | 'realizado' | 'cancelado' | string

export type Agendamento = {
  id: number
  status: StatusAgendamento
  inicio: string
  fim: string
  data: string
  hora_inicio: string
  hora_fim: string
  valor: number
  servico: { id: number; nome: string; duracao_minutos: number } | null
  barbeiro: { vinculo_id: number; nome: string }
  barbearia: { id: number; nome: string; telefone: string | null; endereco: string }
  cliente: { nome: string; telefone: string | null }
  cancelada_em: string | null
  motivo_cancelamento: string | null
  presenca_confirmada_em: string | null
}

export type Bloqueio = {
  id: number
  vinculo_id: number
  barbeiro: string | null
  inicio: string
  fim: string
  data_inicio: string
  hora_inicio: string
  data_fim: string
  hora_fim: string
  motivo: string | null
}
