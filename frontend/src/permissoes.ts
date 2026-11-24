// Permissões por papel no painel (RF #03). A API é quem garante; aqui só se
// decide o que mostrar.
import type { Papel } from './tipos'

/** Gestão da barbearia, dos serviços e da equipe. */
export const podeGerir = (papel?: Papel) => papel === 'proprietario' || papel === 'gerente'

/** Agenda de toda a barbearia (o barbeiro vê só a própria). */
export const veAgendaToda = (papel?: Papel) => !!papel && papel !== 'profissional'

/** Trilha de auditoria (RF #38). */
export const veAuditoria = (papel?: Papel) => papel === 'proprietario'

/** Papéis que este usuário pode atribuir na equipe (RF #12: gerente só pelo proprietário). */
export const papeisAtribuiveis = (papel?: Papel): Papel[] =>
  papel === 'proprietario' ? ['profissional', 'atendente', 'gerente'] : ['profissional', 'atendente']
