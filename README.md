# REDMIN - Guia Mestre do Projeto

## 1. Visão Geral

Este repositório contém um projeto chamado **Docs Monitor**.

O objetivo do sistema é:

- monitorar uma caixa de e-mail via IMAP;
- localizar mensagens novas de um remetente autorizado;
- extrair um link de documento do corpo do e-mail;
- baixar o arquivo;
- armazenar o documento no projeto;
- registrar e exibir os documentos em um painel web.

Hoje o repositório está dividido em dois blocos:

- `docs/`: documentação estratégica e funcional criada antes ou durante a implementação;
- `docs-monitor/`: aplicação Laravel que materializa o sistema.

Também existe uma pasta `.agent/` com instruções, estratégia, regras de negócio e prompts-base usados para orientar agentes de IA durante a construção do projeto.

## 2. Estrutura do Repositório

```text
.
├── .agent/                     # contexto para agentes de IA
├── docs/                       # documentação funcional e estratégica
├── docs-monitor/               # aplicação Laravel principal
└── REDMIN.MD                   # este guia
```

### Pastas mais importantes

#### `.agent/`

Arquivos de apoio para IA:

- `Skill.md`: prompt base do projeto
- `geral.md`: visão geral e estratégia
- `inicial.md`: instrução de exploração e documentação
- `regrasdenegocio.md`: regras do MVP
- `tecnologias.md`: stack planejada
- `uteisdocs.md`: links úteis

#### `docs/`

Documentação já existente:

- `00-README.md`
- `01-ESTRATEGIA.md`
- `02-MVP.md`
- `03-REGRAS-DE-NEGOCIO.md`
- `04-TECNOLOGIAS.md`
- `05-CHECKLIST-IMPLEMENTACAO.md`

#### `docs-monitor/`

Aplicação Laravel responsável pela execução real do sistema.

Arquivos centrais:

- `app/Console/Commands/EmailPollChecker.php`
- `app/Jobs/ProcessNewEmailJob.php`
- `app/Models/Document.php`
- `app/Livewire/DocumentsPanel.php`
- `resources/views/livewire/documents-panel.blade.php`
- `routes/web.php`
- `routes/console.php`
- `config/imap.php`
- `database/migrations/2026_03_31_023030_create_documents_table.php`

## 3. Objetivo de Negócio

O negócio por trás do sistema é simples:

- receber documentos enviados indiretamente por e-mail;
- automatizar a coleta desses documentos;
- evitar tratamento manual da caixa de entrada;
- centralizar tudo em uma listagem web acessível.

O valor do projeto está em reduzir trabalho operacional.

## 4. Fluxo Esperado do Sistema

Fluxo desenhado pela documentação:

1. o scheduler roda a cada 15 minutos;
2. o comando `email:poll` consulta o e-mail via IMAP;
3. somente mensagens não lidas devem ser consideradas;
4. apenas o remetente autorizado deve ser processado;
5. a primeira URL válida do corpo deve ser extraída;
6. o documento deve ser baixado;
7. o arquivo deve ir para `storage/app/public/documents/AAAA/MM/`;
8. o banco deve receber um registro na tabela `documents`;
9. o e-mail deve ser marcado como lido;
10. o painel `/documentos` deve listar os documentos processados.

## 5. O Que a Implementação Atual Faz de Verdade

A aplicação real em `docs-monitor/` já tem:

- comando agendado em `routes/console.php`;
- rota `/documentos`;
- componente Livewire para listagem;
- model `Document`;
- migration da tabela `documents`;
- job de processamento;
- integração IMAP configurável;
- storage público linkado;
- um PDF já salvo em `storage/app/public/documents/2026/04/`.

### Comportamento atual do polling

Arquivo: `docs-monitor/app/Console/Commands/EmailPollChecker.php`

- busca e-mails `unseen`;
- usa janela de `2 dias`, não de `25 minutos`;
- filtra remetente manualmente dentro do loop;
- despacha `ProcessNewEmailJob` só para remetente autorizado;
- marca **todos** os e-mails encontrados como lidos, inclusive os ignorados.

### Comportamento atual do job

Arquivo: `docs-monitor/app/Jobs/ProcessNewEmailJob.php`

- abre a mensagem pelo UID;
- lê HTML/texto do e-mail;
- procura especificamente um link do **Google Drive**;
- converte o link para download direto;
- baixa o conteúdo com timeout de 60 segundos;
- salva sempre como PDF com nome `Digitalizado_YYYYmmdd_His.pdf`;
- grava o arquivo no disco `public`;
- marca o e-mail como lido.

