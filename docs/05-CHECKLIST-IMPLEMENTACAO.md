# Checklist de Implementação - Docs Monitor (Polling Only)

> **Atenção**: Este é o guia completo para implementar o sistema passo a passo. Não pule etapas.

---

## Fase 1: Configuração Inicial

### 1.1 Criar Projeto Laravel
- [ ] Verificar se Laravel 11 está instalado globalmente
- [ ] Criar novo projeto: `laravel new docs-monitor` ou `composer create-project laravel/laravel docs-monitor`
- [ ] Acessar diretório do projeto: `cd docs-monitor`

### 1.2 Instalar Dependências
- [ ] Instalar DirectoryTree/ImapEngine:
  ```bash
  composer require directorytree/imap-engine directorytree/imapengine-laravel
  ```
- [ ] Publicar configuração do ImapEngine:
  ```bash
  php artisan vendor:publish --provider="DirectoryTree\ImapEngine\Laravel\ImapServiceProvider" --tag=config
  ```
- [ ] Criar link simbólico do storage:
  ```bash
  php artisan storage:link
  ```

**Arquivos afetados:**
- `composer.json` (atualizado automaticamente)
- `config/imap.php` (criado após publish)

---

## Fase 2: Banco de Dados e Model

### 2.1 Criar Migration e Model
- [ ] Criar model com migration:
  ```bash
  php artisan make:model Document -m
  ```

### 2.2 Editar Migration
**Arquivo:** `database/migrations/XXXX_XX_XX_create_documents_table.php`
- [ ] Adicionar campos na função `up()`:
  ```php
  public function up(): void
  {
      Schema::create('documents', function (Blueprint $table) {
          $table->id();
          $table->string('subject');
          $table->string('original_url')->nullable();
          $table->string('file_path');
          $table->string('filename');
          $table->string('from_email');
          $table->timestamp('received_at');
          $table->timestamps();
      });
  }
  ```

### 2.3 Editar Model
**Arquivo:** `app/Models/Document.php`
- [ ] Configurar fillable:
  ```php
  protected $fillable = [
      'subject',
      'original_url',
      'file_path',
      'filename',
      'from_email',
      'received_at',
  ];
  ```
- [ ] Configurar casts para dates:
  ```php
  protected $casts = [
      'received_at' => 'datetime',
  ];
  ```

### 2.4 Rodar Migration
- [ ] Executar: `php artisan migrate`

---

## Fase 3: Job de Processamento

### 3.1 Criar Job
- [ ] Criar job:
  ```bash
  php artisan make:job ProcessNewEmailJob
  ```

### 3.2 Implementar Job
**Arquivo:** `app/Jobs/ProcessNewEmailJob.php`
- [ ] Importar classes necessárias:
  ```php
  use App\Models\Document;
  use DirectoryTree\ImapEngine\Mailbox;
  use Illuminate\Support\Facades\Http;
  use Illuminate\Support\Facades\Storage;
  use Illuminate\Support\Str;
  ```
- [ ] Definir propriedades do construtor:
  ```php
  public function __construct(
      public int $uid,
      public string $folderName = 'INBOX'
  ) {}
  ```
- [ ] Implementar método `handle()` com toda a lógica:
  - Conectar ao mailbox
  - Buscar mensagem por UID
  - Validar remetente (comparar com env DOCUMENT_SENDER)
  - Extrair URL do corpo (regex para pdf, doc, docx, xlsx, zip, rar)
  - Baixar arquivo (timeout 60s)
  - Salvar no storage (documents/YYYY/MM/)
  - Salvar no banco (firstOrCreate pela original_url)
  - Marcar e-mail como lido
  - Adicionar logs

---

## Fase 4: Command de Polling

### 4.1 Criar Command
- [ ] Criar comando:
  ```bash
  php artisan make:command EmailPollChecker --command=email:poll
  ```

### 4.2 Implementar Command
**Arquivo:** `app/Console/Commands/EmailPollChecker.php`
- [ ] Importar classes:
  ```php
  use App\Jobs\ProcessNewEmailJob;
  use DirectoryTree\ImapEngine\Mailbox;
  ```
