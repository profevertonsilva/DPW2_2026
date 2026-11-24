# Decisões do AgendAki

Registro das decisões que alteram o que a turma definiu (requisitos e banco) ou que precisam ser defendidas na apresentação. Cada entrada traz o motivo e o requisito relacionado. O banco original (`docs/banco/AgendAki_PostgreSQL_turma.sql`) e os requisitos (`docs/requisitos/`) não são alterados; as mudanças ficam aqui.

---

## 1. Divergências em relação aos requisitos da turma

Decididas antes do início do desenvolvimento individual. Constam do cabeçalho de `docs/requisitos/requisitos.md`.

| # | Requisito | Previsto pela turma | No AgendAki | Motivo |
|---|---|---|---|---|
| 1.1 | Escopo | Plataforma multi-segmento, com categoria escolhida no cadastro (RF #09) | Apenas barbearias; categoria única "Barbearia", criada por seeder e oculta na interface | Projeto individual com prazo de cerca de três semanas; o escopo foi reduzido ao que é demonstrável de ponta a ponta |
| 1.2 | RNF #14 (tecnologias) | MySQL e React em todas as pontas | PostgreSQL; React apenas no painel da barbearia; Flutter no app do cliente (Android) | O PostgreSQL oferece restrição de exclusão (`EXCLUDE USING gist`), que garante no banco a ausência de agendamentos sobrepostos (RF #36, RNF #01). O app nativo atende o cliente, e o painel web atende a barbearia |
| 1.3 | RNF #15 (documentação OpenAPI) | Especificação OpenAPI | `docs/api.http`, com exemplos executáveis de todos os endpoints | Mais simples de manter e de demonstrar; cobre o mesmo objetivo de documentar a API |
| 1.4 | RNF #02 (isolamento por filtragem global) | Filtro global por estabelecimento | Rotas do painel aninhadas em `/api/painel/estabelecimentos/{estabelecimento}`, com middleware de vínculo e papel e *scoped bindings*: recurso de outra barbearia resulta em 404 | O isolamento fica explícito em cada rota e é testável (teste de isolamento da seção 9.1 do briefing), sem filtro implícito que pode ser esquecido ou desligado |
| 1.5 | RNF #05 (HTTPS) | Comunicação cifrada | Não se aplica | Demonstração local, sem deploy. O tráfego HTTP sem TLS é liberado apenas no build de debug do Android |
| 1.6 | RNF #07 (pagamento) | Pagamento seguro | Não se aplica | Pagamento online (RF #25) está fora do escopo |
| 1.7 | RNF #08 (consentimento) | Consentimento de uso de dados | Não se aplica | Demonstração local com dados fictícios |

---

## 2. Ambiente e configuração (Fase 0)

| # | Decisão | Motivo |
|---|---|---|
| 2.1 | Usuário de banco próprio, `agendaki`, dono dos bancos `agendaki` e `agendaki_test`, sem privilégio de superusuário | Menor privilégio. Desde o PostgreSQL 13, `btree_gist` é extensão confiável e pode ser criada pelo dono do banco (verificado nos dois bancos) |
| 2.2 | Testes da API em PostgreSQL (`agendaki_test`, no `phpunit.xml`), nunca em SQLite | A restrição de exclusão é recurso do PostgreSQL; testar em outro banco esconderia exatamente o comportamento mais importante (RF #36) |
| 2.3 | Fuso `America/Sao_Paulo` na aplicação (`config/app.php`) e na conexão `pgsql` (`config/database.php`) | RNF #09. O `timestamptz` guarda o instante absoluto, e a sessão do banco devolve horários em `-03:00` |
| 2.4 | CORS restrito às rotas `api/*` e à origem do Vite (`CORS_ALLOWED_ORIGINS`, padrão `http://localhost:5173`); rota `sanctum/csrf-cookie` removida do CORS | A autenticação é por token Bearer, sem cookie de SPA (seção 2 do briefing) |
| 2.5 | API criada sem starter kit e sem Laravel Boost; removidos os arquivos `backend/CLAUDE.md` e `backend/AGENTS.md` gerados pelo instalador | A API é só REST, e o briefing pede poucas dependências. Os arquivos removidos instruíam a instalação do Boost e conflitavam com o `CLAUDE.md` da raiz |
| 2.6 | PHP 8.5 oficial (windows.php.net) em `%USERPROFILE%\develop\php`; Composer e instalador do Laravel vindos do php.new | O binário de PHP do php.new é compilado sem `pgsql`/`pdo_pgsql` e não carrega extensões externas. O PHP do WampServer existente na máquina não é usado nem fica no PATH |
| 2.7 | Painel com o template oficial `react-ts` do Vite, que usa Oxlint como linter, e Tailwind via `@tailwindcss/vite` | Padrões atuais das ferramentas, sem dependências extras |
| 2.8 | URL da API configurável: `VITE_API_URL` no painel (padrão `http://localhost:8000/api`) e `--dart-define=API_URL=...` no app (padrão `http://10.0.2.2:8000/api`, o computador visto pelo emulador) | Permite testar em celular físico na mesma rede sem alterar código |

---

## 3. Banco de dados: correções em relação ao script da turma (Fase 1)

O banco da turma (`docs/banco/AgendAki_PostgreSQL_turma.sql`) foi convertido em migrations do Laravel (`backend/database/migrations`). O esquema resultante está em `docs/banco/AgendAki_schema_final.sql`, gerado com `pg_dump --schema-only`, para comparação lado a lado.

### 3.1 Conversão

| # | Decisão | Motivo |
|---|---|---|
| 3.1.1 | Mantidos os nomes de tabelas, colunas, chaves primárias, estrangeiras, únicas, CHECKs e índices do script da turma (`pk_login`, `fk_atendimento_cliente_local`, `idx_atendimento_estabelecimento_inicio` etc.) | O banco da turma é a base do projeto; preservar os nomes facilita a comparação |
| 3.1.2 | Ids `BIGINT GENERATED BY DEFAULT AS IDENTITY` (a turma usava `INTEGER`, também identity) e chaves estrangeiras `BIGINT` | Padrão do Laravel (`foreignId`). Mesma semântica de geração; faixa maior |
| 3.1.3 | Chaves primárias, CHECKs, a restrição de exclusão e índices parciais criados com `DB::statement`; o resto com o Schema Builder | No PostgreSQL, o Schema Builder ignora o nome da chave primária (gera `login_pkey`) e não cria CHECK nem EXCLUDE |
| 3.1.4 | `TIMESTAMPTZ` e `TIME` sem precisão explícita (microssegundos), como no script | O Laravel 13 usa precisão 0 por padrão; o padrão foi anulado em `AppServiceProvider` |
| 3.1.5 | Da migration padrão do Laravel saiu apenas a tabela `users`; ficaram `password_reset_tokens`, `sessions`, `cache`, `jobs` e `personal_access_tokens` (Sanctum) | A entidade autenticável é `login` (model `Login`, senha em `senha_hash`) |

### 3.2 Correções (seção 5 do briefing)

| # | Correção | Requisito | Motivo |
|---|---|---|---|
| 3.2.1 | Restrição de exclusão `ex_atendimento_sem_sobreposicao` em `atendimento`: mesmo vínculo e intervalo `tstzrange(inicio, fim, '[)')` sobreposto são proibidos para os status ativos (`reservado`, `agendado`, `aguardando_confirmacao`, `confirmado`, `realizado`). Extensão `btree_gist` | RF #36, RNF #01 | O script delegava à aplicação o controle de sobreposição, que falha com requisições simultâneas: as duas verificam antes de qualquer uma gravar, e as duas gravam. No banco, a segunda gravação falha (SQLSTATE 23P01) e a API responde 409 com alternativas. Intervalo semiaberto: 10:00–10:30 e 10:30–11:00 não conflitam |
| 3.2.2 | `servico.preco NUMERIC(10,2) NOT NULL CHECK (preco > 0)` | RF #10, RF #15 | A tabela não tinha preço. `servico_atendimento.valor` continua registrando o preço praticado no agendamento |
| 3.2.3 | `login.email VARCHAR(150) NOT NULL UNIQUE`, gravado em minúsculas (`CHECK (email = lower(email))`) | RF #04, RF #05 | `login` não tinha e-mail, e os e-mails de cliente e profissional não eram únicos. Os e-mails de `cliente` e `profissional` continuam como contato |
| 3.2.4 | `UNIQUE` em `cliente.login_id` e `profissional.login_id` (substituem os índices simples `idx_cliente_login` e `idx_profissional_login`) | RF #04 | Cada login tem no máximo um cliente e um profissional |
| 3.2.5 | `vinculo_estabelecimento.papel` (`proprietario`, `gerente`, `atendente`, `profissional`) e índice único parcial `uq_vinculo_ativo (profissional_id, estabelecimento_id) WHERE status = 'a'` | RF #03 | Sem o papel não havia como saber quem é o dono. O índice parcial permite que um barbeiro removido seja vinculado de novo, preservando o histórico |
| 3.2.6 | Tabelas novas `vinculo_servico` (serviços que cada barbeiro executa) e `jornada` (faixas de trabalho por dia da semana, várias por dia) | RF #13, base dos RF #19 e #20 | O script não tinha onde guardar a atribuição de serviços nem os horários de cada profissional |
| 3.2.7 | Sem PostGIS: `localizacao GEOGRAPHY` e seu índice GiST trocados por `latitude` e `longitude NUMERIC(9,6)` opcionais | RF #16 fora do escopo | O PostGIS complica a instalação no Windows e não seria usado |
| 3.2.8 | Status de `atendimento` sem acento nem espaço: `aguardando_confirmacao`, `nao_compareceu` | — | Evita erro de codificação no Windows e comparação frágil no código |
| 3.2.9 | `auditoria.usuario_responsavel` referencia `login(id)`; nova coluna `entidade VARCHAR(50)`; `valor_anterior` e `valor_novo` em `JSONB` | RF #38 | O cliente também cancela (RF #21), e o cancelamento é auditado; `entidade_afetada` guardava só o id |
| 3.2.10 | `CHECK (nota BETWEEN 1 AND 5)` em `avaliacao` | RF #30 | Escala de nota não definida no script |
| 3.2.11 | CHECKs `hora_fim > hora_inicio` e `dia_semana BETWEEN 0 AND 6` em `jornada` | RF #13 | Mesma convenção de `horario_funcionamento` (0 = domingo) |

### 3.3 Campos sem definição da turma (seção 5.10 do briefing)

Propostas aceitas pela equipe, sem definição prévia da turma.

| Campo | Decisão |
|---|---|
| `login.tipo_usuario` | Gravado como `cliente` no cadastro e não usado em nenhuma regra: os papéis vêm do vínculo com a barbearia |
| `profissional.status` | `a` ou `i`, default `a` (`ck_profissional_status`) |
| `servico.status` | `a` ou `i`, default `a` (`ck_servico_status`). "Excluir" no painel inativa o serviço, porque atendimentos antigos o referenciam |
| `avaliacao.status` | `a` ou `i` (visível ou oculta), default `a` (`ck_avaliacao_status`) |
| `atendimento.presenca_confirmada_em` | Instante em que o atendimento é marcado como realizado |

### 3.4 Mantidos sem uso (seção 5.11)

Tabelas `pacote`, `papel_plataforma` e `login_papel`; colunas `cliente.recorrencia`, `cpf`, `foto_perfil`, `estabelecimento.cobranca_antecipada`, `antecedencia_lembrete_minutos` e `atendimento.reserva_expira_em`; status `reservado`, `expirado`, `aguardando_confirmacao`, `confirmado` e `nao_compareceu`. Existem no banco, sem funcionalidade construída sobre eles.

---

## 4. API e regras (Fases 1 a 4)

| # | Decisão | Motivo |
|---|---|---|
| 4.1 | Isolamento entre barbearias (RNF #02) por rotas aninhadas em `/api/painel/estabelecimentos/{estabelecimento}`, middleware que exige vínculo ativo (sem vínculo: 404; papel insuficiente: 403) e *scoped bindings* (recurso de outra barbearia: 404), no lugar da filtragem global | O isolamento fica explícito em cada rota e é coberto por testes (`IsolamentoTest`) |
| 4.2 | A verificação de horário livre na aplicação continua existindo, mas a garantia é a restrição do banco. Criação e remarcação rodam em `DB::transaction`, e a violação 23P01 é capturada fora do bloco | Capturada dentro, o PostgreSQL deixaria a transação abortada; fora, o rollback devolve uma conexão utilizável |
| 4.3 | Status gravado explicitamente como `agendado` na criação | O default da tabela é `reservado`, pensado para o RF #35; um atendimento criado com ele ocuparia o horário sem nunca expirar no MVP |
| 4.4 | Grade de horários a cada 30 minutos (`config/agendaki.php`), a partir do início de cada faixa de trabalho. O pedido de agendamento não precisa cair na grade, mas precisa caber inteiro em uma faixa, estar no futuro e não sobrepor atendimento ativo nem bloqueio | Seções 5.5 e 6.3 do briefing |
| 4.5 | Remarcação mantém a duração original do atendimento (`fim - inicio`), além de serviço, barbeiro e valor | Mudar a duração do serviço no catálogo não altera agendamentos já feitos, assim como o preço |
| 4.6 | Grade da remarcação em rota autenticada própria (`GET /api/agendamentos/{id}/disponibilidade`), que ignora o próprio atendimento | Evita expor na rota pública um parâmetro para "ignorar" atendimentos de terceiros |
| 4.7 | Cliente que tenta ver, cancelar ou remarcar agendamento de outro cliente recebe 403; barbeiro que age sobre atendimento de outro barbeiro recebe 403 | Seção 6.1 do briefing |
| 4.8 | Senha do cadastro: mínimo de 8 caracteres e pelo menos um caractere especial; a confirmação de senha fica só no front-end | RF #04 |
| 4.9 | Login com erro responde "E-mail ou senha inválidos." sem indicar o campo; login com status `i` responde "Esta conta está inativa." | RF #05 |
| 4.10 | No MVP (Fases 1 a 3), serviços e equipe no painel eram exclusivos do proprietário, e o barbeiro acessava a barbearia, a própria agenda e os próprios atendimentos. Com o desejável 8, o gerente passou a ter a gestão e o atendente a agenda de todos (ver 4.18) | RF #03 |
| 4.11 | Respostas JSON sem o envelope `data` dos API Resources; mensagens de erro e de validação em português (`lang/pt_BR/validation.php`, sem pacote extra) | Clientes mais simples; poucas dependências |
| 4.12 | Datas gravadas com o deslocamento explícito (`$dateFormat = 'Y-m-d H:i:sP'` nos models) e instantes recebidos convertidos para `America/Sao_Paulo` | Um início enviado em UTC (`...Z`) é gravado como o mesmo instante, sem ser reinterpretado como horário local |
| 4.13 | Não é possível remover da equipe um barbeiro com agendamentos futuros com status `agendado`: a API responde 422 com a lista desses agendamentos, e o dono precisa cancelá-los antes (decisão da equipe, 08/11/2026) | Por analogia ao RF #14, em que o bloqueio de horários não cancela automaticamente os agendamentos existentes: o cancelamento é sempre um ato explícito, e o cliente não fica com um agendamento órfão |
| 4.14 | Recuperação de senha (RF #06) com **código de 6 dígitos** enviado por e-mail, válido por 30 minutos e de uso único, em vez de link. O código fica com hash em `password_reset_tokens`, perde a validade após 5 tentativas erradas, e a redefinição encerra todas as sessões (tokens). A resposta ao pedido é sempre a mesma, exista ou não a conta. Em desenvolvimento, o e-mail vai para `storage/logs/laravel.log` (driver `log`) | O app é nativo e não tem deep link: um link no e-mail não abriria a tela 11. Na demonstração, o e-mail só aparece no log do computador, e um código é fácil de digitar no emulador. Prazo de 30 minutos conforme o exemplo do RF #06 |
| 4.15 | Agendamento manual pelo painel (tela 33) só para cliente com conta, identificado pelo e-mail; passa pela mesma proteção contra sobreposição do app. A busca de clientes mostra, por nome, telefone ou e-mail, só quem já tem cadastro na barbearia (`cliente_estabelecimento`); outras contas aparecem apenas pelo e-mail exato. Sem campo "Observações" e sem "Cliente sem cadastro". O barbeiro só agenda na própria agenda | O banco exige `login` para o cliente (INDICE, tela 33). A busca restrita evita expor clientes de outras barbearias (RF #37). `atendimento` não tem coluna de observações, e o briefing manda não inventar campos |
| 4.16 | Trilha de auditoria (RF #38) com as operações do RF #38 que existem no escopo: cancelamento e remarcação de agendamento (pelo cliente ou pelo painel), alteração de cargo e remoção de colaborador, e alteração dos dados da barbearia (só os campos que mudaram). Caixa e cupom estão fora do escopo. Consulta só do proprietário, com filtro por período (padrão: últimos 30 dias) e por usuário | RF #38 |
| 4.17 | Imutabilidade da auditoria garantida no banco: o trigger `tg_auditoria_imutavel` recusa UPDATE e DELETE na tabela `auditoria`, além de a API não oferecer edição nem exclusão | "Registrar, de forma não editável pelos usuários" (RF #38): a garantia não depende só da aplicação, como na proteção do RF #36 |
| 4.18 | Papéis completos (RF #03, desejável 8): **gerente** gere barbearia, serviços, equipe e agenda (sem auditoria); **atendente** vê e gere a agenda de toda a barbearia (realizado, cancelar, bloqueio, agendamento manual), sem editar barbearia, serviços nem equipe; **barbeiro** só a própria agenda. O papel inicial é escolhido ao adicionar (RF #11). Só o proprietário atribui, altera ou remove o papel de gerente; o gerente move colaboradores apenas entre atendente e barbeiro (RF #12) e, por coerência, não remove outro gerente. O papel do proprietário não se atribui nem se altera. Qualquer vínculo pode executar serviços, como já previsto na seção 6.2 do briefing | RF #03, #11 e #12. A restrição do gerente sobre remover outro gerente é interpretação do RF #12 (que só permite ao gerente mexer em atendentes e barbeiros) |

---

## 5. Telas (Fases 2 e 3)

As telas seguem `docs/prototipo/telas/INDICE.md`. Decisões de interface que vale explicar:

| # | Decisão | Motivo |
|---|---|---|
| 5.1 | App: a escolha de data mostra os próximos 21 dias a partir de hoje em São Paulo; dias em que a barbearia fecha aparecem esmaecidos. A grade mostra só os horários livres | Datas calculadas no fuso de São Paulo, independente do fuso do aparelho (RNF #09). O INDICE permite omitir os horários riscados |
| 5.2 | App: no 409, a tela 20 oferece as alternativas devolvidas pela API; escolher uma volta ao resumo com o novo horário, e "Escolher outro horário" volta à grade sem refazer as etapas anteriores. O mesmo vale para a remarcação | INDICE, tela 20 |
| 5.3 | App: na escolha de serviço e de barbeiro, só aparecem serviços com ao menos um barbeiro e barbeiros que executam o serviço escolhido | RF #20 e INDICE, tela 15 |
| 5.4 | Painel: o detalhe do agendamento (tela 32) abre como painel lateral sobre a agenda, com "Marcar como realizado" (habilitado a partir do dia do atendimento) e "Cancelar", com motivo opcional. Sem remarcar no painel | INDICE, tela 32 |
| 5.5 | Painel: na tela 40, "Seguir o horário de funcionamento da barbearia" grava a jornada vazia; fora disso, cada dia tem uma faixa e, opcionalmente, uma pausa (que vira duas faixas no banco) | Atende a tela 40 e a pausa para almoço da seção 5.5 do briefing |
| 5.6 | Painel: a agenda mostra uma coluna por barbeiro com ao menos um serviço (ou com atendimento no dia), hachurando folga, pausa e horário fora do expediente; atendimentos de quem saiu da equipe continuam visíveis | Tela 31; o histórico não some quando um vínculo é encerrado |
| 5.7 | Painel: quem entra sem barbearia vai para o cadastro (tela 30), com aviso de que também pode ser adicionado à equipe de outra barbearia pelo e-mail. O seletor do menu troca a barbearia ativa e oferece cadastrar outra | INDICE, regras gerais (RF #37) |
| 5.8 | Painel: ao tentar remover um barbeiro com agendamentos futuros, a lista retornada pela API aparece com links que abrem cada agendamento na agenda | DECISOES 4.13 |

---

## 6. Desejáveis cortados (Fase 4)

Decisão da equipe (08/11/2026), registrada antes de iniciar os desejáveis da seção 4.2 do briefing.

| Item | Requisito | Motivo do corte |
|---|---|---|
| 7. Reserva temporária durante a confirmação | RF #35 | Mexe na proteção do RF #36: a reserva entraria com status `reservado`, que já ocupa o horário na restrição de exclusão, e exigiria expirar reservas vencidas na mesma transação de cada nova gravação. O risco de quebrar a garantia principal do projeto às vésperas da apresentação não compensa |
| 9. Check-in por QR Code | RF #23 (completo) | O check-in manual ("Marcar como realizado", tela 32) já cobre o RF #23 no MVP; o QR Code exigiria câmera e leitura no painel, sem ganho para a demonstração |

---

## 7. Padrão visual da turma e landing page (v1.1)

Decisão da equipe (10/11/2026). Só apresentação: API, banco e regras de negócio não mudaram.

| # | Decisão | Motivo e referência |
|---|---|---|
| 7.1 | O painel (`frontend/`) e o app (`mobile/`) adotam o padrão visual do front-end da turma, repositório `adrianefranco/agendaki-frontend` (commit `e789165`): paleta completa (fundo, superfície, bordas, três níveis de texto e as cores de sucesso, alerta, erro e informação), raios de 8, 12 e 20 px, botões, campos, etiquetas, cartões, menu lateral escuro com o símbolo ◈, blocos da agenda em degradê e capa da barbearia em degradê | Unificar a identidade com o trabalho da turma. Primária `#1F3A8A` e destaque `#F0662B` já eram as nossas. Detalhes em `docs/front-turma/MAPEAMENTO.md` |
| 7.2 | Os tokens ficam num só lugar em cada ponta: `@theme` do Tailwind em `frontend/src/index.css` e `temaAgendaki()` (ThemeData, Material 3) com as constantes em `mobile/lib/componentes.dart` | Um ponto único de manutenção |
| 7.3 | Refinamentos sobre o padrão da turma (marca "AgendAki", sem itens de menu pendentes, grade da agenda pelo horário da barbearia, rótulo do bloqueio visível, estados de carregamento e erro uniformes, foco visível) | Inconsistências e problemas de uso encontrados no front da turma; lista completa no MAPEAMENTO, seção 4 |
| 7.4 | Telas que só existem na turma (34 Check-in por QR Code, Clientes, Financeiro, Marketing, Relatórios, ambiente de testes) não foram implementadas | Fora do escopo do briefing (seção 4.3) ou desejável cortado (seção 6 deste documento) |
| 7.5 | Landing page pública em `/` do `frontend/`, com "Acessar painel", hero, benefícios, "como funciona", prints e rodapé. O painel passou de `/` para `/painel` (login, criar conta e cadastro de barbearia continuam em `/login`, `/criar-conta` e `/barbearias/nova`) | Pedido da equipe para a v1.1 |
| 7.6 | Os botões "Baixar no Google Play" e "Baixar na App Store" são fictícios: ícones genéricos, sem os selos oficiais, e abrem o aviso "Em breve" | O app não está publicado; os selos oficiais têm regras de uso das lojas |

---

## 8. Entrega no repositório da turma (pacotes por grupo)

Decisão da equipe (10/11/2026). O código vai para o repositório da turma, `profevertonsilva/DPW2_2026`, em três commits feitos pelos líderes dos grupos Back-end, Front-end e Mobile. Eu só preparo os pacotes; nenhuma operação de escrita é feita no repositório da turma.

| # | Decisão | Motivo e referência |
|---|---|---|
| 8.1 | Três pacotes zip em `C:\PROJETOS\entrega-turma\`: `AgendAki_Backend.zip` (`backend/`, conteúdo de `api/`, mais `docs/` e `README_AGENDAKI.md`), `AgendAki_Frontend.zip` (`frontend/`, conteúdo de `web/`, com a landing page) e `AgendAki_Mobile.zip` (`mobile/`, conteúdo de `app/`) | Cada líder publica a sua parte; cada pacote mexe só na própria pasta, então os três commits não se sobrepõem |
| 8.2 | Correspondência de pastas: `api/` vira `backend/`, `web/` vira `frontend/` e `app/` vira `mobile/`. As referências a `docs/` continuam valendo, porque `docs/` fica na raiz nos dois repositórios | Nomes pedidos para o repositório da turma, que ainda não tinha convenção para essas pastas |
| 8.3 | O repositório da turma já tinha, na raiz, um esqueleto Laravel 12 ("Init project") com `README.md`. Nada da raiz é alterado: o README do AgendAki vai como `README_AGENDAKI.md`, e o esqueleto fica como está | Não sobrescrever o que a turma já publicou |
| 8.4 | Ficam fora dos pacotes: `.env`, senhas e tokens, `vendor/`, `node_modules/`, `build/`, `.dart_tool/`, `dist/`, logs, arquivos de IDE, `CLAUDE.md`, `docs/BRIEFING.md`, `docs/ROTEIRO.md`, `docs/apresentacao/` e `docs/front-turma/comparativo/`. Cada pasta leva o próprio `.gitignore` e um `.env.example` com valores de exemplo | Só código e documentação do projeto; material pessoal e credenciais não vão para a turma |
| 8.5 | A URL da API continua configurável: `VITE_API_URL` no frontend (padrão `http://localhost:8000/api`) e `--dart-define=API_URL` no mobile (padrão `http://10.0.2.2:8000/api`) | O banco e a API oficiais ficarão no servidor do professor, a definir depois |
| 8.6 | O `README_AGENDAKI.md` e o leia-me do Back-end avisam que o banco precisa de um locale que trate acentos (no Linux, `pt_BR.UTF-8`). Com o locale `C`, a busca sem diferenciar maiúsculas não encontra "Águas" ao procurar "águas", e um teste da API falha | Encontrado na verificação da entrega: num cluster temporário com locale `C`, 124 de 125 testes passaram; com o locale do instalador do Windows, os 125. Nenhum código foi alterado |
| 8.7 | Verificação da entrega: cópia do clone da turma com os três zips extraídos (só `backend/`, `frontend/`, `mobile/`, `docs/` e `README_AGENDAKI.md` aparecem como novos, nada modificado), instalação do zero num cluster PostgreSQL temporário (`migrate --seed`, 125 testes, `health` conectado), build e lint do frontend com o painel funcionando contra a API do pacote, `flutter analyze`, `flutter test` e build de debug do mobile, e busca de segredos nos zips sem achados. A pasta e o banco temporários foram apagados | Item 4 do pedido da entrega |

---

## 9. Busca independente do locale do banco (v1.1.1)

Decisão da equipe (10/11/2026), a partir do item 8.6.

| # | Decisão | Motivo e referência |
|---|---|---|
| 9.1 | As buscas sem diferenciar maiúsculas (barbearias por nome e cidade; clientes no agendamento manual por nome, telefone e e-mail) comparam `lower(coluna COLLATE "und-x-icu")` com `lower(termo COLLATE "und-x-icu")`, em `App\Support\Busca`, no lugar do `ILIKE`. O resultado é o mesmo em banco com locale C, C.UTF-8 ou pt_BR.UTF-8. Acentos continuam diferenciados: "aguas" não encontra "Águas", como antes | O `ILIKE` converte maiúsculas pela collation da coluna, e com o locale C não converte letras acentuadas. A collation `und-x-icu` existe em qualquer PostgreSQL compilado com ICU, como o instalador oficial e os pacotes das distribuições Linux |
| 9.2 | Teste `BuscaLocaleTest`: troca a collation das colunas buscadas para `"C"` dentro da transação do teste (o mesmo efeito de um banco criado com locale C) e confere a busca com acento e em maiúsculas, nas duas buscas, e que a busca sem acento continua sem achar o nome acentuado. A API passa a ter 127 testes | Os dois testes falhavam com o `ILIKE` e passam com a collation ICU |
| 9.3 | O aviso de locale do item 8.6 deixa de ser necessário. O `README_AGENDAKI.md` do pacote passa a citar só o requisito de PostgreSQL com ICU | Substitui 8.6 |
| 9.4 | O PDF da documentação que vai no pacote da turma é gerado com os nomes de pasta da turma (`backend/`, `frontend/`, `mobile/`); o deste repositório continua com `api/`, `web/` e `app/` | Pedido da equipe |
