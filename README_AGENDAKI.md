# AgendAki

Sistema de agendamento para barbearias. Projeto da disciplina de Desenvolvimento Web (TSI, IFSP São João da Boa Vista).

Neste repositório, o AgendAki ocupa três pastas, publicadas pelos grupos Back-end, Front-end e Mobile, e a pasta `docs/`:

| Pasta | O que é | Tecnologia |
|---|---|---|
| `backend/` | API REST, regras de negócio e autenticação | Laravel 13, Sanctum (token Bearer), Pest |
| `frontend/` | Landing page pública (`/`) e painel da barbearia (`/painel`: dono, gerente, atendente e barbeiros) | React, Vite, TypeScript, Tailwind |
| `mobile/` | App do cliente | Flutter (Android) |
| `docs/` | Banco da turma e esquema final, requisitos, telas do protótipo, decisões, endpoints e documentação técnica em PDF | |

Como as partes se ligam: o painel (`frontend/`) e o app (`mobile/`) falam com a API (`backend/`) por HTTP, em JSON, com o mesmo token Bearer. Só a API acessa o banco PostgreSQL.

A raiz deste repositório também tem um projeto Laravel inicial da turma (commit "Init project"), que não faz parte do AgendAki. A documentação técnica em PDF usa os nomes de pasta do repositório original (`api/`, `web/` e `app/`), que aqui são `backend/`, `frontend/` e `mobile/`.

