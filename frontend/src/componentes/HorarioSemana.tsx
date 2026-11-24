import { DIAS_SEMANA } from '../formatos'

export type Dia = { dia_semana: number; ativo: boolean; inicio: string; fim: string }

/** Segunda primeiro, domingo por último (como no protótipo). */
const ORDEM = [1, 2, 3, 4, 5, 6, 0]

/**
 * Editor dos sete dias: chave liga/desliga e horário de início e fim
 * (telas 30 e 40). `erros` usa a chave do dia_semana.
 */
export function HorarioSemana({
  dias,
  aoMudar,
  textoDesligado = 'Fechado',
  erros = {},
}: {
  dias: Dia[]
  aoMudar: (dias: Dia[]) => void
  textoDesligado?: string
  erros?: Record<number, string>
}) {
  const alterar = (dia: number, mudanca: Partial<Dia>) =>
    aoMudar(dias.map((d) => (d.dia_semana === dia ? { ...d, ...mudanca } : d)))

  return (
    <div className="divide-y divide-bd">
      {ORDEM.map((n) => {
        const d = dias.find((x) => x.dia_semana === n)!
        const nome = DIAS_SEMANA[n].split('-')[0]
        return (
          <div key={n} className="flex flex-wrap items-center gap-4 py-3">
            <label className="flex w-40 cursor-pointer items-center gap-3">
              <input
                type="checkbox"
                role="switch"
                aria-label={`${nome} ${d.ativo ? 'ligado' : 'desligado'}`}
                checked={d.ativo}
                onChange={(e) => alterar(n, { ativo: e.target.checked })}
                className="peer sr-only"
              />
              <span className="relative h-6 w-11 rounded-full bg-bd transition peer-checked:bg-pri after:absolute after:top-0.5 after:left-0.5 after:h-5 after:w-5 after:rounded-full after:bg-sup after:transition peer-checked:after:translate-x-5" />
              <span className="font-semibold text-tx">{nome}</span>
            </label>
            {d.ativo ? (
              <div className="flex items-center gap-2 text-sm">
                <input
                  type="time"
                  aria-label={`${nome}: início`}
                  value={d.inicio}
                  onChange={(e) => alterar(n, { inicio: e.target.value })}
                  className="rounded-r1 border border-bd bg-fundo px-2 py-1"
                  required
                />
                <span className="text-tx2">às</span>
                <input
                  type="time"
                  aria-label={`${nome}: fim`}
                  value={d.fim}
                  onChange={(e) => alterar(n, { fim: e.target.value })}
                  className="rounded-r1 border border-bd bg-fundo px-2 py-1"
                  required
                />
              </div>
            ) : (
              <span className="text-sm text-tx3">{textoDesligado}</span>
            )}
            {erros[n] && <span className="w-full text-xs text-err">{erros[n]}</span>}
          </div>
        )
      })}
    </div>
  )
}
