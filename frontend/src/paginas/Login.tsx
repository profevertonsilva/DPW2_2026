import { useState, type FormEvent, type ReactNode } from 'react'
import { Link, Navigate } from 'react-router'
import { ErroApi } from '../api'
import { Marca } from '../componentes/Layout'
import { Alerta, Botao, Campo } from '../componentes/ui'
import { PAINEL } from '../rotas'
import { useSessao } from '../sessao'

/** Tela 29: Login do painel (RF #05). */
export function Login() {
  const { usuario, entrar } = useSessao()
  const [email, setEmail] = useState('')
  const [senha, setSenha] = useState('')
  const [erro, setErro] = useState<string | null>(null)
  const [enviando, setEnviando] = useState(false)

  if (usuario) return <Navigate to={PAINEL} replace />

  async function enviar(evento: FormEvent) {
    evento.preventDefault()
    setErro(null)
    setEnviando(true)
    try {
      await entrar(email, senha)
    } catch (e) {
      setErro(e instanceof ErroApi ? (e.erro('email') ?? e.erro('senha') ?? e.message) : 'Erro inesperado.')
    } finally {
      setEnviando(false)
    }
  }

  return (
    <TelaCentral titulo="Entrar no painel" subtitulo="Gerencie a agenda, os serviços e a equipe da sua barbearia.">
      <form onSubmit={enviar} className="space-y-4">
        <Campo rotulo="E-mail" type="email" autoComplete="email" value={email} onChange={(e) => setEmail(e.target.value)} required />
        <Campo rotulo="Senha" type="password" autoComplete="current-password" value={senha} onChange={(e) => setSenha(e.target.value)} required />
        {erro && <Alerta>{erro}</Alerta>}
        <Botao type="submit" carregando={enviando} className="w-full">
          Entrar
        </Botao>
      </form>
      <p className="mt-6 text-center text-sm text-tx2">
        Não tem conta?{' '}
        <Link to="/criar-conta" className="font-semibold text-pri hover:underline">
          Criar conta
        </Link>
      </p>
    </TelaCentral>
  )
}

export function TelaCentral({ titulo, subtitulo, children }: { titulo: string; subtitulo?: string; children: ReactNode }) {
  return (
    <div className="flex min-h-screen items-center justify-center bg-fundo p-4">
      <div className="w-[420px] max-w-full">
        <Link to="/" className="mb-6 flex justify-center" aria-label="AgendAki, página inicial">
          <Marca />
        </Link>
        {/* .card-login da turma: 420 px, raio de 20 px, padding de 36 px e sombra suave */}
        <div className="flex flex-col rounded-r3 border border-bd bg-sup p-6 shadow-login sm:p-9">
          <h1 className="text-2xl font-bold text-tx">{titulo}</h1>
          {subtitulo && <p className="mt-1 mb-6 text-sm text-tx2">{subtitulo}</p>}
          {children}
        </div>
      </div>
    </div>
  )
}
