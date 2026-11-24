import { useCallback, useMemo, useState } from 'react'
import { useSearchParams } from 'react-router'
import { api } from '../api'
import { DetalheAgendamento } from '../componentes/DetalheAgendamento'
import { ModalBloqueio } from '../componentes/ModalBloqueio'
import { ModalNovoAgendamento } from '../componentes/ModalNovoAgendamento'
import { Alerta, Botao, Cabecalho, Carregando } from '../componentes/ui'
import { CORES_STATUS, CORES_TAG, dataPorExtenso, diaDaSemana, hojeSaoPaulo, hora, minutos, somarDias } from '../formatos'
import { veAgendaToda } from '../permissoes'
import { useSessao } from '../sessao'
import type { Agendamento, Barbearia, Bloqueio, MembroEquipe } from '../tipos'
import { useCarregar } from '../useCarregar'

const ALTURA_HORA = 96 // px por hora na grade (30 min = 48 px, perto dos 46 px mínimos dos blocos da turma)

type Coluna = {
  vinculoId: number
  nome: string
  /** Faixas de trabalho no dia, em minutos; null = desconhecidas (não sombrear). */
  janelas: [number, number][] | null
}

/**
 * Tela 31: Agenda do dia (RF #33). Uma coluna por barbeiro; o barbeiro vê só
 * a própria coluna. Faixas fora do expediente aparecem hachuradas.
 */
