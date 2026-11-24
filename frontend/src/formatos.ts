// Formatação para exibição. As datas da API já vêm em -03:00 e com
// data e hora prontas; aqui só se lida com o dia selecionado (AAAA-MM-DD).

export const DIAS_SEMANA = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado']
const MESES = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro']

export function dinheiro(valor: number): string {
  return valor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

/** Hoje em São Paulo, no formato AAAA-MM-DD, independente do fuso do navegador. */
export function hojeSaoPaulo(): string {
  return new Intl.DateTimeFormat('en-CA', { timeZone: 'America/Sao_Paulo' }).format(new Date())
}

/** Soma dias a uma data AAAA-MM-DD. */
export function somarDias(data: string, dias: number): string {
  const [a, m, d] = data.split('-').map(Number)
  const nova = new Date(Date.UTC(a, m - 1, d + dias))
  return nova.toISOString().slice(0, 10)
}

/** 0 = domingo a 6 = sábado (convenção da API). */
export function diaDaSemana(data: string): number {
  const [a, m, d] = data.split('-').map(Number)
  return new Date(Date.UTC(a, m - 1, d)).getUTCDay()
}

/** Terça-feira, 17 de novembro de 2026 */
export function dataPorExtenso(data: string): string {
  const [a, m, d] = data.split('-').map(Number)
  return `${DIAS_SEMANA[diaDaSemana(data)]}, ${d} de ${MESES[m - 1]} de ${a}`
}

/** 'HH:mm' -> minutos desde 00:00 */
export function minutos(hora: string): number {
  const [h, m] = hora.slice(0, 5).split(':').map(Number)
  return h * 60 + m
}

export function hora(minutosDoDia: number): string {
  const h = Math.floor(minutosDoDia / 60)
  const m = minutosDoDia % 60
  return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`
}

export const NOMES_PAPEL: Record<string, string> = {
  proprietario: 'Proprietário',
  profissional: 'Barbeiro',
  gerente: 'Gerente',
  atendente: 'Atendente',
}

export const NOMES_STATUS: Record<string, string> = {
  agendado: 'Agendado',
  realizado: 'Realizado',
  cancelado: 'Cancelado',
}

/** Etiqueta (.tag da turma): pílula de 11 px. */
export type VarianteTag = 'marca' | 'sucesso' | 'alerta' | 'erro' | 'neutro'
export const CORES_TAG: Record<VarianteTag, string> = {
  marca: 'bg-pri-s text-pri',
  sucesso: 'bg-suc-s text-suc',
  alerta: 'bg-ale-s text-ale',
  erro: 'bg-err-s text-err',
  neutro: 'bg-sut text-tx2',
}

/** Variante de etiqueta de cada status (Tag da turma: marca, sucesso, erro). */
export const VARIANTE_STATUS: Record<string, VarianteTag> = {
  agendado: 'marca',
  realizado: 'sucesso',
  cancelado: 'erro',
}

/** Blocos da agenda (tela 31 da turma): degradê na cor do status e texto branco. */
export const CORES_STATUS: Record<string, string> = {
  agendado: 'bg-[linear-gradient(135deg,#1f3a8a_0%,#1d4ed8_100%)] text-white',
  realizado: 'bg-[linear-gradient(135deg,#0e7a46_0%,#149a5c_100%)] text-white',
  cancelado: 'bg-[linear-gradient(135deg,#c0261b_0%,#e14a3d_100%)] text-white',
}

