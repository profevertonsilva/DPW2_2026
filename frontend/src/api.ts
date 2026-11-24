// Cliente da API do AgendAki: fetch nativo e token Bearer no localStorage (seção 8.2).

export const API_URL: string =
  import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api'

const CHAVE_TOKEN = 'agendaki.token'

export function lerToken(): string | null {
  return localStorage.getItem(CHAVE_TOKEN)
}

export function gravarToken(token: string | null) {
  if (token) localStorage.setItem(CHAVE_TOKEN, token)
  else localStorage.removeItem(CHAVE_TOKEN)
}

/** Erro devolvido pela API, com mensagem em português. */
export class ErroApi extends Error {
  status: number
  erros: Record<string, string[]>
  corpo: Record<string, unknown>

  constructor(status: number, mensagem: string, erros: Record<string, string[]> = {}, corpo: Record<string, unknown> = {}) {
    super(mensagem)
    this.status = status
    this.erros = erros
    this.corpo = corpo
  }

  /** Primeira mensagem de validação de um campo. */
  erro(campo: string): string | undefined {
    return this.erros[campo]?.[0]
  }
}

/** Chamado quando a API responde 401 (token inválido ou expirado). */
let aoPerderSessao: () => void = () => {}
export function definirAoPerderSessao(funcao: () => void) {
  aoPerderSessao = funcao
}

export async function api<T = unknown>(
  caminho: string,
  opcoes: { metodo?: string; corpo?: unknown; query?: Record<string, string | number | undefined | null> } = {},
): Promise<T> {
  const url = new URL(API_URL + caminho)
  for (const [chave, valor] of Object.entries(opcoes.query ?? {})) {
    if (valor !== undefined && valor !== null && valor !== '') url.searchParams.set(chave, String(valor))
  }

  const token = lerToken()
  let resposta: Response
  try {
    resposta = await fetch(url, {
      method: opcoes.metodo ?? 'GET',
      headers: {
        Accept: 'application/json',
        ...(opcoes.corpo !== undefined ? { 'Content-Type': 'application/json' } : {}),
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
      },
      body: opcoes.corpo !== undefined ? JSON.stringify(opcoes.corpo) : undefined,
    })
  } catch {
    throw new ErroApi(0, 'Não foi possível falar com a API. Verifique se ela está rodando.')
  }

  const texto = await resposta.text()
  const json = texto ? JSON.parse(texto) : null

  if (resposta.ok) return json as T

  if (resposta.status === 401 && token) aoPerderSessao()

  const corpo = (json ?? {}) as Record<string, unknown>
  throw new ErroApi(
    resposta.status,
    (corpo.message as string) ?? `Erro inesperado (${resposta.status}).`,
    (corpo.errors as Record<string, string[]>) ?? {},
    corpo,
  )
}
