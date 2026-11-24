import { useEffect, useState, type ReactNode } from 'react'
import { Link } from 'react-router'
import { Marca } from '../componentes/Layout'
import { Botao } from '../componentes/ui'
import { PAINEL } from '../rotas'

/**
 * Página pública (/) no padrão visual do front-end da turma. Os botões de download
 * são fictícios: o app não está publicado e abrem um aviso "Em breve".
 */
export function Landing() {
  const [emBreve, setEmBreve] = useState<string | null>(null)

  return (
    <div className="min-h-screen bg-fundo text-tx">
      <header className="sticky top-0 z-20 border-b border-bd bg-sup/95 backdrop-blur">
        <div className="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3 sm:px-6">
          <Link to="/" aria-label="AgendAki, página inicial">
            <Marca className="!text-xl sm:!text-[23px]" />
          </Link>
          <Link
            to={PAINEL}
            className="inline-flex items-center justify-center rounded-r2 bg-pri px-3.5 py-2 text-[13px] font-medium whitespace-nowrap text-white transition hover:bg-pri-f sm:px-5 sm:py-2.5 sm:text-[15px]"
          >
            Acessar painel
          </Link>
        </div>
      </header>

      <main>
        {/* Hero */}
        <section className="relative overflow-hidden">
          <div aria-hidden="true" className="absolute inset-x-0 top-0 bottom-16 bg-[linear-gradient(135deg,rgba(31,58,138,0.92),rgba(240,102,43,0.82))]" />
          <div className="relative mx-auto grid max-w-6xl items-center gap-10 px-4 pt-12 pb-6 sm:px-6 md:grid-cols-[1.1fr_1fr] md:pt-16">
            <div className="text-white">
              <p className="mb-3 text-[11px] font-bold tracking-[1.5px] text-white/80 uppercase">Agendamento para barbearias</p>
              <h1 className="text-[34px] leading-[1.15] font-bold sm:text-[44px]">Seu horário na barbearia, marcado em poucos toques</h1>
              <p className="mt-4 max-w-xl text-base leading-relaxed text-white/90 sm:text-lg">
                O cliente escolhe serviço, barbeiro e horário pelo app. A barbearia acompanha a agenda da equipe no painel. E dois clientes nunca ficam com o mesmo horário.
              </p>
              <div className="mt-7 flex flex-col gap-3 sm:flex-row">
                <BotaoLoja rotulo="Baixar no Google Play" linha="Disponível em breve" icone={<IconeCelular />} aoClicar={() => setEmBreve('Google Play')} />
                <BotaoLoja rotulo="Baixar na App Store" linha="Disponível em breve" icone={<IconeDownload />} aoClicar={() => setEmBreve('App Store')} />
              </div>
            </div>
            <div className="flex items-end justify-center gap-4 md:justify-end">
              <Celular src="/landing/app-busca.png" alt="App: busca de barbearias com a nota média" className="hidden w-40 translate-y-6 sm:block lg:w-48" />
              <Celular src="/landing/app-horario.png" alt="App: escolha de data e horário, só com horários livres" className="w-48 lg:w-56" />
            </div>
          </div>
        </section>

        {/* Benefícios */}
        <section className="mx-auto max-w-6xl px-4 pt-20 pb-16 sm:px-6" aria-labelledby="titulo-beneficios">
          <Secao rotulo="Por que usar" titulo="Feito para a rotina da barbearia" id="titulo-beneficios" />
          <div className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Beneficio icone={<IconeRelogio />} titulo="Só horários livres">
              A grade considera o expediente de cada barbeiro, as pausas, os bloqueios e a duração do serviço.
            </Beneficio>
            <Beneficio icone={<IconeEscudo />} titulo="Sem conflito de horário">
              Se dois clientes pedem o mesmo horário ao mesmo tempo, só um consegue. O outro recebe alternativas.
            </Beneficio>
            <Beneficio icone={<IconeEquipe />} titulo="Agenda da equipe">
              Uma coluna por barbeiro, com check-in, cancelamento, bloqueios e agendamento manual.
            </Beneficio>
            <Beneficio icone={<IconeEstrela />} titulo="Avaliações reais">
              Só quem foi atendido avalia. A nota média aparece na busca e na página da barbearia.
            </Beneficio>
          </div>
        </section>

        {/* Como funciona */}
        <section className="border-y border-bd bg-sup" aria-labelledby="titulo-como">
          <div className="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <Secao rotulo="Como funciona" titulo="Três passos até a cadeira" id="titulo-como" />
            <ol className="mt-8 grid gap-6 md:grid-cols-3">
              <Passo n={1} titulo="Encontre a barbearia">
                Busque por nome ou cidade e veja endereço, horário, serviços com preço e avaliações.
              </Passo>
              <Passo n={2} titulo="Escolha e confirme">
                Serviço, barbeiro, data e um horário livre. Revise o resumo e confirme, com pagamento no local.
              </Passo>
              <Passo n={3} titulo="Acompanhe pelo app">
                Veja seus agendamentos, remarque ou cancele quando precisar e avalie depois do atendimento.
              </Passo>
            </ol>
          </div>
        </section>

        {/* Prints */}
        <section className="mx-auto max-w-6xl px-4 py-16 sm:px-6" aria-labelledby="titulo-telas">
          <Secao rotulo="Telas" titulo="O app do cliente e o painel da barbearia" id="titulo-telas" />
          <div className="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
            <FiguraCelular src="/landing/app-barbearia.png" legenda="Página da barbearia" />
            <FiguraCelular src="/landing/app-horario.png" legenda="Horários livres" />
            <FiguraCelular src="/landing/app-comprovante.png" legenda="Comprovante" />
            <FiguraCelular src="/landing/app-meus-agendamentos.png" legenda="Meus agendamentos" />
          </div>
          <figure className="mt-10">
            <div className="overflow-hidden rounded-r2 border border-bd bg-sup shadow-modal">
              <div aria-hidden="true" className="flex items-center gap-1.5 border-b border-bd bg-sut px-4 py-2.5">
                <span className="h-2.5 w-2.5 rounded-full bg-bd-f" />
                <span className="h-2.5 w-2.5 rounded-full bg-bd-f" />
                <span className="h-2.5 w-2.5 rounded-full bg-bd-f" />
              </div>
              <img src="/landing/painel-agenda.png" alt="Painel: agenda do dia com uma coluna por barbeiro" className="block w-full" />
            </div>
            <figcaption className="mt-3 text-center text-sm text-tx2">Painel da barbearia: agenda do dia, uma coluna por barbeiro</figcaption>
          </figure>
          <div className="mt-10 flex flex-col items-center gap-3 text-center">
            <p className="text-tx2">É dono ou trabalha numa barbearia?</p>
            <Link
              to={PAINEL}
              className="inline-flex items-center justify-center rounded-r2 bg-dest px-5 py-3 text-[15px] font-medium text-white transition hover:bg-dest-f"
            >
              Acessar painel
            </Link>
          </div>
        </section>
      </main>

      <footer className="border-t border-bd bg-sup">
        <div className="mx-auto flex max-w-6xl flex-col items-center justify-between gap-2 px-4 py-6 text-sm text-tx2 sm:flex-row sm:px-6">
          <span>AgendAki · Projeto acadêmico IFSP São João da Boa Vista</span>
          <Link to={PAINEL} className="font-medium text-pri hover:underline">
            Painel da barbearia
          </Link>
        </div>
      </footer>

      {emBreve && <AvisoEmBreve loja={emBreve} aoFechar={() => setEmBreve(null)} />}
    </div>
  )
}