- [ ] Definir signature e description
- [ ] Implementar método `handle()`:
  - Log de início
  - Conectar ao mailbox
  - Buscar mensagens: unseen() + from(env) + since(now->subMinutes(25))
  - Para cada mensagem: dispatch ProcessNewEmailJob com o UID
  - Log de conclusão com contagem

---

## Fase 5: Scheduler

### 5.1 Configurar Kernel
**Arquivo:** `app/Console/Kernel.php` (ou `routes/console.php` no Laravel 11)
- [ ] Adicionar agendamento:
  ```php
  $schedule->command('email:poll')
           ->everyFifteenMinutes()
           ->withoutOverlapping()
           ->runInBackground();
  ```

### 5.2 Verificar Configuração de Queue
**Arquivo:** `.env`
- [ ] Verificar: `QUEUE_CONNECTION=database`

### 5.3 Criar Tabela de Jobs (se necessário)
- [ ] Se não existir, criar:
  ```bash
  php artisan queue:table
  php artisan migrate
  ```

---

## Fase 6: Livewire Panel

### 6.1 Instalar Livewire (se não estiver)
- [ ] Verificar se Livewire está instalado
- [ ] Se não: `composer require livewire/livewire`

### 6.2 Criar Componente
- [ ] Criar componente:
  ```bash
  php artisan make:livewire DocumentsPanel
  ```

### 6.3 Implementar Componente
**Arquivo:** `app/Livewire/DocumentsPanel.php`
- [ ] Criar propriedade pública para documentos
- [ ] Implementar método `mount()` ou `render()` para carregar documentos
- [ ] Ordenar por `received_at` DESC

### 6.4 Criar View
**Arquivo:** `resources/views/livewire/documents-panel.blade.php`
- [ ] Criar estrutura HTML com Tailwind CSS
- [ ] Tabela com colunas:
  - Data/Hora (d/m/Y H:i)
  - Subject
  - Nome do arquivo (link para download)
  - URL original (texto)
- [ ] Usar `Storage::url()` para links de download
- [ ] Adicionar mensagem quando não houver documentos

---

## Fase 7: Rotas e Configurações

### 7.1 Adicionar Rota
**Arquivo:** `routes/web.php`
- [ ] Adicionar rota para o painel:
  ```php
  Route::get('/documentos', DocumentsPanel::class)->name('documents.index');
  ```
- [ ] Importar componente Livewire

### 7.2 Configurar .env
**Arquivo:** `.env`
- [ ] Adicionar variáveis IMAP:
  ```env
  IMAP_DEFAULT_HOST=imap.gmail.com
  IMAP_DEFAULT_PORT=993
  IMAP_DEFAULT_USERNAME=seuemail@gmail.com
  IMAP_DEFAULT_PASSWORD=suasenha
  ```
- [ ] Adicionar remetente:
  ```env
  DOCUMENT_SENDER=documentos@seusistema.com
  ```
- [ ] Configurar queue:
  ```env
  QUEUE_CONNECTION=database
  ```

### 7.3 Atualizar .env.example
**Arquivo:** `.env.example`
- [ ] Adicionar as mesmas variáveis (sem valores reais)

---

## Fase 8: Testes e Validação

### 8.1 Testes Manuais - Setup
- [ ] Configurar Gmail com senha de app
- [ ] Verificar conexão IMAP
- [ ] Criar diretório se não existir: `mkdir -p storage/app/public/documents`

### 8.2 Testes Manuais - Polling
- [ ] Rodar comando manualmente:
  ```bash
  php artisan email:poll
  ```
- [ ] Verificar logs em `storage/logs/laravel.log`
- [ ] Verificar se jobs são criados na tabela `jobs`

### 8.3 Testes Manuais - Queue Worker
- [ ] Rodar worker manualmente:
  ```bash
  php artisan queue:work
  ```
- [ ] Enviar e-mail de teste com URL
- [ ] Verificar se documento foi baixado
- [ ] Verificar se registro foi criado no banco
- [ ] Verificar se e-mail foi marcado como lido

### 8.4 Testes Manuais - Painel
- [ ] Acessar `/documentos` no navegador
- [ ] Verificar listagem de documentos
- [ ] Testar link de download

