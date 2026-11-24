import { useCallback, useState } from 'react'
import { api } from '../api'
import { Alerta, Cabecalho, Campo, Carregando, Selecao } from '../componentes/ui'
import { hojeSaoPaulo, somarDias } from '../formatos'
import { useSessao } from '../sessao'
import { useCarregar } from '../useCarregar'

type Valores = Record<string, string | number | null> | null

type Registro = {
  id: number
  data: string
  hora: string
  usuario: { id: number; nome: string }
  tipo_operacao: string
  operacao: string
  entidade_nome: string
  entidade_afetada: number
  valor_anterior: Valores
  valor_novo: Valores
}

type Resposta = { usuarios: { id: number; nome: string }[]; registros: Registro[] }

const CORES: Record<string, string> = {
  cancelamento: 'bg-err-s text-err',
  remarcacao: 'bg-ale-s text-ale',
  alteracao_cargo: 'bg-ale-s text-ale',
  remocao_colaborador: 'bg-err-s text-err',
  alteracao_estabelecimento: 'bg-inf-s text-pri',
}

const ROTULOS: Record<string, string> = {
  horario_dom: 'Domingo',
  horario_seg: 'Segunda',
  horario_ter: 'Terça',
  horario_qua: 'Quarta',
  horario_qui: 'Quinta',
  horario_sex: 'Sexta',
  horario_sab: 'Sábado',
}

/** Tela 46: trilha de auditoria (RF #38), com filtro por período e por usuário. */
export function Auditoria() {
  const { barbearia } = useSessao()
  const [de, setDe] = useState(somarDias(hojeSaoPaulo(), -30))
  const [ate, setAte] = useState(hojeSaoPaulo())
  const [usuario, setUsuario] = useState('')

  const carregar = useCallback(
    () => api<Resposta>(`/painel/estabelecimentos/${barbearia?.id}/auditoria`, { query: { de, ate, usuario_id: usuario } }),
    [barbearia?.id, de, ate, usuario],
  )
  const { dados, erro, carregando } = useCarregar(carregar, { manterAnterior: true })

  return (
    <>
      <Cabecalho titulo="Trilha de auditoria" />
      <div className="space-y-4 p-8">
        <Alerta tipo="info">Os registros desta tela não podem ser editados nem removidos por nenhum usuário.</Alerta>

        <div className="flex flex-wrap items-end gap-4">
          <Campo rotulo="De" type="date" value={de} onChange={(e) => setDe(e.target.value)} />
          <Campo rotulo="Até" type="date" value={ate} onChange={(e) => setAte(e.target.value)} />
          <Selecao rotulo="Usuário" value={usuario} onChange={(e) => setUsuario(e.target.value)} className="min-w-56">
            <option value="">Todos os usuários</option>
            {dados?.usuarios.map((u) => (
              <option key={u.id} value={u.id}>
                {u.nome}
              </option>
            ))}
          </Selecao>
          {carregando && dados && <span className="pb-3 text-sm text-tx3">Atualizando…</span>}
        </div>

        {erro && <Alerta>{erro}</Alerta>}
        {!dados && !erro && <Carregando />}
        {dados && dados.registros.length === 0 && <Alerta tipo="info">Nenhum registro no período.</Alerta>}
        {dados && dados.registros.length > 0 && (
          <div className="overflow-x-auto rounded-r2 border border-bd bg-sup">
            <table className="w-full text-sm">
              <thead className="bg-fundo text-left text-tx2">
                <tr>
                  <th className="px-4 py-3 font-medium">Data/hora</th>
                  <th className="px-4 py-3 font-medium">Usuário</th>
                  <th className="px-4 py-3 font-medium">Operação</th>
                  <th className="px-4 py-3 font-medium">Entidade</th>
                  <th className="px-4 py-3 font-medium">Valor anterior</th>
                  <th className="px-4 py-3 font-medium">Valor novo</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-bd align-top">
                {dados.registros.map((r) => (
                  <tr key={r.id}>
                    <td className="px-4 py-3 whitespace-nowrap">
                      {r.data.slice(0, 5)} {r.hora}
                    </td>
                    <td className="px-4 py-3">{r.usuario.nome}</td>
                    <td className="px-4 py-3">
                      <span className={`rounded-full px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap ${CORES[r.tipo_operacao] ?? 'bg-sut'}`}>
                        {r.operacao}
                      </span>
                    </td>
                    <td className="px-4 py-3 whitespace-nowrap">
                      {r.entidade_nome} #{r.entidade_afetada}
                    </td>
                    <td className="px-4 py-3">
                      <ListaValores valores={r.valor_anterior} />
                    </td>
                    <td className="px-4 py-3">
                      <ListaValores valores={r.valor_novo} />
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </>
  )
}

function ListaValores({ valores }: { valores: Valores }) {
  if (!valores || Object.keys(valores).length === 0) return <span className="text-tx3">—</span>
  return (
    <dl className="space-y-0.5">
      {Object.entries(valores).map(([chave, valor]) => (
        <div key={chave}>
          <dt className="inline text-tx2">{ROTULOS[chave] ?? chave}: </dt>
          <dd className="inline">{valor === null || valor === '' ? '—' : String(valor)}</dd>
        </div>
      ))}
    </dl>
  )
}
