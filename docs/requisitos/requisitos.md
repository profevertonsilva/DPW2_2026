# Documento de Requisitos da turma (v1.2)

> **Leia antes:** este documento foi escrito para a versão multi-segmento do projeto, feita em grupo.
> Ele serve como referência para os códigos RF e RNF e para o detalhamento de campos e regras.
> **O escopo do AgendAki é definido em `docs/BRIEFING.md`**, que prevalece sobre este documento:
> apenas barbearias, sem marketplace por categoria, sem geolocalização, sem pagamento online,
> sem cupons, fidelidade, caixa ou relatórios. Trechos sobre esses temas não se aplicam.
>
> **Divergências já decididas** (registrar em `docs/DECISOES.md`, não rediscutir):
> RNF #14 previa MySQL e React em tudo; o AgendAki usa PostgreSQL, React no painel e Flutter no app do cliente.
> RNF #15 (OpenAPI) é substituído por `docs/api.http`. RNF #02 (filtragem global) é atendido por rotas
> aninhadas com scoped bindings, conforme o briefing. RNF #05 (HTTPS), RNF #07 (pagamento) e
> RNF #08 (consentimento) não se aplicam à demonstração local.

TSI, IFSP, SJBV

Tecnologia em Sistemas para Internet

Sistema de Agendamento

de Serviços Presenciais

Documento de Requisitos

Versão 1.2

## Histórico da Revisão

