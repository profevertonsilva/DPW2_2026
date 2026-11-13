# Mapeamento do front-end da turma

Origem do padrão visual: repositório da turma `adrianefranco/agendaki-frontend` (branch `main`, commit `e789165`, "Merge pull request #1 from adrianefranco/feat/Diego"), clonado só para leitura em `C:\PROJETOS\agendaki-frontend-turma`. Nenhuma alteração foi feita nele.

Este documento registra o que foi extraído, como cada tela da turma corresponde às nossas e o que foi refinado ao aplicar o padrão no painel (`frontend/`) e no app (`mobile/`). API, banco e regras de negócio não mudaram.

## 1. Stack e organização do repositório da turma

| Item | Valor |
|---|---|
| Linguagem | JavaScript (JSX), sem TypeScript |
| Framework | React 19 com Vite 8 e React Router 7 |
| Estilo | CSS puro com variáveis em `:root` (`src/index.css`) e um arquivo `.css` por tela; sem Tailwind |
| Dados | Simulados com MSW (`src/mocks/`), contrato provisório "v0.1" com envelope `{ data }` |
| Outras dependências | `@zxing/browser` (leitura de QR Code) |
| Testes | Página própria de verificações no navegador (`src/tests/`), sem framework de testes |

```
agendaki-frontend/
├── index.html                  entrada principal (abre o ambiente de testes)
├── webadmin-preview.html       entrada da prévia das telas 05, 31 e 34
└── src/
    ├── index.css               tokens: cores, raios e fonte (variáveis CSS)
    ├── components/             Button, Field, Card, Tag, Bar e components.css
    ├── layouts/                WebAdminLayout (menu lateral escuro)
    ├── pages/                  EstabelecimentoPublico (05), AgendaDia (31), CheckinQRCode (34)
    ├── auth/                   AuthContext e ProtectedRoute (guarda por papel)
    ├── services/api.js         fetch com token Bearer
    ├── mocks/                  MSW: dados e handlers
    └── tests/                  ambiente de testes (componentes, respostas, rotas, verificações)
```

Como a turma também usa React, os componentes foram reaproveitados na forma de reprodução fiel das classes CSS em Tailwind e TypeScript (`frontend/src/componentes/ui.tsx` e `Layout.tsx`), adaptados à nossa API e ao nosso código. No app, o mesmo padrão foi reproduzido em Flutter (Material 3).

## 2. Padrão visual extraído

### 2.1 Cores

| Token da turma | Hex | Uso | Web (Tailwind) | App (Dart) |
|---|---|---|---|---|
| `--pri` | `#1F3A8A` | Cor primária: botões, links, item ativo do menu, números | `pri` | `corPrimaria` |
| `--pri-f` | `#152863` | Primária forte: hover | `pri-f` | `corPrimariaForte` |
| `--pri-s` | `#E8EDFB` | Primária suave: etiqueta "marca", seleção, avatar | `pri-s` | `corPrimariaSuave` |
| `--dest` | `#F0662B` | Destaque: ação principal ("Novo agendamento", "Agendar agora"), símbolo ◈ | `dest` | `corDestaque` |
| `--bg` | `#F7F8FA` | Fundo das telas | `fundo` | `corFundo` |
| `--sup` | `#FFFFFF` | Superfície: cartões, barras, campos | `sup` | `corSuperficie` |
| `--sut` | `#EFF1F5` | Superfície sutil: campo desabilitado, etiqueta neutra | `sut` | `corSutil` |
| `--bd` | `#D9DEE7` | Bordas | `bd` | `corBorda` |
| `--bd-f` | `#AFB7C4` | Borda forte | `bd-f` | `corBordaForte` |
| `--tx` | `#131722` | Texto principal | `tx` | `corTexto` |
| `--tx2` | `#5B6472` | Texto secundário, rótulos | `tx2` | `corTextoSuave` |
| `--tx3` | `#8B94A3` | Texto fraco: dicas (placeholder) | `tx3` | `corTextoFraco` |
| `--suc` / `--suc-s` | `#0E7A46` / `#E2F5EB` | Sucesso: realizado, confirmação | `suc` / `suc-s` | `corSucesso` / `corSucessoSuave` |
| `--ale` / `--ale-s` | `#B87503` / `#FDF3DE` | Alerta | `ale` / `ale-s` | `corAlerta` / `corAlertaSuave` |
| `--err` / `--err-s` | `#C0261B` / `#FDEAE8` | Erro: mensagens, cancelado, remover | `err` / `err-s` | `corErro` / `corErroSuave` |
| `--inf` / `--inf-s` | `#0B6E99` / `#E3F1F7` | Informação | `inf` / `inf-s` | (não usado no app) |
| (menu lateral) | `#111621`, borda `#1F2A39`, texto `#9AA5B4` | Fundo, borda e itens do menu do painel | `lateral`, `lateral-borda`, `lateral-tx` | (não se aplica) |

