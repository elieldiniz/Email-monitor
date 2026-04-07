### Tecnologias a serem utilizadas

| Camada                    | Tecnologia                           | Versão / Motivo                                                              |
| ------------------------- | ------------------------------------ | ---------------------------------------------------------------------------- |
| Framework                 | **Laravel**                          | 11 (versão mais atual em 2026) – padrão da indústria                         |
| IMAP (leitura de e-mail)  | **DirectoryTree/ImapEngine**         | Única biblioteca necessária – moderna, leve e com excelente suporte a buscas |
| Agendamento               | **Laravel Scheduler**                | Para rodar o polling automaticamente a cada 15 minutos                       |
| Processamento             | **Laravel Queue + Jobs**             | Processamento assíncrono (evita travar o scheduler)                          |
| Banco de Dados            | **Eloquent ORM** + Migration         | Padrão do Laravel para persistência                                          |
| Armazenamento de arquivos | **Laravel Storage** (disco `public`) | Fácil, com `storage:link`, pode migrar para S3 depois                        |
| Interface (Painel)        | **Livewire 3**                       | Componentes reativos sem precisar de Vue/React – simples e integrado         |
| Frontend                  | **Blade + Tailwind CSS**             | Vem junto com Jetstream ou Breeze                                            |
| Logs e Debug              | **Laravel Logging**                  | `info()`, `error()` etc.                                                     |
| Execução em produção      | **Cron + Queue Worker**              | Para rodar o scheduler e a fila                                              |

**Bibliotecas Composer principais:**

- `directorytree/imap-engine`
- `directorytree/imapengine-laravel` (facilita configuração)
- Livewire (já incluso se usar Jetstream)

**Não vamos usar:**

- IMAP IDLE (socket persistente)
- Supervisor (no início)
- Redis (pode usar `database` como driver da queue)

---

### Padrões e Boas Práticas que vamos seguir

| Padrão / Conceito                 | Como vamos aplicar                                                             | Benefício                           |
| --------------------------------- | ------------------------------------------------------------------------------ | ----------------------------------- |
| **Single Responsibility**         | Cada arquivo tem uma única responsabilidade (Command, Job, Model, Livewire)    | Código mais limpo e fácil de manter |
| **Separation of Concerns**        | Busca no IMAP no Command → Processamento no Job → Exibição no Livewire         | Facilita testes e depuração         |
| **Queue / Async Processing**      | Todo trabalho pesado (download + salvar) fica dentro do Job                    | Scheduler não trava                 |
| **Idempotência**                  | Usamos `firstOrCreate` baseado na URL original                                 | Evita duplicar documentos           |
| **Fail-Safe**                     | Se o download falhar, apenas marca como lido e continua (não quebra o polling) | Sistema robusto                     |
| **Config via .env**               | Credenciais do Gmail, remetente, etc. ficam no `.env`                          | Segurança e flexibilidade           |
| **Fluent API**                    | Usamos a API fluente do ImapEngine (`messages()->unseen()->from()->since()`)   | Código legível                      |
| **Convention over Configuration** | Seguimos padrões do Laravel (nomenclatura de comandos, jobs, models)           | Menos configuração manual           |
| **Logging**                       | Uso de `info()`, `warn()` e `error()` no Command e Job                         | Fácil monitorar                     |

---

### Estrutura de Pastas que vamos usar (padrão Laravel)

```
app/
├── Console/
│   └── Commands/
│       └── EmailPollChecker.php          ← Comando que roda a cada 15 min
├── Jobs/
│   └── ProcessNewEmailJob.php            ← Processa o e-mail e baixa documento
├── Livewire/
│   └── DocumentsPanel.php                ← Painel de visualização
├── Models/
│   └── Document.php                      ← Model do documento
config/
├── imap.php                              ← Configuração publicada do ImapEngine
database/
├── migrations/
│   └── create_documents_table.php
resources/
└── views/
    └── livewire/
        └── documents-panel.blade.php
routes/
└── web.php
storage/
└── app/public/documents/                 ← Onde os arquivos serão salvos
```

---

### Resumo do Fluxo Final (com Polling)

1. A cada 15 minutos → `php artisan schedule:run` executa `email:poll`
2. `EmailPollChecker` → conecta no Gmail e busca e-mails não lidos do remetente específico
3. Para cada e-mail → despacha `ProcessNewEmailJob`
4. Job → extrai subject + URL → baixa arquivo → salva no Storage + Banco
5. Marca e-mail como lido
6. Usuário acessa `/documentos` → vê a lista atualizada via Livewire
