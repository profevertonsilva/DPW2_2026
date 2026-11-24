import type { ReactNode } from 'react'
import { BrowserRouter, Navigate, Route, Routes } from 'react-router'
import { Layout } from './componentes/Layout'
import { Carregando } from './componentes/ui'
import { Agenda } from './paginas/Agenda'
import { Auditoria } from './paginas/Auditoria'
import { BarbeiroServicos } from './paginas/BarbeiroServicos'
import { CriarConta } from './paginas/CriarConta'
import { Equipe } from './paginas/Equipe'
import { EditarBarbearia, NovaBarbearia } from './paginas/FormBarbearia'
import { Landing } from './paginas/Landing'
import { Login } from './paginas/Login'
import { FormServico, Servicos } from './paginas/Servicos'
import { ProvedorSessao } from './ProvedorSessao'
import { podeGerir, veAuditoria } from './permissoes'
import { PAINEL } from './rotas'
import { useSessao } from './sessao'
import type { Papel } from './tipos'

/** Exige login; quem ainda não tem barbearia vai para o cadastro (tela 30). */
function Protegida({ children, exigeBarbearia = true }: { children: ReactNode; exigeBarbearia?: boolean }) {
  const { carregando, usuario, barbearia } = useSessao()
  if (carregando) return <Carregando />
  if (!usuario) return <Navigate to="/login" replace />
  if (exigeBarbearia && !barbearia) return <Navigate to="/barbearias/nova" replace />
  return children
}

/** Rotas de gestão (proprietário e gerente); quem não pode volta para a agenda. */
function SomenteDono({ children, pode = podeGerir }: { children: ReactNode; pode?: (papel?: Papel) => boolean }) {
  const { barbearia } = useSessao()
  return pode(barbearia?.papel) ? children : <Navigate to={PAINEL} replace />
}

export default function App() {
  return (
    <ProvedorSessao>
      <BrowserRouter>
        <Routes>
          <Route path="/" element={<Landing />} />
          <Route path="/login" element={<Login />} />
          <Route path="/criar-conta" element={<CriarConta />} />
          <Route
            path="/barbearias/nova"
            element={
              <Protegida exigeBarbearia={false}>
                <NovaBarbearia />
              </Protegida>
            }
          />
          <Route
            path="painel"
            element={
              <Protegida>
                <Layout />
              </Protegida>
            }
          >
            <Route index element={<Agenda />} />
            <Route path="servicos" element={<SomenteDono><Servicos /></SomenteDono>} />
            <Route path="servicos/novo" element={<SomenteDono><FormServico /></SomenteDono>} />
            <Route path="servicos/:id" element={<SomenteDono><FormServico /></SomenteDono>} />
            <Route path="equipe" element={<SomenteDono><Equipe /></SomenteDono>} />
            <Route path="equipe/:vinculo" element={<SomenteDono><BarbeiroServicos /></SomenteDono>} />
            <Route path="barbearia" element={<SomenteDono><EditarBarbearia /></SomenteDono>} />
            <Route path="auditoria" element={<SomenteDono pode={veAuditoria}><Auditoria /></SomenteDono>} />
            <Route path="*" element={<Navigate to={PAINEL} replace />} />
          </Route>
          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </BrowserRouter>
    </ProvedorSessao>
  )
}
