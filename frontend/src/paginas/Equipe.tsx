import { useCallback, useState, type FormEvent } from 'react'
import { Link } from 'react-router'
import { api, ErroApi } from '../api'
import { Alerta, Botao, Cabecalho, Campo, Carregando, Selecao } from '../componentes/ui'
import { NOMES_PAPEL } from '../formatos'
import { useSessao } from '../sessao'
import { papeisAtribuiveis } from '../permissoes'
import type { Agendamento, MembroEquipe, Papel } from '../tipos'
import { useCarregar } from '../useCarregar'
import { PAINEL } from '../rotas'

/** Telas 37 e 38: Equipe, adicionar colaborador e alterar cargo (RF #11, #12). */
export function Equipe() {
  const { barbearia } = useSessao()
  const base = `/painel/estabelecimentos/${barbearia?.id}/equipe`
  const carregar = useCallback(() => api<MembroEquipe[]>(base), [base])
  const { dados, erro, recarregar } = useCarregar(carregar)

  const [email, setEmail] = useState('')
  const [papel, setPapel] = useState<Papel>('profissional')
  const atribuiveis = papeisAtribuiveis(barbearia?.papel)
  const souProprietario = barbearia?.papel === 'proprietario'
  const [erroEmail, setErroEmail] = useState<string | null>(null)
  const [adicionando, setAdicionando] = useState(false)
  const [aviso, setAviso] = useState<string | null>(null)
  const [pendentes, setPendentes] = useState<{ nome: string; agendamentos: Agendamento[] } | null>(null)

  async function adicionar(evento: FormEvent) {
    evento.preventDefault()
    setAdicionando(true)
    setErroEmail(null)
    setAviso(null)
    try {
      const membro = await api<MembroEquipe>(base, { metodo: 'POST', corpo: { email, papel } })
      setEmail('')
      setAviso(
        membro.papel === 'atendente'
          ? `${membro.nome} entrou na equipe como atendente.`
          : `${membro.nome} entrou na equipe como ${NOMES_PAPEL[membro.papel].toLowerCase()}. Defina os serviços e horários em "Serviços e horários", se a pessoa também atende.`,
      )
      recarregar()
    } catch (e) {
      setErroEmail(e instanceof ErroApi ? (e.erro('email') ?? e.message) : 'Erro inesperado.')
    } finally {
      setAdicionando(false)
    }
  }

  async function alterarPapel(membro: MembroEquipe, novo: Papel) {
    setAviso(null)
    try {
      await api(`${base}/${membro.vinculo_id}/papel`, { metodo: 'PUT', corpo: { papel: novo } })
      setAviso(`${membro.nome} agora é ${NOMES_PAPEL[novo].toLowerCase()}.`)
      recarregar()
    } catch (e) {
      setAviso(e instanceof ErroApi ? (e.erro('papel') ?? e.message) : 'Erro inesperado.')
    }
  }

  /** RF #12: o gerente não mexe em quem é gerente; ninguém mexe no proprietário. */
  const podeMexer = (m: MembroEquipe) => m.papel !== 'proprietario' && (souProprietario || m.papel !== 'gerente')

  async function remover(membro: MembroEquipe) {
    if (!confirm(`Remover ${membro.nome} da equipe?\n\nO vínculo é encerrado e o histórico de atendimentos é mantido.`)) return
    setAviso(null)
    setPendentes(null)
    try {
      await api(`${base}/${membro.vinculo_id}`, { metodo: 'DELETE' })
      setAviso(`${membro.nome} foi removido da equipe.`)
      recarregar()
    } catch (e) {
      // Com agendamentos futuros, a API devolve 422 e a lista (DECISOES 4.13).
      if (e instanceof ErroApi && Array.isArray(e.corpo.agendamentos)) {
        setPendentes({ nome: membro.nome, agendamentos: e.corpo.agendamentos as Agendamento[] })
      } else {
        setAviso(e instanceof ErroApi ? (e.erro('vinculo') ?? e.message) : 'Erro inesperado.')
      }
    }
  }

  return (
    <>
      <Cabecalho titulo="Equipe" />
      <div className="space-y-5 p-8">
        <form onSubmit={adicionar} className="rounded-r2 border border-bd bg-sup p-6">
          <h2 className="font-semibold text-tx">Adicionar colaborador</h2>
          <p className="mb-4 text-sm text-tx2">
            Informe o e-mail de quem já tem conta no AgendAki. Se a pessoa ainda não tem, peça para ela se cadastrar pelo app ou pelo painel.
          </p>
          <div className="flex flex-wrap items-start gap-3">
            <Campo
              rotulo="E-mail"
              type="email"
              placeholder="barbeiro@email.com"
              required
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              erro={erroEmail ?? undefined}
              className="min-w-72 flex-1"
            />
            <Selecao rotulo="Papel" value={papel} onChange={(e) => setPapel(e.target.value as Papel)} className="w-44">
              {atribuiveis.map((p) => (
                <option key={p} value={p}>
                  {NOMES_PAPEL[p]}
                </option>
              ))}
            </Selecao>
            <Botao type="submit" carregando={adicionando} className="mt-6">
              Adicionar à equipe
            </Botao>
          </div>
        </form>

        {aviso && <Alerta tipo="info">{aviso}</Alerta>}

        {pendentes && (
          <Alerta>
            <p className="font-semibold">
              {pendentes.nome} tem {pendentes.agendamentos.length} agendamento(s) futuro(s). Cancele-os antes de removê-lo da equipe:
            </p>
            <ul className="mt-2 list-disc pl-5">
              {pendentes.agendamentos.map((a) => (
                <li key={a.id}>
                  <Link className="underline" to={`${PAINEL}?data=${a.inicio.slice(0, 10)}&agendamento=${a.id}`}>
                    {a.data} às {a.hora_inicio}
                  </Link>{' '}
                  · {a.servico?.nome} · {a.cliente.nome}
                </li>
              ))}
            </ul>
          </Alerta>
        )}

        {erro && <Alerta>{erro}</Alerta>}
        {!dados && !erro && <Carregando />}
        {dados && (
          <div className="overflow-hidden rounded-r2 border border-bd bg-sup">
            <table className="w-full text-sm">
              <thead className="bg-fundo text-left text-tx2">
                <tr>
                  <th className="px-5 py-3 font-medium">Nome</th>
                  <th className="px-5 py-3 font-medium">Contato</th>
                  <th className="px-5 py-3 font-medium">Papel</th>
                  <th className="px-5 py-3 font-medium">Serviços</th>
                  <th className="px-5 py-3 font-medium">Horário</th>
                  <th className="px-5 py-3" />
                </tr>
              </thead>
              <tbody className="divide-y divide-bd">
                {dados.map((m) => (
                  <tr key={m.vinculo_id}>
                    <td className="px-5 py-3 font-semibold">{m.nome}</td>
                    <td className="px-5 py-3 text-tx2">
                      {m.email}
                      {m.telefone && <div className="text-xs">{m.telefone}</div>}
                    </td>
                    <td className="px-5 py-3">
                      {podeMexer(m) ? (
                        <select
                          aria-label={`Papel de ${m.nome}`}
                          value={m.papel}
                          onChange={(e) => void alterarPapel(m, e.target.value as Papel)}
                          className="rounded-r1 border border-bd bg-sup px-2 py-1"
                        >
                          {atribuiveis.map((p) => (
                            <option key={p} value={p}>
                              {NOMES_PAPEL[p]}
                            </option>
                          ))}
                        </select>
                      ) : (
                        NOMES_PAPEL[m.papel]
                      )}
                    </td>
                    <td className="px-5 py-3">{m.servico_ids.length === 0 ? <span className="text-ale">Nenhum</span> : m.servico_ids.length}</td>
                    <td className="px-5 py-3 text-tx2">{m.jornada.length === 0 ? 'Horário da barbearia' : 'Jornada própria'}</td>
                    <td className="px-5 py-3 text-right whitespace-nowrap">
                      <Link to={`${PAINEL}/equipe/${m.vinculo_id}`} className="mr-4 font-semibold text-pri hover:underline">
                        Serviços e horários
                      </Link>
                      {podeMexer(m) && (
                        <button type="button" onClick={() => void remover(m)} className="font-semibold text-err hover:underline">
                          Remover
                        </button>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
        <p className="text-sm text-tx2">
          Só aparece para os clientes quem tem pelo menos um serviço atribuído. Se você também atende, atribua serviços a si mesmo.
        </p>
      </div>
    </>
  )
}
