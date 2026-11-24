import { useCallback, useEffect, useMemo, useState, type ReactNode } from 'react'
import { api, definirAoPerderSessao, gravarToken, lerToken } from './api'
import { ContextoSessao } from './sessao'
import type { Usuario } from './tipos'

const CHAVE_BARBEARIA = 'agendaki.barbearia'

type RespostaToken = { token: string; usuario: Usuario }

/** Sessão do painel: token no localStorage (seção 8.2) e barbearia ativa. */
export function ProvedorSessao({ children }: { children: ReactNode }) {
  const [usuario, setUsuario] = useState<Usuario | null>(null)
  const [carregando, setCarregando] = useState(() => lerToken() !== null)
  const [barbeariaId, setBarbeariaId] = useState<number | null>(() => {
    const salvo = localStorage.getItem(CHAVE_BARBEARIA)
    return salvo ? Number(salvo) : null
  })

  const limpar = useCallback(() => {
    gravarToken(null)
    setUsuario(null)
  }, [])

  useEffect(() => definirAoPerderSessao(limpar), [limpar])

  // Ao abrir: com token guardado, carrega o usuário.
  useEffect(() => {
    if (!lerToken()) return
    let ativo = true
    api<Usuario>('/me')
      .then((me) => ativo && setUsuario(me))
      .catch(() => ativo && limpar())
      .finally(() => ativo && setCarregando(false))
    return () => {
      ativo = false
    }
  }, [limpar])

  const recarregar = useCallback(async () => {
    try {
      const me = await api<Usuario>('/me')
      setUsuario(me)
      return me
    } catch {
      return null
    }
  }, [])

  const iniciar = useCallback((resposta: RespostaToken) => {
    gravarToken(resposta.token)
    setUsuario(resposta.usuario)
    setCarregando(false)
  }, [])

  const entrar = useCallback(
    async (email: string, senha: string) => {
      iniciar(await api<RespostaToken>('/auth/login', { metodo: 'POST', corpo: { email, senha, dispositivo: 'painel' } }))
    },
    [iniciar],
  )

  const cadastrar = useCallback(
    async (dados: Record<string, string>) => {
      iniciar(await api<RespostaToken>('/auth/cadastro', { metodo: 'POST', corpo: { ...dados, dispositivo: 'painel' } }))
    },
    [iniciar],
  )

  const sair = useCallback(async () => {
    try {
      await api('/auth/logout', { metodo: 'POST' })
    } catch {
      // A sessão local é encerrada mesmo sem resposta da API.
    }
    limpar()
  }, [limpar])

  const trocarBarbearia = useCallback((id: number) => {
    localStorage.setItem(CHAVE_BARBEARIA, String(id))
    setBarbeariaId(id)
  }, [])

  const barbearia = useMemo(() => {
    const lista = usuario?.barbearias ?? []
    return lista.find((b) => b.id === barbeariaId) ?? lista[0] ?? null
  }, [usuario, barbeariaId])

  const valor = useMemo(
    () => ({ carregando, usuario, barbearia, entrar, cadastrar, sair, trocarBarbearia, recarregar }),
    [carregando, usuario, barbearia, entrar, cadastrar, sair, trocarBarbearia, recarregar],
  )

  return <ContextoSessao.Provider value={valor}>{children}</ContextoSessao.Provider>
}