### Limitação importante do estado atual

Apesar da regra de negócio e da migration preverem persistência em banco, o job atual **não cria registro na tabela `documents`**.

Resultado prático:

- a tabela `documents` existe;
- o painel tenta listar registros do banco;
- mas a base está vazia;
- então o arquivo pode existir no storage sem aparecer no painel.

## 6. Diferença Entre Planejado e Implementado

Esta é a principal verdade técnica do projeto hoje.

### Planejado na documentação

- Laravel 11
- Livewire 3
- extração genérica da primeira URL válida
- extensões permitidas: `pdf`, `doc`, `docx`, `xls`, `xlsx`, `zip`, `rar`
- persistência com `firstOrCreate`
- janela de busca de 25 minutos

### Encontrado no código atual

- Laravel 13.2.0
- Livewire 4.2.3
- busca focada em link do Google Drive
- arquivo salvo sempre como PDF
- sem persistência em `documents`
- busca por e-mails dos últimos 2 dias
- `.env.example` usa `IMAP_DEFAULT_*`, mas `config/imap.php` lê `IMAP_HOST`, `IMAP_PORT`, `IMAP_USERNAME`, `IMAP_PASSWORD`

### Conclusão técnica

O projeto tem uma boa base, mas está em um estado de **implementação parcial**.

Ele já possui:

- estrutura do domínio;
- scheduler;
- command;
- queue/job;
- interface;
- storage;
- configuração IMAP.

Mas ainda precisa alinhar:

- persistência no banco;
- consistência das variáveis de ambiente;
- aderência total às regras de negócio descritas em `docs/`;
- atualização da documentação para refletir Laravel 13 e Livewire 4, caso essa mudança seja intencional.

## 7. Regras de Negócio Consolidadas

Com base em `docs/` e `.agent/`, as regras de negócio pretendidas do projeto são:

1. o sistema deve funcionar por **polling**, nunca por IDLE;
2. a verificação deve ocorrer a cada 15 minutos;
3. apenas mensagens não lidas devem entrar no fluxo;
4. apenas mensagens do remetente autorizado devem ser processadas;
5. o sistema deve extrair o assunto completo;
6. deve capturar a primeira URL válida do corpo do e-mail;
7. deve baixar o documento com timeout de 60 segundos;
8. deve salvar o arquivo no disco público organizado por ano e mês;
9. deve evitar duplicidade via `firstOrCreate` usando `original_url`;
10. o e-mail deve ser marcado como lido ao final do tratamento;
11. o painel deve exibir documentos em ordem decrescente de `received_at`.

## 8. Arquitetura Atual

### Backend

- Framework: Laravel
- Queue: database
- Scheduler: Laravel Scheduler
- Persistência: SQLite local no ambiente atual
- Storage: disco `public`
- Integração de e-mail: `directorytree/imapengine-laravel`

### Frontend

- Blade
- Tailwind CSS 4
- Livewire 4
- rota principal de negócio: `/documentos`

## 9. Banco de Dados

Tabela principal de domínio:

- `documents`

Campos:

- `subject`
- `original_url`
- `file_path`
- `filename`
- `from_email`
- `received_at`
- timestamps

No ambiente atual:

- a tabela existe;
- não há registros nela no momento da análise.

## 10. Estado Operacional Encontrado

Durante a análise do projeto:

- `php artisan schedule:list` mostrou o agendamento de `email:poll` a cada 15 minutos;
- `php artisan route:list` confirmou a rota `/documentos`;
- `php artisan about` confirmou Laravel 13.2.0 e Livewire 4.2.3;
- `php artisan test` passou, mas apenas com os testes padrão do Laravel;
- existe um PDF salvo em `storage/app/public/documents/2026/04/`;
- a tabela `documents` está vazia.

Isso indica que o projeto já foi executado em parte, porém o fluxo completo armazenamento + banco + painel ainda não está fechado.

## 11. Como Rodar o Projeto

A aplicação Laravel está em `docs-monitor/`.

### Setup básico

```bash
cd docs-monitor
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan storage:link
```

### Ambiente

Verificar especialmente:

```env
QUEUE_CONNECTION=database
DOCUMENT_SENDER=seu-remetente@dominio.com
IMAP_HOST=imap.gmail.com
IMAP_PORT=993
IMAP_USERNAME=seu-email
IMAP_PASSWORD=sua-senha-de-app
```

