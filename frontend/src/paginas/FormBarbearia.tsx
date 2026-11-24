import { useCallback, useState, type FormEvent } from 'react'
import { useNavigate } from 'react-router'
import { api, ErroApi } from '../api'
import { HorarioSemana, type Dia } from '../componentes/HorarioSemana'
import { Marca } from '../componentes/Layout'
import { Alerta, AreaTexto, Botao, Cabecalho, Campo, Carregando, Selecao } from '../componentes/ui'
import { useSessao } from '../sessao'
import type { Barbearia } from '../tipos'
import { useCarregar } from '../useCarregar'
import { PAINEL, rota } from '../rotas'

const UFS = 'AC AL AP AM BA CE DF ES GO MA MT MS MG PA PB PR PE PI RJ RN RS RO RR SC SP SE TO'.split(' ')

type Dados = {
  nome: string
  telefone: string
  descricao: string
  cep: string
  estado: string
  cidade: string
  bairro: string
  logradouro: string
  numero: string
  complemento: string
}

const VAZIO: Dados = { nome: '', telefone: '', descricao: '', cep: '', estado: 'SP', cidade: '', bairro: '', logradouro: '', numero: '', complemento: '' }

const SEMANA_PADRAO: Dia[] = [0, 1, 2, 3, 4, 5, 6].map((d) => ({
  dia_semana: d,
  ativo: d !== 0,
  inicio: '09:00',
  fim: d === 6 ? '17:00' : '19:00',
}))

/** Tela 30, cadastro: quem ainda não tem barbearia cai aqui. */
export function NovaBarbearia() {
  const { usuario, sair } = useSessao()
  const navegar = useNavigate()
  const temOutras = (usuario?.barbearias.length ?? 0) > 0

  return (
    <div className="min-h-screen bg-fundo">
      <div className="flex items-center justify-between border-b border-bd bg-sup px-4 py-4 sm:px-8">
        <Marca />
        <div className="flex items-center gap-3 text-sm">
          {temOutras ? (
            <Botao variante="secundario" onClick={() => navegar(PAINEL)}>
              Voltar ao painel
            </Botao>
          ) : (
            <button type="button" className="text-tx2 underline" onClick={() => void sair()}>
              Sair
            </button>
          )}
        </div>
      </div>
      <div className="mx-auto max-w-5xl">
        {!temOutras && (
          <div className="px-8 pt-6">
            <Alerta tipo="info">
              Olá, {usuario?.nome}! Você ainda não faz parte de nenhuma barbearia. Cadastre a sua abaixo, ou peça ao dono de uma barbearia
              para adicionar você à equipe pelo e-mail <strong>{usuario?.email}</strong>.
            </Alerta>
          </div>
        )}
        <FormBarbearia />
      </div>
    </div>
  )
}

/** Tela 30, edição: "Barbearia" no menu (dados e horário). */
export function EditarBarbearia() {
  const { barbearia } = useSessao()
  const id = barbearia?.id
  const carregar = useCallback(() => api<Barbearia>(`/painel/estabelecimentos/${id}`), [id])
  const { dados, erro } = useCarregar(carregar)

  if (erro) return <div className="p-8"><Alerta>{erro}</Alerta></div>
  if (!dados) return <Carregando />
  return <FormBarbearia key={dados.id} existente={dados} />
}