Padrão visual: o painel e o app seguem o front-end da turma, repositório [adrianefranco/agendaki-frontend](https://github.com/adrianefranco/agendaki-frontend). O mapeamento está em `docs/front-turma/MAPEAMENTO.md`.

Banco: PostgreSQL 18, com os bancos `agendaki` (desenvolvimento) e `agendaki_test` (testes automatizados).

## Pré-requisitos

| Ferramenta | Versão usada | Observação |
|---|---|---|
| PHP | 8.5 | Extensões: `pdo_pgsql`, `pgsql`, `mbstring`, `openssl`, `fileinfo`, `curl`, `zip`, `intl`. Confira com `php -m` |
| Composer | 2.8 | |
| Node e npm | 24 LTS | |
| PostgreSQL | 18 | Serviço na porta 5432, com a pasta `bin` no PATH (para o `psql`), a extensão `btree_gist` e suporte a ICU, que vêm no instalador oficial |
| Flutter | estável (3.47) | Com Android SDK, licenças aceitas (`flutter doctor --android-licenses`) e um emulador (AVD). No Windows, ative o Modo de Desenvolvedor |

## Primeira configuração

Os comandos partem da raiz do repositório, no PowerShell.

**1. Banco** (uma vez, como superusuário `postgres`). Escolha uma senha para o usuário `agendaki`:

```powershell
psql -h 127.0.0.1 -U postgres -c "CREATE ROLE agendaki LOGIN PASSWORD 'troque-esta-senha';"
psql -h 127.0.0.1 -U postgres -c "CREATE DATABASE agendaki OWNER agendaki;"
psql -h 127.0.0.1 -U postgres -c "CREATE DATABASE agendaki_test OWNER agendaki;"
```

O usuário `agendaki` não precisa ser superusuário: as migrations criam a extensão `btree_gist`, que o dono do banco pode criar.

**2. API:**

```powershell
cd backend
composer install
copy .env.example .env
```

Abra `backend\.env` e preencha `DB_PASSWORD` com a senha escolhida no passo 1. Depois:

```powershell
php artisan key:generate
php artisan migrate --seed
cd ..
```

O `--seed` cria os dados de demonstração e as contas da seção "Credenciais de desenvolvimento".

**3. Painel e app:**

```powershell
cd frontend
npm install
cd ..\mobile
flutter pub get
cd ..
```

O `.env` nunca é versionado. As credenciais ficam só na máquina local.

## Como rodar

Cada servidor ocupa um terminal. Todos partem da raiz do repositório.

```powershell
# Terminal 1: API em http://localhost:8000 (0.0.0.0 permite acesso pelo emulador e por celular na rede)
cd backend
php artisan serve --host=0.0.0.0 --port=8000
```

```powershell
# Terminal 2: landing page em http://localhost:5173 e painel em http://localhost:5173/painel
cd frontend
npm run dev
```

```powershell
# Terminal 3: emulador e app (aguarde o Android iniciar antes do flutter run)
flutter emulators --launch <nome-do-emulador>
cd mobile
flutter run
```

Prova de vida: http://localhost:8000/api/health deve mostrar `"conectado": true`.

No emulador, o app acessa a API por `http://10.0.2.2:8000/api`. Em celular físico na mesma rede:

```powershell
flutter run --dart-define=API_URL=http://<IP-do-computador>:8000/api
```

O painel aceita outra URL da API pela variável `VITE_API_URL` (veja `frontend/.env.example`), e a API só aceita chamadas do navegador vindas da origem configurada em `CORS_ALLOWED_ORIGINS` (`backend\.env`, padrão `http://localhost:5173`).

## Testes e verificações

```powershell
cd backend
php artisan test            # Pest, contra o banco agendaki_test (nunca SQLite)
cd ..\frontend
npm run build
npm run lint
cd ..\mobile
flutter analyze
flutter test
cd ..
```

## Recriar os dados

```powershell
cd backend
php artisan migrate:fresh --seed
```

Os atendimentos de demonstração são criados para ontem, hoje e amanhã, relativos ao dia em que o comando roda.

E-mails (recuperação de senha) não são enviados de verdade: vão para `backend\storage\logs\laravel.log`. Para ver o último código:

```powershell
Select-String 'Seu código' backend\storage\logs\laravel.log | Select-Object -Last 1
```

### Credenciais de desenvolvimento

**Uso exclusivamente local.** Dados fictícios, criados pelo seeder. Todas as contas usam a senha `Agendaki@2026`.

| Conta | E-mail | Papel |
|---|---|---|
| Lucas Pereira | `cliente@agendaki.test` | Cliente de teste (app) |
| Carlos Mendes | `dono.central@agendaki.test` | Proprietário da Barbearia Central (painel) |
| Rafael Souza | `rafael@agendaki.test` | Barbeiro da Central, segue o horário da barbearia |
| Bruno Lima | `bruno@agendaki.test` | Barbeiro da Central, folga às segundas |
| Diego Alves | `diego@agendaki.test` | Barbeiro da Central, pausa para almoço, folga aos domingos |
| Júlia Ramos | `atendente@agendaki.test` | Atendente da Central: agenda de todos, sem gestão |
| Marcos Ribeiro | `dono.porto@agendaki.test` | Proprietário e barbeiro da Barbearia do Porto (Águas da Prata) |
| Marina, Pedro, Thiago, Felipe | `marina@`, `pedro@`, `thiago@`, `felipe@agendaki.test` | Outros clientes |

## API

Todos os endpoints estão documentados com exemplos executáveis em `docs/api.http` (extensão REST Client do VS Code). Resumo:

- **Públicas:** `GET /api/health`; `POST /api/auth/cadastro`, `/auth/login`, `/auth/esqueci-senha`, `/auth/redefinir-senha`; `GET /api/barbearias`, `/barbearias/{id}`, `/barbearias/{id}/disponibilidade`, `/barbearias/{id}/avaliacoes`
- **Autenticadas:** `POST /api/auth/logout`; `GET` e `PUT /api/me`
- **Cliente:** `/api/agendamentos` (listar, criar, ver, cancelar, remarcar, grade de remarcação e avaliação)
- **Painel:** `/api/painel/estabelecimentos` (barbearias, serviços, equipe e papéis, jornada, agenda do dia, realizado, cancelar, agendamento manual, bloqueios e auditoria)

## Problemas comuns

- **`could not find driver` ou erro de conexão com o banco:** as extensões `pdo_pgsql` e `pgsql` não estão ativas no `php.ini` em uso (`php --ini` mostra qual é).
- **Porta 8000 ou 5173 ocupada:** encerre o processo que a usa ou rode em outra porta. Se mudar a porta do painel, ajuste `CORS_ALLOWED_ORIGINS`.
- **App não conecta à API:** a API precisa estar rodando com `--host=0.0.0.0`. No emulador, o endereço é `10.0.2.2`.
- **`flutter doctor` reclama de licenças:** `flutter doctor --android-licenses`.
- **Erro `collation "und-x-icu" for encoding "UTF8" does not exist` na busca:** o PostgreSQL foi compilado sem suporte a ICU. O instalador oficial e os pacotes das distribuições Linux já trazem o ICU. As buscas funcionam com qualquer locale do banco (C, C.UTF-8 ou pt_BR.UTF-8).

## Documentação

- `docs/AgendAki_Documentacao_Tecnica.pdf`: documentação técnica (arquitetura, fluxos com telas, banco, API, organização do código, testes)
- `docs/DECISOES.md`: decisões e divergências em relação ao material da turma
- `docs/front-turma/MAPEAMENTO.md`: mapeamento do padrão visual do front-end da turma
- `docs/api.http`: endpoints com exemplos executáveis
- `docs/banco/`: banco original da turma e esquema final gerado pelas migrations
- `docs/requisitos/`: requisitos da turma (RF e RNF)
- `docs/prototipo/telas/`: telas de referência do protótipo
