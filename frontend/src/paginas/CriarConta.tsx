import { useState, type FormEvent } from 'react'
import { Link, Navigate } from 'react-router'
import { ErroApi } from '../api'
import { Alerta, Botao, Campo } from '../componentes/ui'
import { useSessao } from '../sessao'
import { TelaCentral } from './Login'
import { PAINEL } from '../rotas'

/** Criar conta no painel: mesmos campos da tela 07 (RF #04). */
export function CriarConta() {
  const { usuario, cadastrar } = useSessao()
  const [dados, setDados] = useState({ nome: '', email: '', telefone: '', senha: '', confirmacao: '' })
  const [erros, setErros] = useState<Record<string, string>>({})
  const [erroGeral, setErroGeral] = useState<string | null>(null)
  const [enviando, setEnviando] = useState(false)

  if (usuario) return <Navigate to={PAINEL} replace />

  const campo = (chave: keyof typeof dados) => ({
    value: dados[chave],
    onChange: (e: { target: { value: string } }) => setDados({ ...dados, [chave]: e.target.value }),
    erro: erros[chave],
  })

  async function enviar(evento: FormEvent) {
    evento.preventDefault()
    setErroGeral(null)
    // A confirmação de senha é só do front-end (RF #04).
    if (dados.senha !== dados.confirmacao) {
      setErros({ confirmacao: 'As senhas não conferem.' })
      return
    }
    setErros({})
    setEnviando(true)
    try {
      await cadastrar({ nome: dados.nome, email: dados.email, telefone: dados.telefone, senha: dados.senha })
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
    <TelaCentral titulo="Criar conta" subtitulo="Depois, cadastre sua barbearia ou peça ao dono para adicionar você à equipe.">
      <form onSubmit={enviar} className="space-y-4">
        <Campo rotulo="Nome completo" autoComplete="name" required {...campo('nome')} />
        <Campo rotulo="E-mail" type="email" autoComplete="email" required {...campo('email')} />
        <Campo rotulo="Telefone" type="tel" placeholder="(19) 99999-9999" required {...campo('telefone')} />
        <Campo rotulo="Senha" type="password" autoComplete="new-password" ajuda="Mínimo de 8 caracteres, com pelo menos um caractere especial." required {...campo('senha')} />
        <Campo rotulo="Confirmar senha" type="password" autoComplete="new-password" required {...campo('confirmacao')} />
        {erroGeral && <Alerta>{erroGeral}</Alerta>}
        <Botao type="submit" carregando={enviando} className="w-full">
          Criar conta
        </Botao>
      </form>
      <p className="mt-6 text-center text-sm text-tx2">
        Já tem conta?{' '}
        <Link to="/login" className="font-semibold text-pri hover:underline">
          Entrar
        </Link>
      </p>
    </TelaCentral>
  )
}
