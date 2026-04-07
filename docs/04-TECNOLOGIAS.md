# Tecnologias - Docs Monitor

## Stack Tecnológica Completa

| Camada | Tecnologia | Versão / Motivo |
|--------|------------|-----------------|
| **Framework** | Laravel | 11 (versão mais atual em 2026) - padrão da indústria |
| **IMAP** | DirectoryTree/ImapEngine | Única biblioteca necessária - moderna, leve, excelente suporte a buscas |
| **Agendamento** | Laravel Scheduler | Para rodar o polling automaticamente a cada 15 minutos |
| **Processamento** | Laravel Queue + Jobs | Processamento assíncrono (evita travar o scheduler) |
| **Banco de Dados** | Eloquent ORM + Migration | Padrão do Laravel para persistência |
| **Storage** | Laravel Storage (disco `public`) | Fácil, com `storage:link`, pode migrar para S3 depois |
| **Interface** | Livewire 3 | Componentes reativos sem precisar de Vue/React |
| **Frontend** | Blade + Tailwind CSS | Vem junto com Jetstream ou Breeze |
| **Logs** | Laravel Logging | `info()`, `error()`, etc. |
| **Execução** | Cron + Queue Worker | Para rodar o scheduler e a fila |

## Pacotes Composer

### Pacotes Principais

```json
{
    "require": {
        "php": "^8.2",
        "laravel/framework": "^11.0",
        "directorytree/imap-engine": "^1.0",
        "directorytree/imapengine-laravel": "^1.0",
        "livewire/livewire": "^3.0"
    }
}
```

### Instalação

```bash
# ImapEngine
composer require directorytree/imap-engine directorytree/imapengine-laravel

# Publicar configuração
php artisan vendor:publish --provider="DirectoryTree\ImapEngine\Laravel\ImapServiceProvider" --tag=config

# Livewire (se não estiver instalado)
composer require livewire/livewire

# Link do storage
php artisan storage:link
```

## Configuração do Ambiente

### Variáveis .env

```env
# ============================================
# IMAP Configuration (Gmail)
# ============================================
IMAP_DEFAULT_HOST=imap.gmail.com
IMAP_DEFAULT_PORT=993
IMAP_DEFAULT_USERNAME=seuemaildocs@gmail.com
IMAP_DEFAULT_PASSWORD=abcdefghijklmnop  # Senha de App de 16 caracteres

# ============================================
# Document Sender (Remetente Autorizado)
# ============================================
DOCUMENT_SENDER=documentos@seusistema.com

# ============================================
# Queue Configuration
# ============================================
QUEUE_CONNECTION=database

# ============================================
# Database (padrão Laravel)
# ============================================
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=docs_monitor
DB_USERNAME=root
DB_PASSWORD=
```

## Estrutura de Pastas

```
app/
├── Console/
│   ├── Commands/
│   │   └── EmailPollChecker.php          # Comando: email:poll
│   └── Kernel.php                        # Scheduler config
├── Jobs/
│   └── ProcessNewEmailJob.php          # Job de processamento
├── Livewire/
│   └── DocumentsPanel.php                # Componente Livewire
├── Models/
│   └── Document.php                      # Model Eloquent
config/
├── imap.php                              # Config ImapEngine (publicado)
├── queue.php                             # Config Queue
└── filesystems.php                       # Config Storage
database/
├── migrations/
│   └── 2026_03_30_000000_create_documents_table.php
└── seeders/
resources/
└── views/
    └── livewire/
        └── documents-panel.blade.php     # View do painel
routes/
└── web.php                               # Rota /documentos
storage/
└── app/
    └── public/
        └── documents/                    # Arquivos baixados
```

## Documentação de Referência

### DirectoryTree/ImapEngine

- **Documentação oficial**: https://imapengine.com/docs/laravel/introduction
- **Instalação**: https://imapengine.com/docs/laravel/installation
- **Uso básico**: https://imapengine.com/docs/laravel/usage
- **Repositório Core**: https://github.com/DirectoryTree/ImapEngine
- **Repositório Laravel**: https://github.com/DirectoryTree/ImapEngine-Laravel

**Métodos úteis:**
- `messages()->unseen()` - Busca não lidos
- `messages()->from('email')` - Filtra por remetente
- `messages()->since(Carbon $date)` - Filtra por data
- `markSeen()` - Marca como lido