function Secao({ rotulo, titulo, id }: { rotulo: string; titulo: string; id: string }) {
  return (
    <div className="flex flex-col gap-2">
      <p className="text-[11px] font-bold tracking-[1.5px] text-pri uppercase">{rotulo}</p>
      <h2 id={id} className="text-[26px] leading-tight font-bold text-tx sm:text-[32px]">
        {titulo}
      </h2>
    </div>
  )
}

function Beneficio({ icone, titulo, children }: { icone: ReactNode; titulo: string; children: ReactNode }) {
  return (
    <div className="flex flex-col gap-3 rounded-r2 border border-bd bg-sup p-5 shadow-cartao">
      <span className="grid h-11 w-11 place-items-center rounded-r2 bg-pri-s text-pri">{icone}</span>
      <h3 className="text-[17px] font-semibold text-tx">{titulo}</h3>
      <p className="text-sm leading-relaxed text-tx2">{children}</p>
    </div>
  )
}

function Passo({ n, titulo, children }: { n: number; titulo: string; children: ReactNode }) {
  return (
    <li className="flex gap-4">
      <span className="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-dest text-lg font-bold text-white">{n}</span>
      <div>
        <h3 className="text-[17px] font-semibold text-tx">{titulo}</h3>
        <p className="mt-1 text-sm leading-relaxed text-tx2">{children}</p>
      </div>
    </li>
  )
}