### 8.5 Testes Manuais - Cenários de Erro
- [ ] E-mail sem URL → deve marcar como lido e ignorar
- [ ] E-mail de remetente errado → deve marcar como lido e ignorar
- [ ] URL inválida → deve marcar como lido e ignorar
- [ ] Download falha → deve marcar como lido e logar erro

---

## Fase 9: Configuração de Produção

### 9.1 Configurar Cron
- [ ] Editar crontab do servidor:
  ```bash
  crontab -e
  ```
- [ ] Adicionar entrada:
  ```
  * * * * * cd /caminho/do/projeto && php artisan schedule:run >> /dev/null 2>&1
  ```

### 9.2 Configurar Queue Worker (Opcional)
- [ ] Criar arquivo de configuração do Supervisor
- [ ] Ou rodar manualmente: `php artisan queue:work --daemon`

### 9.3 Permissões
- [ ] Configurar permissões do storage:
  ```bash
  chmod -R 775 storage/
  chmod -R 775 bootstrap/cache/
  ```

---

## Checklist Final de Validação

Antes de considerar o projeto completo, verifique:

- [ ] Sistema conecta ao Gmail IMAP com sucesso
- [ ] Scheduler executa a cada 15 minutos (verificar logs)
- [ ] Processa apenas e-mails do remetente configurado
- [ ] Extrai subject corretamente
- [ ] Extrai primeira URL válida (extensões permitidas)
- [ ] Baixa arquivo em até 60 segundos
- [ ] Salva arquivo em estrutura YYYY/MM/
- [ ] Salva registro no banco com todos os campos
- [ ] Não cria duplicatas (mesma URL)
- [ ] Sempre marca e-mail como lido
- [ ] Painel exibe documentos ordenados por data
- [ ] Download via painel funciona corretamente
- [ ] Nunca usa IDLE - apenas polling
- [ ] Processamento em Job (Queue) para não bloquear
- [ ] Tratamento de erros sem quebrar o fluxo
- [ ] Logs em todas as etapas críticas

---

## Resumo dos Arquivos Criados/Modificados

| Arquivo | Ação | Descrição |
|---------|------|-----------|
| `config/imap.php` | Criado | Configuração do ImapEngine |
| `app/Models/Document.php` | Criado | Model Eloquent |
| `database/migrations/XXXX_create_documents_table.php` | Criado | Migration da tabela |
| `app/Jobs/ProcessNewEmailJob.php` | Criado | Job de processamento |
| `app/Console/Commands/EmailPollChecker.php` | Criado | Comando de polling |
| `app/Console/Kernel.php` | Modificado | Scheduler config |
| `app/Livewire/DocumentsPanel.php` | Criado | Componente Livewire |
| `resources/views/livewire/documents-panel.blade.php` | Criado | View do painel |
| `routes/web.php` | Modificado | Rota /documentos |
| `.env` | Modificado | Credenciais IMAP + DOCUMENT_SENDER |
| `.env.example` | Modificado | Template de variáveis |

---

## Próximos Passos

Após completar este checklist:

1. **Teste completo** com e-mails reais
2. **Monitore os logs** por alguns dias
3. **Ajuste fino** se necessário
4. **Documente** quaisquer alterações feitas

---

## Comandos Úteis para Referência

```bash
# Verificar status do sistema
php artisan schedule:list

# Rodar polling manualmente (para testes)
php artisan email:poll

# Rodar queue worker
php artisan queue:work

# Verificar logs em tempo real
tail -f storage/logs/laravel.log

# Limpar cache de configuração (após alterar .env)
php artisan config:clear

# Verificar rotas
php artisan route:list
```

---

**Documentação relacionada:**
- [00-README.md](00-README.md) - Visão geral
- [01-ESTRATEGIA.md](01-ESTRATEGIA.md) - Estratégia completa
- [02-MVP.md](02-MVP.md) - Escopo do MVP
- [03-REGRAS-DE-NEGOCIO.md](03-REGRAS-DE-NEGOCIO.md) - Regras detalhadas
- [04-TECNOLOGIAS.md](04-TECNOLOGIAS.md) - Stack tecnológica

---

**Pronto para começar a implementação!**

Diga "INICIAR FASE 1" quando quiser começar.
