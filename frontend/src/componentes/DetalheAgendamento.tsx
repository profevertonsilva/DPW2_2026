import { useCallback, useState } from 'react'
import { api, ErroApi } from '../api'
import { dinheiro, hojeSaoPaulo } from '../formatos'
import type { Agendamento } from '../tipos'
import { useCarregar } from '../useCarregar'
import { Alerta, AreaTexto, Botao, Carregando, EtiquetaStatus } from './ui'

/**
 * Tela 32: detalhe do agendamento no painel, com check-in manual
 * ("Marcar como realizado", RF #23) e cancelamento (RF #21). Sem remarcar no painel.
 */
export function DetalheAgendamento({
  barbeariaId,
  id,
  aoFechar,
  aoMudar,
}: {
  barbeariaId: number
  id: number
  aoFechar: () => void
  aoMudar: () => void
}) {
  const base = `/painel/estabelecimentos/${barbeariaId}/agendamentos/${id}`
  const carregar = useCallback(() => api<Agendamento>(base), [base])
  const { dados, erro, recarregar } = useCarregar(carregar)
  const [cancelando, setCancelando] = useState(false)
  const [motivo, setMotivo] = useState('')
  const [enviando, setEnviando] = useState(false)
  const [erroAcao, setErroAcao] = useState<string | null>(null)

  async function acao(caminho: 'realizado' | 'cancelar') {
    setEnviando(true)
    setErroAcao(null)
    try {
      await api(`${base}/${caminho}`, { metodo: 'POST', corpo: caminho === 'cancelar' && motivo.trim() ? { motivo: motivo.trim() } : {} })
      setCancelando(false)
      setMotivo('')
      recarregar()
      aoMudar()
    } catch (e) {
      setErroAcao(e instanceof ErroApi ? (e.erro('status') ?? e.message) : 'Erro inesperado.')
    } finally {
      setEnviando(false)
    }
  }

  const a = dados
  const jaChegouODia = a ? a.inicio.slice(0, 10) <= hojeSaoPaulo() : false

  return (
    <div className="fixed inset-0 z-20 flex justify-end bg-[#0f161f]/40" onClick={aoFechar}>
      <aside
        role="dialog"
        aria-label="Detalhe do agendamento"
        className="m-4 h-fit max-h-[calc(100vh-2rem)] w-full max-w-md overflow-y-auto rounded-r3 border border-bd bg-sup p-6 shadow-modal"
        onClick={(e) => e.stopPropagation()}
      >
        <div className="mb-3 flex items-center justify-between">
          <h2 className="text-lg font-bold text-tx">Detalhe do agendamento</h2>
          <button type="button" aria-label="Fechar" onClick={aoFechar} className="rounded p-1 text-xl leading-none text-tx3 hover:bg-sut">
            ×
          </button>
        </div>

        {erro && <Alerta>{erro}</Alerta>}
        {!a && !erro && <Carregando />}
        {a && (
          <div className="space-y-4">
            <EtiquetaStatus status={a.status} />
            <dl className="divide-y divide-bd rounded-r2 border border-bd px-4 text-sm">
              {[
                ['Cliente', a.cliente.nome],
                ['Telefone', a.cliente.telefone ?? '—'],
                ['Serviço', `${a.servico?.nome ?? ''} · ${a.servico?.duracao_minutos ?? ''} min`],
                ['Barbeiro', a.barbeiro.nome],
                ['Data e horário', `${a.data} das ${a.hora_inicio} às ${a.hora_fim}`],
                ['Valor', dinheiro(a.valor)],
                ...(a.status === 'cancelado' ? [['Motivo do cancelamento', a.motivo_cancelamento ?? '—']] : []),
              ].map(([rotulo, valor]) => (
                <div key={rotulo} className="flex justify-between gap-4 py-2.5">
                  <dt className="text-tx2">{rotulo}</dt>
                  <dd className="text-right font-semibold text-tx">{valor}</dd>
                </div>
              ))}
            </dl>

            {erroAcao && <Alerta>{erroAcao}</Alerta>}

            {a.status === 'agendado' && !cancelando && (
              <div className="space-y-2">
                <Botao className="w-full" carregando={enviando} disabled={!jaChegouODia} onClick={() => void acao('realizado')}>
                  Marcar como realizado
                </Botao>
                {!jaChegouODia && <p className="text-center text-xs text-tx2">Disponível a partir do dia do atendimento.</p>}
                <Botao variante="perigo" className="w-full" onClick={() => setCancelando(true)}>
                  Cancelar agendamento
                </Botao>
              </div>
            )}

            {cancelando && (
              <div className="space-y-3 rounded-r2 border border-err/40 bg-err-s p-4">
                <p className="text-sm font-semibold text-err">Cancelar este agendamento? O horário fica livre para outros clientes.</p>
                <AreaTexto rotulo="Motivo (opcional)" value={motivo} onChange={(e) => setMotivo(e.target.value)} maxLength={500} />
                <div className="flex gap-2">
                  <Botao variante="secundario" className="flex-1" onClick={() => setCancelando(false)}>
                    Voltar
                  </Botao>
                  <Botao variante="perigo" className="flex-1" carregando={enviando} onClick={() => void acao('cancelar')}>
                    Confirmar cancelamento
                  </Botao>
                </div>
              </div>
            )}
          </div>
        )}
      </aside>
    </div>
  )
}