function Celular({ src, alt, className = '' }: { src: string; alt: string; className?: string }) {
  return (
    <div className={`rounded-[26px] bg-tx p-1.5 shadow-[0_18px_40px_rgba(19,23,34,0.28)] ${className}`}>
      <img src={src} alt={alt} className="block w-full rounded-[20px]" />
    </div>
  )
}

function FiguraCelular({ src, legenda }: { src: string; legenda: string }) {
  return (
    <figure className="flex flex-col items-center gap-2">
      <Celular src={src} alt={`App: ${legenda.toLowerCase()}`} className="w-full max-w-[220px]" />
      <figcaption className="text-center text-[13px] text-tx2">{legenda}</figcaption>
    </figure>
  )
}

/** Botão de loja genérico: ícone simples e texto, sem selo oficial das lojas. */
function BotaoLoja({ rotulo, linha, icone, aoClicar }: { rotulo: string; linha: string; icone: ReactNode; aoClicar: () => void }) {
  return (
    <button
      type="button"
      onClick={aoClicar}
      className="inline-flex items-center gap-3 rounded-r2 border border-white/30 bg-tx px-4 py-2.5 text-left text-white transition hover:bg-black focus-visible:outline-white"
    >
      <span aria-hidden="true" className="text-white">
        {icone}
      </span>
      <span className="flex flex-col leading-tight">
        <span className="text-[11px] text-white/70">{linha}</span>
        <span className="text-[15px] font-semibold">{rotulo}</span>
      </span>
    </button>
  )
}

function AvisoEmBreve({ loja, aoFechar }: { loja: string; aoFechar: () => void }) {
  useEffect(() => {
    const tecla = (e: KeyboardEvent) => e.key === 'Escape' && aoFechar()
    window.addEventListener('keydown', tecla)
    return () => window.removeEventListener('keydown', tecla)
  }, [aoFechar])

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-[#0f161f]/70 p-4" onClick={aoFechar}>
      <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="titulo-em-breve"
        className="w-full max-w-sm rounded-r3 bg-sup p-6 text-center shadow-modal"
        onClick={(e) => e.stopPropagation()}
      >
        <span aria-hidden="true" className="mx-auto mb-3 grid h-12 w-12 place-items-center rounded-full bg-pri-s text-pri">
          <IconeCelular />
        </span>
        <h2 id="titulo-em-breve" className="text-xl font-bold text-tx">
          Em breve
        </h2>
        <p className="mt-2 text-sm leading-relaxed text-tx2">
          O app do AgendAki ainda não está na {loja}. Este é um projeto acadêmico, e a versão Android roda por enquanto em ambiente de desenvolvimento.
        </p>
        <Botao className="mt-5 w-full" onClick={aoFechar} autoFocus>
          Entendi
        </Botao>
      </div>
    </div>
  )
}

// Ícones simples (traço), sem marcas de terceiros.
const svg = { width: 22, height: 22, viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', strokeWidth: 2, strokeLinecap: 'round' as const, strokeLinejoin: 'round' as const }

function IconeCelular() {
  return (
    <svg {...svg}>
      <rect x="6" y="2" width="12" height="20" rx="2.5" />
      <path d="M11 18h2" />
    </svg>
  )
}
function IconeDownload() {
  return (
    <svg {...svg}>
      <path d="M12 3v12" />
      <path d="m7 10 5 5 5-5" />
      <path d="M5 21h14" />
    </svg>
  )
}
function IconeRelogio() {
  return (
    <svg {...svg}>
      <circle cx="12" cy="12" r="9" />
      <path d="M12 7v5l3 2" />
    </svg>
  )
}
function IconeEscudo() {
  return (
    <svg {...svg}>
      <path d="M12 3 5 6v5c0 4.5 3 8.3 7 10 4-1.7 7-5.5 7-10V6z" />
      <path d="m9 12 2 2 4-4" />
    </svg>
  )
}
function IconeEquipe() {
  return (
    <svg {...svg}>
      <circle cx="9" cy="8" r="3" />
      <path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6" />
      <circle cx="17" cy="9" r="2.5" />
      <path d="M16 14.2c2.8.4 5 2.8 5 5.8" />
    </svg>
  )
}
function IconeEstrela() {
  return (
    <svg {...svg}>
      <path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z" />
    </svg>
  )
}
