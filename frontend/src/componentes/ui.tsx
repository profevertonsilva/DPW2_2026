import type { ButtonHTMLAttributes, InputHTMLAttributes, ReactNode, SelectHTMLAttributes, TextareaHTMLAttributes } from 'react'
import { CORES_TAG, NOMES_STATUS, VARIANTE_STATUS, type VarianteTag } from '../formatos'

// Componentes base no padrão da turma (components.css: .btn, .campo, .tag, .secao, .barra).

type Variante = 'primario' | 'destaque' | 'secundario' | 'perigo' | 'fantasma' | 'perigo-cheio'

const VARIANTES: Record<Variante, string> = {
  primario: 'bg-pri text-white hover:bg-pri-f',
  destaque: 'bg-dest text-white hover:bg-dest-f',
  secundario: 'border border-bd bg-sup text-pri hover:bg-pri-s',
  perigo: 'border border-bd bg-sup text-err hover:bg-err-s',
  'perigo-cheio': 'bg-err text-white hover:brightness-95',
  fantasma: 'text-pri hover:bg-pri-s',
}

export function Botao({
  variante = 'primario',
  carregando = false,
  mini = false,
  className = '',
  children,
  disabled,
  ...props
}: ButtonHTMLAttributes<HTMLButtonElement> & { variante?: Variante; carregando?: boolean; mini?: boolean }) {
  const tamanho = mini ? 'px-3.5 py-2 text-[13px]' : 'px-5 py-3 text-[15px]'
  return (
    <button
      {...props}
      disabled={disabled || carregando}
      className={`inline-flex items-center justify-center gap-2 rounded-r2 font-medium transition disabled:cursor-not-allowed disabled:opacity-50 ${tamanho} ${VARIANTES[variante]} ${className}`}
    >
      {carregando && <span className="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" />}
      {children}
    </button>
  )
}

/** Caixa de campo (.campo-box): 46 px de altura mínima, borda de 1 px e foco de 2 px na cor primária. */
const ESTILO_CAMPO =
  'w-full min-h-[46px] rounded-r1 border border-bd bg-sup px-3.5 py-3 text-[15px] text-tx placeholder:text-tx3 outline-none focus:border-pri focus:ring-1 focus:ring-pri disabled:bg-sut disabled:text-tx3'

const ROTULO = 'mb-1.5 block text-[13px] font-medium text-tx2'

export function Campo({
  rotulo,
  erro,
  ajuda,
  className = '',
  ...props
}: InputHTMLAttributes<HTMLInputElement> & { rotulo: string; erro?: string; ajuda?: string }) {
  return (
    <label className={`block ${className}`}>
      <span className={ROTULO}>{rotulo}</span>
      <input {...props} aria-invalid={erro ? true : undefined} className={`${ESTILO_CAMPO} ${erro ? 'border-err' : ''}`} />
      {ajuda && !erro && <span className="mt-1 block text-xs text-tx2">{ajuda}</span>}
      {erro && <span className="mt-1 block text-xs text-err">{erro}</span>}
    </label>
  )
}

export function AreaTexto({
  rotulo,
  erro,
  className = '',
  ...props
}: TextareaHTMLAttributes<HTMLTextAreaElement> & { rotulo: string; erro?: string }) {
  return (
    <label className={`block ${className}`}>
      <span className={ROTULO}>{rotulo}</span>
      <textarea {...props} aria-invalid={erro ? true : undefined} className={`${ESTILO_CAMPO} min-h-24 resize-y ${erro ? 'border-err' : ''}`} />
      {erro && <span className="mt-1 block text-xs text-err">{erro}</span>}
    </label>
  )
}

export function Selecao({
  rotulo,
  erro,
  className = '',
  children,
  ...props
}: SelectHTMLAttributes<HTMLSelectElement> & { rotulo: string; erro?: string }) {
  return (
    <label className={`block ${className}`}>
      <span className={ROTULO}>{rotulo}</span>
      <select {...props} aria-invalid={erro ? true : undefined} className={`${ESTILO_CAMPO} ${erro ? 'border-err' : ''}`}>
        {children}
      </select>
      {erro && <span className="mt-1 block text-xs text-err">{erro}</span>}
    </label>
  )
}

/** Mensagens no padrão da turma (.checkin-erro, .checkin-sucesso): fundo suave e borda na cor do estado. */
export function Alerta({ children, tipo = 'erro' }: { children: ReactNode; tipo?: 'erro' | 'sucesso' | 'info' | 'alerta' }) {
  const cores = {
    erro: 'border-err/40 bg-err-s text-err',
    sucesso: 'border-suc/40 bg-suc-s text-suc',
    info: 'border-inf/30 bg-inf-s text-inf',
    alerta: 'border-ale/40 bg-ale-s text-ale',
  }[tipo]
  return (
    <div role={tipo === 'erro' ? 'alert' : 'status'} className={`rounded-r1 border px-4 py-3 text-sm ${cores}`}>
      {children}
    </div>
  )
}

/** Etiqueta (.tag): pílula de 11 px. */

export function Etiqueta({ children, variante = 'neutro' }: { children: ReactNode; variante?: VarianteTag }) {
  return <span className={`inline-block rounded-full px-2.5 py-1 text-[11px] leading-normal font-semibold tracking-[0.2px] ${CORES_TAG[variante]}`}>{children}</span>
}

export function EtiquetaStatus({ status }: { status: string }) {
  return <Etiqueta variante={VARIANTE_STATUS[status] ?? 'neutro'}>{NOMES_STATUS[status] ?? status}</Etiqueta>
}

/** Cartão (.secao): fundo branco, borda e raio de 12 px. */
export function Cartao({ children, className = '' }: { children: ReactNode; className?: string }) {
  return <section className={`rounded-r2 border border-bd bg-sup p-5 ${className}`}>{children}</section>
}

export function Carregando({ texto = 'Carregando…' }: { texto?: string }) {
  return (
    <div role="status" className="flex items-center gap-3 p-8 text-sm text-tx2">
      <span className="h-5 w-5 animate-spin rounded-full border-2 border-pri border-t-transparent" />
      {texto}
    </div>
  )
}

/** Estado vazio (.agenda-dia-vazio / .checkin-vazio): caixa tracejada com texto centralizado. */
export function Vazio({ titulo, children }: { titulo?: string; children?: ReactNode }) {
  return (
    <div className="rounded-r2 border border-dashed border-bd bg-sup px-4 py-8 text-center text-sm text-tx2">
      {titulo && <h3 className="mb-2 text-base font-semibold text-tx">{titulo}</h3>}
      {children}
    </div>
  )
}

/**
 * Cabeçalho da página no padrão da agenda da turma: rótulo pequeno em caixa alta
 * na cor primária e título grande, com as ações à direita.
 */
export function Cabecalho({ titulo, rotulo = 'Painel da barbearia', children }: { titulo: string; rotulo?: string; children?: ReactNode }) {
  return (
    <header className="flex flex-wrap items-end justify-between gap-4 px-4 pt-20 pb-2 sm:px-8 md:pt-8">
      <div className="flex flex-col gap-2">
        <p className="text-[11px] font-bold tracking-[1.5px] text-pri uppercase">{rotulo}</p>
        <h1 className="text-[28px] leading-tight font-bold text-tx sm:text-[32px]">{titulo}</h1>
      </div>
      {children && <div className="flex flex-wrap items-center gap-3">{children}</div>}
    </header>
  )
}