A primária e o destaque já eram as cores do AgendAki desde a v1.0 (as duas vêm do protótipo da turma). A mudança está no restante da paleta: fundo, bordas, textos e estados, que antes usavam a paleta `slate` do Tailwind e cores próprias no app.

### 2.2 Tipografia

| Elemento | Turma | Onde |
|---|---|---|
| Fonte | Pilha do sistema: `-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif` | `--font-sans` no web; Roboto (padrão do Android) no app |
| Título de página | 32 px, negrito | `Cabecalho` (web) |
| Rótulo acima do título | 11 px, 700, espaçamento 1,5 px, caixa alta, cor primária ("WEBADMIN") | `Cabecalho` (web) e landing |
| Título da barra | 17 px, 600 | `AppBar` (app) |
| Botão | 15 px, 500 (mini: 13 px) | `Botao` (web), `FilledButton` (app) |
| Campo | 15 px; rótulo 13 px, 500, cor secundária | `Campo` (web), `estiloRotulo` (app) |
| Etiqueta | 11 px, 500 a 700, espaçamento 0,2 px | `Etiqueta` (web), `EtiquetaStatus` (app) |
| Mensagens de erro do campo | 11 px, cor de erro | `Campo` e `InputDecorationTheme` |
| Números de resumo | 30 px, cor primária | Cartões de resumo da agenda |

### 2.3 Espaçamentos, raios e sombras

| Item | Valor |
|---|---|
| Raios | `--r1` 8 px (campos, mensagens), `--r2` 12 px (botões, cartões), `--r3` 20 px (login, modais), pílula 999 px (etiquetas); blocos da agenda 10 px |
| Botão | padding 14 x 20 px (mini 8 x 14 px), largura total no celular |
| Campo | altura mínima 46 px, padding 13 x 14 px, borda 1 px, foco com 2 px na cor primária |
| Cartão de seção | padding 20 px, gap 12 px |
| Conteúdo do painel | padding 32 px (16 a 20 px no celular), gap de 24 px entre blocos |
| Barra | padding 14 x 16 px no app; 22 x 32 px no painel |
| Sombras | cartão `0 2px 8px rgba(18,23,34,.05)`; login `0 6px 24px rgba(18,23,34,.07)`; página pública `0 14px 30px rgba(19,23,34,.08)`; bloco da agenda `0 12px 18px rgba(31,58,138,.12)`; menu aberto no celular `18px 0 30px rgba(0,0,0,.2)` |

### 2.4 Ícones

A turma usa caracteres geométricos em vez de biblioteca de ícones: ◈ (marca e check-in), ◫ (agenda), ◉, ▣, ◌, ◔, ◐, ◒, ◍ (itens do menu), ‹ e › (navegação de data e voltar), ☰ e ✕ (menu no celular). No painel foram mantidos esses caracteres. No app ficaram os ícones Material, que são o padrão do Android, nas mesmas cores.

### 2.5 Componentes recorrentes

| Componente da turma | Classe | Reprodução no web | Reprodução no app |
|---|---|---|---|
| `Button` (primario, secundario, destaque, texto, perigo, perigo-cheio, mini) | `.btn` | `Botao` com as mesmas variantes | `BotaoPrincipal`, `FilledButtonTheme`, `OutlinedButtonTheme` |
| `Field` (input, select, textarea, erro, desabilitado) | `.campo` | `Campo`, `Selecao`, `AreaTexto` | `InputDecorationTheme` e `estiloRotulo` |
| `Tag` (marca, sucesso, alerta, erro, neutro) | `.tag` | `Etiqueta` e `EtiquetaStatus` | `EtiquetaStatus` |
| `Card` (bloco, estabelecimento, agendamento, seção, login) | `.bloco`, `.card-estab`, `.card-ag`, `.secao`, `.card-login` | `Cartao`, cartão de login em `TelaCentral` | `Cartao` (com sombra opcional), `FotoBarbearia` |
| `Bar` | `.barra` | `Cabecalho` | `AppBarTheme` |
| `WebAdminLayout` | `.webadmin-*` | `Layout` | (não se aplica) |
| Mensagens | `.checkin-erro`, `.checkin-sucesso`, `.agenda-dia-confirmacao` | `Alerta` | `CaixaMensagem` |
| Estado vazio | `.checkin-vazio`, `.agenda-dia-vazio` | `Vazio` | `Vazio` |
| Capa do estabelecimento | `.estabelecimento-capa` (degradê primária para destaque) | landing (hero) | `CapaBarbearia` |
| Modais | (a turma usa `window.confirm`) | caixas com raio de 20 px e sombra, fundo escurecido `rgba(15,22,31,.7)` | `DialogTheme` com raio de 20 px |

