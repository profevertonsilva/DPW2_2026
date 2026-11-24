import { useState, type FormEvent } from 'react'
import { api, ErroApi } from '../api'
import type { Agendamento } from '../tipos'
import { Alerta, AreaTexto, Botao, Campo, Selecao } from './ui'

type Barbeiro = { vinculoId: number; nome: string }

/**
 * Tela 41: bloquear horários (RF #14). Antes de confirmar, mostra os
 * agendamentos do intervalo, que não são cancelados automaticamente:
 * cada um pode ser cancelado aqui ou mantido.
 */
export function ModalBloqueio({
  barbeariaId,
  barbeiros,
  data,
  aoFechar,
  aoSalvar,
}: {
  barbeariaId: number
  barbeiros: Barbeiro[]
  data: string
  aoFechar: () => void
  aoSalvar: () => void
}) {
  const base = `/painel/estabelecimentos/${barbeariaId}`
  const [form, setForm] = useState({
    vinculo_id: String(barbeiros[0]?.vinculoId ?? ''),
    data_inicio: data,
    data_fim: data,
    hora_inicio: '12:00',
    hora_fim: '13:00',
    motivo: '',
  })
  const [conflitos, setConflitos] = useState<Agendamento[] | null>(null)
  const [erros, setErros] = useState<Record<string, string>>({})
  const [erroGeral, setErroGeral] = useState<string | null>(null)
  const [enviando, setEnviando] = useState(false)

  const campo = (chave: keyof typeof form) => ({
    value: form[chave],
    onChange: (e: { target: { value: string } }) => {
      setForm({ ...form, [chave]: e.target.value })
      setConflitos(null) // intervalo mudou: verificar de novo
    },
    erro: erros[chave],
  })

  const intervalo = () => ({
    vinculo_id: form.vinculo_id,
    inicio: `${form.data_inicio}T${form.hora_inicio}:00-03:00`,
    fim: `${form.data_fim}T${form.hora_fim}:00-03:00`,
  })

  function tratarErro(e: unknown) {
    if (e instanceof ErroApi && Object.keys(e.erros).length > 0) {
      setErros(Object.fromEntries(Object.entries(e.erros).map(([c, m]) => [c === 'fim' ? 'hora_fim' : c, m[0]])))
    } else {
      setErroGeral(e instanceof Error ? e.message : 'Erro inesperado.')
    }
  }

  async function confirmar(evento: FormEvent) {
    evento.preventDefault()
    setEnviando(true)
    setErros({})
    setErroGeral(null)
    try {
      // 1º passo: verificar agendamentos no intervalo.
      if (conflitos === null) {
        const lista = await api<Agendamento[]>(`${base}/bloqueios/conflitos`, { query: intervalo() })
        if (lista.length > 0) {
          setConflitos(lista)
          return
        }
      }
      await api(`${base}/bloqueios`, { metodo: 'POST', corpo: { ...intervalo(), motivo: form.motivo || null } })
      aoSalvar()
    } catch (e) {
      tratarErro(e)
    } finally {
      setEnviando(false)
    }
  }

  async function cancelarAgendamento(a: Agendamento) {
    try {
      await api(`${base}/agendamentos/${a.id}/cancelar`, { metodo: 'POST', corpo: { motivo: form.motivo ? `Bloqueio de horário: ${form.motivo}` : 'Bloqueio de horário' } })
      setConflitos((lista) => (lista ?? []).filter((x) => x.id !== a.id))
    } catch (e) {
      setErroGeral(e instanceof ErroApi ? e.message : 'Erro ao cancelar.')
    }
  }

  return (
    <div className="fixed inset-0 z-30 flex items-center justify-center bg-[#0f161f]/70 p-4" onClick={aoFechar}>
      <form
        role="dialog"
        aria-label="Bloquear horários"
        onSubmit={confirmar}
        onClick={(e) => e.stopPropagation()}
        className="w-full max-w-xl space-y-4 rounded-r3 bg-sup p-6 shadow-modal"
      >
        <h2 className="text-lg font-bold text-tx">Bloquear horários</h2>

        {barbeiros.length > 1 ? (
          <Selecao rotulo="Barbeiro" {...campo('vinculo_id')}>
            {barbeiros.map((b) => (
              <option key={b.vinculoId} value={b.vinculoId}>
                {b.nome}
              </option>
            ))}
          </Selecao>
        ) : (
          <p className="text-sm text-tx2">Agenda de {barbeiros[0]?.nome}</p>
        )}

        <div className="grid grid-cols-2 gap-4">
          <Campo rotulo="Data de início" type="date" required {...campo('data_inicio')} />
          <Campo rotulo="Data de término" type="date" required {...campo('data_fim')} />
          <Campo rotulo="Horário de início" type="time" required {...campo('hora_inicio')} />
          <Campo rotulo="Horário de término" type="time" required {...campo('hora_fim')} />
        </div>
        <AreaTexto rotulo="Motivo (opcional)" placeholder="Ex.: consulta médica" maxLength={500} {...campo('motivo')} />

        {erroGeral && <Alerta>{erroGeral}</Alerta>}

        {conflitos && conflitos.length > 0 && (
          <div className="space-y-2">
            <div className="rounded-r1 border border-ale/40 bg-ale-s px-4 py-3 text-sm text-ale">
              {conflitos.length === 1
                ? 'Existe 1 agendamento neste intervalo. Ele não será cancelado automaticamente: decida se quer mantê-lo ou cancelá-lo.'
                : `Existem ${conflitos.length} agendamentos neste intervalo. Eles não serão cancelados automaticamente: decida se quer mantê-los ou cancelá-los.`}
            </div>
            {conflitos.map((a) => (
              <div key={a.id} className="flex items-center justify-between rounded-r1 bg-sut px-3 py-2 text-sm">
                <span>
                  {a.data.slice(0, 5)} {a.hora_inicio} · {a.cliente.nome} · {a.servico?.nome}
                </span>
                <button type="button" onClick={() => void cancelarAgendamento(a)} className="font-semibold text-err hover:underline">
                  Cancelar
                </button>
              </div>
            ))}
          </div>
        )}

        <div className="flex justify-end gap-2 border-t border-sut pt-4">
          <Botao type="button" variante="secundario" onClick={aoFechar}>
            Fechar
          </Botao>
          <Botao type="submit" carregando={enviando}>
            {conflitos && conflitos.length > 0 ? 'Bloquear mesmo assim' : 'Confirmar bloqueio'}
          </Botao>
        </div>
      </form>
    </div>
  )
}
