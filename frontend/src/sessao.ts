import { createContext, useContext } from 'react'
import type { BarbeariaDoUsuario, Usuario } from './tipos'

export type Sessao = {
  carregando: boolean
  usuario: Usuario | null
  /** Barbearia ativa no painel (RF #37: quem tem mais de uma escolhe). */
  barbearia: BarbeariaDoUsuario | null
  entrar: (email: string, senha: string) => Promise<void>
  cadastrar: (dados: Record<string, string>) => Promise<void>
  sair: () => Promise<void>
  trocarBarbearia: (id: number) => void
  recarregar: () => Promise<Usuario | null>
}

export const ContextoSessao = createContext<Sessao | null>(null)

export function useSessao(): Sessao {
  const sessao = useContext(ContextoSessao)
  if (!sessao) throw new Error('useSessao fora do ProvedorSessao')
  return sessao
}
