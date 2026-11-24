import { useEffect, useState } from 'react'
import { ErroApi } from './api'

type Estado<T> = { fonte: unknown; versao: number; dados?: T; erro?: string }

/**
 * Carrega dados com `carregar` (memorizada com useCallback). Quando `carregar`
 * muda (por exemplo, outra data ou outra barbearia), o carregamento recomeça.
 * Com `manterAnterior`, os dados da fonte anterior continuam na tela até os
 * novos chegarem (útil para filtros).
 */
export function useCarregar<T>(carregar: () => Promise<T>, { manterAnterior = false } = {}) {
  const [versao, setVersao] = useState(0)
  const [estado, setEstado] = useState<Estado<T>>({ fonte: null, versao: -1 })

  useEffect(() => {
    let ativo = true
    carregar()
      .then((dados) => ativo && setEstado({ fonte: carregar, versao, dados }))
      .catch((e: unknown) =>
        ativo && setEstado({ fonte: carregar, versao, erro: e instanceof ErroApi ? e.message : 'Erro inesperado ao carregar.' }),
      )
    return () => {
      ativo = false
    }
  }, [carregar, versao])

  const mesmaFonte = estado.fonte === carregar
  const atual = mesmaFonte && estado.versao === versao
  return {
    // Ao recarregar a mesma fonte, mantém os dados anteriores na tela.
    dados: mesmaFonte || manterAnterior ? estado.dados : undefined,
    erro: atual ? estado.erro : undefined,
    carregando: !atual,
    recarregar: () => setVersao((v) => v + 1),
  }
}