| Data | Versão | Descrição | Autor |
|---|---|---|---|
| 06/08/2026 | 1.0 | Criação do documento; definição dos stakeholders. |  |
| 21/08/2026 | 1.1 | Evolução e detalhamento dos requisitos funcionais (RF #01 a RF #34) a partir dos pré-requisitos e recomendações levantadas pelo grupo. |  |
|  | 1.2 | Consolidação: preenchimento das seções 1, 2, 3 e 4.3; acréscimo dos RF #35 a RF #38; inclusão da matriz de rastreabilidade e do registro de consolidação. |  |

## 1. Introdução

Este documento especifica os requisitos do sistema de agendamento desenvolvido como projeto integrador da disciplina de Desenvolvimento Web do curso de Tecnologia em Sistemas para Internet do IFSP, Campus São João da Boa Vista.

Trata-se da consolidação de quatro fontes produzidas pela turma: a planilha de recomendações, preenchida por dez integrantes; o documento de requisitos elaborado a partir dela, que definiu os requisitos RF #01 a RF #34; o diagrama de casos de uso; e a análise funcional da plataforma AppBarber, utilizada como referência de mercado.

Os requisitos RF #01 a RF #34 e as decisões de produto neles contidas foram preservados integralmente. A seção 5 registra o que foi acrescentado nesta versão e quais alternativas foram descartadas no processo.

Este é um documento de disciplina. Ele especifica o produto de forma completa para servir de referência à equipe, mas a entrega esperada é o software em funcionamento. A seção 4.2.3 delimita o subconjunto previsto para a primeira versão.

## 2. Definição do Objetivo

### 2.1 Objetivo Geral

Desenvolver uma plataforma web que conecte consumidores a estabelecimentos prestadores de serviços presenciais mediante hora marcada, permitindo que o cliente localize o estabelecimento, agende um serviço com um profissional específico e acompanhe seu histórico, e que o estabelecimento gerencie sua agenda, sua equipe, seu catálogo e sua movimentação financeira.

A plataforma atende múltiplos segmentos de mercado, barbearia, salão de beleza, clínica de estética, estética automotiva e pet shop, entre outros, por meio da categorização do estabelecimento no momento do cadastro (RF #09), sem especialização de lógica por segmento.

### 2.2 Público Alvo

Público primário, estabelecimentos prestadores de serviço presencial.

Micro e pequenos negócios, com um a quinze colaboradores, que atendem mediante hora marcada em local físico. O perfil típico opera atualmente com agenda de papel, planilha ou conversas por aplicativo de mensagem, e enfrenta perda de faturamento por esquecimento de horários, agendamentos conflitantes e ausência de histórico do cliente. Caracteriza-se por baixa disponibilidade de tempo para configuração inicial e ausência de equipe de tecnologia.

Público secundário, consumidores de serviços presenciais.

Pessoas que buscam um prestador de serviço próximo, desejam comparar preços e avaliações antes de escolher, e preferem agendar sem depender de ligação telefônica. Este público acessa a plataforma tanto para descobrir estabelecimentos novos (RF #16, RF #17) quanto para reagendar com um estabelecimento que já frequenta (RF #33).

### 2.3 Objetivos Específicos

- Permitir que o consumidor localize estabelecimentos por proximidade e refine os resultados por categoria, avaliação, preço e disponibilidade.
- Permitir que o consumidor conclua um agendamento em até cinco etapas de seleção, com confirmação imediata.
- Impedir a ocorrência de agendamentos conflitantes sobre um mesmo profissional, inclusive sob requisições simultâneas.
- Permitir que o estabelecimento organize sua equipe em papéis com permissões distintas e atribua serviços e horários a cada profissional.
- Reduzir o não comparecimento por meio de lembretes automáticos com confirmação de presença.
- Registrar a movimentação financeira diária do estabelecimento, com dedução automática de taxas de cartão.
- Oferecer ao estabelecimento mecanismos de retenção de clientes por meio de cupons e programa de fidelidade.
- Garantir isolamento completo de dados entre estabelecimentos distintos.
### 2.4 Análise da Concorrência (benchmark)

A referência principal foi o AppBarber, submetido a análise funcional de caixa-preta pela turma, com registro em documento próprio e diagrama de casos de uso. A tabela resume o que foi aproveitado e o que foi rejeitado em cada plataforma analisada.

| Plataforma | Origem | Aproveitado | Rejeitado |
|---|---|---|---|
| AppBarber | Barbearia | Estrutura de comanda e caixa, jornada de trabalho por profissional, hierarquia de papéis, links de agendamento | Modelo restrito a um único segmento; exigência de aplicativo dedicado para o cliente |
| Trinks | Beleza em geral | Marketplace com descoberta de estabelecimentos por proximidade e filtros | Cobrança por profissional cadastrado |
| Avec | Beleza e bem-estar | Programa de fidelidade e cupons por estabelecimento | Split automático de pagamento |
| Belasis | Beleza e estética | Gestão de agenda por profissional com bloqueios | Automação de marketing restrita a planos superiores |

Lacuna identificada

As plataformas analisadas são verticais: cada uma atende um segmento específico e sua categorização de serviços reflete esse segmento. Um estabelecimento de estética automotiva ou um pet shop não encontra, nessas plataformas, uma categoria adequada nem um catálogo que faça sentido para seu público. Este projeto endereça a lacuna tratando a categoria como um atributo do estabelecimento (RF #09), utilizado na busca e nos filtros (RF #17), em vez de como uma característica do produto.

### 2.5 Delimitação do Escopo

Registram-se explicitamente as funcionalidades fora do escopo desta versão.

| Item excluído | Justificativa |
|---|---|
| Emissão de documentos fiscais | Exige integração com sistemas municipais heterogêneos, sem valor demonstrativo proporcional ao esforço. |
| Rede multiunidade (matriz e filiais) | Postergado. Um usuário pode ser vinculado a vários estabelecimentos (RF #37), mas não há consolidação entre eles. |
| Assinaturas de clientes com cobrança recorrente | O programa de fidelidade (RF #29) atende ao objetivo de retenção a um custo menor. |
| Controle de estoque | Não há requisito de catálogo de produtos nesta versão. Ver pendência no Anexo A. |
| Cobrança de assinatura dos estabelecimentos pela plataforma | Não há requisito de plano ou cobrança nas fontes consultadas. O modelo de receita da plataforma não foi definido. |

## 3. Definição dos Especialistas de Domínio (Stakeholders)

Os papéis abaixo derivam diretamente das hierarquias definidas nos requisitos RF #01, RF #02 e RF #03.

| Nome | Descrição | Responsabilidade |
|---|---|---|
| Visitante | Usuário não autenticado que navega, busca estabelecimentos e visualiza catálogos, sem permissão para gravar dados (RF #02). | Representar as necessidades do primeiro contato: encontrar o estabelecimento e avaliar serviços e preços antes de criar conta. |
| Usuário Comum | Usuário autenticado que agenda, avalia e consulta seu histórico (RF #02). | Definir requisitos de agendamento, cancelamento, remarcação, pagamento, avaliação e comunicação. |
| Profissional / Prestador | Colaborador com acesso restrito à própria agenda e aos próprios bloqueios de horário (RF #03). | Definir requisitos de agenda pessoal, bloqueio de horários e notificações de atribuição. |
| Atendente | Colaborador responsável pela agenda do estabelecimento e pelo check-in de clientes (RF #03). | Apontar necessidades operacionais do dia a dia: agenda, check-in e movimentação de caixa. |
| Gerente | Responsável pela gestão de equipe, serviços, agenda e relatórios do estabelecimento (RF #03). | Definir requisitos de gestão de equipe, catálogo, cupons, fidelidade e relatórios. |
| Proprietário | Criador do estabelecimento, com acesso irrestrito às configurações e dados financeiros daquele estabelecimento (RF #03). | Autorizar decisões de configuração, política de preços, comissionamento e uso de cobrança antecipada. |
| Administrador da Plataforma | Papel de plataforma com acesso completo às funcionalidades administrativas do sistema (RF #01). | Definir requisitos de isolamento entre estabelecimentos, atribuição de papéis de plataforma e disponibilidade. |
| Moderador | Papel de plataforma responsável pela moderação de avaliações e denúncias, sem acesso a dados financeiros (RF #01). | Definir requisitos de análise e remoção de conteúdo impróprio. |
| Suporte | Papel de plataforma com acesso somente leitura a dados de usuários e estabelecimentos, para atendimento de chamados (RF #01). | Definir requisitos de consulta para resolução de chamados, respeitando limites de privacidade. |

Nota de elicitação: os papéis acima devem ser representados por pessoas reais na aplicação do questionário Kano (seção 4.2.2). Na ausência de acesso a estabelecimentos externos, integrantes da turma podem assumir os papéis, desde que isso fique registrado.

## 4. Solicitações dos Envolvidos

### 4.1 Requisitos Funcionais

Segue abaixo a lista dos requisitos funcionais identificados para este sistema, organizados por área funcional. Cada requisito especifica os tipos de dado esperados nos campos de entrada, bem como as rotas alternativas de navegação para outros requisitos relacionados.

A coluna Origem indica o integrante que propôs a funcionalidade na planilha de recomendações ou a fonte de onde o requisito foi derivado.

#### 4.1.1  Hierarquia e Perfis de Acesso

Esta seção define os níveis de hierarquia e permissão reconhecidos pelo sistema. Os demais requisitos funcionais fazem referência a estes papéis.

#### RF #01: Hierarquia de Papéis da Plataforma
*Origem: Arthur*

O sistema deve reconhecer três papéis de acesso em nível de plataforma, independentes de qualquer estabelecimento específico.

**Papéis definidos:**

- Administrador: acesso completo a todas as funcionalidades administrativas do sistema, incluindo gerenciamento de estabelecimentos, usuários, moderação de conteúdo e atribuição de papéis de plataforma a outros usuários.
- Moderador: acesso a funcionalidades de moderação de conteúdo, como avaliações (RF #30) e denúncias, sem acesso a dados financeiros dos estabelecimentos.
- Suporte: acesso a um painel de atendimento para consulta (somente leitura) de dados de usuários e estabelecimentos, com o objetivo de auxiliar em chamados de suporte.
A atribuição inicial do papel de Administrador é feita diretamente na base de dados do sistema (usuário "raiz") no momento da implantação. A partir de um Administrador existente, novos usuários podem ser promovidos a Administrador, Moderador ou Suporte por meio de um painel administrativo restrito, desde que o usuário promovido já possua um cadastro (RF #04) na plataforma.

**Fluxos alternativos:**

Não se aplica (requisito de definição de domínio, utilizado como referência pelos demais requisitos).

#### RF #02: Hierarquia de Papéis do Cliente
*Origem: Arthur*

O sistema deve reconhecer dois papéis de acesso para o público consumidor de serviços.

**Papéis definidos:**

- Visitante: usuário não autenticado, capaz de navegar, buscar estabelecimentos (RF #17) e visualizar catálogos de serviços (RF #15), porém sem permissão para realizar agendamentos, avaliações ou qualquer ação que grave dados em seu nome.
- Usuário Comum: usuário autenticado, com todas as permissões de Visitante, acrescidas da capacidade de realizar agendamentos (RF #18), avaliar estabelecimentos e serviços (RF #30) e consultar seu próprio histórico (RF #33).
A transição de Visitante para Usuário Comum ocorre automaticamente ao concluir o Cadastro (RF #04) e o Login (RF #05).

**Fluxos alternativos:**

O sistema deve direcionar o Visitante para a tela de Cadastro (RF #04) ou de Login (RF #05) sempre que ele tentar executar uma ação restrita a Usuário Comum.

#### RF #03: Hierarquia de Papéis do Estabelecimento
*Origem: Arthur*

O sistema deve reconhecer quatro papéis de acesso dentro do contexto de um estabelecimento, listados do maior para o menor nível de permissão.

**Papéis definidos (do maior para o menor nível de permissão):**

- Proprietário: criador do estabelecimento, com acesso irrestrito às configurações, dados financeiros, equipe e serviços daquele estabelecimento.
- Gerente: acesso à gestão de equipe (RF #11, RF #12), de serviços (RF #10), da agenda e de relatórios (RF #33, RF #34), sem permissão para excluir o estabelecimento ou transferir a titularidade de Proprietário.
- Atendente: acesso à visualização e gestão da agenda de agendamentos do estabelecimento e ao check-in de clientes (RF #23), sem permissão para editar os dados cadastrais do estabelecimento ou dos serviços.
- Profissional/Prestador: acesso restrito à própria agenda, podendo consultar seus agendamentos (RF #19) e bloquear seus próprios horários (RF #14).
Um usuário torna-se Proprietário automaticamente ao cadastrar um novo estabelecimento (RF #09). Os demais papéis são atribuídos a usuários que já possuem cadastro na plataforma (RF #04) por um Proprietário ou Gerente, por meio da Gestão de Equipe do Estabelecimento (RF #11), podendo ser alterados posteriormente (RF #12). É desta forma que um colaborador "sobe" de nível hierárquico dentro de um estabelecimento.

**Fluxos alternativos:**

O usuário deve ser capaz de consultar a Gestão de Equipe do Estabelecimento (RF #11) para visualizar todos os colaboradores vinculados e seus respectivos papéis.

#### 4.1.2  Cadastro, Login e Conta do Usuário

#### RF #04: Cadastro de Usuário
*Origem: Arthur · Renan*

O usuário (Visitante, RF #02) deve ser capaz de realizar seu cadastro na plataforma por meio de um formulário com os seguintes campos.

**Campos do formulário:**

- Nome completo: texto (string-150), obrigatório, mínimo de 3 caracteres.
- E-mail: texto (string-50), obrigatório, validado conforme formato de e-mail, único na base de dados.
- Senha: texto (string-50, campo mascarado), obrigatório, mínimo de 8 caracteres alfanuméricos, com pelo menos um caractere especial.
- Confirmação de senha: texto (string-50, campo mascarado), obrigatório, idêntico ao campo Senha. Não deve ser persistido; uso exclusivo do front-end.
- Telefone/celular: texto (string-15, máscara (00) 00000-0000), obrigatório.
Deverá haver um botão "Cadastrar" que, ao ser clicado, valida se todos os campos obrigatórios foram preenchidos corretamente. Caso estejam válidos, o sistema efetua o cadastro do usuário com o papel de Usuário Comum (RF #02) e o direciona à tela de Login (RF #05). Caso contrário, o sistema deve exibir uma notificação de erro indicando os campos inválidos.

Alternativamente, o usuário deve ser capaz de realizar o cadastro a partir de uma conta Google, por meio de um botão "Cadastrar com Google". Nesse fluxo, o sistema deve obter automaticamente o nome e o e-mail da conta Google autenticada, solicitando ao usuário apenas o preenchimento do campo Telefone antes de concluir o cadastro.

**Fluxos alternativos:**

O usuário que já possui cadastro deve ser capaz de se dirigir à tela de Login a partir desta tela (RF #05).

#### RF #05: Login de Usuário
*Origem: Arthur*

O usuário deve ser capaz de realizar login na plataforma por meio de um formulário com os seguintes campos.

**Campos do formulário:**

- E-mail: texto (string), obrigatório.
- Senha: texto (string, campo mascarado), obrigatório.
Deverá haver um botão "Entrar" que, ao ser clicado, valida as credenciais informadas. Caso válidas, o sistema autentica o usuário, atribuindo-lhe o papel de Usuário Comum (RF #02), ou papel superior, conforme vínculos de estabelecimento (RF #03) ou de plataforma (RF #01) já atribuídos, e o direciona à tela inicial. Caso inválidas, o sistema deve exibir uma notificação de erro genérica ("e-mail ou senha inválidos"), sem indicar qual dos dois campos está incorreto, por questão de segurança.

Alternativamente, o usuário deve ser capaz de realizar o login a partir de uma conta Google, desde que a conta esteja associada a um cadastro já existente (RF #04).

**Fluxos alternativos:**

O usuário deve ser capaz de se dirigir à tela de Cadastro (RF #04) ou à tela de Recuperação de Senha, por meio de um link "Esqueci minha senha" (RF #06).

#### RF #06: Recuperação de Senha
*Origem: Arthur*

O usuário deve ser capaz de solicitar a redefinição de sua senha a partir da tela de Login (RF #05), informando o e-mail (texto/string, obrigatório, correspondente a um e-mail já cadastrado conforme RF #04).

Ao confirmar, o sistema deve enviar ao e-mail informado um link de redefinição válido por tempo limitado (ex.: 30 minutos). Ao acessar o link, o usuário deve visualizar um formulário com os campos Nova senha e Confirmação de nova senha (mesmas regras de validação do RF #04).

Caso os campos sejam válidos, o sistema atualiza a senha e direciona o usuário à tela de Login (RF #05). Caso o link esteja expirado ou inválido, o sistema deve exibir mensagem de erro e permitir nova solicitação.

**Fluxos alternativos:**

Não se aplica.

#### RF #07: Edição de Perfil do Usuário
*Origem: Arthur*

O usuário autenticado (RF #05) deve ser capaz de visualizar e editar os dados de seu próprio perfil.

**Campos do formulário:**

- Nome completo: texto (string), editável.
- Telefone/celular: texto (string, com máscara numérica), editável.
- Foto de perfil: arquivo de imagem (JPG ou PNG), opcional.
- E-mail: texto (string), somente leitura, por questão de segurança.
Deverá haver um botão "Salvar alterações" que valida e persiste os novos dados. O usuário também deve ser capaz de alterar sua senha a partir desta tela, informando Senha atual, Nova senha e Confirmação de nova senha (mesmas regras do RF #04).

**Fluxos alternativos:**

O usuário deve ser capaz de encerrar sua sessão a partir desta tela (RF #08).

#### RF #08: Logout (Encerramento de Sessão)
*Origem: Arthur*

O usuário autenticado deve ser capaz de encerrar sua sessão na plataforma a partir de um botão "Sair", disponível no menu principal ou na tela de Edição de Perfil (RF #07). Ao confirmar, o sistema deve invalidar o token de autenticação e redirecionar o usuário à tela inicial, retornando seu papel a Visitante (RF #02).

**Fluxos alternativos:**

O usuário deve ser capaz de realizar login novamente a partir da tela para a qual foi redirecionado (RF #05).

#### 4.1.3  Gestão do Estabelecimento

#### RF #09: Cadastro e Edição de Estabelecimento
*Origem: Arthur*

O usuário autenticado (RF #05) deve ser capaz de cadastrar um novo estabelecimento por meio de um formulário com os seguintes campos.

**Campos do formulário:**

- Nome do estabelecimento: texto (string), obrigatório.
- Categoria do estabelecimento: seleção única a partir de categorias pré-definidas (Barbearia, Salão de Beleza, Estética Automotiva, Pet Shop, Clínica de Estética, Outro), obrigatório.
- Descrição geral: texto longo (string), opcional.
- Telefone de contato: texto (string, com máscara numérica), obrigatório.
- CEP: texto (string, máscara 00000-000), obrigatório.
- Estado: seleção única (27 siglas de UF), obrigatório. Cidade, Bairro e Logradouro: texto (string), obrigatórios. Número: inteiro, obrigatório. Complemento: texto (string), opcional.
- Horário de atendimento: para cada dia da semana, campo booleano indicando se o estabelecimento está aberto ou fechado e, se aberto, horário de abertura e de fechamento (tipo hora, formato HH:mm).
Ao concluir e clicar em "Cadastrar Estabelecimento", o sistema valida os campos obrigatórios e, caso válidos, cria o estabelecimento e atribui ao usuário o papel de Proprietário (RF #03) sobre ele. O Proprietário ou Gerente deve ser capaz de editar todos os campos acima a qualquer momento.

**Fluxos alternativos:**

Após o cadastro, o usuário deve ser capaz de se dirigir ao Cadastro de Serviço (RF #10) e à Gestão de Equipe do Estabelecimento (RF #11) para completar a configuração.

#### RF #10: Cadastro e Edição de Serviço
*Origem: Arthur*

O Proprietário ou Gerente (RF #03) de um estabelecimento deve ser capaz de cadastrar um novo serviço oferecido.

**Campos do formulário:**

- Nome do serviço: texto (string), obrigatório (ex.: "Banho e tosa").
- Descrição do serviço: texto longo (string), opcional.
- Valor do serviço: número decimal (formato monetário), obrigatório, maior que zero.
- Tempo de duração do serviço: número inteiro (em minutos), obrigatório, maior que zero.
- Categoria do serviço: seleção única (ex.: Estética, Pets), opcional, utilizada no Catálogo (RF #15) e nos Filtros de Busca (RF #17).
Ao clicar em "Salvar", o sistema valida os campos obrigatórios e cadastra ou atualiza o serviço no catálogo do estabelecimento. O Proprietário ou Gerente também deve ser capaz de editar ou excluir serviços já cadastrados; a exclusão de um serviço já atribuído a algum profissional (RF #13) deve exigir confirmação, informando que agendamentos futuros vinculados a ele podem ser impactados.

#### RF #11: Gestão de Equipe do Estabelecimento
*Origem: Arthur*

O Proprietário ou Gerente (RF #03) deve ser capaz de adicionar um colaborador ao estabelecimento, buscando um usuário já cadastrado na plataforma (RF #04) por e-mail ou telefone.

Após localizar o usuário, deve selecionar o papel inicial a ser atribuído dentro do estabelecimento, dentre as opções definidas em RF #03 (Gerente, Atendente ou Profissional/Prestador), por meio de seleção única.

O colaborador passa a ter acesso ao estabelecimento conforme o papel atribuído, sem necessidade de novo cadastro, é pré-requisito que ele já possua conta na plataforma (RF #04) antes de ser adicionado.

O Proprietário ou Gerente também deve ser capaz de remover um colaborador a qualquer momento, mediante confirmação. A remoção não exclui a conta do usuário na plataforma, apenas o vínculo com aquele estabelecimento.

**Fluxos alternativos:**

O Proprietário ou Gerente deve ser capaz de alterar o cargo de um colaborador (RF #12) e de atribuir serviços a um profissional (RF #13) a partir desta tela.

#### RF #12: Atribuição e Alteração de Cargo do Colaborador
*Origem: Arthur*

O Proprietário ou Gerente (RF #03) deve ser capaz de alterar o cargo de um colaborador já vinculado ao estabelecimento (RF #11), selecionando-o em uma lista e escolhendo um novo papel dentre as opções definidas em RF #03.

O papel de Proprietário não pode ser atribuído por este fluxo, sendo exclusivo de quem cadastrou o estabelecimento (RF #09). Somente um Proprietário pode promover um colaborador a Gerente; um Gerente pode promover ou rebaixar colaboradores apenas entre os papéis de Atendente e Profissional/Prestador.

Ao confirmar a alteração, o sistema atualiza imediatamente as permissões de acesso do colaborador.

**Fluxos alternativos:**

Não se aplica.

#### RF #13: Atribuição de Serviços a Profissionais
*Origem: Arthur*

O Proprietário ou Gerente (RF #03) deve ser capaz de atribuir um ou mais serviços já cadastrados (RF #10) a um profissional já adicionado à equipe (RF #11).

**Campos do formulário:**

- Serviço(s): seleção múltipla a partir dos serviços cadastrados no estabelecimento, obrigatório (pelo menos um).
- Dias e horários de atuação: para cada dia da semana, campo booleano indicando disponibilidade e, se disponível, horário de início e término de expediente (tipo hora, HH:mm).
Ao clicar em "Salvar", o sistema vincula os serviços selecionados ao profissional dentro dos horários informados. Essa atribuição é a base de cálculo utilizada pela Consulta de Horários Disponíveis (RF #19) durante o Fluxo de Agendamento (RF #18).

#### RF #14: Bloqueio de Horários pelo Profissional
*Origem: Arthur · Murilo*

O usuário com papel de Profissional/Prestador (RF #03) deve ser capaz de bloquear períodos específicos de sua própria agenda (folgas, reuniões ou compromissos pessoais).

**Campos do formulário:**

- Data de início do bloqueio: data (DD/MM/AAAA), obrigatório.
- Data de término do bloqueio: data (DD/MM/AAAA), obrigatório, maior ou igual à data de início.
- Horário de início e horário de término: hora (HH:mm), obrigatórios; o término deve ser maior que o início.
- Motivo: texto (string), opcional.
Ao confirmar, o sistema remove os horários bloqueados da Consulta de Horários Disponíveis (RF #19), impedindo novos agendamentos no período. Bloqueios não cancelam automaticamente agendamentos já confirmados; nesse caso, o sistema deve exibir um aviso ao profissional informando o conflito, permitindo que ele opte por manter os agendamentos existentes ou cancelá-los (RF #21).

#### 4.1.4  Catálogo, Busca e Localização

#### RF #15: Visualização do Catálogo de Serviços do Estabelecimento
*Origem: Arthur · Derek · Vagner*

O usuário (Visitante ou Usuário Comum, RF #02) deve ser capaz de visualizar, na página de um estabelecimento, o catálogo completo de serviços oferecidos, cadastrados conforme RF #10.

Para cada serviço, o sistema deve exibir nome, descrição, valor e tempo de duração (ex.: "Corte de Cabelo, 30 min, R$ 40,00"). O usuário também deve ser capaz de visualizar as informações gerais do estabelecimento (nome, categoria, descrição, endereço e horário de atendimento, conforme RF #09).

**Fluxos alternativos:**

O usuário deve ser capaz de iniciar um agendamento a partir de um serviço específico do catálogo (RF #18) e de visualizar as avaliações do estabelecimento (RF #30).

#### RF #16: Localização de Estabelecimentos Próximos (Geolocalização)
*Origem: Arthur · Lucca · Diego*

O usuário deve ser capaz de visualizar, em lista ou mapa, os estabelecimentos mais próximos de sua localização atual.

O sistema deve solicitar permissão de acesso à geolocalização do dispositivo (latitude e longitude, obtidas automaticamente). Caso o usuário não conceda a permissão, o sistema deve permitir a informação manual de um endereço ou CEP (texto/string) como referência.

Para cada estabelecimento exibido, o sistema deve mostrar a distância aproximada (número decimal, em quilômetros) até a localização de referência.

**Fluxos alternativos:**

O usuário deve ser capaz de selecionar um estabelecimento para visualizar seu catálogo (RF #15) e de refinar os resultados com os Filtros de Busca (RF #17).

#### RF #17: Busca e Filtro de Estabelecimentos e Serviços
*Origem: Arthur · Lucca*

O usuário deve ser capaz de buscar estabelecimentos e serviços por meio de um campo de texto livre (string), pesquisando por nome do estabelecimento ou nome do serviço.

**Filtros disponíveis:**

- Categoria do estabelecimento: seleção múltipla, conforme categorias definidas em RF #09.
- Avaliação mínima: seleção única (ex.: 3 estrelas ou mais), com base nas avaliações (RF #30).
- Distância máxima: número decimal (em quilômetros), a partir da localização do usuário (RF #16).
- Faixa de preço: intervalo de valores mínimo e máximo (números decimais).
- Disponibilidade: seleção de data e horário para exibir apenas estabelecimentos com horários livres (RF #19) no período informado.
Os resultados devem ser listados dinamicamente conforme os filtros aplicados, podendo ser ordenados por relevância, distância ou avaliação (seleção única).

**Fluxos alternativos:**

O usuário deve ser capaz de selecionar um estabelecimento nos resultados para visualizar seu catálogo (RF #15).

#### 4.1.5  Agendamento

#### RF #18: Fluxo de Agendamento de Serviço
*Origem: Arthur*

O usuário com papel de Usuário Comum (RF #02) deve ser capaz de realizar um agendamento a partir da tela de Busca (RF #17) ou do Catálogo de um estabelecimento (RF #15), seguindo o fluxo sequencial abaixo.

**Etapas do fluxo:**

- 1. Categoria de serviço (ex.: Barbearia, Estética, Pet Shop), seleção única, caso o usuário ainda não tenha selecionado um estabelecimento específico.
- 2. Estabelecimento, seleção única, a partir da lista da categoria escolhida, ou diretamente, caso o usuário tenha chegado pelo RF #15.
- 3. Serviço, seleção única, dentre os serviços cadastrados pelo estabelecimento (RF #10).
- 4. Profissional, seleção única, dentre os profissionais habilitados a executar o serviço escolhido (RF #20).
- 5. Data e horário, seleção única, dentre os horários disponíveis do profissional escolhido (RF #19).
Ao concluir a seleção, o sistema deve exibir um resumo do agendamento (estabelecimento, serviço, profissional, data/horário e valor) para confirmação. Ao confirmar, o sistema registra o agendamento associado ao Usuário Comum e envia notificação de confirmação (RF #32).

**Fluxos alternativos:**

O usuário deve ser capaz de configurar o agendamento como recorrente (RF #22), de escolher a forma de pagamento e resgatar cupom de desconto (RF #25, RF #28), e de cancelar ou remarcar o agendamento após sua criação (RF #21).

#### RF #19: Consulta de Horários Disponíveis
*Origem: Arthur · Derek*

O sistema deve ser capaz de calcular e exibir, para um profissional e serviço selecionados (RF #18, RF #20), a lista de horários disponíveis para agendamento, considerando cumulativamente:

- Os dias e horários de atuação do profissional (RF #13);
- O tempo de duração do serviço selecionado (RF #10);
- Os agendamentos já existentes no período;
- Os bloqueios de horário definidos pelo profissional (RF #14).
O usuário deve ser capaz de navegar entre diferentes dias (campo do tipo data) para consultar a disponibilidade, exibida como lista de horários (HH:mm) selecionáveis.

**Fluxos alternativos:**

Não se aplica (requisito de suporte ao Fluxo de Agendamento, RF #18).

#### RF #20: Seleção de Profissional do Estabelecimento
*Origem: Arthur · Adrian*

Durante o Fluxo de Agendamento (RF #18), caso o estabelecimento possua mais de um profissional habilitado a executar o serviço escolhido (RF #13), o sistema deve exibir a lista de profissionais disponíveis, permitindo seleção única.

Para cada profissional, o sistema deve exibir, no mínimo, o nome e, quando disponível, foto de perfil e avaliação média (RF #30). Caso apenas um profissional esteja habilitado, o sistema pode selecioná-lo automaticamente, sem exigir ação do usuário.

**Fluxos alternativos:**

Não se aplica (requisito de suporte ao Fluxo de Agendamento, RF #18).

#### RF #21: Cancelamento e Remarcação de Agendamento
*Origem: Arthur*

O Usuário Comum deve ser capaz de cancelar um agendamento já confirmado (RF #18), a partir do seu Histórico de Agendamentos (RF #33), mediante confirmação em caixa de diálogo. Ao cancelar, o sistema deve liberar o horário correspondente na Consulta de Horários Disponíveis (RF #19) e notificar o profissional responsável (RF #31).

O usuário também deve ser capaz de remarcar um agendamento, selecionando nova data e horário (RF #19) dentre os disponíveis para o mesmo profissional e serviço, sem necessidade de refazer todo o Fluxo de Agendamento (RF #18).

O Atendente ou Profissional/Prestador (RF #03) também deve ser capaz de cancelar um agendamento pelo mesmo fluxo, sendo o cliente notificado do cancelamento (RF #32).

**Fluxos alternativos:**

Não se aplica.

#### RF #22: Agendamento Recorrente
*Origem: Arthur · Willian*

Durante o Fluxo de Agendamento (RF #18), o usuário deve ser capaz de configurar a repetição automática de um agendamento.

**Campos do formulário:**

- Frequência de repetição: seleção única (Semanal, Quinzenal ou Mensal), obrigatório.
- Número de repetições ou data final: número inteiro (quantidade) ou campo do tipo data (limite da última ocorrência), uma das duas opções é obrigatória.
Ao confirmar, o sistema deve gerar automaticamente os agendamentos futuros no mesmo dia da semana, horário, serviço e profissional do agendamento original, respeitando a disponibilidade (RF #19) de cada ocorrência. Caso um horário futuro não esteja mais disponível, o sistema deve notificar o usuário sobre o conflito daquela ocorrência específica, sem impedir a criação das demais.

**Fluxos alternativos:**

O usuário deve ser capaz de cancelar uma ocorrência específica ou toda a série a partir do Histórico de Agendamentos (RF #33, RF #21).

#### RF #23: Check-in de Agendamento via QR Code
*Origem: Arthur · Diego*

O sistema deve gerar, para cada agendamento confirmado (RF #18), um código QR único, exibido ao Usuário Comum em seu Histórico de Agendamentos (RF #33).

No momento da chegada do cliente ao estabelecimento, o Atendente ou Profissional/Prestador (RF #03) deve ser capaz de realizar a leitura do código QR (por meio da câmera do dispositivo) para confirmar a presença do cliente, atualizando o status do agendamento para "Realizado".

Alternativamente, o Atendente deve ser capaz de confirmar a presença manualmente, buscando o agendamento por nome do cliente (texto/string) na agenda do estabelecimento.

**Fluxos alternativos:**

Não se aplica.

#### RF #24: Confirmação de Presença via Notificação
*Origem: Arthur · Murilo*

O sistema deve enviar automaticamente, em intervalo configurável antes do horário agendado (ex.: 24 horas ou 2 horas, número inteiro, configurável pelo estabelecimento), uma notificação de lembrete ao Usuário Comum (RF #32), podendo ser integrada a canais externos como WhatsApp.

A notificação deve conter duas ações possíveis: "Confirmar presença" ou "Cancelar agendamento". Caso o usuário selecione "Cancelar agendamento", o sistema deve executar o fluxo de Cancelamento (RF #21), liberando o horário para outros clientes. Caso o usuário não responda até o horário agendado, o agendamento permanece com status "Aguardando confirmação".

**Fluxos alternativos:**

Não se aplica.

#### 4.1.6  Pagamento e Fidelidade

#### RF #25: Seleção de Forma de Pagamento
*Origem: Arthur · Renan*

Durante o Fluxo de Agendamento (RF #18) ou no momento do atendimento (RF #23), o usuário deve ser capaz de selecionar a forma de pagamento do serviço, dentre as opções disponibilizadas pelo estabelecimento, por meio de seleção única: Cartão de Crédito, Cartão de Débito, PIX ou Dinheiro (pagamento presencial).

Caso o estabelecimento habilite cobrança antecipada, os dados de pagamento (número do cartão, validade e código de segurança, campos mascarados) devem ser solicitados e processados por meio de um gateway de pagamento externo antes da confirmação do agendamento.

Caso o pagamento seja presencial (Dinheiro ou Cartão no estabelecimento), o valor é apenas registrado no Gerenciamento de Caixa (RF #26) no momento do atendimento.

**Fluxos alternativos:**

Não se aplica.

#### RF #26: Gerenciamento de Caixa do Estabelecimento
*Origem: Arthur · Vagner*

O Proprietário, Gerente ou Atendente (RF #03) deve ser capaz de registrar entradas e saídas financeiras do estabelecimento.

**Campos do formulário:**

- Tipo de movimentação: seleção única (Entrada ou Saída), obrigatório.
- Valor: número decimal (formato monetário), obrigatório, maior que zero.
- Forma de pagamento: seleção única (Cartão de Crédito, Cartão de Débito, PIX ou Dinheiro), obrigatório.
- Descrição: texto (string), opcional (ex.: "Corte de Cabelo, Cliente João").
- Data e horário: campo do tipo data/hora, preenchido automaticamente no momento do registro, porém editável.
Ao registrar uma movimentação com forma de pagamento em Cartão de Crédito ou Débito, o sistema deve calcular e deduzir automaticamente a taxa da bandeira/operadora (número decimal, percentual configurável pelo estabelecimento), exibindo o valor líquido recebido, de forma a manter a precisão dos Relatórios Avançados (RF #34).

O sistema também deve permitir a visualização do saldo total do caixa em um período selecionado (datas de início e fim).

**Fluxos alternativos:**

Não se aplica.

#### RF #27: Cadastro e Gerenciamento de Cupons de Desconto
*Origem: Arthur · Renan*

O Proprietário ou Gerente (RF #03) deve ser capaz de criar um cupom de desconto para o estabelecimento.

**Campos do formulário:**

- Código do cupom: texto (string), obrigatório, personalizável, único por estabelecimento.
- Tipo de desconto: seleção única (Percentual ou Valor fixo), obrigatório.
- Valor do desconto: número decimal (percentual de 0 a 100, ou valor monetário, conforme o tipo), obrigatório.
- Data de início e data de término de validade: campos do tipo data, obrigatórios; o término deve ser maior ou igual ao início.
- Limite de uso: número inteiro (quantidade máxima de resgates), opcional (sem limite, se não informado).
O Proprietário ou Gerente também deve ser capaz de editar, desativar ou excluir um cupom já criado.

**Fluxos alternativos:**

O cliente deve ser capaz de resgatar um cupom criado a partir deste requisito durante o Fluxo de Agendamento (RF #28).

#### RF #28: Resgate de Cupom de Desconto
*Origem: Arthur · Renan*

O Usuário Comum deve ser capaz de resgatar um cupom de desconto (RF #27) durante o Fluxo de Agendamento (RF #18), informando o Código do cupom (texto/string) no resumo do agendamento.

Ao informar um código válido (dentro do período de validade e do limite de uso definidos em RF #27), o sistema deve aplicar o desconto correspondente ao valor total do agendamento e exibir o novo valor atualizado. Caso o código seja inválido, expirado ou já tenha atingido o limite de uso, o sistema deve exibir notificação de erro informando o motivo.

**Fluxos alternativos:**

Não se aplica.

#### RF #29: Configuração do Sistema de Fidelidade
*Origem: Arthur · Renan*

O Proprietário ou Gerente (RF #03) deve ser capaz de ativar e configurar um programa de fidelidade para o estabelecimento.

**Campos do formulário:**

- Ativar sistema de fidelidade: campo booleano.
- Frequência necessária: número inteiro (quantidade de agendamentos concluídos, RF #23, dentro de um período configurável), obrigatório se ativado.
- Período de referência: seleção única (ex.: mensal, trimestral), obrigatório se ativado.
- Desconto concedido: número decimal (percentual), obrigatório se ativado.
Quando ativado, o sistema deve verificar automaticamente, a cada novo agendamento (RF #18) de um Usuário Comum, se ele atingiu a frequência necessária dentro do período de referência e, em caso positivo, aplicar o desconto de fidelidade configurado ao valor do agendamento, de forma automática (sem necessidade de código, diferentemente do RF #28).

**Fluxos alternativos:**

Não se aplica.

#### 4.1.7  Avaliações

#### RF #30: Avaliação de Estabelecimento e Serviço
*Origem: Arthur · Murilo*

O Usuário Comum deve ser capaz de avaliar um estabelecimento e o serviço prestado, exclusivamente após um agendamento com status "Realizado" (RF #23).

**Campos do formulário:**

- Nota: número inteiro, de 1 a 5 (representado visualmente como estrelas), obrigatório.
- Comentário: texto longo (string), opcional.
Ao confirmar, o sistema associa a avaliação ao estabelecimento, ao serviço e ao profissional envolvidos no agendamento, recalculando a avaliação média exibida no Catálogo (RF #15) e nos resultados de Busca e Filtro (RF #17).

Cada agendamento pode ser avaliado apenas uma vez, porém o Usuário Comum deve ser capaz de editar sua avaliação em até 7 dias após o envio.

**Fluxos alternativos:**

Não se aplica.

#### 4.1.8  Notificações

#### RF #31: Notificações para Profissionais
*Origem: Arthur · Vagner*

O sistema deve emitir notificações automáticas para o Profissional/Prestador (RF #03) sempre que: um novo agendamento for atribuído a ele (RF #18); um agendamento existente for cancelado ou remarcado (RF #21); ou um cliente confirmar ou cancelar presença por meio de lembrete (RF #24).

As notificações devem ser exibidas no aplicativo do profissional, direcionadas aos dispositivos nos quais ele esteja autenticado (RF #05). Cada notificação deve conter, no mínimo: tipo do evento, nome do cliente, serviço e horário envolvidos.

**Fluxos alternativos:**

O profissional deve ser capaz de tocar na notificação para visualizar o agendamento correspondente em sua agenda (RF #19).

#### RF #32: Notificações para Clientes
*Origem: Arthur · Cauan*

O sistema deve emitir notificações automáticas para o Usuário Comum sempre que: um agendamento for confirmado (RF #18); um agendamento for cancelado ou remarcado pelo estabelecimento (RF #21); ou o horário do agendamento estiver próximo (lembrete, RF #24).

As notificações devem ser exibidas no aplicativo e/ou por e-mail, conforme preferência configurada pelo usuário em seu perfil (RF #07).

**Fluxos alternativos:**

O usuário deve ser capaz de tocar na notificação para visualizar o agendamento correspondente em seu Histórico de Agendamentos (RF #33).

#### 4.1.9  Relatórios

#### RF #33: Histórico de Agendamentos
*Origem: Arthur · Renan*

O Usuário Comum deve ser capaz de acessar uma tela com o histórico de todos os seus agendamentos (passados e futuros), podendo filtrar por período (datas de início e fim) e por status (seleção única: Agendado, Realizado ou Cancelado).

Para cada agendamento, o sistema deve exibir: nome do estabelecimento, serviço, profissional, data/horário, valor pago e status.

De forma equivalente, o Proprietário, Gerente ou Atendente (RF #03) deve ser capaz de acessar o histórico de agendamentos do estabelecimento, com os mesmos filtros, acrescido de filtro por profissional. Para cada agendamento, o sistema deve exibir adicionalmente o nome do cliente e o valor recebido pelo estabelecimento.

**Fluxos alternativos:**

O usuário deve ser capaz de cancelar ou remarcar um agendamento a partir desta tela (RF #21) e de avaliar um agendamento com status "Realizado" (RF #30).

#### RF #34: Relatórios Avançados do Estabelecimento
*Origem: Arthur · Willian*

O Proprietário ou Gerente (RF #03) deve ser capaz de visualizar relatórios estatísticos do estabelecimento em um período selecionável (datas de início e fim).

**Conteúdo dos relatórios:**

- Horários mais movimentados: gráfico ou lista com a quantidade de agendamentos por faixa de horário.
- Serviços mais agendados: lista ordenada dos serviços (RF #10) por quantidade de agendamentos no período.
- Clientes mais fiéis: lista ordenada dos clientes por quantidade de agendamentos concluídos no período.
- Faturamento total: número decimal, somando os valores registrados no Gerenciamento de Caixa (RF #26) no período.
O sistema deve permitir a exportação dos relatórios em formato PDF ou planilha (CSV/XLSX).

**Fluxos alternativos:**

Não se aplica.

#### 4.1.10  Integridade, Concorrência e Auditoria

Seção acrescentada na consolidação. Trata de comportamentos que não apareciam nas fontes originais e que não decorrem de decisão de produto, mas de integridade do sistema.

#### RF #35: Reserva Temporária de Horário
*Origem: Consolidação*

Ao selecionar um horário na etapa 5 do Fluxo de Agendamento (RF #18), o sistema deve criar uma reserva temporária daquele horário, com prazo de expiração (campo do tipo data/hora), tornando-o indisponível na Consulta de Horários Disponíveis (RF #19) para outros usuários até a confirmação do agendamento ou o vencimento do prazo.

Expirada a reserva sem confirmação, o sistema deve liberar o horário automaticamente, sem intervenção manual.

**Fluxos alternativos:**

Caso a reserva expire enquanto o usuário ainda está na tela de resumo, o sistema deve informá-lo e retornar à etapa de seleção de data e horário (RF #19).

#### RF #36: Prevenção de Conflito sob Concorrência
*Origem: Consolidação*

O sistema deve garantir que duas requisições simultâneas de agendamento sobre o mesmo profissional, data e horário não resultem em agendamentos conflitantes, confirmando apenas a primeira e retornando à segunda uma notificação de indisponibilidade, com sugestão de horários alternativos (RF #19).

A verificação de disponibilidade realizada na Consulta de Horários Disponíveis (RF #19) não é suficiente para atender a este requisito, por ocorrer em momento anterior à gravação. O comportamento aqui exigido deve ser garantido no momento da persistência do agendamento, conforme o RNF #01.

**Fluxos alternativos:**

O usuário cuja requisição foi recusada deve ser capaz de retornar à seleção de data e horário sem refazer as etapas anteriores do Fluxo de Agendamento (RF #18).

#### RF #37: Isolamento de Dados entre Estabelecimentos
*Origem: Consolidação*

O sistema deve garantir que um usuário vinculado a um estabelecimento (RF #11) não acesse dados de outro estabelecimento ao qual não esteja vinculado, em qualquer operação de leitura ou escrita, incluindo agendamentos, clientes, serviços, movimentações de caixa (RF #26), cupons (RF #27) e relatórios (RF #34).

Um mesmo usuário pode estar vinculado a mais de um estabelecimento, com papéis distintos em cada um (RF #03). Nesse caso, o sistema deve exigir a seleção explícita do estabelecimento em que o usuário está atuando, e restringir os dados exibidos àquele contexto.

Esta restrição não se aplica aos papéis de plataforma (RF #01), que operam por definição acima do escopo de um estabelecimento.

**Fluxos alternativos:**

O usuário vinculado a mais de um estabelecimento deve ser capaz de alternar entre eles sem encerrar a sessão (RF #08).

#### RF #38: Trilha de Auditoria
*Origem: Consolidação*

O sistema deve registrar, de forma não editável pelos usuários, a ocorrência das seguintes operações:

- Cancelamento e remarcação de agendamento (RF #21);
- Alteração de cargo de colaborador (RF #12) e remoção de colaborador (RF #11);
- Registro, edição e exclusão de movimentação de caixa (RF #26);
- Criação, edição, desativação e exclusão de cupom (RF #27);
- Alteração dos dados cadastrais do estabelecimento (RF #09).
Cada registro deve conter: usuário responsável, tipo de operação (texto/string), entidade afetada e seu identificador, valor anterior e valor novo, e data e hora da operação (campo do tipo data/hora, preenchido automaticamente).

O Proprietário (RF #03) deve ser capaz de consultar a trilha de auditoria de seu estabelecimento, com filtro por período e por usuário responsável.

**Fluxos alternativos:**

Não se aplica.

### 4.2 Priorização dos Requisitos Funcionais

#### 4.2.1 Método adotado

A priorização definitiva utiliza o método de Análise Kano, aplicado sobre o conjunto completo RF #01 a RF #38, conforme recomendado na versão anterior deste documento. Para cada requisito submetido à avaliação, aplicam-se duas perguntas ao stakeholder:

- Forma funcional: como você se sentiria se o sistema possuísse esta funcionalidade?
- Forma disfuncional: como você se sentiria se o sistema não possuísse esta funcionalidade?
Escala de resposta para ambas: Gosto assim / Espero que seja assim / Sou neutro / Posso conviver assim / Não gosto assim.

#### 4.2.2 Resultado da priorização

Seção pendente de preenchimento. Os resultados só podem ser registrados após a aplicação do questionário aos stakeholders. Estruturar uma tabela por stakeholder e, ao final, consolidar.

| Identificador do Requisito | Resposta Positiva | Resposta Negativa | Avaliação |
|---|---|---|---|
| RF #01 |  |  |  |

#### 4.2.3 Priorização preliminar por MoSCoW

Enquanto o Kano não é aplicado, adota-se a priorização preliminar abaixo como base de planejamento das entregas. Esta tabela é substituída pelo resultado do Kano, não o antecipa.

| Prioridade | Significado | Requisitos |
|---|---|---|
| Must have | Sem estes requisitos o sistema não realiza sua função. Compõem a primeira entrega. | RF #01 a #05, #09 a #13, #15, #18 a #21, #33, #36, #37 |
| Should have | Alto valor, mas o sistema opera sem eles. Entram conforme a folga de cronograma. | RF #06 a #08, #14, #16, #17, #23, #24, #26, #30, #31, #32, #35, #38 |
| Could have | Desejáveis. Permanecem no backlog priorizado. | RF #22, #25, #27, #28, #29, #34 |

Recomendação de sequenciamento

O RF #36 e o RNF #01 devem ser tratados na mesma etapa em que o agendamento for implementado, e não postergados. A verificação de disponibilidade seguida de inserção é a implementação intuitiva e é também a que falha sob concorrência: a solução exige restrição de unicidade em nível de banco de dados. Corrigir isso depois implica refazer a modelagem da tabela de agendamentos.

### 4.3 Requisitos Não Funcionais

Esta seção estava explicitamente pendente na versão anterior. Os requisitos abaixo foram derivados dos requisitos funcionais já definidos e não introduzem decisões de produto novas.

| ID | Título | Descrição |
|---|---|---|
| RNF #01 | Integridade sob concorrência | O comportamento exigido pelo RF #36 deve ser garantido por restrição de unicidade em nível de banco de dados sobre a combinação de profissional, data e horário, associada a controle transacional. A verificação em camada de aplicação, isoladamente, não atende a este requisito. |
| RNF #02 | Isolamento multiempresa | A restrição exigida pelo RF #37 deve ser aplicada por mecanismo global de filtragem de consultas, e não por filtro acrescentado individualmente a cada consulta do código. |
| RNF #03 | Tempo de resposta | A Consulta de Horários Disponíveis (RF #19) deve responder em até 2 segundos. As demais operações de leitura devem responder em até 1 segundo. |
| RNF #04 | Armazenamento de credenciais | As senhas (RF #04) devem ser armazenadas com função de hash adaptativa com sal (bcrypt). O sistema não deve armazenar nem registrar senhas em texto legível. |
| RNF #05 | Transporte seguro | Toda comunicação entre cliente e servidor deve ocorrer sobre HTTPS. |
| RNF #06 | Autenticação da API | O acesso à API deve ser autenticado por token, com prazo de expiração e possibilidade de revogação, conforme o encerramento de sessão previsto no RF #08. |
| RNF #07 | Dados de pagamento | O sistema não deve armazenar número completo de cartão, código de segurança ou dados equivalentes. Os dados de pagamento previstos no RF #25 devem ser transmitidos diretamente ao gateway externo, cabendo ao sistema armazenar apenas o identificador da transação e seu status. |
| RNF #08 | Consentimento e dados pessoais | O sistema deve registrar o consentimento do titular no momento do cadastro (RF #04), com data, hora e versão do termo aceito, e permitir a exclusão da conta com anonimização dos dados pessoais, preservando os registros financeiros (RF #26). |
| RNF #09 | Tratamento de fuso horário | Todos os instantes devem ser persistidos em UTC e convertidos para o fuso do estabelecimento no momento da exibição. |
| RNF #10 | Responsividade | A interface deve ser utilizável em telas a partir de 360 pixels de largura, sem rolagem horizontal. |
| RNF #11 | Usabilidade do agendamento | O Fluxo de Agendamento (RF #18) deve ser concluído em no máximo cinco etapas de seleção, conforme especificado no próprio requisito. |
| RNF #12 | Acessibilidade | As telas de cadastro, busca e agendamento devem observar contraste mínimo e tamanho de fonte legível, conforme o nível AA das WCAG 2.1. |
| RNF #13 | Compatibilidade | O sistema deve funcionar nas duas versões mais recentes dos navegadores Chrome, Firefox, Safari e Edge. |
| RNF #14 | Plataforma tecnológica | O sistema deve ser desenvolvido com Laravel na camada de aplicação e API, React na camada de apresentação e MySQL na camada de persistência. |
| RNF #15 | Contrato de API | O contrato da API deve ser especificado em OpenAPI antes da implementação de cada módulo, permitindo o trabalho paralelo das frentes de back-end e front-end. |
| RNF #16 | Canal de notificação | A integração com WhatsApp prevista no RF #24 é opcional. O sistema deve operar integralmente com notificação interna e e-mail, sendo o WhatsApp um canal adicional habilitável. |
| RNF #17 | Cobertura de testes | Os requisitos classificados como Must have na seção 4.2 devem possuir testes automatizados cobrindo seus critérios de aceite, com atenção especial ao RF #36 e ao RNF #01. |
| RNF #18 | Rastreabilidade | Cada requisito funcional deve possuir vínculo documentado com os casos de teste que o verificam. |
| RNF #19 | Cópia de segurança | O banco de dados deve ser objeto de rotina diária de cópia de segurança, com procedimento de restauração testado ao menos uma vez durante o projeto. |
| RNF #20 | Idioma e formato | A interface deve ser apresentada em português brasileiro, com valores monetários em real e datas no formato dia/mês/ano. |

### 4.4 Matriz de Rastreabilidade das Contribuições

A tabela relaciona cada proposta registrada na planilha de recomendações ao requisito ou requisitos que a incorporaram, evidenciando que todas as contribuições foram tratadas.

| Integrante | Proposta original | Requisito(s) |
|---|---|---|
| Derek | Listar tipos de serviços por estabelecimento | RF #15 |
| Derek | Consulta de horários disponíveis | RF #19 |
| Murilo | Sistema de confirmação | RF #24 |
| Murilo | Sistema de avaliações | RF #30 |
| Murilo | Bloqueio de horários | RF #14 |
| Renan | Sistema de escolha de pagamento | RF #25 |
| Renan | Cadastro e resgate de cupons | RF #27, RF #28 |
| Renan | Sistema de cadastro de usuários | RF #04 |
| Renan | Histórico de consultas | RF #33 |
| Renan | Sistema de fidelidade | RF #29 |
| Vagner | Catálogo | RF #15, confirmar se a proposta se refere a serviços ou a produtos |
| Vagner | Notificações para profissionais | RF #31 |
| Vagner | Gerenciamento de caixa | RF #26 |
| Lucca | Localização de serviços e lojas | RF #16 |
| Lucca | Filtros de serviços e lojas | RF #17 |
| Willian | Agendamento recorrente | RF #22 |
| Willian | Relatórios avançados | RF #34 |
| Diego | Geolocalização | RF #16 |
| Diego | Check-in por QR Code | RF #23 |
| Arthur | Cadastro e edição do estabelecimento | RF #09 |
| Arthur | Cadastro e edição de serviço | RF #10 |
| Arthur | Perfil do profissional | RF #07, RF #13 |
| Arthur | Fluxo de agendamento | RF #18 |
| Arthur | Cargos e hierarquias | RF #01, RF #02, RF #03, RF #11, RF #12 |
| Arthur | Diferentes meios de autenticação | RF #04, RF #05 |
| Adrian | Seleção de prestadores no estabelecimento | RF #20 |
| Cauan | Notificações para clientes | RF #32 |
| Grupo de QA | Lacunas de integridade e auditoria | RF #35, RF #36, RF #37, RF #38 |

## 5. Registro de Consolidação

Esta seção registra o que foi alterado nesta versão em relação à anterior, e quais alternativas foram avaliadas e descartadas. Serve para que decisões já tomadas não voltem à discussão sem informação nova.

Preservado integralmente

Os requisitos RF #01 a RF #34 foram mantidos com a numeração, a estrutura em nove áreas funcionais, os campos de formulário e os fluxos alternativos definidos no documento anterior. Nenhuma decisão de produto ali registrada foi alterada.

Modelo de descoberta

Confirmado o modelo de marketplace, conforme RF #16, RF #17 e a etapa 1 do RF #18. O cliente localiza estabelecimentos dentro da plataforma, por proximidade e por filtros. Uma proposta anterior de restringir o acesso ao link próprio de cada estabelecimento foi descartada.

Modelo de pagamento

Confirmado o modelo híbrido do RF #25: cobrança antecipada por gateway externo quando habilitada pelo estabelecimento, e registro no caixa (RF #26) quando o pagamento for presencial. Uma proposta anterior de registro exclusivamente declaratório foi descartada.

Modelo multi-segmento

Confirmada a abordagem do RF #09, que trata a diversidade de segmentos por meio do campo Categoria do estabelecimento. Uma proposta alternativa, baseada em entidades genéricas de Recurso e Objeto Atendido, foi descartada por elevar significativamente o custo de implementação sem ganho proporcional no escopo desta versão. A consequência conhecida está registrada no Anexo A.

Acrescentado

Foram acrescentados os requisitos RF #35 a RF #38, referentes a reserva temporária, concorrência, isolamento entre estabelecimentos e trilha de auditoria. Não decorrem de decisão de produto, e sim de comportamentos necessários à integridade do sistema, ausentes em todas as fontes consultadas.

Preenchido

Foram preenchidas as seções 1, 2 e 3, anteriormente em formato de modelo, e a seção 4.3, explicitamente adiada na versão anterior. O conteúdo dessas seções foi derivado dos requisitos já definidos, sem introduzir decisões novas.

## Anexo A, Pendências desta Versão

- Preenchimento do histórico de revisão com os nomes dos autores.
- Aplicação do questionário Kano sobre RF #01 a RF #38 e preenchimento da seção 4.2.2, conforme recomendado na versão anterior deste documento.
- Confirmação, junto ao integrante Vagner, se a proposta "Catálogo" registrada na planilha de recomendações se refere ao catálogo de serviços (RF #15) ou a um catálogo de produtos vendidos pelo estabelecimento, hipótese em que será necessário um requisito adicional.
- Registro de dado próprio do objeto atendido: no modelo adotado (RF #09), um pet shop não dispõe de campo para identificar o animal atendido, nem uma estética automotiva para identificar o veículo. Avaliar se o campo de descrição do agendamento é suficiente para a primeira versão ou se um requisito adicional é necessário.
- Definição do provedor de gateway de pagamento previsto no RF #25 e verificação da disponibilidade de ambiente de homologação.
- Definição dos critérios de aceite de cada requisito Must have, sob responsabilidade da frente de QA.
- Elaboração do modelo conceitual de dados, sob responsabilidade da frente de Banco, com atenção à restrição de unicidade exigida pelo RNF #01.
- Definição do contrato OpenAPI dos módulos da primeira entrega (RNF #15).
- Atualização do diagrama de casos de uso para incorporar os requisitos RF #35 a RF #38.