### Laravel Scheduler

- **Documentação**: https://laravel.com/docs/11.x/scheduling

**Métodos úteis:**
- `->everyFifteenMinutes()` - A cada 15 minutos
- `->withoutOverlapping()` - Evita sobreposição
- `->runInBackground()` - Roda em background

### Laravel Queues

- **Documentação**: https://laravel.com/docs/11.x/queues

**Conceitos:**
- Criar Job: `php artisan make:job NomeDoJob`
- Dispatch: `NomeDoJob::dispatch($param)`
- Rodar worker: `php artisan queue:work`

### Laravel Livewire

- **Site oficial**: https://livewire.laravel.com/
- **Documentação v3**: https://livewire.laravel.com/docs/3.x/quickstart
- **Instalação**: https://livewire.laravel.com/docs/3.x/installation

### Laravel Storage

- **Documentação**: https://laravel.com/docs/11.x/filesystem

**Métodos úteis:**
- `Storage::disk('public')->put($path, $content)`
- `Storage::disk('public')->url($path)`
- `Storage::disk('public')->exists($path)`

### Laravel Eloquent

- **Documentação**: https://laravel.com/docs/11.x/eloquent

**Métodos úteis:**
- `Model::firstOrCreate($attributes, $values)`
- `Model::latest()->get()`

## Padrões e Boas Práticas

| Padrão | Aplicação | Benefício |
|--------|-----------|-----------|
| **Single Responsibility** | Cada arquivo tem uma única responsabilidade | Código limpo e fácil de manter |
| **Separation of Concerns** | Busca no IMAP no Command → Processamento no Job → Exibição no Livewire | Facilita testes e depuração |
| **Queue / Async Processing** | Todo trabalho pesado (download + salvar) fica dentro do Job | Scheduler não trava |
| **Idempotência** | Usamos `firstOrCreate` baseado na URL original | Evita duplicar documentos |
| **Fail-Safe** | Se o download falhar, apenas marca como lido e continua | Sistema robusto |
| **Config via .env** | Credenciais do Gmail, remetente, etc. ficam no `.env` | Segurança e flexibilidade |
| **Fluent API** | Usamos a API fluente do ImapEngine (`messages()->unseen()->from()`) | Código legível |
| **Convention over Configuration** | Seguimos padrões do Laravel (nomenclatura de comandos, jobs, models) | Menos configuração manual |
| **Logging** | Uso de `info()`, `warn()` e `error()` no Command e Job | Fácil monitorar |

## O Que NÃO Usamos

| Tecnologia | Motivo da Exclusão |
|------------|-------------------|
| **IMAP IDLE** | Complexidade desnecessária, polling é suficiente |
| **Supervisor** | No início, podemos rodar queue manualmente ou usar cron simples |
| **Redis** | Database queue é suficiente para o volume inicial |
| **Vue/React** | Livewire 3 cobre todas as necessidades de reatividade |
| **WebSockets** | Não necessário para polling de 15 em 15 minutos |

## Configuração de Produção

### Cron para Scheduler

```bash
# Editar crontab
crontab -e

# Adicionar linha (roda o scheduler a cada minuto)
* * * * * cd /caminho/do/projeto && php artisan schedule:run >> /dev/null 2>&1
```

### Supervisor (opcional, para filas)

```ini
# /etc/supervisor/conf.d/laravel-worker.conf
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /caminho/do/projeto/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/laravel-worker.log
stopwaitsecs=3600
```

### Permissões

```bash
# Storage
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/

# Ownership (ajuste conforme seu servidor)
chown -R www-data:www-data storage/
chown -R www-data:www-data bootstrap/cache/
```

## Documentação Relacionada

- [00-README.md](00-README.md) - Visão geral
- [01-ESTRATEGIA.md](01-ESTRATEGIA.md) - Estratégia completa
- [02-MVP.md](02-MVP.md) - Escopo do MVP
- [03-REGRAS-DE-NEGOCIO.md](03-REGRAS-DE-NEGOCIO.md) - Regras detalhadas
- [05-CHECKLIST-IMPLEMENTACAO.md](05-CHECKLIST-IMPLEMENTACAO.md) - Checklist de implementação
