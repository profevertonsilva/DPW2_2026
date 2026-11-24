# Leia-me do líder: Front-end do AgendAki

## O que é esta parte

A pasta `frontend/` é o site do AgendAki, em React 19 com Vite, TypeScript e Tailwind:

- a **landing page** pública, na raiz (`/`);
- o **painel da barbearia**, em `/painel`, onde o dono, o gerente, a atendente e os barbeiros cuidam da agenda, dos serviços, da equipe, dos bloqueios e da auditoria.

Como se liga às outras partes: o painel busca e grava tudo na API (`backend/`), com token Bearer. O app do cliente (`mobile/`) usa a mesma API. Sem a API no ar, só a landing page funciona.

## Como publicar (VS Code)

1. **Tenha o repositório da turma atualizado.** Se ainda não tem: no VS Code, `Ctrl+Shift+P`, "Git: Clone", cole `https://github.com/profevertonsilva/DPW2_2026.git` e escolha onde salvar. Se já tem: abra a pasta e, em Source Control, use "Pull".
2. **Extraia o zip na raiz do clone**, a pasta `DPW2_2026` (a que tem o `README.md` e a pasta oculta `.git`). Atenção: o "Extrair tudo" do Windows sugere uma subpasta com o nome do zip no final do caminho. Apague esse final para extrair direto em `DPW2_2026`. O resultado tem que ser `DPW2_2026\frontend\`.
3. **Abra a pasta `DPW2_2026` no VS Code** e vá em Source Control (`Ctrl+Shift+G`). Devem aparecer só arquivos novos (U) dentro de `frontend/`. Nenhum arquivo modificado (M) fora dela.
4. **Faça o commit** com a mensagem `feat(frontend): painel web e landing page do AgendAki` e clique em "Sync Changes" (ou "Push").

### Pelo terminal

```powershell
git clone https://github.com/profevertonsilva/DPW2_2026.git   # ou, se já tiver: cd DPW2_2026; git pull
cd DPW2_2026
Expand-Archive C:\caminho\do\AgendAki_Frontend.zip -DestinationPath .
git status                    # só frontend/
git add frontend
git commit -m "feat(frontend): painel web e landing page do AgendAki"
git push
```

**Não commite `.env`, `node_modules/` nem `dist/`.** O `.gitignore` da pasta já ignora esses arquivos; mesmo assim, confira o `git status` antes do commit.

## Como rodar e testar a sua parte

Pré-requisito: Node 24 e npm.

```powershell
cd frontend
npm install
npm run dev
```

Abra http://localhost:5173 (landing page) e clique em "Acessar painel" (ou abra http://localhost:5173/painel). Contas de teste no `README_AGENDAKI.md` da raiz (por exemplo, `dono.central@agendaki.test`).

**Endereço da API:** o padrão é `http://localhost:8000/api`. Para usar outro, copie `.env.example` para `.env` e mude o `VITE_API_URL`.

**Verificações:**

```powershell
npm run build    # compila o TypeScript e gera dist/
npm run lint
```

## Do que esta parte depende

- **A API (`backend/`) no ar**, no endereço do `VITE_API_URL`. A API, por sua vez, precisa do PostgreSQL.
- **CORS:** a API só aceita o painel vindo da origem configurada em `CORS_ALLOWED_ORIGINS`, no `.env` do backend. O padrão é `http://localhost:5173`; se o painel rodar em outro endereço, ajuste lá.
- **Ordem:** publique depois do Back-end. Os três commits são independentes, mas o painel só funciona com a API.