Observação:

O arquivo `config/imap.php` atualmente lê `IMAP_HOST`, `IMAP_PORT`, `IMAP_USERNAME` e `IMAP_PASSWORD`.

## 12. Como Executar

### Desenvolvimento

```bash
cd docs-monitor
composer run dev
```

### Testes

```bash
cd docs-monitor
php artisan test
```

### Polling manual

```bash
cd docs-monitor
php artisan email:poll
```

### Worker de fila

```bash
cd docs-monitor
php artisan queue:work
```

### Painel

Abrir:

```text
/documentos
```

## 13. Skills, Agentes e Modo de Trabalho Recomendado

Este projeto foi claramente preparado para trabalhar bem com agentes de IA.

### Fontes de contexto que devem ser lidas primeiro

Ao iniciar qualquer tarefa neste repositório, o agente deve ler nesta ordem:

1. `REDMIN.MD`
2. `docs/00-README.md`
3. `docs/03-REGRAS-DE-NEGOCIO.md`
4. `.agent/Skill.md`
5. arquivos reais do fluxo em `docs-monitor/app/...`

### Como usar as skills neste projeto

Se estiver usando Codex, Claude Code ou agente similar, trate a pasta `.agent/` como a memória operacional do projeto.

Uso recomendado:

- `Skill.md`: define a missão macro do sistema;
- `geral.md`: ajuda a entender a estratégia do MVP;
- `regrasdenegocio.md`: serve como checklist funcional;
- `tecnologias.md`: orienta stack e padrões esperados;
- `uteisdocs.md`: aponta documentação externa relevante.

### Regra prática para agentes

Antes de alterar código:

1. compare a documentação com o comportamento real;
2. registre se a mudança vai seguir o planejado ou o implementado;
3. evite assumir que `docs/` e `docs-monitor/` estão 100% alinhados;
4. preserve a ideia principal do projeto: simplicidade, polling e baixo custo operacional.

### Tipos de tarefa e contexto mínimo

#### Se a tarefa for de negócio

Ler:

- `docs/03-REGRAS-DE-NEGOCIO.md`
- `.agent/regrasdenegocio.md`
- `docs-monitor/app/Console/Commands/EmailPollChecker.php`
- `docs-monitor/app/Jobs/ProcessNewEmailJob.php`

#### Se a tarefa for de interface

Ler:

- `docs-monitor/app/Livewire/DocumentsPanel.php`
- `docs-monitor/resources/views/livewire/documents-panel.blade.php`

#### Se a tarefa for de infraestrutura

Ler:

- `docs-monitor/routes/console.php`
- `docs-monitor/config/imap.php`
- `docs-monitor/composer.json`
- `docs-monitor/.env.example`

### Prompt base sugerido para novos agentes

```text
Leia REDMIN.MD, depois compare docs/, .agent/ e docs-monitor/.
Identifique primeiro o comportamento real do sistema antes de editar.
Sempre destaque divergências entre a especificação e o código atual.
Ao implementar, preserve o modelo de polling a cada 15 minutos e o fluxo baseado em IMAP.
```

## 14. Pendências Mais Prováveis

Os próximos trabalhos mais úteis neste projeto parecem ser:

1. fazer o job salvar corretamente na tabela `documents`;
2. alinhar `config/imap.php` com `.env.example`;
3. decidir se o produto é genérico para várias URLs/extensões ou focado em Google Drive PDF;
4. alinhar a janela de busca para 25 minutos, se essa regra continuar válida;
5. adicionar testes reais para command, job e persistência;
6. trocar o `README.md` padrão do Laravel por documentação do produto.

## 15. Resumo Executivo

O projeto é simples, bem direcionado e tem uma proposta clara.

A ideia central já está montada:

- polling via scheduler;
- leitura de e-mail por IMAP;
- processamento assíncrono;
- armazenamento local;
- painel web para consulta.

O principal ponto de atenção é que o repositório contém uma diferença entre **visão/documentação** e **estado real do código**. Qualquer evolução futura deve começar por esse alinhamento.

Se alguém novo entrar no projeto, este é o entendimento correto:

- a visão do produto está em `docs/` e `.agent/`;
- a verdade operacional está em `docs-monitor/`;
- hoje o sistema está próximo de funcionar ponta a ponta, mas ainda não conclui toda a persistência prevista.
