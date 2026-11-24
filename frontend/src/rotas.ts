// O painel fica sob /painel; a raiz (/) é a landing page pública.
export const PAINEL = '/painel'

/** Caminho dentro do painel: rota('/') é a agenda, rota('/equipe') é a equipe. */
export function rota(caminho = '/'): string {
  return caminho === '/' ? PAINEL : PAINEL + caminho
}