## 3. Telas: turma e AgendAki

| Tela da turma | Arquivo da turma | Tela nossa (protótipo) | Arquivo nosso | Status | O que muda |
|---|---|---|---|---|---|
| 05 Estabelecimento público | `pages/EstabelecimentoPublico.jsx` | 05 Página da barbearia | `mobile/lib/telas/barbearia.dart` | Equivalente (lá é web; aqui é o app do cliente) | Capa em degradê, título, nota, endereço, horário e serviços em cartões com preço na cor primária; "Agendar agora" no destaque. Aqui o botão funciona |
| 31 Agenda do dia | `pages/AgendaDia.jsx` | 31 Agenda do dia | `frontend/src/paginas/Agenda.tsx` | Equivalente | Rótulo e título grandes, setas redondas, cartões de resumo (total, agendados, realizados, cancelados), grade dentro de cartão, blocos em degradê por status, hachura tracejada no bloqueio, legenda com etiquetas |
| 34 Check-in QR Code | `pages/CheckinQRCode.jsx` | 34 Check-in por QR Code | (nenhum) | Só na turma | Fica fora: desejável 9 cortado (`docs/DECISOES.md`, seção 6); o check-in manual está na tela 32 |
| `WebAdminLayout` | `layouts/WebAdminLayout.jsx` | Menu do painel | `frontend/src/componentes/Layout.tsx` | Equivalente | Menu escuro com ◈, cartão "Área administrativa" com o seletor de barbearia, título "Navegação", ícones, avatar no rodapé, menu recolhível no celular |
| Itens "Pendente" do menu: Catálogo, Equipe, Configurações | `layouts/WebAdminLayout.jsx` | 35 e 36 Serviços; 37, 38 e 40 Equipe; 30 Barbearia | `paginas/Servicos.tsx`, `Equipe.tsx`, `BarbeiroServicos.tsx`, `FormBarbearia.tsx` | Só nossas (na turma são só itens de menu) | Padrão aplicado |
| Itens "Pendente" do menu: Clientes, Financeiro, Marketing, Relatórios | `layouts/WebAdminLayout.jsx` | (nenhuma) | (nenhum) | Só na turma | Fora do escopo do briefing (seção 4.3: caixa, relatórios, cupons, fidelidade, marketing) |
| Ambiente de testes (`/testes`, componentes, respostas, rotas, verificações) | `tests/MockTestRoutes.jsx` | (nenhuma) | (nenhum) | Só na turma | Fora: é ferramenta de desenvolvimento com dados simulados (MSW), não tela do produto. Nossos testes ficam na API (Pest), no app e nos roteiros do painel |
| Prévia das telas | `WebAdminPreview.jsx`, `webadmin-preview.html` | (nenhuma) | (nenhum) | Só na turma | Fora pelo mesmo motivo |
| Componentes `Button`, `Field`, `Card`, `Tag`, `Bar` | `components/` | (todas) | `frontend/src/componentes/ui.tsx`, `mobile/lib/componentes.dart` | Equivalente | Reproduzidos e usados em todas as telas |

### 3.1 Telas que só existem no AgendAki (padrão da turma aplicado)

**App (Flutter, `mobile/lib/telas/`):** 09 Login, 07 Cadastro, 10 e 11 Recuperar senha, 01 e 02 Busca e resultados, 06 Avaliações, 14 a 17 Agendamento, 19 Comprovante, 20 Horário indisponível, 21 e 22 Meus agendamentos, 23 Detalhe, 24 Cancelar, 25 Remarcar, 26 Avaliar, 27 Perfil e edição do perfil.

**Painel (React, `frontend/src/`):** 29 Login, Criar conta, 30 Cadastro e edição da barbearia, 32 Detalhe do agendamento (marcar realizado e cancelar), 33 Agendamento manual, 35 e 36 Serviços, 37 e 38 Equipe, 40 Serviços e horários do barbeiro, 41 Bloqueio, 46 Auditoria, mensagens de erro (e-mail sem conta, remoção bloqueada) e estados vazios.

