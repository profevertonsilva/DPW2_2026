import { useState } from 'react'
import { NavLink, Outlet, useNavigate } from 'react-router'
import { NOMES_PAPEL } from '../formatos'
import { podeGerir, veAuditoria } from '../permissoes'
import { PAINEL, rota } from '../rotas'
import { useSessao } from '../sessao'
import type { Papel } from '../tipos'

type ItemMenu = { para: string; texto: string; icone: string; pode?: (papel?: Papel) => boolean }

// Ícones geométricos, como no menu do WebAdminLayout da turma.
const MENU: ItemMenu[] = [
  { para: rota('/'), texto: 'Agenda', icone: '◫' },
  { para: rota('/servicos'), texto: 'Serviços', icone: '▣', pode: podeGerir },
  { para: rota('/equipe'), texto: 'Equipe', icone: '◉', pode: podeGerir },
  { para: rota('/barbearia'), texto: 'Barbearia', icone: '⌂', pode: podeGerir },
  { para: rota('/auditoria'), texto: 'Auditoria', icone: '◔', pode: veAuditoria },
]

/** Marca do AgendAki: símbolo ◈ na cor de destaque, como no layout da turma. */
export function Marca({ clara = false, className = '' }: { clara?: boolean; className?: string }) {
  return (
    <span className={`inline-flex items-center gap-2 text-[23px] font-medium ${clara ? 'text-white' : 'text-tx'} ${className}`}>
      <span aria-hidden="true" className="text-[28px] leading-none text-dest">
        ◈
      </span>
      <span>
        Agend<strong className="font-extrabold">Aki</strong>
      </span>
    </span>
  )
}

/** Menu lateral (WebAdminLayout da turma) com o seletor da barbearia ativa (RF #37) e o usuário. */
export function Layout() {
  const { usuario, barbearia, trocarBarbearia, sair } = useSessao()
  const navegar = useNavigate()
  const [aberto, setAberto] = useState(false)

  return (
    <div className="flex min-h-screen bg-fundo text-tx">
      <button
        type="button"
        className="fixed top-4 right-4 z-50 grid h-[42px] w-[42px] place-items-center rounded-r1 border border-bd bg-sup text-xl text-tx md:hidden"
        aria-label={aberto ? 'Fechar menu' : 'Abrir menu'}
        aria-expanded={aberto}
        aria-controls="menu-lateral"
        onClick={() => setAberto((a) => !a)}
      >
        {aberto ? '✕' : '☰'}
      </button>
      {aberto && <button type="button" aria-label="Fechar menu lateral" className="fixed inset-0 z-40 bg-[#0f161f]/70 md:hidden" onClick={() => setAberto(false)} />}

      <aside
        id="menu-lateral"
        className={`fixed inset-y-0 left-0 z-50 flex w-[min(240px,85vw)] flex-col gap-[18px] overflow-y-auto border-r border-lateral-borda bg-lateral px-3 py-[18px] text-[#c9d1dc] transition-transform md:sticky md:top-0 md:h-screen md:w-60 md:min-w-60 md:translate-x-0 md:shadow-none ${aberto ? 'translate-x-0 shadow-[18px_0_30px_rgba(0,0,0,0.2)]' : '-translate-x-full'}`}
      >
        <div className="px-2 pt-0.5">
          <Marca clara />
        </div>

        <div className="flex flex-col gap-1.5 rounded-r1 border border-white/5 bg-white/[0.04] px-3.5 py-4">
          <span className="text-[10px] font-bold tracking-[1.1px] text-[#96a5bb]">ÁREA ADMINISTRATIVA</span>
          <label className="block">
            <span className="sr-only">Barbearia ativa</span>
            <select
              aria-label="Barbearia ativa"
              value={barbearia?.id ?? ''}
              onChange={(e) => {
                setAberto(false)
                if (e.target.value === 'nova') {
                  navegar('/barbearias/nova')
                  return
                }
                trocarBarbearia(Number(e.target.value))
                navegar(PAINEL)
              }}
              className="w-full cursor-pointer bg-transparent text-base leading-snug font-bold text-white outline-none"
            >
              {usuario?.barbearias.map((b) => (
                <option key={b.id} value={b.id} className="text-tx">
                  {b.nome}
                </option>
              ))}
              <option value="nova" className="text-tx">
                + Cadastrar outra barbearia
              </option>
            </select>
          </label>
          <small className="text-xs text-[#9aa7b7]">{barbearia ? NOMES_PAPEL[barbearia.papel] : ''}</small>
        </div>

        <nav className="flex flex-col gap-0.5" aria-label="Menu do painel">
          <span className="mb-2 px-3 text-[10px] tracking-[1.4px] text-[#8c97a7] uppercase">Navegação</span>
          {MENU.filter((item) => !item.pode || item.pode(barbearia?.papel)).map((item) => (
            <NavLink
              key={item.para}
              to={item.para}
              end={item.para === PAINEL}
              onClick={() => setAberto(false)}
              className={({ isActive }) =>
                `flex items-center gap-2 rounded-r1 px-3 py-2.5 text-sm font-medium transition-colors ${
                  isActive ? 'bg-pri text-white' : 'text-lateral-tx hover:bg-white/[0.04] hover:text-white'
                }`
              }
            >
              <span aria-hidden="true" className="inline-flex w-5 justify-center text-lg">
                {item.icone}
              </span>
              {item.texto}
            </NavLink>
          ))}
        </nav>

        <div className="mt-auto border-t border-white/[0.08] pt-4">
          <div className="flex items-center gap-2.5 px-2 pt-1.5">
            <div aria-hidden="true" className="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-pri-s font-bold text-pri">
              {(usuario?.nome ?? '?').slice(0, 1).toUpperCase()}
            </div>
            <div className="min-w-0">
              <strong className="block truncate text-[13px] text-white">{usuario?.nome}</strong>
              <small className="block truncate text-[11px] text-[#8a97ad]">{usuario?.email}</small>
            </div>
          </div>
          <button type="button" onClick={() => void sair()} className="mt-3 ml-2 text-xs text-lateral-tx underline hover:text-white">
            Sair
          </button>
        </div>
      </aside>

      <main className="min-w-0 flex-1">
        <Outlet />
      </main>
    </div>
  )
}
