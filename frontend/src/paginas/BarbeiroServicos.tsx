import { useCallback, useState } from 'react'
import { useNavigate, useParams } from 'react-router'
import { api, ErroApi } from '../api'
import { Alerta, Botao, Cabecalho, Carregando } from '../componentes/ui'
import { DIAS_SEMANA, dinheiro } from '../formatos'
import { useSessao } from '../sessao'
import type { Barbearia, FaixaJornada, MembroEquipe, Servico } from '../tipos'
import { useCarregar } from '../useCarregar'
import { rota } from '../rotas'

type DiaJornada = { dia: number; ativo: boolean; inicio: string; fim: string; pausa: boolean; pausaInicio: string; pausaFim: string }

const ORDEM = [1, 2, 3, 4, 5, 6, 0]

/** Tela 40: serviços que o barbeiro executa e dias e horários de atuação (RF #13). */
export function BarbeiroServicos() {
  const { barbearia } = useSessao()
  const { vinculo } = useParams()
  const base = `/painel/estabelecimentos/${barbearia?.id}`
  const carregar = useCallback(async () => {
    const [equipe, servicos, detalhe] = await Promise.all([
      api<MembroEquipe[]>(`${base}/equipe`),
      api<Servico[]>(`${base}/servicos`),
      api<Barbearia>(base),
    ])
    return { membro: equipe.find((m) => m.vinculo_id === Number(vinculo)) ?? null, servicos, detalhe }
  }, [base, vinculo])
  const { dados, erro } = useCarregar(carregar)

  if (erro) return <div className="p-8"><Alerta>{erro}</Alerta></div>
  if (!dados) return <Carregando />
  if (!dados.membro) return <div className="p-8"><Alerta>Barbeiro não encontrado na equipe.</Alerta></div>
  return <Formulario key={dados.membro.vinculo_id} base={base} membro={dados.membro} servicos={dados.servicos} detalhe={dados.detalhe} />
}