**Nova nesta etapa:** landing page pública em `/` (`frontend/src/paginas/Landing.tsx`). O painel passou para `/painel`.

### 3.2 Telas que só existem na turma, fora do escopo

| Tela | Motivo |
|---|---|
| 34 Check-in por QR Code | Desejável 9, cortado (DECISOES, seção 6) |
| Clientes, Financeiro, Marketing, Relatórios | Fora do escopo do briefing (seção 4.3) |
| Ambiente de testes e prévia | Ferramentas de desenvolvimento com dados simulados |
| Visão "Semana" da agenda | A própria turma marca como pendente; o briefing prevê só a visão Dia (tela 31) |

## 4. Refinamentos sobre o padrão da turma

A identidade da turma foi mantida: cores, raios, fonte, menu escuro, botões, campos, etiquetas, blocos da agenda e capa em degradê. Onde havia inconsistência ou problema de uso, o padrão foi refinado:

| Ponto | Na turma | No AgendAki | Motivo |
|---|---|---|---|
| Nome da marca | "AgendaKi" no layout e "AgendaJá" no ambiente de testes | "AgendAki", com o símbolo ◈ da turma na cor de destaque | Nome oficial do projeto; as duas grafias da turma divergem |
| Itens de menu "Pendente" | Itens desabilitados com selo "Pendente" | Só os itens que funcionam, filtrados pelo papel | Não mostrar funções que não existem |
| Ícone do item Barbearia | ◍ | ⌂ | No Windows, ◍ aparece como emoji colorido e quebra a uniformidade |
| Título duplicado | Título na barra superior e de novo no `h1` da página | Só o `h1`, com o rótulo pequeno acima | Evitar repetição |
| Rótulo acima do título | "WEBADMIN" | Nome da barbearia ativa na agenda e "Painel da barbearia" nas demais | Informação útil no lugar de um termo técnico |
| Grade da agenda | Horas fixas de 9h a 17h, 56 px por hora | Limites pelo horário da barbearia; 96 px por hora | Atendimento de 30 min com hora, cliente e serviço legíveis em duas linhas (perto dos 46 px mínimos da turma) |
| Conteúdo do bloco da agenda | "Cliente #id" e serviço | Hora, nome do cliente e serviço; cancelado em vermelho e riscado | Dados reais da API |
| Rótulo do bloqueio | Centralizado na faixa | No topo da faixa | Centralizado, ficava escondido atrás de um atendimento (defeito visual registrado na v1.0) |
| Bloco de status "alerta" | Texto branco sobre `#B87503` (contraste de cerca de 3:1) | Não usado | Não há status "Aguardando" no AgendAki, e o contraste é baixo para texto pequeno |
| Estados de carregamento | Só texto | Indicador giratório com texto, `role="status"` | Feedback visível e acessível |
| Mensagens de erro e sucesso | Caixa com borda cheia em algumas telas e só texto em outras | Sempre caixa com fundo suave e borda na cor do estado; erro com `role="alert"` | Consistência |
| Foco do teclado | Contorno só em botões | Contorno de 2 px na cor primária em todos os controles | Acessibilidade |
| Foco do campo | Borda passa a 2 px e o padding diminui 1 px | Borda de 1 px mais anel de 1 px na mesma cor | Mesmo efeito visual sem deslocar o texto |
| Peso do texto do botão no app | 500 | 600 | Legibilidade em tela pequena |
| Ponto de quebra do menu | 800 px | 768 px (`md` do Tailwind) | Ponto padrão do Tailwind, diferença de 32 px |
| Modais | `window.confirm` | Caixas no padrão (raio de 20 px, sombra, fundo escurecido); confirmações simples continuam com `confirm` | As telas de bloqueio, agendamento manual e detalhe precisam de formulário |

## 5. Onde ficam os tokens

- **Painel:** `frontend/src/index.css`, bloco `@theme` do Tailwind 4. As classes usam os nomes curtos da turma (`bg-pri`, `text-tx2`, `border-bd`, `rounded-r2`). Componentes base em `frontend/src/componentes/ui.tsx`, menu em `frontend/src/componentes/Layout.tsx`, cores de status em `frontend/src/formatos.ts`.
- **App:** `mobile/lib/componentes.dart`: constantes de cor e raio com o nome da variável da turma em comentário, `temaAgendaki()` (ThemeData, Material 3) e os widgets base.

## 6. Comparativo

Os prints antes (v1.0) e depois (v1.1) de cada tela ficaram no repositório original do AgendAki e não fazem parte desta entrega.
