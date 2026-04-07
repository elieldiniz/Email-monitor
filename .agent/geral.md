✅ **Entendido perfeitamente!**

Essa é a versão **mais simples, leve e estável** para começar.  
Consumo de memória/CPU muito baixo, fácil de manter e perfeita para um sisteminha de teste.

---

### 1. Estratégia Completa do Sisteminha (Apenas Polling)

**Como o sistema vai funcionar:**

- A cada **15 minutos**, o Laravel Scheduler vai rodar automaticamente um comando (`email:poll`).
- Esse comando vai se conectar ao Gmail via IMAP.
- Vai buscar **apenas** os e-mails **não lidos** (`unseen`) vindos de um **remetente específico**.
- Para cada e-mail encontrado, vai disparar um Job que:
  - Pega o **subject**
  - Extrai a **URL** do corpo do e-mail
  - Baixa o documento da URL
  - Salva o arquivo no Storage
  - Salva as informações no banco de dados
- Depois de processar, marca o e-mail como lido.
- Você terá um **painel simples** (Livewire) para visualizar todos os documentos já salvos.

**Vantagens dessa abordagem:**

- Muito leve (não fica processo rodando 24h)
- Fácil de configurar e depurar
- Alta confiabilidade (não depende de conexão persistente)
- Ideal para começar e validar o fluxo

**Desvantagem:**

- O documento pode demorar até 15 minutos para aparecer no painel (aceitável para o início)

---

### 2. Regras de Negócio Completas (Mesmas, mas adaptadas)

- Só processa e-mails do **remetente específico** configurado.
- Só processa e-mails **não lidos** (`unseen`).
- Captura o **subject** completo.
- Extrai a **primeira URL** válida do corpo (pdf, doc, docx, xlsx, zip, rar).
- Baixa o arquivo da URL.
- Salva o arquivo em `storage/app/public/documents/ANO/MÊS/`.
- Salva no banco: subject, URL original, caminho do arquivo, nome do arquivo, remetente e data.
- Evita duplicidade usando `firstOrCreate` pela URL.
- Após processar, marca o e-mail como lido.
- Exibe lista no painel com link para download.

---

### 3. Tecnologias Utilizadas (Mesma stack, mais simples)

- **Laravel 11**
- **DirectoryTree/ImapEngine** (única biblioteca para IMAP)
- **Laravel Scheduler** + **Command** (para polling)
- **Laravel Queue** (Jobs)
- **Eloquent** + Migration
- **Livewire 3** (painel)
- **Laravel Storage** (disco public)
- **Supervisor** (opcional, só se quiser rodar queue worker)

---

### 4. Implementação Completa (Código Pronto - Apenas Polling)

#### Passo 1: Instalações

```bash
composer require directorytree/imap-engine directorytree/imapengine-laravel

php artisan vendor:publish --provider="DirectoryTree\ImapEngine\Laravel\ImapServiceProvider" --tag=config

php artisan storage:link
```

#### Passo 2: Configuração `.env`

```env
IMAP_DEFAULT_HOST=imap.gmail.com
IMAP_DEFAULT_PORT=993
IMAP_DEFAULT_USERNAME=seuemaildocs@gmail.com
IMAP_DEFAULT_PASSWORD=abcdefghijklmnop   # Senha de App de 16 caracteres

QUEUE_CONNECTION=database
```

#### Passo 3: Model + Migration

```bash
php artisan make:model Document -m
```

**Migration:**

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

```bash
php artisan migrate
```

#### Passo 4: Job - ProcessNewEmailJob

```bash
php artisan make:job ProcessNewEmailJob
```

**app/Jobs/ProcessNewEmailJob.php**

```php
<?php

namespace App\Jobs;

use App\Models\Document;
use DirectoryTree\ImapEngine\Mailbox;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProcessNewEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $uid,
        public string $folderName = 'INBOX'
    ) {}

    public function handle()
    {
        $mailbox = new Mailbox(config('imap.connections.default'));
        $folder = $mailbox->folder($this->folderName);

        $message = $folder->messages()
            ->withBody()
            ->withHeaders()
            ->find($this->uid);

        if (!$message) return;

        $subject = $message->subject();
        $from    = $message->from()->email();
        $body    = $message->body() ?? $message->htmlBody() ?? '';

        // Regra: só processa remetente específico
        $remetenteCorreto = 'documentos@seusistema.com'; // ← MUDE AQUI

        if (strtolower($from) !== strtolower($remetenteCorreto)) {
            $message->markSeen();
            return;
        }

        // Extrai URL
        preg_match('/https?:\/\/[^\s<>"\']+\.(pdf|docx?|xlsx?|zip|rar)/i', $body, $match);
        $url = $match[0] ?? null;

        if (!$url) {
            $message->markSeen();
            return;
        }

        // Baixa o arquivo
        $response = Http::timeout(60)->get($url);
        if (!$response->successful()) {
            $message->markSeen();
            return;
        }

        $filename = basename(parse_url($url, PHP_URL_PATH)) ?: 'doc_' . now()->timestamp . '.pdf';
        $path = 'documents/' . date('Y/m/') . Str::slug($filename);

        Storage::disk('public')->put($path, $response->body());

        // Salva no banco (evita duplicata)
        Document::firstOrCreate(
            ['original_url' => $url],
            [
                'subject'      => $subject,
                'original_url' => $url,
                'file_path'    => $path,
                'filename'     => $filename,
                'from_email'   => $from,
                'received_at'  => $message->date(),
            ]
        );

        $message->markSeen();

        info("✅ Documento salvo via polling: {$filename}");
    }
}
```

#### Passo 5: Comando de Polling

```bash
php artisan make:command EmailPollChecker --command=email:poll
```

**app/Console/Commands/EmailPollChecker.php**

```php
<?php

namespace App\Console\Commands;

use App\Jobs\ProcessNewEmailJob;
use DirectoryTree\ImapEngine\Mailbox;
use Illuminate\Console\Command;

class EmailPollChecker extends Command
{
    protected $signature = 'email:poll';
    protected $description = 'Verifica novos documentos a cada 15 minutos via Polling';

    public function handle()
    {
        $this->info('🔄 Iniciando verificação de novos documentos...');

        $mailbox = new Mailbox(config('imap.connections.default'));
        $inbox = $mailbox->inbox();

        $messages = $inbox->messages()
            ->unseen()
            ->from('documentos@seusistema.com')        // ← Mesmo remetente
            ->since(now()->subMinutes(25))
            ->get();

        $count = $messages->count();

        if ($count === 0) {
            $this->info('Nenhum e-mail novo encontrado.');
            return;
        }

        $this->info("📬 Encontrados {$count} e-mail(s) para processar.");

        foreach ($messages as $message) {
            ProcessNewEmailJob::dispatch($message->uid());
        }
    }
}
```

#### Passo 6: Agendamento no Kernel

Em `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('email:poll')
             ->everyFifteenMinutes()
             ->withoutOverlapping()
             ->runInBackground();
}
```

#### Passo 7: Painel Livewire (mesmo de antes)

```bash
php artisan make:livewire DocumentsPanel
```

Use o mesmo código do Livewire e da view que te passei anteriormente.

---

### Como testar agora

1. Configure o `.env` com Gmail + Senha de App.
2. Rode `php artisan migrate`
3. Rode `php artisan queue:table && php artisan migrate` (se usar queue)
4. Rode o scheduler manualmente para testar:
   ```bash
   php artisan schedule:run
   ```
5. Envie um e-mail de teste com uma URL no corpo.
6. Aguarde até 15 minutos ou rode o comando manualmente:
   ```bash
   php artisan email:poll
   ```