function Formulario({ base, membro, servicos, detalhe }: { base: string; membro: MembroEquipe; servicos: Servico[]; detalhe: Barbearia }) {
  const navegar = useNavigate()
  const ativos = servicos.filter((s) => s.status === 'a')
  const [selecionados, setSelecionados] = useState<Set<number>>(() => new Set(membro.servico_ids))
  const [seguirBarbearia, setSeguirBarbearia] = useState(membro.jornada.length === 0)
  const [dias, setDias] = useState<DiaJornada[]>(() => diasIniciais(membro, detalhe))
  const [mensagem, setMensagem] = useState<{ tipo: 'erro' | 'sucesso'; texto: string } | null>(null)
  const [salvando, setSalvando] = useState(false)

  const alterar = (dia: number, mudanca: Partial<DiaJornada>) => setDias(dias.map((d) => (d.dia === dia ? { ...d, ...mudanca } : d)))

  async function salvar() {
    setSalvando(true)
    setMensagem(null)
    const faixas: FaixaJornada[] = seguirBarbearia
      ? []
      : dias
          .filter((d) => d.ativo)
          .flatMap((d) =>
            d.pausa
              ? [
                  { dia_semana: d.dia, hora_inicio: d.inicio, hora_fim: d.pausaInicio },
                  { dia_semana: d.dia, hora_inicio: d.pausaFim, hora_fim: d.fim },
                ]
              : [{ dia_semana: d.dia, hora_inicio: d.inicio, hora_fim: d.fim }],
          )
    try {
      await api(`${base}/equipe/${membro.vinculo_id}/servicos`, { metodo: 'PUT', corpo: { servico_ids: [...selecionados] } })
      await api(`${base}/equipe/${membro.vinculo_id}/jornada`, { metodo: 'PUT', corpo: { faixas } })
      navegar(rota('/equipe'))
    } catch (e) {
      if (e instanceof ErroApi) {
        const detalhes = Object.entries(e.erros).map(([campo, m]) => {
          const i = campo.match(/^faixas\.(\d+)\./)
          return i ? `${DIAS_SEMANA[faixas[Number(i[1])].dia_semana]}: ${m[0]}` : m[0]
        })
        setMensagem({ tipo: 'erro', texto: detalhes.length ? detalhes.join(' ') : e.message })
      } else {
        setMensagem({ tipo: 'erro', texto: 'Erro inesperado.' })
      }
    } finally {
      setSalvando(false)
    }
  }

  return (
    <>
      <Cabecalho titulo={`${membro.nome} · Serviços e horários`}>
        <Botao variante="secundario" onClick={() => navegar(rota('/equipe'))}>
          Cancelar
        </Botao>
        <Botao onClick={() => void salvar()} carregando={salvando}>
          Salvar
        </Botao>
      </Cabecalho>
      <div className="space-y-4 p-8">
        {mensagem && <Alerta tipo={mensagem.tipo}>{mensagem.texto}</Alerta>}
        <div className="grid gap-5 lg:grid-cols-2">
          <section className="rounded-r2 border border-bd bg-sup p-6">
            <h2 className="mb-1 font-semibold text-tx">Serviços que executa</h2>
            <p className="mb-4 text-sm text-tx2">Escolha pelo menos um.</p>
            {ativos.length === 0 && <Alerta tipo="info">Nenhum serviço ativo. Cadastre serviços primeiro.</Alerta>}
            <div className="space-y-3">
              {ativos.map((s) => (
                <label key={s.id} className="flex cursor-pointer items-center gap-3">
                  <input
                    type="checkbox"
                    className="h-4 w-4 accent-pri"
                    checked={selecionados.has(s.id)}
                    onChange={(e) => {
                      const novo = new Set(selecionados)
                      if (e.target.checked) novo.add(s.id)
                      else novo.delete(s.id)
                      setSelecionados(novo)
                    }}
                  />
                  <span className="flex-1">{s.nome}</span>
                  <span className="text-sm text-tx2">
                    {s.duracao_minutos} min · {dinheiro(s.preco)}
                  </span>
                </label>
              ))}
            </div>
          </section>

          <section className="rounded-r2 border border-bd bg-sup p-6">
            <h2 className="mb-3 font-semibold text-tx">Dias e horários de atuação</h2>
            <label className="mb-4 flex cursor-pointer items-center gap-3 rounded-r1 bg-fundo p-3 text-sm">
              <input type="checkbox" className="h-4 w-4 accent-pri" checked={seguirBarbearia} onChange={(e) => setSeguirBarbearia(e.target.checked)} />
              Seguir o horário de funcionamento da barbearia
            </label>
            {!seguirBarbearia && (
              <div className="divide-y divide-bd">
                {ORDEM.map((n) => {
                  const d = dias.find((x) => x.dia === n)!
                  const nome = DIAS_SEMANA[n].split('-')[0]
                  return (
                    <div key={n} className="py-3">
                      <div className="flex flex-wrap items-center gap-3">
                        <label className="flex w-32 cursor-pointer items-center gap-2 font-semibold">
                          <input type="checkbox" className="h-4 w-4 accent-pri" checked={d.ativo} onChange={(e) => alterar(n, { ativo: e.target.checked })} />
                          {nome}
                        </label>
                        {d.ativo ? (
                          <>
                            <input type="time" aria-label={`${nome}: início`} value={d.inicio} onChange={(e) => alterar(n, { inicio: e.target.value })} className="rounded-r1 border border-bd px-2 py-1 text-sm" />
                            <span className="text-sm text-tx2">às</span>
                            <input type="time" aria-label={`${nome}: fim`} value={d.fim} onChange={(e) => alterar(n, { fim: e.target.value })} className="rounded-r1 border border-bd px-2 py-1 text-sm" />
                            <label className="ml-2 flex items-center gap-1 text-xs text-tx2">
                              <input type="checkbox" className="accent-pri" checked={d.pausa} onChange={(e) => alterar(n, { pausa: e.target.checked })} />
                              pausa
                            </label>
                          </>
                        ) : (
                          <span className="text-sm text-tx3">Folga</span>
                        )}
                      </div>
                      {d.ativo && d.pausa && (
                        <div className="mt-2 ml-32 flex items-center gap-2 pl-3 text-sm text-tx2">
                          Pausa das
                          <input type="time" aria-label={`${nome}: início da pausa`} value={d.pausaInicio} onChange={(e) => alterar(n, { pausaInicio: e.target.value })} className="rounded-r1 border border-bd px-2 py-1" />
                          às
                          <input type="time" aria-label={`${nome}: fim da pausa`} value={d.pausaFim} onChange={(e) => alterar(n, { pausaFim: e.target.value })} className="rounded-r1 border border-bd px-2 py-1" />
                        </div>
                      )}
                    </div>
                  )
                })}
              </div>
            )}
            <p className="mt-3 text-xs text-tx2">
              A agenda do barbeiro é sempre limitada ao horário de funcionamento da barbearia.
            </p>
          </section>
        </div>
      </div>
    </>
  )
}

/** Monta os sete dias a partir da jornada (até duas faixas = expediente com pausa) ou do horário da barbearia. */
function diasIniciais(membro: MembroEquipe, detalhe: Barbearia): DiaJornada[] {
  return [0, 1, 2, 3, 4, 5, 6].map((dia) => {
    const funcionamento = detalhe.horarios.find((h) => h.dia_semana === dia)
    const padrao = { inicio: funcionamento?.hora_abertura ?? '09:00', fim: funcionamento?.hora_fechamento ?? '18:00' }
    if (membro.jornada.length === 0) {
      return { dia, ativo: !!funcionamento?.aberto, ...padrao, pausa: false, pausaInicio: '12:00', pausaFim: '13:00' }
    }
    const faixas = membro.jornada.filter((f) => f.dia_semana === dia).sort((a, b) => a.hora_inicio.localeCompare(b.hora_inicio))
    if (faixas.length === 0) return { dia, ativo: false, ...padrao, pausa: false, pausaInicio: '12:00', pausaFim: '13:00' }
    const ultima = faixas[faixas.length - 1]
    return {
      dia,
      ativo: true,
      inicio: faixas[0].hora_inicio,
      fim: ultima.hora_fim,
      pausa: faixas.length > 1,
      pausaInicio: faixas.length > 1 ? faixas[0].hora_fim : '12:00',
      pausaFim: faixas.length > 1 ? faixas[1].hora_inicio : '13:00',
    }
  })
}
