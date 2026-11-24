# Telas de referência

Capturas do protótipo visual da turma (`docs/prototipo/prototipo.html`), apenas das telas que entram no escopo do AgendAki. O número é o mesmo do protótipo e do briefing.

As telas são referência de conteúdo e de fluxo, não de pixel. O protótipo foi feito para a versão multi-segmento: nomes, datas e exemplos de salão, pet shop, estética e automotiva são fictícios e devem ser ignorados. Quando esta lista e o briefing divergirem, vale o briefing.

## Regras gerais

- App (Flutter): barra inferior com Buscar, Agenda e Perfil.
- Painel (React): o menu lateral fica com Agenda, Serviços, Equipe e Barbearia (dados e horário). Clientes, Financeiro, Marketing e Relatórios ficam fora.
- O seletor no topo do menu do painel ("Barbearia Central / Proprietário") troca a barbearia ativa (RF #37). Quem ainda não tem barbearia cai direto no cadastro (tela 30).
- "Continuar com Google" não existe em nenhuma tela.

## App do cliente (Flutter)

| Arquivo | Tela | RF | Prioridade | O que muda em relação à imagem |
|---|---|---|---|---|
| 09-login.png | Login | #05 | Obrigatória | "Esqueci minha senha" só com o desejável 3. |
| 07-cadastro.png | Cadastro | #04 | Obrigatória | Termos de uso apenas como texto, sem registro de consentimento. |
| 01-busca.png | Busca | #17 | Obrigatória | Sem chips de categoria e sem "Próximos de você". Campo de busca e lista de barbearias. |
| 02-resultados-da-busca.png | Resultados | #17 | Obrigatória | Busca por nome e cidade. Sem Mapa, Filtros, chips e distância. Nota média só com o desejável 2. |
| 05-pagina-do-estabelecimento.png | Página da barbearia | #15 | Obrigatória | Sem rótulo de categoria. Nota e avaliações só com o desejável 2. |
| 14-agendar-3-servico.png | Escolha do serviço | #18 | Obrigatória | Sem mudanças. |
| 15-agendar-4-profissional.png | Escolha do barbeiro | #20 | Obrigatória | Só barbeiros que executam o serviço. Sem nota e sem descrição, que não existem no banco. |
| 16-agendar-5-data-e-horario.png | Data e horário | #19 | Obrigatória | Pode mostrar só os horários livres, sem os riscados. |
| 17-agendar-resumo.png | Resumo | #18 | Obrigatória | Sem cupom, sem "Repetir este agendamento" e sem forma de pagamento. Exibir "Pagamento no local". |
| 19-comprovante.png | Comprovante | #18 | Obrigatória | Sem a frase sobre e-mail enviado. |
| 20-horario-indisponivel.png | Horário indisponível | #36 | Obrigatória | Aparece quando a API responde 409. As alternativas vêm da resposta. Voltar à escolha de horário sem refazer as etapas anteriores. |
| 21-meus-agendamentos-proximos.png | Próximos | #33 | Obrigatória | Sem status "Aguardando". |
| 22-meus-agendamentos-historico.png | Histórico | #33 | Obrigatória | Filtros de período e status são opcionais. "Avaliar" só com o desejável 2. |
| 23-detalhe-do-agendamento.png | Detalhe | #33 | Obrigatória | Sem QR Code, sem "Pagamento PIX" e sem "Ver no mapa". |
| 24-cancelar-agendamento.png | Cancelar | #21 | Obrigatória | Sem notificação ao barbeiro. |
| 25-remarcar-agendamento.png | Remarcar | #21 | Obrigatória | Mesmo serviço e mesmo barbeiro. Reaproveitar a grade da tela 16. |
| 27-meu-perfil.png | Perfil | #07, #08 | Obrigatória (só Sair) | No MVP: dados em modo leitura e botão Sair. Editar nome e telefone é o desejável 4. Foto e "Excluir minha conta" ficam fora. |
| 26-avaliar-atendimento.png | Avaliar | #30 | Desejável 2 | Só para atendimento realizado, uma avaliação por atendimento. |
| 06-avaliacoes.png | Avaliações | #30 | Desejável 2 | Sem mudanças. |
| 10-recuperar-senha.png | Recuperar senha | #06 | Desejável 3 | E-mail com driver log; o link aparece em `storage/logs`. |
| 11-redefinir-senha.png | Redefinir senha | #06 | Desejável 3 | Sem mudanças. |

## Painel da barbearia (React)

| Arquivo | Tela | RF | Prioridade | O que muda em relação à imagem |
|---|---|---|---|---|
| 29-login-do-webadmin.png | Login | #05 | Obrigatória | Incluir link "Criar conta", com os mesmos campos da tela 07. |
| 30-cadastro-de-estabelecimento.png | Cadastro da barbearia | #09 | Obrigatória | Sem campo Categoria (sempre Barbearia). A mesma tela serve para edição. |
| 35-lista-de-servicos.png | Serviços | #10 | Obrigatória | Sem coluna Categoria. "Excluir" inativa o serviço (ver pendências do banco no briefing). |
| 36-cadastro-de-servico.png | Cadastro de serviço | #10 | Obrigatória | Sem Categoria. |
| 37-gestao-de-equipe.png | Equipe | #11 | Obrigatória | MVP só com proprietário e barbeiro. Sem a ação "Cargo". "Remover" encerra o vínculo. |
| 38-adicionar-colaborador.png | Adicionar barbeiro | #11 | Obrigatória | Busca só por e-mail. Papel fixo de barbeiro no MVP. |
| 40-atribuir-servicos-e-horarios.png | Serviços e horários do barbeiro | #13 | Obrigatória | Uma faixa por dia, como na imagem. Mais de uma faixa (pausa de almoço) é opcional. |
| 31-agenda-do-dia.png | Agenda do dia | #33 | Obrigatória | Só a visão Dia. Barbeiro vê só a própria coluna. "Novo agendamento" só com o desejável 5, faixas de bloqueio só com o desejável 1. Sem status "Aguardando". |
| 32-detalhe-do-agendamento.png | Detalhe do agendamento | #21, #23 | Obrigatória | Botões "Marcar como realizado" e "Cancelar". Sem "Remarcar" no painel. Bloco "Histórico" só com o desejável 6. |
| 41-bloqueio-de-horarios.png | Bloqueio | #14 | Desejável 1 | Avisar agendamentos no intervalo, sem cancelar automaticamente. |
| 33-novo-agendamento-manual.png | Agendamento manual | #18 | Desejável 5 | Só para cliente já cadastrado, pois o banco exige login para o cliente. Sem "Cliente sem cadastro". |
| 46-trilha-de-auditoria.png | Auditoria | #38 | Desejável 6 | Só operações do escopo, sem caixa e cupom. |
| 34-check-in-por-qr-code.png | Check-in por QR Code | #23 | Desejável 9 | No MVP o check-in manual fica na tela 32. |

## Telas fora do escopo (sem imagem)

03 Filtros, 04 Mapa, 08 Cadastro com Google, 12 Categoria, 13 Escolha do estabelecimento por categoria, 18 Pagamento com cartão, 28 Notificações, 39 Alterar cargo, 42 Caixa, 43 Cupons, 44 Fidelidade, 45 Relatórios.