export function Agenda() {
  const { barbearia, usuario } = useSessao()
  const [parametros, setParametros] = useSearchParams()
  const data = parametros.get('data') ?? hojeSaoPaulo()
  const [mostrarCancelados, setMostrarCancelados] = useState(false)

  // Proprietário, gerente e atendente veem a agenda de todos (RF #03).
  const veTudo = veAgendaToda(barbearia?.papel)
  const barbeariaId = barbearia?.id

  const carregar = useCallback(async () => {
    const base = `/painel/estabelecimentos/${barbeariaId}`
    const [detalhe, equipe, agendamentos, bloqueios] = await Promise.all([
      api<Barbearia>(base),
      veTudo ? api<MembroEquipe[]>(`${base}/equipe`) : Promise.resolve(null),
      api<Agendamento[]>(`${base}/agenda`, { query: { data } }),
      api<Bloqueio[]>(`${base}/bloqueios`, { query: { de: data } }),
    ])
    return { detalhe, equipe, agendamentos, bloqueios }
  }, [barbeariaId, veTudo, data])

  const { dados, erro, recarregar } = useCarregar(carregar)
  const aberto = parametros.get('agendamento')
  const detalhe = dados?.detalhe ?? null
  const equipe = dados?.equipe ?? null
  const agendamentos = dados?.agendamentos ?? null
  const bloqueios = dados?.bloqueios ?? []
  const [bloqueando, setBloqueando] = useState(false)
  const [agendandoManual, setAgendandoManual] = useState(false)

  async function removerBloqueio(b: Bloqueio) {
    if (!confirm(`Remover o bloqueio de ${b.barbeiro} (${b.data_inicio} ${b.hora_inicio} até ${b.data_fim} ${b.hora_fim})?`)) return
    try {
      await api(`/painel/estabelecimentos/${barbeariaId}/bloqueios/${b.id}`, { metodo: 'DELETE' })
      recarregar()
    } catch (e) {
      alert(e instanceof Error ? e.message : 'Erro ao remover o bloqueio.')
    }
  }

  const irPara = (novaData: string) => setParametros({ data: novaData })

  const funcionamento = useMemo(() => {
    const h = detalhe?.horarios.find((x) => x.dia_semana === diaDaSemana(data))
    return h?.aberto && h.hora_abertura && h.hora_fechamento
      ? ([minutos(h.hora_abertura), minutos(h.hora_fechamento)] as [number, number])
      : null
  }, [detalhe, data])

  const colunas = useMemo<Coluna[]>(() => {
    const lista = agendamentos ?? []
    if (!veTudo) {
      const nome = lista[0]?.barbeiro.nome ?? usuario?.nome ?? 'Minha agenda'
      return [{ vinculoId: barbearia?.vinculo_id ?? 0, nome, janelas: null }]
    }
    const comAgendamento = new Set(lista.map((a) => a.barbeiro.vinculo_id))
    const daEquipe = (equipe ?? [])
      .filter((m) => m.servico_ids.length > 0 || comAgendamento.has(m.vinculo_id))
      .map((m) => ({ vinculoId: m.vinculo_id, nome: m.nome, janelas: janelasDoDia(m, data, funcionamento) }))
    // Atendimentos de quem já saiu da equipe continuam visíveis.
    const ids = new Set(daEquipe.map((c) => c.vinculoId))
    const antigos = new Map<number, Coluna>()
    for (const a of lista) {
      if (!ids.has(a.barbeiro.vinculo_id)) antigos.set(a.barbeiro.vinculo_id, { vinculoId: a.barbeiro.vinculo_id, nome: a.barbeiro.nome, janelas: null })
    }
    return [...daEquipe, ...antigos.values()]
  }, [agendamentos, equipe, veTudo, barbearia, usuario, data, funcionamento])

  const visiveis = (agendamentos ?? []).filter((a) => mostrarCancelados || a.status !== 'cancelado')

  // Limites da grade: horário da barbearia, ampliado se houver atendimento fora dele.
  const [inicioGrade, fimGrade] = useMemo(() => {
    let inicio = funcionamento?.[0] ?? 9 * 60
    let fim = funcionamento?.[1] ?? 18 * 60
    for (const a of visiveis) {
      inicio = Math.min(inicio, minutos(a.hora_inicio))
      fim = Math.max(fim, minutos(a.hora_fim))
    }
    return [Math.floor(inicio / 60) * 60, Math.ceil(fim / 60) * 60]
  }, [funcionamento, visiveis])

  const y = (m: number) => ((m - inicioGrade) / 60) * ALTURA_HORA
  const horas = Array.from({ length: (fimGrade - inicioGrade) / 60 }, (_, i) => inicioGrade + i * 60)

  return (
    <>
      <Cabecalho titulo="Agenda" rotulo={detalhe?.nome ?? 'Painel da barbearia'}>
        <Botao variante="destaque" onClick={() => setAgendandoManual(true)} disabled={!detalhe}>
          Novo agendamento
        </Botao>
        <Botao variante="secundario" onClick={() => setBloqueando(true)} disabled={colunas.length === 0}>
          Bloquear horários
        </Botao>
        <Botao variante="secundario" onClick={() => irPara(hojeSaoPaulo())}>
          Hoje
        </Botao>
      </Cabecalho>

      <div className="px-4 py-6 sm:px-8">
        <div className="mb-5 flex flex-wrap items-center justify-between gap-4 rounded-r2 border border-bd bg-sup p-5">
          <div className="flex flex-wrap items-center gap-3.5">
            <button type="button" aria-label="Dia anterior" onClick={() => irPara(somarDias(data, -1))} className="grid h-9 w-9 place-items-center rounded-full border border-bd bg-sup text-[26px] leading-none text-tx hover:bg-pri-s">
              ‹
            </button>
            <h2 className="text-center text-lg font-bold text-tx first-letter:uppercase sm:min-w-80">{dataPorExtenso(data)}</h2>
            <button type="button" aria-label="Próximo dia" onClick={() => irPara(somarDias(data, 1))} className="grid h-9 w-9 place-items-center rounded-full border border-bd bg-sup text-[26px] leading-none text-tx hover:bg-pri-s">
              ›
            </button>
            <input
              type="date"
              aria-label="Escolher data"
              value={data}
              onChange={(e) => e.target.value && irPara(e.target.value)}
              className="min-h-10 rounded-r1 border border-bd bg-sup px-3 py-1.5 text-sm text-tx"
            />
          </div>
          <label className="flex items-center gap-2 text-sm text-tx2">
            <input type="checkbox" checked={mostrarCancelados} onChange={(e) => setMostrarCancelados(e.target.checked)} />
            Mostrar cancelados
          </label>
        </div>

        {erro && <Alerta>{erro}</Alerta>}
        {!erro && agendamentos === null && <Carregando texto="Carregando a agenda…" />}

        {!erro && agendamentos !== null && (
          <>
            <div className="mb-5 grid grid-cols-2 gap-4 lg:grid-cols-4">
              {(
                [
                  ['Total', agendamentos.length],
                  ['Agendados', agendamentos.filter((a) => a.status === 'agendado').length],
                  ['Realizados', agendamentos.filter((a) => a.status === 'realizado').length],
                  ['Cancelados', agendamentos.filter((a) => a.status === 'cancelado').length],
                ] as const
              ).map(([rotulo, valor]) => (
                <div key={rotulo} className="flex flex-col gap-2.5 rounded-r2 border border-bd bg-sup p-5">
                  <span className="text-[13px] text-tx2">{rotulo}</span>
                  <strong className="text-[30px] leading-none text-pri">{valor}</strong>
                </div>
              ))}
            </div>
            {!funcionamento && <div className="mb-4"><Alerta tipo="info">A barbearia não abre neste dia.</Alerta></div>}
            {veTudo && colunas.length === 0 && (
              <Alerta tipo="info">Nenhum barbeiro com serviços atribuídos. Cadastre serviços e atribua-os à equipe.</Alerta>
            )}

            {colunas.length > 0 && (
              <div className="overflow-x-auto rounded-r2 border border-bd bg-sup">
                <div className="flex min-w-fit">
                  {/* Coluna das horas */}
                  <div className="w-[72px] shrink-0 border-r border-bd">
                    <div className="h-[52px] border-b border-bd" />
                    <div className="relative" style={{ height: y(fimGrade) }}>
                      {horas.map((h) => (
                        <div key={h} className="absolute inset-x-0 pt-1.5 text-center text-xs text-tx2" style={{ top: y(h) }}>
                          {hora(h)}
                        </div>
                      ))}
                    </div>
                  </div>

                  {colunas.map((coluna) => (
                    <div key={coluna.vinculoId} className="min-w-[180px] flex-1 border-r border-bd last:border-r-0">
                      <div className="flex h-[52px] items-center justify-center border-b border-bd px-2 text-center font-semibold text-tx">
                        {coluna.nome}
                      </div>
                      <div className="relative bg-[linear-gradient(to_bottom,#ffffff_0%,#f7f8fa_100%)]" style={{ height: y(fimGrade) }}>
                        {horas.map((h) => (
                          <div key={h} className="absolute inset-x-0 border-t border-bd/70" style={{ top: y(h) }} />
                        ))}

                        {coluna.janelas &&
                          lacunas(coluna.janelas, inicioGrade, fimGrade).map(([de, ate]) => (
                            <div
                              key={de}
                              className="hachura-folga absolute inset-x-1.5 flex items-center justify-center rounded-[10px] text-xs font-medium text-tx3"
                              style={{ top: y(de), height: y(ate) - y(de) }}
                            >
                              {rotuloLacuna(coluna.janelas!, de, ate)}
                            </div>
                          ))}

                        {bloqueios
                          .filter((b) => b.vinculo_id === coluna.vinculoId)
                          .map((b) => {
                            const de = Math.max(b.inicio.slice(0, 10) < data ? 0 : minutos(b.hora_inicio), inicioGrade)
                            const ate = Math.min(b.fim.slice(0, 10) > data ? 24 * 60 : minutos(b.hora_fim), fimGrade)
                            if (ate <= de) return null
                            return (
                              <button
                                type="button"
                                key={`b${b.id}`}
                                title="Clique para remover o bloqueio"
                                onClick={() => void removerBloqueio(b)}
                                className="hachura-bloqueio absolute inset-x-1.5 flex items-start justify-center overflow-hidden rounded-[10px] border border-dashed border-tx2/60 px-2 pt-1 text-[11px] font-semibold text-tx2"
                                style={{ top: y(de), height: y(ate) - y(de) }}
                              >
                                Bloqueado{b.motivo ? ` · ${b.motivo}` : ''}
                              </button>
                            )
                          })}

                        {visiveis
                          .filter((a) => a.barbeiro.vinculo_id === coluna.vinculoId)
                          .map((a) => (
                            <button
                              type="button"
                              key={a.id}
                              onClick={() => setParametros({ data, agendamento: String(a.id) })}
                              className={`absolute right-2.5 left-2.5 z-10 overflow-hidden rounded-[10px] px-3 py-1.5 text-left text-xs shadow-bloco transition hover:brightness-110 ${CORES_STATUS[a.status] ?? 'bg-pri text-white'} ${a.status === 'cancelado' ? 'line-through opacity-80' : ''}`}
                              style={{ top: y(minutos(a.hora_inicio)) + 1, height: Math.max(y(minutos(a.hora_fim)) - y(minutos(a.hora_inicio)) - 2, 22) }}
                            >
                              <div className="truncate text-xs font-bold">
                                {a.hora_inicio} · {a.cliente.nome}
                              </div>
                              <div className="truncate text-[11px] opacity-90">{a.servico?.nome}</div>
                            </button>
                          ))}
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}

            <div className="mt-4 flex flex-wrap items-center gap-3 text-[13px] text-tx2">
              <span className={`rounded-full px-2.5 py-1.5 text-[11px] font-bold ${CORES_TAG.marca}`}>Agendado</span>
              <span className={`rounded-full px-2.5 py-1.5 text-[11px] font-bold ${CORES_TAG.sucesso}`}>Realizado</span>
              <span className={`rounded-full px-2.5 py-1.5 text-[11px] font-bold ${CORES_TAG.erro}`}>Cancelado</span>
              <span aria-hidden="true" className="hachura-bloqueio h-[18px] w-[18px] rounded border border-dashed border-tx2/60" />
              <span>Bloqueio de horário</span>
              <span>
                {visiveis.length} {visiveis.length === 1 ? 'atendimento' : 'atendimentos'} no dia
              </span>
            </div>
          </>
        )}
      </div>

      {agendandoManual && detalhe && (
        <ModalNovoAgendamento
          barbearia={detalhe}
          vinculoProprio={veTudo ? null : (barbearia?.vinculo_id ?? null)}
          data={data}
          aoFechar={() => setAgendandoManual(false)}
          aoSalvar={(dia) => {
            setAgendandoManual(false)
            if (dia !== data) irPara(dia)
            else recarregar()
          }}
        />
      )}

      {bloqueando && barbeariaId && (
        <ModalBloqueio
          barbeariaId={barbeariaId}
          barbeiros={colunas.filter((c) => c.janelas !== null || !veTudo)}
          data={data}
          aoFechar={() => setBloqueando(false)}
          aoSalvar={() => {
            setBloqueando(false)
            recarregar()
          }}
        />
      )}

      {aberto && barbeariaId && (
        <DetalheAgendamento barbeariaId={barbeariaId} id={Number(aberto)} aoFechar={() => setParametros({ data })} aoMudar={recarregar} />
      )}
    </>
  )
}

/** Faixas de trabalho do barbeiro no dia: jornada recortada pelo funcionamento (seção 5.5). */
function janelasDoDia(membro: MembroEquipe, data: string, funcionamento: [number, number] | null): [number, number][] {
  if (!funcionamento) return []
  if (membro.jornada.length === 0) return [funcionamento]
  return membro.jornada
    .filter((f) => f.dia_semana === diaDaSemana(data))
    .map((f): [number, number] => [Math.max(minutos(f.hora_inicio), funcionamento[0]), Math.min(minutos(f.hora_fim), funcionamento[1])])
    .filter(([de, ate]) => ate > de)
    .sort((a, b) => a[0] - b[0])
}

/** Folga (sem expediente no dia), Pausa (entre duas faixas) ou Fora do expediente. */
function rotuloLacuna(janelas: [number, number][], de: number, ate: number): string {
  if (janelas.length === 0) return 'Folga'
  return de >= janelas[0][1] && ate <= janelas[janelas.length - 1][0] ? 'Pausa' : 'Fora do expediente'
}

/** Intervalos da grade que ficam fora das janelas de trabalho. */
function lacunas(janelas: [number, number][], inicio: number, fim: number): [number, number][] {
  const resultado: [number, number][] = []
  let cursor = inicio
  for (const [de, ate] of janelas) {
    if (de > cursor) resultado.push([cursor, de])
    cursor = Math.max(cursor, ate)
  }
  if (cursor < fim) resultado.push([cursor, fim])
  return resultado
}
