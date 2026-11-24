import { useCallback, useState, type FormEvent } from 'react'
import { Link, useNavigate, useParams } from 'react-router'
import { api, ErroApi } from '../api'
import { Alerta, AreaTexto, Botao, Cabecalho, Campo, Carregando } from '../componentes/ui'
import { dinheiro } from '../formatos'
import { useSessao } from '../sessao'
import type { Servico } from '../tipos'
import { useCarregar } from '../useCarregar'
import { PAINEL, rota } from '../rotas'

/** Tela 35: Serviços (RF #10). "Excluir" inativa: o serviço sai do app e fica no histórico. */
export function Servicos() {
  const { barbearia } = useSessao()
  const navegar = useNavigate()
  const base = `/painel/estabelecimentos/${barbearia?.id}/servicos`
  const carregar = useCallback(() => api<Servico[]>(base), [base])
  const { dados, erro, recarregar } = useCarregar(carregar)
  const [aviso, setAviso] = useState<string | null>(null)

  async function alterarStatus(servico: Servico, ativar: boolean) {
    if (!ativar && !confirm(`Excluir "${servico.nome}"?\n\nO serviço deixa de aparecer no app e não aceita novos agendamentos. Agendamentos já feitos não são afetados. Você pode reativá-lo depois.`)) {
      return
    }
    try {
      if (ativar) {
        await api(`${base}/${servico.id}`, {
          metodo: 'PUT',
          corpo: { nome: servico.nome, descricao: servico.descricao, preco: servico.preco, duracao_minutos: servico.duracao_minutos, status: 'a' },
        })
      } else {
        await api(`${base}/${servico.id}`, { metodo: 'DELETE' })
      }
      setAviso(ativar ? `"${servico.nome}" reativado.` : `"${servico.nome}" excluído (inativado).`)
      recarregar()
    } catch (e) {
      setAviso(e instanceof ErroApi ? e.message : 'Erro inesperado.')
    }
  }

  return (
    <>
      <Cabecalho titulo="Serviços">
        <Botao variante="destaque" onClick={() => navegar(rota('/servicos/novo'))}>
          Novo serviço
        </Botao>
      </Cabecalho>
      <div className="space-y-4 p-8">
        {aviso && <Alerta tipo="info">{aviso}</Alerta>}
        {erro && <Alerta>{erro}</Alerta>}
        {!dados && !erro && <Carregando />}
        {dados && dados.length === 0 && <Alerta tipo="info">Nenhum serviço cadastrado ainda. Comece pelo botão "Novo serviço".</Alerta>}
        {dados && dados.length > 0 && (
          <div className="overflow-hidden rounded-r2 border border-bd bg-sup">
            <table className="w-full text-sm">
              <thead className="bg-fundo text-left text-tx2">
                <tr>
                  <th className="px-5 py-3 font-medium">Serviço</th>
                  <th className="px-5 py-3 font-medium">Duração</th>
                  <th className="px-5 py-3 font-medium">Preço</th>
                  <th className="px-5 py-3 font-medium">Situação</th>
                  <th className="px-5 py-3" />
                </tr>
              </thead>
              <tbody className="divide-y divide-bd">
                {dados.map((s) => (
                  <tr key={s.id} className={s.status === 'i' ? 'text-tx3' : ''}>
                    <td className="px-5 py-3">
                      <div className="font-semibold">{s.nome}</div>
                      {s.descricao && <div className="text-xs text-tx2">{s.descricao}</div>}
                    </td>
                    <td className="px-5 py-3">{s.duracao_minutos} min</td>
                    <td className="px-5 py-3">{dinheiro(s.preco)}</td>
                    <td className="px-5 py-3">{s.status === 'a' ? 'Ativo' : 'Inativo'}</td>
                    <td className="px-5 py-3 text-right whitespace-nowrap">
                      <Link to={`${PAINEL}/servicos/${s.id}`} className="mr-4 font-semibold text-pri hover:underline">
                        Editar
                      </Link>
                      {s.status === 'a' ? (
                        <button type="button" onClick={() => void alterarStatus(s, false)} className="font-semibold text-err hover:underline">
                          Excluir
                        </button>
                      ) : (
                        <button type="button" onClick={() => void alterarStatus(s, true)} className="font-semibold text-suc hover:underline">
                          Reativar
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
          Depois de cadastrar, atribua os serviços aos barbeiros em <Link to={rota('/equipe')} className="text-pri underline">Equipe</Link>.
        </p>
      </div>
    </>
  )
}

/** Tela 36: cadastro e edição de serviço (RF #10). */
export function FormServico() {
  const { barbearia } = useSessao()
  const { id } = useParams()
  const base = `/painel/estabelecimentos/${barbearia?.id}/servicos`
  const carregar = useCallback(async () => (id ? (await api<Servico[]>(base)).find((s) => s.id === Number(id)) ?? null : null), [base, id])
  const { dados, erro, carregando } = useCarregar(carregar)

  if (erro) return <div className="p-8"><Alerta>{erro}</Alerta></div>
  if (carregando) return <Carregando />
  if (id && !dados) return <div className="p-8"><Alerta>Serviço não encontrado.</Alerta></div>
  return <FormularioServico key={id ?? 'novo'} base={base} existente={dados ?? undefined} />
}

function FormularioServico({ base, existente }: { base: string; existente?: Servico }) {
  const navegar = useNavigate()
  const [dados, setDados] = useState({
    nome: existente?.nome ?? '',
    descricao: existente?.descricao ?? '',
    preco: existente ? existente.preco.toFixed(2) : '',
    duracao_minutos: existente?.duracao_minutos.toString() ?? '30',
  })
  const [erros, setErros] = useState<Record<string, string>>({})
  const [erroGeral, setErroGeral] = useState<string | null>(null)
  const [enviando, setEnviando] = useState(false)

  const campo = (chave: keyof typeof dados) => ({
    value: dados[chave],
    onChange: (e: { target: { value: string } }) => setDados({ ...dados, [chave]: e.target.value }),
    erro: erros[chave],
  })

  async function salvar(evento: FormEvent) {
    evento.preventDefault()
    setEnviando(true)
    setErros({})
    setErroGeral(null)
    const corpo = {
      nome: dados.nome,
      descricao: dados.descricao || null,
      preco: Number(dados.preco.replace(',', '.')),
      duracao_minutos: Number(dados.duracao_minutos),
    }
    try {
      await api(existente ? `${base}/${existente.id}` : base, { metodo: existente ? 'PUT' : 'POST', corpo })
      navegar(rota('/servicos'))
    } catch (e) {
      if (e instanceof ErroApi && Object.keys(e.erros).length > 0) {
        setErros(Object.fromEntries(Object.entries(e.erros).map(([c, m]) => [c, m[0]])))
      } else {
        setErroGeral(e instanceof Error ? e.message : 'Erro inesperado.')
      }
    } finally {
      setEnviando(false)
    }
  }

  return (
    <form onSubmit={salvar}>
      <Cabecalho titulo={existente ? 'Editar serviço' : 'Novo serviço'}>
        <Botao type="button" variante="secundario" onClick={() => navegar(rota('/servicos'))}>
          Cancelar
        </Botao>
        <Botao type="submit" carregando={enviando}>
          Salvar
        </Botao>
      </Cabecalho>
      <div className="max-w-2xl space-y-4 p-8">
        {erroGeral && <Alerta>{erroGeral}</Alerta>}
        <div className="space-y-4 rounded-r2 border border-bd bg-sup p-6">
          <Campo rotulo="Nome do serviço" placeholder="Ex.: Corte de cabelo" required maxLength={255} {...campo('nome')} />
          <AreaTexto rotulo="Descrição (opcional)" {...campo('descricao')} />
          <div className="grid gap-4 sm:grid-cols-2">
            <Campo rotulo="Preço (R$)" inputMode="decimal" placeholder="40,00" required {...campo('preco')} />
            <Campo rotulo="Duração (minutos)" type="number" min={1} max={1440} required {...campo('duracao_minutos')} />
          </div>
        </div>
      </div>
    </form>
  )
}
