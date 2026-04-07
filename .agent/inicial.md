Você é um Arquiteto Sênior de Software especializado em Laravel 11 + agentes de IA (Claude Code).
Você trabalha com o padrão ReAct + Chain of Thought + Planning + Tool Use.

Eu tenho exatamente estas pastas no projeto atual:

1. Pasta principal (raiz do projeto Laravel)
2. Uma pasta chamada ".agent" (ou similar) que contém:
   - Estratégia completa do sistema/home/elieldiniz/ workspace/integração-gmail-laravel/.agent/geral.md
   - MVP do sistema
   - Regras de negócio MVP/home/elieldiniz/workspace/integração-gmail-laravel/.agent/regrasdenegocio.md
   - Tecnologias MVP/home/elieldiniz/workspace/integração-gmail-laravel/.agent/tecnologias.md
   - UT Docs MVP (com links úteis de documentação)/home/elieldiniz/workspace/integração-gmail-laravel/.agent/uteisdocs.md
     skill:/home/elieldiniz/workspace/integração-gmail-laravel/.agent/Skill.md

Tarefa completa (faça em ordem, sem pular etapas):

PASSO 1 – EXPLORAÇÃO

- Use suas ferramentas para listar todas as pastas e arquivos existentes no diretório atual e subdiretórios.
- Leia TODOS os arquivos das pastas mencionadas acima (estratégia, MVP, regras de negócio, tecnologias, UT Docs MVP).
- Entenda 100% o projeto: é um sisteminha Laravel chamado "Docs Monitor" que monitora e-mail via IMAP (apenas Polling a cada 15 minutos, sem IDLE).

PASSO 2 – CRIAÇÃO DA DOCUMENTAÇÃO

- Crie uma pasta nova chamada `docs/` na raiz do projeto.
- Dentro de `docs/` crie os seguintes arquivos Markdown bem organizados:
  - `00-README.md` (visão geral do projeto)
  - `01-ESTRATEGIA.md`
  - `02-MVP.md`
  - `03-REGRAS-DE-NEGOCIO.md`
  - `04-TECNOLOGIAS.md`
  - `05-CHECKLIST-IMPLEMENTACAO.md` ← este é o mais importante

PASSO 3 – CHECKLIST DETALHADO
No arquivo `05-CHECKLIST-IMPLEMENTACAO.md` crie um checklist completo, fase por fase e parte por parte, com o seguinte formato:

# Checklist de Implementação - Docs Monitor (Polling Only)

## Fase 1: Configuração Inicial

- [ ] ...

## Fase 2: Banco de Dados e Model

- [ ] ...

## Fase 3: Job de Processamento

- [ ] ...

## Fase 4: Command de Polling

- [ ] ...

## Fase 5: Scheduler

- [ ] ...

## Fase 6: Livewire Panel

- [ ] ...

## Fase 7: Rotas e Testes

- [ ] ...

Cada item deve ser claro, com descrição curta do que fazer e qual arquivo será criado/editado.

PASSO 4 – RELATÓRIO FINAL
Depois de terminar todos os passos acima, responda com:

1. Resumo do que foi lido nas pastas.
2. Confirmação de que a pasta `docs/` foi criada.
3. Conteúdo completo do arquivo `05-CHECKLIST-IMPLEMENTACAO.md`.
4. Instrução clara: "Amanhã podemos começar a implementação seguindo exatamente este checklist fase por fase. Diga 'INICIAR FASE 1' quando quiser começar."

Regras obrigatórias:

- Use apenas Polling (Laravel Scheduler a cada 15 minutos).
- Nunca use IDLE.
- Tecnologias exatas: Laravel 11, DirectoryTree/ImapEngine, Laravel Queue, Livewire 3, Storage public.
- Seja extremamente organizado e use boas práticas de 2026.
- Não crie código ainda — apenas a documentação e o checklist.

Comece agora executando o PASSO 1.
