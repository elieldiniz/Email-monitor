### 1. DirectoryTree/ImapEngine (principal biblioteca para IMAP)

- **Documentação oficial completa**: https://imapengine.com/docs/laravel/introduction
- **Instalação e configuração Laravel**: https://imapengine.com/docs/laravel/installation
- **Uso básico no Laravel (acesso a mailbox, mensagens, etc.)**: https://imapengine.com/docs/laravel/usage
- **Repositório principal (core)**: https://github.com/DirectoryTree/ImapEngine
- **Pacote Laravel específico**: https://github.com/DirectoryTree/ImapEngine-Laravel

**Dica**: Comece pela página de "Laravel Usage" — ela mostra como usar `Mailbox`, `messages()->unseen()->from()->since()`, etc.

### 2. Laravel Scheduler (para rodar o polling a cada 15 minutos)

- **Documentação oficial do Task Scheduling**: https://laravel.com/docs/13.x/scheduling

Principais seções úteis:

- Definir agendamentos (`everyFifteenMinutes()`, `withoutOverlapping()`, etc.)
- Rodar o scheduler via cron

### 3. Laravel Queues e Jobs (processamento assíncrono)

- **Documentação oficial de Queues**: https://laravel.com/docs/13.x/queues
- Principais tópicos:
  - Criar Jobs (`php artisan make:job`)
  - Dispatch de jobs
  - Configuração (`config/queue.php`)
  - Rodar worker (`php artisan queue:work`)

### 4. Laravel Livewire (painel de documentos)

- **Site oficial + Documentação Livewire v3**: https://livewire.laravel.com/docs/3.x/quickstart
- **Instalação**: https://livewire.laravel.com/docs/3.x/installation
- **Guia rápido de componentes**: https://livewire.laravel.com/

### 5. Outras documentações úteis do Laravel

- **Laravel Storage** (salvar arquivos baixados): https://laravel.com/docs/13.x/filesystem
- **Eloquent ORM** (Model Document): https://laravel.com/docs/13.x/eloquent
- **Migrations**: https://laravel.com/docs/13.x/migrations
- **Artisan Commands**: https://laravel.com/docs/13.x/artisan

### Links extras recomendados

- **Gmail IMAP configuração** (Senha de App + ativar IMAP):  
  https://support.google.com/mail/answer/7126229 (Senha de App)  
  https://support.google.com/mail/answer/7126229?hl=pt-BR (em português)