function FormBarbearia({ existente }: { existente?: Barbearia }) {
  const { recarregar, trocarBarbearia } = useSessao()
  const navegar = useNavigate()

  const [dados, setDados] = useState<Dados>(() =>
    existente
      ? {
          nome: existente.nome,
          telefone: existente.telefone ?? '',
          descricao: existente.descricao ?? '',
          cep: existente.endereco.cep ?? '',
          estado: existente.endereco.estado ?? 'SP',
          cidade: existente.endereco.cidade ?? '',
          bairro: existente.endereco.bairro ?? '',
          logradouro: existente.endereco.logradouro ?? '',
          numero: existente.endereco.numero?.toString() ?? '',
          complemento: existente.endereco.complemento ?? '',
        }
      : VAZIO,
  )
  const [dias, setDias] = useState<Dia[]>(() =>
    existente
      ? existente.horarios.map((h) => ({
          dia_semana: h.dia_semana,
          ativo: h.aberto,
          inicio: h.hora_abertura ?? '09:00',
          fim: h.hora_fechamento ?? '18:00',
        }))
      : SEMANA_PADRAO,
  )
  const [erros, setErros] = useState<Record<string, string>>({})
  const [errosDias, setErrosDias] = useState<Record<number, string>>({})
  const [mensagem, setMensagem] = useState<{ tipo: 'erro' | 'sucesso'; texto: string } | null>(null)
  const [enviando, setEnviando] = useState(false)

  const campo = (chave: keyof Dados) => ({
    value: dados[chave],
    onChange: (e: { target: { value: string } }) => setDados({ ...dados, [chave]: e.target.value }),
    erro: erros[chave],
  })

  async function salvar(evento: FormEvent) {
    evento.preventDefault()
    setEnviando(true)
    setErros({})
    setErrosDias({})
    setMensagem(null)

    // Sete linhas, na ordem do dia da semana (o índice é o dia).
    const horarios = [...dias]
      .sort((a, b) => a.dia_semana - b.dia_semana)
      .map((d) => ({
        dia_semana: d.dia_semana,
        aberto: d.ativo,
        hora_abertura: d.ativo ? d.inicio : null,
        hora_fechamento: d.ativo ? d.fim : null,
      }))
    const corpo = { ...dados, numero: dados.numero === '' ? null : Number(dados.numero), horarios }

    try {
      if (existente) {
        await api(`/painel/estabelecimentos/${existente.id}`, { metodo: 'PUT', corpo })
        await recarregar()
        setMensagem({ tipo: 'sucesso', texto: 'Dados da barbearia salvos.' })
      } else {
        const nova = await api<Barbearia>('/painel/estabelecimentos', { metodo: 'POST', corpo })
        await recarregar()
        trocarBarbearia(nova.id)
        navegar(rota('/servicos'))
      }
    } catch (e) {
      if (e instanceof ErroApi && Object.keys(e.erros).length > 0) {
        const gerais: Record<string, string> = {}
        const porDia: Record<number, string> = {}
        for (const [chave, msgs] of Object.entries(e.erros)) {
          const m = chave.match(/^horarios\.(\d+)\./)
          if (m) porDia[Number(m[1])] = msgs[0]
          else gerais[chave] = msgs[0]
        }
        setErros(gerais)
        setErrosDias(porDia)
        setMensagem({ tipo: 'erro', texto: 'Confira os campos destacados.' })
      } else {
        setMensagem({ tipo: 'erro', texto: e instanceof Error ? e.message : 'Erro inesperado.' })
      }
    } finally {
      setEnviando(false)
    }
  }

  return (
    <form onSubmit={salvar}>
      <Cabecalho titulo={existente ? 'Dados da barbearia' : 'Cadastrar barbearia'}>
        <Botao type="submit" carregando={enviando}>
          {existente ? 'Salvar alterações' : 'Salvar barbearia'}
        </Botao>
      </Cabecalho>

      <div className="space-y-5 p-8">
        {mensagem && <Alerta tipo={mensagem.tipo}>{mensagem.texto}</Alerta>}

        <section className="rounded-r2 border border-bd bg-sup p-6">
          <h2 className="mb-4 font-semibold text-tx">Dados gerais</h2>
          <div className="grid gap-4 md:grid-cols-2">
            <Campo rotulo="Nome da barbearia" required maxLength={100} {...campo('nome')} />
            <Campo rotulo="Telefone de contato" type="tel" placeholder="(19) 3600-0000" required {...campo('telefone')} />
            <AreaTexto rotulo="Descrição (opcional)" className="md:col-span-2" {...campo('descricao')} />
          </div>
        </section>

        <section className="rounded-r2 border border-bd bg-sup p-6">
          <h2 className="mb-4 font-semibold text-tx">Endereço</h2>
          <div className="grid gap-4 md:grid-cols-3">
            <Campo rotulo="CEP" placeholder="00000-000" required {...campo('cep')} />
            <Selecao rotulo="Estado" required {...campo('estado')}>
              {UFS.map((uf) => (
                <option key={uf}>{uf}</option>
              ))}
            </Selecao>
            <Campo rotulo="Cidade" required maxLength={50} {...campo('cidade')} />
            <Campo rotulo="Bairro" required maxLength={50} {...campo('bairro')} />
            <Campo rotulo="Logradouro" required maxLength={100} {...campo('logradouro')} />
            <Campo rotulo="Número" type="number" min={0} required {...campo('numero')} />
            <Campo rotulo="Complemento (opcional)" className="md:col-span-3" maxLength={255} {...campo('complemento')} />
          </div>
        </section>

        <section className="rounded-r2 border border-bd bg-sup p-6">
          <h2 className="mb-2 font-semibold text-tx">Horário de atendimento</h2>
          {erros.horarios && <p className="text-sm text-err">{erros.horarios}</p>}
          <HorarioSemana dias={dias} aoMudar={setDias} erros={errosDias} />
        </section>
      </div>
    </form>
  )
}
