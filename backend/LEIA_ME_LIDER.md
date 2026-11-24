# Leia-me do líder: Back-end do AgendAki

## O que é esta parte

A pasta `backend/` é a API REST do AgendAki, em Laravel 13: regras de negócio, autenticação por token (Sanctum) e acesso ao banco PostgreSQL. O pacote também traz a pasta `docs/` (banco da turma, requisitos, telas do protótipo, decisões, endpoints e documentação técnica em PDF) e o `README_AGENDAKI.md`, com o guia completo do projeto.

Como se liga às outras partes: o painel web (`frontend/`) e o app (`mobile/`) chamam esta API. Sem a API no ar, os dois não funcionam.

## Como publicar (VS Code)

1. **Tenha o repositório da turma atualizado.** Se ainda não tem: no VS Code, `Ctrl+Shift+P`, "Git: Clone", cole `https://github.com/profevertonsilva/DPW2_2026.git` e escolha onde salvar. Se já tem: abra a pasta e, em Source Control, use "Pull".
2. **Extraia o zip na raiz do clone**, a pasta `DPW2_2026` (a que tem o `README.md` e a pasta oculta `.git`). Atenção: o "Extrair tudo" do Windows sugere uma subpasta com o nome do zip no final do caminho. Apague esse final para extrair direto em `DPW2_2026`. O resultado tem que ser `DPW2_2026\backend\`, `DPW2_2026\docs\` e `DPW2_2026\README_AGENDAKI.md`.
3. **Abra a pasta `DPW2_2026` no VS Code** e vá em Source Control (`Ctrl+Shift+G`). Devem aparecer só arquivos novos (U) dentro de `backend/` e `docs/` e o `README_AGENDAKI.md`. Nenhum arquivo modificado (M) fora disso.
4. **Faça o commit** com a mensagem `feat(backend): API Laravel do AgendAki` e clique em "Sync Changes" (ou "Push").

### Pelo terminal

```powershell
git clone https://github.com/profevertonsilva/DPW2_2026.git   # ou, se já tiver: cd DPW2_2026; git pull
cd DPW2_2026
Expand-Archive C:\caminho\do\AgendAki_Backend.zip -DestinationPath .
git status                    # só backend/, docs/ e README_AGENDAKI.md
git add backend docs README_AGENDAKI.md
git commit -m "feat(backend): API Laravel do AgendAki"
git push
```

**Não commite `.env`, `vendor/` nem `node_modules/`.** O `.gitignore` da pasta já ignora esses arquivos; mesmo assim, confira o `git status` antes do commit. O `.env` guarda a senha do banco e nunca vai para o repositório. Só o `.env.example`, com valores de exemplo, é versionado.

## Como rodar e testar a sua parte

Pré-requisitos: PHP 8.5 (com `pdo_pgsql` e `pgsql`), Composer e PostgreSQL 18. Detalhes no `README_AGENDAKI.md`.

1. **Banco** (uma vez, como `postgres`), com uma senha escolhida por você:

   ```powershell
   psql -h 127.0.0.1 -U postgres -c "CREATE ROLE agendaki LOGIN PASSWORD 'troque-esta-senha';"
   psql -h 127.0.0.1 -U postgres -c "CREATE DATABASE agendaki OWNER agendaki;"
   psql -h 127.0.0.1 -U postgres -c "CREATE DATABASE agendaki_test OWNER agendaki;"
   ```

2. **Instalação:**

   ```powershell
   cd backend
   composer install
   copy .env.example .env
   ```

   Abra `backend\.env` e troque `DB_PASSWORD` pela senha do passo 1. Depois:

   ```powershell
   php artisan key:generate
   php artisan migrate --seed
   php artisan serve --host=0.0.0.0 --port=8000
   ```

3. **Conferir:** abra http://localhost:8000/api/health. Deve aparecer `"conectado": true`.
4. **Testes:** `php artisan test` (127 testes, no banco `agendaki_test`).

## Do que esta parte depende

- **PostgreSQL 18** no ar, com o banco e o usuário do passo 1. A extensão `btree_gist`, que vem no instalador oficial, é criada pelas migrations. As buscas usam a collation ICU `und-x-icu`, presente no instalador oficial e nos pacotes das distribuições Linux, e funcionam com qualquer locale do banco (C, C.UTF-8 ou pt_BR.UTF-8).
- O **frontend** e o **mobile** dependem desta API. Por isso, publique o Back-end primeiro.
