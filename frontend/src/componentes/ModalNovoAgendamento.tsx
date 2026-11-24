import { useCallback, useState, type FormEvent } from 'react'
import { api, ErroApi } from '../api'
import { dinheiro } from '../formatos'
import type { Barbearia } from '../tipos'
import { useCarregar } from '../useCarregar'
import { Alerta, Botao, Campo, Selecao } from './ui'

type ClienteEncontrado = { nome: string; email: string; telefone: string | null }
type Grade = { data: string; horarios: { inicio: string; hora: string }[] }

/**
 * Tela 33: novo agendamento pelo painel, para cliente que chega sem marcar.
 * Só para quem já tem cadastro (o banco exige login para o cliente).
 */
export function ModalNovoAgendamento({
  barbearia,
  vinculoProprio,
  data,
  aoFechar,
  aoSalvar,
}: {
  barbearia: Barbearia
  /** Barbeiro: só a própria agenda. Null para quem gere a agenda toda. */
  vinculoProprio: number | null
  data: string
  aoFechar: () => void
  aoSalvar: (dataDoAgendamento: string) => void
}) {
  const [busca, setBusca] = useState('')
  const [encontrados, setEncontrados] = useState<ClienteEncontrado[] | null>(null)
  const [cliente, setCliente] = useState<ClienteEncontrado | null>(null)
  const [servicoId, setServicoId] = useState('')
  const [vinculoId, setVinculoId] = useState(vinculoProprio ? String(vinculoProprio) : '')
  const [dia, setDia] = useState(data)
  const [inicio, setInicio] = useState('')
  const [alternativas, setAlternativas] = useState<string[]>([])
  const [erro, setErro] = useState<string | null>(null)
  const [enviando, setEnviando] = useState(false)

  const barbeiros = barbearia.barbeiros.filter(
    (b) => (vinculoProprio === null || b.vinculo_id === vinculoProprio) && (!servicoId || b.servico_ids.includes(Number(servicoId))),
  )
  const servicos = barbearia.servicos.filter((s) =>
    barbearia.barbeiros.some((b) => (vinculoProprio === null || b.vinculo_id === vinculoProprio) && b.servico_ids.includes(s.id)),
  )

  const carregarGrade = useCallback(async (): Promise<Grade | null> => {
    if (!servicoId || !vinculoId || !dia) return null
    return api<Grade>(`/barbearias/${barbearia.id}/disponibilidade`, { query: { servico_id: servicoId, vinculo_id: vinculoId, data: dia } })
  }, [barbearia.id, servicoId, vinculoId, dia])
  const { dados: grade, carregando: carregandoGrade, recarregar } = useCarregar(carregarGrade)

  async function buscar() {
    setErro(null)
    try {
      setEncontrados(await api<ClienteEncontrado[]>(`/painel/estabelecimentos/${barbearia.id}/clientes`, { query: { busca } }))
    } catch (e) {
      setErro(e instanceof ErroApi ? (e.erro('busca') ?? e.message) : 'Erro na busca.')
    }
  }

  async function criar(evento: FormEvent) {
    evento.preventDefault()
    if (!cliente || !inicio) return
    setEnviando(true)
    setErro(null)
    setAlternativas([])
    try {
      await api(`/painel/estabelecimentos/${barbearia.id}/agendamentos`, {
        metodo: 'POST',
        corpo: { email_cliente: cliente.email, servico_id: Number(servicoId), vinculo_id: Number(vinculoId), inicio },
      })
      aoSalvar(dia)
    } catch (e) {
      if (e instanceof ErroApi && e.status === 409) {
        // Horário ocupado enquanto se preenchia (RF #36).
        setErro(e.message)
        setAlternativas((e.corpo.alternativas as string[]) ?? [])
        setInicio('')
        recarregar()
      } else if (e instanceof ErroApi) {
        setErro(Object.values(e.erros)[0]?.[0] ?? e.message)
      } else {
        setErro('Erro inesperado.')
      }
    } finally {
      setEnviando(false)
    }
  }

  return (
    <div className="fixed inset-0 z-30 flex items-center justify-center bg-[#0f161f]/70 p-4" onClick={aoFechar}>
      <form
        role="dialog"
        aria-label="Novo agendamento"
        onSubmit={criar}
        onClick={(e) => e.stopPropagation()}
        className="max-h-[calc(100vh-2rem)] w-full max-w-xl space-y-4 overflow-y-auto rounded-r3 bg-sup p-6 shadow-modal"
      >
        <h2 className="text-lg font-bold text-tx">Novo agendamento</h2>

        {cliente ? (
          <div className="flex items-center justify-between rounded-r1 bg-sut px-4 py-3 text-sm">
            <div>
              <div className="font-semibold">{cliente.nome}</div>
              <div className="text-tx2">
                {cliente.email}
                {cliente.telefone ? ` · ${cliente.telefone}` : ''}
              </div>
            </div>
            <button type="button" className="font-semibold text-pri hover:underline" onClick={() => setCliente(null)}>
              Trocar
            </button>
          </div>
        ) : (
          <div>
            <div className="flex items-end gap-2">
              <Campo
                rotulo="Cliente"
                placeholder="Nome, telefone ou e-mail"
                value={busca}
                onChange={(e) => setBusca(e.target.value)}
                onKeyDown={(e) => {
                  if (e.key === 'Enter') {
                    e.preventDefault()
                    void buscar()
                  }
                }}
                className="flex-1"
              />
              <Botao type="button" variante="secundario" onClick={() => void buscar()} disabled={busca.trim().length < 2}>
                Buscar
              </Botao>
            </div>
            <p className="mt-1 text-xs text-tx2">
              Clientes que já agendaram aqui aparecem pelo nome. Para um cliente novo, digite o e-mail completo da conta dele no AgendAki.
            </p>
            {encontrados && encontrados.length === 0 && (
              <p className="mt-2 text-sm text-ale">Nenhum cliente encontrado. A pessoa precisa criar a conta no app antes.</p>
            )}
            {encontrados && encontrados.length > 0 && (
              <ul className="mt-2 divide-y divide-bd rounded-r1 border border-bd">
                {encontrados.map((c) => (
                  <li key={c.email}>
                    <button type="button" className="w-full px-3 py-2 text-left text-sm hover:bg-fundo" onClick={() => setCliente(c)}>
                      <span className="font-semibold">{c.nome}</span> <span className="text-tx2">· {c.email}</span>
                    </button>
                  </li>
                ))}
              </ul>
            )}
          </div>
        )}

        <div className="grid grid-cols-2 gap-4">
          <Selecao rotulo="Serviço" value={servicoId} onChange={(e) => { setServicoId(e.target.value); setInicio('') }} required>
            <option value="">Escolha…</option>
            {servicos.map((s) => (
              <option key={s.id} value={s.id}>
                {s.nome} · {s.duracao_minutos} min · {dinheiro(s.preco)}
              </option>
            ))}
          </Selecao>
          <Selecao rotulo="Barbeiro" value={vinculoId} onChange={(e) => { setVinculoId(e.target.value); setInicio('') }} required disabled={vinculoProprio !== null}>
            <option value="">Escolha…</option>
            {barbeiros.map((b) => (
              <option key={b.vinculo_id} value={b.vinculo_id}>
                {b.nome}
              </option>
            ))}
          </Selecao>
          <Campo rotulo="Data" type="date" value={dia} onChange={(e) => { setDia(e.target.value); setInicio('') }} required />
          <Selecao rotulo="Horário" value={inicio} onChange={(e) => setInicio(e.target.value)} required disabled={!grade}>
            <option value="">
              {!servicoId || !vinculoId ? 'Escolha serviço e barbeiro' : carregandoGrade ? 'Carregando…' : grade?.horarios.length ? 'Escolha…' : 'Sem horários livres'}
            </option>
            {grade?.horarios.map((h) => (
              <option key={h.inicio} value={h.inicio}>
                {h.hora}
              </option>
            ))}
          </Selecao>
        </div>

        {erro && (
          <Alerta>
            {erro}
            {alternativas.length > 0 && (
              <div className="mt-2 flex flex-wrap gap-2">
                {alternativas.map((a) => (
                  <button key={a} type="button" className="rounded-r1 border border-err/40 bg-sup px-3 py-1 font-semibold" onClick={() => { setInicio(a); setErro(null); setAlternativas([]) }}>
                    {a.slice(11, 16)}
                  </button>
                ))}
              </div>
            )}
          </Alerta>
        )}

        <div className="flex justify-end gap-2 border-t border-sut pt-4">
          <Botao type="button" variante="secundario" onClick={aoFechar}>
            Cancelar
          </Botao>
          <Botao type="submit" carregando={enviando} disabled={!cliente || !inicio}>
            Criar agendamento
          </Botao>
        </div>
      </form>
    </div>
  )
}
